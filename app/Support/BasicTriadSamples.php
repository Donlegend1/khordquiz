<?php

namespace App\Support;

use App\Models\QuizQuestion;

class BasicTriadSamples
{
    private const QUALITIES = [
        'major' => [0, 4, 7], 'minor' => [0, 3, 7], 'diminished' => [0, 3, 6],
        'augmented' => [0, 4, 8], 'sus2' => [0, 2, 7], 'sus4' => [0, 5, 7],
        'suspended' => [0, 2, 7],
    ];

    /**
     * Preserve keys, suspensions and voicings encoded in the audio filename.
     * Fit recorded ranges using whole-octave shifts of the complete chord.
     *
     * @return list<array{slug: string, degree: string, octave: int, offset: float}>|null
     */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^basic-triads/(?:level([2-8])/)?([a-z-]+)/([a-z0-9-]+)\.wav$#', $path, $matches)) {
            return null;
        }
        $level = ($matches[1] ?? '') === '' ? 1 : (int) $matches[1];
        $slug = $instrument ?? $matches[2];
        $parts = explode('-', $matches[3]);
        $key = 0;
        if (in_array($level, [2, 4, 6, 8], true)) {
            $key = array_search(array_shift($parts), ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'], true);
            if ($key === false) {
                return null;
            }
        }
        $quality = array_shift($parts);
        $intervals = self::QUALITIES[$quality] ?? null;
        $inversion = $level >= 5 ? array_shift($parts) : 'root';
        if ($intervals === null || $parts !== [] || ! in_array($inversion, ['root', '1st', '2nd'], true)
            || ($level < 5 && $quality === 'suspended')
            || ($level >= 5 && in_array($quality, ['sus2', 'sus4'], true))
            || (in_array($level, [6, 8], true) && ! in_array($quality, ['major', 'minor', 'diminished'], true))) {
            return null;
        }
        $drop2 = in_array($level, [3, 4, 7, 8], true);
        $base = ($drop2 || $slug === 'celesta' ? 60 : 48) + $key;
        $pitches = array_map(fn (int $interval): int => $base + $interval, $intervals);
        $rotations = ['root' => 0, '1st' => 1, '2nd' => 2][$inversion];
        for ($i = 0; $i < $rotations; $i++) {
            $pitches[] = array_shift($pitches) + 12;
        }
        if ($drop2) {
            $pitches[1] -= 12;
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
