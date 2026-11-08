<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizRetryInstrumentTest extends TestCase
{
    use RefreshDatabase;

    private function quiz(): Quiz
    {
        $quiz = Quiz::factory()->create(['category' => 'Relative Pitch', 'title' => 'Find the Note', 'question_count' => 1, 'is_published' => true]);
        $question = $quiz->questions()->create(['position' => 1, 'answer' => 'C', 'choices' => ['C'], 'prompt' => 'Identify the note.']);
        foreach (['Piano', 'Upright Piano', 'EP'] as $sound) {
            $question->audioVariants()->create(['sound' => $sound, 'audio_path' => 'test/'.str_replace(' ', '-', $sound).'.wav']);
        }

        return $quiz;
    }

    public function test_retry_without_a_sound_keeps_the_saved_default_instrument(): void
    {
        $quiz = $this->quiz();
        $user = User::factory()->create(['default_instrument' => 'Upright Piano']);
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'sound' => 'Upright Piano', 'status' => QuizAttempt::STATUS_COMPLETED, 'completed_at' => now()]);
        $this->actingAs($user)->post(route('quiz.start', $quiz))->assertRedirect(route('quiz.play', $quiz));
        $this->assertSame('Upright Piano', $attempt->fresh()->sound);
        $this->assertSame(QuizAttempt::STATUS_IN_PROGRESS, $attempt->fresh()->status);
    }

    public function test_results_expose_the_attempt_sound_and_retry_preserves_an_explicit_selection(): void
    {
        $quiz = $this->quiz();
        $user = User::factory()->create(['default_instrument' => 'Upright Piano']);
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'sound' => 'EP', 'status' => QuizAttempt::STATUS_COMPLETED, 'completed_at' => now()]);
        $props = $this->actingAs($user)->get(route('quiz.results', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertSame('EP', $props['result']['sound']);
        $this->post(route('quiz.start', $quiz), ['sound' => $props['result']['sound']])->assertRedirect();
        $this->assertSame('EP', $attempt->fresh()->sound);
    }

    public function test_unavailable_saved_instrument_uses_an_available_quiz_sound(): void
    {
        $quiz = $this->quiz();
        $user = User::factory()->create(['default_instrument' => 'Not an instrument']);
        $this->actingAs($user)->post(route('quiz.start', $quiz))->assertRedirect();
        $this->assertSame('Piano', $quiz->attempts()->firstOrFail()->sound);
    }
}
