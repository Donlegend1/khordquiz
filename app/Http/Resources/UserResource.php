<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'email' => $this->email,
            'country' => $this->country,
            'skill_level' => $this->skill_level,
            'avatar' => $this->avatar,
            'current_streak' => $this->current_streak,
            'longest_streak' => $this->longest_streak,
            'daily_goal' => $this->daily_goal,
            'terms_accepted_at' => $this->terms_accepted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
