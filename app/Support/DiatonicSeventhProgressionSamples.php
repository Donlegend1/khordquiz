<?php

namespace App\Support;

class DiatonicSeventhProgressionSamples extends DiatonicProgressionSamples
{
    public const QUALITIES = [1 => 'Major 7th', 2 => 'Minor 7th', 3 => 'Minor 7th', 4 => 'Major 7th', 5 => 'Dominant 7th', 6 => 'Minor 7th', 7 => 'Minor 7(♭5)'];

    protected const PATH_PREFIX = 'diatonic-seventh-progressions/c-major-v1';

    protected const KEYS = [0 => 'c', 1 => 'db', 2 => 'd', 3 => 'eb', 4 => 'e', 5 => 'f', 6 => 'gb', 7 => 'g', 8 => 'ab', 9 => 'a', 10 => 'bb', 11 => 'b'];

    protected const INTERVALS = [1 => [0, 4, 7, 11], 2 => [0, 3, 7, 10], 3 => [0, 3, 7, 10], 4 => [0, 4, 7, 11], 5 => [0, 4, 7, 10], 6 => [0, 3, 7, 10], 7 => [0, 3, 6, 10]];

    protected static function completeVoicing(array $chord, string $slug, int $high): ?array
    {
        $melody = $chord[1] + 12;
        while ($melody <= $chord[3]) {
            $melody += 12;
        }
        if ($melody > $high || NoteSample::fromRecordedMidiPitch($melody, $slug) === null) {
            return null;
        }

        return [...$chord, $melody];
    }

    protected static function firstChords(array $candidates, int $key, int $degree, int $variant, int $center): array
    {
        $root = ($key + static::SCALE[$degree]) % 12;
        $intervals = static::INTERVALS[$degree];
        $bass = ($root + $intervals[match ($variant) {
            0, 1 => 0, 2 => 1, 3 => 2
        }]) % 12;
        $preferred = array_values(array_filter($candidates, fn (array $chord): bool => $variant === 1 ? $chord[3] - $chord[0] >= 14 : ($bass === $chord[0] % 12 && $chord[3] - $chord[0] < 12)));
        usort($preferred, fn (array $a, array $b): int => [abs(($a[0] + $a[3]) / 2 - $center), $a[3] - $a[0], $a[0]] <=> [abs(($b[0] + $b[3]) / 2 - $center), $b[3] - $b[0], $b[0]]);

        $alternatives = array_values(array_filter($candidates, fn (array $chord): bool => ! in_array($chord, $preferred, true)));
        usort($alternatives, fn (array $a, array $b): int => [abs(($a[0] + $a[4]) / 2 - $center), $a[4] - $a[0], $a[0]] <=> [abs(($b[0] + $b[4]) / 2 - $center), $b[4] - $b[0], $b[0]]);

        return [...$preferred, ...$alternatives];
    }
}
