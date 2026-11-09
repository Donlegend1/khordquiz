<?php

namespace App\Support;

use App\Models\QuizQuestion;

// Same 3 voicing techniques as EleventhDegreeChordSamples (ported from
// scripts/audio/gen_secondary_eleventh_voicings.php, which explicitly
// mirrors the 11th Degree Chords generators level-for-level) — only the
// quality set and path prefix differ, so this extends that class purely to
// reuse its resolveFor() voicing logic.
class SecondaryEleventhChordSamples extends EleventhDegreeChordSamples
{
    private const QUALITIES = [
        'maj9s5s11' => [0, 4, 8, 11, 14, 18],   // Major 9(♯5,♯11)
        'dim7b911' => [0, 3, 6, 9, 13, 17],     // Diminished 7(♭9,11)
        'minmaj11' => [0, 3, 7, 11, 14, 17],    // Minor-Major 11th
        'aug9s11' => [0, 4, 8, 10, 14, 18],     // Augmented 9(♯11)
    ];

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^secondary-eleventh-chords/level([1-6])/([a-z-]+)/([A-Za-z0-9-]+)\.wav$#', $path, $matches)) {
            return null;
        }

        return self::resolveFor(self::QUALITIES, (int) $matches[1], $instrument ?? $matches[2], $matches[3]);
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
