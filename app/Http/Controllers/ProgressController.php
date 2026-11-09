<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Support\QuizCatalog;
use App\Support\QuizHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProgressController extends Controller
{
    // Choices offered on the "Today's goal" card.
    // Same options as the mobile app's Settings → Daily goal picker.
    public const GOAL_OPTIONS = [20, 50, 100, 150, 200, 250];

    // A completed quiz below this accuracy is "Needs work"; above it, "Strongest".
    // Exactly on the line counts as neither.
    public const RANKING_THRESHOLD = 65;

    public function history(Request $request): Response
    {
        $history = QuizHistory::paginateCompleted($request->user(), max(1, $request->integer('page', 1)))
            ->withPath($request->url());

        return Inertia::render('Member/AttemptHistory', ['history' => $history]);
    }

    public function forYou(Request $request): Response
    {
        // One merged, deduplicated view of completed attempts + archived
        // sessions (QuizHistory::completed()), reused for every completion
        // check below instead of re-querying quiz_attempts and quiz_sessions
        // separately.
        $completed = QuizHistory::completed($request->user());
        $completedIds = $completed->pluck('quiz_id')->unique();
        $completedSlugs = $completed->pluck('quiz.slug')->unique();
        $quizzes = Quiz::query()->where('is_published', true)->where('question_count', '>', 0)
            ->get(['id', 'title', 'slug', 'category', 'difficulty'])
            ->sortBy(function (Quiz $quiz): array {
                $category = array_search($quiz->category, Quiz::CATEGORIES, true);
                $lesson = array_search($quiz->title, QuizCatalog::subcategories($quiz->category), true);

                return [$category === false ? PHP_INT_MAX : $category, $lesson === false ? PHP_INT_MAX : $lesson, $quiz->title, $quiz->id];
            });
        $completedResults = $completed->filter(fn (QuizAttempt $attempt) => $quizzes->contains('id', $attempt->quiz_id));
        $paths = [];

        foreach (Quiz::DIFFICULTIES as $level) {
            $lessons = $quizzes->where('difficulty', $level)->values();
            $remaining = $lessons->map(fn (Quiz $quiz, int $index) => [
                'id' => $quiz->id,
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'category' => $quiz->category,
                'categorySlug' => Str::slug($quiz->category),
                'position' => $index + 1,
            ])->reject(fn (array $quiz) => $completedIds->contains($quiz['id']) || $completedSlugs->contains($quiz['slug']))->values();

            $latestResult = $completedResults->first(fn (QuizAttempt $attempt) => $attempt->quiz->difficulty === $level);
            $paths[$level] = [
                'upNext' => $remaining->first(),
                'done' => $lessons->count() - $remaining->count(),
                'total' => $lessons->count(),
                'comingUp' => $remaining->slice(1, 9)->values(),
                'practiceAgain' => $latestResult && $latestResult->correct_answers * 100 < 40 * $latestResult->quiz->question_count
                    ? $this->practiceAgainPayload($latestResult) : null,
            ];
        }

        return Inertia::render('Member/ForYou', ['paths' => $paths]);
    }

    private function practiceAgainPayload(QuizAttempt $attempt): array
    {
        $quiz = $attempt->quiz;

        return [
            'title' => $quiz->title, 'slug' => $quiz->slug, 'category' => $quiz->category,
            'categorySlug' => Str::slug($quiz->category),
            'score' => (int) round($attempt->correct_answers / max(1, $quiz->question_count) * 100),
            'mistakesCount' => $quiz->question_count - $attempt->correct_answers,
            'reviewUrl' => $attempt->history_session_id
                ? route('quiz.history-review', $attempt->history_session_id)
                : route('quiz.results', ['quiz' => $quiz->slug, 'review' => 1]),
        ];
    }

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $attempts = QuizAttempt::query()->where('user_id', $user->id)->with('quiz')->withCount('answers')->get();

        $completedAttempts = QuizHistory::completed($user, $attempts);
        $allRuns = $completedAttempts->concat($attempts->where('status', QuizAttempt::STATUS_IN_PROGRESS));
        $totalAnswered = $allRuns->sum(fn (QuizAttempt $attempt) => QuizHistory::answeredCount($attempt));
        $totalCorrect = $allRuns->sum('correct_answers');
        $completedCount = $completedAttempts->pluck('quiz_id')->unique()->count();
        $totalQuizzes = Quiz::query()->where('is_published', true)->count();

        $quizzesByCategory = Quiz::query()->where('is_published', true)->get(['id', 'category'])->groupBy('category');
        $completedQuizIds = $completedAttempts->pluck('quiz_id')->unique();
        $categoriesDone = $quizzesByCategory
            ->filter(fn ($quizzes) => $quizzes->pluck('id')->diff($completedQuizIds)->isEmpty())
            ->count();

        $answeredToday = QuizHistory::answeredOn($user, now());

        $resume = $attempts
            ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
            ->sortByDesc('last_played_at')
            ->first();

        // Below the threshold = Needs work, above it = Strongest. A quiz can only
        // land in one list, since the two ranges never overlap.
        $ranked = $completedAttempts->unique('quiz_id')
            ->map(fn (QuizAttempt $attempt) => [
                'attempt' => $attempt,
                'accuracy' => (int) round(($attempt->correct_answers / max(1, $attempt->quiz->question_count)) * 100),
            ]);
        $needsWork = $ranked->filter(fn ($entry) => $entry['accuracy'] < self::RANKING_THRESHOLD)->sortBy('accuracy')->values();
        $strongest = $ranked->filter(fn ($entry) => $entry['accuracy'] > self::RANKING_THRESHOLD)->sortByDesc('accuracy')->values();

        $week = collect(range(6, 0))->map(function (int $daysAgo) use ($user) {
            $day = now()->subDays($daysAgo);

            return ['label' => $day->translatedFormat('D'), 'count' => QuizHistory::answeredOn($user, $day)];
        });

        return Inertia::render('Member/Progress', [
            'overall' => [
                'accuracy' => $totalAnswered > 0 ? (int) round(($totalCorrect / $totalAnswered) * 100) : null,
                'correct' => $totalCorrect,
                'answered' => $totalAnswered,
            ],
            'quizzesCompleted' => ['done' => $completedCount, 'total' => $totalQuizzes],
            'categoriesDone' => ['done' => $categoriesDone, 'total' => $quizzesByCategory->count()],
            'streak' => $user->current_streak,
            'goal' => ['target' => $user->daily_goal, 'done' => min($answeredToday, $user->daily_goal)],
            'goalOptions' => self::GOAL_OPTIONS,
            'resume' => $resume ? $this->attemptPayload($resume) : null,
            'needsWork' => $needsWork->map(fn ($entry) => $this->rankedQuizPayload($entry)),
            'strongest' => $strongest->map(fn ($entry) => $this->rankedQuizPayload($entry)),
            'week' => $week,
            // The in-progress attempt (if any) already has its own "Continue
            // where you left off" card above, so this is completed only.
            'recentQuizzes' => $completedAttempts
                ->sortByDesc('completed_at')
                ->take(5)
                ->map(fn (QuizAttempt $attempt) => $this->attemptPayload($attempt))
                ->values(),
        ]);
    }

    public function updateGoal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'daily_goal' => ['required', 'integer', 'min:5', 'max:250'],
        ]);

        $request->user()->update(['daily_goal' => $data['daily_goal']]);

        return back();
    }

    private function answeredCount(QuizAttempt $attempt): int
    {
        return QuizHistory::answeredCount($attempt);
    }

    /**
     * @return array<string, mixed>
     */
    private function rankedQuizPayload(array $entry): array
    {
        return [
            'quiz' => [
                'title' => $entry['attempt']->quiz->title,
                'category' => $entry['attempt']->quiz->category,
            ],
            'accuracy' => $entry['accuracy'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptPayload(QuizAttempt $attempt): array
    {
        $quiz = $attempt->quiz;
        $total = max(1, $quiz->question_count);

        return [
            'status' => $attempt->status,
            'stopped_at_question' => $attempt->stopped_at_question,
            'correct_answers' => $attempt->correct_answers,
            'score' => (int) round(($attempt->correct_answers / $total) * 100),
            'progress' => (int) min(100, round((($attempt->stopped_at_question - 1) / $total) * 100)),
            'last_played_at' => $attempt->last_played_at?->diffForHumans(),
            'quiz' => [
                'title' => $quiz->title,
                'category' => $quiz->category,
                'categorySlug' => Str::slug($quiz->category),
                'slug' => $quiz->slug,
                'question_count' => $quiz->question_count,
            ],
        ];
    }
}
