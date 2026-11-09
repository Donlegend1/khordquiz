<?php

namespace App\Support;

class ModalHarmonySamples
{
    public const CATEGORY = 'Modal Harmony';

    public const PREFIX = 'modal-harmony/major-triads-v1';

    public const MINOR_PREFIX = 'modal-harmony/minor-triads-v1';

    /** Two agreed four-note voicings for each taught modal-color association, above C3. */
    public const GROUPS = [
        1 => [
            'flat-two-major' => ['Phrygian · ♭II Major', [[48, 53, 56, 61], [48, 56, 61, 65]]],
            'two-major' => ['Lydian Dominant · II Major', [[48, 54, 57, 62], [48, 57, 62, 66]]],
            'flat-three-major' => ['Aeolian · ♭III Major', [[48, 55, 58, 63], [48, 58, 63, 67]]],
            'three-major' => ['Lydian Augmented · III Major', [[48, 56, 59, 64], [48, 59, 64, 68]]],
            'five-major' => ['Ionian · V Major', [[48, 50, 55, 59], [48, 55, 59, 62]]],
        ],
        2 => [
            'four-major' => ['Ionian · IV Major', [[48, 53, 57, 60], [48, 57, 60, 65]]],
            'flat-five-major' => ['Altered Dominant · ♭V Major', [[48, 54, 58, 61], [48, 58, 61, 66]]],
            'six-major' => ['Dominant Diminished · VI Major', [[48, 52, 57, 61], [48, 57, 61, 64]]],
            'flat-seven-major' => ['Mixolydian · ♭VII Major', [[48, 53, 58, 62], [48, 58, 62, 65]]],
            'seven-major' => ['Lydian Diminished · VII Major', [[48, 54, 59, 63], [48, 51, 54, 59]]],
        ],
        3 => [
            'flat-two-minor' => ['Phrygian Dominant · ♭II Minor', [[48, 52, 56, 61], [48, 56, 61, 64]]],
            'two-minor' => ['Dorian · II Minor', [[48, 53, 57, 62], [48, 57, 62, 65]]],
            'flat-three-minor' => ['Locrian · ♭III Minor', [[48, 54, 58, 63], [48, 58, 63, 66]]],
            'three-minor' => ['Ionian · III Minor', [[48, 55, 59, 64], [48, 59, 64, 67]]],
            'four-minor' => ['Aeolian · IV Minor', [[48, 53, 56, 60], [48, 56, 60, 65]]],
        ],
        4 => [
            'flat-five-minor' => ['Locrian ♮6 · ♭V Minor', [[48, 54, 57, 61], [48, 57, 61, 66]]],
            'five-minor' => ['Mixolydian · V Minor', [[48, 50, 55, 58], [48, 55, 58, 62]]],
            'flat-six-minor' => ['Harmonic Minor · ♭VI Minor', [[48, 51, 56, 59], [48, 56, 59, 63]]],
            'six-minor' => ['Ionian · VI Minor', [[48, 52, 57, 60], [48, 57, 60, 64]]],
            'flat-seven-minor' => ['Phrygian · ♭VII Minor', [[48, 53, 58, 61], [48, 58, 61, 65]]],
            'seven-minor' => ['Lydian · VII Minor', [[48, 54, 59, 62], [48, 50, 54, 59]]],
        ],
    ];

    public static function titles(): array
    {
        return array_map(fn (int $number): string => 'Modal Harmony #'.$number, range(1, 8));
    }

    public static function group(int $number): int
    {
        if (! in_array($number, range(1, 8), true)) {
            throw new \InvalidArgumentException('Unknown Modal Harmony lesson.');
        }

        return intdiv($number + 1, 2);
    }

    public static function prefix(int $number): string
    {
        return self::group($number) <= 2 ? self::PREFIX : self::MINOR_PREFIX;
    }

    public static function choices(int $number): array
    {
        return array_column(self::GROUPS[self::group($number)], 0);
    }

    public static function presets(): array
    {
        $presets = [];
        foreach (self::titles() as $index => $title) {
            $presets[$title] = self::choices($index + 1);
        }

        return $presets;
    }

    public static function sounds(int $number): array
    {
        self::group($number);

        $excluded = ['Bass Guitar', 'Upright Bass', 'Tuba'];
        if ($number === 2) {
            $excluded = [...$excluded, 'Violin', 'Celesta', 'Vibraphone'];
        } elseif ($number === 6) {
            $excluded = [...$excluded, 'Celesta', 'Vibraphone'];
        }

        return array_values(array_diff(QuizCatalog::allInstruments(), $excluded));
    }

    public static function preferredRegister(string $slug): ?array
    {
        return $slug === 'tropar-choir' ? [36, 67, 52] : ModalVoicingSamples::preferredRegister($slug);
    }

    public static function voicings(int $group): array
    {
        $voicings = [];
        foreach (self::GROUPS[$group] ?? [] as $id => [$answer, $shapes]) {
            foreach ($shapes as $index => $shape) {
                $voicings[$id.'-v'.($index + 1)] = [$answer, $shape];
            }
        }

        return $voicings;
    }

    public static function resolve(string $path): ?array
    {
        $minor = str_starts_with($path, self::MINOR_PREFIX.'/');
        $prefix = $minor ? self::MINOR_PREFIX : self::PREFIX;
        $groups = $minor ? '[34]' : '[12]';
        if (! preg_match('~^'.preg_quote($prefix, '~').'/('.$groups.')/([a-z-]+)/([a-z]+)-([a-z-]+-v[12])\.wav$~', $path, $match)) {
            return null;
        }
        [, $group, $slug, $key, $recipe] = $match;
        $transpose = ModalVoicingLessons::KEYS[$key] ?? null;
        if ($transpose === null) {
            return null;
        }

        return ModalVoicingLessons::resolvePitches(self::voicings((int) $group), $recipe, $transpose, $slug, self::preferredRegister($slug));
    }

    public static function manifest(int $number): array
    {
        $group = self::group($number);
        $rows = [];
        $keys = $number % 2 === 1 ? ['c' => 0] : ModalVoicingLessons::KEYS;
        foreach ($keys as $key => $transpose) {
            foreach (self::voicings($group) as $id => [$answer]) {
                $recipe = $key.'-'.$id;
                $samples = [];
                foreach (self::sounds($number) as $sound) {
                    $slug = QuizCatalog::SOUND_SLUGS[$sound];
                    $notes = self::resolve(self::prefix($number).'/'.$group.'/'.$slug.'/'.$recipe.'.wav');
                    if ($notes !== null) {
                        $samples[$slug] = array_map(fn (array $note): array => array_diff_key($note, ['slug' => true]), $notes);
                    }
                }
                if ($samples === []) {
                    throw new \RuntimeException('No recorded instrument can play Modal Harmony '.$recipe.'.');
                }
                $rows[] = compact('recipe', 'key', 'answer', 'samples');
            }
        }

        return $rows;
    }
}
