<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $period = $request->validate([
            'period' => ['sometimes', 'in:week,all'],
        ])['period'] ?? 'week';

        /** @var User $user */
        $user = $request->user();
        $query = QuizSession::query()
            ->selectRaw('user_id, sum(correct) as points, sum(total) as attempts')
            ->groupBy('user_id');

        if ($period === 'week') {
            $query->where('played_at', '>=', now()->subDays(6)->startOfDay());
        }

        $scores = $query->get()->keyBy('user_id');
        $users = User::query()->whereIn('id', $scores->keys()->push($user->id)->unique())->get()->keyBy('id');

        $rows = $scores->map(function ($score) use ($users) {
            $member = $users->get($score->user_id);
            if (! $member) {
                return null;
            }

            return [
                'id' => $member->id,
                'name' => $member->name,
                'points' => (int) $score->points,
                'accuracy' => $score->attempts ? (int) round(($score->points / $score->attempts) * 100) : 0,
                'streak' => $member->current_streak,
            ];
        })->filter()->sortByDesc('points')->values();

        if (! $rows->contains(fn ($row) => $row['id'] === $user->id)) {
            $rows->push([
                'id' => $user->id,
                'name' => $user->name,
                'points' => 0,
                'accuracy' => 0,
                'streak' => $user->current_streak,
            ]);
        }

        $ranked = $rows->values()->map(function (array $row, int $index) use ($user) {
            $row['rank'] = $index + 1;
            $row['is_you'] = $row['id'] === $user->id;

            return $row;
        });

        $youIndex = $ranked->search(fn ($row) => $row['is_you']);
        $start = max(0, $youIndex - 3);
        $around = $ranked->slice($start, 7)->values()->all();

        return response()->json([
            'period' => $period,
            'total' => $ranked->count(),
            'you' => $ranked[$youIndex],
            'top' => $ranked->take(50)->values()->all(),
            'around' => $around,
        ]);
    }
}
