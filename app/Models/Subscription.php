<?php

namespace App\Models;

use App\Support\Plans;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'access',
        'plan_id',
        'status',
        'provider',
        'renews',
        'since',
        'next_plan',
        'trial_ends_at',
        'trial_cancelled',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'trial_cancelled' => 'boolean',
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

    public function unlocksQuizzes(): bool
    {
        if ($this->access === 'none') {
            return false;
        }

        $trial = $this->trial();

        if ($trial === null) {
            return true;
        }

        return ! ($trial['ended'] && $trial['cancelled']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function trial(): ?array
    {
        if ($this->access !== 'trial' || $this->trial_ends_at === null) {
            return null;
        }

        $daysLeft = max(0, (int) ceil(($this->trial_ends_at->getTimestamp() - now()->getTimestamp()) / 86400));

        if ($daysLeft === 0 && ! $this->trial_cancelled) {
            return null;
        }

        return [
            'cancelled' => $this->trial_cancelled,
            'days_left' => $daysLeft,
            'days_used' => Plans::TRIAL_DAYS - $daysLeft,
            'ended' => $daysLeft === 0,
            'ends_on' => $this->trial_ends_at->timezone(config('app.timezone'))->format('j M Y'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toApi(): array
    {
        $history = SubscriptionPayment::query()
            ->where('user_id', $this->user_id)
            ->latest('id')
            ->get()
            ->map(fn (SubscriptionPayment $payment) => [
                'amount' => $payment->amount,
                'date' => $payment->paid_on,
                'plan' => $payment->plan,
                'status' => $payment->status,
            ])
            ->all();

        return [
            'access' => $this->access,
            'plan_id' => $this->plan_id,
            'plan' => $this->plan_id ? (Plans::all()[$this->plan_id] ?? null) : null,
            'status' => $this->status,
            'provider' => $this->provider,
            'renews' => $this->renews,
            'since' => $this->since,
            'next_plan' => $this->next_plan,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'trial_cancelled' => $this->trial_cancelled,
            'trial' => $this->trial(),
            'unlocked' => $this->unlocksQuizzes(),
            'history' => $history,
        ];
    }

    public static function formatDate(Carbon $date): string
    {
        return $date->timezone(config('app.timezone'))->format('j M Y');
    }
}
