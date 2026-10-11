<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Support\QuizCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function categories(Request $request): Response
    {
        $user = $request->user();
        $quizzes = Quiz::query()->where('is_published', true)->get(['id', 'category', 'title']);
        $byCategory = $quizzes->groupBy('category');
        $completedQuizIds = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->pluck('quiz_id')
            ->unique();

        // Shows every category the app has, not just ones with a real quiz behind
        // them yet — quizCount is the real catalog size (app parity), done/percent
        // are computed only from real, playable quizzes.
        $categories = collect(Quiz::CATEGORIES)
            ->map(function ($name) use ($byCategory, $completedQuizIds) {
                $realQuizIds = $byCategory->get($name, collect())->pluck('id');
                $catalogSlugs = collect(QuizCatalog::subcategories($name))->map(fn ($n) => Str::slug($n));
                $realSlugs = $byCategory->get($name, collect())->pluck('title')->map(fn ($t) => Str::slug($t));
                $quizCount = $catalogSlugs->concat($realSlugs)->unique()->count();
                $done = $realQuizIds->intersect($completedQuizIds)->count();

                return [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'quizCount' => $quizCount,
                    'done' => $done,
                    'percent' => (int) round(($done / max(1, $quizCount)) * 100),
                ];
            })
            ->values();

        return Inertia::render('Member/Quiz', [
            'categories' => $categories,
        ]);
    }

    public function category(Request $request, string $category): Response
    {
        $name = $this->resolveCategory($category);
        $user = $request->user();

        $quizzes = Quiz::query()
            ->where('is_published', true)
            ->where('category', $name)
            ->orderBy('difficulty')
            ->orderBy('title')
            ->get();

        // One attempt per user per quiz (unique index on user_id + quiz_id), so
        // there's at most one row to key by quiz_id here.
        $attempts = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->keyBy('quiz_id');

        $real = $quizzes->map(function (Quiz $quiz) use ($attempts) {
            $attempt = $attempts->get($quiz->id);

            return [
                'available' => true,
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'difficulty' => $quiz->difficulty,
                'description' => $quiz->description,
                'question_count' => $quiz->question_count,
                'status' => $attempt?->status,
                'score' => $attempt && $attempt->status === QuizAttempt::STATUS_COMPLETED
                    ? (int) round(($attempt->correct_answers / max(1, $quiz->question_count)) * 100)
                    : null,
            ];
        })->keyBy(fn ($entry) => Str::slug($entry['title']));

        // Every subcategory the app has for this category, minus the ones already
        // covered above by a real quiz, rendered as "Coming soon" placeholders.
        $placeholders = collect(QuizCatalog::subcategories($name))
            ->reject(fn ($title) => $real->has(Str::slug($title)))
            ->map(fn ($title) => [
                'available' => false,
                'slug' => Str::slug($title),
                'title' => $title,
                'difficulty' => null,
                'description' => null,
                'question_count' => null,
                'status' => null,
                'score' => null,
            ]);

        return Inertia::render('Member/QuizCategory', [
            'category' => $name,
            'quizzes' => $real->values()->concat($placeholders->values()),
        ]);
    }

    public function preview(Request $request, Quiz $quiz): Response
    {
        $this->ensurePublished($quiz);
        $attempt = $this->latestAttempt($request, $quiz);

        return Inertia::render('Member/QuizPreview', [
            'quiz' => [
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'category' => $quiz->category,
                'difficulty' => $quiz->difficulty,
                'description' => $quiz->description,
                'question_count' => $quiz->question_count,
            ],
            'attempt' => $attempt ? [
                'status' => $attempt->status,
                'stopped_at_question' => $attempt->stopped_at_question,
                'correct_answers' => $attempt->correct_answers,
                'score' => (int) round(($attempt->correct_answers / max(1, $quiz->question_count)) * 100),
            ] : null,
        ]);
    }

    public function start(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);
        $user = $request->user();

        // The schema allows only one attempt per user per quiz (a unique index
        // on user_id + quiz_id), so there's no attempt history to preserve —
        // "retry" means resetting this same row back to question 1, not a new one.
        $attempt = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->first();

        if (! $attempt) {
            QuizAttempt::query()->create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'status' => QuizAttempt::STATUS_IN_PROGRESS,
                'stopped_at_question' => 1,
                'correct_answers' => 0,
                'last_played_at' => now(),
            ]);
        } elseif ($attempt->status === QuizAttempt::STATUS_COMPLETED) {
            $attempt->update([
                'status' => QuizAttempt::STATUS_IN_PROGRESS,
                'stopped_at_question' => 1,
                'correct_answers' => 0,
                'last_played_at' => now(),
                'completed_at' => null,
            ]);
        }

        return redirect()->route('quiz.play', $quiz);
    }

    public function play(Request $request, Quiz $quiz): Response|RedirectResponse
    {
        $this->ensurePublished($quiz);
        $attempt = $this->activeAttempt($request, $quiz);

        if (! $attempt) {
            return redirect()->route('quiz.preview', $quiz);
        }

        return Inertia::render('Member/QuizPlay', $this->playProps($quiz, $attempt));
    }

    public function answer(Request $request, Quiz $quiz): Response|RedirectResponse
    {
        $this->ensurePublished($quiz);
        $data = $request->validate(['choice' => ['required', 'string']]);

        $attempt = $this->activeAttempt($request, $quiz);
        if (! $attempt) {
            return redirect()->route('quiz.preview', $quiz);
        }

        $question = $quiz->questions()->where('position', $attempt->stopped_at_question)->first();
        if (! $question) {
            return redirect()->route('quiz.play', $quiz);
        }

        $correct = $data['choice'] === $question->answer;

        $attempt->correct_answers += $correct ? 1 : 0;
        $attempt->stopped_at_question += 1;
        $attempt->last_played_at = now();

        if ($attempt->stopped_at_question > $quiz->question_count) {
            $attempt->status = QuizAttempt::STATUS_COMPLETED;
            $attempt->completed_at = now();
        }

        $attempt->save();

        return Inertia::render('Member/QuizPlay', $this->playProps($quiz, $attempt, feedback: [
            'correct' => $correct,
            'chosen' => $data['choice'],
            'correctAnswer' => $question->answer,
            'prompt' => $question->prompt,
        ]));
    }

    public function results(Request $request, Quiz $quiz): Response
    {
        $attempt = QuizAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->latest('completed_at')
            ->firstOrFail();

        return Inertia::render('Member/QuizResults', [
            'quiz' => [
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'category' => $quiz->category,
                'categorySlug' => Str::slug($quiz->category),
                'question_count' => $quiz->question_count,
            ],
            'result' => [
                'correct' => $attempt->correct_answers,
                'total' => $quiz->question_count,
                'percent' => (int) round(($attempt->correct_answers / max(1, $quiz->question_count)) * 100),
            ],
        ]);
    }

    private function resolveCategory(string $slug): string
    {
        $match = collect(Quiz::CATEGORIES)->first(fn ($name) => Str::slug($name) === $slug);
        abort_if($match === null, 404);

        return $match;
    }

    private function ensurePublished(Quiz $quiz): void
    {
        abort_unless($quiz->is_published, 404);
    }

    private function latestAttempt(Request $request, Quiz $quiz): ?QuizAttempt
    {
        return QuizAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('quiz_id', $quiz->id)
            ->latest('last_played_at')
            ->first();
    }

    private function activeAttempt(Request $request, Quiz $quiz): ?QuizAttempt
    {
        return QuizAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
            ->latest('last_played_at')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function playProps(Quiz $quiz, QuizAttempt $attempt, ?array $feedback = null): array
    {
        $finished = $attempt->status === QuizAttempt::STATUS_COMPLETED;
        $question = $finished
            ? null
            : $quiz->questions()->where('position', $attempt->stopped_at_question)->first();

        return [
            'quiz' => [
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'category' => $quiz->category,
            ],
            'progress' => [
                'current' => min($attempt->stopped_at_question, $quiz->question_count),
                'total' => $quiz->question_count,
                'correct' => $attempt->correct_answers,
            ],
            'question' => $question ? [
                'prompt' => $question->prompt,
                'choices' => $question->choices,
                'audio_path' => $question->audio_path,
            ] : null,
            'finished' => $finished,
            'feedback' => $feedback,
        ];
    }
}
