<?php

namespace Tests\Feature;

use App\Support\NoteSample;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganChordalOctaveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        require_once base_path('scripts/audio/chordal_melodies_pool.php');
    }

    private function recordOrganOctaves(int $low, int $high, string $slug = 'organ'): void
    {
        foreach (range($low, $high) as $octave) {
            foreach (array_keys(NoteSample::CHROMATIC_SLUGS) as $label) {
                Storage::disk('public')->put(NoteSample::path($slug, NoteSample::equivalentDegree($label) ?? $label, $octave), 'recording');
            }
        }
    }

    public static function chordalInstruments(): array
    {
        return ['Organ' => ['organ'], 'Harpejji' => ['harpejji']];
    }

    #[DataProvider('chordalInstruments')]
    public function test_octave_up_paths_raise_every_voice_and_preserve_shared_originals(string $slug): void
    {
        $this->recordOrganOctaves(0, 8, $slug);
        foreach (array_keys(chordalLiteralProgressions()) as $index) {
            foreach (['orig', 'drop2'] as $variant) {
                $path = "melodic-dictation/chordal-melodies/{$slug}/p{$index}-{$variant}.wav";
                $original = NoteSample::resolve($path);
                $raised = NoteSample::resolve(substr($path, 0, -4).'-up12.wav');
                $this->assertNotNull($original);
                $this->assertNotNull($raised);
                $this->assertCount(count($original), $raised);
                foreach ($original as $position => $note) {
                    $expected = [...$note, 'octave' => $note['octave'] + 1];
                    $this->assertSame($expected, $raised[$position]);
                }
                $this->assertSame($original, NoteSample::resolve($path));
            }
        }
    }

    public function test_raised_solo_choir_uses_the_true_octave_of_legacy_sharp_recordings(): void
    {
        $this->recordOrganOctaves(0, 8, 'voice');
        $path = 'melodic-dictation/chordal-melodies/voice/p10-orig';
        $original = NoteSample::resolve($path.'.wav');
        $raised = NoteSample::resolve($path.'-up12.wav');
        $this->assertNotNull($raised);
        $this->assertTrue(collect($raised)->contains(fn ($note) => $note['degree'] === 'F#/Gb' && $note['octave'] === 3));
        $this->assertFalse(collect($raised)->contains(fn ($note) => $note['degree'] === 'F#/Gb' && $note['octave'] === 4));
        foreach ($original as $index => $note) {
            $expected = [...$note, 'octave' => $note['octave'] + (str_contains($note['degree'], '#') ? 0 : 1)];
            $this->assertSame($expected, $raised[$index]);
        }
        $this->assertSame($original, NoteSample::resolve($path.'.wav'));
    }

    public function test_raising_drop_two_voicings_resolves_the_missing_low_g(): void
    {
        $this->recordOrganOctaves(3, 7);
        $index = collect(chordalLiteralProgressions())->search(function (array $steps): bool {
            return collect(chordalDrop2Voicings(chordalBuildVoicings($steps)))->flatten()->contains(43);
        });
        $this->assertNotFalse($index);
        $path = "melodic-dictation/chordal-melodies/organ/p{$index}-drop2";
        $this->assertNull(NoteSample::resolve($path.'.wav'));
        $this->assertNotNull(NoteSample::resolve($path.'-up12.wav'));
    }
}
