<?php

namespace App\Support;

use App\Models\QuizQuestion;

class ThirteenthDegreeChordSamples
{
    private const QUALITIES = [
        'maj13s11' => [0, 4, 7, 11, 14, 18, 21],    // Major 13(♯11)
        'minor13' => [0, 3, 7, 10, 14, 17, 21],     // Minor 13th
        'dom13s11' => [0, 4, 7, 10, 14, 18, 21],    // Dominant 13(♯11)
        'dom13b9s11' => [0, 4, 7, 10, 13, 18, 21],  // Dominant 13(♭9,♯11)
    ];

    private const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    /**
     * Mirrors the other chord-category Samples classes, ported from
     * scripts/audio/gen_thirteenth_level*.php — 7-note chords (root, 3rd,
     * 5th, 7th, 9th, 11th, 13th) across 3 voicing techniques, one per level
     * pair:
     *  - Levels 1-2: flat root position.
     *  - Levels 3-4: left hand fixed as the root-position triad
     *    (root/3rd/5th); right hand is the upper 4-note structure
     *    (7th/9th/11th/13th), rotated through 1st and 2nd inversion only.
     *  - Levels 5-6: "avoid the root" — both hands always inverted (never
     *    root position), same split as 3-4, but the SAME inversion number
     *    (1st or 2nd) applies to both hands together.
     *
     * @return list<array{slug: string, degree: string, octave: int, offset: float}>|null
     */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^thirteenth-degree-chords/level([1-6])/([a-z-]+)/([a-z0-9-]+)\.wav$#', $path, $matches)) {
            return null;
        }
        $level = (int) $matches[1];
        $slug = $instrument ?? $matches[2];
        $parts = explode('-', $matches[3]);

        $key = 5;
        if (in_array($level, [2, 4, 6], true)) {
            $key = array_search(array_shift($parts), self::KEYS, true);
            if ($key === false) {
                return null;
            }
        }

        $quality = array_shift($parts);
        $intervals = self::QUALITIES[$quality] ?? null;
        if ($intervals === null) {
            return null;
        }

        $base = 48 + $key;
        $pitches = match (true) {
            in_array($level, [1, 2], true) => self::rootPosition($base, $intervals, $parts),
            in_array($level, [3, 4], true) => self::fixedTriadUpperStructure($base, $intervals, $parts),
            default => self::avoidTheRoot($base, $intervals, $parts),
        };
        if ($pitches === null) {
            return null;
        }

        foreach ([0, 12, -12, 24, -24, 36, -36] as $shift) {
            $notes = [];
            foreach ($pitches as $pitch) {
                $note = NoteSample::fromRecordedMidiPitch($pitch + $shift, $slug);
                if ($note === null) {
                    continue 2;
                }
                $notes[] = ['slug' => $slug, ...$note];
            }

            return $notes;
        }

        return null;
    }

    /** @return list<int>|null */
    private static function rootPosition(int $base, array $intervals, array $parts): ?array
    {
        if ($parts !== []) {
            return null;
        }
        $notes = array_map(fn (int $i): int => $base + $i, $intervals);
        sort($notes);

        return $notes;
    }

    /** @return list<int>|null */
    private static function fixedTriadUpperStructure(int $base, array $intervals, array $parts): ?array
    {
        $variant = array_shift($parts);
        if ($parts !== [] || ! preg_match('/^v([12])$/', (string) $variant, $m)) {
            return null;
        }
        [$i0, $i1, $i2, $i3, $i4, $i5, $i6] = $intervals;
        $left = [$base + $i0, $base + $i1, $base + $i2];
        $right = [$base + $i3, $base + $i4, $base + $i5, $base + $i6];

        $voicing = array_merge($left, self::rotation($right, (int) $m[1]));
        sort($voicing);

        return $voicing;
    }

    /** @return list<int>|null */
    private static function avoidTheRoot(int $base, array $intervals, array $parts): ?array
    {
        $variant = array_shift($parts);
        if ($parts !== [] || ! preg_match('/^v([12])$/', (string) $variant, $m)) {
            return null;
        }
        [$i0, $i1, $i2, $i3, $i4, $i5, $i6] = $intervals;
        $left = [$base + $i0, $base + $i1, $base + $i2];
        $right = [$base + $i3, $base + $i4, $base + $i5, $base + $i6];
        $inv = (int) $m[1];

        $voicing = array_merge(self::rotation($left, $inv), self::rotation($right, $inv));
        sort($voicing);

        return $voicing;
    }

    /** @return list<int> */
    private static function rotation(array $notes, int $inv): array
    {
        $n = count($notes);
        $rotated = array_merge(array_slice($notes, $inv), array_slice($notes, 0, $inv));
        for ($i = 0; $i < $inv; $i++) {
            $rotated[$n - $inv + $i] += 12;
        }

        return $rotated;
    }

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function forQuestion(QuizQuestion $question, string $slug): ?array
    {
        $variants = $question->audioVariants;
        $path = $variants->firstWhere('sound', 'Piano')?->audio_path
            ?? $variants->first()?->audio_path ?? $question->audio_path;

        return $path ? self::resolve($path, $slug) : null;
    }
}
