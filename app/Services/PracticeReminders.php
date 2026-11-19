<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\PracticeReminder;
use App\Notifications\StreakAlert;
use Illuminate\Support\Carbon;

/**
 * Sends the scheduled nudges from Settings (notifications:reminders, every
 * five minutes):
 *
 * - Practice reminder: at the member's reminder time (their own timezone),
 *   if they haven't practised today. Once per local day.
 * - Streak alert: from STREAK_ALERT_HOUR local time, if they have a streak
 *   going and haven't practised today. Once per streak day.
 *
 * "Practised today" follows users.last_practiced_on, which the streak logic
 * keeps in the app timezone — so a streak alert matches exactly when the
 * streak would break.
 */
class PracticeReminders
{
    /** How late a reminder may still go out if a scheduler run was missed. */
    public const REMINDER_WINDOW_MINUTES = 15;

    public const STREAK_ALERT_HOUR = 20;

    /**
     * @return array{reminders: int, streak_alerts: int}
     */
    public function send(): array
    {
        $sent = ['reminders' => 0, 'streak_alerts' => 0];
        $today = now()->toDateString();

        UserPreference::query()
            ->with('user')
            ->where('notify_by', '!=', 'Off')
            ->where(fn ($query) => $query->where('reminders', true)->orWhere('streak_alerts', true))
            ->each(function (UserPreference $preferences) use ($today, &$sent) {
                $user = $preferences->user;

                if ($user === null || $user->isSuspended() || $user->last_practiced_on?->toDateString() === $today) {
                    return;
                }

                $localNow = $preferences->localNow();

                if ($preferences->reminders && $this->isReminderDue($preferences->reminder_time, $localNow)) {
                    $sent['reminders'] += (int) $this->remind($user, $localNow->toDateString());
                }

                if ($preferences->streak_alerts && $user->current_streak > 0 && $localNow->hour >= self::STREAK_ALERT_HOUR) {
                    $sent['streak_alerts'] += (int) $this->alertStreak($user, $today);
                }
            });

        return $sent;
    }

    public function isReminderDue(?string $reminderTime, Carbon $localNow): bool
    {
        try {
            $due = Carbon::createFromFormat('g:i A', trim((string) $reminderTime), $localNow->getTimezone())
                ->setDate($localNow->year, $localNow->month, $localNow->day)
                ->startOfMinute();
        } catch (\Throwable) {
            return false;
        }

        return $localNow->gte($due) && $localNow->lt($due->copy()->addMinutes(self::REMINDER_WINDOW_MINUTES));
    }

    private function remind(User $user, string $localDate): bool
    {
        if ($this->alreadySent($user, PracticeReminder::class, $localDate)) {
            return false;
        }

        $user->notify(new PracticeReminder((int) $user->daily_goal, $localDate));

        return true;
    }

    private function alertStreak(User $user, string $today): bool
    {
        if ($this->alreadySent($user, StreakAlert::class, $today)) {
            return false;
        }

        $user->notify(new StreakAlert((int) $user->current_streak, $today));

        return true;
    }

    private function alreadySent(User $user, string $type, string $on): bool
    {
        return $user->notifications()->where('type', $type)->where('data->on', $on)->exists();
    }
}
