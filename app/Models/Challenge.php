<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    protected $fillable = ['user_id', 'mine', 'slug', 'total', 'from_name', 'from_avatar', 'from_score', 'sent_at'];

    protected function casts(): array
    {
        return [
            'mine' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ChallengeResult::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'mine' => $this->mine,
            'slug' => $this->slug,
            'total' => $this->total,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'from' => [
                'name' => $this->from_name,
                'avatar' => $this->from_avatar,
                'score' => $this->from_score,
            ],
            'results' => $this->results->map(fn (ChallengeResult $result) => [
                'name' => $result->name,
                'avatar' => $result->avatar,
                'score' => $result->score,
            ])->all(),
        ];
    }
}
