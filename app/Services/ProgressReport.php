<?php

namespace App\Services;

use App\Models\QuizAnswer;
use App\Models\QuizCompletion;
use App\Models\QuizSession;
use App\Models\User;
use App\Support\MobileCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProgressReport
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $answers = QuizAnswer::query()->where('user_id', $user->id)->orderBy('answered_at')->get();
        $sessions = QuizSession::query()->where('user_id', $user->id)->orderBy('played_at')->get();
        $completed = QuizCompletion::query()->where('user_id', $user->id)->pluck('slug')->all();
        $completedSet = array_flip($completed);
        $now = now();
        $today = $now->copy()->startOfDay();

        $bySlug = [];
        foreach ($answers as $answer) {
            $score = $bySlug[$answer->slug] ?? ['correct' => 0, 'total' => 0];
            $score['total']++;
            if ($answer->correct) {
                $score['correct']++;
            }
            $bySlug[$answer->slug] = $score;
        }

        $overall = [
            'correct' => $answers->where('correct', true)->count(),
            'total' => $answers->count(),
        ];

        $categories = [];
        foreach (MobileCatalog::categories() as $name) {
            $quizzes = [];
            $score = ['correct' => 0, 'total' => 0];
            $done = 0;

            foreach (MobileCatalog::quizzes($name) as $quiz) {
                $quizScore = $bySlug[$quiz['slug']] ?? ['correct' => 0, 'total' => 0];
                $score['correct'] += $quizScore['correct'];
                $score['total'] += $quizScore['total'];
                $isDone = isset($completedSet[$quiz['slug']]);
                $done += $isDone ? 1 : 0;
                $quizzes[] = $quiz + [
                    'completed' => $isDone,
                    'score' => $quizScore,
                    'accuracy' => $this->percent($quizScore),
                ];
            }

            $categories[] = [
                'name' => $name,
                'completed' => $done,
                'total' => count($quizzes),
                'score' => $score,
                'accuracy' => $this->percent($score),
                'quizzes' => $quizzes,
            ];
        }

        $ranked = [];
        foreach ($bySlug as $slug => $score) {
            if ($score['total'] < 5) {
                continue;
            }
            $quiz = MobileCatalog::find($slug);
            if ($quiz) {
                $ranked[] = $quiz + $score + ['accuracy' => $this->percent($score)];
            }
        }
        usort($ranked, fn ($a, $b) => $a['accuracy'] <=> $b['accuracy'] ?: $b['total'] <=> $a['total']);
        $weak = array_values(array_filter($ranked, fn ($quiz) => $quiz['accuracy'] < 100));
        $weakSpots = array_slice($weak, 0, 3);
        $weakIds = array_column($weakSpots, 'slug');
        $strengths = array_slice(array_values(array_filter(array_reverse($ranked), fn ($quiz) => ! in_array($quiz['slug'], $weakIds, true))), 0, 3);

        $confusions = [];
        foreach ($answers as $answer) {
            if ($answer->kind !== 'single' || $answer->correct || ! $answer->picked) {
                continue;
            }
            $key = $answer->answer.'→'.$answer->picked;
            $confusions[$key] ??= ['answer' => $answer->answer, 'picked' => $answer->picked, 'count' => 0];
            $confusions[$key]['count']++;
        }
        $confusions = array_values(array_filter($confusions, fn ($entry) => $entry['count'] >= 2));
        usort($confusions, fn ($a, $b) => $b['count'] <=> $a['count']);
        $confusions = array_slice($confusions, 0, 3);

        $practiceDays = [];
        foreach ($answers as $answer) {
            $practiceDays[$answer->answered_at->copy()->startOfDay()->timestamp] = true;
        }
        $streak = 0;
        $cursor = isset($practiceDays[$today->timestamp]) ? $today->copy() : $today->copy()->subDay();
        while (isset($practiceDays[$cursor->timestamp])) {
            $streak++;
            $cursor->subDay();
        }

        $week = [];
        for ($index = 0; $index < 7; $index++) {
            $start = $today->copy()->subDays(6 - $index);
            $end = $start->copy()->addDay();
            $dayAnswers = $answers->filter(fn (QuizAnswer $answer) => $answer->answered_at >= $start && $answer->answered_at < $end);
            $week[] = [
                'start' => $start->toDateString(),
                'correct' => $dayAnswers->where('correct', true)->count(),
                'total' => $dayAnswers->count(),
            ];
        }

        $scoreBetween = function (Carbon $from, Carbon $to) use ($answers) {
            $range = $answers->filter(fn (QuizAnswer $answer) => $answer->answered_at >= $from && $answer->answered_at < $to);
            $correct = $range->where('correct', true)->count();

            return ['correct' => $correct, 'total' => $range->count()];
        };
        $thisWeek = $scoreBetween($today->copy()->subDays(6), $today->copy()->addDay());
        $lastWeek = $scoreBetween($today->copy()->subDays(13), $today->copy()->subDays(6));
        $trend = $thisWeek['total'] >= 5 && $lastWeek['total'] >= 5
            ? $this->percent($thisWeek) - $this->percent($lastWeek)
            : null;

        $dayTotals = [];
        foreach ($answers as $answer) {
            $key = $answer->answered_at->copy()->startOfDay()->timestamp;
            $dayTotals[$key] = ($dayTotals[$key] ?? 0) + 1;
        }
        $sortedDays = array_keys($dayTotals);
        sort($sortedDays);
        $longest = 0;
        $run = 0;
        foreach ($sortedDays as $index => $day) {
            $run = $index > 0 && ($day - $sortedDays[$index - 1]) <= (int) (1.5 * 86400) ? $run + 1 : 1;
            $longest = max($longest, $run);
        }

        $bestSession = $sessions->sortByDesc(fn (QuizSession $session) => $session->total ? $session->correct / $session->total : 0)->first();
        $bestQuiz = $bestSession ? MobileCatalog::find($bestSession->slug) : null;

        $recent = $sessions->reverse()->take(5)->map(function (QuizSession $session) {
            $quiz = MobileCatalog::find($session->slug);

            return $quiz ? [
                'slug' => $session->slug,
                'correct' => $session->correct,
                'total' => $session->total,
                'played_at' => $session->played_at?->toIso8601String(),
                'quiz' => $quiz,
            ] : null;
        })->filter()->values()->all();

        $lastCategory = $sessions->isEmpty() ? null : MobileCatalog::find($sessions->last()->slug)['category'] ?? null;
        $ordered = $lastCategory
            ? array_merge(
                array_values(array_filter($categories, fn ($category) => $category['name'] === $lastCategory)),
                array_values(array_filter($categories, fn ($category) => $category['name'] !== $lastCategory)),
            )
            : $categories;
        $nextQuiz = null;
        foreach ($ordered as $category) {
            foreach ($category['quizzes'] as $quiz) {
                if (! $quiz['completed']) {
                    $nextQuiz = $quiz;
                    break 2;
                }
            }
        }

        $badges = array_values(array_map(
            fn ($category) => $category['name'],
            array_filter($categories, fn ($category) => $category['total'] > 0 && $category['completed'] === $category['total']),
        ));
        $closest = collect($categories)
            ->filter(fn ($category) => $category['completed'] < $category['total'] && $category['completed'] > 0)
            ->sortByDesc(fn ($category) => $category['completed'] / $category['total'])
            ->first();

        return [
            'overall' => $overall,
            'accuracy' => $this->percent($overall),
            'categories' => $categories,
            'quiz_count' => array_sum(array_column($categories, 'total')),
            'completed_count' => array_sum(array_column($categories, 'completed')),
            'weak_spots' => $weakSpots,
            'strengths' => $strengths,
            'confusions' => $confusions,
            'streak' => $streak,
            'week' => $week,
            'trend' => $trend,
            'goal' => [
                'done' => $week[6]['total'],
                'target' => $user->daily_goal,
            ],
            'bests' => [
                'best_day' => $dayTotals === [] ? 0 : max($dayTotals),
                'longest_streak' => $longest,
                'best_session' => $bestSession && $bestQuiz ? [
                    'correct' => $bestSession->correct,
                    'total' => $bestSession->total,
                    'played_at' => $bestSession->played_at?->toIso8601String(),
                    'quiz' => $bestQuiz,
                ] : null,
            ],
            'mistakes' => $this->mistakes($answers, $now),
            'recent' => $recent,
            'next_quiz' => $nextQuiz,
            'badges' => $badges,
            'closest' => $closest,
        ];
    }

    /**
     * @param  Collection<int, QuizAnswer>  $answers
     * @return list<array<string, mixed>>
     */
    public function mistakes($answers, ?Carbon $now = null): array
    {
        $now ??= now();
        $open = [];

        foreach ($answers as $answer) {
            if ($answer->kind !== 'single') {
                continue;
            }
            $key = $answer->slug.'|'.$answer->answer;
            if ($answer->correct) {
                unset($open[$key]);
            } else {
                $open[$key] = $answer;
            }
        }

        $cutoff = $now->copy()->subDays(30);
        $mistakes = array_values(array_filter($open, fn (QuizAnswer $answer) => $answer->answered_at >= $cutoff));
        usort($mistakes, fn (QuizAnswer $a, QuizAnswer $b) => $b->answered_at <=> $a->answered_at);

        return array_map(function (QuizAnswer $answer) {
            $quiz = MobileCatalog::find($answer->slug);

            return [
                'slug' => $answer->slug,
                'answer' => $answer->answer,
                'picked' => $answer->picked,
                'kind' => $answer->kind,
                'answered_at' => $answer->answered_at?->toIso8601String(),
                'quiz' => $quiz,
            ];
        }, array_slice($mistakes, 0, 10));
    }

    /**
     * @param  array{correct: int, total: int}  $score
     */
    private function percent(array $score): int
    {
        return $score['total'] ? (int) round(($score['correct'] / $score['total']) * 100) : 0;
    }
}
