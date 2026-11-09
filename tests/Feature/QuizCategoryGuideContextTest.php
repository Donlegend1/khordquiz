<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizCategoryGuideContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_secondary_seventh_lessons_include_category_for_the_quick_guide_even_with_a_video(): void
    {
        foreach (['Secondary 7th Chords · Level 1', 'General'] as $title) {
            Quiz::factory()->create([
                'category' => 'Secondary 7th Chords',
                'title' => $title,
                'video_embed' => '<iframe src="https://example.com/old-video"></iframe>',
            ]);
        }

        $response = $this->actingAs(User::factory()->create())
            ->get(route('quiz.category', ['secondary-7th-chords']))
            ->assertOk();

        $quizzes = collect($response->viewData('page')['props']['quizzes']);
        $this->assertCount(9, $quizzes);
        $this->assertCount(2, $quizzes->where('available', true));
        $this->assertCount(7, $quizzes->where('available', false));
        $guides = json_decode(file_get_contents(resource_path('js/data/secondarySeventhGuides.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($quizzes as $quiz) {
            $this->assertSame('Secondary 7th Chords', $quiz['category']);
            $this->assertArrayHasKey($quiz['title'], $guides);
        }
    }

    public function test_lessons_without_an_uploaded_video_do_not_receive_the_old_generic_video(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Find the Note', 'category' => 'Relative Pitch', 'video_embed' => null]);
        $response = $this->actingAs(User::factory()->create())->get(route('quiz.category', ['relative-pitch']))->assertOk();
        $lessons = collect($response->viewData('page')['props']['quizzes']);
        $this->assertNull($lessons->firstWhere('slug', $quiz->slug)['video_embed']);
        $this->assertNull($lessons->firstWhere('title', 'Find the Pitch')['video_embed']);
        $embed = '<iframe title="Lesson video" src="https://example.com/lesson"></iframe>';
        $quiz->update(['video_embed' => $embed]);
        $response = $this->get(route('quiz.category', ['relative-pitch']))->assertOk();
        $lessons = collect($response->viewData('page')['props']['quizzes']);
        $this->assertSame($embed, $lessons->firstWhere('slug', $quiz->slug)['video_embed']);
    }
}
