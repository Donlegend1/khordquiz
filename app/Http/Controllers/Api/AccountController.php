<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ProgressReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;

class AccountController extends Controller
{
    public function show(Request $request, ProgressReport $progress): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $report = $progress->forUser($user);

        return response()->json([
            'user' => UserResource::make($user)->resolve(),
            'subscription' => Subscription::forUser($user)->toApi(),
            'stats' => [
                'accuracy' => $report['accuracy'],
                'streak' => $report['streak'],
                'quizzes_completed' => $report['completed_count'],
                'daily_goal' => $report['goal'],
            ],
        ]);
    }

    public function email(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
        ]);

        $user->update(['email' => strtolower($data['email'])]);

        return response()->json([
            'message' => 'Email updated.',
            'user' => UserResource::make($user->fresh())->resolve(),
        ]);
    }

    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $data['password']]);
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $request->user()->tokens()->whereKeyNot($token->id)->delete();
        }

        return response()->json(['message' => 'Password updated.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted.']);
    }
}
