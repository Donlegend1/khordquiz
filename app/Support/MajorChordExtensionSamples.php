<?php

namespace App\Support;

use App\Models\QuizQuestion;

class MajorChordExtensionSamples
{
    // Level 1 — triad + 1-2 color tones, no 7th ("add" chords).
    private const QUALITIES_LEVEL1 = [
        'maj6' => [0, 4, 7, 9],
        'maj7' => [0, 4, 7, 11],
        'majaddb9' => [0, 4, 7, 13],
        'majadd9' => [0, 4, 7, 14],
        'majadds11' => [0, 4, 7, 18],
        'majadd9s11' => [0, 4, 7, 14, 18],
    ];

    // Level 2 — 6-chords (6th instead of a 7th).
    private const QUALITIES_LEVEL2 = [
        'maj69' => [0, 4, 7, 9, 14],
        'maj6s11' => [0, 4, 7, 9, 18],
        'maj69s11' => [0, 4, 7, 9, 14, 18],
        'maj6b9s11' => [0, 4, 7, 9, 13, 18],
        'maj6b911' => [0, 4, 7, 9, 13, 17],
        'maj6911' => [0, 4, 7, 9, 14, 17],
    ];

    // Level 3 — 7-chords with extensions.
    private const QUALITIES_LEVEL3 = [
        'maj9' => [0, 4, 7, 11, 14],
        'maj7s11' => [0, 4, 7, 11, 18],
        'maj713' => [0, 4, 7, 11, 21],
        'maj9s11' => [0, 4, 7, 11, 14, 18],
        'maj139s11' => [0, 4, 7, 11, 14, 18, 21],
        'maj7s1113' => [0, 4, 7, 11, 18, 21],
        'maj7913' => [0, 4, 7, 11, 14, 21],
        'maj7b9s11' => [0, 4, 7, 11, 13, 18],
    ];

    private const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    /**
     * Exposes QUALITIES_LEVEL{1,2,3} for build-time tooling (e.g.
     * scripts/audio/build_major_chord_extensions.php, which derives each
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
     * Each of the 20 chord qualities across 3 levels has 5 live-generated
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

        if (! preg_match('#^major-chord-extensions/level([123])/([a-z-]+)/([a-z0-9-]+)\.wav$#', $path, $matches)) {
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

        $variants = ExtensionVoicings::forIntervals($intervals);
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
