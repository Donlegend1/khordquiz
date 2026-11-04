<?php

namespace App\Http\Controllers;

use App\Services\Billing\StripeGateway;
use App\Services\Billing\SubscriptionSync;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

// POST /stripe/webhook — add this endpoint in the Stripe dashboard with these
// events: checkout.session.completed, customer.subscription.created,
// customer.subscription.updated, customer.subscription.deleted, invoice.paid.
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeGateway $stripe, SubscriptionSync $sync): Response
    {
        try {
            $event = $stripe->constructWebhookEvent($request->getContent(), (string) $request->header('Stripe-Signature'));
        } catch (\Throwable $e) {
            Log::warning('Rejected a Stripe webhook.', ['error' => $e->getMessage()]);

            return response('Invalid signature', 400);
        }

        $object = $event['data']['object'] ?? [];

        match ($event['type'] ?? null) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $sync->completeCheckout($object),
            'customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted' => $sync->syncStripeSubscription($object),
            'invoice.paid' => $sync->recordInvoice($object),
            default => null,
        };

        return response('OK');
    }
}
