<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\QuizActivityController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SuggestionController;
use App\Http\Controllers\Api\SupportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::post('forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:5,1');
    Route::post('verify-otp', [PasswordResetController::class, 'verify'])->middleware('throttle:10,1');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1');

    Route::get('catalog', [CatalogController::class, 'index']);
    Route::get('quizzes/{slug}', [CatalogController::class, 'show'])->where('slug', '.*');
    Route::get('plans', [SubscriptionController::class, 'plans']);
    Route::get('help', [SupportController::class, 'help']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::patch('profile', [ProfileController::class, 'update']);

        Route::get('home', HomeController::class);
        Route::get('for-you', [ProgressController::class, 'forYou']);
        Route::get('progress', [ProgressController::class, 'show']);
        Route::patch('progress/goal', [ProgressController::class, 'updateGoal']);
        Route::delete('progress', [ProgressController::class, 'reset']);
        Route::get('mistakes', [ProgressController::class, 'mistakes']);
        Route::get('leaderboard', LeaderboardController::class);

        Route::get('saved', [QuizActivityController::class, 'saved']);
        Route::post('quizzes/{slug}/bookmark', [QuizActivityController::class, 'bookmark'])->where('slug', '.*');
        Route::post('quizzes/{slug}/sessions', [QuizActivityController::class, 'session'])->where('slug', '.*');

        Route::get('challenges', [ChallengeController::class, 'index']);
        Route::post('challenges', [ChallengeController::class, 'store']);
        Route::post('challenges/{challenge}/complete', [ChallengeController::class, 'complete']);
        Route::post('challenges/{challenge}/results', [ChallengeController::class, 'result']);

        Route::get('suggestions', [SuggestionController::class, 'index']);
        Route::post('suggestions', [SuggestionController::class, 'store']);
        Route::post('suggestions/{suggestion}/vote', [SuggestionController::class, 'vote']);

        Route::get('settings', [SettingsController::class, 'show']);
        Route::patch('settings', [SettingsController::class, 'update']);

        Route::get('subscription', [SubscriptionController::class, 'show']);
        Route::post('subscription/trial', [SubscriptionController::class, 'trial']);
        Route::post('subscription', [SubscriptionController::class, 'subscribe']);
        Route::post('subscription/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('subscription/resume', [SubscriptionController::class, 'resume']);

        Route::get('account', [AccountController::class, 'show']);
        Route::put('account/email', [AccountController::class, 'email']);
        Route::put('account/password', [AccountController::class, 'password']);
        Route::delete('account', [AccountController::class, 'destroy']);

        Route::get('chat', [SupportController::class, 'messages']);
        Route::post('chat', [SupportController::class, 'send']);
        Route::post('support', [SupportController::class, 'email']);
    });
});
