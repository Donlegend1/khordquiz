<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class UserPreference extends Model
{
    public const APPEARANCES = ['System', 'Light', 'Dark'];

    public const NOTATIONS = ['Solfège', 'Numbers'];

    /** Push = in-app (and the mobile app's push), Email = in-app plus an email, Off = nothing. */
    public const NOTIFY_BY = ['Push', 'Email', 'Off'];

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
        'notation',
        'notify_by',
        'timezone',
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
        $preferences = self::query()->firstOrCreate(['user_id' => $user->id]);

        // A freshly inserted row doesn't load its column defaults back.
        return $preferences->wasRecentlyCreated ? $preferences->refresh() : $preferences;
    }

    /**
     * Older app builds only know the push_notifications switch; keep it and
     * notify_by telling the same story whichever one was just changed.
     */
    public function syncPushFlag(): void
    {
        if ($this->isDirty('notify_by')) {
            $this->push_notifications = $this->notify_by !== 'Off';
        } elseif ($this->isDirty('push_notifications')) {
            $this->notify_by = $this->push_notifications ? ($this->notify_by === 'Off' ? 'Push' : $this->notify_by) : 'Off';
        }
    }

    public function wantsEmail(): bool
    {
        return $this->notify_by === 'Email';
    }

    public function notificationsOff(): bool
    {
        return $this->notify_by === 'Off';
    }

    /**
     * The member's own clock, falling back to the app timezone until their
     * browser or phone has reported one.
     */
    public function localNow(): Carbon
    {
        $timezone = $this->timezone && in_array($this->timezone, timezone_identifiers_list(), true) ? $this->timezone : config('app.timezone');

        return now($timezone);
    }

    /**
     * The settings the web pages need to behave as chosen (theme, notation,
     * sound effects, haptics), shared with every Inertia page.
     *
     * @return array{appearance: string, notation: string, sound_effects: bool, haptics: bool, timezone: ?string}
     */
    public function toClient(): array
    {
        return [
            'timezone' => $this->timezone,
            'appearance' => $this->appearance ?? 'System',
            'notation' => $this->notation ?? 'Solfège',
            'sound_effects' => (bool) ($this->sound_effects ?? true),
            'haptics' => (bool) ($this->haptics ?? true),
        ];
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
            'language' => $user->language ?? $this->language ?? 'English',
            'last_name' => $user->last_name,
            'push_notifications' => $this->push_notifications,
            'reminder_time' => $this->reminder_time,
            'reminders' => $this->reminders,
            'skill_level' => $user->skill_level,
            'sound_effects' => $this->sound_effects,
            'streak_alerts' => $this->streak_alerts,
            'daily_goal' => $user->daily_goal,
            'default_instrument' => $this->default_instrument,
            'notation' => $this->notation,
            'notify_by' => $this->notify_by,
            'timezone' => $this->timezone,
        ];
    }
}
