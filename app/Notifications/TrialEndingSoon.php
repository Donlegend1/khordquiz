<?php

namespace App\Notifications;

use App\Notifications\Concerns\DeliveredByPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent once per trial, a couple of days before it ends (notifications:trial-ending).
 * `ends_on` identifies the trial so the same one is never announced twice.
 */
class TrialEndingSoon extends Notification
{
    use DeliveredByPreference, Queueable;

    public function __construct(public int $daysLeft, public string $plan, public string $price, public string $endsOn) {}

    /**
     * Account notices still show in-app when notifications are switched off.
     */
    protected function alwaysInApp(): bool
    {
        return true;
    }

    /**
     * @return array{kind: string, title: string, body: string, params: array{days: int, plan: string, price: string}, ends_on: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'payment',
            'title' => 'Your trial is ending soon',
            'body' => 'Your free trial ends in :days days. :plan (:price) starts after that — cancel anytime in Account.',
            'params' => ['days' => $this->daysLeft, 'plan' => $this->plan, 'price' => $this->price],
            'ends_on' => $this->endsOn,
        ];
    }
}
