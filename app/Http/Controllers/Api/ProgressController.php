<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizAnswer;
use App\Models\QuizCompletion;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\ProgressReport;
use App\Support\MobileCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function show(Request $request, ProgressReport $progress): JsonResponse
    {
        return response()->json($progress->forUser($request->user()));
    }

    public function forYou(Request $request, ProgressReport $progress): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $level = in_array($user->skill_level, MobileCatalog::LEVELS, true) ? $user->skill_level : 'Beginner';
        $completed = QuizCompletion::query()->where('user_id', $user->id)->pluck('slug')->all();
        $path = MobileCatalog::learningPath($level, $completed);
        $index = array_search($level, MobileCatalog::LEVELS, true);
        $nextLevel = is_int($index) ? (MobileCatalog::LEVELS[$index + 1] ?? null) : null;
        $report = $progress->forUser($user);

        return response()->json([
            'skill_level' => $level,
            'done_share' => $path['done_share'],
            'up_next' => $path['up_next'],
            'upcoming' => array_slice($path['remaining'], 1, 4),
            'path_total' => $path['total'],
            'weak_spots' => $report['weak_spots'],
            'next_level' => $nextLevel,
            'ready_for_next' => $path['done_share'] >= 0.7,
        ]);
    }

    public function mistakes(Request $request, ProgressReport $progress): JsonResponse
    {
        $answers = QuizAnswer::query()->where('user_id', $request->user()->id)->orderBy('answered_at')->get();

        return response()->json([
            'mistakes' => $progress->mistakes($answers),
        ]);
    }

    public function updateGoal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'daily_goal' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $request->user()->update($data);

        return response()->json([
            'message' => 'Daily goal updated.',
            'daily_goal' => $request->user()->daily_goal,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        QuizAnswer::query()->where('user_id', $userId)->delete();
        QuizSession::query()->where('user_id', $userId)->delete();
        QuizCompletion::query()->where('user_id', $userId)->delete();
        $request->user()->update([
            'current_streak' => 0,
            'last_practiced_on' => null,
        ]);

        return response()->json(['message' => 'Progress reset. Saved quizzes were kept.']);
    }
}
