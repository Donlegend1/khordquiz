<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quiz_attempt_id', 'quiz_question_id', 'position', 'picked', 'correct'])]
class QuizAttemptAnswer extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'correct' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (QuizAttemptAnswer $answer): void {
            $attempt = $answer->attempt;
            QuizAnswer::firstOrCreate(['web_answer_id' => $answer->id], [
                'user_id' => $attempt->user_id, 'slug' => $attempt->quiz->slug,
                'answer' => $answer->question->answer, 'picked' => $answer->picked,
                'correct' => $answer->correct, 'kind' => 'single', 'answered_at' => $answer->created_at,
            ]);
        });
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
