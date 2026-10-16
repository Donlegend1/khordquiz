<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCode extends Notification
{
    use Queueable;

    public function __construct(public string $code, public int $expiresInMinutes)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your KhordQuiz password reset code')
            ->greeting('Reset your password')
            ->line('Use this 6-digit code to reset your KhordQuiz password:')
            ->line($this->code)
            ->line('This code expires in '.$this->expiresInMinutes.' minutes.')
            ->line('If you did not request a password reset, you can ignore this email.');
    }
}
