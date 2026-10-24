<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->fill($request->safe()->only([
            'first_name',
            'last_name',
            'country',
            'skill_level',
            'avatar',
        ]));

        if ($user->isDirty('first_name') || $user->isDirty('last_name')) {
            $user->name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated.',
            'user' => UserResource::make($user)->resolve(),
        ]);
    }
}
