<?php

namespace App\Support;

class ProgressionBuildSamples extends DiatonicProgressionSamples
{
    protected const PATH_PREFIX = 'progression-build-one/f-major-v1';

    protected const KEYS = [5 => 'f', 0 => 'c', 1 => 'db', 2 => 'd', 3 => 'eb', 4 => 'e', 6 => 'gb', 7 => 'g', 8 => 'ab', 9 => 'a', 10 => 'bb', 11 => 'b'];

    public const LABELS = [1 => '1 Major', 2 => '2 Minor', 3 => '3 Minor', 4 => '4 Major', 5 => '5 Major', 6 => '6 Minor', 7 => '7 Diminished', 8 => '2 Major', 9 => '3 Major', 10 => '4 Minor', 11 => '5 Minor', 12 => '6 Major', 13 => '♭7 Major', 14 => '1 Diminished', 15 => '2 Diminished', 16 => '3 Diminished', 17 => '4 Diminished', 18 => '5 Diminished', 19 => '6 Diminished', 26 => '1 Sus4', 27 => '2 Sus4', 28 => '3 Sus4', 29 => '4 Sus4', 30 => '5 Sus4', 31 => '6 Sus4'];

    public const DESTINATIONS = [8 => [5, 4], 9 => [4, 6], 10 => [1], 11 => [4], 12 => [2, 4], 13 => [4, 5, 1], 14 => [1], 15 => [1, 6], 16 => [4, 10, 15], 17 => [1, 6], 18 => [4, 2], 19 => [5, 1], 7 => [1], 26 => [1], 27 => [8], 28 => [9], 29 => [4], 30 => [5], 31 => [12]];

    protected const SCALE = [1 => 0, 2 => 2, 3 => 4, 4 => 5, 5 => 7, 6 => 9, 7 => 11, 8 => 2, 9 => 4, 10 => 5, 11 => 7, 12 => 9, 13 => 10, 14 => 0, 15 => 2, 16 => 4, 17 => 5, 18 => 7, 19 => 9, 26 => 0, 27 => 2, 28 => 4, 29 => 5, 30 => 7, 31 => 9];

    protected const INTERVALS = [1 => [0, 4, 7], 2 => [0, 3, 7], 3 => [0, 3, 7], 4 => [0, 4, 7], 5 => [0, 4, 7], 6 => [0, 3, 7], 7 => [0, 3, 6], 8 => [0, 4, 7], 9 => [0, 4, 7], 10 => [0, 3, 7], 11 => [0, 3, 7], 12 => [0, 4, 7], 13 => [0, 4, 7], 14 => [0, 3, 6], 15 => [0, 3, 6], 16 => [0, 3, 6], 17 => [0, 3, 6], 18 => [0, 3, 6], 19 => [0, 3, 6], 26 => [0, 5, 7], 27 => [0, 5, 7], 28 => [0, 5, 7], 29 => [0, 5, 7], 30 => [0, 5, 7], 31 => [0, 5, 7]];

    public const PATTERNS = [
        [1, 8, 5, 1],
        [6, 8, 5, 1],
        [4, 8, 5, 1],
        [2, 8, 5, 1],
        [1, 8, 4, 5],
        [6, 8, 4, 1],
        [1, 9, 6, 4],
        [4, 9, 6, 2],
        [2, 9, 6, 5],
        [6, 9, 4, 1],
        [1, 9, 4, 5],
        [5, 9, 6, 2],
        [1, 4, 10, 1],
        [6, 4, 10, 1],
        [2, 5, 10, 1],
        [13, 4, 10, 1],
        [9, 4, 10, 1],
        [1, 11, 4, 1],
        [6, 11, 4, 5],
        [2, 11, 4, 1],
        [3, 11, 4, 1],
        [1, 12, 2, 5],
        [4, 12, 2, 5],
        [6, 12, 4, 1],
        [5, 12, 4, 1],
        [12, 4, 10, 1],
        [1, 13, 4, 1],
        [6, 13, 5, 1],
        [2, 13, 1, 4],
        [4, 13, 5, 6],
        [3, 13, 4, 5],
        [13, 1, 8, 5],
        [4, 14, 1, 5],
        [6, 14, 1, 4],
        [1, 15, 6, 5],
        [4, 15, 1, 6],
        [8, 4, 15, 1],
        [1, 16, 4, 5],
        [6, 16, 10, 1],
        [16, 15, 6, 4],
        [1, 17, 6, 2],
        [5, 17, 1, 4],
        [13, 4, 17, 1],
        [1, 18, 4, 5],
        [6, 18, 2, 5],
        [4, 19, 5, 1],
        [2, 19, 1, 4],
        [9, 6, 7, 1],
        [12, 2, 7, 1],
        [11, 4, 7, 1],
        68 => [13, 4, 26, 1],
        69 => [9, 4, 26, 1],
        70 => [27, 8, 5, 1],
        71 => [27, 8, 4, 5],
        72 => [28, 9, 6, 4],
        73 => [28, 9, 6, 2],
        74 => [29, 4, 10, 1],
        75 => [29, 4, 15, 1],
        76 => [30, 5, 10, 1],
        77 => [13, 1, 30, 5],
        78 => [31, 12, 2, 5],
        79 => [31, 12, 4, 1],
        80 => [8, 5, 26, 1],
        81 => [12, 4, 26, 1],
        82 => [27, 8, 5, 6],
        83 => [27, 8, 4, 1],
        84 => [28, 9, 4, 1],
        85 => [28, 9, 4, 5],
        86 => [29, 4, 17, 1],
        87 => [30, 5, 17, 1],
    ];

