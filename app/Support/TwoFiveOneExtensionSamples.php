<?php

namespace App\Support;

class TwoFiveOneExtensionSamples
{
    public const TITLE = '2-5-1 chord degree';

    public const PREFIX = 'two-five-one-extensions/all-keys-root-v1';

    private const CHORDS = [
        'maj69' => ['family' => 'maj6', 'label' => 'Major 6/9', 'extensions' => ['9'], 'source' => 'major', 'group' => 2],
        'maj9' => ['family' => 'maj7', 'label' => 'Major 9', 'extensions' => ['9'], 'source' => 'major', 'group' => 3],
        'maj69s11' => ['family' => 'maj6', 'label' => 'Major 6/9(♯11)', 'extensions' => ['9', '♯11'], 'source' => 'major', 'group' => 2],
        'maj7913' => ['family' => 'maj7', 'label' => 'Major 7(9, 13)', 'extensions' => ['9', '13'], 'source' => 'major', 'group' => 3],
        'maj9s11' => ['family' => 'maj7', 'label' => 'Major 9(♯11)', 'extensions' => ['9', '♯11'], 'source' => 'major', 'group' => 3],
        'maj139s11' => ['family' => 'maj7', 'label' => 'Major 13(9, ♯11)', 'extensions' => ['9', '♯11', '13'], 'source' => 'major', 'group' => 3],
        'min69' => ['family' => 'min6', 'label' => 'Minor 6/9', 'extensions' => ['9'], 'source' => 'minor', 'group' => 2],
        'min6add11' => ['family' => 'min6', 'label' => 'Minor 6 add 11', 'extensions' => ['11'], 'source' => 'minor', 'group' => 2],
        'min9' => ['family' => 'min7', 'label' => 'Minor 9', 'extensions' => ['9'], 'source' => 'minor', 'group' => 2],
        'min711' => ['family' => 'min7', 'label' => 'Minor 7(11)', 'extensions' => ['11'], 'source' => 'minor', 'group' => 2],
        'min69add11' => ['family' => 'min6', 'label' => 'Minor 6/9 add 11', 'extensions' => ['9', '11'], 'source' => 'minor', 'group' => 3],
        'min11' => ['family' => 'min7', 'label' => 'Minor 11', 'extensions' => ['9', '11'], 'source' => 'minor', 'group' => 3],
        'min13' => ['family' => 'min7', 'label' => 'Minor 13', 'extensions' => ['9', '11', '13'], 'source' => 'minor', 'group' => 3],
        'min71113' => ['family' => 'min7', 'label' => 'Minor 7(11, 13)', 'extensions' => ['11', '13'], 'source' => 'minor', 'group' => 3],
        'min7913' => ['family' => 'min7', 'label' => 'Minor 7(9, 13)', 'extensions' => ['9', '13'], 'source' => 'minor', 'group' => 3],
        'dom7b9s11' => ['family' => 'dom7', 'label' => 'Dom7(♭9, ♯11)', 'extensions' => ['♭9', '♯11'], 'source' => 'dominant', 'group' => 1],
        'dom7b9b13' => ['family' => 'dom7', 'label' => 'Dom7(♭9, ♭13)', 'extensions' => ['♭9', '♭13'], 'source' => 'dominant', 'group' => 1],
        'dom7s9s11' => ['family' => 'dom7', 'label' => 'Dom7(♯9, ♯11)', 'extensions' => ['♯9', '♯11'], 'source' => 'dominant', 'group' => 1],
        'dom7s9b13' => ['family' => 'dom7', 'label' => 'Dom7(♯9, ♭13)', 'extensions' => ['♯9', '♭13'], 'source' => 'dominant', 'group' => 1],
        'dom79s11' => ['family' => 'dom7', 'label' => 'Dom7(9, ♯11)', 'extensions' => ['9', '♯11'], 'source' => 'dominant', 'group' => 2],
        'dom7b913' => ['family' => 'dom7', 'label' => 'Dom7(♭9, 13)', 'extensions' => ['♭9', '13'], 'source' => 'dominant', 'group' => 1],
        'dom7913' => ['family' => 'dom7', 'label' => 'Dom7(9, 13)', 'extensions' => ['9', '13'], 'source' => 'dominant', 'group' => 2],
        'dom7b9s1113' => ['family' => 'dom7', 'label' => 'Dom7(♭9, ♯11, 13)', 'extensions' => ['♭9', '♯11', '13'], 'source' => 'dominant', 'group' => 1],
        'dom79s1113' => ['family' => 'dom7', 'label' => 'Dom7(9, ♯11, 13)', 'extensions' => ['9', '♯11', '13'], 'source' => 'dominant', 'group' => 2],
        'min9b5' => ['family' => 'm7b5', 'label' => 'Minor 9(♭5)', 'extensions' => ['9'], 'intervals' => [0, 3, 6, 10, 14]],
        'min11b5' => ['family' => 'm7b5', 'label' => 'Minor 11(♭5)', 'extensions' => ['9', '11'], 'intervals' => [0, 3, 6, 10, 14, 17]],
        'min11b5b9' => ['family' => 'm7b5', 'label' => 'Minor 11(♭5, ♭9)', 'extensions' => ['♭9', '11'], 'intervals' => [0, 3, 6, 10, 13, 17]],
    ];

