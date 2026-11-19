<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Routes a notification by the member's Settings → "Notify me by" choice:
 *   Push  → in-app (the Notifications page; the mobile app reads the same feed)
 *   Email → in-app and an email
 *   Off   → nothing, unless the notification is an account notice
 *           ($alwaysInApp), which still lands in-app.
 *
 * The title, body and params from toArray() double as the email's copy, so a
 * notification only has to describe itself once.
 */
trait DeliveredByPreference
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $preferences = $notifiable instanceof User ? UserPreference::forUser($notifiable) : null;

        if ($preferences?->notificationsOff()) {
            return $this->alwaysInApp() ? ['database'] : [];
        }

        return $preferences?->wantsEmail() ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject(__($data['title'], $data['params']))
            ->line(__($data['body'], $data['params']))
            ->action(__('Open KhordQuiz'), isset($data['url']) ? url($data['url']) : route('notifications'));
    }

    protected function alwaysInApp(): bool
    {
        return false;
    }
}
