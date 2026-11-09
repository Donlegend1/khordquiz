<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Support\NoteSample;
use App\Support\ReferenceAudioTracks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReferencePoolRebuildTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebuild_preserves_same_answer_in_different_registers_and_is_idempotent(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        Storage::disk('public')->put('notes/piano/do.wav', 'note');
        Storage::disk('public')->put('notes/piano/do-c4.wav', 'note');
        $quiz = Quiz::factory()->create(['title' => 'Basic Triads · Level 1', 'category' => 'Basic Triads', 'is_published' => true, 'question_count' => 25]);
        foreach (['notes/piano/do.wav', 'notes/piano/do-c4.wav', 'notes/piano/do.wav'] as $i => $path) {
            $question = $quiz->questions()->create(['position' => $i + 1, 'prompt' => 'Listen', 'answer' => 'Same label', 'choices' => ['Same label']]);
            $question->audioVariants()->create(['sound' => 'Piano', 'audio_path' => $path]);
        }
        $this->artisan('audio:rebuild-reference-pools', ['--category' => 'Basic Triads'])->assertSuccessful();
        $this->assertNull($quiz->fresh()->reference_audio_tracks);
        $this->artisan('audio:rebuild-reference-pools', ['--category' => 'Basic Triads', '--apply' => true])->assertSuccessful();
        $tracks = $quiz->fresh()->reference_audio_tracks;
        $this->assertCount(2, $tracks);
        $this->assertSame(['notes/piano/do.wav', 'notes/piano/do-c4.wav'], array_column($tracks, 'path'));
        $this->assertCount(2, (new ReferenceAudioTracks)->pool($tracks));
        $this->artisan('audio:rebuild-reference-pools', ['--category' => 'Basic Triads', '--apply' => true])->assertSuccessful();
        $this->assertSame($tracks, $quiz->fresh()->reference_audio_tracks);
        $this->assertSame(3, $quiz->questions()->count());
    }

    public function test_unplayable_question_stops_the_rebuild_without_overwriting_existing_references(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        $original = [['id' => 'old', 'label' => 'Old reference', 'sound' => 'Piano', 'path' => 'original.wav']];
        $quiz = Quiz::factory()->create(['title' => 'Basic Triads · Level 1', 'category' => 'Basic Triads', 'is_published' => true, 'question_count' => 25, 'reference_audio_tracks' => $original]);
        $question = $quiz->questions()->create(['position' => 1, 'prompt' => 'Listen', 'answer' => 'Major', 'choices' => ['Major']]);
        $question->audioVariants()->create(['sound' => 'Piano', 'audio_path' => 'missing.wav']);
        $this->artisan('audio:rebuild-reference-pools', ['--category' => 'Basic Triads', '--apply' => true])->assertFailed();
        $this->assertSame($original, $quiz->fresh()->reference_audio_tracks);
        $this->assertSame(1, $quiz->questions()->count());
    }
}
