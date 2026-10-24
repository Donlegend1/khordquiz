<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_screen_can_be_rendered(): void
    {
        $response = $this->get('/signup?plan=yearly');

        $response->assertStatus(200);
    }

    public function test_member_can_sign_up_with_a_plan_and_payment_method(): void
    {
        $response = $this->post('/signup', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'terms' => true,
            'plan' => 'yearly',
            'payment_method' => 'stripe',
        ]);

        $this->assertAuthenticated();
        // Signup no longer lands straight on the dashboard — it goes through the
        // profile-setup onboarding step first, same as the mobile app's flow.
        $response->assertRedirect(route('profile-setup.edit', absolute: false));
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'plan_choice' => 'yearly',
            'payment_method' => 'stripe',
        ]);
    }

    public function test_signup_requires_a_valid_plan_and_payment_method(): void
    {
        $response = $this->post('/signup', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'terms' => true,
            'plan' => 'not-a-plan',
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasErrors(['plan', 'payment_method']);
        $this->assertGuest();
    }
}
