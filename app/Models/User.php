<?php

namespace App\Models;

// Not implementing MustVerifyEmail yet: MAIL_MAILER is still 'log' (no real
// mail delivery configured), so enforcing verification would lock every
// member — including existing test accounts — out of the app with no way to
// receive the verification link. Revisit once real mail delivery is set up.
use App\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'first_name', 'last_name', 'email', 'password', 'role', 'suspended_at', 'current_streak', 'longest_streak', 'last_practiced_on', 'daily_goal', 'default_instrument', 'country', 'skill_level', 'avatar', 'language', 'terms_accepted_at', 'plan_choice', 'payment_method', 'plan_started_at', 'plan_expires_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // Same three options as the mobile app's Edit Profile skill-level picker.
    public const SKILL_LEVELS = ['Beginner', 'Intermediate', 'Advanced'];

    // Same 10 options as the mobile app's AVATAR_OPTIONS (src/components/avatar.tsx)
    // — kept in sync by hand, same as the instrument/language lists elsewhere.
    public const AVATARS = ['piano', 'electric-guitar', 'note', 'headphones', 'metronome', 'saxophone', 'trumpet', 'violin', 'acoustic-guitar', 'microphone'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'suspended_at' => 'datetime',
            'plan_started_at' => 'datetime',
            'plan_expires_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'last_practiced_on' => 'date',
            'daily_goal' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    // A plan with no expiry date (e.g. Lifetime, or one an admin granted without
    // setting a date) never lapses. One with a date is expired once that date has
    // passed — this only governs the admin-managed plan fields, not real billing.
    public function hasPlanExpired(): bool
    {
        return $this->plan_choice !== null && $this->plan_expires_at !== null && $this->plan_expires_at->isPast();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(Award::class);
    }
}
