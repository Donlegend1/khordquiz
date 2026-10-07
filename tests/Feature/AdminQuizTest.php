<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_member_quiz_and_streak_stats(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create([
            'current_streak' => 4,
            'last_practiced_on' => now()->toDateString(),
        ]);
        Quiz::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('auth.user.role', 'admin')
                ->where('stats.members', 3)
                ->where('stats.quizzes', 2)
                ->where('stats.publishedQuizzes', 2)
                ->where('stats.activeStreaks', 3)
                ->has('recentStreaks', 3)
                ->has('quizzes', 2));
    }

    public function test_members_cannot_manage_quizzes(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->get(route('admin.quizzes.create'))
            ->assertForbidden();

        $this->actingAs($member)
            ->post(route('admin.quizzes.store'), [
                'title' => 'Find the Note',
                'category' => 'Relative Pitch',
                'difficulty' => 'Beginner',
                'description' => 'Name the note you hear.',
                'question_count' => 25,
                'is_published' => true,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_add_a_quiz(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.quizzes.store'), [
                'title' => 'Find the Note',
                'category' => 'Relative Pitch',
                'difficulty' => 'Beginner',
                'description' => 'Name the note you hear.',
                'question_count' => 25,
                'is_published' => true,
            ])
            ->assertRedirect(route('admin.quizzes.index'))
            ->assertSessionHas('success', 'Quiz added.');

        $this->actingAs($admin)
            ->get(route('admin.quizzes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('flash.success', 'Quiz added.'));

        $this->assertDatabaseHas('quizzes', [
            'title' => 'Find the Note',
            'slug' => 'find-the-note',
            'created_by' => $admin->id,
        ]);
    }

    public function test_a_second_quiz_with_the_same_title_gets_its_own_slug(): void
    {
        $admin = User::factory()->admin()->create();
        Quiz::factory()->create([
            'title' => 'Find the Note',
            'slug' => 'find-the-note',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.quizzes.store'), [
                'title' => 'Find the Note',
                'category' => 'Relative Pitch',
                'difficulty' => 'Beginner',
                'question_count' => 25,
                'is_published' => true,
            ])
            ->assertRedirect(route('admin.quizzes.index'));

        $this->assertDatabaseHas('quizzes', ['slug' => 'find-the-note-2']);
    }

    public function test_quiz_title_is_required(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.quizzes.create'))
            ->post(route('admin.quizzes.store'), [
                'title' => '',
                'category' => 'Intervals',
                'difficulty' => 'Beginner',
                'question_count' => 25,
                'is_published' => true,
            ])
            ->assertRedirect(route('admin.quizzes.create'))
            ->assertSessionHasErrors('title');
    }

    public function test_admin_can_update_and_delete_a_quiz(): void
    {
        $admin = User::factory()->admin()->create();
        $quiz = Quiz::factory()->create(['title' => 'Cadences']);
        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.quizzes.update', $quiz), [
                'title' => 'Cadences Revised',
                'category' => 'Chord Progressions',
                'difficulty' => 'Intermediate',
                'description' => 'Hear the cadence.',
                'question_count' => 20,
                'is_published' => false,
            ])
            ->assertRedirect(route('admin.quizzes.index'));

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'title' => 'Cadences Revised',
            'is_published' => false,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.quizzes.destroy', $quiz))
            ->assertRedirect();

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $attempt->id]);
    }
}
