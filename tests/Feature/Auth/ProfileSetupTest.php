<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_setup_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile-setup');

        $response->assertStatus(200);
    }

    public function test_member_can_complete_profile_setup(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile-setup', [
            'avatar' => 'piano',
            'skill_level' => 'Intermediate',
            'language' => 'Français',
            'default_instrument' => 'Guitar',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar' => 'piano',
            'skill_level' => 'Intermediate',
            'language' => 'Français',
            'default_instrument' => 'Guitar',
        ]);
    }

    public function test_profile_setup_requires_valid_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile-setup', [
            'avatar' => 'not-a-real-avatar',
            'skill_level' => 'Expert',
            'language' => 'Klingon',
            'default_instrument' => 'Kazoo',
        ]);

        $response->assertSessionHasErrors(['avatar', 'skill_level', 'language', 'default_instrument']);
    }
}
