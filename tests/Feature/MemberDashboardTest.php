<?php

namespace Tests\Feature;

use App\Models\Award;
use App\Models\Quiz;
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

    public function test_member_sees_where_they_stopped_plus_awards_and_recent_quizzes(): void
    {
        $member = User::factory()->create([
            'current_streak' => 7,
            'longest_streak' => 12,
            'last_practiced_on' => now()->toDateString(),
        ]);

        $quiz = Quiz::factory()->create([
            'title' => 'Harmonic 5th',
            'category' => 'Relative Pitch',
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

        Award::factory()->create([
            'user_id' => $member->id,
            'title' => '7-day streak',
            'kind' => 'streak',
        ]);

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Dashboard')
                ->where('auth.user.role', 'member')
                ->where('streak.current', 7)
                ->where('streak.longest', 12)
                ->where('resume.quiz.title', 'Harmonic 5th')
                ->where('resume.stopped_at_question', 12)
                ->where('resume.quiz.question_count', 25)
                ->where('resume.question.prompt', 'Which pair is a perfect 5th?')
                ->where('resume.question.choices.0', 'E-B')
                ->missing('resume.question.answer')
                ->has('recentQuizzes', 1)
                ->has('awards', 1)
                ->where('awards.0.title', '7-day streak'));
    }
}
