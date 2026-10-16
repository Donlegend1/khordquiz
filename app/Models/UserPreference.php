<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    public const APPEARANCES = ['System', 'Light', 'Dark'];

    public const INSTRUMENTS = ['Piano', 'Violin', 'Strings', 'Choir', 'Organ', 'Saxophone', 'Xylophone', 'Guitar', 'Voice', 'Synth'];

    protected $fillable = [
        'user_id',
        'appearance',
        'haptics',
        'language',
        'push_notifications',
        'reminder_time',
        'reminders',
        'sound_effects',
        'streak_alerts',
        'default_instrument',
    ];

    protected function casts(): array
    {
        return [
            'haptics' => 'boolean',
            'push_notifications' => 'boolean',
            'reminders' => 'boolean',
            'sound_effects' => 'boolean',
            'streak_alerts' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(User $user): self
    {
        return self::query()->firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSettings(User $user): array
    {
        return [
            'appearance' => $this->appearance,
            'avatar' => $user->avatar,
            'country' => $user->country,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'haptics' => $this->haptics,
            'language' => $this->language,
            'last_name' => $user->last_name,
            'push_notifications' => $this->push_notifications,
            'reminder_time' => $this->reminder_time,
            'reminders' => $this->reminders,
            'skill_level' => $user->skill_level,
            'sound_effects' => $this->sound_effects,
            'streak_alerts' => $this->streak_alerts,
            'daily_goal' => $user->daily_goal,
            'default_instrument' => $this->default_instrument,
        ];
    }
}
