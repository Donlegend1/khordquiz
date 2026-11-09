<?php

namespace Tests\Feature;

use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicQuizCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_uses_published_quizzes_and_counts_categories_once_across_difficulties(): void
    {
        Quiz::factory()->create(['title' => 'Relative Tone Pitch #1', 'category' => 'Relative Pitch']);
        Quiz::factory()->create(['title' => 'Find the Note', 'category' => 'Relative Pitch']);
        $pitch = Quiz::factory()->create(['title' => 'Find the Pitch', 'category' => 'Relative Pitch', 'difficulty' => 'Advanced']);
        Quiz::factory()->create(['title' => 'Dyad Melodies', 'category' => 'Melodic Dictation']);
        Quiz::factory()->create(['title' => 'Chordal Bass Lines', 'category' => 'Melodic Dictation']);
        Quiz::factory()->create(['is_published' => false]);
        Quiz::factory()->create(['question_count' => 0]);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('catalog.totals.quizzes', 5)
            ->where('catalog.totals.categories', 2)
            ->has('catalog.paths', 3)
            ->where('catalog.paths.0.categories.0.quizzes.0', 'Find the Note')
            ->where('catalog.paths.0.categories.0.quizzes.1', 'Relative Tone Pitch #1')
            ->where('catalog.paths.0.categories.1.quizzes.0', 'Dyad Melodies')
            ->where('catalog.paths.0.categories.1.quizzes.1', 'Chordal Bass Lines')
            ->has('catalog.paths.1.categories', 0)
            ->where('catalog.paths.2.categories.0.quizzes.0', 'Find the Pitch'));

        $pitch->update(['difficulty' => 'Intermediate']);
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('catalog.paths.1.categories.0.quizzes.0', 'Find the Pitch')
            ->has('catalog.paths.2.categories', 0)
            ->where('catalog.totals.quizzes', 5));
    }
}
