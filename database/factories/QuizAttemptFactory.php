<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
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
            'quiz_id' => Quiz::factory(),
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'stopped_at_question' => 1,
            'correct_answers' => 0,
            'last_played_at' => now(),
            'completed_at' => null,
        ];
    }
}
