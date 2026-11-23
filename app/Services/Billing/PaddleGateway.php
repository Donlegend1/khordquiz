<?php

namespace App\Services\Billing;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * The only class that talks to Paddle Billing. Everything comes back as plain
 * arrays (the API's `data` object) so PaddleSubscriptionSync and the
 * controllers never deal with HTTP — and tests can Http::fake() the API.
 *
 * @see https://developer.paddle.com/api-reference/overview
 */
class PaddleGateway
{
    // How old a webhook's signed timestamp may be before it's refused (replays).
    private const WEBHOOK_TOLERANCE_SECONDS = 300;

    public function isConfigured(): bool
    {
        return filled(config('services.paddle.api_key')) && filled(config('services.paddle.client_token'));
    }

    public function isSandbox(): bool
    {
        return config('services.paddle.environment') !== 'production';
    }

    public function clientToken(): string
    {
        return (string) config('services.paddle.client_token');
    }

    /**
     * The Paddle customer for this email — created, or found if Paddle already
     * has one with that email (e.g. from an earlier checkout).
     *
     * @param  array<string, string>  $customData
     *
     * @throws RequestException
     */
    public function findOrCreateCustomer(string $email, ?string $name, array $customData = []): string
    {
        $response = $this->client()->post('/customers', array_filter([
            'email' => $email,
            'name' => $name,
            'custom_data' => $customData ?: null,
        ]));

        if ($response->status() === 409) {
            $existing = $this->client()->get('/customers', ['email' => $email])->throw()->json('data.0.id');

            if (is_string($existing)) {
                return $existing;
            }
        }

        return (string) $response->throw()->json('data.id');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function getPrice(string $id): array
    {
        return $this->data($this->client()->get("/prices/{$id}"));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function createTransaction(array $params): array
    {
        return $this->data($this->client()->post('/transactions', $params));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function getTransaction(string $id): array
    {
        return $this->data($this->client()->get("/transactions/{$id}"));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function getSubscription(string $id): array
    {
        return $this->data($this->client()->get("/subscriptions/{$id}"));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function updateSubscription(string $id, array $params): array
    {
        return $this->data($this->client()->patch("/subscriptions/{$id}", $params));
    }

    /**
     * @param  'next_billing_period'|'immediately'  $effectiveFrom
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function cancelSubscription(string $id, string $effectiveFrom = 'next_billing_period'): array
    {
        return $this->data($this->client()->post("/subscriptions/{$id}/cancel", ['effective_from' => $effectiveFrom]));
    }

    /**
     * A link to Paddle's customer portal: update the card, see invoices,
     * cancel. Portal links are single-use and short-lived, so make one per click.
     *
     * @param  list<string>  $subscriptionIds
     *
     * @throws RequestException
     */
    public function createPortalUrl(string $customerId, array $subscriptionIds = []): string
    {
        $session = $this->data($this->client()->post("/customers/{$customerId}/portal-sessions", array_filter([
            'subscription_ids' => $subscriptionIds ?: null,
        ])));

        return (string) ($session['urls']['general']['overview'] ?? '');
    }

    /**
     * Checks the Paddle-Signature header ("ts=…;h1=…") against the webhook
     * secret and decodes the notification.
     *
     * @return array<string, mixed>
     *
     * @throws \UnexpectedValueException
     */
    public function verifyWebhook(string $payload, string $signatureHeader): array
    {
        $secret = (string) config('services.paddle.webhook_secret');

        if ($secret === '') {
            throw new \UnexpectedValueException('The Paddle webhook secret is not configured.');
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(';', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

            if ($key === 'ts') {
                $timestamp = $value;
            } elseif ($key === 'h1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || ! ctype_digit($timestamp) || $signatures === []) {
            throw new \UnexpectedValueException('Malformed Paddle-Signature header.');
        }

        if (abs(time() - (int) $timestamp) > self::WEBHOOK_TOLERANCE_SECONDS) {
            throw new \UnexpectedValueException('The Paddle webhook timestamp is outside the allowed window.');
        }

        $expected = hash_hmac('sha256', "{$timestamp}:{$payload}", $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                $event = json_decode($payload, true);

                if (! is_array($event)) {
                    throw new \UnexpectedValueException('The Paddle webhook body is not valid JSON.');
                }

                return $event;
            }
        }

        throw new \UnexpectedValueException('The Paddle webhook signature does not match.');
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->isSandbox() ? 'https://sandbox-api.paddle.com' : 'https://api.paddle.com')
            ->withToken((string) config('services.paddle.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    private function data(Response $response): array
    {
        return (array) $response->throw()->json('data', []);
    }
}
