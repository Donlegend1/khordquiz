<?php

namespace App\Support;

use App\Models\QuizQuestion;

class DominantChordExtensionSamples
{
    private const QUALITIES_LEVEL1 = [
        'dom7b9s11' => [0, 4, 7, 10, 13, 18],
        'dom7b913' => [0, 4, 7, 10, 13, 21],
        'dom7b9b13' => [0, 4, 7, 10, 13, 20],
        'dom7s9s11' => [0, 4, 7, 10, 15, 18],
        'dom7s9b13' => [0, 4, 7, 10, 15, 20],
        'dom7b9s1113' => [0, 4, 7, 10, 13, 18, 21],
        'dom7b9s9b13' => [0, 4, 7, 10, 13, 15, 20],
    ];

    private const QUALITIES_LEVEL2 = [
        'dom79s11' => [0, 4, 7, 10, 14, 18],
        'dom7913' => [0, 4, 7, 10, 14, 21],
        'dom79b13' => [0, 4, 7, 10, 14, 20],
        'dom7911' => [0, 4, 7, 10, 14, 17],
        'dom7s1113' => [0, 4, 7, 10, 18, 21],
        'dom79s1113' => [0, 4, 7, 10, 14, 18, 21],
    ];

    private const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    /** @return array<string, list<int>> */
    public static function qualitiesForLevel(int $level): array
    {
        return match ($level) {
            1 => self::QUALITIES_LEVEL1,
            2 => self::QUALITIES_LEVEL2,
            default => [],
        };
    }

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (str_starts_with($path, 'plain-chord-extensions/dominant/')) {
            return PlainChordExtensionSamples::resolve($path, $instrument);
        }
        if (! preg_match('#^dominant-chord-extensions/level([12])/([a-z-]+)/([a-z0-9-]+)\.wav$#', $path, $matches)) {
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
            default => [],
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
