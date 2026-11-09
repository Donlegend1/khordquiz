<?php

namespace App\Support;

class ModalVoicingSamples
{
    public const TITLE = 'Lydian Mode';

    public const LEGACY_TITLE = 'Modal Voicings · Level 1';

    public const CATEGORY = 'Modal Voicings';

    public const PREFIX = 'modal-voicings/lydian-mode-c-v1';

    /** Six-note Lydian-family voicings; each variant changes one note from Lydian. */
    public const VOICINGS = [
        'lydian' => ['Lydian', [48, 52, 55, 59, 62, 66]],
        'lydian-sharp-two' => ['Lydian ♯2', [48, 52, 55, 59, 63, 66]],
        'lydian-augmented' => ['Lydian Augmented', [48, 52, 56, 59, 62, 66]],
        'lydian-dominant' => ['Lydian Dominant', [48, 52, 55, 58, 62, 66]],
        'lydian-flat-three' => ['Lydian Diminished', [48, 51, 55, 59, 62, 66]],
    ];

    /** @return list<string> */
    public static function choices(): array
    {
        return array_column(static::VOICINGS, 0);
    }

    /** @return array{int, int, int}|null */
    public static function preferredRegister(string $slug): ?array
    {
        return match ($slug) {
            'saxophone' => [48, 78, 64],
            default => CadenceSamples::preferredRegister($slug),
        };
    }

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function resolve(string $path): ?array
    {
        $prefix = preg_quote(static::PREFIX, '~');
        if (! preg_match('~^'.$prefix.'/([a-z-]+)/c-([a-z-]+)\.wav$~', $path, $match)) {
            return null;
        }
        [, $slug, $recipe] = $match;
        $voicing = static::VOICINGS[$recipe] ?? null;
        $register = static::preferredRegister($slug);
        if ($voicing === null || $register === null) {
            return null;
        }
        $shift = static::commonOctaveShift($slug, $register);
        if ($shift === null) {
            return null;
        }

        return array_map(fn (int $pitch): array => ['slug' => $slug, ...NoteSample::fromRecordedMidiPitch($pitch + $shift, $slug)], $voicing[1]);
    }

    /** @param array{int, int, int} $register */
    private static function commonOctaveShift(string $slug, array $register): ?int
    {
        [$low, $high, $center] = $register;
        $pitches = array_values(array_unique(array_merge(...array_column(static::VOICINGS, 1))));
        $lowest = min($pitches);
        $highest = max($pitches);
        $midpoint = ($lowest + $highest) / 2;
        $shifts = range(-48, 48, 12);
        usort($shifts, fn (int $a, int $b): int => [abs($midpoint + $a - $center), abs($a)] <=> [abs($midpoint + $b - $center), abs($b)]);
        foreach ($shifts as $shift) {
            if ($lowest + $shift < $low || $highest + $shift > $high) {
                continue;
            }
            foreach ($pitches as $pitch) {
                if (NoteSample::fromRecordedMidiPitch($pitch + $shift, $slug) === null) {
                    continue 2;
                }
            }

            return $shift;
        }

        return null;
    }

    /** @return list<array{recipe: string, answer: string, samples: array<string, list<array{degree: string, octave: int, offset: float}>>}> */
    public static function manifest(): array
    {
        $rows = [];
        foreach (static::VOICINGS as $id => [$answer]) {
            $key = 'c';
            $recipe = $key.'-'.$id;
            $samples = [];
            foreach (QuizCatalog::SOUND_SLUGS as $slug) {
                $notes = static::resolve(static::PREFIX.'/'.$slug.'/'.$recipe.'.wav');
                if ($notes !== null) {
                    $samples[$slug] = array_map(fn (array $note): array => array_diff_key($note, ['slug' => true]), $notes);
                }
            }
            if ($samples === []) {
                throw new \RuntimeException('No recorded instrument can play modal voicing '.$recipe.'.');
            }
            $rows[] = compact('recipe', 'key', 'answer', 'samples');
        }

        return $rows;
    }
}
