<?php

namespace App\Support;

use App\Models\QuizQuestion;

class SeventhDegreeChordSamples
{
    private const QUALITIES = [
        'diminished7' => [0, 3, 6, 9],
        'dominant7' => [0, 4, 7, 10],
        'm7b5' => [0, 3, 6, 10],
        'major7' => [0, 4, 7, 11],
        'minor7' => [0, 3, 7, 10],
    ];

    private const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    private const INVERSIONS = ['root' => 0, '1st' => 1, '2nd' => 2, '3rd' => 3];

    /**
     * Mirrors BasicTriadSamples::resolve() for 7th Degree Chords Levels 1-8
     * (see scripts/audio/gen_seventh_voicings.php — same structure: Levels
     * 1-4 root position, 5-8 all 4 rotated positions; Levels 3/4/7/8 drop-2;
     * Levels 6/8 exclude Diminished 7th, a fully symmetric chord whose
     * inversions are indistinguishable without a key reference.
     *
     * @return list<array{slug: string, degree: string, octave: int, offset: float}>|null
     */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^seventh-degree-chords/level([1-8])/([a-z-]+)/([a-z0-9-]+)\.wav$#', $path, $matches)) {
            return null;
        }
        $level = (int) $matches[1];
        $slug = $instrument ?? $matches[2];
        $parts = explode('-', $matches[3]);
        $key = 5;
        if (in_array($level, [2, 4, 6, 8], true)) {
            $key = array_search(array_shift($parts), self::KEYS, true);
            if ($key === false) {
                return null;
            }
        }
        $quality = array_shift($parts);
        $intervals = self::QUALITIES[$quality] ?? null;
        $inversion = $level >= 5 ? array_shift($parts) : 'root';
        if ($intervals === null || $parts !== [] || ! isset(self::INVERSIONS[$inversion])
            || (in_array($level, [6, 8], true) && $quality === 'diminished7')) {
            return null;
        }
        $drop2 = in_array($level, [3, 4, 7, 8], true);
        $base = ($drop2 || $slug === 'celesta' ? 60 : 48) + $key;
        $pitches = array_map(fn (int $interval): int => $base + $interval, $intervals);
        for ($i = 0; $i < self::INVERSIONS[$inversion]; $i++) {
            $pitches[] = array_shift($pitches) + 12;
        }
        if ($drop2) {
            // Drop the 2nd-from-top note (index n-2 of 4) an octave — same
            // rule as chordalDrop2() in chordal_melodies_pool.php, applied
            // after rotation so it targets the current voicing's own
            // 2nd-from-top note, not always the chord's original 3rd degree.
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
