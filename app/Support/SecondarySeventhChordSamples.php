<?php

namespace App\Support;

use App\Models\QuizQuestion;

class SecondarySeventhChordSamples
{
    private const QUALITIES = [
        'aug7' => [0, 4, 8, 10],       // Augmented 7th (augmented triad + m7)
        'minmaj7' => [0, 3, 7, 11],    // Minor-Major 7th
        'dimmaj7' => [0, 3, 6, 11],    // Diminished-Major 7th
        'dom7b5' => [0, 4, 6, 10],     // Dominant 7(b5)
        'maj7s5' => [0, 4, 8, 11],     // Major 7(#5)
        'maj7b5' => [0, 4, 6, 11],     // Major 7(b5)
    ];

    // Level 9 ("General") is a mixed-review pool spanning both categories —
    // 7th Degree Chords' 5 qualities plus this category's own 6, with no
    // exclusions (see gen_secondary_seventh_general_voicings.php).
    private const QUALITIES_GENERAL = [
        'diminished7' => [0, 3, 6, 9],
        'dominant7' => [0, 4, 7, 10],
        'm7b5' => [0, 3, 6, 10],
        'major7' => [0, 4, 7, 11],
        'minor7' => [0, 3, 7, 10],
        'aug7' => [0, 4, 8, 10],
        'minmaj7' => [0, 3, 7, 11],
        'dimmaj7' => [0, 3, 6, 11],
        'dom7b5' => [0, 4, 6, 10],
        'maj7s5' => [0, 4, 8, 11],
        'maj7b5' => [0, 4, 6, 11],
    ];

    private const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    private const INVERSIONS = ['root' => 0, '1st' => 1, '2nd' => 2, '3rd' => 3];

    /**
     * Mirrors SeventhDegreeChordSamples::resolve() for Secondary 7th Chords
     * Levels 1-8 (see scripts/audio/gen_secondary_seventh_voicings.php) plus
     * Level 9 "General" (gen_secondary_seventh_general_voicings.php) — key +
     * quality + inversion always, close-position only, all 11 qualities
     * pooled, no exclusions. Levels 6/8 exclude Dominant 7(b5) — a
     * tritone-symmetric chord whose inversions are indistinguishable
     * without a key reference, same reasoning as 7th Degree Chords
     * excluding Diminished 7th from its own Levels 6/8.
     *
     * @return list<array{slug: string, degree: string, octave: int, offset: float}>|null
     */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^secondary-seventh-chords/level([1-9])/([a-z-]+)/([a-z0-9-]+)\.wav$#', $path, $matches)) {
            return null;
        }
        $level = (int) $matches[1];
        $slug = $instrument ?? $matches[2];
        $parts = explode('-', $matches[3]);

        $key = 5;
        if (in_array($level, [2, 4, 6, 8, 9], true)) {
            $key = array_search(array_shift($parts), self::KEYS, true);
            if ($key === false) {
                return null;
            }
        }

        $quality = array_shift($parts);
        $qualities = $level === 9 ? self::QUALITIES_GENERAL : self::QUALITIES;
        $intervals = $qualities[$quality] ?? null;
        $inversion = $level >= 5 ? array_shift($parts) : 'root';
        if ($intervals === null || $parts !== [] || ! isset(self::INVERSIONS[$inversion])
            || (in_array($level, [6, 8], true) && $quality === 'dom7b5')) {
            return null;
        }

        $drop2 = in_array($level, [3, 4, 7, 8], true);
        $base = ($drop2 || $slug === 'celesta' ? 60 : 48) + $key;
        $pitches = array_map(fn (int $interval): int => $base + $interval, $intervals);
        for ($i = 0; $i < self::INVERSIONS[$inversion]; $i++) {
            $pitches[] = array_shift($pitches) + 12;
        }
        if ($drop2) {
            $pitches[2] -= 12;
            sort($pitches);
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

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function forQuestion(QuizQuestion $question, string $slug): ?array
    {
        $variants = $question->audioVariants;
        $path = $variants->firstWhere('sound', 'Piano')?->audio_path
            ?? $variants->first()?->audio_path ?? $question->audio_path;

        return $path ? self::resolve($path, $slug) : null;
    }
}
