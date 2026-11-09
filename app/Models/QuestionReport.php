<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionReport extends Model
{
    // The single source of truth for this form's options — validated against
    // directly by StoreQuestionReportRequest, and supplied to the member-facing
    // report form via HandleInertiaRequests so the two never drift apart.
    public const REASONS = ['Audio does not play', 'Audio and answer do not match', 'Instructions are unclear', 'Something else'];

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
