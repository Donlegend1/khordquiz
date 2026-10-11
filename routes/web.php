<?php

use App\Http\Controllers\Admin\AudioQuizController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SettingsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/privacy-policy', fn () => Inertia::render('Legal', ['page' => 'privacy']))->name('privacy');
Route::get('/terms', fn () => Inertia::render('Legal', ['page' => 'terms']))->name('terms');

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/progress', ProgressController::class)
    ->middleware(['auth', 'verified'])
    ->name('progress');

Route::patch('/progress/goal', [ProgressController::class, 'updateGoal'])
    ->middleware(['auth', 'verified'])
    ->name('progress.goal');

Route::get('/settings', [SettingsController::class, 'edit'])
    ->middleware(['auth', 'verified'])
    ->name('settings');

Route::delete('/settings/progress', [SettingsController::class, 'resetProgress'])
    ->middleware(['auth', 'verified'])
    ->name('settings.reset-progress');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/quiz', [QuizController::class, 'categories'])->name('quiz.categories');
    Route::get('/quiz/{category}', [QuizController::class, 'category'])->name('quiz.category');
    Route::get('/quiz/play/{quiz:slug}', [QuizController::class, 'preview'])->name('quiz.preview');
    Route::post('/quiz/play/{quiz:slug}/start', [QuizController::class, 'start'])->name('quiz.start');
    Route::get('/quiz/play/{quiz:slug}/question', [QuizController::class, 'play'])->name('quiz.play');
    Route::post('/quiz/play/{quiz:slug}/answer', [QuizController::class, 'answer'])->name('quiz.answer');
    Route::get('/quiz/play/{quiz:slug}/results', [QuizController::class, 'results'])->name('quiz.results');
});

// Previews built with sample data — the real features aren't built yet.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/for-you', fn () => Inertia::render('Member/ForYou'))->name('for-you');
    Route::get('/mistakes', fn () => Inertia::render('Member/Mistakes'))->name('mistakes');
    Route::get('/saved', fn () => Inertia::render('Member/Saved'))->name('saved');
    Route::get('/challenges', fn () => Inertia::render('Member/Challenges'))->name('challenges');
    Route::get('/leaderboard', fn () => Inertia::render('Member/Leaderboard'))->name('leaderboard');
    Route::get('/account', fn () => Inertia::render('Member/Account'))->name('account');
    Route::get('/subscription', fn () => Inertia::render('Member/Subscription'))->name('subscription');
    Route::get('/suggestions', fn () => Inertia::render('Member/Suggestions'))->name('suggestions');
    Route::get('/notifications', fn () => Inertia::render('Member/Notifications'))->name('notifications');
    Route::get('/support', fn () => Inertia::render('Member/Support'))->name('support');
    Route::get('/help-center', fn () => Inertia::render('Member/HelpCenter'))->name('help-center');
    Route::get('/support-email', fn () => Inertia::render('Member/SupportEmail'))->name('support-email');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/audio-quiz', [AudioQuizController::class, 'index'])->name('audio-quiz.index');
        Route::get('/audio-quiz/{category}', [AudioQuizController::class, 'show'])->name('audio-quiz.show');
        Route::get('/audio-quiz/{category}/{lesson}', [AudioQuizController::class, 'show'])->name('audio-quiz.lesson');
        Route::post('/audio-quiz/lessons/{quiz}/settings', [AudioQuizController::class, 'updateSettings'])->name('audio-quiz.settings.update');
        Route::post('/audio-quiz/lessons/{quiz}/questions', [AudioQuizController::class, 'storeQuestion'])->name('audio-quiz.questions.store');
        Route::post('/audio-quiz/lessons/{quiz}/questions/bulk', [AudioQuizController::class, 'storeBulkQuestions'])->name('audio-quiz.questions.bulk-store');
        Route::delete('/audio-quiz/questions/{question}', [AudioQuizController::class, 'destroyQuestion'])->name('audio-quiz.questions.destroy');
        Route::delete('/audio-quiz/lessons/{quiz}', [AudioQuizController::class, 'destroyLesson'])->name('audio-quiz.lessons.destroy');

        Route::get('/quizzes', [AdminQuizController::class, 'index'])->name('quizzes.index');
        Route::get('/quizzes/create', [AdminQuizController::class, 'create'])->name('quizzes.create');
        Route::post('/quizzes', [AdminQuizController::class, 'store'])->name('quizzes.store');
        Route::get('/quizzes/{quiz}/edit', [AdminQuizController::class, 'edit'])->name('quizzes.edit');
        Route::put('/quizzes/{quiz}', [AdminQuizController::class, 'update'])->name('quizzes.update');
        Route::delete('/quizzes/{quiz}', [AdminQuizController::class, 'destroy'])->name('quizzes.destroy');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/account/password', fn () => Inertia::render('Member/ChangePassword'))->name('account.password');
    Route::get('/account/delete', fn () => Inertia::render('Member/DeleteAccount'))->name('account.delete');
});

require __DIR__.'/auth.php';
