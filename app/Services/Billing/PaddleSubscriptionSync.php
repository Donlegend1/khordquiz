<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\Plans;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors Paddle's view of a member's billing onto their Subscription row —
 * the same row the mobile app reads, so access, the Subscription page and the
 * API all agree. Called from the Paddle webhook and from the checkout success
 * redirect (so access doesn't wait on webhook delivery).
 */
class PaddleSubscriptionSync
{
    public function __construct(
        private readonly PaddleGateway $paddle,
        private readonly StripeGateway $stripe,
    ) {}

    public function priceFor(string $planId): ?string
    {
        return config("services.paddle.prices.{$planId}");
    }

    public function planForPrice(?string $priceId): ?string
    {
        if ($priceId === null) {
            return null;
        }

        $planId = array_search($priceId, (array) config('services.paddle.prices'), true);

        return is_string($planId) ? $planId : null;
    }

    /**
     * The checkout line item for a plan. Monthly/Yearly get the 7-day free
     * trial only while the member hasn't had one: when the catalog price
     * doesn't match that (it has a trial the member already used, or no trial
     * for a first-timer), the same price is sent as a one-off copy with the
     * trial added or removed.
     *
     * @return array<string, mixed>
     */
    public function transactionItem(string $planId, bool $withTrial): array
    {
        $priceId = (string) $this->priceFor($planId);

        if ($planId === 'lifetime') {
            return ['price_id' => $priceId, 'quantity' => 1];
        }

        $price = $this->paddle->getPrice($priceId);

        if (! empty($price['trial_period']) === $withTrial) {
            return ['price_id' => $priceId, 'quantity' => 1];
        }

        return [
            'quantity' => 1,
            'price' => array_filter([
                'product_id' => $price['product_id'],
                'description' => $price['description'] ?? Plans::all()[$planId]['label'],
                'name' => $price['name'] ?? null,
                'unit_price' => $price['unit_price'],
                'unit_price_overrides' => $price['unit_price_overrides'] ?? null,
                'billing_cycle' => $price['billing_cycle'],
                'trial_period' => $withTrial ? ['interval' => 'day', 'frequency' => Plans::TRIAL_DAYS] : null,
                'tax_mode' => $price['tax_mode'] ?? null,
                'custom_data' => ['plan_id' => $planId],
            ], fn ($value) => $value !== null && $value !== []),
        ];
    }

    /**
     * A transaction Paddle has collected payment for (or completed with no
     * charge, e.g. the start of a free trial): a one-time Lifetime purchase,
     * a new subscription, or a renewal.
     *
     * @param  array<string, mixed>  $transaction
     */
    public function completeTransaction(array $transaction): void
    {
        if (! in_array($transaction['status'] ?? null, ['paid', 'completed'], true)) {
            return;
        }

        $subscription = $this->subscriptionForCustomer($transaction['customer_id'] ?? null, $transaction['custom_data']['user_id'] ?? null);

        if ($subscription === null) {
            Log::warning('Paddle transaction completed for an unknown customer.', ['transaction' => $transaction['id'] ?? null]);

            return;
        }

        $planId = $this->planForTransaction($transaction);

        $this->recordPayment($subscription, $transaction, $planId);

        if ($planId === 'lifetime' && empty($transaction['subscription_id'])) {
            $this->grantLifetime($subscription, $transaction);

            return;
        }

        if (! empty($transaction['subscription_id'])) {
            $this->syncSubscription($this->paddle->getSubscription($transaction['subscription_id']), $subscription);

            return;
        }

        // Paid, but Paddle hasn't created the subscription yet (it does so once
        // the transaction completes). Unlock now; the subscription.created
        // webhook fills in the real ids and dates moments later.
        if ($planId !== null && ! $this->hasActiveLifetime($subscription)) {
            $hasTrial = ! empty($transaction['items'][0]['price']['trial_period']);
            $periodEnd = $this->date($transaction['billing_period']['ends_at'] ?? null) ?? now()->addDays(Plans::TRIAL_DAYS);

            $subscription->fill([
                'access' => $hasTrial ? 'trial' : 'paid',
                'plan_id' => $planId,
                'status' => 'active',
                'provider' => Subscription::PROVIDER_PADDLE,
                'paddle_customer_id' => $transaction['customer_id'] ?? $subscription->paddle_customer_id,
                'current_period_end' => $periodEnd,
                'renews' => Subscription::formatDate($periodEnd),
                'trial_ends_at' => $hasTrial ? $periodEnd : $subscription->trial_ends_at,
                'trial_used' => $hasTrial || $subscription->trial_used,
                'trial_cancelled' => false,
                'next_plan' => null,
                'since' => $hasTrial ? $subscription->since : ($subscription->since ?? now()->format('M Y')),
            ])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $paddleSubscription
     */
    public function syncSubscription(array $paddleSubscription, ?Subscription $subscription = null): void
    {
        $subscription ??= $this->subscriptionForCustomer($paddleSubscription['customer_id'] ?? null, $paddleSubscription['custom_data']['user_id'] ?? null);

        if ($subscription === null) {
            Log::warning('Paddle subscription event for an unknown customer.', ['subscription' => $paddleSubscription['id'] ?? null]);

            return;
        }

        // A Lifetime purchase supersedes the recurring plan it replaced — later
        // events about that old subscription (e.g. its cancellation) don't apply.
        if ($this->hasActiveLifetime($subscription)) {
            return;
        }

        $status = $paddleSubscription['status'] ?? null;

        // Ignore the end of an older subscription the member has since replaced.
        if ($subscription->paddle_subscription_id && $subscription->paddle_subscription_id !== $paddleSubscription['id']
            && in_array($status, ['canceled', 'paused'], true)) {
            return;
        }

        $item = $paddleSubscription['items'][0] ?? [];
        $planId = $this->planForPrice($item['price']['id'] ?? null)
            ?? $item['price']['custom_data']['plan_id']
            ?? $paddleSubscription['custom_data']['plan_id']
            ?? $subscription->plan_id
            ?? 'yearly';
        $periodEnd = $this->date($paddleSubscription['current_billing_period']['ends_at'] ?? null)
            ?? $this->date($paddleSubscription['canceled_at'] ?? null);
        $cancelling = ($paddleSubscription['scheduled_change']['action'] ?? null) === 'cancel';

        // A Monthly ⇄ Yearly switch changes the Paddle price straight away (not
        // billed now) but only takes effect at renewal — keep showing the
        // current plan until the billing period actually rolls over.
        $previousPeriodEnd = $subscription->current_period_end;
        $switchPending = $subscription->next_plan !== null
            && $subscription->next_plan === $planId
            && $previousPeriodEnd !== null && $periodEnd !== null
            && $periodEnd->lessThanOrEqualTo($previousPeriodEnd);

        $attributes = [
            'provider' => Subscription::PROVIDER_PADDLE,
            'paddle_customer_id' => $paddleSubscription['customer_id'] ?? $subscription->paddle_customer_id,
            'paddle_subscription_id' => $paddleSubscription['id'],
            'current_period_end' => $periodEnd,
            'plan_id' => $switchPending ? $subscription->plan_id : $planId,
            'next_plan' => $switchPending ? $subscription->next_plan : null,
            'renews' => $periodEnd ? Subscription::formatDate($periodEnd) : null,
        ];

        switch ($status) {
            case 'trialing':
                $attributes += [
                    'access' => 'trial',
                    'status' => 'active',
                    'trial_used' => true,
                    'trial_ends_at' => $this->date($item['trial_dates']['ends_at'] ?? null) ?? $periodEnd,
                    'trial_cancelled' => $cancelling,
                ];
                break;

            case 'active':
            case 'past_due':
                $attributes += [
                    'access' => 'paid',
                    'status' => $status === 'past_due' ? 'past_due' : ($cancelling ? 'cancelling' : 'active'),
                    'trial_cancelled' => false,
                    'since' => $subscription->since ?? now()->format('M Y'),
                ];
                break;

            case 'canceled':
            case 'paused':
                $attributes += [
                    'access' => $subscription->access === 'trial' ? 'trial' : 'paid',
                    'status' => 'expired',
                    'trial_cancelled' => $subscription->access === 'trial' ? true : $subscription->trial_cancelled,
                    'next_plan' => null,
                ];
                break;

            default:
                return;
        }

        $subscription->fill($attributes)->save();
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    private function grantLifetime(Subscription $subscription, array $transaction): void
    {
        $replacingPaddle = $subscription->provider === Subscription::PROVIDER_PADDLE ? $subscription->paddle_subscription_id : null;
        $replacingStripe = $subscription->provider === Subscription::PROVIDER_STRIPE && $subscription->status !== 'expired' ? $subscription->stripe_subscription_id : null;

        $subscription->fill([
            'access' => 'paid',
            'plan_id' => 'lifetime',
            'status' => 'active',
            'provider' => Subscription::PROVIDER_PADDLE,
            'paddle_customer_id' => $transaction['customer_id'] ?? $subscription->paddle_customer_id,
            'paddle_subscription_id' => null,
            'current_period_end' => null,
            'renews' => null,
            'next_plan' => null,
            'trial_cancelled' => false,
            'since' => now()->format('M Y'),
        ])->save();

        // The recurring plan it upgrades from stops now: Lifetime covers everything.
        try {
            if ($replacingPaddle) {
                $this->paddle->cancelSubscription($replacingPaddle, 'immediately');
            }

            if ($replacingStripe && $this->stripe->isConfigured()) {
                $this->stripe->cancelSubscriptionNow($replacingStripe);
            }
        } catch (\Throwable $e) {
            Log::error('Could not cancel the subscription replaced by Lifetime.', [
                'paddle' => $replacingPaddle,
                'stripe' => $replacingStripe,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Adds a paid transaction to the member's billing history (once per transaction).
     *
     * @param  array<string, mixed>  $transaction
     */
    private function recordPayment(Subscription $subscription, array $transaction, ?string $planId): void
    {
        $amount = (int) ($transaction['details']['totals']['grand_total'] ?? 0);

        if ($amount <= 0 || empty($transaction['id'])) {
            return;
        }

        $currency = strtoupper((string) ($transaction['currency_code'] ?? 'USD'));
        $formatted = number_format($amount / 100, 2);
        $plan = Plans::all()[$planId ?? ''] ?? null;

        SubscriptionPayment::query()->firstOrCreate(
            ['stripe_invoice_id' => $transaction['id']],
            [
                'user_id' => $subscription->user_id,
                'amount' => $currency === 'USD' ? '$'.$formatted : "{$currency} {$formatted}",
                'plan' => $plan ? $plan['tier'].' · '.$plan['label'] : 'KhordQuiz',
                'status' => 'Paid',
                'paid_on' => Subscription::formatDate($this->date($transaction['billed_at'] ?? null) ?? now()),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    private function planForTransaction(array $transaction): ?string
    {
        $price = $transaction['items'][0]['price'] ?? [];

        return $this->planForPrice($price['id'] ?? null)
            ?? $price['custom_data']['plan_id']
            ?? $transaction['custom_data']['plan_id']
            ?? null;
    }

    private function hasActiveLifetime(Subscription $subscription): bool
    {
        return $subscription->plan_id === 'lifetime' && $subscription->access === 'paid' && $subscription->status !== 'expired';
    }

    private function subscriptionForCustomer(?string $customerId, int|string|null $userId): ?Subscription
    {
        $user = $userId ? User::query()->find($userId) : null;

        if ($user === null && $customerId) {
            $existing = Subscription::query()->where('paddle_customer_id', $customerId)->first();

            if ($existing) {
                return $existing;
            }
        }

        if ($user === null) {
            return null;
        }

        $subscription = Subscription::forUser($user);

        if ($customerId && ! $subscription->paddle_customer_id) {
            $subscription->update(['paddle_customer_id' => $customerId]);
        }

        return $subscription;
    }

    private function date(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }
}
