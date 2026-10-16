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

#[Fillable(['name', 'first_name', 'last_name', 'email', 'password', 'role', 'current_streak', 'longest_streak', 'last_practiced_on', 'daily_goal', 'country', 'skill_level', 'avatar', 'terms_accepted_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // Same three options as the mobile app's Edit Profile skill-level picker.
    public const SKILL_LEVELS = ['Beginner', 'Intermediate', 'Advanced'];

    // Same ids as the mobile app's avatar picker.
    public const AVATARS = [
        'piano',
        'electric-guitar',
        'note',
        'headphones',
        'metronome',
        'saxophone',
        'trumpet',
        'violin',
        'acoustic-guitar',
        'microphone',
    ];

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

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(Award::class);
    }
}