    private const CURATED = [
        'min7-dom7-dom7' => ['min11', 'dom7913', 'dom79s11'],
        'min7-dom7-min7' => ['min13', 'dom7b9b13', 'min11'],
        'min7-dom7-maj7' => ['min9', 'dom79s1113', 'maj9'],
        'min7-dom7-maj6' => ['min711', 'dom7b913', 'maj69'],
        'min7-dom7-min6' => ['min71113', 'dom7s9b13', 'min69add11'],
        'min7-min7-dom7' => ['min7913', 'min11', 'dom7913'],
        'min7-min7-min7' => ['min11', 'min13', 'min711'],
        'min7-min7-maj7' => ['min9', 'min71113', 'maj9s11'],
        'min7-min7-maj6' => ['min711', 'min7913', 'maj69s11'],
        'min7-min7-min6' => ['min13', 'min9', 'min69'],
        'min7-m7b5-dom7' => ['min71113', 'min11b5', 'dom7b913'],
        'min7-m7b5-min7' => ['min7913', 'min9b5', 'min13'],
        'min7-m7b5-maj7' => ['min11', 'min11b5b9', 'maj139s11'],
        'min7-m7b5-maj6' => ['min711', 'min11b5', 'maj69'],
        'min7-m7b5-min6' => ['min13', 'min9b5', 'min6add11'],
        'm7b5-dom7-dom7' => ['min11b5', 'dom7b9s1113', 'dom7b9b13'],
        'm7b5-dom7-min7' => ['min11b5b9', 'dom7b9s11', 'min9'],
        'm7b5-dom7-maj7' => ['min9b5', 'dom79s11', 'maj7913'],
        'm7b5-dom7-maj6' => ['min11b5', 'dom7s9s11', 'maj69s11'],
        'm7b5-dom7-min6' => ['min11b5b9', 'dom7b9b13', 'min69add11'],
        'm7b5-min7-dom7' => ['min9b5', 'min711', 'dom79s1113'],
        'm7b5-min7-min7' => ['min11b5', 'min11', 'min71113'],
        'm7b5-min7-maj7' => ['min9b5', 'min13', 'maj9'],
        'm7b5-min7-maj6' => ['min11b5b9', 'min71113', 'maj69'],
        'm7b5-min7-min6' => ['min11b5', 'min711', 'min69'],
        'm7b5-m7b5-dom7' => ['min11b5b9', 'min11b5b9', 'dom7b9s11'],
        'm7b5-m7b5-min7' => ['min9b5', 'min11b5', 'min7913'],
        'm7b5-m7b5-maj7' => ['min11b5', 'min11b5b9', 'maj9s11'],
        'm7b5-m7b5-maj6' => ['min11b5b9', 'min9b5', 'maj69s11'],
        'm7b5-m7b5-min6' => ['min9b5', 'min11b5', 'min6add11'],
        'dom7-dom7-dom7' => ['dom79s11', 'dom7b913', 'dom7s9b13'],
        'dom7-dom7-min7' => ['dom7b9b13', 'dom7s9b13', 'min11'],
        'dom7-dom7-maj7' => ['dom7s9s11', 'dom79s11', 'maj139s11'],
        'dom7-dom7-maj6' => ['dom7s9b13', 'dom7913', 'maj69'],
        'dom7-dom7-min6' => ['dom79s1113', 'dom7b9s11', 'min69add11'],
        'dom7-min7-dom7' => ['dom7b9s11', 'min9', 'dom7b9s1113'],
        'dom7-min7-min7' => ['dom7s9b13', 'min7913', 'min711'],
        'dom7-min7-maj7' => ['dom7913', 'min11', 'maj7913'],
        'dom7-min7-maj6' => ['dom7b913', 'min9', 'maj69s11'],
        'dom7-min7-min6' => ['dom7b9s1113', 'min711', 'min69'],
        'dom7-m7b5-dom7' => ['dom7s9s11', 'min11b5b9', 'dom7s9s11'],
        'dom7-m7b5-min7' => ['dom7b9b13', 'min9b5', 'min13'],
        'dom7-m7b5-maj7' => ['dom79s11', 'min11b5', 'maj139s11'],
        'dom7-m7b5-maj6' => ['dom7b9s11', 'min11b5b9', 'maj69'],
        'dom7-m7b5-min6' => ['dom7913', 'min9b5', 'min6add11'],
        'min6-dom7-dom7' => ['min69add11', 'dom79s1113', 'dom79s11'],
        'min6-dom7-min7' => ['min69', 'dom7b9s1113', 'min9'],
        'min6-dom7-maj7' => ['min6add11', 'dom7s9s11', 'maj9'],
        'min6-dom7-maj6' => ['min69add11', 'dom7913', 'maj69s11'],
        'min6-dom7-min6' => ['min6add11', 'dom7b9b13', 'min69add11'],
        'min6-min7-dom7' => ['min69', 'min13', 'dom7b9s11'],
        'min6-min7-min7' => ['min69add11', 'min71113', 'min11'],
        'min6-min7-maj7' => ['min69', 'min7913', 'maj9s11'],
        'min6-min7-maj6' => ['min6add11', 'min13', 'maj69'],
        'min6-min7-min6' => ['min69add11', 'min11', 'min69'],
        'min6-m7b5-dom7' => ['min6add11', 'min11b5', 'dom7b913'],
        'min6-m7b5-min7' => ['min69', 'min9b5', 'min71113'],
        'min6-m7b5-maj7' => ['min69add11', 'min11b5b9', 'maj7913'],
        'min6-m7b5-maj6' => ['min6add11', 'min11b5', 'maj69s11'],
        'min6-m7b5-min6' => ['min69', 'min9b5', 'min6add11'],
    ];

