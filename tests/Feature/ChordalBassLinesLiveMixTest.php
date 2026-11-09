<?php

namespace Tests\Feature;

use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChordalBassLinesLiveMixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        require_once base_path('scripts/audio/chordal_bass_lines_pool.php');
        foreach (range(0, 8) as $octave) {
            foreach (array_keys(NoteSample::CHROMATIC_SLUGS) as $degree) {
                Storage::disk('public')->put(NoteSample::path('piano', NoteSample::equivalentDegree($degree) ?? $degree, $octave), 'sample');
            }
        }
    }

    public function test_every_sequence_reproduces_its_exact_final_drop2_voicing_and_bass_answer(): void
    {
        $patterns = chordalBassLineLiteralProgressions();
        $this->assertCount(150, $patterns);

        $candidates = json_decode(file_get_contents(base_path('docs/composition/chordal-bass-line-150-candidates.json')), true);
        $bySequenceId = [];
        foreach ($candidates['sequences'] as $sequence) {
            $bySequenceId[$sequence['id']] = $sequence;
        }

        $fiveEventCount = 0;
        $sixEventCount = 0;
        $seenBassSequences = [];
        foreach ($patterns as $i => $steps) {
            $id = sprintf('CBL-%03d', $i + 1);
            $voicings = chordalDrop2Voicings(chordalBuildVoicings($steps));
            $basses = array_map(fn ($v) => min($v), $voicings);
            $label = chordalStripOctaves(implode(' ', array_map('chordalLabelForTop', $basses)));

            $expectedBasses = array_map(fn ($step) => $step['bass_midi'], $bySequenceId[$id]['steps']);
            $this->assertSame($expectedBasses, $basses, "{$id} bass sequence");
            $this->assertSame($bySequenceId[$id]['answer'], $label, "{$id} answer label");

            foreach ($basses as $bass) {
                $this->assertGreaterThanOrEqual(36, $bass);
                $this->assertLessThanOrEqual(48, $bass);
            }

            count($steps) === 5 ? $fiveEventCount++ : $sixEventCount++;

            $bassKey = implode(',', $basses);
            $this->assertArrayNotHasKey($bassKey, $seenBassSequences, "{$id} duplicates ".($seenBassSequences[$bassKey] ?? '?')."'s bass sequence");
            $seenBassSequences[$bassKey] = $id;
        }
        $this->assertSame(75, $fiveEventCount);
        $this->assertSame(75, $sixEventCount);
    }

    public function test_resolve_builds_the_chord_sequence_from_the_independent_bass_line_pool(): void
    {
        $notes = NoteSample::resolve('melodic-dictation/chordal-bass-lines/piano/cbl1.wav');

        $this->assertNotNull($notes);
        $this->assertCount(5, array_unique(array_column($notes, 'offset')));
        $this->assertSame(
            [['degree' => 'Fa', 'octave' => 2], ['degree' => 'Do', 'octave' => 3], ['degree' => 'Mi', 'octave' => 3], ['degree' => 'La', 'octave' => 3]],
            array_map(fn ($n) => ['degree' => $n['degree'], 'octave' => $n['octave']], array_filter($notes, fn ($n) => $n['offset'] === 0.0))
        );
    }

    public function test_resolve_returns_null_for_an_out_of_range_index_or_missing_recording(): void
    {
        $this->assertNull(NoteSample::resolve('melodic-dictation/chordal-bass-lines/piano/cbl0.wav'));
        $this->assertNull(NoteSample::resolve('melodic-dictation/chordal-bass-lines/piano/cbl151.wav'));

        Storage::disk('public')->deleteDirectory('notes/piano');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        $this->assertNull(NoteSample::resolve('melodic-dictation/chordal-bass-lines/piano/cbl1.wav'));
    }
}
