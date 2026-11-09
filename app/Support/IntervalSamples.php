<?php

namespace App\Support;

class IntervalSamples
{
    public const SEMITONES = [
        'Minor 2nd' => 1, 'Major 2nd' => 2, 'Minor 3rd' => 3, 'Major 3rd' => 4,
        'Perfect 4th' => 5, 'Tri Tone' => 6, 'Perfect 5th' => 7,
        'Minor 6th' => 8, 'Major 6th' => 9, 'Minor 7th' => 10, 'Major 7th' => 11, 'Octave' => 12,
    ];

    private const PREFERRED_ROOTS = [
        'bass-guitar' => 36, 'bassoon' => 36, 'tuba' => 36,
        'upright-bass' => 36, 'cello' => 48, 'saxophone' => 48, 'trombone' => 48,
        'celesta' => 48, 'vibraphone' => 48,
        'voice' => 48, 'tropar-choir' => 48, 'ensemble-choir' => 48, 'sustained-choir' => 48,
    ];

    /** @return list<string> */
    public static function answersFor(string $title): array
    {
        if (str_contains($title, 'Non-diatonic')) {
            return ['Minor 2nd', 'Minor 3rd', 'Tri Tone', 'Minor 6th', 'Minor 7th'];
        }
        if (str_contains($title, 'Diatonic')) {
            return ['Major 2nd', 'Major 3rd', 'Perfect 4th', 'Perfect 5th', 'Major 6th', 'Major 7th', 'Octave'];
        }

        return array_keys(self::SEMITONES);
    }

    /** @return array{0: int, 1: int}|null */
    public static function pairFor(string $answer, string $slug): ?array
    {
        $semitones = self::SEMITONES[$answer] ?? null;
        if ($semitones === null) {
            return null;
        }
        $preferred = self::PREFERRED_ROOTS[$slug] ?? 60;
        $roots = range(24, 95);
        usort($roots, fn (int $a, int $b) => [$a % 12 !== 0, abs($a - $preferred), $a] <=> [$b % 12 !== 0, abs($b - $preferred), $b]);
        foreach ($roots as $root) {
            if (NoteSample::fromRecordedMidiPitch($root, $slug) !== null
                && NoteSample::fromRecordedMidiPitch($root + $semitones, $slug) !== null) {
                return [$root, $root + $semitones];
            }
        }

        return null;
    }

    /**
     * Every one of the 12 keys (pitch classes) this instrument's recorded
     * range can play this interval in — one representative root per pitch
     * class (whichever octave lands closest to the instrument's preferred
     * register), not just the single closest-to-center root pairFor() picks.
     * A narrow-range instrument (e.g. Tuba) legitimately comes back with
     * fewer than 12 — that's expected, not an error; a wide-range instrument
     * (e.g. Piano) comes back with all 12.
     *
     * @return array<int, int> pitch class (0-11, 0 = C) => chosen MIDI root
     */
    public static function rootsForSemitones(int $semitones, string $slug): array
    {
        $preferred = self::PREFERRED_ROOTS[$slug] ?? 60;
        $byPitchClass = [];
        foreach (range(24, 95) as $root) {
            if (NoteSample::fromRecordedMidiPitch($root, $slug) === null
                || NoteSample::fromRecordedMidiPitch($root + $semitones, $slug) === null) {
                continue;
            }
            $pitchClass = $root % 12;
            $current = $byPitchClass[$pitchClass] ?? null;
            if ($current === null || abs($root - $preferred) < abs($current - $preferred)) {
                $byPitchClass[$pitchClass] = $root;
            }
        }
        ksort($byPitchClass);

        return $byPitchClass;
    }

    /** @return list<array{degree: string, octave: int, offset: float}>|null */
    public static function notesForPair(int $first, int $second, string $timing, string $slug): ?array
    {
        if (! in_array($timing, ['melodic', 'harmonic'], true) || $second - $first < 1 || $second - $first > 12) {
            return null;
        }
        $low = NoteSample::fromRecordedMidiPitch($first, $slug);
        $high = NoteSample::fromRecordedMidiPitch($second, $slug);
        if ($low === null || $high === null) {
            return null;
        }
        $high['offset'] = $timing === 'melodic' ? NoteSample::MELODIC_NOTE_SPACING_SECONDS : 0.0;

        return [$low, $high];
    }

    /** @return list<array{degree: string, octave: int, offset: float}>|null */
    public static function fromAnswer(string $answer, string $timing, string $slug): ?array
    {
        $pair = self::pairFor($answer, $slug);

        return $pair === null ? null : self::notesForPair($pair[0], $pair[1], $timing, $slug);
    }
}
