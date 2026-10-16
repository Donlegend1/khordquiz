<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Http\Resources\UserResource;
use App\Models\User;

trait IssuesApiTokens
{
    /**
     * @return array{token: string, token_type: string, user: array<string, mixed>}
     */
    protected function tokenPayload(User $user, ?string $deviceName): array
    {
        $name = trim((string) $deviceName);

        return [
            'token' => $user->createToken($name !== '' ? $name : 'khordquiz-mobile')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => UserResource::make($user)->resolve(),
        ];
    }
}
