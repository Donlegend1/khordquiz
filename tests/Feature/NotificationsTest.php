<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Notifications\PerfectScore;
use App\Notifications\TrialEndingSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_notifications(): void
    {
        $this->get(route('notifications'))->assertRedirect(route('login'));
    }

    public function test_members_see_only_their_own_notifications_newest_first(): void
    {
        $member = User::factory()->create();
        $member->notify(new PerfectScore('Diatonic Intervals'));
        $this->travel(1)->minutes();
        $member->notify(new PerfectScore('C Root Position'));
        User::factory()->create()->notify(new PerfectScore('Someone else'));

        $this->actingAs($member)->get(route('notifications'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Member/Notifications')
                ->has('notifications', 2)
                ->where('notifications.0.kind', 'perfect')
                ->where('notifications.0.title', 'Perfect score!')
                ->where('notifications.0.params.quiz', 'C Root Position')
                ->where('notifications.0.read', false)
                ->where('unreadNotifications', 2)
            );
    }

    public function test_members_can_mark_one_or_all_notifications_read(): void
    {
        $member = User::factory()->create();
        $member->notify(new PerfectScore('One'));
        $member->notify(new PerfectScore('Two'));
        $first = $member->notifications()->first();

        $this->actingAs($member)->patch(route('notifications.read', $first->id))->assertRedirect();
        $this->assertNotNull($first->fresh()->read_at);
        $this->assertSame(1, $member->unreadNotifications()->count());

        $this->actingAs($member)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $member->unreadNotifications()->count());
    }

    public function test_members_cannot_mark_someone_elses_notification_read(): void
    {
        $owner = User::factory()->create();
        $owner->notify(new PerfectScore('Mine'));
        $notification = $owner->notifications()->first();

        $this->actingAs(User::factory()->create())->patch(route('notifications.read', $notification->id));

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_a_perfect_quiz_from_the_app_creates_a_notification_but_a_partial_one_does_not(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs($member);
        $slug = rawurlencode('Relative Tone Pitch no1');

        $this->postJson('/api/v1/quizzes/'.$slug.'/sessions', ['correct' => 20, 'total' => 25])->assertSuccessful();
        $this->assertSame(0, $member->notifications()->count());

        $this->postJson('/api/v1/quizzes/'.$slug.'/sessions', ['correct' => 25, 'total' => 25])->assertSuccessful();
        $this->assertSame(['quiz' => 'Relative Tone Pitch #1'], $member->notifications()->sole()->data['params']);
    }

    public function test_a_recorded_payment_creates_a_notification(): void
    {
        $member = User::factory()->create();

        SubscriptionPayment::query()->create(['user_id' => $member->id, 'amount' => '$49.99', 'plan' => 'Premium · Yearly', 'status' => 'Paid', 'paid_on' => 'Nov 19, 2026']);

        $data = $member->notifications()->sole()->data;
        $this->assertSame('payment', $data['kind']);
        $this->assertSame(['amount' => '$49.99', 'plan' => 'Premium · Yearly'], $data['params']);
    }

    public function test_trials_ending_soon_are_notified_once(): void
    {
        $endingSoon = User::factory()->create();
        Subscription::forUser($endingSoon)->update(['access' => 'trial', 'plan_id' => 'yearly', 'trial_ends_at' => now()->addDays(2)->subHour()]);
        $endingLater = User::factory()->create();
        Subscription::forUser($endingLater)->update(['access' => 'trial', 'plan_id' => 'yearly', 'trial_ends_at' => now()->addDays(5)]);
        $cancelled = User::factory()->create();
        Subscription::forUser($cancelled)->update(['access' => 'trial', 'plan_id' => 'yearly', 'trial_cancelled' => true, 'trial_ends_at' => now()->addDay()]);

        $this->artisan('notifications:trial-ending')->assertSuccessful();
        $this->artisan('notifications:trial-ending')->assertSuccessful();

        $notification = $endingSoon->notifications()->sole();
        $this->assertSame(TrialEndingSoon::class, $notification->type);
        $this->assertSame(2, $notification->data['params']['days']);
        $this->assertSame(0, $endingLater->notifications()->count());
        $this->assertSame(0, $cancelled->notifications()->count());
    }
}
