<?php

namespace App\Support;

use App\Models\QuizQuestion;

class BasicChordNamingSamples
{
    public const TITLE = 'Chord Naming · Level 1';

    public const ROOT_MIDI = 48;

    public const LEVEL_TWO_TITLE = 'Chord Naming · Level 2';

    public const LEVEL_THREE_TITLE = 'Chord Naming · Level 3';

    public const LEVEL_FOUR_TITLE = 'Chord Naming · Level 4';

    public const LEVEL_FIVE_TITLE = 'Chord Naming · Level 5';

    public const LEVEL_SIX_TITLE = 'Chord Naming · Level 6';

    public const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    /** @return array<string, array{family: string, intervals: list<int>, answer: string}> */
    public static function recipes(int $group = 1): array
    {
        static $cache = [];
        if (isset($cache[$group])) {
            return $cache[$group];
        }
        $recipes = [];
        $seen = [];
        $tokens = [9 => '6', 10 => '♭7', 11 => '7', 13 => '♭9', 14 => '9', 15 => '♯9', 17 => '11', 18 => '♯11', 20 => '♭13', 21 => '13'];
        foreach (['major' => MajorChordExtensionSamples::class, 'minor' => MinorChordExtensionSamples::class, 'dominant' => DominantChordExtensionSamples::class] as $family => $source) {
            foreach ([$group] as $sourceGroup) {
                foreach ($source::qualitiesForLevel($sourceGroup) as $slug => $intervals) {
                    $signature = $family.':'.implode(',', $intervals);
                    if (isset($seen[$signature])) {
                        continue;
                    }
                    $seen[$signature] = true;
                    $degrees = array_filter(array_map(fn (int $interval): ?string => $tokens[$interval] ?? null, $intervals));
                    $degrees = array_values(array_intersect(ChordNamingAnswer::DEGREES, $degrees));
                    $quality = $family === 'minor' ? 'Minor' : 'Major';
                    $recipes[$family.'-'.$slug] = ['family' => $family, 'intervals' => $intervals, 'answer' => 'v2|'.$quality.'|'.implode(',', $degrees).'|None'];
                }
            }
        }

        if ($group === 3) {
            $recipes = [...$recipes, ...self::secondaryRecipes()];
        }

        return $cache[$group] = $recipes;
    }

    /** @return array<string, array{family: string, intervals: list<int>, answer: string}> */
    private static function secondaryRecipes(): array
    {
        $families = [
            'major-flat5' => ['Major', ['♭5', '7'], [0, 4, 6, 11], [[], [14], [21], [14, 21]]],
            'major-sharp5' => ['Major', ['♯5', '7'], [0, 4, 8, 11], [[], [14], [14, 18], [18]]],
            'augmented7' => ['Augmented', ['♭7'], [0, 4, 8, 10], [[], [14], [14, 18], [13, 18], [13], [18]]],
            'minor-major7' => ['Minor', ['7'], [0, 3, 7, 11], [[], [14], [14, 17], [14, 18], [17], [21], [14, 21]]],
            'diminished-major7' => ['Diminished', ['maj7'], [0, 3, 6, 11], [[], [14], [14, 17], [17]]],
            'diminished7' => ['Diminished', ['7'], [0, 3, 6, 9], [[], [14], [14, 17], [17]]],
            'half-diminished7' => ['Minor', ['♭5', '♭7'], [0, 3, 6, 10], [[], [14], [17], [14, 17]]],
        ];
        $tokens = [13 => '♭9', 14 => '9', 17 => '11', 18 => '♯11', 21 => '13'];
        $recipes = [];
        foreach ($families as $name => [$quality, $baseDegrees, $baseTones, $extensions]) {
            foreach ($extensions as $tones) {
                $degrees = [...$baseDegrees, ...array_map(fn (int $tone): string => $tokens[$tone], $tones)];
                $degrees = array_values(array_intersect(ChordNamingAnswer::DEGREES, $degrees));
                $id = 'secondary-'.$name.($tones === [] ? '' : '-'.implode('-', $tones));
                $recipes[$id] = ['family' => 'secondary', 'intervals' => [...$baseTones, ...$tones], 'answer' => 'v2|'.$quality.'|'.implode(',', $degrees).'|None'];
            }
        }

        return $recipes;
    }

