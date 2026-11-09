<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecordedVoicingRangeCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_removes_only_the_out_of_range_instrument_and_preserves_missing_note_cases(): void
    {
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        foreach (['piano' => [24, 107], 'cello' => [36, 70], 'celesta' => [48, 95]] as $slug => [$low, $high]) {
            foreach (range($low, $high) as $midi) {
                $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
                if ($slug === 'celesta' && str_contains($label, '#')) {
                    continue;
                }
                $octave = intdiv($midi, 12) - 1 - ($slug === 'cello' && str_contains($label, '#') ? 1 : 0);
                Storage::disk('public')->put(NoteSample::path($slug, NoteSample::equivalentDegree($label) ?? $label, $octave), 'sample');
            }
        }
        $quiz = Quiz::factory()->create(['category' => '9th Degree Chords', 'title' => '9th Degree Chords · Level 4']);
        $question = $quiz->questions()->create(['position' => 1, 'answer' => 'Major 9th', 'choices' => ['Major 9th'], 'prompt' => 'Identify the chord.']);
        $fixturePaths = [];
        foreach (['Piano' => 'piano', 'Cello' => 'cello', 'Celesta' => 'celesta'] as $sound => $slug) {
            $path = 'ninth-degree-chords/level4/'.$slug.'/a-major9-3rd.wav';
            $fixturePaths[$slug] = $path;
            $question->audioVariants()->create(['sound' => $sound, 'audio_path' => $path]);
            Storage::disk('public')->put($path, 'rendered chord');
        }
        $missing = $quiz->questions()->create(['position' => 2, 'answer' => 'Major 9th', 'choices' => ['Major 9th'], 'prompt' => 'Identify the chord.']);
        $missing->audioVariants()->create(['sound' => 'Celesta', 'audio_path' => 'ninth-degree-chords/level4/celesta/db-major9-root.wav']);
        $missing->audioVariants()->create(['sound' => 'Piano', 'audio_path' => 'ninth-degree-chords/level4/piano/db-major9-root.wav']);
        $quiz->update(['reference_audio_tracks' => [['id' => 'cello-example', 'label' => 'Major 9th', 'sound' => 'Cello', 'path' => $fixturePaths['cello']]]]);
        $celloAttempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'sound' => 'Cello', 'question_sequence' => [$question->id]]);
        $pianoAttempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'sound' => 'Piano', 'question_sequence' => [$question->id]]);
        $_SERVER['argv'][] = '--apply';
        $_SERVER['argv'][] = '--include-unavailable-live';
        ob_start();
        try {
            require base_path('scripts/audio/prune_out_of_range_voicings.php');
        } finally {
            ob_end_clean();
            array_pop($_SERVER['argv']);
            array_pop($_SERVER['argv']);
        }
        $this->assertSame(['Celesta', 'Piano'], $question->audioVariants()->orderBy('sound')->pluck('sound')->all());
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $celloAttempt->id]);
        $this->assertDatabaseHas('quiz_attempts', ['id' => $pianoAttempt->id]);
        $this->assertDatabaseHas('quiz_questions', ['id' => $question->id]);
        $this->assertSame([], $quiz->fresh()->reference_audio_tracks);
        $this->assertSame(['Piano'], $missing->audioVariants()->pluck('sound')->all());
        Storage::disk('public')->assertMissing($fixturePaths['cello']);
        Storage::disk('public')->assertExists($fixturePaths['piano']);
        Storage::disk('public')->assertExists($fixturePaths['celesta']);
        Storage::disk('public')->assertExists('notes/cello/do.wav');
    }
}
