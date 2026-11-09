<?php

namespace App\Support;

class ProgressionRecognitionInversionSamples
{
    public const TITLE = 'Progression Recognition #3';

    public const PREFIX = 'progression-recognition/f-part-three-close-v2';

    public const PART_FOUR_TITLE = 'Progression Recognition #4';

    public const PART_FOUR_PREFIX = 'progression-recognition/all-keys-part-four-v1';

    private static array $candidates = [];

    public static function recipes(): array
    {
        static $recipes = null;
        if ($recipes !== null) {
            return $recipes;
        }
        $recipes = [];
        foreach (ProgressionRecognitionSamples::recipes() as $id => $recipe) {
            $fifth = array_map(fn (int $pitch): int => 48 + $pitch % 12, $recipe['chords'][1]);
            sort($fifth);
            $roots = [$recipe['chords'][0], $fifth, $recipe['chords'][2]];
            foreach (range(0, 3) as $two) {
                $first = self::invert($roots[0], $two);
                $recipes[$id.'-i'.$two] = ['answer' => $recipe['answer'], 'starting_inversion' => $two, 'chords' => [$first, $roots[1], $roots[2]]];
            }
        }

        return $recipes;
    }

    private static function candidates(array $chord, string $slug, array $register): array
    {
        $id = $slug.':'.implode(',', $chord);
        if (isset(self::$candidates[$id])) {
            return self::$candidates[$id];
        }
        [$low, $high] = $register;
        $rows = [];
        foreach (range(-48, 48, 12) as $shift) {
            $pitches = array_map(fn (int $pitch): int => $pitch + $shift, $chord);
            if (min($pitches) < $low || max($pitches) > $high) {
                continue;
            }
            $notes = [];
            foreach ($pitches as $pitch) {
                $note = NoteSample::fromRecordedMidiPitch($pitch, $slug);
                if ($note === null) {
                    continue 2;
                }
                $notes[] = $note;
            }
            $rows[] = ['pitches' => $pitches, 'notes' => $notes];
        }

        return self::$candidates[$id] = $rows;
    }

    private static function invert(array $chord, int $inversion): array
    {
        return [...array_slice($chord, $inversion), ...array_map(fn (int $pitch): int => $pitch + 12, array_slice($chord, 0, $inversion))];
    }

    private static function allInversions(array $rootChord, string $slug, array $register): array
    {
        $rows = [];
        foreach (range(0, 3) as $inversion) {
            foreach (self::candidates(self::invert($rootChord, $inversion), $slug, $register) as $candidate) {
                $rows[] = $candidate;
            }
        }

        return $rows;
    }

    private static function nearest(array $previous, array $options, int $center): ?array
    {
        $best = null;
        $bestScore = null;
        foreach ($options as $candidate) {
            $pitches = $candidate['pitches'];
            $motion = array_map(fn (int $pitch, int $voice): int => abs($pitch - $previous[$voice]), $pitches, range(0, 3));
            $score = [array_sum($motion), max($motion), -count(array_intersect($previous, $pitches)), $motion[0], $motion[3], abs(array_sum($pitches) / 4 - $center), $pitches[0]];
            if ($bestScore === null || $score < $bestScore) {
                $best = $candidate;
                $bestScore = $score;
            }
        }

        return $best;
    }

    public static function resolve(string $path): ?array
    {
        $transpose = 0;
        if (preg_match('#^'.self::PART_FOUR_PREFIX.'/([^/]+)/([a-z]+)-([a-z0-9-]+-i[0-3])\.wav$#', $path, $m)) {
            [, $slug, $key, $id] = $m;
            $keyIndex = array_search($key, CadenceSamples::KEYS, true);
            if ($keyIndex === false) {
                return null;
            }
            $transpose = $keyIndex - 5;
        } elseif (preg_match('#^'.self::PREFIX.'/([^/]+)/([a-z0-9-]+-i[0-3])\.wav$#', $path, $m)) {
            [, $slug, $id] = $m;
        } else {
            return null;
        }
        $recipe = self::recipes()[$id] ?? null;
        $register = CadenceSamples::preferredRegister($slug);
        if ($recipe === null || $register === null) {
            return null;
        }
        $recipe['chords'] = array_map(fn (array $chord): array => array_map(fn (int $pitch): int => $pitch + $transpose, $chord), $recipe['chords']);
        $firstOptions = self::candidates($recipe['chords'][0], $slug, $register);
        $fiveOptions = self::allInversions($recipe['chords'][1], $slug, $register);
        $oneOptions = self::allInversions($recipe['chords'][2], $slug, $register);
        $best = null;
        $bestScore = null;
        foreach ($firstOptions as $two) {
            $five = self::nearest($two['pitches'], $fiveOptions, $register[2]);
            if ($five === null) {
                continue;
            }
            $one = self::nearest($five['pitches'], $oneOptions, $register[2]);
            if ($one === null) {
                continue;
            }
            $firstMotion = array_map(fn (int $pitch, int $voice): int => abs($pitch - $two['pitches'][$voice]), $five['pitches'], range(0, 3));
            $lastMotion = array_map(fn (int $pitch, int $voice): int => abs($pitch - $five['pitches'][$voice]), $one['pitches'], range(0, 3));
            $allPitches = [...$two['pitches'], ...$five['pitches'], ...$one['pitches']];
            $score = [array_sum($firstMotion) + array_sum($lastMotion), max(...$firstMotion, ...$lastMotion), abs(array_sum($two['pitches']) / 4 - $register[2]), abs(array_sum($allPitches) / 12 - $register[2]), $two['pitches'][0]];
            if ($bestScore === null || $score < $bestScore) {
                $best = [$two, $five, $one];
                $bestScore = $score;
            }
        }
        if ($best === null) {
            return null;
        }
        $notes = [];
        foreach ($best as $index => $chord) {
            foreach ($chord['notes'] as $note) {
                $notes[] = ['slug' => $slug, ...$note, 'offset' => $index * 1.6];
            }
        }

        return $notes;
    }

    /** Compact note tuples keep the offline instrument manifest small. */
    public static function manifest(int $part = 3): array
    {
        $names = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];
        $rows = [];
        $prefix = $part === 4 ? self::PART_FOUR_PREFIX : self::PREFIX;
        $keys = $part === 4 ? CadenceSamples::KEYS : ['f'];
        foreach ($keys as $key) {
            foreach (self::recipes() as $id => $recipe) {
                $recipeId = $part === 4 ? $key.'-'.$id : $id;
                $row = ['recipe' => $recipeId, 'key' => $key, 'answer' => $recipe['answer'], 'starting_inversion' => $recipe['starting_inversion'], 'samples' => []];
                foreach (QuizCatalog::SOUND_SLUGS as $slug) {
                    $notes = self::resolve($prefix.'/'.$slug.'/'.$recipeId.'.wav');
                    if ($notes !== null) {
                        $row['samples'][$slug] = array_map(fn (array $note): array => [array_search($note['degree'], $names, true), $note['octave']], $notes);
                    }
                }
                if ($row['samples'] !== []) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }
}
