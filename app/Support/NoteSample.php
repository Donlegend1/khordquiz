<?php

namespace App\Support;

/**
 * Resolves the live-mixing engine's "which single-note sample files, at what
 * offsets" from the storage paths the generated-audio pipeline already wrote
 * (quiz_question_audio.audio_path) — no new database column needed. Two path
 * shapes are understood today, both under storage/app/public/:
 *
 *   notes/{slug}/{degree}{octave-suffix}.wav
 *     — a single note, already exactly what we need. Octave suffix is empty
 *       at the quiz's base octave (3) and `-c{octave}` everywhere else, the
 *       same convention used throughout scripts/audio (e.g. attach_relative_
 *       tone_pitch_2_audio.php's $degreeSlug + octave-suffix logic).
 *
 *   intervals/{melodic|harmonic}-{ordinal}/{slug}/{degree1}-{degree2}[2].wav
 *     — two notes pre-rendered together. The folder's melodic/harmonic
 *       prefix is the only place the timing (sequential vs simultaneous)
 *       is recorded. Whether degree2 is up an octave from degree1's octave
 *       is sometimes marked explicitly in the filename (a literal trailing
 *       "2" on degree2, e.g. intervals/melodic-fourth/piano/so-do2.wav —
 *       confirmed this never appears as anything but "2", i.e. +1 octave,
 *       across every file in storage/app/public/intervals) and sometimes
 *       not marked at all (the Second/Third folders never carry it, e.g.
 *       melodic-third/piano/la-do.wav has no "2" suffix despite actually
 *       being A3→C4 by ear). Where it's absent we fall back to standard
 *       diatonic solfège scale-degree arithmetic — confirmed by ear against
 *       the unmarked files: do-mi.wav is C3→E3 (no wrap, Mi's degree index
 *       is higher than Do's), la-do.wav is A3→C4 (Do wraps up an octave
 *       because its degree index, 0, is lower than La's, 5).
 */
class NoteSample
{
    private const DEGREE_ORDER = ['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti'];

    private const DEGREE_SLUGS = ['Do' => 'do', 'Re' => 're', 'Mi' => 'mi', 'Fa' => 'fa', 'So' => 'so', 'La' => 'la', 'Ti' => 'ti'];

    // Find the Note's answers are chromatic letter names, not solfège — same
    // single-note path shape (notes/{slug}/{slug}{octave-suffix}.wav, e.g.
    // notes/piano/d.wav) and the same QuizController::NOTE_SLUGS labels, kept
    // in sync by hand since that constant is Controller-private. Intervals
    // are always solfège-only (Find the Note has no 2-note questions), so
    // this never needs to appear in the DEGREE_ORDER/wrap-math side of
    // things — only as an extra name this single-note branch recognizes.
    private const CHROMATIC_SLUGS = [
        'C' => 'c', 'C#/Db' => 'csharp', 'D' => 'd', 'D#/Eb' => 'dsharp', 'E' => 'e', 'F' => 'f',
        'F#/Gb' => 'fsharp', 'G' => 'g', 'G#/Ab' => 'gsharp', 'A' => 'a', 'A#/Bb' => 'asharp', 'B' => 'b',
    ];

    // The 7 natural letters are pitch-identical to a solfège degree, just
    // recorded under the Find the Note-style scheme's own filename — sharps
    // don't need an equivalent here since both schemes already share the
    // same literal sharp filenames (csharp.wav etc. — confirmed: an
    // instrument's solfège-scheme bank build already writes these once and
    // both schemes read the same file). Used only as a fallback when the
    // letter-named file itself doesn't exist for some instrument, so a
    // preview can still play from whichever scheme that instrument actually
    // has recorded — see AudioQuizController::missingInstrumentPreviews().
    private const NATURAL_LETTER_TO_DEGREE = ['C' => 'Do', 'D' => 'Re', 'E' => 'Mi', 'F' => 'Fa', 'G' => 'So', 'A' => 'La', 'B' => 'Ti'];

    // Major-scale semitone offset for each degree, relative to its own octave's Do.
    private const DEGREE_SEMITONES = [0, 2, 4, 5, 7, 9, 11];

