<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Leaderboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaderboardController extends Controller
{
    public function __invoke(Request $request, Leaderboard $leaderboard): JsonResponse
    {
        $period = $request->validate([
            'period' => ['sometimes', Rule::in(Leaderboard::PERIODS)],
        ])['period'] ?? 'week';

        return response()->json($leaderboard->forUser($request->user(), $period));
    }
}
