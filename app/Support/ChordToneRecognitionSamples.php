<?php

namespace App\Support;

use App\Models\QuizQuestion;

class ChordToneRecognitionSamples
{
    private const QUALITIES = [
        'diminished7' => [0, 3, 6, 9],
        'dominant7' => [0, 4, 7, 10],
        'm7b5' => [0, 3, 6, 10],
        'major7' => [0, 4, 7, 11],
        'minor7' => [0, 3, 7, 10],
        'aug7' => [0, 4, 8, 10],
        'minmaj7' => [0, 3, 7, 11],
        'dimmaj7' => [0, 3, 6, 11],
        'dom7b5' => [0, 4, 6, 10],
        'maj7s5' => [0, 4, 8, 11],
        'maj7b5' => [0, 4, 6, 11],
    ];

    private const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    private const DEGREES = ['1', '3', '5', '7'];

    // Matches render_chord_then_tone.sh / make_chord_then_tone_midi.py's
    // fixed timing exactly: CHORD_BEATS = 3.2 at FluidSynth's default
    // 120bpm (0.5s/beat) = 1.6s of chord before the isolated tone starts.
    private const TONE_OFFSET_SECONDS = 1.6;

    /**
     * All 6 Chord Tone Recognition quizzes play a chord, then —
     * immediately after — the one tone being tested, alone (see
     * gen_chord_tone_recognition{1,2}_voicings.php, which #3-#6 mirror
     * exactly). #1/#3/#5 are root position; #2/#4/#6 are 1st/2nd/3rd
     * inversion. #1 is the 5 7th Degree Chord qualities, #3 is the 6
     * Secondary 7th Chord qualities, #5 is both combined (11) — same
     * split for #2/#4/#6. The chord's 4 notes share offset 0.0
     * (simultaneous); the isolated tone gets TONE_OFFSET_SECONDS, matching
     * the live-mix engine's existing "chord, then melody note" convention
     * used by Chordal Melodies. Quality slugs never collide between the
     * two groups, so one QUALITIES map covers all 6 quizzes — only the
     * path prefix and root-vs-inverted shape differ.
     *
     * @return list<array{slug: string, degree: string, octave: int, offset: float}>|null
     */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^chord-tone-recognition-([123456])/([a-z-]+)/([a-z0-9-]+)\.wav$#', $path, $m)) {
            return null;
        }
        $slug = $instrument ?? $m[2];

        return in_array($m[1], ['1', '3', '5'], true)
            ? self::resolveRootPosition($slug, $m[3])
            : self::resolveInverted($slug, $m[3]);
    }

    private static function resolveRootPosition(string $slug, string $filename): ?array
    {
        $parts = explode('-', $filename);
        $key = array_search(array_shift($parts), self::KEYS, true);
        if ($key === false) {
            return null;
        }
        $quality = array_shift($parts);
        $intervals = self::QUALITIES[$quality] ?? null;
        $degree = array_shift($parts);
        $idx = array_search($degree, self::DEGREES, true);
        if ($intervals === null || $idx === false || $parts !== []) {
            return null;
        }

        $base = 48 + $key;
        $chordPitches = array_map(fn (int $i): int => $base + $i, $intervals);
        $tonePitch = $base + $intervals[$idx];

        return self::buildNotes($slug, $chordPitches, $tonePitch);
    }

    private static function resolveInverted(string $slug, string $filename): ?array
    {
        $parts = explode('-', $filename);
        $key = array_search(array_shift($parts), self::KEYS, true);
        if ($key === false) {
            return null;
        }
        $quality = array_shift($parts);
        $intervals = self::QUALITIES[$quality] ?? null;
        $invSlug = array_shift($parts);
        $degree = array_shift($parts);
        $idx = array_search($degree, self::DEGREES, true);
        if ($intervals === null || $idx === false || $parts !== [] || ! preg_match('/^inv([123])$/', (string) $invSlug, $m)) {
            return null;
        }
        $inv = (int) $m[1];

        $base = 48 + $key;
        $rootPosition = array_map(fn (int $i): int => $base + $i, $intervals);
        $chordPitches = self::rotation($rootPosition, $inv);
        sort($chordPitches);
        $tonePitch = $rootPosition[$idx] + ($idx < $inv ? 12 : 0);

        return self::buildNotes($slug, $chordPitches, $tonePitch);
    }

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    private static function buildNotes(string $slug, array $chordPitches, int $tonePitch): ?array
    {
        foreach ([0, 12, -12, 24, -24, 36, -36] as $shift) {
            $notes = [];
            foreach ($chordPitches as $pitch) {
                $note = NoteSample::fromRecordedMidiPitch($pitch + $shift, $slug);
                if ($note === null) {
                    continue 2;
                }
                $notes[] = ['slug' => $slug, 'degree' => $note['degree'], 'octave' => $note['octave'], 'offset' => 0.0];
            }
            $toneNote = NoteSample::fromRecordedMidiPitch($tonePitch + $shift, $slug);
            if ($toneNote === null) {
                continue;
            }
            $notes[] = ['slug' => $slug, 'degree' => $toneNote['degree'], 'octave' => $toneNote['octave'], 'offset' => self::TONE_OFFSET_SECONDS];

            return $notes;
        }

        return null;
    }

    /** @return list<int> */
    private static function rotation(array $notes, int $inv): array
    {
        $n = count($notes);
        $rotated = array_merge(array_slice($notes, $inv), array_slice($notes, 0, $inv));
        for ($i = 0; $i < $inv; $i++) {
            $rotated[$n - $inv + $i] += 12;
        }

        return $rotated;
    }

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function forQuestion(QuizQuestion $question, string $slug): ?array
    {
        $variants = $question->audioVariants;
        $path = $variants->firstWhere('sound', 'Piano')?->audio_path
            ?? $variants->first()?->audio_path ?? $question->audio_path;

        return $path ? self::resolve($path, $slug) : null;
    }
}
