<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuizResultsReviewTest extends TestCase
{
    use RefreshDatabase;

    private function playThrough(User $user, Quiz $quiz, array $choices): void
    {
        $this->actingAs($user)->post(route('quiz.start', $quiz));

        foreach ($choices as $choice) {
            $this->actingAs($user)->post(route('quiz.answer', $quiz), ['choice' => $choice]);
            $this->actingAs($user)->post(route('quiz.next', $quiz));
        }
    }

    public function test_results_page_includes_a_per_question_review(): void
    {
        $user = User::factory()->subscribed()->create();
        $quiz = Quiz::factory()->create(['question_count' => 2]);
        QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => 1, 'answer' => 'C', 'choices' => ['C', 'D']]);
        QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => 2, 'answer' => 'D', 'choices' => ['C', 'D']]);

        $this->playThrough($user, $quiz, ['C', 'C']);

        $response = $this->actingAs($user)
            ->get(route('quiz.results', $quiz))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/QuizResults')
                ->where('result.correct', 1)
                ->has('review', 2));

        // Which of the two questions the attempt's shuffled sequence drew into slot 1
        // vs 2 is random (see QuizController::buildQuestionSequence) — assert the
        // right pairing exists rather than a fixed position, so this isn't flaky.
        $review = collect($response->viewData('page')['props']['review'])->keyBy('answer');
        $this->assertTrue($review['C']['correct']);
        $this->assertSame('C', $review['C']['picked']);
        $this->assertFalse($review['D']['correct']);
        $this->assertSame('C', $review['D']['picked']);
        foreach ($review as $row) {
            $question = $quiz->questions()->where('answer', $row['answer'])->firstOrFail();
            $this->assertSame($question->prompt, $row['prompt']);
            $this->assertContains($row['answer'], $row['choices']);
            $this->assertSame(1, $row['answer_length']);
            $this->assertNull($row['reference_note']);
        }
        $this->assertSame([], $response->viewData('page')['props']['review_keyboard_audio']);
    }

    public function test_retrying_a_quiz_clears_the_previous_review(): void
    {
        $user = User::factory()->subscribed()->create();
        $quiz = Quiz::factory()->create(['question_count' => 1]);
        QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => 1, 'answer' => 'C', 'choices' => ['C', 'D']]);

        $this->playThrough($user, $quiz, ['D']);
        $this->playThrough($user, $quiz, ['C']);

        $this->actingAs($user)
            ->get(route('quiz.results', $quiz))
            ->assertInertia(fn (Assert $page) => $page
                ->has('review', 1)
                ->where('review.0.picked', 'C')
                ->where('review.0.correct', true));
    }

    public function test_review_never_exposes_another_members_completed_attempt(): void
    {
        $quiz = Quiz::factory()->create(['question_count' => 1]);
        QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => 1, 'answer' => 'C', 'choices' => ['C', 'D']]);
        $owner = User::factory()->create();
        $this->playThrough($owner, $quiz, ['C']);
        $this->actingAs(User::factory()->create())->get(route('quiz.results', $quiz))->assertNotFound();
    }
}
