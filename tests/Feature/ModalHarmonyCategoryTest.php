<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Support\MobileCatalog;
use App\Support\ModalHarmonySamples;
use App\Support\ModalVoicingLessons;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModalHarmonyCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_precedes_modal_voicings_in_web_mobile_and_learning_path(): void
    {
        foreach ([Quiz::CATEGORIES, array_keys(QuizCatalog::all()), MobileCatalog::categories(), MobileCatalog::PATHS['Advanced']] as $categories) {
            $index = array_search('Modal Voicings', $categories, true);
            $this->assertIsInt($index);
            $this->assertSame('Modal Harmony', $categories[$index - 1]);
        }
        $this->assertSame(ModalHarmonySamples::titles(), QuizCatalog::subcategories('Modal Harmony'));
        $this->assertSame(ModalHarmonySamples::titles(), array_column(MobileCatalog::quizzes('Modal Harmony'), 'name'));
        $this->assertSame(ModalVoicingLessons::titles(), QuizCatalog::subcategories('Modal Voicings'));
    }

    public function test_draft_creation_is_repeatable_and_keeps_existing_modal_content(): void
    {
        $existing = Quiz::factory()->create(['category' => 'Modal Voicings', 'title' => 'Lydian Mode #1']);
        $existing->questions()->create(['position' => 1, 'prompt' => 'Keep audio', 'choices' => ['Lydian'], 'answer' => 'Lydian']);
        $before = $existing->fresh()->load('questions')->toJson();
        ob_start();
        try {
            require base_path('scripts/audio/create_modal_harmony.php');
            require base_path('scripts/audio/create_modal_harmony.php');
        } finally {
            ob_end_clean();
        }
        $quiz = Quiz::where('category', 'Modal Harmony')->sole();
        $this->assertSame('Major Triads over Bass', $quiz->title);
        $this->assertSame('Advanced', $quiz->difficulty);
        $this->assertFalse($quiz->is_published);
        $this->assertSame(0, $quiz->questions()->count());
        $this->assertSame($before, $existing->fresh()->load('questions')->toJson());
        $saved = $quiz->getAttributes();
        ob_start();
        try {
            require base_path('scripts/audio/create_modal_harmony.php');
        } finally {
            ob_end_clean();
        }
        $this->assertSame($saved, $quiz->fresh()->getAttributes());
        $this->assertDatabaseCount('quizzes', 2);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.audio-quiz.lesson', ['modal-harmony', 'major-triads-over-bass']))->assertOk();
        $page = $this->actingAs(User::factory()->create())->get(route('quiz.category', ['modal-harmony']))->assertOk()->viewData('page')['props'];
        $this->assertCount(8, $page['quizzes']);
        $this->assertFalse($page['quizzes'][0]['available']);
        $this->post(route('quiz.start', $quiz))->assertNotFound();
        $this->assertSame(0, $quiz->attempts()->count());
    }
}
