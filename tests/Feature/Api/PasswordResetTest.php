<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use App\Support\MobilePasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reset_code_can_be_requested_verified_and_used(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'ada@example.com',
        ]);
        $oldToken = $user->createToken('phone')->plainTextToken;

        $this->postJson('/api/v1/forgot-password', [
            'email' => 'Ada@Example.com',
        ])->assertOk()
            ->assertJsonPath('message', "If an account exists for that email, we've sent a 6-digit code.");

        $code = $this->sentCode($user);

        $verify = $this->postJson('/api/v1/verify-otp', [
            'email' => 'ada@example.com',
            'code' => $code,
        ]);

        $verify->assertOk()->assertJsonPath('message', 'Code verified.');
        $resetToken = $verify->json('reset_token');
        $this->assertIsString($resetToken);

        $this->postJson('/api/v1/reset-password', [
            'email' => 'ada@example.com',
            'reset_token' => $resetToken,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()
            ->assertJsonPath('message', 'Your password has been reset. Sign in with your new password.');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseCount('password_reset_codes', 0);

        $this->withToken($oldToken)
            ->getJson('/api/v1/user')
            ->assertUnauthorized();

        $this->postJson('/api/v1/login', [
            'email' => 'ada@example.com',
            'password' => 'new-password',
        ])->assertOk();
    }

    public function test_unknown_emails_get_the_same_response_and_no_code(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/forgot-password', [
            'email' => 'missing@example.com',
        ])->assertOk()
            ->assertJsonPath('message', "If an account exists for that email, we've sent a 6-digit code.");

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_codes', 0);
    }

    public function test_an_expired_or_wrong_code_is_rejected(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])->assertOk();
        $code = $this->sentCode($user);
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->postJson('/api/v1/verify-otp', [
            'email' => $user->email,
            'code' => $wrong,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->travel(MobilePasswordReset::EXPIRES_MINUTES + 1)->minutes();

        $this->postJson('/api/v1/verify-otp', [
            'email' => $user->email,
            'code' => $code,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_too_many_wrong_codes_invalidate_the_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])->assertOk();
        $code = $this->sentCode($user);
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($attempt = 0; $attempt < MobilePasswordReset::MAX_ATTEMPTS; $attempt++) {
            $this->postJson('/api/v1/verify-otp', [
                'email' => $user->email,
                'code' => $wrong,
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/verify-otp', [
            'email' => $user->email,
            'code' => $code,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->assertDatabaseCount('password_reset_codes', 0);
    }

    private function sentCode(User $user): string
    {
        $code = null;

        Notification::assertSentTo($user, PasswordResetCode::class, function (PasswordResetCode $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $this->assertNotNull($code);

        return $code;
    }
}
