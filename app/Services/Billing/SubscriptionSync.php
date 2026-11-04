<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\Plans;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors Stripe's view of a member's billing onto their Subscription row —
 * the same row the mobile app reads (Api\SubscriptionController), so access,
 * the Subscription page and the API all agree. Called from the webhook and from
 * the checkout success redirect (so access doesn't wait on webhook delivery).
 */
class SubscriptionSync
{
    public function __construct(private readonly StripeGateway $stripe) {}

    public function planForPrice(?string $priceId): ?string
    {
        if ($priceId === null) {
            return null;
        }

        $planId = array_search($priceId, (array) config('services.stripe.prices'), true);

        return is_string($planId) ? $planId : null;
    }

    public function priceFor(string $planId): ?string
    {
        return config("services.stripe.prices.{$planId}");
    }

    /**
     * A finished Checkout Session: either a subscription (monthly/yearly, maybe
     * trialing) or a one-time Lifetime payment.
     *
     * @param  array<string, mixed>  $session
     */
    public function completeCheckout(array $session): void
    {
        $subscription = $this->subscriptionForCustomer($session['customer'] ?? null, $session['client_reference_id'] ?? null);

        if ($subscription === null) {
            Log::warning('Stripe checkout completed for an unknown customer.', ['session' => $session['id'] ?? null]);

            return;
        }

        if (($session['mode'] ?? null) === 'subscription' && ! empty($session['subscription'])) {
            $stripeSubscription = is_array($session['subscription'])
                ? $session['subscription']
                : $this->stripe->retrieveSubscription($session['subscription']);

            $this->syncStripeSubscription($stripeSubscription, $subscription);

            return;
        }

        if (($session['mode'] ?? null) === 'payment' && ($session['payment_status'] ?? null) === 'paid') {
            $this->grantLifetime($subscription, $session);
        }
    }

    /**
     * @param  array<string, mixed>  $stripeSubscription
     */
    public function syncStripeSubscription(array $stripeSubscription, ?Subscription $subscription = null): void
    {
        $subscription ??= $this->subscriptionForCustomer($stripeSubscription['customer'] ?? null, $stripeSubscription['metadata']['user_id'] ?? null);

        if ($subscription === null) {
            return;
        }

        // A Lifetime purchase supersedes the recurring plan it replaced — later
        // events about that old subscription (e.g. its cancellation) don't apply.
        if ($subscription->plan_id === 'lifetime' && $subscription->access === 'paid') {
            return;
        }

        // Ignore events for an older subscription the member has since replaced.
        if ($subscription->stripe_subscription_id && $subscription->stripe_subscription_id !== $stripeSubscription['id']
            && in_array($stripeSubscription['status'], ['canceled', 'incomplete_expired'], true)) {
            return;
        }

        $item = $stripeSubscription['items']['data'][0] ?? [];
        $planId = $this->planForPrice($item['price']['id'] ?? null) ?? $subscription->plan_id ?? 'yearly';
        // Newer Stripe API versions put the billing period on the item.
        $periodEnd = $this->timestamp($item['current_period_end'] ?? $stripeSubscription['current_period_end'] ?? null);
        $cancelling = (bool) ($stripeSubscription['cancel_at_period_end'] ?? false);

        // A Monthly ⇄ Yearly switch changes the Stripe price straight away (with
        // no proration) but only takes effect at renewal — keep showing the
        // current plan until the billing period actually rolls over.
        $previousPeriodEnd = $subscription->current_period_end;
        $switchPending = $subscription->next_plan !== null
            && $subscription->next_plan === $planId
            && $previousPeriodEnd !== null && $periodEnd !== null
            && $periodEnd->lessThanOrEqualTo($previousPeriodEnd);

        $attributes = [
            'provider' => Subscription::PROVIDER_STRIPE,
            'stripe_customer_id' => $stripeSubscription['customer'],
            'stripe_subscription_id' => $stripeSubscription['id'],
            'current_period_end' => $periodEnd,
            'plan_id' => $switchPending ? $subscription->plan_id : $planId,
            'next_plan' => $switchPending ? $subscription->next_plan : null,
            'renews' => $periodEnd ? Subscription::formatDate($periodEnd) : null,
        ];

        switch ($stripeSubscription['status']) {
            case 'trialing':
                $attributes += [
                    'access' => 'trial',
                    'status' => 'active',
                    'trial_used' => true,
                    'trial_ends_at' => $this->timestamp($stripeSubscription['trial_end'] ?? null) ?? $periodEnd,
                    'trial_cancelled' => $cancelling,
                ];
                break;

            case 'active':
            case 'past_due':
                $attributes += [
                    'access' => 'paid',
                    'status' => $stripeSubscription['status'] === 'past_due' ? 'past_due' : ($cancelling ? 'cancelling' : 'active'),
                    'trial_cancelled' => false,
                    'since' => $subscription->since ?? now()->format('M Y'),
                ];
                break;

            case 'canceled':
            case 'unpaid':
            case 'incomplete_expired':
                $attributes += [
                    'access' => $subscription->access === 'trial' ? 'trial' : 'paid',
                    'status' => 'expired',
                    'trial_cancelled' => $subscription->access === 'trial' ? true : $subscription->trial_cancelled,
                    'next_plan' => null,
                ];
                break;

            default:
                // 'incomplete' — the first payment hasn't gone through yet.
                return;
        }

        $subscription->fill($attributes)->save();
    }

    /**
     * Adds a paid invoice to the member's billing history (once per invoice).
     *
     * @param  array<string, mixed>  $invoice
     */
    public function recordInvoice(array $invoice): void
    {
        if (($invoice['amount_paid'] ?? 0) <= 0 || empty($invoice['id'])) {
            return;
        }

        $subscription = $this->subscriptionForCustomer($invoice['customer'] ?? null, null);

        if ($subscription === null) {
            return;
        }

        $line = $invoice['lines']['data'][0] ?? [];
        $planId = $this->planForPrice($line['price']['id'] ?? $line['pricing']['price_details']['price'] ?? null) ?? $subscription->plan_id;

        $this->recordPayment($subscription, $invoice['id'], (int) $invoice['amount_paid'], $planId, $invoice['created'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function grantLifetime(Subscription $subscription, array $session): void
    {
        $replacing = $subscription->provider === Subscription::PROVIDER_STRIPE ? $subscription->stripe_subscription_id : null;

        $subscription->fill([
            'access' => 'paid',
            'plan_id' => 'lifetime',
            'status' => 'active',
            'provider' => Subscription::PROVIDER_STRIPE,
            'stripe_customer_id' => $session['customer'] ?? $subscription->stripe_customer_id,
            'stripe_subscription_id' => null,
            'current_period_end' => null,
            'renews' => null,
            'next_plan' => null,
            'trial_cancelled' => false,
            'since' => now()->format('M Y'),
        ])->save();

        $this->recordPayment($subscription, 'checkout:'.$session['id'], (int) ($session['amount_total'] ?? 0), 'lifetime', $session['created'] ?? null);

        // The recurring plan it upgrades from stops now: Lifetime covers everything.
        if ($replacing) {
            try {
                $this->stripe->cancelSubscriptionNow($replacing);
            } catch (\Throwable $e) {
                Log::error('Could not cancel the subscription replaced by Lifetime.', ['subscription' => $replacing, 'error' => $e->getMessage()]);
            }
        }
    }

    private function recordPayment(Subscription $subscription, string $reference, int $amountCents, ?string $planId, ?int $createdAt): void
    {
        if ($amountCents <= 0) {
            return;
        }

        $plan = Plans::all()[$planId ?? ''] ?? null;

        SubscriptionPayment::query()->firstOrCreate(
            ['stripe_invoice_id' => $reference],
            [
                'user_id' => $subscription->user_id,
                'amount' => '$'.number_format($amountCents / 100, 2),
                'plan' => $plan ? $plan['tier'].' · '.$plan['label'] : 'KhordQuiz',
                'status' => 'Paid',
                'paid_on' => Subscription::formatDate($this->timestamp($createdAt) ?? now()),
            ],
        );
    }

    private function subscriptionForCustomer(?string $customerId, int|string|null $userId): ?Subscription
    {
        if ($customerId) {
            $subscription = Subscription::query()->where('stripe_customer_id', $customerId)->first();

            if ($subscription) {
                return $subscription;
            }
        }

        $user = $userId ? User::query()->find($userId) : null;

        if ($user === null) {
            return null;
        }

        $subscription = Subscription::forUser($user);

        if ($customerId && ! $subscription->stripe_customer_id) {
            $subscription->update(['stripe_customer_id' => $customerId]);
        }

        return $subscription;
    }

    private function timestamp(int|string|null $value): ?Carbon
    {
        return $value ? Carbon::createFromTimestamp((int) $value) : null;
    }
}
