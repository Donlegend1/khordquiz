<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobilePasswordReset
{
    public const EXPIRES_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public function send(string $email): void
    {
        $user = $this->user($email);

        if (! $user) {
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_codes')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ],
        );

        Cache::forget($this->attemptsKey($user->email));

        $user->notify(new PasswordResetCode($code, self::EXPIRES_MINUTES));
    }

    public function verify(string $email, string $code): string
    {
        $record = $this->record($email);

        if ($record === null || $this->expired($record->created_at) || $this->tooManyAttempts($record->email)) {
            $this->invalidate($record);

            throw $this->invalidCode();
        }

        if (! Hash::check($code, $record->token)) {
            $this->hit($record->email);

            if ($this->tooManyAttempts($record->email)) {
                $this->delete($record->email);
            }

            throw $this->invalidCode();
        }

        $resetToken = Str::random(64);

        DB::table('password_reset_codes')->where('email', $record->email)->update([
            'token' => Hash::make($resetToken),
            'created_at' => now(),
        ]);

        Cache::forget($this->attemptsKey($record->email));

        return $resetToken;
    }

    public function reset(string $email, string $resetToken, string $password): void
    {
        $record = $this->record($email);

        if ($record === null || $this->expired($record->created_at) || ! Hash::check($resetToken, $record->token)) {
            throw ValidationException::withMessages([
                'reset_token' => 'This password reset session is invalid or has expired.',
            ]);
        }

        $user = User::query()->where('email', $record->email)->first();

        if (! $user) {
            $this->delete($record->email);

            throw ValidationException::withMessages([
                'reset_token' => 'This password reset session is invalid or has expired.',
            ]);
        }

        $user->forceFill([
            'password' => $password,
        ])->save();

        $user->tokens()->delete();
        $this->delete($record->email);
        Cache::forget($this->attemptsKey($record->email));
    }

    private function user(string $email): ?User
    {
        return User::query()
            ->whereRaw('lower(email) = ?', [Str::lower($email)])
            ->first();
    }

    private function record(string $email): ?object
    {
        return DB::table('password_reset_codes')
            ->whereRaw('lower(email) = ?', [Str::lower($email)])
            ->first();
    }

    private function expired(mixed $createdAt): bool
    {
        if ($createdAt === null) {
            return true;
        }

        return Carbon::parse($createdAt)->addMinutes(self::EXPIRES_MINUTES)->isPast();
    }

    private function attemptsKey(string $email): string
    {
        return 'password-reset-code-attempts:'.Str::lower($email);
    }

    private function tooManyAttempts(string $email): bool
    {
        return (int) Cache::get($this->attemptsKey($email), 0) >= self::MAX_ATTEMPTS;
    }

    private function hit(string $email): void
    {
        $key = $this->attemptsKey($email);
        $attempts = (int) Cache::get($key, 0) + 1;

        Cache::put($key, $attempts, now()->addMinutes(self::EXPIRES_MINUTES));
    }

    private function invalidate(?object $record): void
    {
        if ($record && $this->tooManyAttempts($record->email)) {
            $this->delete($record->email);
        }
    }

    private function delete(string $email): void
    {
        DB::table('password_reset_codes')->where('email', $email)->delete();
    }

    private function invalidCode(): ValidationException
    {
        return ValidationException::withMessages([
            'code' => 'The code is invalid or has expired.',
        ]);
    }
}
