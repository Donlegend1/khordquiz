<?php

namespace App\Support;

class ModalVoicingLessons
{
    public const PREFIX = 'modal-voicings/paired-v1';

    public const FAMILIES = [
        'lydian' => ModalVoicingSamples::class,
        'mixolydian' => MixolydianVoicingSamples::class,
        'phrygian' => PhrygianVoicingSamples::class,
        'locrian' => LocrianVoicingSamples::class,
        'dorian' => DorianVoicingSamples::class,
        'aeolian' => AeolianVoicingSamples::class,
        'ionian' => IonianVoicingSamples::class,
        'major-modes' => MajorModeVoicingSamples::class,
        'minor-modes' => MinorModeVoicingSamples::class,
    ];

    public const KEYS = ['c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5, 'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11];

    public static function lessons(): array
    {
        $lessons = [];
        foreach (self::FAMILIES as $family => $source) {
            foreach ([1, 2] as $number) {
                $lessons[] = ['title' => $source::TITLE.' #'.$number, 'family' => $family, 'number' => $number, 'choices' => $source::choices()];
            }
        }

        return $lessons;
    }

    public static function titles(): array
    {
        return array_column(self::lessons(), 'title');
    }

    public static function presets(): array
    {
        return array_column(self::lessons(), 'choices', 'title');
    }

    public static function sounds(string $title): array
    {
        $excluded = ['Bass Guitar', 'Upright Bass', 'Tuba'];
        if ($title === AeolianVoicingSamples::TITLE.' #2') {
            $excluded = [...$excluded, 'Celesta', 'Vibraphone'];
        }

        return array_values(array_diff(QuizCatalog::allInstruments(), $excluded));
    }

    /** Whole-voicing octave shifts preserve every interval and use one bass octave per family/key/instrument. */
    public static function resolve(string $path): ?array
    {
        if (! preg_match('~^'.preg_quote(self::PREFIX, '~').'/([a-z-]+)/([a-z-]+)/([a-z]+)-([a-z-]+)\.wav$~', $path, $match)) {
            return null;
        }
        [, $family, $slug, $key, $recipe] = $match;
        $source = self::FAMILIES[$family] ?? null;
        $transpose = self::KEYS[$key] ?? null;
        if ($source === null || $transpose === null) {
            return null;
        }

        return self::resolvePitches($source::VOICINGS, $recipe, $transpose, $slug);
    }

    /** Resolve one shape using a common whole-octave shift across its full comparison group. */
    public static function resolvePitches(array $voicings, string $recipe, int $transpose, string $slug, ?array $register = null): ?array
    {
        $voicing = $voicings[$recipe] ?? null;
        $register ??= ModalVoicingSamples::preferredRegister($slug);
        if ($voicing === null || $register === null) {
            return null;
        }
        [$low, $high, $center] = $register;
        $pitches = array_values(array_unique(array_merge(...array_column($voicings, 1))));
        $lowest = min($pitches) + $transpose;
        $highest = max($pitches) + $transpose;
        $midpoint = ($lowest + $highest) / 2;
        $shifts = range(-48, 48, 12);
        usort($shifts, fn (int $a, int $b): int => [abs($midpoint + $a - $center), abs($a)] <=> [abs($midpoint + $b - $center), abs($b)]);
        // Some keys straddle a register boundary. Allow at most six semitones of extra room, retaining recorded pitches.
        foreach ([0, 6] as $margin) {
            foreach ($shifts as $shift) {
                if ($lowest + $shift < $low - $margin || $highest + $shift > $high + $margin) {
                    continue;
                }
                foreach ($pitches as $pitch) {
                    if (NoteSample::fromRecordedMidiPitch($pitch + $transpose + $shift, $slug) === null) {
                        continue 2;
                    }
                }

                return array_map(fn (int $pitch): array => ['slug' => $slug, ...NoteSample::fromRecordedMidiPitch($pitch + $transpose + $shift, $slug)], $voicing[1]);
            }
        }

        return null;
    }

    public static function manifest(string $family, int $number): array
    {
        $source = self::FAMILIES[$family] ?? throw new \InvalidArgumentException('Unknown modal family.');
        if (! in_array($number, [1, 2], true)) {
            throw new \InvalidArgumentException('Unknown modal lesson number.');
        }
        $rows = [];
        foreach ($number === 1 ? ['f' => 5] : self::KEYS as $key => $transpose) {
            foreach ($source::VOICINGS as $id => [$answer]) {
                $recipe = $key.'-'.$id;
                $samples = [];
                foreach (QuizCatalog::SOUND_SLUGS as $slug) {
                    if ($family === 'aeolian' && $number === 2 && in_array($slug, ['celesta', 'vibraphone'], true)) {
                        continue;
                    }

                    $notes = self::resolve(self::PREFIX.'/'.$family.'/'.$slug.'/'.$recipe.'.wav');
                    if ($notes !== null) {
                        $samples[$slug] = array_map(fn (array $note): array => array_diff_key($note, ['slug' => true]), $notes);
                    }
                }
                if ($samples === []) {
                    throw new \RuntimeException('No recorded instrument can play '.$family.'/'.$recipe.'.');
                }
                $rows[] = compact('recipe', 'key', 'answer', 'samples');
            }
        }

        return $rows;
    }
}
