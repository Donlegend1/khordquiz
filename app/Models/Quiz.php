<?php

namespace App\Models;

use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'created_by',
    'title',
    'slug',
    'category',
    'difficulty',
    'description',
    'question_count',
    'is_published',
])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    public const CATEGORIES = [
        'Relative Pitch',
        'Intervals',
        'Basic Triads',
        'Melodic Dictation',
        'Add 9 & b9',
        '7th Degree Chords',
        'Secondary 7th Chords',
        'Chord Progressions',
        'Scales',
        '9th Degree Chords',
        'Secondary 9th Chords',
        '11th Degree Chords',
        'Secondary 11th Chords',
        '13th Degree Chords',
        'Extensions',
        'Modal Voicings',
    ];

    public const DIFFICULTIES = [
        'Beginner',
        'Intermediate',
        'Advanced',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'question_count' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    /**
     * Replace this quiz's questions. Each item needs prompt, choices, and either answer or correct.
     *
     * @param  array<int, array<string, mixed>>  $questions
     */
    public function syncQuestions(array $questions): void
    {
        $this->questions()->reorder()->delete();

        foreach (array_values($questions) as $index => $question) {
            $choices = array_values($question['choices']);
            $answer = $question['answer'] ?? $choices[(int) $question['correct']];

            $this->questions()->create([
                'position' => $index + 1,
                'prompt' => $question['prompt'],
                'choices' => $choices,
                'answer' => $answer,
                'audio_path' => $question['audio_path'] ?? null,
                'video_path' => $question['video_path'] ?? null,
            ]);
        }

        $this->update([
            'question_count' => count($questions),
        ]);
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $base = $base !== '' ? $base : 'quiz';
        $slug = $base;
        $suffix = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'difficulty' => $this->difficulty,
            'description' => $this->description,
            'question_count' => isset($this->questions_count) ? (int) $this->questions_count : $this->question_count,
            'is_published' => $this->is_published,
            'attempts_count' => (int) ($this->attempts_count ?? 0),
        ];
    }
}
