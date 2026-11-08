<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MelodicDictationTonicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // NoteSample memoizes file-existence lookups per instrument in a
        // static property (see NoteSample::instrumentFiles()) — without
        // resetting it, a later test's fresh/empty fake disk still sees an
        // earlier test's cached "this instrument has files" result.
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
    }

    private function recordDo(string $slug): void
    {
        foreach (range(1, 6) as $octave) {
            Storage::disk('public')->put(NoteSample::path($slug, 'Do', $octave), 'note');
        }
    }

    public function test_melodic_dictation_question_carries_a_playable_tonic_note(): void
    {
        Storage::fake('public');
        $this->recordDo('piano');

        $quiz = Quiz::factory()->create(['category' => 'Melodic Dictation', 'title' => '3-Note Stepwise', 'question_count' => 1, 'is_published' => true]);
        $question = $quiz->questions()->create(['position' => 1, 'answer' => 'Do Re Mi', 'choices' => ['Do', 'Re', 'Mi'], 'prompt' => 'Listen to the melody.']);
        $user = User::factory()->create();
        QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'sound' => 'Piano', 'status' => QuizAttempt::STATUS_IN_PROGRESS, 'stopped_at_question' => 1, 'question_sequence' => [$question->id]]);

        $props = $this->actingAs($user)->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];

        $tonic = $props['question']['tonic_notes'];
        $this->assertCount(1, $tonic);
        $this->assertSame(0.0, $tonic[0]['offset']);
        $this->assertStringContainsString('/notes/piano/', $tonic[0]['url']);
    }

    public function test_tonic_is_absent_from_find_the_note(): void
    {
        Storage::fake('public');
        $this->recordDo('piano');

        $quiz = Quiz::factory()->create(['category' => 'Relative Pitch', 'title' => 'Find the Note', 'question_count' => 1, 'is_published' => true]);
        $question = $quiz->questions()->create(['position' => 1, 'answer' => 'Do', 'choices' => ['Do', 'Re'], 'prompt' => 'Listen to the note.']);
        $user = User::factory()->create();
        QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'sound' => 'Piano', 'status' => QuizAttempt::STATUS_IN_PROGRESS, 'stopped_at_question' => 1, 'question_sequence' => [$question->id]]);

        $props = $this->actingAs($user)->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];

        $this->assertNull($props['question']['tonic_notes']);
    }

    public function test_tonic_is_null_when_the_instrument_has_no_recorded_do(): void
    {
        Storage::fake('public');
        // No files recorded at all — nothing to resolve "Do" from.

        $quiz = Quiz::factory()->create(['category' => 'Melodic Dictation', 'title' => '3-Note Stepwise', 'question_count' => 1, 'is_published' => true]);
        $question = $quiz->questions()->create(['position' => 1, 'answer' => 'Do Re Mi', 'choices' => ['Do', 'Re', 'Mi'], 'prompt' => 'Listen to the melody.']);
        $user = User::factory()->create();
        QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'sound' => 'Piano', 'status' => QuizAttempt::STATUS_IN_PROGRESS, 'stopped_at_question' => 1, 'question_sequence' => [$question->id]]);

        $props = $this->actingAs($user)->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];

        $this->assertNull($props['question']['tonic_notes']);
    }

    public function test_relative_tone_quizzes_use_a_fixed_do_in_the_selected_instrument_register(): void
    {
        Storage::fake('public');
        $this->recordDo('upright-piano');
        $user = User::factory()->create(['default_instrument' => 'Piano']);
        foreach (['Relative Tone Pitch #1', 'Relative Tone Pitch #2'] as $title) {
            $quiz = Quiz::factory()->create(['category' => 'Relative Pitch', 'title' => $title, 'question_count' => 2]);
            $first = $quiz->questions()->create(['position' => 1, 'answer' => 'Re', 'choices' => ['Do', 'Re'], 'prompt' => 'Listen']);
            $second = $quiz->questions()->create(['position' => 2, 'answer' => 'Mi', 'choices' => ['Do', 'Mi'], 'prompt' => 'Listen']);
            $attempt = QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id, 'sound' => 'Upright Piano', 'question_sequence' => [$first->id, $second->id]]);
            $firstProps = $this->actingAs($user)->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
            $attempt->update(['stopped_at_question' => 2]);
            $secondProps = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
            $tonic = $firstProps['question']['tonic_notes'];
            $this->assertSame($tonic, $secondProps['question']['tonic_notes']);
            $this->assertCount(1, $tonic);
            $expected = NoteSample::fromAnswer('Do', 'melodic', 'upright-piano')[0];
            $this->assertSame(NoteSample::url(NoteSample::path('upright-piano', 'Do', $expected['octave'])), $tonic[0]['url']);
            $this->assertSame(0.0, $tonic[0]['offset']);
        }
    }
}
