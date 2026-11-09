<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

// Sent when the Kingsleykhordpiano sync creates a KhordQuiz account: the
// membership already includes KhordQuiz, they just need to choose a password.
// The link is a normal password-reset link, so it expires like one
// (config/auth.php → passwords.users.expire) — "Forgot password" works after.
class KingsleyKhordPianoInvitation extends Notification
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $token = Password::broker()->createToken($notifiable);
        $url = route('password.reset', ['token' => $token, 'email' => $notifiable->email]);

        return (new MailMessage)
            ->subject(__('Your KhordQuiz access is ready'))
            ->greeting(__('Hi :name,', ['name' => $notifiable->first_name ?: $notifiable->name]))
            ->line(__('Your Kingsleykhordpiano membership now includes KhordQuiz — ear-training quizzes for intervals, chords, scales and progressions.'))
            ->line(__('Your account is set up with the email :email. Choose a password to sign in:', ['email' => $notifiable->email]))
            ->action(__('Set your password'), $url)
            ->line(__('This link expires in :count minutes. After that, use "Forgot password" on the sign-in page.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]));
    }
}
