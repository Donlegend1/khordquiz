<?php

namespace Tests\Feature;

use App\Models\QuestionReport;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttemptHistoryAndReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_parameter_does_not_add_fake_history(): void
    {
        $this->app['env'] = 'local';
        $props = $this->actingAs(User::factory()->create())->get(route('quiz.history', ['sample' => 1]))->assertOk()->viewData('page')['props'];
        $this->assertSame(0, $props['history']['total']);
        $this->assertDatabaseCount('quiz_sessions', 0);
    }

    public function test_history_is_private_paginated_and_includes_archived_runs(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        foreach (range(1, 22) as $index) {
            QuizSession::create(['user_id' => $user->id, 'slug' => $quiz->slug, 'correct' => $index, 'total' => 25, 'played_at' => now()->subMinutes($index), 'sound' => 'Upright Piano']);
        }
        QuizSession::create(['user_id' => User::factory()->create()->id, 'slug' => $quiz->slug, 'correct' => 25, 'total' => 25, 'played_at' => now()]);
        $props = $this->actingAs($user)->get(route('quiz.history'))->assertOk()->viewData('page')['props']['history'];
        $this->assertSame(22, $props['total']);
        $this->assertCount(20, $props['data']);
        $this->assertSame('Upright Piano', $props['data'][0]['sound']);
        $this->assertSame(4, $props['data'][0]['percent']);
        $this->assertCount(2, $this->get(route('quiz.history', ['page' => 2]))->assertOk()->viewData('page')['props']['history']['data']);
    }

    public function test_reports_record_actual_attempt_instrument_and_reject_unseen_questions(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id, 'question_sequence' => [$question->id], 'sound' => 'Upright Piano']);
        $this->actingAs($user)->from('/quiz')->post(route('quiz.report', $quiz), ['question_id' => $question->id, 'instrument' => 'Piano', 'reason' => 'Audio does not play', 'message' => 'Silent playback'])->assertRedirect('/quiz');
        $this->assertDatabaseHas('question_reports', ['user_id' => $user->id, 'question_id' => $question->id, 'instrument' => 'Upright Piano', 'status' => 'open']);
        $other = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => 2]);
        $this->post(route('quiz.report', $quiz), ['question_id' => $other->id, 'reason' => 'Audio does not play'])->assertNotFound();
        $this->post(route('quiz.report', $quiz), ['question_id' => $question->id, 'reason' => 'invalid'])->assertSessionHasErrors('reason');
    }

    public function test_archived_reports_use_saved_question_and_sound_and_are_owner_only(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $session = QuizSession::create(['user_id' => $user->id, 'slug' => $quiz->slug, 'correct' => 0, 'total' => 1, 'played_at' => now(), 'sound' => 'Violin', 'review_answers' => [['question_id' => 999, 'prompt' => 'Original question']]]);
        $payload = ['session_id' => $session->id, 'question_id' => 999, 'reason' => 'Instructions are unclear'];
        $this->actingAs($user)->post(route('quiz.report', $quiz), $payload)->assertRedirect();
        $this->assertDatabaseHas('question_reports', ['question_prompt' => 'Original question', 'instrument' => 'Violin']);
        $this->actingAs(User::factory()->create())->post(route('quiz.report', $quiz), $payload)->assertNotFound();
    }

    public function test_admin_reports_include_member_identity_and_filter_without_losing_pagination(): void
    {
        $member = User::factory()->create(['name' => 'Reporting Member', 'email' => 'reporter@example.com']);
        $other = User::factory()->create();
        foreach (range(1, 26) as $index) {
            QuestionReport::create(['user_id' => $member->id, 'quiz_id' => 1, 'question_id' => $index,
                'quiz_title' => 'Find the Note', 'question_prompt' => 'Identify the note',
                'reason' => 'Audio does not play', 'instrument' => 'Violin']);
        }
        QuestionReport::create(['user_id' => $other->id, 'quiz_id' => 2, 'question_id' => 40,
            'quiz_title' => 'Intervals', 'question_prompt' => 'Identify the interval',
            'reason' => 'Instructions are unclear', 'status' => 'resolved']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $props = $this->get(route('admin.question-reports.index', ['search' => 'reporter@example.com', 'status' => 'open']))->assertOk()->viewData('page')['props'];
        $this->assertSame(26, $props['reports']['total']);
        $this->assertCount(25, $props['reports']['data']);
        $this->assertSame('Reporting Member', $props['reports']['data'][0]['user']['name']);
        $this->assertSame('reporter@example.com', $props['reports']['data'][0]['user']['email']);
        $this->assertArrayNotHasKey('password', $props['reports']['data'][0]['user']);
        $this->assertStringContainsString('status=open', $props['reports']['next_page_url']);
        $this->assertSame(['all' => 27, 'open' => 26, 'resolved' => 1], $props['counts']);
        $resolved = $this->get(route('admin.question-reports.index', ['status' => 'resolved']))->assertOk()->viewData('page')['props'];
        $this->assertSame(1, $resolved['reports']['total']);
        $this->assertSame('Intervals', $resolved['reports']['data'][0]['quiz_title']);
        $missing = $this->get(route('admin.question-reports.index', ['search' => 'no-such-member']))->assertOk()->viewData('page')['props'];
        $this->assertSame(0, $missing['reports']['total']);
    }

    public function test_only_admin_can_read_and_resolve_reports(): void
    {
        $member = User::factory()->create();
        $report = QuestionReport::create(['user_id' => $member->id, 'quiz_id' => 1, 'question_id' => 1, 'quiz_title' => 'Quiz', 'question_prompt' => 'Question', 'reason' => 'Audio does not play']);
        $this->actingAs($member)->get(route('admin.question-reports.index'))->assertForbidden();
        $this->patch(route('admin.question-reports.update', $report), ['status' => 'resolved'])->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.question-reports.index'))->assertOk();
        $this->patch(route('admin.question-reports.update', $report), ['status' => 'resolved'])->assertRedirect();
        $this->assertSame('resolved', $report->fresh()->status);
    }
}
