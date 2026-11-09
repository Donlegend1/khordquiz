<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\MobileCatalog;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChordNamingCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_quiz_moves_without_changing_its_content_identifiers_or_progress(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Chord naming', 'category' => 'Dominant Chord Extensions']);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        $audio = QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => 'Piano', 'audio_path' => 'chord-naming/piano/major.wav']);
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'question_sequence' => [$question->id]]);
        $before = [$quiz->fresh()->getAttributes(), $question->fresh()->getAttributes(), $audio->fresh()->getAttributes(), $attempt->fresh()->getAttributes()];
        ob_start();
        try {
            require base_path('scripts/audio/move_chord_naming_category.php');
            require base_path('scripts/audio/move_chord_naming_category.php');
        } finally {
            ob_end_clean();
        }
        $this->assertSame([...$before[0], 'category' => 'Chord Naming'], $quiz->fresh()->getAttributes());
        $this->assertSame($before[1], $question->fresh()->getAttributes());
        $this->assertSame($before[2], $audio->fresh()->getAttributes());
        $this->assertSame($before[3], $attempt->fresh()->getAttributes());
        $this->assertDatabaseCount('quizzes', 1);
    }

    public function test_web_and_mobile_catalogs_show_chord_naming_as_a_separate_category(): void
    {
        $this->assertContains('Chord Naming', Quiz::CATEGORIES);
        $this->assertSame(['Chord Naming · Level 1', 'Chord Naming · Level 2', 'Chord Naming · Level 3', 'Chord Naming · Level 4', 'Chord Naming · Level 5', 'Chord Naming · Level 6'], QuizCatalog::subcategories('Chord Naming'));
        $this->assertCount(8, QuizCatalog::subcategories('Dominant Chord Extensions'));
        $this->assertNotContains('Chord naming', QuizCatalog::subcategories('Dominant Chord Extensions'));
        $this->assertSame(QuizCatalog::soundsFor('Dominant Chord Extensions', 'Dominant Chord Extentions · Level 1'), QuizCatalog::soundsFor('Chord Naming', 'Chord naming'));
        $this->assertContains('Chord Naming', MobileCatalog::categories());
        $mobile = MobileCatalog::quizzes('Chord Naming');
        $this->assertCount(6, $mobile);
        $this->assertSame('Chord Naming · Level 1', $mobile[0]['name']);
        $this->assertSame('Extentions recognition · Level 4', $mobile[0]['slug']);
        $this->assertSame('Chord Naming', MobileCatalog::find($mobile[0]['slug'])['category']);
        $this->assertNotContains('Extentions recognition · Level 4', MobileCatalog::subcategoryNames()['Extentions recognition']);
        $this->assertContains('Chord Naming', MobileCatalog::PATHS['Advanced']);
    }
}
