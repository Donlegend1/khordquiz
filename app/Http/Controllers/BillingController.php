<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\PaddleGateway;
use App\Services\Billing\PaddleSubscriptionSync;
use App\Services\Billing\StripeGateway;
use App\Services\Billing\SubscriptionSync;
use App\Support\Plans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

// Web billing. New purchases go through Paddle (or Stripe Checkout when Paddle
// isn't configured). Changes to an existing plan — cancel, resume, Monthly ⇄
// Yearly, the billing portal — go to whichever provider bills that member, and
// are mirrored back by PaddleSubscriptionSync / SubscriptionSync (here, and
// again when the provider's webhook arrives).
class BillingController extends Controller
{
    // The Subscription page's cancel reasons → Stripe's cancellation feedback.
    private const CANCEL_FEEDBACK = [
        'Too expensive' => 'too_expensive',
        "I'm not using it enough" => 'unused',
        "It's missing a feature I need" => 'missing_features',
        'I found a better alternative' => 'switched_service',
        'Technical issues or bugs' => 'low_quality',
        'Other' => 'other',
    ];

    public function __construct(
        private readonly StripeGateway $stripe,
        private readonly SubscriptionSync $sync,
        private readonly PaddleGateway $paddle,
        private readonly PaddleSubscriptionSync $paddleSync,
    ) {}

    public function checkout(Request $request, string $plan): Response
    {
        abort_unless(in_array($plan, ['monthly', 'yearly', 'lifetime'], true), 404);
        $this->ensureConfigured();

        /** @var User $user */
        $user = $request->user();
        $subscription = Subscription::forUser($user);

        if ($this->isStoreManaged($subscription)) {
            return $this->backWithError(__('Your plan is managed through the :provider. Change it from the KhordQuiz app.', ['provider' => $subscription->provider]));
        }

        // Already on a recurring web plan: switching happens on the page, not
        // through a second subscription. And Lifetime can't be bought twice.
        $hasLifetime = $subscription->plan_id === 'lifetime' && $subscription->access === 'paid' && $subscription->status !== 'expired';
        if ($hasLifetime || ($plan !== 'lifetime' && $this->hasLiveWebSubscription($subscription))) {
            return redirect()->route('subscription');
        }

        return $this->startCheckout($user, $plan);
    }

    // Sends the member to checkout for a plan. Also used right after signup
    // (SignupController), so a new member pays (or starts the trial) before
    // onboarding.
    public function startCheckout(User $user, string $plan): Response
    {
        if ($this->paddle->isConfigured()) {
            return $this->startPaddleCheckout($user, $plan);
        }

        $subscription = Subscription::forUser($user);

        $params = [
            'customer' => $this->stripeCustomerId($user, $subscription),
            'client_reference_id' => (string) $user->id,
            'line_items' => [['price' => $this->sync->priceFor($plan), 'quantity' => 1]],
            'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('subscription'),
            'allow_promotion_codes' => true,
            'locale' => 'auto',
            'metadata' => ['user_id' => (string) $user->id, 'plan_id' => $plan],
        ];

        if ($plan === 'lifetime') {
            $params['mode'] = 'payment';
        } else {
            $params['mode'] = 'subscription';
            $params['subscription_data'] = ['metadata' => ['user_id' => (string) $user->id]];

            // One free trial per member, same as the mobile app.
            if (! $subscription->trial_used && $subscription->access === 'none') {
                $params['subscription_data']['trial_period_days'] = Plans::TRIAL_DAYS;
            }
        }

        return Inertia::location($this->stripe->createCheckoutSession($params)['url']);
    }

    // Paddle: the transaction is created here with the API key (price, trial,
    // customer and our user id are all decided server-side), then the member is
    // sent to the pay page, where Paddle.js opens the overlay checkout for it.
    private function startPaddleCheckout(User $user, string $plan): RedirectResponse
    {
        $subscription = Subscription::forUser($user);

        if (! $subscription->paddle_customer_id) {
            $subscription->update([
                'paddle_customer_id' => $this->paddle->findOrCreateCustomer($user->email, $user->name, ['user_id' => (string) $user->id]),
            ]);
        }

        // One free trial per member, same as the mobile app.
        $withTrial = $plan !== 'lifetime' && ! $subscription->trial_used && $subscription->access === 'none';

        $transaction = $this->paddle->createTransaction([
            'items' => [$this->paddleSync->transactionItem($plan, $withTrial)],
            'customer_id' => $subscription->paddle_customer_id,
            'collection_mode' => 'automatic',
            'custom_data' => ['user_id' => (string) $user->id, 'plan_id' => $plan],
        ]);

        return redirect()->route('billing.pay', ['transaction' => $transaction['id']]);
    }