    /** @return array<string, array{family: string, intervals: list<int>, answer: string}> */
    public static function recipesWithOmissions(int $group = 1): array
    {
        static $cache = [];
        if (isset($cache[$group])) {
            return $cache[$group];
        }
        $recipes = self::recipes($group);
        foreach (self::recipes($group) as $id => $recipe) {
            $answer = ChordNamingAnswer::decode($recipe['answer']);
            if (array_intersect($answer['degrees'], ['7', '♭7', 'maj7']) === []) {
                continue;
            }
            foreach (['No 3' => 'no3', 'No 5' => 'no5'] as $exclude => $suffix) {
                $removed = $exclude === 'No 3' ? $recipe['intervals'][1] : $recipe['intervals'][2];
                $recipes[$id.'-'.$suffix] = [
                    'family' => $recipe['family'],
                    'intervals' => array_values(array_diff($recipe['intervals'], [$removed])),
                    'answer' => str_replace('|None', '|'.$exclude, $recipe['answer']),
                ];
            }
        }

        return $cache[$group] = $recipes;
    }

    /** Exact written pitches only: no doubling, gap filling, inversions, or octave substitution.
     * @return list<array{slug: string, degree: string, octave: int, offset: float}>|null
     */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^chord-naming/(basic-v1|basic-group[23]-v1)/([a-z-]+)/([a-z]+)-([a-z0-9-]+)\.wav$#', $path, $matches)) {
            return null;
        }
        $group = match ($matches[1]) {
            'basic-v1' => 1,
            'basic-group2-v1' => 2,
            'basic-group3-v1' => 3,
        };
        $key = array_search($matches[3], self::KEYS, true);
        $recipe = self::recipesWithOmissions($group)[$matches[4]] ?? null;
        if ($recipe === null || $key === false) {
            return null;
        }
        $slug = $instrument ?? $matches[2];
        $notes = [];
        foreach ($recipe['intervals'] as $interval) {
            $note = NoteSample::fromRecordedMidiPitch(48 + $key + $interval, $slug);
            if ($note === null) {
                return null;
            }
            $notes[] = ['slug' => $slug, ...$note];
        }

        return $notes;
    }

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function forQuestion(QuizQuestion $question, string $slug): ?array
    {
        $path = $question->audioVariants->firstWhere('sound', 'Piano')?->audio_path
            ?? $question->audioVariants->first()?->audio_path ?? $question->audio_path;

        return $path ? self::resolve($path, $slug) : null;
    }

    /** @return list<array{key: string, recipe: string, answer: string, samples: array<string, list<array{degree: string, octave: int, offset: float}>>}> */
    public static function manifest(array $keys = ['c'], int $group = 1, bool $includeOmissions = false): array
    {
        $rows = [];
        foreach ($keys as $key) {
            foreach ($includeOmissions ? self::recipesWithOmissions($group) : self::recipes($group) as $id => $recipe) {
                $samples = [];
                foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
                    if (in_array($sound, ['Bass Guitar', 'Upright Bass', 'Tuba'], true)) {
                        continue;
                    }
                    $notes = self::resolve(($group === 1 ? 'chord-naming/basic-v1/' : 'chord-naming/basic-group'.$group.'-v1/').$slug.'/'.$key.'-'.$id.'.wav');
                    if ($notes !== null) {
                        $samples[$slug] = array_map(fn (array $note): array => array_diff_key($note, ['slug' => true]), $notes);
                    }
                }
                if ($samples === []) {
                    throw new \RuntimeException('No recorded instrument for '.$id);
                }
                $rows[] = ['key' => $key, 'recipe' => $id, 'answer' => $recipe['answer'], 'samples' => $samples];
            }
        }

        return $rows;
    }
}
