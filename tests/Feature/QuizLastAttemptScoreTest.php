<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizLastAttemptScoreTest extends TestCase
{
    use RefreshDatabase;

    private function quiz(): Quiz
    {
        return Quiz::factory()->create(['category' => 'Relative Pitch', 'title' => 'Find the Note', 'question_count' => 25, 'is_published' => true]);
    }

    private function score(Quiz $quiz): ?int
    {
        $rows = $this->get(route('quiz.category', ['relative-pitch']))->assertOk()->viewData('page')['props']['quizzes'];

        return collect($rows)->firstWhere('slug', $quiz->slug)['score'];
    }

    public function test_no_completed_attempt_is_empty_and_another_users_score_is_private(): void
    {
        $quiz = $this->quiz();
        QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'status' => QuizAttempt::STATUS_COMPLETED, 'correct_answers' => 25, 'completed_at' => now()]);
        $this->actingAs(User::factory()->create());
        $this->assertNull($this->score($quiz));
    }

    public function test_completed_attempt_shows_a_percentage_including_zero(): void
    {
        $quiz = $this->quiz();
        $user = User::factory()->create();
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'status' => QuizAttempt::STATUS_COMPLETED, 'correct_answers' => 18, 'completed_at' => now()]);
        $this->actingAs($user);
        $this->assertSame(72, $this->score($quiz));
        $attempt->update(['correct_answers' => 0]);
        $this->assertSame(0, $this->score($quiz));
    }

    public function test_retry_keeps_the_last_completed_score_until_the_new_attempt_finishes(): void
    {
        $quiz = $this->quiz();
        $question = $quiz->questions()->create(['position' => 1, 'answer' => 'C', 'choices' => ['C'], 'prompt' => 'Identify the note.']);
        $question->audioVariants()->create(['sound' => 'Piano', 'audio_path' => 'test/piano.wav']);
        $user = User::factory()->create();
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'status' => QuizAttempt::STATUS_COMPLETED, 'correct_answers' => 20, 'completed_at' => now()]);
        $this->actingAs($user)->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $this->assertSame(0, $attempt->fresh()->correct_answers);
        $this->assertSame(80, $this->score($quiz));
        $this->assertDatabaseHas('quiz_sessions', ['user_id' => $user->id, 'slug' => $quiz->slug, 'correct' => 20, 'total' => 25]);
        $attempt->refresh()->update(['status' => QuizAttempt::STATUS_COMPLETED, 'correct_answers' => 3, 'completed_at' => now()->addMinute()]);
        $this->assertSame(12, $this->score($quiz));
    }

    public function test_history_uses_the_latest_score_rather_than_the_highest(): void
    {
        $quiz = $this->quiz();
        $user = User::factory()->create();
        foreach ([[25, now()->subDay()], [10, now()]] as [$correct, $playedAt]) {
            QuizSession::create(['user_id' => $user->id, 'slug' => $quiz->slug, 'correct' => $correct, 'total' => 25, 'played_at' => $playedAt]);
        }
        $this->actingAs($user);
        $this->assertSame(40, $this->score($quiz));
    }

    public function test_score_history_migration_preserves_an_existing_table(): void
    {
        $user = User::factory()->create();
        $session = QuizSession::create(['user_id' => $user->id, 'slug' => 'find-the-note', 'correct' => 18, 'total' => 25, 'played_at' => now()]);
        $migration = require database_path('migrations/2026_10_16_185959_create_quiz_sessions_table.php');
        $migration->up();
        $this->assertDatabaseHas('quiz_sessions', ['id' => $session->id, 'correct' => 18, 'total' => 25]);
    }
}
