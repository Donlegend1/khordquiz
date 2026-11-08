<?php

namespace App\Support;

class ProgressionRecognitionSamples
{
    public const TITLE = 'Progression Recognition #1';

    public const PREFIX = 'progression-recognition/f-part-one-v1';

    public const PART_TWO_TITLE = 'Progression Recognition #2';

    public const PART_TWO_PREFIX = 'progression-recognition/all-keys-part-two-v1';

    public const OPTIONS = [
        2 => ['min7' => 'Minor 7th', 'm7b5' => 'Minor 7(♭5)', 'dom7' => 'Dominant 7th', 'min6' => 'Minor 6'],
        5 => ['dom7' => 'Dominant 7th', 'min7' => 'Minor 7th', 'm7b5' => 'Minor 7(♭5)'],
        1 => ['dom7' => 'Dominant 7th', 'min7' => 'Minor 7th', 'maj7' => 'Major 7th', 'maj6' => 'Major 6', 'min6' => 'Minor 6'],
    ];

    private const INTERVALS = [
        'min7' => [0, 3, 7, 10], 'm7b5' => [0, 3, 6, 10], 'dom7' => [0, 4, 7, 10],
        'min6' => [0, 3, 7, 9], 'maj7' => [0, 4, 7, 11], 'maj6' => [0, 4, 7, 9],
    ];

    public static function recipes(): array
    {
        static $recipes = null;
        if ($recipes !== null) {
            return $recipes;
        }
        $rows = [];
        foreach (self::OPTIONS[2] as $two => $twoLabel) {
            foreach (self::OPTIONS[5] as $five => $fiveLabel) {
                foreach (self::OPTIONS[1] as $one => $oneLabel) {
                    $fifth = self::INTERVALS[$five];
                    $chords = [
                        array_map(fn (int $n): int => 55 + $n, self::INTERVALS[$two]),
                        [48 + $fifth[2], 48 + $fifth[3], 60 + $fifth[0], 60 + $fifth[1]],
                        array_map(fn (int $n): int => 53 + $n, self::INTERVALS[$one]),
                    ];
                    $rows[$two.'-'.$five.'-'.$one] = ['answer' => '2 '.$twoLabel.' - 5 '.$fiveLabel.' - 1 '.$oneLabel, 'chords' => $chords];
                }
            }
        }

        return $recipes = $rows;
    }

    public static function resolve(string $path): ?array
    {
        $transpose = 0;
        if (preg_match('#^'.self::PART_TWO_PREFIX.'/([^/]+)/([a-z]+)-([a-z0-9-]+)\.wav$#', $path, $m)) {
            [, $slug, $key, $id] = $m;
            $keyIndex = array_search($key, CadenceSamples::KEYS, true);
            if ($keyIndex === false) {
                return null;
            }
            $transpose = $keyIndex - 5;
        } elseif (preg_match('#^'.self::PREFIX.'/([^/]+)/([a-z0-9-]+)\.wav$#', $path, $m)) {
            [, $slug, $id] = $m;
        } else {
            return null;
        }
        $recipe = self::recipes()[$id] ?? null;
        $register = CadenceSamples::preferredRegister($slug);
        if ($recipe === null || $register === null) {
            return null;
        }
        [$low, $high, $center] = $register;
        $pitches = array_merge(...$recipe['chords']);
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
                    $notes[] = ['slug' => $slug, ...$note, 'offset' => $index * 1.6];
                }
            }

            return $notes;
        }

        return null;
    }

    public static function manifest(int $part = 1): array
    {
        $rows = [];
        $prefix = $part === 2 ? self::PART_TWO_PREFIX : self::PREFIX;
        $keys = $part === 2 ? CadenceSamples::KEYS : ['f'];
        foreach ($keys as $key) {
            foreach (self::recipes() as $id => $recipe) {
                $recipeId = $part === 2 ? $key.'-'.$id : $id;
                $row = ['recipe' => $recipeId, 'key' => $key, 'answer' => $recipe['answer'], 'samples' => []];
                foreach (QuizCatalog::SOUND_SLUGS as $slug) {
                    $notes = self::resolve($prefix.'/'.$slug.'/'.$recipeId.'.wav');
                    if ($notes !== null) {
                        $row['samples'][$slug] = array_map(fn (array $n): array => array_diff_key($n, ['slug' => true]), $notes);
                    }
                }
                if ($row['samples'] !== []) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    public static function mobileManifest(array $manifest): array
    {
        $names = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];

        return array_map(function (array $row) use ($names): array {
            foreach ($row['samples'] as $slug => $notes) {
                $row['samples'][$slug] = array_map(fn (array $note): array => [array_search($note['degree'], $names, true), $note['octave']], $notes);
            }

            return $row;
        }, $manifest);
    }
}
