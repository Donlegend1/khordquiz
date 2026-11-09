<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\Concerns\NormalizesEmail;
use App\Models\User;
use App\Services\KingsleyKhordPiano\MemberSync;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    use NormalizesEmail;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $user = User::query()
            ->whereRaw('lower(email) = ?', [$this->string('email')->toString()])
            ->first();

        if (! $user || ! Hash::check($this->string('password')->toString(), $user->password)) {
            RateLimiter::hit($this->throttleKey());

            if (! $user && app(MemberSync::class)->provisionByEmail($this->string('email')->toString())) {
                throw ValidationException::withMessages(['email' => __('Your Kingsleykhordpiano membership includes KhordQuiz! We\'ve emailed you a link to set your password.')]);
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        app(MemberSync::class)->refresh($user);

        return $user->refresh();
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return 'api-login|'.Str::transliterate($this->string('email')->toString().'|'.$this->ip());
    }
}
