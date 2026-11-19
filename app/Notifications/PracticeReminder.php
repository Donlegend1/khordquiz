<?php

namespace App\Notifications;

use App\Notifications\Concerns\DeliveredByPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * The daily nudge from Settings → Practice reminders, sent at the member's
 * chosen reminder time if they haven't practised yet that day. `on` is the
 * member's local date, so it goes out at most once a day.
 */
class PracticeReminder extends Notification
{
    use DeliveredByPreference, Queueable;

    public function __construct(public int $dailyGoal, public string $on) {}

    /**
     * @return array{kind: string, title: string, body: string, params: array{goal: int}, on: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'reminder',
            'title' => 'Time to practise',
            'body' => 'Your daily goal is :goal questions. A few minutes now keeps your ear sharp.',
            'params' => ['goal' => $this->dailyGoal],
            'on' => $this->on,
        ];
    }
}
