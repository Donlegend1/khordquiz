<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Support\QuizHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyAnswerHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_answers_are_counted_on_submission_day_and_survive_cancellation_and_retry(): void
    {
        $user = User::factory()->create(['daily_goal' => 20]);
        $quiz = Quiz::factory()->create();
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        $attempt = QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);
        $yesterday = now()->subDay()->startOfDay()->addHours(23);
        $today = now()->startOfDay()->addHours(1);
        foreach ([$yesterday, $today] as $position => $time) {
            $this->travelTo($time);
            $attempt->answers()->create(['position' => $position + 1, 'quiz_question_id' => $question->id, 'picked' => 'Do', 'correct' => true]);
        }
        $this->travelTo($today);
        $attempt->answers()->delete();
        $attempt->update(['stopped_at_question' => 1, 'last_played_at' => now()]);
        $this->assertSame(1, QuizHistory::answeredOn($user, $yesterday));
        $this->assertSame(1, QuizHistory::answeredOn($user, $today));
        $this->assertSame(0, QuizHistory::answeredOn(User::factory()->create(), $today));
        $dashboard = $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props'];
        $progress = $this->get(route('progress'))->assertOk()->viewData('page')['props'];
        $this->assertSame(1, $dashboard['goal']['done']);
        $this->assertSame(1, $progress['goal']['done']);
        $this->assertSame(1, $progress['week'][5]['count']);
        $this->assertSame(1, $progress['week'][6]['count']);
        $this->assertSame(2, QuizAnswer::where('user_id', $user->id)->count());
        $this->travelBack();
    }

    public function test_repeated_submit_does_not_create_extra_daily_answers(): void
    {
        $user = User::factory()->create(['daily_goal' => 20]);
        $quiz = Quiz::factory()->create(['question_count' => 1]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'answer' => 'Do']);
        QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id, 'question_sequence' => [$question->id]]);
        $this->actingAs($user)->post(route('quiz.answer', $quiz), ['choice' => 'Do'])->assertRedirect();
        $this->post(route('quiz.answer', $quiz), ['choice' => 'Do'])->assertRedirect();
        $this->assertSame(1, QuizHistory::answeredOn($user, now()));
        $this->delete(route('settings.reset-progress'))->assertRedirect();
        $this->assertSame(0, QuizHistory::answeredOn($user, now()));
    }
}
