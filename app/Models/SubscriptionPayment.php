<?php

namespace App\Models;

use App\Notifications\PaymentReceived;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = ['user_id', 'stripe_invoice_id', 'amount', 'plan', 'status', 'paid_on'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        // One place for every way a payment is recorded (Stripe webhooks, the
        // mobile app's store purchases), so each one shows in Notifications.
        static::created(function (SubscriptionPayment $payment): void {
            $payment->user?->notify(new PaymentReceived($payment->amount, $payment->plan));
        });
    }
}
