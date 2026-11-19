<?php

namespace App\Services;

use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Support\Collection;

class Leaderboard
{
    public const PERIODS = ['week', 'all'];

    public const TOP_LIMIT = 50;

    /**
     * Rank every member by correct answers in the period. Members who haven't
     * played still get a rank (after everyone with points) so "you" always exists.
     *
     * @return array{
     *     period: string,
     *     total: int,
     *     you: array{id: int, name: string, points: int, accuracy: int, streak: int, rank: int, is_you: bool},
     *     top: list<array{id: int, name: string, points: int, accuracy: int, streak: int, rank: int, is_you: bool}>,
     *     around: list<array{id: int, name: string, points: int, accuracy: int, streak: int, rank: int, is_you: bool}>,
     * }
     */
    public function forUser(User $user, string $period = 'week'): array
    {
        $query = QuizSession::query()
            ->selectRaw('user_id, sum(correct) as points, sum(total) as attempts')
            ->groupBy('user_id');

        if ($period === 'week') {
            $query->where('played_at', '>=', now()->subDays(6)->startOfDay());
        }

        $scores = $query->get()->keyBy('user_id');
        $users = User::query()->whereIn('id', $scores->keys()->push($user->id)->unique())->get()->keyBy('id');

        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = $scores->map(function (QuizSession $score) use ($users): ?array {
            $member = $users->get($score->user_id);
            if (! $member) {
                return null;
            }

            return [
                'id' => $member->id,
                'name' => $member->name,
                'points' => (int) $score->points,
                'accuracy' => $score->attempts ? (int) round(($score->points / $score->attempts) * 100) : 0,
                'streak' => (int) $member->current_streak,
            ];
        })->filter()->sortByDesc('points')->values();

        if (! $rows->contains(fn (array $row): bool => $row['id'] === $user->id)) {
            $rows->push([
                'id' => $user->id,
                'name' => $user->name,
                'points' => 0,
                'accuracy' => 0,
                'streak' => (int) $user->current_streak,
            ]);
        }

        $ranked = $rows->values()->map(function (array $row, int $index) use ($user): array {
            $row['rank'] = $index + 1;
            $row['is_you'] = $row['id'] === $user->id;

            return $row;
        });

        $youIndex = $ranked->search(fn (array $row): bool => $row['is_you']);

        return [
            'period' => $period,
            'total' => $ranked->count(),
            'you' => $ranked[$youIndex],
            'top' => $ranked->take(self::TOP_LIMIT)->values()->all(),
            'around' => $ranked->slice(max(0, $youIndex - 3), 7)->values()->all(),
        ];
    }
}
