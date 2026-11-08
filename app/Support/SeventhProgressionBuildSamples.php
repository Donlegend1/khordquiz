<?php

namespace App\Support;

class SeventhProgressionBuildSamples extends DiatonicSeventhProgressionSamples
{
    protected const PATH_PREFIX = 'progression-build-two/c-major-v1';

    protected const KEYS = [0 => 'c', 1 => 'db', 2 => 'd', 3 => 'eb', 4 => 'e', 5 => 'f', 6 => 'gb', 7 => 'g', 8 => 'ab', 9 => 'a', 10 => 'bb', 11 => 'b'];

    public const LABELS = [1 => '1 Major 7th', 2 => '2 Minor 7th', 3 => '3 Minor 7th', 4 => '4 Major 7th', 5 => '5 Dominant 7th', 6 => '6 Minor 7th', 7 => '7 Minor 7(♭5)', 8 => '2 Dominant 7th', 9 => '2 Minor 7(♭5)', 10 => '3 Dominant 7th', 11 => '3 Minor 7(♭5)', 12 => '4 Dominant 7th', 13 => '5 Minor 7th', 14 => '6 Dominant 7th', 15 => '6 Minor 7(♭5)', 16 => '7 Dominant 7th', 17 => '1 Dominant 7th', 18 => '1 7 Sus4', 19 => '2 7 Sus4', 20 => '3 7 Sus4', 21 => '4 7 Sus4', 22 => '5 7 Sus4', 23 => '6 7 Sus4', 24 => '7 7 Sus4'];

    public const DESTINATIONS = [5 => [1], 8 => [5, 4], 9 => [1], 10 => [4, 6], 11 => [4, 9, 14], 12 => [17, 11], 13 => [17], 14 => [2, 8], 15 => [9], 16 => [3], 17 => [4], 18 => [17], 19 => [8], 20 => [10], 21 => [12], 22 => [5], 23 => [14], 24 => [16]];

    protected const SCALE = [1 => 0, 2 => 2, 3 => 4, 4 => 5, 5 => 7, 6 => 9, 7 => 11, 8 => 2, 9 => 2, 10 => 4, 11 => 4, 12 => 5, 13 => 7, 14 => 9, 15 => 9, 16 => 11, 17 => 0, 18 => 0, 19 => 2, 20 => 4, 21 => 5, 22 => 7, 23 => 9, 24 => 11];

    protected const INTERVALS = [1 => [0, 4, 7, 11], 2 => [0, 3, 7, 10], 3 => [0, 3, 7, 10], 4 => [0, 4, 7, 11], 5 => [0, 4, 7, 10], 6 => [0, 3, 7, 10], 7 => [0, 3, 6, 10], 8 => [0, 4, 7, 10], 9 => [0, 3, 6, 10], 10 => [0, 4, 7, 10], 11 => [0, 3, 6, 10], 12 => [0, 4, 7, 10], 13 => [0, 3, 7, 10], 14 => [0, 4, 7, 10], 15 => [0, 3, 6, 10], 16 => [0, 4, 7, 10], 17 => [0, 4, 7, 10], 18 => [0, 5, 7, 10], 19 => [0, 5, 7, 10], 20 => [0, 5, 7, 10], 21 => [0, 5, 7, 10], 22 => [0, 5, 7, 10], 23 => [0, 5, 7, 10], 24 => [0, 5, 7, 10]];

    public const PATTERNS = [
        [2, 8, 5, 1],
        [1, 2, 8, 4],
        [3, 2, 9, 1],
        [5, 1, 10, 4],
        [4, 1, 10, 6],
        [6, 1, 11, 4],
        [11, 9, 1, 2],
        [1, 11, 14, 2],
        [12, 17, 4, 1],
        [2, 12, 11, 4],
        [13, 17, 4, 1],
        [14, 2, 1, 3],
        [3, 14, 8, 4],
        [15, 9, 1, 2],
        [4, 1, 16, 3],
        [17, 4, 1, 2],
        [8, 4, 1, 2],
        [5, 1, 8, 4],
        [6, 1, 8, 4],
        [8, 4, 1, 3],
        [9, 1, 2, 3],
        [9, 1, 2, 4],
        [2, 3, 9, 1],
        [3, 4, 9, 1],
        [10, 4, 1, 2],
        [10, 4, 1, 3],
        [1, 2, 10, 4],
        [4, 2, 10, 6],
        [5, 1, 10, 6],
        [11, 4, 1, 2],
        [6, 2, 11, 4],
        [11, 4, 1, 3],
        [12, 11, 4, 1],
        [12, 11, 4, 2],
        [1, 12, 11, 4],
        [2, 12, 17, 4],
        [3, 12, 11, 4],
        [13, 17, 4, 2],
        [13, 17, 4, 3],
        [6, 13, 17, 4],
        [13, 17, 4, 6],
        [4, 13, 17, 4],
        [1, 13, 17, 4],
        [14, 2, 1, 4],
        [14, 2, 1, 6],
        [5, 1, 14, 2],
        [14, 2, 1, 7],
        [15, 9, 1, 3],
        [15, 9, 1, 4],
        [15, 9, 1, 6],
        [2, 15, 9, 1],
        [3, 15, 9, 1],
        [4, 15, 9, 1],
        [5, 1, 16, 3],
        [6, 1, 16, 3],
        [1, 2, 16, 3],
        [2, 1, 16, 3],
        [4, 2, 16, 3],
        [6, 2, 16, 3],
        [17, 4, 1, 3],
        [8, 4, 1, 6],
        [9, 1, 2, 6],
        [10, 4, 1, 6],
        [17, 4, 1, 6],
        [8, 4, 1, 7],
        [9, 1, 2, 7],
        [10, 4, 1, 7],
        [11, 4, 1, 6],
        [17, 4, 1, 7],
        [8, 4, 2, 1],
        [1, 18, 17, 4],
        [2, 18, 17, 4],
        [3, 18, 17, 4],
        [6, 18, 17, 4],
        [18, 17, 4, 1],
        [1, 19, 8, 4],
        [2, 19, 8, 4],
        [3, 19, 8, 4],
        [6, 19, 8, 4],
        [1, 20, 10, 4],
        [1, 20, 10, 6],
        [2, 20, 10, 4],
        [21, 12, 11, 4],
        [21, 12, 17, 4],
        [1, 23, 14, 2],
        [3, 23, 14, 2],
        [4, 23, 14, 2],
        [1, 24, 16, 3],
        [2, 24, 16, 3],
        [4, 24, 16, 3],
    ];

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
