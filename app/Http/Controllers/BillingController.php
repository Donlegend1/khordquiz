<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\StripeGateway;
use App\Services\Billing\SubscriptionSync;
use App\Support\Plans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

// Web billing through Stripe. New purchases go through Stripe Checkout; plan
// changes on an existing subscription are made through the API and mirrored
// back by SubscriptionSync (here, and again when the webhook arrives).
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

        // Already on a recurring Stripe plan: switching happens on the page, not
        // through a second subscription. And Lifetime can't be bought twice.
        $hasLifetime = $subscription->plan_id === 'lifetime' && $subscription->access === 'paid' && $subscription->status !== 'expired';
        if ($hasLifetime || ($plan !== 'lifetime' && $this->hasLiveStripeSubscription($subscription))) {
            return redirect()->route('subscription');
        }

        return $this->startCheckout($user, $plan);
    }

    // Sends the member to Stripe Checkout for a plan. Also used right after
    // signup (SignupController), so a new member pays (or starts the trial)
    // before onboarding.
    public function startCheckout(User $user, string $plan): Response
    {
        $subscription = Subscription::forUser($user);

        $params = [
            'customer' => $this->customerId($user, $subscription),
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

    // Stripe sends the member back here after paying. Sync right away so the
    // plan shows up immediately rather than whenever the webhook lands.
    public function success(Request $request): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id');
        $this->ensureConfigured();

        if ($sessionId !== '') {
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

    // Stripe's hosted billing portal: update card, download invoices.
    public function portal(Request $request): Response
    {
        $this->ensureConfigured();
        $subscription = Subscription::forUser($request->user());
        abort_unless($subscription->stripe_customer_id, 404);

        return Inertia::location($this->stripe->createPortalSession($subscription->stripe_customer_id, route('subscription')));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', Rule::in(array_keys(self::CANCEL_FEEDBACK))],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $subscription = $this->liveStripeSubscription($request);

        $this->sync->syncStripeSubscription($this->stripe->updateSubscription($subscription->stripe_subscription_id, [
            'cancel_at_period_end' => true,
            'cancellation_details' => array_filter([
                'feedback' => self::CANCEL_FEEDBACK[$data['reason'] ?? ''] ?? null,
                'comment' => $data['comment'] ?? null,
            ]),
        ]), $subscription);

        return back()->with('success', __('Your plan will end on :date. You won\'t be charged again.', ['date' => $subscription->fresh()->renews]));
    }

    public function resume(Request $request): RedirectResponse
    {
        $subscription = $this->liveStripeSubscription($request);

        $this->sync->syncStripeSubscription(
            $this->stripe->updateSubscription($subscription->stripe_subscription_id, ['cancel_at_period_end' => false]),
            $subscription,
        );

        return back()->with('success', __('Welcome back! Your plan will keep renewing.'));
    }

    // Monthly ⇄ Yearly: the new price applies from the next renewal — nothing is
    // charged or refunded today (no proration). Picking the current plan again
    // undoes a pending switch.
    public function switch(Request $request): RedirectResponse
    {
        $plan = $request->validate(['plan' => ['required', Rule::in(['monthly', 'yearly'])]])['plan'];
        $subscription = $this->liveStripeSubscription($request);
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

    private function liveStripeSubscription(Request $request): Subscription
    {
        $this->ensureConfigured();
        $subscription = Subscription::forUser($request->user());
        abort_unless($this->hasLiveStripeSubscription($subscription), 404);

        return $subscription;
    }

    private function hasLiveStripeSubscription(Subscription $subscription): bool
    {
        return $subscription->provider === Subscription::PROVIDER_STRIPE
            && $subscription->stripe_subscription_id !== null
            && $subscription->status !== 'expired';
    }

    private function isStoreManaged(Subscription $subscription): bool
    {
        return in_array($subscription->provider, ['App Store', 'Google Play'], true)
            && $subscription->access !== 'none'
            && $subscription->unlocksQuizzes();
    }

    private function customerId(User $user, Subscription $subscription): string
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
        if (! $this->stripe->isConfigured()) {
            throw ValidationException::withMessages(['billing' => __('Payments aren\'t available right now. Please try again later.')]);
        }
    }

    private function backWithError(string $message): RedirectResponse
    {
        return back()->withErrors(['billing' => $message]);
    }
}
