<?php

namespace App\Support;

use App\Models\QuizQuestion;

class EleventhDegreeChordSamples
{
    private const QUALITIES = [
        'maj9s11' => [0, 4, 7, 11, 14, 18],   // Major 9(♯11)
        'minor11' => [0, 3, 7, 10, 14, 17],   // Minor 11th
        'dim7911' => [0, 3, 6, 9, 14, 17],    // Diminished 7(9,11)
        'dom9s11' => [0, 4, 7, 10, 14, 18],   // Dominant 9(♯11)
        'minor11b5' => [0, 3, 6, 10, 14, 17], // Minor 11(♭5)
        'dom7b9s11' => [0, 4, 7, 10, 13, 18], // Dominant 7(♭9,♯11)
    ];

    private const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    /**
     * Mirrors the other chord-category Samples classes, ported from
     * scripts/audio/gen_eleventh_level*.php — 6-note chords (root, 3rd,
     * 5th, 7th, 9th, 11th) across 3 voicing techniques, one per level pair:
     *  - Levels 1-2: flat root position.
     *  - Levels 3-4: left hand fixed as the root-position triad
     *    (root/3rd/5th); right hand is either the upper triad (7th/9th/
     *    11th) rotated through inversions 1-2 ("pA"), or the upper 7th
     *    chord (5th/7th/9th/11th) rotated through inversions 1-3 ("pB").
     *  - Levels 5-6: "avoid the root" — both hands always inverted (never
     *    root position), left = triad or 7th chord (root/3rd/5th[/7th]),
     *    right = triad or 7th chord (7th/9th/11th or 5th/7th/9th/11th),
     *    both rotated by the SAME inversion index (1 or 2); 4 hand-size
     *    combinations ("tt"/"ts"/"st"/"ss") x 2 inversions = 8 variants.
     *
     * The "General" quiz (both this category and Secondary 11th Chords)
     * reuses these categories' own Level 6 audio paths directly rather
     * than rendering anything new, so no separate handling is needed here
     * — resolve() dispatches purely by path prefix.
     *
     * @return list<array{slug: string, degree: string, octave: int, offset: float}>|null
     */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^eleventh-degree-chords/level([1-6])/([a-z-]+)/([A-Za-z0-9-]+)\.wav$#', $path, $matches)) {
            return null;
        }

        return self::resolveFor(self::QUALITIES, (int) $matches[1], $instrument ?? $matches[2], $matches[3]);
    }

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    protected static function resolveFor(array $qualities, int $level, string $slug, string $filename): ?array
    {
        $parts = explode('-', $filename);

        $key = 5;
        if (in_array($level, [2, 4, 6], true)) {
            $key = array_search(array_shift($parts), self::KEYS, true);
            if ($key === false) {
                return null;
            }
        }

        $quality = array_shift($parts);
        $intervals = $qualities[$quality] ?? null;
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
        if ($parts !== [] || ! preg_match('/^p([AB])v([123])$/', (string) $variant, $m)) {
            return null;
        }
        $pattern = $m[1];
        $inv = (int) $m[2];
        if ($pattern === 'A' && $inv > 2) {
            return null;
        }
        [$i0, $i1, $i2, $i3, $i4, $i5] = $intervals;
        $left = [$base + $i0, $base + $i1, $base + $i2];
        $upper = $pattern === 'A'
            ? [$base + $i3, $base + $i4, $base + $i5]
            : [$base + $i2, $base + $i3, $base + $i4, $base + $i5];

        $voicing = array_merge($left, self::rotation($upper, $inv));
        sort($voicing);

        return $voicing;
    }

    /** @return list<int>|null */
    private static function avoidTheRoot(int $base, array $intervals, array $parts): ?array
    {
        $variant = array_shift($parts);
        if ($parts !== [] || ! preg_match('/^(tt|ts|st|ss)([12])$/', (string) $variant, $m)) {
            return null;
        }
        [$i0, $i1, $i2, $i3, $i4, $i5] = $intervals;
        $leftTriad = [$base + $i0, $base + $i1, $base + $i2];
        $leftSeventh = [$base + $i0, $base + $i1, $base + $i2, $base + $i3];
        $rightTriad = [$base + $i3, $base + $i4, $base + $i5];
        $rightSeventh = [$base + $i2, $base + $i3, $base + $i4, $base + $i5];

        [$left, $right] = match ($m[1]) {
            'tt' => [$leftTriad, $rightTriad],
            'ts' => [$leftTriad, $rightSeventh],
            'st' => [$leftSeventh, $rightTriad],
            'ss' => [$leftSeventh, $rightSeventh],
        };
        $inv = (int) $m[2];
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
