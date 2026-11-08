<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSession;
use App\Models\User;
use App\Support\QuizHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizHistoryProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_keeps_completed_count_accuracy_recent_results_and_achievements(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['question_count' => 25]);
        $time = now()->subHour();
        QuizSession::create(['user_id' => $user->id, 'slug' => $quiz->slug, 'correct' => 25, 'total' => 25, 'played_at' => $time]);
        $attempt = QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id, 'status' => 'completed', 'correct_answers' => 25, 'completed_at' => $time, 'stopped_at_question' => 26]);
        $this->assertCount(1, QuizHistory::completed($user));
        $attempt->update(['status' => 'in_progress', 'correct_answers' => 0, 'completed_at' => null, 'stopped_at_question' => 1]);
        $dashboard = $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props'];
        $this->assertSame(1, $dashboard['stats']['completed']);
        $this->assertSame(100, $dashboard['stats']['accuracy']);
        $this->assertCount(1, $dashboard['recentQuizzes']);
        $this->assertSame(1, collect($dashboard['achievements'])->firstWhere('name', 'Perfect Harmony')['current']);
        $progress = $this->get(route('progress'))->assertOk()->viewData('page')['props'];
        $this->assertSame(1, $progress['quizzesCompleted']['done']);
        $this->assertSame(25, $progress['overall']['answered']);
        $this->assertSame(100, $progress['overall']['accuracy']);
        $this->assertCount(1, $progress['strongest']);
        $this->assertCount(0, QuizHistory::completed(User::factory()->create()));
    }

    public function test_history_uses_original_totals_and_counts_distinct_quizzes_across_retries(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['question_count' => 50]);
        foreach ([20, 10] as $index => $correct) {
            QuizSession::create(['user_id' => $user->id, 'slug' => $quiz->slug, 'correct' => $correct, 'total' => 25, 'played_at' => now()->subDays($index)]);
        }
        $props = $this->actingAs($user)->get(route('progress'))->assertOk()->viewData('page')['props'];
        $this->assertSame(1, $props['quizzesCompleted']['done']);
        $this->assertSame(50, $props['overall']['answered']);
        $this->assertSame(60, $props['overall']['accuracy']);
        $this->assertCount(1, $props['strongest']);
        $this->assertCount(0, $props['needsWork']);
    }

    public function test_reset_clears_archived_completion_for_only_the_current_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach ([$user, $other] as $member) {
            QuizSession::create(['user_id' => $member->id, 'slug' => 'test', 'correct' => 1, 'total' => 1, 'played_at' => now()]);
        }
        $this->actingAs($user)->delete(route('settings.reset-progress'))->assertRedirect();
        $this->assertSame(0, QuizSession::where('user_id', $user->id)->count());
        $this->assertSame(1, QuizSession::where('user_id', $other->id)->count());
    }
}
