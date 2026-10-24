<?php

namespace App\Models;

use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'quiz_id',
    'status',
    'stopped_at_question',
    'question_sequence',
    'reference_note_sequence',
    'sound',
    'correct_answers',
    'last_played_at',
    'completed_at',
])]
class QuizAttempt extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stopped_at_question' => 'integer',
            'question_sequence' => 'array',
            'reference_note_sequence' => 'array',
            'correct_answers' => 'integer',
            'last_played_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    // The quiz_question ID for the given 1-based position in this attempt's own
    // shuffled sequence — not the quiz's `position` column, which no longer
    // determines play order (see question_sequence's migration comment).
    public function questionIdAt(int $position): ?int
    {
        return $this->question_sequence[$position - 1] ?? null;
    }

    // Find the Key #2's shuffled reference note for the given 1-based position —
    // null for every other quiz, which has no reference-note concept at all.
    public function referenceNoteAt(int $position): ?string
    {
        return $this->reference_note_sequence[$position - 1] ?? null;
    }
}
