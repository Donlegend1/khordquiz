<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuizResultsReplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
    }

    public function test_live_mix_answers_replay_in_the_attempt_instrument_even_without_prerendered_audio(): void
    {
        Storage::disk('public')->put('notes/upright-piano/do-c4.wav', 'sample');
        Storage::disk('public')->put('notes/upright-piano/do.wav', 'sample');
        $quiz = Quiz::factory()->create(['category' => 'Relative Pitch', 'title' => 'Find the Note', 'question_count' => 2]);
        $question = $quiz->questions()->create(['position' => 1, 'answer' => 'C', 'choices' => ['C', 'D'], 'prompt' => 'Identify the note.']);
        $user = User::factory()->create(['default_instrument' => 'Piano']);
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'sound' => 'Upright Piano', 'status' => QuizAttempt::STATUS_COMPLETED, 'completed_at' => now()]);
        foreach ([true, false] as $index => $correct) {
            $attempt->answers()->create(['quiz_question_id' => $question->id, 'position' => $index + 1, 'picked' => $correct ? 'C' : 'D', 'correct' => $correct]);
        }
        $props = $this->actingAs($user)->get(route('quiz.results', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertCount(2, $props['review']);
        foreach ($props['review'] as $row) {
            $this->assertNull($row['audio_url']);
            $this->assertSame([['url' => NoteSample::url('notes/upright-piano/do-c4.wav'), 'offset' => 0.0]], $row['notes']);
        }
    }

    public function test_recorded_audio_remains_available_when_it_cannot_be_live_mixed(): void
    {
        $quiz = Quiz::factory()->create(['category' => 'Relative Pitch', 'title' => 'Find the Key #1', 'question_count' => 1]);
        $question = $quiz->questions()->create(['position' => 1, 'answer' => 'F', 'choices' => ['F'], 'prompt' => 'Identify the key.']);
        $question->audioVariants()->create(['sound' => 'Monophonic', 'audio_path' => 'songs/example.wav']);
        $user = User::factory()->create();
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'sound' => 'Monophonic', 'status' => QuizAttempt::STATUS_COMPLETED, 'completed_at' => now()]);
        $attempt->answers()->create(['quiz_question_id' => $question->id, 'position' => 1, 'picked' => 'F', 'correct' => true]);
        $row = $this->actingAs($user)->get(route('quiz.results', $quiz))->assertOk()->viewData('page')['props']['review'][0];
        $this->assertNull($row['notes']);
        $this->assertSame(NoteSample::url('songs/example.wav'), $row['audio_url']);
    }

    public function test_scale_review_preserves_the_full_sequence_and_timing(): void
    {
        foreach (range(3, 4) as $octave) {
            foreach (array_keys(NoteSample::CHROMATIC_SLUGS) as $label) {
                Storage::disk('public')->put(NoteSample::path('piano', NoteSample::equivalentDegree($label) ?? $label, $octave), 'sample');
            }
        }
        $quiz = Quiz::factory()->create(['category' => 'Scales', 'title' => 'Ionian Scales #1', 'question_count' => 1]);
        $question = $quiz->questions()->create(['position' => 1, 'answer' => 'Ionian', 'choices' => ['Ionian'], 'prompt' => 'Identify the scale.']);
        $question->audioVariants()->create(['sound' => 'Piano', 'audio_path' => 'scales/ionian-scales-c-v1/piano/c-ionian.wav']);
        $user = User::factory()->create();
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'sound' => 'Piano', 'status' => QuizAttempt::STATUS_COMPLETED, 'completed_at' => now()]);
        $attempt->answers()->create(['quiz_question_id' => $question->id, 'position' => 1, 'picked' => 'Ionian', 'correct' => true]);
        $notes = $this->actingAs($user)->get(route('quiz.results', $quiz))->assertOk()->viewData('page')['props']['review'][0]['notes'];
        $this->assertCount(15, $notes);
        $this->assertSame(0.0, $notes[0]['offset']);
        $this->assertSame(5.6, $notes[14]['offset']);
        $this->assertSame($notes[0]['url'], $notes[14]['url']);
    }

    public function test_pitch_review_includes_the_attempt_instruments_keyboard_and_original_question(): void
    {
        Storage::disk('public')->put('notes/upright-piano/do-c4.wav', 'C4 sample');
        $quiz = Quiz::factory()->create(['category' => 'Relative Pitch', 'title' => 'Find the Pitch', 'question_count' => 1]);
        $question = $quiz->questions()->create(['position' => 1, 'answer' => 'C4', 'choices' => ['C4', 'D4'], 'prompt' => 'Find the pitch you hear.']);
        $user = User::factory()->create();
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'sound' => 'Upright Piano', 'status' => QuizAttempt::STATUS_COMPLETED, 'completed_at' => now()]);
        $attempt->answers()->create(['quiz_question_id' => $question->id, 'position' => 1, 'picked' => 'D4', 'correct' => false]);
        $props = $this->actingAs($user)->get(route('quiz.results', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertSame('Find the pitch you hear.', $props['review'][0]['prompt']);
        $this->assertSame('D4', $props['review'][0]['picked']);
        $this->assertSame('C4', $props['review'][0]['answer']);
        $this->assertFalse($props['review'][0]['correct']);
        $this->assertSame($props['review'][0]['notes'][0]['url'], $props['review_keyboard_audio']['C4']);
        $this->assertSame(1, $attempt->answers()->count());
    }
}
