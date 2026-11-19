<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SavedQuizzesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_bookmarked_quiz_links_straight_to_that_quiz(): void
    {
        $member = User::factory()->create();
        $title = QuizCatalog::subcategories('Relative Pitch')[0];
        $quiz = Quiz::factory()->create(['title' => $title, 'slug' => Str::slug($title), 'category' => 'Relative Pitch']);

        $this->actingAs($member)->post(route('quiz.bookmark', $quiz->slug));

        $this->actingAs($member)->get(route('saved'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Member/Saved')
                ->where('saved.0.slug', $quiz->slug)
                ->where('saved.0.categorySlug', 'relative-pitch')
            );

        $this->actingAs($member)->get(route('quiz.category', ['category' => 'relative-pitch', 'quiz' => $quiz->slug]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('selectedSlug', $quiz->slug));
    }
}
