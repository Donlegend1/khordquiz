<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $attempts = QuizAttempt::query()->where('user_id', $user->id)->with('quiz')->get();

        $totalAnswered = 0;
        $totalCorrect = 0;
        foreach ($attempts as $attempt) {
            $totalAnswered += $this->answeredCount($attempt);
            $totalCorrect += $attempt->correct_answers;
        }

        $completedAttempts = $attempts->where('status', QuizAttempt::STATUS_COMPLETED);
        $completedCount = $completedAttempts->count();
        $totalQuizzes = Quiz::query()->where('is_published', true)->count();

        $quizzesByCategory = Quiz::query()->where('is_published', true)->get(['id', 'category'])->groupBy('category');
        $completedQuizIds = $completedAttempts->pluck('quiz_id')->unique();
        $categoriesDone = $quizzesByCategory
            ->filter(fn ($quizzes) => $quizzes->pluck('id')->diff($completedQuizIds)->isEmpty())
            ->count();

        // Best-effort only: an attempt only records its latest session, not a log of
        // every day it was played, so a quiz finished today after starting it
        // yesterday counts its earlier answers as "today" too — same for the week chart.
        $today = now()->startOfDay();
        $answeredToday = 0;
        foreach ($attempts as $attempt) {
            if ($attempt->last_played_at?->greaterThanOrEqualTo($today)) {
                $answeredToday += $this->answeredCount($attempt);
            }
        }

        $resume = $attempts
            ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
            ->sortByDesc('last_played_at')
            ->first();

        // Below the threshold = Needs work, above it = Strongest. A quiz can only
        // land in one list, since the two ranges never overlap.
        $ranked = $completedAttempts
            ->map(fn (QuizAttempt $attempt) => [
                'attempt' => $attempt,
                'accuracy' => (int) round(($attempt->correct_answers / max(1, $attempt->quiz->question_count)) * 100),
            ]);
        $needsWork = $ranked->filter(fn ($entry) => $entry['accuracy'] < self::RANKING_THRESHOLD)->sortBy('accuracy')->values();
        $strongest = $ranked->filter(fn ($entry) => $entry['accuracy'] > self::RANKING_THRESHOLD)->sortByDesc('accuracy')->values();

        // Last 7 days, questions answered per day. There's no per-question mistake
        // tracking on the web yet (only an attempt's final tally), so "Most confused"
        // has nothing to draw from — it's left empty, same as the dedicated Mistakes page.
        $week = collect(range(6, 0))->map(function (int $daysAgo) use ($attempts) {
            $day = now()->subDays($daysAgo)->startOfDay();
            $count = $attempts
                ->filter(fn (QuizAttempt $attempt) => $attempt->last_played_at?->isSameDay($day))
                ->sum(fn (QuizAttempt $attempt) => $this->answeredCount($attempt));

            return ['label' => $day->format('D'), 'count' => $count];
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
        return $attempt->status === QuizAttempt::STATUS_COMPLETED
            ? $attempt->quiz->question_count
            : $attempt->stopped_at_question;
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
                'question_count' => $quiz->question_count,
            ],
        ];
    }
}
