<?php

namespace App\Services\Billing;

use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * The only class that talks to Stripe. Everything comes back as plain arrays so
 * the rest of billing (SubscriptionSync, the controllers) never touches SDK
 * objects — and tests can bind a fake in its place.
 */
class StripeGateway
{
    private ?StripeClient $client = null;

    public function isConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    private function client(): StripeClient
    {
        return $this->client ??= new StripeClient((string) config('services.stripe.secret'));
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function createCustomer(array $params): string
    {
        return $this->client()->customers->create($params)->id;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(array $params): array
    {
        $session = $this->client()->checkout->sessions->create($params);

        return ['id' => $session->id, 'url' => $session->url];
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveCheckoutSession(string $id): array
    {
        return $this->client()->checkout->sessions->retrieve($id)->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveSubscription(string $id): array
    {
        return $this->client()->subscriptions->retrieve($id)->toArray();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function updateSubscription(string $id, array $params): array
    {
        return $this->client()->subscriptions->update($id, $params)->toArray();
    }

    public function cancelSubscriptionNow(string $id): void
    {
        $this->client()->subscriptions->cancel($id, ['prorate' => false]);
    }

    public function createPortalSession(string $customerId, string $returnUrl): string
    {
        return $this->client()->billingPortal->sessions->create([
            'customer' => $customerId,
            'return_url' => $returnUrl,
        ])->url;
    }

    /**
     * Verifies the Stripe-Signature header and decodes the event.
     *
     * @return array<string, mixed>
     *
     * @throws \Stripe\Exception\SignatureVerificationException|\UnexpectedValueException
     */
    public function constructWebhookEvent(string $payload, string $signature): array
    {
        return Webhook::constructEvent($payload, $signature, (string) config('services.stripe.webhook_secret'))->toArray();
    }
}
