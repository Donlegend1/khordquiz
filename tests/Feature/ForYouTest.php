<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizSession;
use App\Models\User;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ForYouTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_learning_paths(): void
    {
        $this->get(route('for-you'))->assertRedirect(route('login'));
    }

    public function test_published_lessons_follow_catalog_order_and_show_the_next_nine(): void
    {
        $member = User::factory()->create();
        $titles = array_slice(QuizCatalog::subcategories('Relative Pitch'), 0, 12);
        foreach (array_reverse($titles) as $title) {
            Quiz::factory()->create(['title' => $title, 'slug' => Str::slug($title), 'category' => 'Relative Pitch']);
        }
        Quiz::factory()->create(['is_published' => false]);
        Quiz::factory()->create(['question_count' => 0]);
        $intermediate = Quiz::factory()->create(['difficulty' => 'Intermediate']);
        $advanced = Quiz::factory()->create(['difficulty' => 'Advanced']);
        $first = Quiz::query()->where('title', $titles[0])->firstOrFail();

        $this->actingAs($member)->get(route('for-you'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Member/ForYou')
                ->where('paths.Beginner.total', 12)
                ->where('paths.Beginner.done', 0)
                ->where('paths.Beginner.upNext.slug', $first->slug)
                ->where('paths.Beginner.upNext.categorySlug', 'relative-pitch')
                ->where('paths.Beginner.upNext.position', 1)
                ->has('paths.Beginner.comingUp', 9)
                ->where('paths.Beginner.comingUp.0.title', $titles[1])
                ->where('paths.Beginner.comingUp.0.position', 2)
                ->where('paths.Beginner.comingUp.8.title', $titles[9])
                ->where('paths.Beginner.comingUp.8.position', 10)
                ->where('paths.Intermediate.upNext.slug', $intermediate->slug)
                ->where('paths.Advanced.upNext.slug', $advanced->slug));
        $lessonUrl = route('quiz.category', ['category' => 'relative-pitch', 'quiz' => $first->slug]);
        $this->get(route('quiz.preview', $first))->assertRedirect($lessonUrl);
        $this->get($lessonUrl)->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/QuizCategory')
                ->where('selectedSlug', $first->slug));
    }

    public function test_path_advances_past_completed_lessons_for_this_member_only(): void
    {
        $member = User::factory()->create();
        $first = Quiz::factory()->create(['title' => 'Find the Note', 'category' => 'Relative Pitch']);
        $second = Quiz::factory()->create(['title' => 'Find the Pitch', 'category' => 'Relative Pitch']);
        $third = Quiz::factory()->create(['title' => 'Relative Tone Pitch #1', 'category' => 'Relative Pitch']);
        QuizAttempt::factory()->create(['user_id' => $member->id, 'quiz_id' => $first->id, 'status' => QuizAttempt::STATUS_COMPLETED]);
        QuizAttempt::factory()->create(['quiz_id' => $second->id, 'status' => QuizAttempt::STATUS_COMPLETED]);

        $this->actingAs($member)->get(route('for-you'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('paths.Beginner.done', 1)
                ->where('paths.Beginner.total', 3)
                ->where('paths.Beginner.upNext.slug', $second->slug)
                ->where('paths.Beginner.upNext.position', 2)
                ->where('paths.Beginner.comingUp.0.slug', $third->slug)
                ->where('paths.Beginner.comingUp.0.position', 3)
                ->where('paths.Advanced.upNext', null));
    }

    public function test_completed_path_has_no_next_lesson(): void
    {
        $member = User::factory()->create();
        $quiz = Quiz::factory()->create();
        QuizAttempt::factory()->create(['user_id' => $member->id, 'quiz_id' => $quiz->id, 'status' => QuizAttempt::STATUS_COMPLETED]);

        $this->actingAs($member)->get(route('for-you'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('paths.Beginner.done', 1)
                ->where('paths.Beginner.total', 1)
                ->where('paths.Beginner.upNext', null)
                ->has('paths.Beginner.comingUp', 0));
    }

    public function test_finishing_the_up_next_quiz_promotes_the_next_lesson_and_refills_the_list(): void
    {
        $member = User::factory()->create();
        $titles = array_slice(QuizCatalog::subcategories('Relative Pitch'), 0, 12);
        $lessons = collect($titles)->map(fn (string $title) => Quiz::factory()->create([
            'title' => $title,
            'slug' => Str::slug($title),
            'category' => 'Relative Pitch',
        ]));
        $first = $lessons->first();
        $question = QuizQuestion::factory()->create(['quiz_id' => $first->id, 'position' => 25]);
        QuizAttempt::factory()->create([
            'user_id' => $member->id,
            'quiz_id' => $first->id,
            'stopped_at_question' => 25,
            'question_sequence' => array_fill(0, 25, $question->id),
        ]);

        $this->actingAs($member)->get(route('for-you'))
            ->assertInertia(fn (Assert $page) => $page->where('paths.Beginner.upNext.slug', $first->slug));
        $this->post(route('quiz.answer', $first), ['choice' => $question->answer])
            ->assertRedirect(route('quiz.play', $first));
        $this->post(route('quiz.next', $first))->assertRedirect(route('quiz.results', $first));

        $this->get(route('for-you'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('paths.Beginner.done', 1)
                ->where('paths.Beginner.upNext.slug', $lessons[1]->slug)
                ->where('paths.Beginner.upNext.position', 2)
                ->has('paths.Beginner.comingUp', 9)
                ->where('paths.Beginner.comingUp.0.slug', $lessons[2]->slug)
                ->where('paths.Beginner.comingUp.0.position', 3)
                ->where('paths.Beginner.comingUp.8.slug', $lessons[10]->slug)
                ->where('paths.Beginner.comingUp.8.position', 11));
    }

    public function test_retrying_a_finished_quiz_does_not_put_it_back_in_the_learning_path_even_with_zero_score(): void
    {
        $member = User::factory()->create();
        $first = Quiz::factory()->create(['title' => 'Find the Note', 'slug' => 'find-the-note', 'category' => 'Relative Pitch']);
        $second = Quiz::factory()->create(['title' => 'Find the Pitch', 'slug' => 'find-the-pitch', 'category' => 'Relative Pitch']);
        QuizAttempt::factory()->create([
            'user_id' => $member->id,
            'quiz_id' => $first->id,
            'status' => QuizAttempt::STATUS_COMPLETED,
            'stopped_at_question' => 26,
            'correct_answers' => 0,
            'completed_at' => now()->subDay(),
        ]);

        $this->actingAs($member)->post(route('quiz.start', $first))->assertRedirect(route('quiz.play', $first));
        $this->assertDatabaseHas('quiz_attempts', ['user_id' => $member->id, 'quiz_id' => $first->id, 'status' => QuizAttempt::STATUS_IN_PROGRESS]);
        $this->assertDatabaseHas('quiz_sessions', ['user_id' => $member->id, 'slug' => $first->slug, 'total' => 25, 'correct' => 0]);

        $this->get(route('for-you'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('paths.Beginner.done', 1)
                ->where('paths.Beginner.upNext.slug', $second->slug)
                ->has('paths.Beginner.comingUp', 0));
    }

    public function test_other_members_completed_sessions_do_not_change_the_path(): void
    {
        $member = User::factory()->create();
        $quiz = Quiz::factory()->create();
        QuizSession::query()->create([
            'user_id' => User::factory()->create()->id,
            'slug' => $quiz->slug,
            'total' => 25,
            'correct' => 25,
            'played_at' => now(),
        ]);

        $this->actingAs($member)->get(route('for-you'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('paths.Beginner.done', 0)
                ->where('paths.Beginner.upNext.slug', $quiz->slug));
    }

    public function test_old_preview_urls_redirect_to_the_selected_lesson_for_every_category(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (Quiz::CATEGORIES as $category) {
            $quiz = Quiz::factory()->create(['category' => $category]);
            $this->get(route('quiz.preview', $quiz))->assertRedirect(route('quiz.category', [
                'category' => Str::slug($category),
                'quiz' => $quiz->slug,
            ]));
        }

        $hidden = Quiz::factory()->create(['is_published' => false]);
        $this->get(route('quiz.preview', $hidden))->assertNotFound();
    }
}
