<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Models\User;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionsCategorySplitTest extends TestCase
{
    use RefreshDatabase;

    public function test_split_moves_existing_and_future_levels_without_changing_content_or_progress(): void
    {
        $titles = [
            'Chord Tone Recognition #6' => 'Chord Tone Recognition',
            'Major Chord Extentions · Level 3' => 'Major Chord Extensions',
            'Major Chord Extentions · Level 4' => 'Major Chord Extensions',
            'Minor Chord Extentions' => 'Minor Chord Extensions',
            'Dominant Chord Extentions' => 'Dominant Chord Extensions',
            'Chord naming' => 'Chord Naming',
            'Custom extension lesson' => 'Extensions',
        ];
        $rows = [];
        foreach ($titles as $title => $category) {
            $quiz = Quiz::factory()->create(['category' => 'Extensions', 'title' => $title]);
            $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
            $audio = QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => 'Piano', 'audio_path' => 'custom/chord.wav']);
            $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id]);
            $rows[] = [$quiz, $category, $quiz->fresh()->getAttributes(), $question->fresh()->getAttributes(), $audio->fresh()->getAttributes(), $attempt->fresh()->getAttributes()];
        }
        ob_start();
        try {
            require base_path('scripts/audio/split_extensions_categories.php');
            require base_path('scripts/audio/split_extensions_categories.php');
        } finally {
            ob_end_clean();
        }
        foreach ($rows as [$quiz, $category, $original, $question, $audio, $attempt]) {
            $this->assertSame([...$original, 'category' => $category], $quiz->fresh()->getAttributes());
            $this->assertSame($question, QuizQuestion::findOrFail($question['id'])->getAttributes());
            $this->assertSame($audio, QuizQuestionAudio::findOrFail($audio['id'])->getAttributes());
            $this->assertSame($attempt, QuizAttempt::findOrFail($attempt['id'])->getAttributes());
        }
    }

    public function test_new_categories_have_the_expected_titles_and_instruments(): void
    {
        $expected = [
            'Chord Tone Recognition' => 6,
            'Major Chord Extensions' => 13,
            'Minor Chord Extensions' => 13,
            'Dominant Chord Extensions' => 8,
            'Chord Naming' => 6,
        ];
        foreach ($expected as $category => $count) {
            $this->assertContains($category, Quiz::CATEGORIES);
            $this->assertCount($count, QuizCatalog::subcategories($category));
            $this->assertSame(QuizCatalog::soundsFor('Dominant Chord Extensions', 'Chord naming'), QuizCatalog::soundsFor($category, QuizCatalog::subcategories($category)[0]));
        }
        $this->assertSame([], QuizCatalog::subcategories('Extensions'));
        $this->assertNotContains('Extensions', Quiz::CATEGORIES);
        $this->assertContains('Chord Naming · Level 1', QuizCatalog::subcategories('Chord Naming'));
        $this->assertNotContains('Chord naming', QuizCatalog::subcategories('Dominant Chord Extensions'));
        $this->assertSame(['1', '3', '5', '7'], QuizAnswerPresets::presetFor('Chord Tone Recognition', 'Chord Tone Recognition #1'));
    }

    public function test_admin_can_open_each_new_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (['chord-tone-recognition', 'major-chord-extensions', 'minor-chord-extensions', 'dominant-chord-extensions', 'chord-naming'] as $slug) {
            $this->get(route('admin.audio-quiz.show', $slug))->assertOk();
        }
    }
}
