<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
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

        return [
            'stats' => [
                'members' => (clone $members)->count(),
                'quizzes' => Quiz::query()->count(),
                'publishedQuizzes' => Quiz::query()->where('is_published', true)->count(),
                'attempts' => QuizAttempt::query()->count(),
                'activeStreaks' => (clone $members)->where('current_streak', '>', 0)->count(),
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
            'quizzes' => Quiz::query()
                ->withCount(['attempts', 'questions'])
                ->latest()
                ->get()
                ->map(fn (Quiz $quiz) => $quiz->summary())
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberDashboard(User $user): array
    {
        $attempts = QuizAttempt::query()->where('user_id', $user->id);
        $completed = (clone $attempts)
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->with('quiz')
            ->get();

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
            'recentQuizzes' => (clone $attempts)
                ->with('quiz.questions')
                ->latest('last_played_at')
                ->limit(5)
                ->get()
                ->map(fn (QuizAttempt $attempt) => $this->attemptPayload($attempt))
                ->values(),
            'awards' => $user->awards()
                ->latest('earned_at')
                ->get()
                ->map(fn ($award) => [
                    'id' => $award->id,
                    'title' => $award->title,
                    'description' => $award->description,
                    'kind' => $award->kind,
                    'earned_at' => $award->earned_at->format('M j, Y'),
                ])
                ->values(),
        ];
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
