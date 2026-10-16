<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\IssuesApiTokens;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    use IssuesApiTokens;

    public function register(RegisterRequest $request): JsonResponse
    {
        $firstName = $request->string('first_name')->toString();
        $lastName = $request->string('last_name')->toString();

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim($firstName.' '.$lastName),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'country' => $request->string('country')->toString(),
            'role' => Role::Member,
            'terms_accepted_at' => now(),
        ]);

        event(new Registered($user));

        return response()->json([
            'message' => 'Account created.',
            ...$this->tokenPayload($user, $request->input('device_name')),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $request->authenticate();

        return response()->json([
            'message' => 'Signed in.',
            ...$this->tokenPayload($user, $request->input('device_name')),
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => UserResource::make($request->user())->resolve(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Signed out.',
        ]);
    }
}
