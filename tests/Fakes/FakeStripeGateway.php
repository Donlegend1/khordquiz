<?php

namespace Tests\Fakes;

use App\Services\Billing\StripeGateway;

// Stands in for Stripe in tests: records every call and returns canned objects.
class FakeStripeGateway extends StripeGateway
{
    /** @var list<array{0: string, 1: mixed}> */
    public array $calls = [];

    /** @var array<string, array<string, mixed>> */
    public array $subscriptions = [];

    /** @var array<string, array<string, mixed>> */
    public array $sessions = [];

    public function isConfigured(): bool
    {
        return true;
    }

    public function createCustomer(array $params): string
    {
        $this->calls[] = ['createCustomer', $params];

        return 'cus_test';
    }

    public function createCheckoutSession(array $params): array
    {
        $this->calls[] = ['createCheckoutSession', $params];

        return ['id' => 'cs_test', 'url' => 'https://checkout.stripe.test/cs_test'];
    }

    public function retrieveCheckoutSession(string $id): array
    {
        return $this->sessions[$id];
    }

    public function retrieveSubscription(string $id): array
    {
        return $this->subscriptions[$id];
    }

    public function updateSubscription(string $id, array $params): array
    {
        $this->calls[] = ['updateSubscription', [$id, $params]];
        $subscription = $this->subscriptions[$id];

        if (array_key_exists('cancel_at_period_end', $params)) {
            $subscription['cancel_at_period_end'] = $params['cancel_at_period_end'];
        }

        if (isset($params['items'][0]['price'])) {
            $subscription['items']['data'][0]['price']['id'] = $params['items'][0]['price'];
        }

        return $this->subscriptions[$id] = $subscription;
    }

    public function cancelSubscriptionNow(string $id): void
    {
        $this->calls[] = ['cancelSubscriptionNow', $id];
    }

    public function createPortalSession(string $customerId, string $returnUrl): string
    {
        $this->calls[] = ['createPortalSession', $customerId];

        return 'https://billing.stripe.test/portal';
    }

    /**
     * @return array<string, mixed>
     */
    public static function subscription(string $id, string $priceId, string $status = 'active', ?int $periodEnd = null, array $extra = []): array
    {
        $periodEnd ??= now()->addMonth()->getTimestamp();

        return array_merge([
            'id' => $id,
            'object' => 'subscription',
            'customer' => 'cus_test',
            'status' => $status,
            'cancel_at_period_end' => false,
            'trial_end' => $status === 'trialing' ? $periodEnd : null,
            'metadata' => [],
            'items' => ['data' => [[
                'id' => 'si_test',
                'price' => ['id' => $priceId],
                'current_period_end' => $periodEnd,
            ]]],
        ], $extra);
    }

    public function called(string $method): array
    {
        return array_values(array_map(fn ($call) => $call[1], array_filter($this->calls, fn ($call) => $call[0] === $method)));
    }
}
