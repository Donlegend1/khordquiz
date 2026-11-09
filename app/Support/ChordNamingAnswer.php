<?php

namespace App\Support;

class ChordNamingAnswer
{
    public const QUALITIES = ['Major', 'Minor', 'Augmented', 'Diminished', 'Sus2', 'Sus4'];

    public const DEGREES = ['♭5', '♯5', '6', '♭7', '7', 'maj7', '♭9', '9', '♯9', '♭11', '11', '♯11', '♭13', '13', '♯13'];

    /** @return array{quality: string, degrees: list<string>, exclude: string}|null */
    public static function decode(string $value): ?array
    {
        $parts = explode('|', $value);
        $modern = $parts[0] === 'v2';
        if ($modern) {
            array_shift($parts);
        }
        if (count($parts) !== 3) {
            return null;
        }
        [$quality, $text, $exclude] = $parts;
        $degrees = $text === '' ? [] : explode(',', $text);
        if (! $modern) {
            if ($quality === 'Minor') {
                $degrees = array_map(fn (string $degree): string => $degree === '7' ? '♭7' : $degree, $degrees);
            }
            if ($quality === 'Dominant') {
                $quality = 'Major';
                $degrees = [...array_diff($degrees, ['7']), '♭7'];
            }
            $suspension = array_values(array_intersect($degrees, ['Sus 2', 'Sus 4']))[0] ?? null;
            if ($suspension !== null) {
                if ($quality === 'Augmented') {
                    $degrees[] = '♯5';
                } elseif ($quality === 'Diminished') {
                    $degrees[] = '♭5';
                }
                $quality = str_replace(' ', '', $suspension);
                $degrees = array_values(array_diff($degrees, ['Sus 2', 'Sus 4']));
            }
        }
        if (! in_array($quality, self::QUALITIES, true) || array_diff($degrees, self::DEGREES) !== [] || ! in_array($exclude, ['None', 'No 3', 'No 5'], true)) {
            return null;
        }

        return ['quality' => $quality, 'degrees' => array_values(array_intersect(self::DEGREES, $degrees)), 'exclude' => $exclude];
    }

    public static function isValid(string $value): bool
    {
        $answer = self::decode($value);
        if ($answer === null || $answer['degrees'] === []) {
            return false;
        }
        $degrees = $answer['degrees'];

        return count(array_intersect(['7', '♭7', 'maj7'], $degrees)) <= 1
            && (! in_array('maj7', $degrees, true) || $answer['quality'] === 'Diminished')
            && ! (in_array('♭5', $degrees, true) && in_array('♯5', $degrees, true))
            && ! (str_starts_with($answer['quality'], 'Sus') && in_array('6', $degrees, true))
            && ($answer['exclude'] === 'None' || array_intersect(['7', '♭7', 'maj7'], $degrees) !== []);
    }

    public static function matches(string $submitted, string $correct): bool
    {
        return self::isValid($submitted) && self::isValid($correct)
            && self::decode($submitted)['exclude'] === self::decode($correct)['exclude']
            && (self::decode($correct)['exclude'] === 'None'
                ? self::canonical(self::decode($submitted)) === self::canonical(self::decode($correct))
                : self::intervals($submitted) === self::intervals($correct));
    }

    /**
     * @param  array{quality: string, degrees: list<string>, exclude: string}  $answer
     * @return array{quality: string, degrees: list<string>, exclude: string}
     */
    private static function canonical(array $answer): array
    {
        $degrees = $answer['degrees'];
        if ($answer['quality'] === 'Augmented' && in_array('7', $degrees, true)) {
            $answer['quality'] = 'Major';
            $degrees[] = '♯5';
        } elseif ($answer['quality'] === 'Major' && in_array('♯5', $degrees, true) && in_array('♭7', $degrees, true)) {
            $answer['quality'] = 'Augmented';
            $degrees = array_diff($degrees, ['♯5']);
        } elseif ($answer['quality'] === 'Minor' && in_array('♭5', $degrees, true) && in_array('7', $degrees, true)) {
            $answer['quality'] = 'Diminished';
            $degrees = [...array_diff($degrees, ['♭5', '7']), 'maj7'];
        } elseif ($answer['quality'] === 'Diminished' && in_array('♭7', $degrees, true)) {
            $answer['quality'] = 'Minor';
            $degrees[] = '♭5';
        }
        $answer['degrees'] = array_values(array_intersect(self::DEGREES, $degrees));

        return $answer;
    }

    /** @return list<int>|null */
    public static function intervals(string $value): ?array
    {
        if (! self::isValid($value)) {
            return null;
        }
        $answer = self::decode($value);
        $tones = match ($answer['quality']) {
            'Major' => [0, 4, 7],
            'Minor' => [0, 3, 7],
            'Augmented' => [0, 4, 8],
            'Diminished' => [0, 3, 6],
            'Sus2' => [0, 2, 7],
            'Sus4' => [0, 5, 7],
        };
        $degrees = $answer['degrees'];
        if (in_array('♭5', $degrees, true)) {
            $tones[2] = 6;
        } elseif (in_array('♯5', $degrees, true)) {
            $tones[2] = 8;
        }
        if ($answer['exclude'] === 'No 3') {
            unset($tones[1]);
        } elseif ($answer['exclude'] === 'No 5') {
            unset($tones[2]);
        }
        $mapping = ['6' => 9, '♭7' => 10, '7' => $answer['quality'] === 'Diminished' ? 9 : 11, 'maj7' => 11,
            '♭9' => 13, '9' => 14, '♯9' => 15, '♭11' => 16, '11' => 17, '♯11' => 18, '♭13' => 20, '13' => 21, '♯13' => 22];
        foreach ($degrees as $degree) {
            if (isset($mapping[$degree])) {
                $tones[] = $mapping[$degree];
            }
        }
        $tones = array_values(array_unique($tones));
        sort($tones);

        return $tones;
    }
}
