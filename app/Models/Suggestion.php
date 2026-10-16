<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Suggestion extends Model
{
    public const STATUSES = ['pending', 'approved', 'declined', 'done'];

    protected $fillable = ['user_id', 'author', 'title', 'description', 'status', 'reason', 'votes'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SuggestionVote::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApi(bool $voted = false): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'reason' => $this->reason,
            'votes' => $this->votes,
            'voted' => $voted,
        ];
    }
}
