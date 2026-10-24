<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\QuizAnswer;
use App\Models\QuizBookmark;
use App\Models\QuizCompletion;
use App\Models\QuizSession;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ProgressReport;
use App\Support\MobileCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request, ProgressReport $progress): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $report = $progress->forUser($user);
        $level = in_array($user->skill_level, MobileCatalog::LEVELS, true) ? $user->skill_level : 'Beginner';
        $completed = QuizBookmark::query()->where('user_id', $user->id);
        $completedSlugs = QuizCompletion::query()->where('user_id', $user->id)->pluck('slug')->all();
        $path = MobileCatalog::learningPath($level, $completedSlugs);
        $subscription = Subscription::forUser($user);
        $sessions = QuizSession::query()->where('user_id', $user->id)->get();
        $answers = QuizAnswer::query()->where('user_id', $user->id)->orderBy('answered_at')->get(['correct']);
        $bestRun = 0;
        $run = 0;
        foreach ($answers as $answer) {
            $run = $answer->correct ? $run + 1 : 0;
            $bestRun = max($bestRun, $run);
        }
        $perfect = $sessions->contains(fn (QuizSession $session) => $session->total > 0 && $session->correct === $session->total);
        $longest = $report['bests']['longest_streak'];

        $achievements = [
            ['name' => 'First Steps', 'detail' => 'Complete your first quiz', 'icon' => 'footsteps', 'current' => $sessions->isNotEmpty() ? 1 : 0, 'target' => 1],
            ['name' => 'Sharp Ear', 'detail' => 'Answer 10 questions in a row correctly', 'icon' => 'ear', 'current' => $bestRun, 'target' => 10],
            ['name' => 'Perfect Harmony', 'detail' => 'Score 100% on any quiz', 'icon' => 'musical-notes', 'current' => $perfect ? 1 : 0, 'target' => 1],
            ['name' => 'Week Warrior', 'detail' => 'Keep a 7-day streak', 'icon' => 'flame', 'current' => $longest, 'target' => 7],
            ['name' => 'Consistent Player', 'detail' => 'Keep a 14-day streak', 'icon' => 'calendar', 'current' => $longest, 'target' => 14],
            ['name' => 'Monthly Master', 'detail' => 'Keep a 30-day streak', 'icon' => 'trophy', 'current' => $longest, 'target' => 30],
        ];

        $waiting = Challenge::query()
            ->where('user_id', $user->id)
            ->where('mine', false)
            ->whereDoesntHave('results')
            ->count();

        return response()->json([
            'greeting_name' => $user->first_name,
            'avatar' => $user->avatar,
            'skill_level' => $level,
            'streak' => $report['streak'],
            'accuracy' => $report['accuracy'],
            'quizzes_completed' => $report['completed_count'],
            'daily_goal' => $report['goal'],
            'up_next' => $subscription->unlocksQuizzes() ? $path['up_next'] : null,
            'learning_path' => [
                'done_share' => $path['done_share'],
                'remaining' => count($path['remaining']),
                'total' => $path['total'],
            ],
            'saved_count' => $completed->count(),
            'challenges_to_play' => $waiting,
            'unlocked' => $subscription->unlocksQuizzes(),
            'achievements' => $achievements,
        ]);
    }
}
