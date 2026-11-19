<?php

namespace App\Notifications;

use App\Models\SupportMessage;
use App\Notifications\Concerns\DeliveredByPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * The support team answered one of the member's requests. Links to the thread.
 */
class SupportReplied extends Notification
{
    use DeliveredByPreference, Queueable;

    public function __construct(public SupportMessage $supportMessage) {}

    /**
     * A reply to something they asked us is never noise, so it still shows
     * in-app when notifications are switched off.
     */
    protected function alwaysInApp(): bool
    {
        return true;
    }

    /**
     * @return array{kind: string, title: string, body: string, params: array{subject: string}, url: string, support_message_id: int}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'support',
            'title' => 'Support replied',
            'body' => 'We replied to your request “:subject”.',
            'params' => ['subject' => $this->supportMessage->title()],
            'url' => route('support.requests.show', $this->supportMessage, false),
            'support_message_id' => $this->supportMessage->id,
        ];
    }
}
