<?php

namespace App\Support;

use App\Models\QuizQuestion;

class DiatonicProgressionSamples
{
    public const STEP_SECONDS = 1.6;

    public const QUALITIES = [1 => 'Major', 2 => 'Minor', 3 => 'Minor', 4 => 'Major', 5 => 'Major', 6 => 'Minor', 7 => 'Diminished'];

    public const PATTERNS = [
        '1564', '6415', '4156', '5641', '1645', '1415', '1451', '1251',
        '1473', '2514', '3625', '4736', '5147', '6251',
        '1324', '2435', '1453', '1364', '1625', '6425', '6345', '4325', '2345', '3425',
        '4563',
        '2564', '2415', '2645', '2536', '2451',
        '3645', '3415', '3251', '3614', '3451', '3641',
        '4516', '4251', '4625', '4165',
        '5164', '5624', '5362', '5416', '5124', '5642',
        '6245', '6541', '6451', '6145',
    ];

    protected const PATH_PREFIX = 'diatonic-progressions/all-keys-v1';

    protected const KEYS = CadenceSamples::KEYS;

    protected const INTERVALS = [1 => [0, 4, 7], 2 => [0, 3, 7], 3 => [0, 3, 7], 4 => [0, 4, 7], 5 => [0, 4, 7], 6 => [0, 3, 7], 7 => [0, 3, 6]];

    protected const SCALE = [1 => 0, 2 => 2, 3 => 4, 4 => 5, 5 => 7, 6 => 9, 7 => 11];

    private static array $voicingCache = [];

    /** @return list<list<int>> */
    protected static function candidates(int $key, int $degree, string $slug): array
    {
        $cacheKey = static::class.':'.$slug.':'.$key.':'.$degree;
        if (isset(static::$voicingCache[$cacheKey])) {
            return static::$voicingCache[$cacheKey];
        }
        [$low, $high] = CadenceSamples::preferredRegister($slug);
        $root = ($key + static::SCALE[$degree]) % 12;
        $intervals = static::INTERVALS[$degree];
        $classes = array_map(fn (int $tone): int => ($root + $tone) % 12, $intervals);
        $pitches = [];
        foreach (range($low, $high) as $pitch) {
            if (in_array($pitch % 12, $classes, true) && NoteSample::fromRecordedMidiPitch($pitch, $slug) !== null) {
                $pitches[] = $pitch;
            }
        }
        $voicings = [];
        $count = count($pitches);
        for ($a = 0; $a < $count; $a++) {
            for ($b = $a + 1; $b < $count; $b++) {
                for ($c = $b + 1; $c < $count; $c++) {
                    for ($d = $c + 1; $d < $count; $d++) {
                        $chord = [$pitches[$a], $pitches[$b], $pitches[$c], $pitches[$d]];
                        if ($chord[3] - $chord[0] <= 19 && count(array_unique(array_map(fn (int $pitch): int => $pitch % 12, $chord))) === count($classes)) {
                            $voicing = static::completeVoicing($chord, $slug, $high);
                            if ($voicing !== null) {
                                $voicings[] = $voicing;
                            }
                        }
                    }
                }
            }
        }

        return static::$voicingCache[$cacheKey] = $voicings;
    }

    /** @param list<int> $chord
     * @return list<int>|null
     */
    protected static function completeVoicing(array $chord, string $slug, int $high): ?array
    {
        return $chord;
    }

    /** @param list<list<int>> $candidates
     * @return list<list<int>>
     */
    protected static function firstChords(array $candidates, int $key, int $degree, int $variant, int $center): array
    {
        $root = ($key + static::SCALE[$degree]) % 12;
        $third = ($root + (in_array($degree, [2, 3, 6, 7], true) ? 3 : 4)) % 12;
        $fifth = ($root + ($degree === 7 ? 6 : 7)) % 12;
        $preferred = array_values(array_filter($candidates, function (array $chord) use ($variant, $root, $third, $fifth): bool {
            $span = $chord[3] - $chord[0];

            return match ($variant) {
                0 => $root === $chord[0] % 12 && $span === 12,
                1 => $span >= 14,
                2 => $third === $chord[0] % 12 && $span === 12,
                3 => $fifth === $chord[0] % 12 && $span === 12,
            };
        }));
        $score = function (array $chord) use ($variant, $root, $center, $degree): array {
            if ($variant === 1) {
                $rootCount = count(array_filter($chord, fn (int $pitch): bool => $pitch % 12 === $root));

                return [($root === $chord[0] % 12 && $rootCount === 2) ? 0 : 1, abs($chord[3] - $chord[0] - (in_array($degree, [2, 3, 6, 7], true) ? 15 : 16)), abs(($chord[0] + $chord[3]) / 2 - $center), $chord[0]];
            }

            return [abs(($chord[0] + $chord[3]) / 2 - $center), $chord[3] - $chord[0], $chord[0]];
        };
        usort($preferred, fn (array $a, array $b): int => $score($a) <=> $score($b));

        return $preferred;
    }

