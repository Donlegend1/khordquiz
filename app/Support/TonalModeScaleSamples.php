<?php

namespace App\Support;

class TonalModeScaleSamples
{
    public const CATEGORY = 'Scales';

    public const TITLE = 'Tonal Modes #1';

    public const PREVIOUS_TITLE = 'Tonal Modes';

    public const MOBILE_TITLE = 'Scales · Level 1';

    public const PREFIX = 'scales/tonal-modes-f-v1';

    public const NOTE_SPACING = 0.4;

    public const TONIC_MIDI = 53;

    public const KEYS = ['f' => 0];

    /** Melodic minor uses its ascending/jazz form in both directions. */
    public const SCALES = [
        'ionian' => ['Ionian', [0, 2, 4, 5, 7, 9, 11]],
        'harmonic-major' => ['Harmonic Major', [0, 2, 4, 5, 7, 8, 11]],
        'harmonic-minor' => ['Harmonic Minor', [0, 2, 3, 5, 7, 8, 11]],
        'melodic-minor' => ['Melodic Minor', [0, 2, 3, 5, 7, 9, 11]],
        'aeolian' => ['Aeolian', [0, 2, 3, 5, 7, 8, 10]],
    ];

    public static function choices(): array
    {
        return array_column(static::SCALES, 0);
    }

    public static function sounds(): array
    {
        return QuizCatalog::allInstruments();
    }

    public static function preferredRegister(string $slug): ?array
    {
        return match ($slug) {
            'bass-guitar', 'upright-bass' => [28, 55, 43],
            'tuba' => [29, 55, 41],
            default => CadenceSamples::preferredRegister($slug),
        };
    }

    public static function voicings(): array
    {
        return array_map(fn (array $scale): array => [$scale[0], array_map(fn (int $interval): int => static::TONIC_MIDI + $interval, [...$scale[1], 12])], static::SCALES);
    }

    public static function resolve(string $path): ?array
    {
        if (! preg_match('~^'.preg_quote(static::PREFIX, '~').'/([a-z-]+)/([a-z]+)-([a-z-]+)\.wav$~', $path, $match)) {
            return null;
        }
        [, $slug, $key, $recipe] = $match;
        $transpose = static::KEYS[$key] ?? null;
        $register = static::preferredRegister($slug);
        if ($register === null || $transpose === null || ! isset(static::SCALES[$recipe])) {
            return null;
        }
        $ascending = ModalVoicingLessons::resolvePitches(static::voicings(), $recipe, $transpose, $slug, $register);
        if ($ascending === null) {
            return null;
        }
        $sequence = [...$ascending, ...array_reverse(array_slice($ascending, 0, -1))];

        return array_map(fn (array $note, int $index): array => [...$note, 'offset' => round($index * static::NOTE_SPACING, 3)], $sequence, array_keys($sequence));
    }

    public static function manifest(): array
    {
        $rows = [];
        foreach (static::KEYS as $key => $transpose) {
            foreach (static::SCALES as $id => [$answer]) {
                $recipe = $key.'-'.$id;
                $samples = [];
                foreach (static::sounds() as $sound) {
                    $slug = QuizCatalog::SOUND_SLUGS[$sound];
                    $notes = static::resolve(static::PREFIX.'/'.$slug.'/'.$recipe.'.wav');
                    if ($notes !== null) {
                        $samples[$slug] = array_map(fn (array $note): array => array_diff_key($note, ['slug' => true]), $notes);
                    }
                }
                if ($samples === []) {
                    throw new \RuntimeException('No recorded instrument can play '.$recipe.'.');
                }
                $rows[] = compact('recipe', 'key', 'answer', 'samples');
            }
        }

        return $rows;
    }
}
