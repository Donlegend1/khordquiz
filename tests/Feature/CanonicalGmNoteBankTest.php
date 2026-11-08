<?php

namespace Tests\Feature;

use App\Support\NoteSample;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CanonicalGmNoteBankTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
    }

    public function test_completed_gm_banks_use_actual_midi_pitches_despite_legacy_filename_offsets(): void
    {
        foreach (['celesta', 'vibraphone', 'saxophone', 'voice'] as $slug) {
            Storage::disk('public')->put('notes/'.$slug.'/gm-bank.json', '{}');
            foreach ([48, 54, 66, 78] as $midi) {
                Storage::disk('public')->put('notes/'.$slug.'/midi-'.$midi.'.wav', 'sample');
            }
            Storage::disk('public')->put('notes/'.$slug.'/fsharp-c4.wav', 'legacy F sharp 5');
            $this->assertSame('notes/'.$slug.'/midi-66.wav', NoteSample::path($slug, 'F#/Gb', 4));
            $this->assertSame(['degree' => 'F#/Gb', 'octave' => 4, 'offset' => 0.0], NoteSample::fromRecordedMidiPitch(66, $slug));
            $this->assertSame([['degree' => 'F#/Gb', 'octave' => 4, 'offset' => 0.0]], NoteSample::fromScientificPitchAnswer('F#/Gb4', $slug));
            $this->assertSame([['slug' => $slug, 'degree' => 'F#/Gb', 'octave' => 4, 'offset' => 0.0]], NoteSample::resolve('notes/'.$slug.'/midi-66.wav'));
            $this->assertNull(NoteSample::fromRecordedMidiPitch(67, $slug));
            $this->assertSame('legacy F sharp 5', Storage::disk('public')->get('notes/'.$slug.'/fsharp-c4.wav'));
        }
        $this->assertSame('notes/piano/fsharp-c4.wav', NoteSample::path('piano', 'F#/Gb', 4));
    }

    public function test_extension_quiz_remains_playable_after_its_rendered_clip_is_deleted(): void
    {
        $disk = Storage::disk('public');
        $disk->put('notes/celesta/gm-bank.json', '{}');
        foreach (range(44, 95) as $midi) {
            $disk->put('notes/celesta/midi-'.$midi.'.wav', 'single note');
        }
        $path = 'secondary-eleventh-chords/level4/celesta/a-minmaj11-pBv3.wav';
        $disk->put($path, 'old rendered chord');
        $disk->delete($path);

        $notes = NoteSample::resolve($path);
        $this->assertNotNull($notes);
        $this->assertGreaterThanOrEqual(4, count($notes));
        foreach ($notes as $note) {
            $sample = NoteSample::path($note['slug'], $note['degree'], $note['octave']);
            $this->assertStringContainsString('/midi-', $sample);
            $this->assertTrue($disk->exists($sample));
        }
        $this->assertFalse($disk->exists($path));
    }

    public function test_completed_gm_banks_do_not_lower_chromatic_melody_notes_by_an_octave(): void
    {
        require_once base_path('scripts/audio/dual_tonic_melodies_pool.php');
        $pattern = collect(dualTonicMelodyPatterns())->first(fn ($pattern) => collect($pattern['notes'])->contains('type', 'chromatic'));
        $this->assertNotNull($pattern);
        foreach (['voice', 'saxophone'] as $slug) {
            Storage::disk('public')->put('notes/'.$slug.'/gm-bank.json', '{}');
            $notes = NoteSample::resolve('melodic-dictation/dual-tonic-melodies/'.$slug.'/'.$pattern['fileSlug'].'-o0.wav');
            foreach ($pattern['notes'] as $index => $source) {
                $degree = (($source['idx'] % 7) + 7) % 7;
                $expected = 48 + (int) floor($source['idx'] / 7) * 12 + [0, 2, 4, 5, 7, 9, 11][$degree] + ($source['type'] === 'diatonic' ? 0 : 1);
                $this->assertSame('notes/'.$slug.'/midi-'.$expected.'.wav', NoteSample::path($slug, $notes[$index]['degree'], $notes[$index]['octave']));
            }
        }
    }
}
