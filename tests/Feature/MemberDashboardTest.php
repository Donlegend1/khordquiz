<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_member_sees_where_they_stopped_plus_achievements_and_recent_quizzes(): void
    {
        $member = User::factory()->create([
            'current_streak' => 7,
            'longest_streak' => 12,
            'last_practiced_on' => now()->toDateString(),
            'daily_goal' => 250,
        ]);

        $quiz = Quiz::factory()->create([
            'title' => 'Melodic Diatonic Intervals',
            'category' => 'Intervals',
            'question_count' => 25,
        ]);

        QuizQuestion::factory()->create([
            'quiz_id' => $quiz->id,
            'position' => 12,
            'prompt' => 'Which pair is a perfect 5th?',
            'choices' => ['E-B', 'C-F', 'F-B', 'D-G#'],
            'answer' => 'E-B',
        ]);

        QuizAttempt::factory()->create([
            'user_id' => $member->id,
            'quiz_id' => $quiz->id,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'stopped_at_question' => 12,
            'correct_answers' => 9,
            'last_played_at' => now(),
        ]);

        $completedQuiz = Quiz::factory()->create([
            'title' => 'Find the Note',
            'category' => 'Relative Pitch',
            'question_count' => 4,
        ]);

        QuizAttempt::factory()->create([
            'user_id' => $member->id,
            'quiz_id' => $completedQuiz->id,
            'status' => QuizAttempt::STATUS_COMPLETED,
            'stopped_at_question' => 5,
            'correct_answers' => 4,
            'last_played_at' => now(),
            'completed_at' => now(),
        ]);

        foreach (range(1, 15) as $position) {
            QuizAnswer::create(['user_id' => $member->id, 'slug' => $quiz->slug, 'answer' => 'Do', 'correct' => true, 'answered_at' => now()]);
        }

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Dashboard')
                ->where('auth.user.role', 'member')
                ->where('streak.current', 7)
                ->where('streak.longest', 12)
                ->where('goal.target', 250)
                ->where('goal.done', 15)
                ->where('resume.quiz.title', 'Melodic Diatonic Intervals')
                ->where('resume.stopped_at_question', 12)
                ->where('resume.quiz.question_count', 25)
                ->where('resume.question.prompt', 'Which pair is a perfect 5th?')
                ->where('resume.question.choices.0', 'E-B')
                ->missing('resume.question.answer')
                ->has('recentQuizzes', 1)
                ->where('recentQuizzes.0.quiz.title', 'Find the Note')
                ->where('recentQuizzes.0.score', 100)
                ->has('achievements', 6)
                ->where('achievements.0.name', 'First Steps')
                ->where('achievements.0.current', 1)
                ->where('achievements.3.name', 'Week Warrior')
                ->where('achievements.3.current', 7));
    }

    public function test_daily_goal_starts_empty_and_ignores_other_members_and_previous_days(): void
    {
        $member = User::factory()->create(['daily_goal' => 50]);
        QuizAttempt::factory()->create(['user_id' => $member->id, 'last_played_at' => now()->subDay(), 'stopped_at_question' => 20]);
        QuizAttempt::factory()->create(['stopped_at_question' => 20]);
        QuizAttempt::factory()->create(['user_id' => $member->id, 'stopped_at_question' => 1]);

        $this->actingAs($member)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('goal.target', 50)
                ->where('goal.done', 0));
    }

    public function test_daily_goal_progress_stops_at_the_saved_target(): void
    {
        $member = User::factory()->create(['daily_goal' => 20]);
        QuizAttempt::factory()->create([
            'user_id' => $member->id,
            'quiz_id' => Quiz::factory()->create(['question_count' => 25])->id,
            'status' => QuizAttempt::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        foreach (range(1, 25) as $position) {
            QuizAnswer::create(['user_id' => $member->id, 'slug' => 'test', 'answer' => 'Do', 'correct' => true, 'answered_at' => now()]);
        }

        $this->actingAs($member)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('goal.target', 20)
                ->where('goal.done', 20));
    }

    public function test_progress_continue_card_receives_the_exact_lesson_slug(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['title' => 'Basic Triads · Level 2', 'category' => 'Basic Triads']);
        QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id, 'stopped_at_question' => 5]);
        $this->actingAs($user)->get(route('progress'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Member/Progress')
            ->where('resume.quiz.slug', $quiz->slug)
            ->where('resume.quiz.categorySlug', 'basic-triads')
            ->where('resume.stopped_at_question', 5));
    }
}
