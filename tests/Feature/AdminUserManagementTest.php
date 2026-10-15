<?php

namespace Tests\Feature;

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
                ->where('stats.admins', 1)
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
}