    private static array $candidates = [];

    public static function chords(): array
    {
        static $chords = null;
        if ($chords !== null) {
            return $chords;
        }
        $labels = array_merge(...array_values(ProgressionRecognitionSamples::OPTIONS));
        $chords = [];
        foreach (self::CHORDS as $id => $definition) {
            if (isset($definition['source'])) {
                $class = match ($definition['source']) {
                    'major' => MajorChordExtensionSamples::class,
                    'minor' => MinorChordExtensionSamples::class,
                    'dominant' => DominantChordExtensionSamples::class,
                };
                $definition['intervals'] = $class::qualitiesForLevel($definition['group'])[$id];
            }
            $chords[$id] = [...$definition, 'quality' => $labels[$definition['family']]];
        }

        return $chords;
    }

    public static function recipes(): array
    {
        static $recipes = null;
        if ($recipes !== null) {
            return $recipes;
        }
        $recipes = [];
        $chords = self::chords();
        foreach (self::CURATED as $skeleton => $ids) {
            $parts = [];
            foreach ($ids as $index => $id) {
                $chord = $chords[$id];
                $parts[] = [2, 5, 1][$index].' '.$chord['quality'].'('.implode(', ', $chord['extensions']).')';
            }
            $recipes['p'.str_pad((string) (count($recipes) + 1), 2, '0', STR_PAD_LEFT)] = ['skeleton' => $skeleton, 'chords' => $ids, 'answer' => implode(' - ', $parts)];
        }

        return $recipes;
    }

