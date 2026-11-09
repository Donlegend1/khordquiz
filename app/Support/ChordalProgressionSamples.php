<?php

namespace App\Support;

use App\Models\QuizQuestion;
use RuntimeException;

class ChordalProgressionSamples
{
    public const QUALITIES = [
        'maj' => 'Major', 'min' => 'Minor', 'dim' => 'Diminished', 'aug' => 'Augmented',
        'maj7' => 'Major 7th', 'min7' => 'Minor 7th', 'dom7' => 'Dominant 7th',
        'm7b5' => 'Minor 7(♭5)', 'dim7' => 'Diminished 7th',
        'aug7' => 'Augmented 7th', 'minMaj7' => 'Minor-Major 7th',
        'dimMaj7' => 'Diminished-Major 7th', 'dom7b5' => 'Dominant 7(♭5)',
        'maj7aug' => 'Major 7(♯5)', 'maj7b5' => 'Major 7(♭5)',
        // Added 6 Nov 2026 — first used by the new 6 Nov Chordal melodies
        // progressions; names match this app's existing conventions
        // (Sus2/Sus4: ChordNamingAnswer::QUALITIES; "7 Sus4"/"(♯5)" pattern:
        // quizAnswerPresets.js, maj7aug above).
        'sus2' => 'Sus2', 'sus4' => 'Sus4', 'min7aug' => 'Minor 7(♯5)', 'maj7sus2' => 'Major 7 Sus2',
    ];

    public const PREFIX = 'chordal-progressions/all-keys-v1';

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function resolve(string $path): ?array
    {
        if (! preg_match('#^'.self::PREFIX.'/([^/]+)/([a-z]+)-p(\d+)-(orig|drop2)\.wav$#', $path, $match)) {
            return null;
        }
        [, $slug, $keyName, $pattern, $variant] = $match;
        $key = array_search($keyName, CadenceSamples::KEYS, true);
        $register = CadenceSamples::preferredRegister($slug);
        require_once base_path('scripts/audio/chordal_melodies_pool.php');
        $steps = chordalLiteralProgressions()[(int) $pattern] ?? [];
        if ($key === false || $register === null || count($steps) !== 5) {
            return null;
        }
        $chords = chordalBuildVoicings($steps);
        if ($variant === 'drop2') {
            $chords = chordalDrop2Voicings($chords);
        }
        [$low, $high, $center] = $register;
        $pitches = array_merge(...$chords);
        $shifts = range(-48, 48, 12);
        usort($shifts, fn (int $a, int $b): int => [abs((min($pitches) + max($pitches)) / 2 + $key + $a - $center), abs($a)] <=> [abs((min($pitches) + max($pitches)) / 2 + $key + $b - $center), abs($b)]);
        foreach ($shifts as $shift) {
            if (min($pitches) + $key + $shift < $low || max($pitches) + $key + $shift > $high) {
                continue;
            }
            $notes = [];
            foreach ($chords as $index => $chord) {
                foreach ($chord as $pitch) {
                    $note = NoteSample::fromRecordedMidiPitch($pitch + $key + $shift, $slug);
                    if ($note === null) {
                        continue 3;
                    }
                    $notes[] = ['slug' => $slug, ...$note, 'offset' => $index * NoteSample::MELODIC_NOTE_SPACING_SECONDS];
                }
            }

            return $notes;
        }

        return null;
    }

    /** @return list<array{recipe: string, key: string, answer: string, audio: array, samples: array}> */
    public static function inAllKeys(array $source): array
    {
        $rows = [];
        foreach (CadenceSamples::KEYS as $key) {
            $row = ['recipe' => $key.'-'.$source['recipe'], 'key' => $key, 'answer' => $source['answer'], 'audio' => [], 'samples' => []];
            foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
                $path = self::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav';
                $notes = self::resolve($path);
                if ($notes === null) {
                    continue;
                }
                $row['audio'][$sound] = $path;
                $row['samples'][$slug] = array_map(fn (array $note): array => array_diff_key($note, ['slug' => true]), $notes);
            }
            if ($row['samples'] !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public static function fromSource(QuizQuestion $question): ?array
    {
        require_once base_path('scripts/audio/chordal_melodies_pool.php');
        $paths = $question->audioVariants;
        $path = $paths->first()?->audio_path ?? '';
        if (! preg_match('#/p(\d+)-(orig|drop2)(?:-up12)?\.wav$#', $path, $match)) {
            throw new RuntimeException("Unrecognized chordal source recipe: {$path}");
        }
        $steps = chordalLiteralProgressions()[(int) $match[1]] ?? [];
        if (count($steps) !== 5) {
            return null;
        }
        $answer = implode(' - ', array_map(fn (array $step): string => self::QUALITIES[$step['quality']], $steps));
        $audio = [];
        $samples = [];
        foreach ($paths as $variant) {
            if (! preg_match('#^melodic-dictation/chordal-melodies/([^/]+)/p'.preg_quote($match[1], '#').'-'.$match[2].'(?:-up12)?\.wav$#', $variant->audio_path, $instrument)) {
                throw new RuntimeException('Source instrument has a different chordal recipe.');
            }
            $notes = NoteSample::resolve($variant->audio_path);
            if ($notes === null || count(array_unique(array_column($notes, 'offset'))) !== 5) {
                continue;
            }
            $audio[$variant->sound] = $variant->audio_path;
            $samples[$instrument[1]] = array_map(fn (array $note): array => array_diff_key($note, ['slug' => true]), $notes);
        }

        if ($samples === []) {
            throw new RuntimeException('No playable instruments for a five-chord source.');
        }

        return ['recipe' => 'p'.$match[1].'-'.$match[2], 'answer' => $answer, 'audio' => $audio, 'samples' => $samples];
    }
}
