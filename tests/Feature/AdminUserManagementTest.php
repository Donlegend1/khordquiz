<?php

namespace Tests\Feature;

use App\Models\QuestionReport;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use App\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_view_the_users_list(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_can_view_the_users_list_with_stats(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->where('stats.total', 3)
                ->where('stats.inactiveMembers', 3)
                ->has('users.data', 3));
    }

    public function test_admin_can_search_users_by_name(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Ada Obi']);
        User::factory()->create(['name' => 'Tunde Bello']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'Ada']))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.name', 'Ada Obi'));
    }

    public function test_admin_can_suspend_a_member(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $member))
            ->assertRedirect();

        $this->assertNotNull($member->fresh()->suspended_at);
    }

    public function test_admin_can_unsuspend_a_member(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['suspended_at' => now()]);

        $this->actingAs($admin)->post(route('admin.users.unsuspend', $member));

        $this->assertNull($member->fresh()->suspended_at);
    }

    public function test_admin_cannot_suspend_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $admin))
            ->assertForbidden();

        $this->assertNull($admin->fresh()->suspended_at);
    }

    public function test_admin_can_promote_a_member_to_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.role', $member), ['role' => 'admin']);

        $this->assertSame(Role::Admin, $member->fresh()->role);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.role', $admin), ['role' => 'member'])
            ->assertForbidden();

        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }

    public function test_suspended_member_cannot_log_in(): void
    {
        $member = User::factory()->create(['suspended_at' => now()]);

        $this->post(route('login'), [
            'email' => $member->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_suspending_an_already_logged_in_member_logs_them_out_on_next_request(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('dashboard'))->assertOk();

        $member->update(['suspended_at' => now()]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_can_view_a_member_detail_page(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.show', $member))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Show')
                ->where('member.id', $member->id)
                ->where('member.email', $member->email));
    }

    public function test_detail_page_keeps_archived_history_and_reports_scoped_to_the_selected_member(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['language' => 'German', 'default_instrument' => 'Violin']);
        $other = User::factory()->create();
        $quiz = Quiz::factory()->create();
        foreach (range(1, 21) as $index) {
            QuizSession::create(['user_id' => $member->id, 'slug' => $quiz->slug, 'correct' => 20,
                'total' => 25, 'played_at' => now()->subMinutes($index), 'sound' => 'Violin']);
        }
        QuizSession::create(['user_id' => $other->id, 'slug' => $quiz->slug, 'correct' => 0,
            'total' => 25, 'played_at' => now()]);
        foreach ([$member, $other] as $reporter) {
            QuestionReport::create(['user_id' => $reporter->id, 'quiz_id' => $quiz->id, 'question_id' => 1,
                'quiz_title' => $quiz->title, 'question_prompt' => 'Question', 'reason' => 'Audio does not play']);
        }
        $props = $this->actingAs($admin)->get(route('admin.users.show', ['user' => $member, 'tab' => 'Activity']))->assertOk()->viewData('page')['props'];
        $this->assertSame(21, $props['member']['completed_runs']);
        $this->assertSame(1, $props['member']['completed_quizzes']);
        $this->assertSame(80, $props['member']['accuracy']);
        $this->assertSame('German', $props['member']['language']);
        $this->assertSame('Violin', $props['member']['default_instrument']);
        $this->assertSame(1, $props['reports']['total']);
        $this->assertCount(1, $props['reports']['recent']);
        $this->assertCount(20, $props['history']['data']);
        $this->assertStringContainsString('tab=Activity', $props['history']['next_page_url']);
        $second = $this->get($props['history']['next_page_url'])->assertOk()->viewData('page')['props'];
        $this->assertSame('Activity', $second['activeTab']);
        $this->assertCount(1, $second['history']['data']);
        $this->actingAs($other)->get(route('admin.users.show', $member))->assertForbidden();
    }

    public function test_admin_can_assign_a_plan_to_a_member(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.plan', $member), [
            'plan_choice' => 'yearly',
            'payment_method' => 'stripe',
            'plan_expires_at' => now()->addYear()->toDateString(),
        ]);

        $member->refresh();
        $this->assertSame('yearly', $member->plan_choice);
        $this->assertSame('stripe', $member->payment_method);
        $this->assertNotNull($member->plan_started_at);
        $this->assertTrue($member->plan_expires_at->isFuture());
    }

    public function test_assigning_a_lifetime_plan_clears_any_expiry(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['plan_choice' => 'monthly', 'plan_expires_at' => now()->addMonth()]);

        $this->actingAs($admin)->post(route('admin.users.plan', $member), ['plan_choice' => 'lifetime']);

        $this->assertNull($member->refresh()->plan_expires_at);
    }

    public function test_admin_can_extend_a_members_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $expires = now()->addDays(5);
        $member = User::factory()->create(['plan_choice' => 'monthly', 'plan_expires_at' => $expires]);

        $this->actingAs($admin)->post(route('admin.users.plan.extend', $member), ['days' => 30]);

        $member->refresh();
        $this->assertTrue($member->plan_expires_at->isSameDay($expires->copy()->addDays(30)));
    }

    public function test_extending_an_already_expired_plan_counts_from_today(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['plan_choice' => 'monthly', 'plan_expires_at' => now()->subDays(10)]);

        $this->actingAs($admin)->post(route('admin.users.plan.extend', $member), ['days' => 30]);

        $this->assertTrue($member->refresh()->plan_expires_at->isSameDay(now()->addDays(30)));
    }

    public function test_cannot_extend_a_plan_that_was_never_assigned(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.plan.extend', $member), ['days' => 30])->assertStatus(422);
    }

    public function test_admin_can_cancel_a_members_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create([
            'plan_choice' => 'yearly',
            'payment_method' => 'paypal',
            'plan_started_at' => now(),
            'plan_expires_at' => now()->addYear(),
        ]);

        $this->actingAs($admin)->post(route('admin.users.plan.cancel', $member));

        $member->refresh();
        $this->assertNull($member->plan_choice);
        $this->assertNull($member->plan_started_at);
        $this->assertNull($member->plan_expires_at);
    }
}