    // Matches the measured ~0.75s onset spacing used for the Guitar melodic-
    // dictation sustain fix earlier this session — kept consistent rather
    // than inventing a new value.
    public const MELODIC_NOTE_SPACING_SECONDS = 0.75;

    private const BASE_OCTAVE = 3;

    /**
     * @return list<array{slug: string, degree: string, octave: int, offset: float}>|null
     *         null when $path doesn't match either known shape (caller should
     *         fall back to playing $path directly as one pre-rendered file).
     */
    public static function resolve(string $path): ?array
    {
        if (preg_match('#^notes/([^/]+)/([a-z]+)(?:-c(\d))?\.wav$#', $path, $m)) {
            $degree = array_search($m[2], self::DEGREE_SLUGS, true);
            $degree = $degree !== false ? $degree : array_search($m[2], self::CHROMATIC_SLUGS, true);
            if ($degree === false) {
                return null;
            }

            return [[
                'slug' => $m[1],
                'degree' => $degree,
                'octave' => ($m[3] ?? '') !== '' ? (int) $m[3] : self::BASE_OCTAVE,
                'offset' => 0.0,
            ]];
        }

        if (preg_match('#^intervals/(melodic|harmonic)-[a-z]+/([^/]+)/([a-z]+)-([a-z]+)(2?)\.wav$#', $path, $m)) {
            [$timing, $slug, $slug1, $slug2, $explicitWrap] = [$m[1], $m[2], $m[3], $m[4], $m[5]];
            $degree1 = array_search($slug1, self::DEGREE_SLUGS, true);
            $degree2 = array_search($slug2, self::DEGREE_SLUGS, true);
            if ($degree1 === false || $degree2 === false) {
                return null;
            }

            $wraps = $explicitWrap === '2' || self::degreeIndex($degree2) < self::degreeIndex($degree1);
            $octave2 = self::BASE_OCTAVE + ($wraps ? 1 : 0);

            return [
                ['slug' => $slug, 'degree' => $degree1, 'octave' => self::BASE_OCTAVE, 'offset' => 0.0],
                ['slug' => $slug, 'degree' => $degree2, 'octave' => $octave2, 'offset' => $timing === 'melodic' ? self::MELODIC_NOTE_SPACING_SECONDS : 0.0],
            ];
        }

        return null;
    }

    /**
     * The sample path for a single degree+octave, exactly as the generated-
     * audio pipeline names it — storage/app/public/notes/{slug}/... relative
     * path (no leading slash, no disk URL wrapper; callers add that).
     */
    public static function path(string $slug, string $degree, int $octave): string
    {
        $suffix = $octave === self::BASE_OCTAVE ? '' : "-c{$octave}";
        $degreeSlug = self::DEGREE_SLUGS[$degree] ?? self::CHROMATIC_SLUGS[$degree];

        return "notes/{$slug}/{$degreeSlug}{$suffix}.wav";
    }

    /**
     * Whether $label is a single recognized degree (solfège or chromatic
     * letter) — true for "D" or "Re", false for an interval pair ("Do - Mi"),
     * a chord-quiz answer ("Minor 7th"), etc. Lets a caller offer a live-mix
     * preview straight from a question's own answer (for instruments with no
     * generated audio_path at all) only where that answer unambiguously
     * names one note — see AudioQuizController::missingInstrumentPreviews().
     */
    public static function isSingleDegree(string $label): bool
    {
        return isset(self::DEGREE_SLUGS[$label]) || isset(self::CHROMATIC_SLUGS[$label]);
    }

    // The pitch-equivalent solfège degree for a natural letter answer (D ->
    // Re), or null for a sharp/flat or a label that isn't a single degree at
    // all — see NATURAL_LETTER_TO_DEGREE.
    public static function equivalentDegree(string $label): ?string
    {
        return self::NATURAL_LETTER_TO_DEGREE[$label] ?? null;
    }

    private static function degreeIndex(string $degree): int
    {
        return array_search($degree, self::DEGREE_ORDER, true);
    }
}
