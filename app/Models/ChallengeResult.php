<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeResult extends Model
{
    protected $fillable = ['challenge_id', 'name', 'avatar', 'score'];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
