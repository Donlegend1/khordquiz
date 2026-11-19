<?php

namespace App\Notifications;

use App\Notifications\Concerns\DeliveredByPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent whenever a subscription payment is recorded (Stripe, App Store or
 * Google Play).
 */
class PaymentReceived extends Notification
{
    use DeliveredByPreference, Queueable;

    public function __construct(public string $amount, public string $plan) {}

    /**
     * Account notices still show in-app when notifications are switched off.
     */
    protected function alwaysInApp(): bool
    {
        return true;
    }

    /**
     * @return array{kind: string, title: string, body: string, params: array{amount: string, plan: string}}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'payment',
            'title' => 'Payment received',
            'body' => 'Thanks! We received your payment of :amount for :plan.',
            'params' => ['amount' => $this->amount, 'plan' => $this->plan],
        ];
    }
}