    // The page Paddle's overlay checkout opens on (see Billing/PaddleCheckout.jsx).
    // Also the "default payment link" in Paddle's checkout settings: links
    // Paddle builds itself arrive as ?_ptxn=txn_….
    public function pay(Request $request): InertiaResponse
    {
        $transactionId = (string) ($request->query('transaction') ?? $request->query('_ptxn'));
        abort_unless(preg_match('/^txn_[a-z0-9]+$/', $transactionId) === 1, 404);
        $this->ensureConfigured();

        return Inertia::render('Billing/PaddleCheckout', [
            'clientToken' => $this->paddle->clientToken(),
            'sandbox' => $this->paddle->isSandbox(),
            'transactionId' => $transactionId,
            'successUrl' => route('billing.success', ['transaction_id' => $transactionId]),
            'cancelUrl' => route('subscription'),
        ]);
    }

    // The member lands here after paying. Sync right away so the plan shows up
    // immediately rather than whenever the webhook lands.
    public function success(Request $request): RedirectResponse
    {
        $this->ensureConfigured();
        $transactionId = (string) $request->query('transaction_id');
        $sessionId = (string) $request->query('session_id');

        if ($transactionId !== '') {
            $transaction = $this->paddle->getTransaction($transactionId);
            abort_unless((string) ($transaction['custom_data']['user_id'] ?? '') === (string) $request->user()->id, 403);
            $this->paddleSync->completeTransaction($transaction);
        } elseif ($sessionId !== '') {
            $session = $this->stripe->retrieveCheckoutSession($sessionId);
            abort_unless(($session['client_reference_id'] ?? null) === (string) $request->user()->id, 403);
            $this->sync->completeCheckout($session);
        }

        // Fresh signups continue to the one-time profile setup step.
        if ($request->user()->avatar === null) {
            return redirect()->route('profile-setup.edit');
        }

        return redirect()->route('subscription')->with('success', __('Thanks! Your plan is active.'));
    }

    // The provider's hosted billing portal: update card, download invoices.
    public function portal(Request $request): Response
    {
        $this->ensureConfigured();
        $subscription = Subscription::forUser($request->user());

        if ($this->usesPaddlePortal($subscription)) {
            return Inertia::location($this->paddle->createPortalUrl(
                $subscription->paddle_customer_id,
                array_filter([$subscription->paddle_subscription_id]),
            ));
        }

        abort_unless($subscription->stripe_customer_id && $this->stripe->isConfigured(), 404);

        return Inertia::location($this->stripe->createPortalSession($subscription->stripe_customer_id, route('subscription')));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', Rule::in(array_keys(self::CANCEL_FEEDBACK))],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $subscription = $this->liveWebSubscription($request);

        if ($subscription->provider === Subscription::PROVIDER_PADDLE) {
            // Paddle's API has no cancellation-feedback field, so keep it in the log.
            Log::info('Member cancelled their Paddle subscription.', ['user' => $subscription->user_id, 'reason' => $data['reason'] ?? null, 'comment' => $data['comment'] ?? null]);
            $this->paddleSync->syncSubscription($this->paddle->cancelSubscription($subscription->paddle_subscription_id), $subscription);
        } else {
            $this->sync->syncStripeSubscription($this->stripe->updateSubscription($subscription->stripe_subscription_id, [
                'cancel_at_period_end' => true,
                'cancellation_details' => array_filter([
                    'feedback' => self::CANCEL_FEEDBACK[$data['reason'] ?? ''] ?? null,
                    'comment' => $data['comment'] ?? null,
                ]),
            ]), $subscription);
        }

        return back()->with('success', __('Your plan will end on :date. You won\'t be charged again.', ['date' => $subscription->fresh()->renews]));
    }

