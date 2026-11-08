<?php

namespace App\Support;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuizHistory
{
    /** Same merged (attempts + archived sessions, deduplicated) and sorted
     * view as completed(), but built and paginated entirely at the database
     * level — completed() always materializes the user's whole history,
     * which is fine for the small in-memory filters/aggregates the other
     * callers need but doesn't scale as a user's history page grows.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public static function paginateCompleted(User $user, int $page, int $perPage = 20): LengthAwarePaginator
    {
        $sessions = DB::table('quiz_sessions as qs')
            ->join('quizzes as q', 'q.slug', '=', 'qs.slug')
            ->where('qs.user_id', $user->id)
            ->where('qs.total', '>', 0)
            ->select([
                DB::raw("'session' as source"),
                'qs.id as ref_id', 'qs.slug as slug', 'q.title as title', 'q.category as category',
                'qs.correct as correct', 'qs.total as total', 'qs.played_at as date', 'qs.sound as sound',
            ]);

        $attempts = DB::table('quiz_attempts as qa')
            ->join('quizzes as q', 'q.id', '=', 'qa.quiz_id')
            ->where('qa.user_id', $user->id)
            ->where('qa.status', QuizAttempt::STATUS_COMPLETED)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))->from('quiz_sessions as qs2')
                    ->whereColumn('qs2.user_id', 'qa.user_id')
                    ->whereColumn('qs2.slug', 'q.slug')
                    ->whereColumn('qs2.played_at', DB::raw('COALESCE(qa.completed_at, qa.last_played_at)'));
            })
            ->select([
                DB::raw("'attempt' as source"),
                'qa.id as ref_id', 'q.slug as slug', 'q.title as title', 'q.category as category',
                'qa.correct_answers as correct', 'q.question_count as total',
                DB::raw('COALESCE(qa.completed_at, qa.last_played_at) as date'), 'qa.sound as sound',
            ]);

        $history = DB::query()->fromSub($sessions->unionAll($attempts), 'history')
            ->orderByDesc('date')->orderByDesc('ref_id')
            ->paginate($perPage, ['*'], 'page', $page);

        $history->getCollection()->transform(fn (object $row): array => [
            'id' => $row->source.'-'.$row->ref_id,
            'title' => $row->title, 'category' => $row->category,
            'date' => Carbon::parse($row->date)->toIso8601String(),
            'sound' => $row->sound, 'correct' => $row->correct, 'total' => $row->total,
            'percent' => (int) round($row->correct / max(1, $row->total) * 100),
            'review_url' => $row->source === 'session'
                ? route('quiz.history-review', $row->ref_id)
                : route('quiz.results', ['quiz' => $row->slug, 'review' => 1]),
        ]);

        return $history;
    }

    /** Completed runs, including archived results, without counting the current run twice.
     * @return Collection<int, QuizAttempt>
     */
    public static function completed(User $user, ?Collection $attempts = null): Collection
    {
        $attempts ??= QuizAttempt::where('user_id', $user->id)->with('quiz')->get();
        $sessions = QuizSession::where('user_id', $user->id)->where('total', '>', 0)->orderBy('played_at')->orderBy('id')->get(['id', 'user_id', 'slug', 'correct', 'total', 'played_at', 'sound']);
        $quizzes = Quiz::whereIn('slug', $sessions->pluck('slug'))->get()->keyBy('slug');
        $completed = collect();
        $keys = [];
        foreach ($sessions as $session) {
            $quiz = $quizzes->get($session->slug);
            if (! $quiz) {
                continue;
            }
            $quiz = clone $quiz;
            $quiz->question_count = $session->total;
            $record = new QuizAttempt;
            $record->forceFill([
                'id' => -$session->id, 'quiz_id' => $quiz->id, 'user_id' => $user->id,
                'status' => QuizAttempt::STATUS_COMPLETED, 'correct_answers' => $session->correct,
                'completed_at' => $session->played_at, 'last_played_at' => $session->played_at,
                'stopped_at_question' => $session->total + 1, 'history_session_id' => $session->id,
                'sound' => $session->sound,
            ]);
            $record->setRelation('quiz', $quiz);
            $completed->push($record);
            $keys[$session->slug.'|'.$session->played_at->format('Y-m-d H:i:s')] = true;
        }
        foreach ($attempts->where('status', QuizAttempt::STATUS_COMPLETED) as $attempt) {
            $date = $attempt->completed_at ?? $attempt->last_played_at;
            if ($attempt->quiz && ! isset($keys[$attempt->quiz->slug.'|'.$date->format('Y-m-d H:i:s')])) {
                $completed->push($attempt);
            }
        }

        return $completed->sortByDesc(fn (QuizAttempt $attempt): array => [
            ($attempt->completed_at ?? $attempt->last_played_at)->getTimestamp(),
            $attempt->history_session_id ?? $attempt->id,
        ])->values();
    }

    public static function answeredOn(User $user, Carbon $day): int
    {
        return QuizAnswer::where('user_id', $user->id)
            ->where('answered_at', '>=', $day->copy()->startOfDay())
            ->where('answered_at', '<', $day->copy()->startOfDay()->addDay())->count();
    }

    public static function answeredCount(QuizAttempt $attempt): int
    {
        if ($attempt->status === QuizAttempt::STATUS_COMPLETED) {
            return $attempt->quiz->question_count;
        }

        return min($attempt->quiz->question_count, max($attempt->answers_count ?? $attempt->answers()->count(), max(0, $attempt->stopped_at_question - 1)));
    }
}
