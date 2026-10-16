<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizMark;
use App\Models\QuizQuestion;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    // Same role as the mobile app's hero image on its quiz-preview screen
    // (app/quiz-preview.tsx): one generic clip shown on every quiz, not a
    // unique video authored per quiz. Used as a fallback wherever a quiz
    // has no video_embed of its own.
    private const DEFAULT_VIDEO_EMBED = <<<'HTML'
        <script src="https://fast.wistia.com/player.js" async></script><script src="https://fast.wistia.com/embed/ce5tw47h05.js" async type="module"></script><style>wistia-player[media-id='ce5tw47h05']:not(:defined) { background: center / contain no-repeat url('https://fast.wistia.com/embed/medias/ce5tw47h05/swatch'); display: block; filter: blur(5px); padding-top:56.25%; }</style> <wistia-player media-id="ce5tw47h05" aspect="1.7777777777777777"></wistia-player>
        HTML;

    public function saved(Request $request): Response
    {
        $marks = QuizMark::query()
            ->where('user_id', $request->user()->id)
            ->where('bookmarked', true)
            ->with('quiz')
            ->latest('updated_at')
            ->get();

        return Inertia::render('Member/Saved', [
            'saved' => $marks->map(fn (QuizMark $mark) => [
                'slug' => $mark->quiz->slug,
                'title' => $mark->quiz->title,
                'category' => $mark->quiz->category,
                'difficulty' => $mark->quiz->difficulty,
                'savedAt' => $mark->updated_at->diffForHumans(),
            ])->values(),
        ]);
    }

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

    public function category(Request $request, string $category, ?string $quiz = null): Response
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

        $marks = QuizMark::query()
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->keyBy('quiz_id');

        $real = $quizzes->map(function (Quiz $quiz) use ($attempts, $marks) {
            $attempt = $attempts->get($quiz->id);
            $mark = $marks->get($quiz->id);

            return [
                'available' => true,
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'difficulty' => $quiz->difficulty,
                'description' => $quiz->description,
                'video_embed' => $quiz->video_embed ?? self::DEFAULT_VIDEO_EMBED,
                // Same QUIZ_LENGTH (25) the mobile app falls back to for any
                // quiz without question-specific info — real question_count
                // still drives actual quiz-taking below, this is display only.
                'question_count' => $quiz->question_count > 0 ? $quiz->question_count : 25,
                'sounds' => QuizCatalog::soundsFor($quiz->category, $quiz->title),
                'referenceAudioTracks' => collect($quiz->reference_audio_tracks ?? [])
                    ->map(fn ($track) => [
                        'id' => $track['id'],
                        'label' => $track['label'],
                        'name' => $track['name'] ?? null,
                        'url' => Storage::disk('public')->url($track['path']),
                    ])
                    ->values(),
                'status' => $attempt?->status,
                'score' => $attempt && $attempt->status === QuizAttempt::STATUS_COMPLETED
                    ? (int) round(($attempt->correct_answers / max(1, $quiz->question_count)) * 100)
                    : null,
                'stopped_at_question' => $attempt?->stopped_at_question,
                'bookmarked' => $mark?->bookmarked ?? false,
                'manuallyCompleted' => $mark?->completed ?? false,
            ];
        })->keyBy(fn ($entry) => Str::slug($entry['title']));

        // Every subcategory the app has for this category, minus the ones already
        // covered above by a real quiz, rendered with the same preview content as
        // the mobile app shows before any quiz-specific content is authored:
        // mobile's own QuizPreviewScreen falls back to a generic "Beginner" /
        // QUIZ_LENGTH (25) info card for every subcategory it has no override
        // for (see QUIZ_INFO/FALLBACK_INFO in app/quiz-preview.tsx) — so this
        // mirrors real app behavior, not invented numbers. What's genuinely
        // missing here (and why `available` stays false) is real question
        // content: the web's quiz flow needs pre-authored questions per quiz,
        // while the app generates ear-training audio on the fly.
        $placeholders = collect(QuizCatalog::subcategories($name))
            ->reject(fn ($title) => $real->has(Str::slug($title)))
            ->map(fn ($title) => [
                'available' => false,
                'slug' => Str::slug($title),
                'title' => $title,
                'difficulty' => 'Beginner',
                'description' => null,
                'video_embed' => self::DEFAULT_VIDEO_EMBED,
                'question_count' => 25,
                'sounds' => QuizCatalog::soundsFor($name, $title),
                'referenceAudioTracks' => [],
                'status' => null,
                'score' => null,
                'stopped_at_question' => null,
                'bookmarked' => false,
                'manuallyCompleted' => false,
            ]);

        $total = $real->count() + $placeholders->count();
        $done = $real->filter(fn ($entry) => $entry['status'] === QuizAttempt::STATUS_COMPLETED)->count();

        // $real is keyed by slug in whatever order the DB query returned (not
        // catalog order), and simply appending it before $placeholders would
        // put a real quiz that's late in the catalog (e.g. "Find the Key #1")
        // ahead of an earlier placeholder/real one (e.g. "Find the Note") —
        // walk the real catalog order instead so the list always matches it,
        // same as the app's own category screen.
        $byCatalogOrder = collect(QuizCatalog::subcategories($name))->map(fn ($title) => Str::slug($title));
        $entries = $real->merge($placeholders->keyBy(fn ($entry) => $entry['slug']));
        $allQuizzes = $byCatalogOrder->map(fn ($slug) => $entries->get($slug))->filter()->values();
        $selectedSlug = $allQuizzes->contains('slug', $quiz) ? $quiz : null;

        return Inertia::render('Member/QuizCategory', [
            'category' => $name,
            'categorySlug' => $category,
            'selectedSlug' => $selectedSlug,
            'quizzes' => $allQuizzes,
            'progress' => [
                'done' => $done,
                'total' => $total,
                'percent' => (int) round(($done / max(1, $total)) * 100),
            ],
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

    public function toggleBookmark(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);
        $mark = $this->markFor($request, $quiz);
        $mark->update(['bookmarked' => ! $mark->bookmarked]);

        return back();
    }

    public function toggleCompleted(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);
        $mark = $this->markFor($request, $quiz);
        $mark->update(['completed' => ! $mark->completed]);

        return back();
    }

    private function markFor(Request $request, Quiz $quiz): QuizMark
    {
        return QuizMark::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'quiz_id' => $quiz->id,
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
            $attempt->answers()->delete();
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

        return Inertia::render('Member/QuizPlay', $this->playProps($request, $quiz, $attempt));
    }

    // Grades the current question in place — the attempt's position only moves
    // forward from next(), so the member keeps seeing this same question (with
    // their choice highlighted and locked) until they choose to continue,
    // matching the mobile app's Submit → Next Question flow instead of jumping
    // straight to the next question's blank state.
    public function answer(Request $request, Quiz $quiz): Response|RedirectResponse
    {
        $this->ensurePublished($quiz);
        $data = $request->validate(['choice' => ['required', 'string']]);

        $attempt = $this->activeAttempt($request, $quiz);
        if (! $attempt) {
            return redirect()->route('quiz.preview', $quiz);
        }

        $position = $attempt->stopped_at_question;
        $sessionKey = $this->feedbackSessionKey($attempt, $position);

        // Already graded (a resubmit, or the page was simply reloaded) — don't grade or count it twice.
        if (! $request->session()->has($sessionKey)) {
            $question = $quiz->questions()->where('position', $position)->first();
            if (! $question) {
                return redirect()->route('quiz.play', $quiz);
            }

            $correct = $data['choice'] === $question->answer;
            $attempt->correct_answers += $correct ? 1 : 0;
            $attempt->last_played_at = now();
            $attempt->save();

            // Logged per question (not just the running tally) so the results page can show
            // a real per-question review, same as the mobile app's local answer history.
            $attempt->answers()->updateOrCreate(
                ['position' => $position],
                ['quiz_question_id' => $question->id, 'picked' => $data['choice'], 'correct' => $correct],
            );

            $request->session()->put($sessionKey, [
                'correct' => $correct,
                'chosen' => $data['choice'],
                'correctAnswer' => $question->answer,
            ]);
        }

        return Inertia::render('Member/QuizPlay', $this->playProps($request, $quiz, $attempt));
    }

    // Moves on from a question that's already been graded — refuses to advance
    // an unanswered one, so the position can't skip ahead without a real answer.
    public function next(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);
        $attempt = $this->activeAttempt($request, $quiz);
        if (! $attempt) {
            return redirect()->route('quiz.preview', $quiz);
        }

        $position = $attempt->stopped_at_question;
        $sessionKey = $this->feedbackSessionKey($attempt, $position);
        if (! $request->session()->has($sessionKey)) {
            return redirect()->route('quiz.play', $quiz);
        }

        $request->session()->forget($sessionKey);
        $attempt->stopped_at_question += 1;

        if ($attempt->stopped_at_question > $quiz->question_count) {
            $attempt->status = QuizAttempt::STATUS_COMPLETED;
            $attempt->completed_at = now();
            $attempt->save();

            return redirect()->route('quiz.results', $quiz);
        }

        $attempt->save();

        return redirect()->route('quiz.play', $quiz);
    }

    // The mobile app keeps no mid-quiz position at all (practice.tsx's state is purely
    // in-memory), so cancelling always starts over next time, not resumes — mirrored
    // here by resetting the attempt back to question 1 instead of just leaving the page.
    public function cancel(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);
        $attempt = $this->activeAttempt($request, $quiz);

        if ($attempt) {
            $request->session()->forget($this->feedbackSessionKey($attempt, $attempt->stopped_at_question));
            $attempt->answers()->delete();
            $attempt->update(['stopped_at_question' => 1, 'correct_answers' => 0]);
        }

        return redirect()->route('quiz.category', ['category' => Str::slug($quiz->category), 'quiz' => $quiz->slug]);
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
            'review' => $attempt->answers()
                ->with('question')
                ->orderBy('position')
                ->get()
                ->map(fn (QuizAttemptAnswer $answer) => [
                    'position' => $answer->position,
                    'picked' => $answer->picked,
                    'correct' => $answer->correct,
                    'answer' => $answer->question->answer,
                ])
                ->values(),
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
    /**
     * @return list<string>
     */
    private function choicesFor(Quiz $quiz, QuizQuestion $question): array
    {
        $preset = QuizAnswerPresets::presetFor($quiz->category, $quiz->title);

        if ($preset && in_array($question->answer, $preset, true)) {
            return $preset;
        }

        return $question->choices;
    }

    private function feedbackSessionKey(QuizAttempt $attempt, int $position): string
    {
        return "quiz-feedback:{$attempt->id}:{$position}";
    }

    private function playProps(Request $request, Quiz $quiz, QuizAttempt $attempt): array
    {
        $finished = $attempt->status === QuizAttempt::STATUS_COMPLETED;
        $position = $attempt->stopped_at_question;
        $question = $finished
            ? null
            : $quiz->questions()->where('position', $position)->first();

        $feedback = $finished ? null : $request->session()->get($this->feedbackSessionKey($attempt, $position));

        return [
            'quiz' => [
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'category' => $quiz->category,
                'categorySlug' => Str::slug($quiz->category),
            ],
            'progress' => [
                'current' => min($position, $quiz->question_count),
                'total' => $quiz->question_count,
                'correct' => $attempt->correct_answers,
            ],
            'question' => $question ? [
                'prompt' => $question->prompt,
                // The real app shows a fixed set of options per quiz (not a
                // grab-bag per question) — switch to that canonical list only
                // when it actually contains this question's real answer, so
                // an older question seeded with different choices never ends
                // up with its correct answer missing from the options shown.
                'choices' => $this->choicesFor($quiz, $question),
                'audio_path' => $question->audio_path,
            ] : null,
            'finished' => $finished,
            'feedback' => $feedback,
        ];
    }
}
