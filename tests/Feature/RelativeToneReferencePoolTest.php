<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Support\NoteSample;
use App\Support\ReferenceAudioTracks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RelativeToneReferencePoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_rtp_one_uses_the_quiz_engine_register_for_each_instrument_and_keeps_seven_choices(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $quiz = Quiz::factory()->create(['title' => 'Relative Tone Pitch #1', 'category' => 'Relative Pitch']);
        foreach (['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti'] as $index => $degree) {
            $quiz->questions()->create(['position' => $index + 1, 'prompt' => 'Listen', 'answer' => $degree, 'choices' => [$degree]]);
            foreach (['piano' => 4, 'upright-piano' => 4, 'bass-guitar' => 2] as $slug => $octave) {
                Storage::disk('public')->put(NoteSample::path($slug, $degree, $octave), 'note');
            }
        }
        $this->artisan('audio:rebuild-relative-tone-references', ['quiz' => '1'])->assertSuccessful();
        $this->assertNull($quiz->fresh()->reference_audio_tracks);
        $this->artisan('audio:rebuild-relative-tone-references', ['quiz' => '1', '--apply' => true])->assertSuccessful();
        $tracks = $quiz->fresh()->reference_audio_tracks;
        $pool = (new ReferenceAudioTracks)->pool($tracks);
        $this->assertCount(7, $pool);
        $this->assertCount(21, $tracks);
        $this->actingAs(User::factory()->create(['default_instrument' => 'Upright Piano']));
        foreach (['Piano' => 'piano/do-c4.wav', 'Upright Piano' => 'upright-piano/do-c4.wav', 'Bass Guitar' => 'bass-guitar/do-c2.wav'] as $sound => $file) {
            $response = $this->getJson(route('quiz.reference-audio-clip', [$quiz, $pool[0]['id']]).'?sound='.urlencode($sound))->assertOk();
            $notes = $response->json('clip.notes');
            $this->assertCount(1, $notes);
            $this->assertStringContainsString('/notes/'.$file, $notes[0]['url']);
            $this->assertSame(0, $notes[0]['offset']);
        }
        $this->artisan('audio:rebuild-relative-tone-references', ['quiz' => '1', '--apply' => true])->assertSuccessful();
        $this->assertSame($tracks, $quiz->fresh()->reference_audio_tracks);
        $this->assertSame(7, $quiz->questions()->count());
    }

    public function test_rtp_two_retains_same_label_registers_and_exact_attached_instrument_paths(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $quiz = Quiz::factory()->create(['title' => 'Relative Tone Pitch #2', 'category' => 'Relative Pitch']);
        foreach ([2, 5] as $position => $octave) {
            $question = $quiz->questions()->create(['position' => $position + 1, 'prompt' => 'Listen', 'answer' => 'Do', 'choices' => ['Do']]);
            foreach (['Piano' => ['piano', $octave], 'Organ' => ['organ', $octave + 1]] as $sound => [$slug, $register]) {
                $path = NoteSample::path($slug, 'Do', $register);
                Storage::disk('public')->put($path, 'note');
                $question->audioVariants()->create(['sound' => $sound, 'audio_path' => $path]);
            }
            if ($octave === 2) {
                $path = NoteSample::path('upright-piano', 'Do', 2);
                Storage::disk('public')->put($path, 'note');
                $question->audioVariants()->create(['sound' => 'Upright Piano', 'audio_path' => $path]);
            }
        }
        // A file alone must not invent an unattached RTP2 question/register.
        Storage::disk('public')->put(NoteSample::path('upright-piano', 'Do', 5), 'note');
        $this->artisan('audio:rebuild-relative-tone-references', ['quiz' => '2', '--apply' => true])->assertSuccessful();
        $tracks = $quiz->fresh()->reference_audio_tracks;
        $references = new ReferenceAudioTracks;
        $pool = $references->pool($tracks);
        $this->assertCount(2, $pool);
        $this->assertSame(['Do', 'Do'], array_column($pool, 'label'));
        foreach ($pool as $position => $clip) {
            $piano = $references->resolveOne($tracks, $clip['id'], 'Piano');
            $organ = $references->resolveOne($tracks, $clip['id'], 'Organ');
            $this->assertStringContainsString('/notes/piano/do-c'.[2, 5][$position].'.wav', $piano['notes'][0]['url']);
            $this->assertStringContainsString('/notes/organ/do'.($position === 0 ? '' : '-c6').'.wav', $organ['notes'][0]['url']);
        }
        $this->assertNotNull($references->resolveOne($tracks, $pool[0]['id'], 'Upright Piano'));
        $this->assertNull($references->resolveOne($tracks, $pool[1]['id'], 'Upright Piano'));
    }

    public function test_unplayable_questions_do_not_overwrite_old_references_or_other_quizzes(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $old = [['id' => 'old', 'label' => 'Scale', 'sound' => 'Piano', 'path' => 'reference-scales/piano.wav']];
        $quiz = Quiz::factory()->create(['title' => 'Relative Tone Pitch #2', 'category' => 'Relative Pitch', 'reference_audio_tracks' => $old]);
        $quiz->questions()->create(['position' => 1, 'answer' => 'Do', 'choices' => ['Do'], 'prompt' => 'Listen']);
        $other = Quiz::factory()->create(['title' => 'Find the Note', 'reference_audio_tracks' => $old]);
        $this->artisan('audio:rebuild-relative-tone-references', ['quiz' => '2', '--apply' => true])->assertFailed();
        $this->assertSame($old, $quiz->fresh()->reference_audio_tracks);
        $this->assertSame($old, $other->fresh()->reference_audio_tracks);
    }
}
