<?php

namespace Tests\Feature;

use App\Support\NoteSample;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecordedNoteMappingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
    }

    public function test_celesta_and_vibraphone_use_the_measured_bare_and_suffixed_sharp_registers(): void
    {
        foreach (['celesta', 'vibraphone'] as $slug) {
            foreach (['fsharp.wav', 'fsharp-c2.wav', 'fsharp-c4.wav', 'csharp-c5.wav', 're-c4.wav'] as $file) {
                Storage::disk('public')->put('notes/'.$slug.'/'.$file, 'sample');
            }
            $this->assertSame(['degree' => 'F#/Gb', 'octave' => 3, 'offset' => 0.0], NoteSample::fromRecordedMidiPitch(54, $slug));
            $this->assertNull(NoteSample::fromRecordedMidiPitch(66, $slug));
            $this->assertSame(['degree' => 'F#/Gb', 'octave' => 4, 'offset' => 0.0], NoteSample::fromRecordedMidiPitch(78, $slug));
            $this->assertSame(['degree' => 'C#/Db', 'octave' => 5, 'offset' => 0.0], NoteSample::fromRecordedMidiPitch(85, $slug));
            $this->assertSame(['degree' => 'Re', 'octave' => 4, 'offset' => 0.0], NoteSample::fromRecordedMidiPitch(62, $slug));
            $this->assertNull(NoteSample::fromScientificPitchAnswer('F#/Gb4', $slug));
            $this->assertSame([['degree' => 'F#/Gb', 'octave' => 4, 'offset' => 0.0]], NoteSample::fromScientificPitchAnswer('F#/Gb5', $slug));
        }
    }

    public function test_chordal_melody_preserves_its_shape_by_shifting_the_whole_sequence_into_the_recorded_bank(): void
    {
        foreach (range(36, 70) as $midi) {
            $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
            $octave = intdiv($midi, 12) - 1 - (str_contains($label, '#') ? 1 : 0);
            Storage::disk('public')->put(NoteSample::path('cello', NoteSample::equivalentDegree($label) ?? $label, $octave), 'sample');
        }
        $notes = NoteSample::resolve('melodic-dictation/chordal-melodies/cello/p3-orig.wav');
        $this->assertNotNull($notes);
        $this->assertCount(5, array_unique(array_column($notes, 'offset')));
        foreach ($notes as $note) {
            Storage::disk('public')->assertExists(NoteSample::path('cello', $note['degree'], $note['octave']));
        }
        $this->assertContains(['slug' => 'cello', 'degree' => 'A#/Bb', 'octave' => 1, 'offset' => 0.75], $notes);
    }
}
