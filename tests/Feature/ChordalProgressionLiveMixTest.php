<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Support\CadenceSamples;
use App\Support\ChordalProgressionSamples;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChordalProgressionLiveMixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        require_once base_path('scripts/audio/chordal_melodies_pool.php');
        foreach (range(0, 8) as $octave) {
            foreach (array_keys(NoteSample::CHROMATIC_SLUGS) as $degree) {
                Storage::disk('public')->put(NoteSample::path('piano', NoteSample::equivalentDegree($degree) ?? $degree, $octave), 'sample');
            }
        }
    }

    private function source(Quiz $quiz, int $index, string $variant, int $position): QuizQuestion
    {
        $question = $quiz->questions()->create(['position' => $position, 'prompt' => 'Source', 'answer' => 'Source melody unchanged', 'choices' => []]);
        $question->audioVariants()->create(['sound' => 'Piano', 'audio_path' => "melodic-dictation/chordal-melodies/piano/p{$index}-{$variant}.wav"]);

        return $question;
    }

    public function test_every_five_chord_recipe_keeps_its_exact_source_notes_and_quality_order(): void
    {
        $quiz = Quiz::factory()->create();
        $eligible = 0;
        foreach (chordalLiteralProgressions() as $index => $steps) {
            foreach (['orig', 'drop2'] as $variant) {
                $question = $this->source($quiz, $index, $variant, 100 - $index * 2 - ($variant === 'drop2' ? 1 : 0));
                $row = ChordalProgressionSamples::fromSource($question);
                if (count($steps) !== 5) {
                    $this->assertNull($row);

                    continue;
                }
                $eligible++;
                $this->assertSame('p'.$index.'-'.$variant, $row['recipe']);
                $this->assertSame(array_map(fn (array $step): string => ChordalProgressionSamples::QUALITIES[$step['quality']], $steps), explode(' - ', $row['answer']));
                $this->assertCount(5, array_unique(array_column($row['samples']['piano'], 'offset')));
                $expected = array_map(fn (array $note): array => array_diff_key($note, ['slug' => true]), NoteSample::resolve($row['audio']['Piano']));
                $this->assertSame($expected, $row['samples']['piano']);
            }
        }
        // chordalLiteralProgressions() grew from 26 to 281 entries across seven
        // 6 Nov 2026 additions: 33 Gemini-generated (all 6-chord — doesn't
        // change $eligible), 22 hand-composed (19 of those are 5-chord, taking
        // the 5-chord total from 13 to 32), the first 100-sequence teaching-guide
        // set CM-001..CM-100 (50 five-chord, taking it to 82), then the second
        // 100-sequence set CM-101..CM-200 in three sub-batches: CM-101..CM-110
        // (10 five-chord, to 92), CM-111..CM-150 (40 five-chord, to 132), and
        // CM-151..CM-200 (50 six-chord — doesn't change the five-chord count).
        // $eligible counts each five-chord pattern twice (once per orig/drop2
        // variant), so it's 2x the five-chord count: 26 -> 264.
        $this->assertSame(264, $eligible);
        $this->assertCount(281, chordalLiteralProgressions());
        $this->assertArrayNotHasKey(281, chordalLiteralProgressions());
    }

    public function test_all_active_chords_use_the_fifteen_allowed_qualities_and_preserve_the_chosen_voicings(): void
    {
        // 15 original + 4 added 6 Nov 2026 (sus2, sus4, min7aug, maj7sus2 —
        // first used by that day's new Chordal melodies progressions).
        $this->assertCount(19, ChordalProgressionSamples::QUALITIES);
        $patterns = chordalLiteralProgressions();
        foreach ($patterns as $steps) {
            foreach ($steps as $step) {
                $this->assertArrayHasKey($step['quality'], ChordalProgressionSamples::QUALITIES);
            }
        }
        $expected = [
            13 => [2 => [54, 60, 63], 3 => [51, 54, 60]],
            14 => [0 => [48, 51, 54], 1 => [47, 50, 54], 2 => [48, 51, 54]],
            15 => [2 => [62, 65, 69, 72], 3 => [52, 57, 60], 4 => [53, 58, 62]],
            18 => [1 => [53, 56, 60, 62], 2 => [56, 60, 62, 65]],
            24 => [4 => [57, 60, 65]],
            25 => [0 => [48, 51, 54], 2 => [45, 48, 53]],
        ];
        foreach ($expected as $pattern => $chords) {
            $notes = chordalBuildVoicings($patterns[$pattern]);
            foreach ($chords as $index => $voicing) {
                $this->assertSame($voicing, $notes[$index]);
            }
        }
        $this->assertSame(['root' => 5, 'quality' => 'maj', 'bassIdx' => 1, 'melodyIdx' => 0], $patterns[24][4]);
        $this->assertSame(['root' => 2, 'quality' => 'm7b5', 'bassIdx' => 1, 'melodyIdx' => 0], $patterns[18][1]);
    }

    public function test_transposed_mixes_preserve_every_voice_and_stay_inside_the_preferred_register(): void
    {
        $degrees = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];
        $seenKeys = [];
        foreach (chordalLiteralProgressions() as $index => $steps) {
            if (count($steps) !== 5) {
                continue;
            }
            foreach (['orig', 'drop2'] as $variant) {
                $chords = chordalBuildVoicings($steps);
                if ($variant === 'drop2') {
                    $chords = chordalDrop2Voicings($chords);
                }
                $original = array_merge(...$chords);
                foreach (CadenceSamples::KEYS as $key => $name) {
                    $path = ChordalProgressionSamples::PREFIX.'/piano/'.$name.'-p'.$index.'-'.$variant.'.wav';
                    $notes = NoteSample::resolve($path);
                    if ($notes === null) {
                        continue;
                    }
                    $seenKeys[$name] = true;
                    $this->assertCount(count($original), $notes);
                    $this->assertCount(5, array_unique(array_column($notes, 'offset')));
                    $delta = null;
                    foreach ($notes as $position => $note) {
                        $pitch = ($note['octave'] + 1) * 12 + array_search($note['degree'], $degrees, true);
                        $this->assertGreaterThanOrEqual(48, $pitch);
                        $this->assertLessThanOrEqual(76, $pitch);
                        $delta ??= $pitch - $original[$position];
                        $this->assertSame($delta, $pitch - $original[$position]);
                        $this->assertSame($key, ($delta % 12 + 12) % 12);
                        $this->assertTrue(NoteSample::sampleExists(NoteSample::path('piano', $note['degree'], $note['octave'])));
                    }
                }
            }
        }
        $this->assertCount(12, $seenKeys);
    }

    public function test_a_sequence_is_excluded_whole_when_it_cannot_fit_or_a_recording_is_missing(): void
    {
        $prefix = ChordalProgressionSamples::PREFIX;
        $this->assertNull(NoteSample::resolve($prefix.'/piano/c-p15-drop2.wav'));
        $this->assertNull(NoteSample::resolve($prefix.'/flute/eb-p15-drop2.wav'));
        $path = $prefix.'/piano/eb-p15-drop2.wav';
        $notes = NoteSample::resolve($path);
        $this->assertNotNull($notes);
        $note = $notes[0];
        Storage::disk('public')->delete(NoteSample::path('piano', $note['degree'], $note['octave']));
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        $this->assertNull(NoteSample::resolve($path));
        $this->assertNull(NoteSample::resolve($prefix.'/piano/c-p0-orig.wav'));
        $this->assertNull(NoteSample::resolve($prefix.'/piano/invalid-p24-orig.wav'));
        $this->assertNull(NoteSample::resolve($prefix.'/piano/c-p26-orig.wav'));
    }

    public function test_rebuild_filters_by_recipe_length_and_preserves_both_source_quizzes(): void
    {
        $melody = Quiz::factory()->create(['title' => 'Chordal melodies']);
        $bass = Quiz::factory()->create(['title' => 'Chordal Bass Lines']);
        $target = Quiz::factory()->create(['title' => 'Chordal Progressions']);
        $this->source($melody, 0, 'orig', 1);
        $original = $this->source($melody, 24, 'orig', 2);
        $this->source($bass, 24, 'drop2', 1);
        $original->audioVariants()->create(['sound' => 'Bassoon', 'audio_path' => 'melodic-dictation/chordal-melodies/bassoon/p24-orig.wav']);
        $before = Quiz::whereIn('id', [$melody->id, $bass->id])->with('questions.audioVariants')->get()->toJson();
        $old = $this->source($target, 0, 'orig', 1);
        $export = storage_path('app/private/chordal-progressions.json');
        $previous = is_file($export) ? file_get_contents($export) : null;
        try {
            ob_start();
            require base_path('scripts/audio/build_chordal_progressions.php');
            ob_end_clean();
            $rows = $target->questions()->with('audioVariants')->orderBy('position')->get();
            $expected = 0;
            foreach (['orig', 'drop2'] as $variant) {
                foreach (CadenceSamples::KEYS as $key) {
                    if (ChordalProgressionSamples::resolve(ChordalProgressionSamples::PREFIX.'/piano/'.$key.'-p24-'.$variant.'.wav') !== null) {
                        $expected++;
                    }
                }
            }
            $this->assertCount($expected, $rows);
            $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
            foreach ($rows as $row) {
                $this->assertCount(5, explode(' - ', $row->answer));
                $this->assertCount(1, $row->audioVariants);
                $this->assertStringContainsString('-p24-', $row->audioVariants->first()->audio_path);
                $this->assertStringStartsWith(ChordalProgressionSamples::PREFIX.'/', $row->audioVariants->first()->audio_path);
            }
            $this->assertSame($before, Quiz::whereIn('id', [$melody->id, $bass->id])->with('questions.audioVariants')->get()->toJson());
        } finally {
            if ($previous !== null) {
                file_put_contents($export, $previous);
            } else {
                @unlink($export);
            }
        }
    }
}