    private static function candidates(string $id, int $degree, int $key, string $slug, array $register): array
    {
        $rootClass = ($key + [2 => 2, 5 => 7, 1 => 0][$degree]) % 12;
        $cacheKey = $slug.':'.$rootClass.':'.$id;
        if (isset(self::$candidates[$cacheKey])) {
            return self::$candidates[$cacheKey];
        }
        [$low, $high] = $register;
        $intervals = self::chords()[$id]['intervals'];
        $root = 48 + $rootClass;
        $rows = [];
        foreach (range(-48, 48, 12) as $shift) {
            $pitches = array_map(fn (int $interval): int => $root + $shift + $interval, $intervals);
            if (min($pitches) < $low || max($pitches) > $high) {
                continue;
            }
            foreach ($pitches as $pitch) {
                if (NoteSample::fromRecordedMidiPitch($pitch, $slug) === null) {
                    continue 2;
                }
            }
            $rows[] = $pitches;
        }

        return self::$candidates[$cacheKey] = $rows;
    }

    public static function resolve(string $path): ?array
    {
        if (! preg_match('#^'.self::PREFIX.'/([^/]+)/([a-z]+)-(p[0-9]{2})\.wav$#', $path, $m)) {
            return null;
        }
        [, $slug, $name, $id] = $m;
        $key = array_search($name, CadenceSamples::KEYS, true);
        $recipe = self::recipes()[$id] ?? null;
        $register = CadenceSamples::preferredRegister($slug);
        if ($key === false || $recipe === null || $register === null) {
            return null;
        }
        $options = [];
        foreach ($recipe['chords'] as $index => $chord) {
            $options[] = self::candidates($chord, [2, 5, 1][$index], $key, $slug, $register);
        }
        $best = null;
        $bestScore = null;
        foreach ($options[0] as $two) {
            foreach ($options[1] as $five) {
                foreach ($options[2] as $one) {
                    $bass = [abs($two[0] - $five[0]), abs($five[0] - $one[0])];
                    $topMotion = abs(end($two) - end($five)) + abs(end($five) - end($one));
                    $pitches = [...$two, ...$five, ...$one];
                    $score = [max($bass), array_sum($bass), $topMotion, max($pitches) - min($pitches), abs(array_sum($pitches) / count($pitches) - $register[2]), $two[0]];
                    if ($bestScore === null || $score < $bestScore) {
                        $best = [$two, $five, $one];
                        $bestScore = $score;
                    }
                }
            }
        }
        if ($best === null) {
            return null;
        }
        $notes = [];
        foreach ($best as $index => $chord) {
            foreach ($chord as $pitch) {
                $note = NoteSample::fromRecordedMidiPitch($pitch, $slug);
                $notes[] = ['slug' => $slug, ...$note, 'offset' => $index * 1.6];
            }
        }

        return $notes;
    }

    public static function manifest(): array
    {
        $names = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];
        $rows = [];
        foreach (CadenceSamples::KEYS as $key) {
            foreach (self::recipes() as $id => $recipe) {
                $row = ['recipe' => $key.'-'.$id, 'key' => $key, 'answer' => $recipe['answer'], 'chords' => $recipe['chords'], 'sizes' => array_map(fn (string $id): int => count(self::chords()[$id]['intervals']), $recipe['chords']), 'samples' => []];
                foreach (QuizCatalog::SOUND_SLUGS as $slug) {
                    $notes = self::resolve(self::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav');
                    if ($notes !== null) {
                        $row['samples'][$slug] = array_map(fn (array $n): array => [array_search($n['degree'], $names, true), $n['octave']], $notes);
                    }
                }
                if ($row['samples'] !== []) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    public static function answersMatch(string $submitted, string $correct): bool
    {
        $normalize = function (string $answer): ?array {
            $parts = explode(' - ', trim($answer));
            if (count($parts) !== 3) {
                return null;
            }
            $result = [];
            foreach ($parts as $index => $part) {
                if (! preg_match('/^([251])\s+(.+?)\s*\(([^()]*)\)$/u', trim($part), $m) || $m[1] !== ['2', '5', '1'][$index]) {
                    return null;
                }
                $extensions = preg_split('/\s*,\s*/u', trim($m[3]));
                if (in_array('', $extensions, true)) {
                    return null;
                }
                sort($extensions);
                $result[] = [$m[1], trim($m[2]), $extensions];
            }

            return $result;
        };
        $left = $normalize($submitted);
        $right = $normalize($correct);

        return $left !== null && $right !== null && $left === $right;
    }
}
