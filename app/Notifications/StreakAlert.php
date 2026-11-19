<?php

namespace App\Notifications;

use App\Notifications\Concerns\DeliveredByPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Settings → Streak alerts: an evening warning when the member has a streak
 * going but hasn't practised today, so it's about to run out. `on` is the date
 * the streak would break on, so each day is warned about once.
 */
class StreakAlert extends Notification
{
    use DeliveredByPreference, Queueable;

    public function __construct(public int $streak, public string $on) {}

    /**
     * @return array{kind: string, title: string, body: string, params: array{days: int}, on: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'streak',
            'title' => 'Your :days-day streak is at risk',
            'body' => 'Practise before midnight to keep your :days-day streak going.',
            'params' => ['days' => $this->streak],
            'on' => $this->on,
        ];
    }
}
