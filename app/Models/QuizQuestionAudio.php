<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One per-instrument audio variant for a question — see the
// create_quiz_question_audio_table migration for why this exists separately
// from QuizQuestion::audio_path.
#[Fillable(['quiz_question_id', 'sound', 'audio_path'])]
class QuizQuestionAudio extends Model
{
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
