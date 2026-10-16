<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizAnswer;
use App\Models\QuizBookmark;
use App\Models\QuizCompletion;
use App\Models\QuizSession;
use App\Models\User;
use App\Support\MobileCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuizActivityController extends Controller
{
    public function saved(Request $request): JsonResponse
    {
        $slugs = QuizBookmark::query()->where('user_id', $request->user()->id)->latest('id')->pluck('slug');
        $completed = array_flip(QuizCompletion::query()->where('user_id', $request->user()->id)->pluck('slug')->all());
        $quizzes = [];

        foreach ($slugs as $slug) {
            $quiz = MobileCatalog::find($slug);
            if ($quiz) {
                $quizzes[] = $quiz + ['completed' => isset($completed[$slug])];
            }
        }

        return response()->json(['quizzes' => $quizzes]);
    }

    public function bookmark(Request $request, string $slug): JsonResponse
    {
        $quiz = $this->quiz($slug);
        $bookmark = QuizBookmark::query()->where('user_id', $request->user()->id)->where('slug', $quiz['slug']);

        if ($bookmark->exists()) {
            $bookmark->delete();
            $saved = false;
        } else {
            QuizBookmark::query()->create(['user_id' => $request->user()->id, 'slug' => $quiz['slug']]);
            $saved = true;
        }

        return response()->json([
            'bookmarked' => $saved,
            'quiz' => $quiz,
        ]);
    }

    public function session(Request $request, string $slug): JsonResponse
    {
        $quiz = $this->quiz($slug);
        $data = $request->validate([
            'correct' => ['required', 'integer', 'min:0', 'max:'.MobileCatalog::QUIZ_LENGTH],
            'total' => ['required', 'integer', 'min:1', 'max:'.MobileCatalog::QUIZ_LENGTH],
            'completed' => ['sometimes', 'boolean'],
            'answers' => ['sometimes', 'array', 'max:'.MobileCatalog::QUIZ_LENGTH],
            'answers.*.answer' => ['required', 'string', 'max:255'],
            'answers.*.picked' => ['nullable', 'string', 'max:255'],
            'answers.*.correct' => ['required', 'boolean'],
            'answers.*.kind' => ['sometimes', 'string', Rule::in(['single', 'other'])],
        ]);

        if ($data['correct'] > $data['total']) {
            return response()->json(['message' => 'Correct answers cannot exceed the total.', 'errors' => ['correct' => ['Correct answers cannot exceed the total.']]], 422);
        }

        /** @var User $user */
        $user = $request->user();
        $playedAt = now();

        QuizSession::query()->create([
            'user_id' => $user->id,
            'slug' => $quiz['slug'],
            'correct' => $data['correct'],
            'total' => $data['total'],
            'played_at' => $playedAt,
        ]);

        foreach ($data['answers'] ?? [] as $answer) {
            QuizAnswer::query()->create([
                'user_id' => $user->id,
                'slug' => $quiz['slug'],
                'answer' => $answer['answer'],
                'picked' => $answer['picked'] ?? null,
                'correct' => $answer['correct'],
                'kind' => $answer['kind'] ?? 'single',
                'answered_at' => $playedAt,
            ]);
        }

        $markComplete = ($data['completed'] ?? false) || $data['correct'] === $data['total'];
        if ($markComplete) {
            QuizCompletion::query()->firstOrCreate(
                ['user_id' => $user->id, 'slug' => $quiz['slug']],
                ['completed_at' => $playedAt],
            );
        }

        $this->touchStreak($user);

        return response()->json([
            'message' => 'Quiz saved.',
            'quiz' => $quiz,
            'correct' => $data['correct'],
            'total' => $data['total'],
            'completed' => $markComplete,
            'accuracy' => (int) round(($data['correct'] / $data['total']) * 100),
        ], 201);
    }

    /**
     * @return array{category: string, name: string, slug: string, question_count: int, sounds: list<string>}
     */
    private function quiz(string $slug): array
    {
        $quiz = MobileCatalog::find(urldecode($slug));
        abort_if($quiz === null, 404, 'Quiz not found.');

        return $quiz;
    }

    private function touchStreak(User $user): void
    {
        $today = now()->toDateString();
        $last = $user->last_practiced_on?->toDateString();

        if ($last !== $today) {
            $user->current_streak = $last === now()->subDay()->toDateString()
                ? $user->current_streak + 1
                : 1;
            $user->longest_streak = max($user->longest_streak, $user->current_streak);
            $user->last_practiced_on = $today;
            $user->save();
        }
    }
}