    public function resume(Request $request): RedirectResponse
    {
        $subscription = $this->liveWebSubscription($request);

        if ($subscription->provider === Subscription::PROVIDER_PADDLE) {
            $this->paddleSync->syncSubscription(
                $this->paddle->updateSubscription($subscription->paddle_subscription_id, ['scheduled_change' => null]),
                $subscription,
            );
        } else {
            $this->sync->syncStripeSubscription(
                $this->stripe->updateSubscription($subscription->stripe_subscription_id, ['cancel_at_period_end' => false]),
                $subscription,
            );
        }

        return back()->with('success', __('Welcome back! Your plan will keep renewing.'));
    }

    // Monthly ⇄ Yearly: the new price applies from the next renewal — nothing is
    // charged or refunded today (no proration). Picking the current plan again
    // undoes a pending switch.
    public function switch(Request $request): RedirectResponse
    {
        $plan = $request->validate(['plan' => ['required', Rule::in(['monthly', 'yearly'])]])['plan'];
        $subscription = $this->liveWebSubscription($request);

        if ($subscription->provider === Subscription::PROVIDER_PADDLE) {
            // A pending cancellation has to be lifted before Paddle accepts item changes.
            if ($subscription->status === 'cancelling' || $subscription->trial_cancelled) {
                $this->paddle->updateSubscription($subscription->paddle_subscription_id, ['scheduled_change' => null]);
            }

            $subscription->update(['next_plan' => $plan === $subscription->plan_id ? null : $plan]);

            $this->paddleSync->syncSubscription($this->paddle->updateSubscription($subscription->paddle_subscription_id, [
                'items' => [['price_id' => $this->paddleSync->priceFor($plan), 'quantity' => 1]],
                'proration_billing_mode' => 'do_not_bill',
            ]), $subscription->fresh());

            return back();
        }

        $stripeSubscription = $this->stripe->retrieveSubscription($subscription->stripe_subscription_id);
        $itemId = $stripeSubscription['items']['data'][0]['id'] ?? null;
        abort_unless($itemId, 409);

        $subscription->update(['next_plan' => $plan === $subscription->plan_id ? null : $plan]);

        $this->sync->syncStripeSubscription($this->stripe->updateSubscription($subscription->stripe_subscription_id, [
            'items' => [['id' => $itemId, 'price' => $this->sync->priceFor($plan)]],
            'proration_behavior' => 'none',
            'cancel_at_period_end' => false,
        ]), $subscription->fresh());

        return back();
    }

    private function liveWebSubscription(Request $request): Subscription
    {
        $this->ensureConfigured();
        $subscription = Subscription::forUser($request->user());
        abort_unless($this->hasLiveWebSubscription($subscription), 404);

        return $subscription;
    }

    public static function hasLiveWebSubscription(Subscription $subscription): bool
    {
        if ($subscription->status === 'expired') {
            return false;
        }

        return match ($subscription->provider) {
            Subscription::PROVIDER_PADDLE => $subscription->paddle_subscription_id !== null,
            Subscription::PROVIDER_STRIPE => $subscription->stripe_subscription_id !== null,
            default => false,
        };
    }

    public static function usesPaddlePortal(Subscription $subscription): bool
    {
        return $subscription->paddle_customer_id !== null
            && ($subscription->provider !== Subscription::PROVIDER_STRIPE || $subscription->stripe_customer_id === null);
    }

    private function isStoreManaged(Subscription $subscription): bool
    {
        return in_array($subscription->provider, ['App Store', 'Google Play'], true)
            && $subscription->access !== 'none'
            && $subscription->unlocksQuizzes();
    }

    private function stripeCustomerId(User $user, Subscription $subscription): string
    {
        if ($subscription->stripe_customer_id) {
            return $subscription->stripe_customer_id;
        }

        $id = $this->stripe->createCustomer([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => ['user_id' => (string) $user->id],
        ]);
        $subscription->update(['stripe_customer_id' => $id]);

        return $id;
    }

    private function ensureConfigured(): void
    {
        if (! $this->paddle->isConfigured() && ! $this->stripe->isConfigured()) {
            throw ValidationException::withMessages(['billing' => __('Payments aren\'t available right now. Please try again later.')]);
        }
    }

    private function backWithError(string $message): RedirectResponse
    {
        return back()->withErrors(['billing' => $message]);
    }
}