    /**
     * @param  list<int>  $previous
     * @param  list<list<list<int>>>  $remaining
     * @return list<list<int>>|null
     */
    protected static function closeSequence(array $previous, array $remaining, int $center): ?array
    {
        if ($remaining === []) {
            return [];
        }
        $scores = [];
        foreach ($remaining[0] as $chord) {
            $movement = array_map(fn (int $pitch, int $index): int => abs($pitch - $previous[$index]), $chord, array_keys($chord));
            if (max($movement) > 4) {
                continue;
            }
            $scores[] = ['chord' => $chord, 'score' => [max($movement), array_sum($movement), -count(array_intersect($previous, $chord)), abs($chord[0] - $previous[0]), abs($chord[3] - $previous[3]), abs(($chord[0] + $chord[3]) / 2 - $center)]];
        }
        usort($scores, fn (array $a, array $b): int => $a['score'] <=> $b['score']);
        foreach ($scores as $option) {
            $tail = static::closeSequence($option['chord'], array_slice($remaining, 1), $center);
            if ($tail !== null) {
                return [$option['chord'], ...$tail];
            }
        }

        return null;
    }

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^'.preg_quote(static::PATH_PREFIX, '#').'/([a-z-]+)/([a-z]+)-([1-7]{4})-v([0-3])\.wav$#', $path, $matches)) {
            return null;
        }
        $slug = $instrument ?? $matches[1];
        $register = CadenceSamples::preferredRegister($slug);
        $key = array_search($matches[2], static::KEYS, true);
        if ($register === null || $key === false || ! in_array($matches[3], static::PATTERNS, true)) {
            return null;
        }
        [$low, $high, $center] = $register;
        $degrees = array_map('intval', str_split($matches[3]));
        $remaining = array_map(fn (int $degree): array => static::candidates($key, $degree, $slug), array_slice($degrees, 1));
        $chords = null;
        foreach (static::firstChords(static::candidates($key, $degrees[0], $slug), $key, $degrees[0], (int) $matches[4], $center) as $first) {
            $tail = static::closeSequence($first, $remaining, $center);
            if ($tail !== null) {
                $chords = [$first, ...$tail];
                break;
            }
        }
        if ($chords === null) {
            return null;
        }

        return static::notesForChords($chords, $key, $slug, $register);
    }

    /** @param list<list<int>> $chords
     * @param  array{int, int, int}  $register
     * @return list<array{slug: string, degree: string, octave: int, offset: float}>|null
     */
    protected static function notesForChords(array $chords, int $key, string $slug, array $register): ?array
    {
        $anchor = static::tonicAnchor($chords, $key, $slug, $register);
        if ($anchor === null) {
            return null;
        }
        $events = [$anchor, [$anchor[0]], ...$chords];
        $notes = [];
        foreach ($events as $event => $chord) {
            foreach ($chord as $pitch) {
                $note = NoteSample::fromRecordedMidiPitch($pitch, $slug);
                $notes[] = ['slug' => $slug, ...$note, 'offset' => round($event * static::STEP_SECONDS, 3)];
            }
        }

        return $notes;
    }

    /** @param list<list<int>> $chords
     * @param  array{int, int, int}  $register
     * @return list<int>|null
     */
    protected static function tonicAnchor(array $chords, int $key, string $slug, array $register): ?array
    {
        $root = 48 + $key;
        $anchor = [$root, $root + 4, $root + 7];

        return array_all($anchor, fn (int $pitch): bool => NoteSample::fromRecordedMidiPitch($pitch, $slug) !== null) ? $anchor : null;
    }

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function forQuestion(QuizQuestion $question, string $slug): ?array
    {
        $path = $question->audioVariants->firstWhere('sound', 'Piano')?->audio_path
            ?? $question->audioVariants->first()?->audio_path ?? $question->audio_path;

        return $path ? static::resolve($path, $slug) : null;
    }

    /** @return list<array{key: string, recipe: string, degrees: list<int>, answer: string, samples: array<string, list<array{degree: string, octave: int, offset: float}>>}> */
    public static function manifest(): array
    {
        $rows = [];
        foreach (static::KEYS as $key) {
            foreach (static::PATTERNS as $pattern) {
                $degrees = array_map('intval', str_split($pattern));
                foreach (range(0, 3) as $variant) {
                    $samples = [];
                    $id = $key.'-'.$pattern.'-v'.$variant;
                    foreach (QuizCatalog::SOUND_SLUGS as $slug) {
                        $notes = static::resolve(static::PATH_PREFIX.'/'.$slug.'/'.$id.'.wav');
                        if ($notes !== null) {
                            $samples[$slug] = array_map(fn (array $note): array => array_diff_key($note, ['slug' => true]), $notes);
                        }
                    }
                    if ($samples === []) {
                        throw new \RuntimeException('No playable voicing for '.$id);
                    }
                    $rows[] = ['key' => $key, 'recipe' => $id, 'degrees' => $degrees,
                        'answer' => implode(' - ', array_map(fn (int $degree): string => $degree.' '.static::QUALITIES[$degree], $degrees)), 'samples' => $samples];
                }
            }
        }

        return $rows;
    }

    /** @param list<array{key: string, recipe: string, degrees: list<int>, answer: string, samples: array<string, list<array{degree: string, octave: int, offset: float}>>}> $manifest
     * @return list<array{recipe: string, degrees: list<int>, answer: string, samples: array<string, list<array{int, int}>>}>
     */
    public static function mobileManifest(array $manifest): array
    {
        $names = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];

        return array_map(function (array $row) use ($names): array {
            $samples = [];
            foreach ($row['samples'] as $slug => $notes) {
                $samples[$slug] = array_map(fn (array $note): array => [array_search($note['degree'], $names, true), $note['octave']], $notes);
            }

            return ['recipe' => $row['recipe'], 'degrees' => $row['degrees'], 'answer' => $row['answer'], 'samples' => $samples];
        }, $manifest);
    }
}
