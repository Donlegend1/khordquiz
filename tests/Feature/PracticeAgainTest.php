<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PracticeAgainTest extends TestCase
{
    use RefreshDatabase;

    private function recordResult(User $user, Quiz $quiz, int $correct, int $total, int $minutesAgo = 0): QuizSession
    {
        return QuizSession::create(['user_id' => $user->id, 'slug' => $quiz->slug,
            'correct' => $correct, 'total' => $total, 'played_at' => now()->subMinutes($minutesAgo)]);
    }

    private function paths(User $user): array
    {
        return $this->actingAs($user)->get(route('for-you'))->assertOk()->viewData('page')['props']['paths'];
    }

    public function test_latest_completed_result_is_independent_per_level_and_follows_admin_difficulty(): void
    {
        $user = User::factory()->create();
        $beginner = Quiz::factory()->create(['difficulty' => 'Beginner']);
        $intermediate = Quiz::factory()->create(['difficulty' => 'Intermediate']);
        $advanced = Quiz::factory()->create(['difficulty' => 'Advanced']);
        $this->assertNull($this->paths($user)['Beginner']['practiceAgain']);
        $old = $this->recordResult($user, $beginner, 8, 25, 10);
        $this->recordResult($user, $intermediate, 9, 25, 5);
        $this->recordResult($user, $advanced, 20, 25);
        $paths = $this->paths($user);
        $this->assertSame(32, $paths['Beginner']['practiceAgain']['score']);
        $this->assertSame(17, $paths['Beginner']['practiceAgain']['mistakesCount']);
        $this->assertSame(route('quiz.history-review', $old), $paths['Beginner']['practiceAgain']['reviewUrl']);
        $this->assertSame(36, $paths['Intermediate']['practiceAgain']['score']);
        $this->assertNull($paths['Advanced']['practiceAgain']);
        $beginner->update(['difficulty' => 'Advanced']);
        $paths = $this->paths($user);
        $this->assertNull($paths['Beginner']['practiceAgain']);
        $this->assertNull($paths['Advanced']['practiceAgain']);
    }

    public function test_new_completion_replaces_the_old_one_and_exactly_forty_hides_without_falling_back(): void
    {
        $user = User::factory()->create();
        $first = Quiz::factory()->create(['difficulty' => 'Beginner']);
        $next = Quiz::factory()->create(['difficulty' => 'Beginner']);
        $this->recordResult($user, $first, 0, 25, 10);
        $this->recordResult($user, $next, 9, 25, 5);
        QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $next->id, 'correct_answers' => 0]);
        $this->assertSame($next->slug, $this->paths($user)['Beginner']['practiceAgain']['slug']);
        $this->recordResult($user, $next, 10, 25);
        $this->assertNull($this->paths($user)['Beginner']['practiceAgain']);
        $this->assertNull($this->paths(User::factory()->create())['Beginner']['practiceAgain']);
    }

    public function test_raw_threshold_and_stable_completion_tie_break_are_used(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['difficulty' => 'Beginner']);
        $this->recordResult($user, $quiz, 400, 1000);
        $latest = $this->recordResult($user, $quiz, 399, 1000);
        $this->assertSame(route('quiz.history-review', $latest), $this->paths($user)['Beginner']['practiceAgain']['reviewUrl']);
    }

    public function test_completed_answers_are_reviewable_after_retry_and_private_to_the_member(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['default_instrument' => 'Piano']);
        $quiz = Quiz::factory()->create(['title' => 'Find the Note', 'category' => 'Relative Pitch', 'difficulty' => 'Beginner', 'question_count' => 1]);
        QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'choices' => ['Do', 'Re'], 'answer' => 'Do']);
        $this->actingAs($user)->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $this->post(route('quiz.answer', $quiz), ['choice' => 'Re'])->assertRedirect();
        $this->post(route('quiz.next', $quiz))->assertRedirect(route('quiz.results', $quiz));
        $session = QuizSession::where('user_id', $user->id)->firstOrFail();
        $this->assertCount(1, $session->review_answers);
        $this->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $this->assertSame(0, QuizAttempt::where('user_id', $user->id)->firstOrFail()->answers()->count());
        $this->assertSame(1, QuizSession::where('user_id', $user->id)->count());
        $review = $this->get(route('quiz.history-review', $session))->assertOk()->viewData('page')['props'];
        $this->assertTrue($review['initial_review']);
        $this->assertSame('Re', $review['review'][0]['picked']);
        $this->assertSame('Do', $review['review'][0]['answer']);
        $this->assertSame('Piano', $review['result']['sound']);
        $this->assertSame(route('quiz.history-review', $session), $this->paths($user)['Beginner']['practiceAgain']['reviewUrl']);
        $this->actingAs(User::factory()->create())->get(route('quiz.history-review', $session))->assertNotFound();
    }

    public function test_multiple_retries_preserve_separate_answer_snapshots_even_after_question_changes(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['default_instrument' => 'Piano']);
        $quiz = Quiz::factory()->create(['title' => 'Find the Note', 'category' => 'Relative Pitch', 'question_count' => 1]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'answer' => 'Do']);
        $this->actingAs($user);
        foreach (['Re', 'Do', 'Mi'] as $picked) {
            $this->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
            $this->post(route('quiz.answer', $quiz), ['choice' => $picked])->assertRedirect();
            $this->post(route('quiz.next', $quiz))->assertRedirect();
        }
        $sessions = QuizSession::where('user_id', $user->id)->orderBy('id')->get();
        $this->assertCount(3, $sessions);
        $this->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $question->update(['answer' => 'Fa']);
        foreach ($sessions as $index => $session) {
            $records = $this->get(route('quiz.history-review', $session))->assertOk()->viewData('page')['props']['review'];
            $this->assertSame(['Re', 'Do', 'Mi'][$index], $records[0]['picked']);
            $this->assertSame('Do', $records[0]['answer']);
            $this->assertSame($question->id, $records[0]['question_id']);
            $this->assertNotEmpty($records[0]['answered_at']);
            $this->assertSame($index === 1, $records[0]['correct']);
        }
        $this->assertSame(3, QuizSession::where('user_id', $user->id)->count());
    }

    public function test_older_summary_only_result_is_honest_about_unavailable_answer_details(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $session = $this->recordResult($user, $quiz, 0, 25);
        $props = $this->actingAs($user)->get(route('quiz.history-review', $session))->assertOk()->viewData('page')['props'];
        $this->assertSame([], $props['review']);
        $this->assertNotNull($props['history_notice']);
        $this->assertSame(0, $props['result']['correct']);
    }

    public function test_two_completions_in_the_same_second_remain_distinct_and_latest_retry_wins(): void
    {
        Storage::fake('public');
        $this->freezeTime();
        $user = User::factory()->create(['default_instrument' => 'Piano']);
        $quiz = Quiz::factory()->create(['title' => 'Find the Note', 'category' => 'Relative Pitch', 'difficulty' => 'Beginner', 'question_count' => 1]);
        QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'answer' => 'Do']);
        $this->actingAs($user);
        foreach (['Re', 'Do'] as $answer) {
            $this->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
            $this->post(route('quiz.answer', $quiz), ['choice' => $answer])->assertRedirect();
            $this->post(route('quiz.next', $quiz))->assertRedirect();
        }
        $this->assertSame(2, QuizSession::where('user_id', $user->id)->count());
        $this->assertNull($this->paths($user)['Beginner']['practiceAgain']);
        $this->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $this->assertSame(2, QuizSession::where('user_id', $user->id)->count());
        $this->travelBack();
    }
}
