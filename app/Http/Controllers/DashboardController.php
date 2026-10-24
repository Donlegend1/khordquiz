<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizMark;
use App\Models\User;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return Inertia::render('Admin/Dashboard', $this->adminDashboard());
        }

        return Inertia::render('Member/Dashboard', $this->memberDashboard($user));
    }

    /**
     * @return array<string, mixed>
     */
    private function adminDashboard(): array
    {
        $members = User::query()->where('role', Role::Member);
        $completedAttempts = QuizAttempt::query()->where('status', QuizAttempt::STATUS_COMPLETED)->with('quiz')->get();

        $avgAccuracy = null;
        if ($completedAttempts->isNotEmpty()) {
            $avgAccuracy = (int) round(
                $completedAttempts->sum(fn (QuizAttempt $attempt) => $attempt->correct_answers / max(1, $attempt->quiz->question_count)) / $completedAttempts->count() * 100
            );
        }

        return [
            'stats' => [
                'members' => (clone $members)->count(),
                'quizzes' => Quiz::query()->count(),
                'publishedQuizzes' => Quiz::query()->where('is_published', true)->count(),
                'attempts' => QuizAttempt::query()->count(),
                'completedAttempts' => $completedAttempts->count(),
                'avgAccuracy' => $avgAccuracy,
                'activeStreaks' => (clone $members)->where('current_streak', '>', 0)->count(),
                'newSignups' => (clone $members)->where('created_at', '>=', now()->subDays(7))->count(),
                'suspended' => User::query()->whereNotNull('suspended_at')->count(),
            ],
            'recentStreaks' => User::query()
                ->where('role', Role::Member)
                ->orderByDesc('last_practiced_on')
                ->orderByDesc('current_streak')
                ->limit(6)
                ->get()
                ->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'current_streak' => $member->current_streak,
                    'longest_streak' => $member->longest_streak,
                    'last_practiced_on' => $this->practicedLabel($member->last_practiced_on),
                ])
                ->values(),
            'recentSignups' => User::query()
                ->where('role', Role::Member)
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'joined_ago' => $member->created_at->diffForHumans(),
                ])
                ->values(),
            // reference_audio_tracks is excluded here — Quiz::summary() never reads
            // it, and some quizzes' JSON blob is large enough (thousands of
            // reference tracks) that including it in this ORDER BY query overflows
            // MySQL's filesort buffer ("Out of sort memory", error 1038).
            'quizzes' => Quiz::query()
                ->select(['id', 'title', 'category', 'difficulty', 'description', 'question_count', 'is_published', 'created_at'])
                ->withCount(['attempts', 'questions'])
                ->latest()
                ->get()
                ->map(fn (Quiz $quiz) => $quiz->summary())
                ->values(),
            // Real member counts by country, highest first. Members with no country set
            // (country is optional on Edit Profile) are left out rather than lumped into
            // an "Unknown" bucket that would just be noise on an admin card.
            'topCountries' => (clone $members)
                ->whereNotNull('country')
                ->where('country', '!=', '')
                ->selectRaw('country, count(*) as total')
                ->groupBy('country')
                ->orderByDesc('total')
                ->limit(6)
                ->get()
                ->map(fn ($row) => ['country' => $row->country, 'count' => $row->total])
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberDashboard(User $user): array
    {
        $attempts = QuizAttempt::query()->where('user_id', $user->id);
        $allAttempts = (clone $attempts)->with('quiz')->get();
        $completed = $allAttempts->where('status', QuizAttempt::STATUS_COMPLETED)->values();

        $accuracy = null;

        if ($completed->isNotEmpty()) {
            $accuracy = (int) round(
                $completed->sum(function (QuizAttempt $attempt) {
                    $total = max(1, $attempt->quiz->question_count);

                    return ($attempt->correct_answers / $total) * 100;
                }) / $completed->count()
            );
        }

        $resume = (clone $attempts)
            ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
            ->with('quiz.questions')
            ->latest('last_played_at')
            ->first();

        // The most recently completed attempt whose quiz still has real
        // questions — skips over any quiz that's since lost its content (a
        // catalog edit emptied it, etc.) so this never links to a dead end.
        $continuePracticing = $resume ? null : $completed
            ->filter(fn (QuizAttempt $a) => $a->quiz->question_count > 0)
            ->sortByDesc('completed_at')
            ->first();

        return [
            'streak' => [
                'current' => $user->current_streak,
                'longest' => $user->longest_streak,
                'last_practiced_on' => $this->practicedLabel($user->last_practiced_on),
            ],
            'stats' => [
                'completed' => $completed->count(),
                'inProgress' => (clone $attempts)->where('status', QuizAttempt::STATUS_IN_PROGRESS)->count(),
                'accuracy' => $accuracy,
            ],
            'resume' => $resume ? $this->attemptPayload($resume) : null,
            'continuePracticing' => $continuePracticing ? $this->attemptPayload($continuePracticing) : null,
            // In-progress attempts already have their own "Continue where you
            // left off" card above, so this is completed quizzes only.
            'recentQuizzes' => (clone $attempts)
                ->where('status', QuizAttempt::STATUS_COMPLETED)
                ->with('quiz.questions')
                ->latest('completed_at')
                ->limit(5)
                ->get()
                ->map(fn (QuizAttempt $attempt) => $this->attemptPayload($attempt))
                ->values(),
            'achievements' => $this->achievements($user, $allAttempts, $completed),
            'savedCount' => QuizMark::query()->where('user_id', $user->id)->where('bookmarked', true)->count(),
        ];
    }

    /**
     * Same fixed catalog and unlock rules as the mobile app's Home screen
     * (app/(tabs)/home.tsx). "Sharp Ear" is the one approximation: the app
     * tracks every individual answer in order to find the longest correct
     * streak, but the web only stores each attempt's final tally — so this
     * counts a fully-correct attempt's question count as a confirmed run of
     * that many correct answers in a row, rather than inventing a log that
     * doesn't exist.
     *
     * @param  \Illuminate\Support\Collection<int, QuizAttempt>  $allAttempts
     * @param  \Illuminate\Support\Collection<int, QuizAttempt>  $completed
     * @return array<int, array<string, mixed>>
     */
    private function achievements(User $user, $allAttempts, $completed): array
    {
        $bestCorrectRun = $completed
            ->filter(fn (QuizAttempt $a) => $a->correct_answers === $a->quiz->question_count)
            ->max(fn (QuizAttempt $a) => $a->quiz->question_count) ?? 0;

        $hasPerfectScore = $completed->contains(fn (QuizAttempt $a) => $a->correct_answers === $a->quiz->question_count);

        $longestStreak = $user->longest_streak;

        return collect([
            ['name' => 'First Steps', 'detail' => 'Complete your first quiz', 'icon' => 'footsteps', 'current' => $allAttempts->isNotEmpty() ? 1 : 0, 'target' => 1],
            ['name' => 'Sharp Ear', 'detail' => 'Answer 10 questions in a row correctly', 'icon' => 'ear', 'current' => $bestCorrectRun, 'target' => 10],
            ['name' => 'Perfect Harmony', 'detail' => 'Score 100% on any quiz', 'icon' => 'musical-notes', 'current' => $hasPerfectScore ? 1 : 0, 'target' => 1],
            ['name' => 'Week Warrior', 'detail' => 'Keep a 7-day streak', 'icon' => 'flame', 'current' => $longestStreak, 'target' => 7],
            ['name' => 'Consistent Player', 'detail' => 'Keep a 14-day streak', 'icon' => 'calendar', 'current' => $longestStreak, 'target' => 14],
            ['name' => 'Monthly Master', 'detail' => 'Keep a 30-day streak', 'icon' => 'trophy', 'current' => $longestStreak, 'target' => 30],
        ])
            ->map(fn ($a) => [...$a, 'current' => min($a['current'], $a['target'])])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptPayload(QuizAttempt $attempt): array
    {
        $quiz = $attempt->quiz;
        $total = max(1, $quiz->question_count);
        $question = $quiz->questions->firstWhere('position', $attempt->stopped_at_question);

        return [
            'id' => $attempt->id,
            'status' => $attempt->status,
            'stopped_at_question' => $attempt->stopped_at_question,
            'correct_answers' => $attempt->correct_answers,
            'score' => (int) round(($attempt->correct_answers / $total) * 100),
            'progress' => (int) min(100, round((($attempt->stopped_at_question - 1) / $total) * 100)),
            'last_played_at' => $attempt->last_played_at->diffForHumans(),
            'quiz' => [
                'id' => $quiz->id,
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'category' => $quiz->category,
                'difficulty' => $quiz->difficulty,
                'question_count' => $quiz->question_count,
            ],
            'question' => $question ? [
                'prompt' => $question->prompt,
                'choices' => $question->choices,
                'audio_url' => $question->audio_path ? Storage::disk('public')->url($question->audio_path) : null,
                'video_url' => $question->video_path ? Storage::disk('public')->url($question->video_path) : null,
            ] : null,
        ];
    }

    private function practicedLabel(?Carbon $date): string
    {
        if ($date === null) {
            return 'No practice yet';
        }

        if ($date->isToday()) {
            return 'Today';
        }

        if ($date->isYesterday()) {
            return 'Yesterday';
        }

        return $date->diffForHumans();
    }
}
