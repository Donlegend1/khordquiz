<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Support\CadenceSamples;
use App\Support\NoteSample;
use App\Support\ProgressionRecognitionInversionSamples;
use App\Support\ProgressionRecognitionSamples;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProgressionRecognitionInversionsTest extends TestCase
{
    use RefreshDatabase;

    private const NAMES = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->resetSamples();
        foreach (range(48, 76) as $pitch) {
            Storage::disk('public')->put(NoteSample::path('piano', self::NAMES[$pitch % 12], intdiv($pitch, 12) - 1), 'recording');
        }
    }

    private function resetSamples(): void
    {
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        (new \ReflectionProperty(ProgressionRecognitionInversionSamples::class, 'candidates'))->setValue(null, []);
    }

    public function test_every_quality_sequence_has_four_starting_inversions(): void
    {
        $recipes = ProgressionRecognitionInversionSamples::recipes();
        $this->assertCount(240, $recipes);
        $counts = [];
        foreach ($recipes as $id => $recipe) {
            $counts[$recipe['answer']][] = $recipe['starting_inversion'];
            $base = ProgressionRecognitionSamples::recipes()[substr($id, 0, -3)];
            foreach ($recipe['chords'] as $index => $chord) {
                $this->assertCount(4, $chord);
                $actual = array_map(fn (int $pitch): int => $pitch % 12, $chord);
                $expected = array_map(fn (int $pitch): int => $pitch % 12, $base['chords'][$index]);
                $this->assertEqualsCanonicalizing($expected, $actual);
                if ($index === 0) {
                    $this->assertSame($expected[$recipe['starting_inversion']], $actual[0]);
                }
            }
        }
        $this->assertCount(60, $counts);
        foreach ($counts as $inversions) {
            $this->assertSame([0, 1, 2, 3], $inversions);
        }
    }

    public function test_each_next_chord_is_the_closest_available_inversion_in_register(): void
    {
        foreach (ProgressionRecognitionInversionSamples::recipes() as $id => $recipe) {
            $notes = NoteSample::resolve(ProgressionRecognitionInversionSamples::PREFIX.'/piano/'.$id.'.wav');
            $this->assertCount(12, $notes);
            $this->assertSame([0.0, 1.6, 3.2], array_values(array_unique(array_column($notes, 'offset'))));
            $pitches = array_map(fn (array $note): int => ($note['octave'] + 1) * 12 + array_search($note['degree'], self::NAMES, true), $notes);
            foreach ($recipe['chords'] as $index => $chord) {
                $actual = array_slice($pitches, $index * 4, 4);
                $this->assertEqualsCanonicalizing(array_map(fn (int $p): int => $p % 12, $chord), array_map(fn (int $p): int => $p % 12, $actual));
                foreach ($actual as $pitch) {
                    $this->assertGreaterThanOrEqual(48, $pitch);
                    $this->assertLessThanOrEqual(76, $pitch);
                }
                if ($index === 0) {
                    $this->assertSame($chord[0] % 12, $actual[0] % 12);

                    continue;
                }
                $previous = array_slice($pitches, ($index - 1) * 4, 4);
                $actualMotion = array_sum(array_map(fn (int $p, int $voice): int => abs($p - $previous[$voice]), $actual, range(0, 3)));
                $costs = [];
                foreach (range(0, 3) as $inversion) {
                    $inverted = [...array_slice($chord, $inversion), ...array_map(fn (int $p): int => $p + 12, array_slice($chord, 0, $inversion))];
                    foreach (range(-36, 36, 12) as $shift) {
                        $candidate = array_map(fn (int $p): int => $p + $shift, $inverted);
                        if (min($candidate) >= 48 && max($candidate) <= 76) {
                            $costs[] = array_sum(array_map(fn (int $p, int $voice): int => abs($p - $previous[$voice]), $candidate, range(0, 3)));
                        }
                    }
                }
                $this->assertSame(min($costs), $actualMotion);
            }
        }
    }

    public function test_the_requested_b_flat_bass_example_uses_the_exact_nearest_chords(): void
    {
        $notes = NoteSample::resolve(ProgressionRecognitionInversionSamples::PREFIX.'/piano/min7-dom7-maj7-i1.wav');
        $pitches = array_map(fn (array $note): int => ($note['octave'] + 1) * 12 + array_search($note['degree'], self::NAMES, true), $notes);
        $this->assertSame([58, 62, 65, 67, 58, 60, 64, 67, 57, 60, 64, 65], $pitches);
    }

    public function test_missing_notes_and_invalid_recipes_are_excluded_instead_of_changing_the_chord(): void
    {
        $prefix = ProgressionRecognitionInversionSamples::PREFIX;
        $this->assertNotNull(NoteSample::resolve($prefix.'/piano/min7-dom7-maj7-i1.wav'));
        foreach ([48, 60, 72] as $pitch) {
            Storage::disk('public')->delete(NoteSample::path('piano', 'Do', intdiv($pitch, 12) - 1));
        }
        $this->resetSamples();
        $this->assertNull(NoteSample::resolve($prefix.'/piano/min7-dom7-maj7-i1.wav'));
        $this->assertNull(NoteSample::resolve($prefix.'/piano/min7-dom7-maj7-i4.wav'));
        $this->assertNull(NoteSample::resolve($prefix.'/piano/maj6-dom7-maj7-i0.wav'));
        $this->assertNull(NoteSample::resolve($prefix.'/tuba/min7-dom7-maj7-i0.wav'));
    }

    public function test_building_part_three_preserves_parts_one_and_two(): void
    {
        foreach ([ProgressionRecognitionSamples::TITLE, ProgressionRecognitionSamples::PART_TWO_TITLE] as $title) {
            $quiz = Quiz::factory()->create(['title' => $title]);
            $quiz->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => []]);
        }
        $titles = [ProgressionRecognitionSamples::TITLE, ProgressionRecognitionSamples::PART_TWO_TITLE];
        $before = Quiz::whereIn('title', $titles)->with('questions')->get()->toJson();
        $recognitionPart = 3;
        ob_start();
        require base_path('scripts/audio/build_progression_recognition.php');
        ob_end_clean();
        $quiz = Quiz::where('title', ProgressionRecognitionInversionSamples::TITLE)->firstOrFail();
        $this->assertSame(240, $quiz->questions()->count());
        $this->assertSame($before, Quiz::whereIn('title', $titles)->with('questions')->get()->toJson());
        $this->assertCount(60, $quiz->questions()->pluck('answer')->unique());
        $this->assertTrue($quiz->is_published);
    }

    public function test_part_four_has_four_starts_per_quality_in_every_key_with_nearest_resolutions(): void
    {
        $manifest = ProgressionRecognitionInversionSamples::manifest(4);
        $this->assertCount(2880, $manifest);
        $this->assertCount(2880, array_unique(array_column($manifest, 'recipe')));
        foreach (CadenceSamples::KEYS as $key => $name) {
            $rows = array_values(array_filter($manifest, fn (array $row): bool => $row['key'] === $name));
            $this->assertCount(240, $rows);
            $counts = [];
            foreach ($rows as $row) {
                $counts[$row['answer']][] = $row['starting_inversion'];
                $id = substr($row['recipe'], strlen($name) + 1);
                $recipe = ProgressionRecognitionInversionSamples::recipes()[$id];
                $notes = NoteSample::resolve(ProgressionRecognitionInversionSamples::PART_FOUR_PREFIX.'/piano/'.$row['recipe'].'.wav');
                $this->assertCount(12, $notes);
                $pitches = array_map(fn (array $note): int => ($note['octave'] + 1) * 12 + array_search($note['degree'], self::NAMES, true), $notes);
                foreach ($recipe['chords'] as $index => $base) {
                    $rootChord = array_map(fn (int $p): int => $p + $key - 5, $base);
                    $actual = array_slice($pitches, $index * 4, 4);
                    $this->assertEqualsCanonicalizing(array_map(fn (int $p): int => $p % 12, $rootChord), array_map(fn (int $p): int => $p % 12, $actual));
                    foreach ($actual as $pitch) {
                        $this->assertGreaterThanOrEqual(48, $pitch);
                        $this->assertLessThanOrEqual(76, $pitch);
                    }
                    if ($index === 0) {
                        $this->assertSame($rootChord[0] % 12, $actual[0] % 12);

                        continue;
                    }
                    $previous = array_slice($pitches, ($index - 1) * 4, 4);
                    $cost = array_sum(array_map(fn (int $p, int $v): int => abs($p - $previous[$v]), $actual, range(0, 3)));
                    $costs = [];
                    foreach (range(0, 3) as $inversion) {
                        $inverted = [...array_slice($rootChord, $inversion), ...array_map(fn (int $p): int => $p + 12, array_slice($rootChord, 0, $inversion))];
                        foreach (range(-36, 36, 12) as $shift) {
                            $candidate = array_map(fn (int $p): int => $p + $shift, $inverted);
                            if (min($candidate) >= 48 && max($candidate) <= 76) {
                                $costs[] = array_sum(array_map(fn (int $p, int $v): int => abs($p - $previous[$v]), $candidate, range(0, 3)));
                            }
                        }
                    }
                    $this->assertSame(min($costs), $cost);
                }
                if ($name === 'f') {
                    $this->assertSame(NoteSample::resolve(ProgressionRecognitionInversionSamples::PREFIX.'/piano/'.$id.'.wav'), $notes);
                }
            }
            $this->assertCount(60, $counts);
            foreach ($counts as $starts) {
                $this->assertSame([0, 1, 2, 3], $starts);
            }
        }
        $this->assertNull(NoteSample::resolve(ProgressionRecognitionInversionSamples::PART_FOUR_PREFIX.'/piano/invalid-min7-dom7-maj7-i1.wav'));
    }

    public function test_building_part_four_does_not_change_the_first_three_parts(): void
    {
        $titles = [ProgressionRecognitionSamples::TITLE, ProgressionRecognitionSamples::PART_TWO_TITLE, ProgressionRecognitionInversionSamples::TITLE];
        foreach ($titles as $title) {
            $quiz = Quiz::factory()->create(['title' => $title]);
            $quiz->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => []]);
        }
        $before = Quiz::whereIn('title', $titles)->with('questions')->get()->toJson();
        $recognitionPart = 4;
        ob_start();
        require base_path('scripts/audio/build_progression_recognition.php');
        ob_end_clean();
        $quiz = Quiz::where('title', ProgressionRecognitionInversionSamples::PART_FOUR_TITLE)->firstOrFail();
        $this->assertSame(2880, $quiz->questions()->count());
        $this->assertSame($before, Quiz::whereIn('title', $titles)->with('questions')->get()->toJson());
        $this->assertTrue($quiz->is_published);
    }
}
