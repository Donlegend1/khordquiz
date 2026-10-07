<?php

namespace Database\Factories;

use App\Models\Award;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Award>
 */
class AwardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quiz_id' => null,
            'title' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'kind' => 'milestone',
            'earned_at' => now(),
        ];
    }
}
