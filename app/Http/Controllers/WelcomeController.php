<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        $quizzes = Quiz::query()->where('is_published', true)->where('question_count', '>', 0)
            ->get(['title', 'category', 'difficulty'])
            ->sortBy(function (Quiz $quiz): array {
                $category = array_search($quiz->category, Quiz::CATEGORIES, true);
                $lesson = array_search($quiz->title, QuizCatalog::subcategories($quiz->category), true);

                return [$category === false ? PHP_INT_MAX : $category, $lesson === false ? PHP_INT_MAX : $lesson, $quiz->title];
            });
        $paths = collect(Quiz::DIFFICULTIES)->map(fn (string $level) => [
            'level' => $level,
            'categories' => $quizzes->where('difficulty', $level)->groupBy('category')
                ->map(fn ($lessons, string $category) => [
                    'name' => $category,
                    'quizzes' => $lessons->pluck('title')->values(),
                ])->values(),
        ]);

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'catalog' => [
                'paths' => $paths,
                'totals' => ['categories' => $quizzes->pluck('category')->unique()->count(), 'quizzes' => $quizzes->count()],
            ],
        ]);
    }
}
