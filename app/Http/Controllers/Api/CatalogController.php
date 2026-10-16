<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizBookmark;
use App\Models\QuizCompletion;
use App\Support\MobileCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $completed = [];
        $saved = [];

        if ($user = $request->user('sanctum')) {
            $completed = array_flip(QuizCompletion::query()->where('user_id', $user->id)->pluck('slug')->all());
            $saved = array_flip(QuizBookmark::query()->where('user_id', $user->id)->pluck('slug')->all());
        }

        $categories = [];
        foreach (MobileCatalog::categories() as $name) {
            $quizzes = array_map(fn (array $quiz) => $quiz + [
                'completed' => isset($completed[$quiz['slug']]),
                'bookmarked' => isset($saved[$quiz['slug']]),
            ], MobileCatalog::quizzes($name));

            $categories[] = [
                'name' => $name,
                'quiz_count' => count($quizzes),
                'completed_count' => count(array_filter($quizzes, fn ($quiz) => $quiz['completed'])),
                'quizzes' => $quizzes,
            ];
        }

        return response()->json([
            'quiz_length' => MobileCatalog::QUIZ_LENGTH,
            'categories' => $categories,
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $quiz = MobileCatalog::find(urldecode($slug));

        if (! $quiz) {
            return response()->json(['message' => 'Quiz not found.'], 404);
        }

        $user = $request->user('sanctum');
        $quiz['completed'] = $user && QuizCompletion::query()->where('user_id', $user->id)->where('slug', $quiz['slug'])->exists();
        $quiz['bookmarked'] = $user && QuizBookmark::query()->where('user_id', $user->id)->where('slug', $quiz['slug'])->exists();

        return response()->json(['quiz' => $quiz]);
    }
}
