<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\PaymentReceived;
use App\Notifications\PerfectScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_preferences_are_saved_and_shown_back_on_the_settings_page(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->from(route('settings'))
            ->patch(route('settings.preferences'), [
                'appearance' => 'Dark',
                'notation' => 'Numbers',
                'sound_effects' => false,
                'haptics' => false,
                'reminders' => false,
                'reminder_time' => '8:30 AM',
                'streak_alerts' => false,
                'notify_by' => 'Email',
            ])
            ->assertRedirect(route('settings'));

        $this->actingAs($member)->get(route('settings'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('preferences.appearance', 'Dark')
                ->where('preferences.notation', 'Numbers')
                ->where('preferences.sound_effects', false)
                ->where('preferences.reminder_time', '8:30 AM')
                ->where('preferences.notify_by', 'Email')
            );
    }

    public function test_every_page_receives_the_preferences_that_change_how_it_behaves(): void
    {
        $member = User::factory()->create();
        UserPreference::forUser($member)->update(['appearance' => 'Dark', 'notation' => 'Numbers', 'haptics' => false]);

        $this->actingAs($member)->get(route('dashboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('preferences.appearance', 'Dark')
                ->where('preferences.notation', 'Numbers')
                ->where('preferences.haptics', false)
                ->where('preferences.sound_effects', true)
            );
    }

    public function test_invalid_values_are_rejected(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->patch(route('settings.preferences'), [
            'appearance' => 'Neon',
            'reminder_time' => '25:00',
            'notify_by' => 'Pigeon',
            'timezone' => 'Mars/Olympus',
        ])->assertSessionHasErrors(['appearance', 'reminder_time', 'notify_by', 'timezone']);
    }

    public function test_the_browser_timezone_is_saved_in_the_background(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->patchJson(route('settings.preferences'), ['timezone' => 'Africa/Lagos'])->assertNoContent();

        $this->assertSame('Africa/Lagos', UserPreference::forUser($member)->timezone);
    }

    public function test_notify_by_and_the_apps_push_switch_stay_in_step(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs($member);

        $this->patchJson('/api/v1/settings', ['push_notifications' => false])
            ->assertOk()
            ->assertJsonPath('settings.notify_by', 'Off');

        $this->patchJson('/api/v1/settings', ['notify_by' => 'Email'])
            ->assertOk()
            ->assertJsonPath('settings.push_notifications', true);
    }

    public function test_notify_by_decides_how_notifications_are_delivered(): void
    {
        Notification::fake();
        $member = User::factory()->create();
        $preferences = UserPreference::forUser($member);

        $member->notify(new PerfectScore('Intervals'));
        Notification::assertSentTo($member, PerfectScore::class, fn ($notification, array $channels) => $channels === ['database']);

        $preferences->update(['notify_by' => 'Email']);
        $member->notify(new PerfectScore('Intervals'));
        Notification::assertSentTo($member, PerfectScore::class, fn ($notification, array $channels) => $channels === ['database', 'mail']);

        $preferences->update(['notify_by' => 'Off']);
        $member->notify(new PerfectScore('Intervals'));
        $member->notify(new PaymentReceived('$5.99', 'Standard · Monthly'));
        Notification::assertSentToTimes($member, PerfectScore::class, 2);
        // Account notices still land in-app.
        Notification::assertSentTo($member, PaymentReceived::class, fn ($notification, array $channels) => $channels === ['database']);
    }
}
