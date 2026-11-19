<?php

namespace App\Notifications;

use App\Notifications\Concerns\DeliveredByPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent when a member finishes a quiz with every answer right. The title and body
 * are English translation keys; the Notifications page translates them and
 * fills in `params`.
 */
class PerfectScore extends Notification
{
    use DeliveredByPreference, Queueable;

    public function __construct(public string $quizTitle) {}

    /**
     * @return array{kind: string, title: string, body: string, params: array{quiz: string}}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'perfect',
            'title' => 'Perfect score!',
            'body' => 'You scored 100% on :quiz. Keep that ear sharp!',
            'params' => ['quiz' => $this->quizTitle],
        ];
    }
}
