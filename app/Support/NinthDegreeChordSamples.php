<?php

namespace App\Support;

use App\Models\QuizQuestion;

class NinthDegreeChordSamples
{
    private const QUALITIES = [
        'major9' => [0, 4, 7, 11, 14],      // Major 9th
        'minor9' => [0, 3, 7, 10, 14],      // Minor 9th
        'dim7add9' => [0, 3, 6, 9, 14],     // Diminished 7(9)
        'dominant9' => [0, 4, 7, 10, 14],   // Dominant 9th
        'minor9b5' => [0, 3, 6, 10, 14],    // Minor 9(♭5)
        'dom7b9' => [0, 4, 7, 10, 13],      // Dominant 7(♭9)
    ];

    private const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    private const POSITIONS = ['root' => 0, '1st' => 1, '2nd' => 2, '3rd' => 3];

    /**
     * Mirrors the other chord-category Samples classes (BasicTriadSamples,
     * SeventhDegreeChordSamples, ...) but for 5-note 9th chords across 3
     * distinct voicing techniques, one per level pair — see the matching
     * scripts/audio/gen_ninth_level*.php this was ported from:
     *  - Levels 1-2: flat root-position 5-note chord.
     *  - Levels 3-4: root fixed in the bass; the upper 4 notes (3rd/5th/
     *    7th/9th) form a 7th chord a 3rd above the root and get inverted
     *    through all 4 positions on top of that fixed bass root, with the
     *    5th doubled an octave above the top from position 1 on, and the
     *    7th additionally doubled from position 3 on.
     *  - Levels 5-6: "two-hands, no-root" — left hand plays a lower chord
     *    from the 9th chord's own tones (root/3rd/5th triad, or root/3rd/
     *    5th/7th 7th-chord), never in root position; right hand plays the
     *    upper triad (5th/7th/9th) at inversion (left's inversion + 1) % 3,
     *    shifted up until it sits fully above the left hand.
     *
     * @return list<array{slug: string, degree: string, octave: int, offset: float}>|null
     */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^ninth-degree-chords/level([1-6])/([a-z-]+)/([a-z0-9-]+)\.wav$#', $path, $matches)) {
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
            in_array($level, [3, 4], true) => self::fixedBassUpperStructure($base, $intervals, $parts),
            default => self::twoHandsNoRoot($base, $intervals, $parts),
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
    private static function fixedBassUpperStructure(int $base, array $intervals, array $parts): ?array
    {
        $posSlug = array_shift($parts);
        if ($parts !== [] || ! isset(self::POSITIONS[$posSlug])) {
            return null;
        }
        [$i0, $i1, $i2, $i3, $i4] = $intervals;
        $root = $base + $i0;
        $upper = [$base + $i1, $base + $i2, $base + $i3, $base + $i4];
        $chordFifth = $base + $i2;
        $chordSeventh = $base + $i3;
        $voicing = array_merge([$root], self::rotations4($upper)[$posSlug]);

        if ($posSlug !== 'root') {
            $voicing[] = self::doubleAbove($chordFifth, max($voicing));
        }
        if ($posSlug === '3rd') {
            $voicing[] = self::doubleAbove($chordSeventh, max($voicing));
        }
        sort($voicing);

        return $voicing;
    }

    /** @return list<int>|null */
    private static function twoHandsNoRoot(int $base, array $intervals, array $parts): ?array
    {
        $posSlug = array_shift($parts);
        if ($parts !== [] || ! preg_match('/^p([12])v([123])$/', (string) $posSlug, $m)) {
            return null;
        }
        $pattern = (int) $m[1];
        $leftInv = (int) $m[2];
        if ($pattern === 1 && $leftInv > 2) {
            return null;
        }
        [$i0, $i1, $i2, $i3, $i4] = $intervals;
        $triadLeft = [$base + $i0, $base + $i1, $base + $i2];
        $seventhLeft = [$base + $i0, $base + $i1, $base + $i2, $base + $i3];
        $triadRight = [$base + $i2, $base + $i3, $base + $i4];

        $left = self::rotationN($pattern === 1 ? $triadLeft : $seventhLeft, $leftInv);
        $rightInv = ($leftInv + 1) % 3;
        $right = self::shiftAboveLeft(self::rotationN($triadRight, $rightInv), max($left));
        $voicing = array_merge($left, $right);
        sort($voicing);

        return $voicing;
    }

    /** @return array<string, list<int>> */
    private static function rotations4(array $upper): array
    {
        [$n0, $n1, $n2, $n3] = $upper;

        return [
            'root' => [$n0, $n1, $n2, $n3],
            '1st' => [$n1, $n2, $n3, $n0 + 12],
            '2nd' => [$n2, $n3, $n0 + 12, $n1 + 12],
            '3rd' => [$n3, $n0 + 12, $n1 + 12, $n2 + 12],
        ];
    }

    private static function doubleAbove(int $note, int $above): int
    {
        while ($note <= $above) {
            $note += 12;
        }

        return $note;
    }

    /** @return list<int> */
    private static function rotationN(array $notes, int $inv): array
    {
        $n = count($notes);
        $rotated = array_merge(array_slice($notes, $inv), array_slice($notes, 0, $inv));
        for ($i = 0; $i < $inv; $i++) {
            $rotated[$n - $inv + $i] += 12;
        }

        return $rotated;
    }

    /** @return list<int> */
    private static function shiftAboveLeft(array $right, int $leftMax): array
    {
        while (min($right) <= $leftMax) {
            $right = array_map(fn (int $n): int => $n + 12, $right);
        }

        return $right;
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
