<?php

namespace App\Http\Controllers;

use App\Services\Billing\PaddleGateway;
use App\Services\Billing\PaddleSubscriptionSync;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

// POST /paddle/webhook — add this as a notification destination in Paddle
// (Developer tools → Notifications) with these events: transaction.completed,
// subscription.created, subscription.updated, subscription.activated,
// subscription.trialing, subscription.past_due, subscription.paused,
// subscription.resumed, subscription.canceled. Paste the destination's secret
// key into PADDLE_WEBHOOK_SECRET.
class PaddleWebhookController extends Controller
{
    public function __invoke(Request $request, PaddleGateway $paddle, PaddleSubscriptionSync $sync): Response
    {
        try {
            $event = $paddle->verifyWebhook($request->getContent(), (string) $request->header('Paddle-Signature'));
        } catch (\Throwable $e) {
            Log::warning('Rejected a Paddle webhook.', ['error' => $e->getMessage()]);

            return response('Invalid signature', 400);
        }

        $data = (array) ($event['data'] ?? []);
        $type = (string) ($event['event_type'] ?? '');

        if ($type === 'transaction.completed') {
            $sync->completeTransaction($data);
        } elseif (str_starts_with($type, 'subscription.')) {
            $sync->syncSubscription($data);
        }

        return response('OK');
    }
}
