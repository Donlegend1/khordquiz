<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\PracticeReminder;
use App\Notifications\StreakAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PracticeRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminders_go_out_at_the_members_local_time_once_a_day(): void
    {
        // 18:02 UTC is 19:02 in Lagos (UTC+1).
        Carbon::setTestNow(Carbon::parse('2026-11-19 18:02:00', 'UTC'));
        $member = $this->memberWithPreferences(['reminder_time' => '7:00 PM', 'timezone' => 'Africa/Lagos', 'streak_alerts' => false]);
        $elsewhere = $this->memberWithPreferences(['reminder_time' => '7:00 PM', 'timezone' => 'America/New_York', 'streak_alerts' => false]);

        $this->artisan('notifications:reminders')->assertSuccessful();
        $this->artisan('notifications:reminders')->assertSuccessful();

        $notification = $member->notifications()->sole();
        $this->assertSame(PracticeReminder::class, $notification->type);
        $this->assertSame('2026-11-19', $notification->data['on']);
        $this->assertSame(0, $elsewhere->notifications()->count());
    }

    public function test_members_who_already_practised_or_turned_reminders_off_are_left_alone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-19 19:05:00', 'UTC'));
        $practised = $this->memberWithPreferences(['reminder_time' => '7:00 PM', 'timezone' => 'UTC'], ['last_practiced_on' => '2026-11-19']);
        $remindersOff = $this->memberWithPreferences(['reminders' => false, 'streak_alerts' => false, 'reminder_time' => '7:00 PM', 'timezone' => 'UTC']);
        $notificationsOff = $this->memberWithPreferences(['notify_by' => 'Off', 'reminder_time' => '7:00 PM', 'timezone' => 'UTC']);

        $this->artisan('notifications:reminders')->assertSuccessful();

        $this->assertSame(0, $practised->notifications()->count());
        $this->assertSame(0, $remindersOff->notifications()->count());
        $this->assertSame(0, $notificationsOff->notifications()->count());
    }

    public function test_an_evening_streak_alert_warns_members_whose_streak_is_about_to_break(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-19 20:30:00', 'UTC'));
        $atRisk = $this->memberWithPreferences(['reminders' => false, 'timezone' => 'UTC'], ['current_streak' => 6, 'last_practiced_on' => '2026-11-18']);
        $noStreak = $this->memberWithPreferences(['reminders' => false, 'timezone' => 'UTC'], ['current_streak' => 0, 'last_practiced_on' => '2026-11-10']);
        $alertsOff = $this->memberWithPreferences(['reminders' => false, 'streak_alerts' => false, 'timezone' => 'UTC'], ['current_streak' => 4, 'last_practiced_on' => '2026-11-18']);

        $this->artisan('notifications:reminders')->assertSuccessful();
        $this->artisan('notifications:reminders')->assertSuccessful();

        $notification = $atRisk->notifications()->sole();
        $this->assertSame(StreakAlert::class, $notification->type);
        $this->assertSame(6, $notification->data['params']['days']);
        $this->assertSame(0, $noStreak->notifications()->count());
        $this->assertSame(0, $alertsOff->notifications()->count());
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @param  array<string, mixed>  $attributes
     */
    private function memberWithPreferences(array $preferences, array $attributes = []): User
    {
        $member = User::factory()->create($attributes);
        UserPreference::forUser($member)->update($preferences);

        return $member;
    }
}
