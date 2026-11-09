<?php

namespace App\Support;

use App\Models\QuizQuestion;

class MinorChordExtensionSamples
{
    private const QUALITIES_LEVEL1 = [
        'min6' => [0, 3, 7, 9],
        'min7' => [0, 3, 7, 10],
        'minaddb9' => [0, 3, 7, 13],
        'minadd9' => [0, 3, 7, 14],
        'minadd11' => [0, 3, 7, 17],
    ];

    private const QUALITIES_LEVEL2 = [
        'minadd911' => [0, 3, 7, 14, 17],
        'min69' => [0, 3, 7, 9, 14],
        'min6add11' => [0, 3, 7, 9, 17],
        'min9' => [0, 3, 7, 10, 14],
        'min711' => [0, 3, 7, 10, 17],
        'min713' => [0, 3, 7, 10, 21],
    ];

    private const QUALITIES_LEVEL3 = [
        'min69add11' => [0, 3, 7, 9, 14, 17],
        'min6b911' => [0, 3, 7, 9, 13, 17],
        'min11' => [0, 3, 7, 10, 14, 17],
        'min13' => [0, 3, 7, 10, 14, 17, 21],
        'min71113' => [0, 3, 7, 10, 17, 21],
        'min7913' => [0, 3, 7, 10, 14, 21],
    ];

    private const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    /**
     * Exposes QUALITIES_LEVEL{1,2,3} for build-time tooling (e.g.
     * scripts/audio/build_minor_chord_extensions.php, which derives each
     * question's "which extensions do you hear" answer from these same
     * interval lists) — single source of truth, no separate duplicated
     * table to drift out of sync.
     *
     * @return array<string, list<int>>
     */
    public static function qualitiesForLevel(int $level): array
    {
        return match ($level) {
            1 => self::QUALITIES_LEVEL1,
            2 => self::QUALITIES_LEVEL2,
            3 => self::QUALITIES_LEVEL3,
        };
    }

    /**
     * Each of the 17 chord qualities across 3 levels has 5 live-generated
     * voicing variants (v1-v5), built by ExtensionVoicings under 3 rules given
     * by the user:
     *  - a single chord tone may appear at most twice in a voicing;
     *  - at most 3 distinct tones may be doubled this way per voicing;
     *  - no two adjacent tones (once sorted bottom-to-top) may be more
     *    than 7 semitones apart — any wider gap gets filled by inserting
     *    the smallest-fitting next-octave copy of some other chord tone.
     * v1 starts with the close-position stack and fills any wide gaps. v2-v5 each try one
     * candidate move (double the root, double the 3rd, displace the 3rd
     * open, double the 5th, ...) in a fixed priority order, skipping to
     * the next candidate if one would be infeasible under the rules above
     * — deterministic, returning fewer than 5 when no further distinct
     * valid voicing is available.
     *
     * @return list<array{slug: string, degree: string, octave: int, offset: float}>|null
     */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (str_starts_with($path, 'plain-chord-extensions/')) {
            return PlainChordExtensionSamples::resolve($path, $instrument);
        }

        if (! preg_match('#^minor-chord-extensions/level([123])/([a-z-]+)/([a-z0-9-]+)\.wav$#', $path, $matches)) {
            return null;
        }
        $level = (int) $matches[1];
        $slug = $instrument ?? $matches[2];
        $parts = explode('-', $matches[3]);

        $key = array_search(array_shift($parts), self::KEYS, true);
        if ($key === false) {
            return null;
        }
        $quality = array_shift($parts);
        $variant = array_shift($parts);
        if ($parts !== [] || ! preg_match('/^v([12345])$/', (string) $variant, $m)) {
            return null;
        }

        $qualities = match ($level) {
            1 => self::QUALITIES_LEVEL1,
            2 => self::QUALITIES_LEVEL2,
            3 => self::QUALITIES_LEVEL3,
        };
        $intervals = $qualities[$quality] ?? null;
        if ($intervals === null) {
            return null;
        }

        $variants = ExtensionVoicings::forIntervals($intervals, 3);
        $pitches = $variants["v{$m[1]}"] ?? null;
        if ($pitches === null) {
            return null;
        }

        $base = 48 + $key;
        foreach ([0, 12, -12, 24, -24, 36, -36] as $shift) {
            $notes = [];
            foreach ($pitches as $pitch) {
                $note = NoteSample::fromRecordedMidiPitch($base + $pitch + $shift, $slug);
                if ($note === null) {
                    continue 2;
                }
                $notes[] = ['slug' => $slug, 'degree' => $note['degree'], 'octave' => $note['octave'], 'offset' => 0.0];
            }

            return $notes;
        }

        return null;
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
