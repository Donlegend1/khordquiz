<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/v1/register', $this->registration([
            'email' => 'Kings@Example.com',
            'device_name' => 'Pixel 8',
        ]));

        $response->assertCreated()
            ->assertJsonPath('message', 'Account created.')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'kings@example.com')
            ->assertJsonPath('user.first_name', 'Kings')
            ->assertJsonPath('user.last_name', 'Don')
            ->assertJsonPath('user.name', 'Kings Don')
            ->assertJsonPath('user.country', 'Nigeria');

        $this->assertNotEmpty($response->json('token'));

        $user = User::query()->where('email', 'kings@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(Role::Member, $user->role);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Pixel 8',
        ]);

        $this->withToken($response->json('token'))
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('user.email', 'kings@example.com');
    }

    public function test_registration_requires_the_terms_and_a_known_country(): void
    {
        $this->postJson('/api/v1/register', $this->registration([
            'terms' => false,
            'country' => 'Narnia',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['terms', 'country']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_members_can_sign_in_and_out(): void
    {
        $user = User::factory()->create([
            'email' => 'ada@example.com',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'Ada@Example.com',
            'password' => 'password',
            'device_name' => 'iPhone',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Signed in.')
            ->assertJsonPath('user.id', $user->id);

        $token = $response->json('token');

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Signed out.');

        $this->assertSame(0, PersonalAccessToken::query()->count());

        // The Sanctum guard keeps the user resolved on this application
        // instance. A later request starts clean, so drop that cache here.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/user')
            ->assertUnauthorized();
    }

    public function test_sign_in_rejects_a_wrong_password(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/v1/login', [
            'email' => 'ada@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_current_user_requires_a_token(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_a_member_can_finish_profile_setup(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Kings',
            'last_name' => 'Don',
            'name' => 'Kings Don',
        ]);

        $token = $user->createToken('phone')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/v1/profile', [
                'avatar' => 'piano',
                'skill_level' => 'Beginner',
                'country' => 'Ghana',
            ])
            ->assertOk()
            ->assertJsonPath('user.avatar', 'piano')
            ->assertJsonPath('user.skill_level', 'Beginner')
            ->assertJsonPath('user.country', 'Ghana')
            ->assertJsonPath('user.name', 'Kings Don');

        $this->withToken($token)
            ->patchJson('/api/v1/profile', [
                'avatar' => 'not-an-avatar',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function registration(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Kings',
            'last_name' => 'Don',
            'email' => 'kings@example.com',
            'password' => 'password',
            'country' => 'Nigeria',
            'terms' => true,
        ], $overrides);
    }
}