    public static function answersMatch(string $submitted, string $correct): bool
    {
        $submittedParts = explode(' - ', $submitted);
        $correctParts = explode(' - ', $correct);
        if (count($submittedParts) !== 4 || count($correctParts) !== 4) {
            return false;
        }
        foreach ($correctParts as $index => $chord) {
            if ($submittedParts[$index] === $chord) {
                continue;
            }
            $expected = static::suspendedPitches($chord);
            if ($expected === null || $expected !== static::suspendedPitches($submittedParts[$index])) {
                return false;
            }
        }

        return true;
    }

    private static function suspendedPitches(string $chord): ?array
    {
        if (! preg_match('/^(♭7|[1-7]) Sus ?([24])$/u', $chord, $matches)) {
            return null;
        }
        $root = ['1' => 0, '2' => 2, '3' => 4, '4' => 5, '5' => 7, '6' => 9, '7' => 11, '♭7' => 10][$matches[1]];
        $pitches = [$root, ($root + ($matches[2] === '2' ? 2 : 5)) % 12, ($root + 7) % 12];
        sort($pitches);

        return $pitches;
    }

    protected static function firstChords(array $candidates, int $key, int $degree, int $variant, int $center): array
    {
        $root = ($key + static::SCALE[$degree]) % 12;
        $intervals = static::INTERVALS[$degree];
        $bass = ($root + $intervals[match ($variant) {
            0,1 => 0, 2 => 1, 3 => 2
        }]) % 12;
        $score = fn (array $chord): array => [($variant === 1 ? $chord[3] - $chord[0] >= 14 : ($bass === $chord[0] % 12 && $chord[3] - $chord[0] === 12)) ? 0 : 1, abs(($chord[0] + $chord[3]) / 2 - $center), $chord[0]];
        usort($candidates, fn (array $a, array $b): int => $score($a) <=> $score($b));

        return $candidates;
    }

    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^'.preg_quote(static::PATH_PREFIX, '#').'/([a-z-]+)/([a-z]+)-p([0-9]{3})-v([0-3])\.wav$#', $path, $matches)) {
            return null;
        }
        $slug = $instrument ?? $matches[1];
        $register = CadenceSamples::preferredRegister($slug);
        $key = array_search($matches[2], static::KEYS, true);
        $pattern = static::PATTERNS[(int) $matches[3] - 1] ?? null;
        if ($register === null || $key === false || $pattern === null) {
            return null;
        }
        $remaining = array_map(fn (int $chord): array => static::candidates($key, $chord, $slug), array_slice($pattern, 1));
        foreach (static::firstChords(static::candidates($key, $pattern[0], $slug), $key, $pattern[0], (int) $matches[4], $register[2]) as $first) {
            $tail = static::closeSequence($first, $remaining, $register[2]);
            if ($tail !== null) {
                return static::notesForChords([$first, ...$tail], $key, $slug, $register);
            }
        }

        return null;
    }

    public static function manifest(): array
    {
        $rows = [];
        foreach (static::KEYS as $keyName) {
            foreach (static::PATTERNS as $index => $pattern) {
                $variant = $index % 4;
                $recipe = sprintf('%s-p%03d-v%d', $keyName, $index + 1, $variant);
                $samples = [];
                foreach (QuizCatalog::SOUND_SLUGS as $slug) {
                    $notes = static::resolve(static::PATH_PREFIX.'/'.$slug.'/'.$recipe.'.wav');
                    if ($notes !== null) {
                        $samples[$slug] = array_map(fn (array $note): array => array_diff_key($note, ['slug' => true]), $notes);
                    }
                }
                if ($samples === []) {
                    throw new \RuntimeException('No playable voicing for '.$recipe);
                }
                $labels = array_map(fn (int $chord): string => static::LABELS[$chord], $pattern);
                $rows[] = ['key' => $keyName, 'recipe' => $recipe, 'degrees' => array_map(fn (string $label): string => explode(' ', $label)[0], $labels), 'answer' => implode(' - ', $labels), 'samples' => $samples];
            }

        }

        return $rows;
    }
}
