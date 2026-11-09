<?php

namespace App\Support;

use App\Models\QuizQuestion;

class CadenceSamples
{
    public const CHOICES = ['Authentic', 'Plagal', 'Minor Plagal', 'Sub-Plagal'];

    public const RESOLUTION_OFFSET = 1.6;

    public const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    /** Preferred sounding registers for this quiz, rather than instrument range limits. */
    private const REGISTERS = [
        'piano' => [48, 76, 62], 'ep' => [48, 76, 62], 'upright-piano' => [48, 76, 62], 'organ' => [48, 76, 62],
        'guitar' => [45, 73, 59], 'electric-guitar' => [45, 73, 59], 'harpejji' => [48, 76, 62],
        'violin' => [57, 81, 69], 'strings' => [48, 76, 62], 'cello' => [43, 67, 55],
        'saxophone' => [50, 78, 64], 'trombone' => [43, 67, 55], 'trumpet' => [55, 79, 67],
        'voice' => [48, 72, 60], 'tropar-choir' => [43, 67, 55], 'ensemble-choir' => [48, 76, 62], 'sustained-choir' => [48, 76, 62],
        'marimba' => [48, 76, 62], 'steel-drum' => [55, 79, 67], 'celesta' => [60, 95, 77], 'vibraphone' => [53, 95, 74],
        'flute' => [60, 84, 72], 'clarinet' => [53, 81, 67], 'oboe' => [58, 82, 70], 'bassoon' => [43, 67, 55],
    ];

    /** @return array{int, int, int}|null */
    public static function preferredRegister(string $slug): ?array
    {
        return self::REGISTERS[$slug] ?? null;
    }

    private const APPROACHES = [
        'c7' => ['Authentic', 48, [0, 4, 7, 10]],
        'bbmaj' => ['Plagal', 58, [0, 4, 7]],
        'bbmaj7' => ['Plagal', 58, [0, 4, 7, 11]],
        'bbmin' => ['Minor Plagal', 58, [0, 3, 7]],
        'bbmin6' => ['Minor Plagal', 58, [0, 3, 7, 9]],
        'gmin' => ['Sub-Plagal', 55, [0, 3, 7]],
        'gmin7' => ['Sub-Plagal', 55, [0, 3, 7, 10]],
    ];

    private const TONICS = [
        'fmaj' => [0, 4, 7],
        'fadd9' => [0, 4, 7, 14],
        'fmaj7' => [0, 4, 7, 11],
    ];

    /** @return array<string, array{answer: string, chords: array{list<int>, list<int>}}> */
    public static function recipes(): array
    {
        static $recipes = null;
        if ($recipes !== null) {
            return $recipes;
        }
        $recipes = [];
        foreach (self::APPROACHES as $approach => [$answer, $root, $intervals]) {
            foreach (self::TONICS as $tonic => $tonicIntervals) {
                foreach (array_keys($intervals) as $firstInversion) {
                    $first = self::invert($root, $intervals, $firstInversion);
                    [$lastInversion, $last] = self::nearestResolution($first, $tonicIntervals);
                    $id = $approach.'-i'.$firstInversion.'-'.$tonic.'-i'.$lastInversion;
                    $recipes[$id] = ['answer' => $answer, 'chords' => [$first, $last]];
                }
            }
        }

        return $recipes;
    }

    /**
     * @param  list<int>  $first
     * @param  list<int>  $intervals
     * @return array{int, list<int>}
     */
    private static function nearestResolution(array $first, array $intervals): array
    {
        $best = null;
        $bestScore = null;
        $closed = array_map(fn (int $tone): int => $tone % 12, $intervals);
        foreach (array_keys($closed) as $inversion) {
            foreach ([0, -12, 12, -24, 24] as $shift) {
                $candidate = self::invert(53 + $shift, $closed, $inversion);
                $distances = [];
                foreach ([[$first, $candidate], [$candidate, $first]] as [$from, $to]) {
                    foreach ($from as $pitch) {
                        $distances[] = min(array_map(fn (int $other): int => abs($pitch - $other), $to));
                    }
                }
                $score = [array_sum($distances), max($distances), -count(array_intersect($first, $candidate)), abs($first[0] - $candidate[0]), abs(end($first) - end($candidate))];
                if ($bestScore === null || $score < $bestScore) {
                    $bestScore = $score;
                    $best = [$inversion, $candidate];
                }
            }
        }

        return $best;
    }

    /** @param list<int> $intervals
     * @return list<int>
     */
    private static function invert(int $root, array $intervals, int $inversion): array
    {
        $bass = $root + $intervals[$inversion];
        $pitches = [];
        foreach ($intervals as $interval) {
            $pitch = $root + $interval;
            while ($pitch < $bass) {
                $pitch += 12;
            }
            $pitches[] = $pitch;
        }
        sort($pitches);

        return $pitches;
    }

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^cadences/all-keys-v3/([a-z-]+)/([a-z]+)-([a-z0-9-]+)\.wav$#', $path, $matches)) {
            return null;
        }
        $slug = $instrument ?? $matches[1];
        $register = self::preferredRegister($slug);
        $key = array_search($matches[2], self::KEYS, true);
        $recipe = self::recipes()[$matches[3]] ?? null;
        if ($register === null || $key === false || $recipe === null) {
            return null;
        }
        [$low, $high, $center] = $register;
        $pitches = array_merge(...$recipe['chords']);
        $transpose = $key - 5;
        $shifts = range(-48, 48, 12);
        usort($shifts, fn (int $a, int $b): int => [abs((min($pitches) + max($pitches)) / 2 + $transpose + $a - $center), abs($a)] <=> [abs((min($pitches) + max($pitches)) / 2 + $transpose + $b - $center), abs($b)]);
        foreach ($shifts as $shift) {
            if (min($pitches) + $transpose + $shift < $low || max($pitches) + $transpose + $shift > $high) {
                continue;
            }
            $notes = [];
            foreach ($recipe['chords'] as $index => $chord) {
                foreach ($chord as $pitch) {
                    $note = NoteSample::fromRecordedMidiPitch($pitch + $transpose + $shift, $slug);
                    if ($note === null) {
                        continue 3;
                    }
                    $notes[] = ['slug' => $slug, ...$note, 'offset' => $index * self::RESOLUTION_OFFSET];
                }
            }

            return $notes;
        }

        return null;
    }

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function forQuestion(QuizQuestion $question, string $slug): ?array
    {
        $path = $question->audioVariants->firstWhere('sound', 'Piano')?->audio_path
            ?? $question->audioVariants->first()?->audio_path ?? $question->audio_path;

        return $path ? self::resolve($path, $slug) : null;
    }

    /** @return list<array{key: string, recipe: string, answer: string, samples: array<string, list<array{degree: string, octave: int, offset: float}>>}> */
    public static function manifest(): array
    {
        $rows = [];
        foreach (self::KEYS as $key) {
            foreach (self::recipes() as $id => $recipe) {
                $samples = [];
                foreach (QuizCatalog::SOUND_SLUGS as $slug) {
                    $notes = self::resolve('cadences/all-keys-v3/'.$slug.'/'.$key.'-'.$id.'.wav');
                    if ($notes !== null) {
                        $samples[$slug] = array_map(fn (array $note): array => array_diff_key($note, ['slug' => true]), $notes);
                    }
                }
                if ($samples === []) {
                    throw new \RuntimeException('No comfortable recorded register for cadence '.$key.'-'.$id);
                }
                $rows[] = ['key' => $key, 'recipe' => $key.'-'.$id, 'answer' => $recipe['answer'], 'samples' => $samples];
            }
        }

        return $rows;
    }
}
