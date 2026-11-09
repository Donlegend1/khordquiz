<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_can_save_each_supported_avatar_without_changing_other_profile_fields(): void
    {
        $user = User::factory()->create(['name' => 'Test Member']);
        $other = User::factory()->create(['avatar' => 'piano']);

        foreach (User::AVATARS as $avatar) {
            $this->actingAs($user)->patch(route('profile.update'), [
                'first_name' => 'Test',
                'last_name' => 'Member',
                'avatar' => $avatar,
                'name' => 'Changed name',
                'user_id' => $other->id,
            ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

            $this->assertSame($avatar, $user->fresh()->avatar);
            $this->assertSame('Test Member', $user->fresh()->name);
            $this->assertSame('piano', $other->fresh()->avatar);
        }
    }

    public function test_unsupported_avatar_is_rejected(): void
    {
        $user = User::factory()->create(['avatar' => 'violin']);

        foreach (['invalid-icon', 'unknown-avatar'] as $avatar) {
            $this->actingAs($user)->patch(route('profile.update'), ['first_name' => 'Test', 'last_name' => 'Member', 'avatar' => $avatar])
                ->assertSessionHasErrors('avatar');
            $this->assertSame('violin', $user->fresh()->avatar);
        }
    }

    public function test_guests_cannot_update_an_avatar(): void
    {
        $this->patch(route('profile.update'), ['avatar' => 'piano'])->assertRedirect(route('login'));
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'country' => 'Nigeria',
                'skill_level' => 'Intermediate',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('Nigeria', $user->country);
        $this->assertSame('Intermediate', $user->skill_level);
    }

    public function test_email_cannot_be_changed_from_the_profile_form(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com']);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'changed@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('original@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
