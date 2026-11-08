<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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
    /** Scientific-pitch pool shared by the generator and keyboard audio map. */
    public const SCIENTIFIC_PITCH_OCTAVES = [1, 2, 3, 4, 5, 6, 7];

    /** Inclusive MIDI pitch limits for Find the Pitch; other quizzes retain their registers. */
    private const SCIENTIFIC_PITCH_RANGES = [
        'piano' => [36, 95],
        'ep' => [36, 95],
        'upright-piano' => [36, 95],
        'organ' => [36, 95],
        'harpejji' => [48, 107],
        'voice' => [43, 79],
        'ensemble-choir' => [40, 86],
    ];

    private const DEGREE_ORDER = ['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti'];

    private const DEGREE_SLUGS = ['Do' => 'do', 'Re' => 're', 'Mi' => 'mi', 'Fa' => 'fa', 'So' => 'so', 'La' => 'la', 'Ti' => 'ti'];

    // Find the Note's answers are chromatic letter names, not solfège — same
    // single-note path shape (notes/{slug}/{slug}{octave-suffix}.wav, e.g.
    // notes/piano/d.wav) and the same QuizController::NOTE_SLUGS labels, kept
    // in sync by hand since that constant is Controller-private. Intervals
    // are always solfège-only (Find the Note has no 2-note questions), so
    // this never needs to appear in the DEGREE_ORDER/wrap-math side of
    // things — only as an extra name this single-note branch recognizes.
    public const CHROMATIC_SLUGS = [
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

    // Instruments whose live-mix base octave sits a step below the quiz's
    // usual octave 3 — a real bass's open strings sit closer to octave 2.
    // Same convention already established for Find the Note/RTP1 via
    // QuizController::KEYBOARD_NOTE_SUFFIXES, extended here so it applies
    // everywhere the live-mixing engine resolves a note from an instrument
    // slug (resolve() and fromAnswer() below) — previously only those two
    // quiz types got the lower octave, via manually pre-rendered audio;
    // every other quiz that falls back to live mix (Melodic Second and
    // the rest) was still silently defaulting to octave 3 for these
    // instruments regardless of what any pre-rendered audio elsewhere said.
    private const BASE_OCTAVE_OVERRIDES = [
        'bass-guitar' => 2,
        // Upright Bass is deliberately NOT here — its note bank was rebuilt
        // from a new real recording covering only C3-C5 (no octave 2 at
        // all), so it now uses the default base octave 3 like most
        // instruments instead of the old override.
        // Bassoon's real range reaches down to B1 — 3-Note Stepwise/Skips
        // were deliberately built starting at C2 to use that extra low
        // range, same reasoning as Bass Guitar/Upright Bass above.
        'bassoon' => 2,
        // These instruments' Melodic Dictation quizzes (3-Note Stepwise,
        // 3-Note with Skips, 5-Note Melodies, Dyad Melodies) were built in
        // an earlier session starting at a non-default octave per
        // instrument — discovered when the admin "Live mix" preview was
        // silently reconstructing the wrong octave for them (for Cello/
        // Strings/Tuba/Violin this actually caused real content to be
        // misidentified as out-of-range and deleted; restored afterward).
        // Guitar and the whole Piano family (Piano/EP/Upright Piano/Organ)
        // are deliberately NOT here — their Melodic Dictation audio was
        // standardized to the default octave 3 (C3 base, A2-C6 hard range)
        // instead of restoring their old octave-2 content.
        'harp' => 2,
        'jazz-guitar' => 2,
        'cello' => 2,
        'strings' => 2,
        'tuba' => 1,
        'violin' => 4,
    ];

    // Instruments whose note bank was built from real individually-recorded
    // chromatic notes at every octave (not the legacy single-sample-plus-
    // pitch-shift scheme every other instrument still uses) — their bare
    // chromatic filename genuinely is octave 3, same as diatonic notes, so
    // no "-1" compensation is needed when computing which file a chromatic
    // note at a given true octave lives in.
    private const FULL_CHROMATIC_BANK_SLUGS = [
        'piano', 'ep', 'upright-piano', 'organ', 'guitar', 'electric-guitar', 'harpejji',
        'strings', 'upright-bass', 'flute', 'oboe', 'marimba', 'steel-drum',
        'ensemble-choir', 'sustained-choir', 'tropar-choir',
    ];

    // Per-instrument, per-VARIANT register shift applied to Chordal melodies
    // ("orig" — the close-position voicing) and Chordal Bass Lines ("drop2" —
    // the same progression with its 2nd-from-top voice dropped an octave)
    // when each instrument's audio was built. Restricted to whole-octave
    // shifts only (never a plain interval like +7/-7), since shifting by
    // anything other than a multiple of 12 semitones moves the whole
    // progression out of the key of C (confirmed: an earlier version of this
    // table used +7/-7 "for maximum coverage" and it silently broke the key
    // for 8 instruments — some chords landed on pitch classes outside the
    // diatonic set). The two variants are optimized INDEPENDENTLY (not one
    // shared shift) since drop2's lower bass note often needs a different
    // octave-safe shift than the original voicing to stay in range — e.g.
    // Saxophone's orig fits at shift 0 but its drop2 only fits at shift 12.
    // Picked per instrument/variant as whichever octave-safe shift maximized
    // how many of the 13 progressions were fully playable in that
    // instrument's real recorded range; chords need more simultaneous range
    // than a single melody line, so some instrument/variant combinations
    // still miss a few regardless. Bass Guitar, Tuba, and Upright Bass are
    // excluded entirely by explicit request, not a range limitation. Keep in
    // sync by hand with whatever shift a future rebuild uses.
    private const CHORDAL_TRANSPOSE_SEMITONES = [
        'guitar' => ['orig' => 0, 'drop2' => 0],
        'electric-guitar' => ['orig' => 0, 'drop2' => 0],
        'harpejji' => ['orig' => 12, 'drop2' => 12],
        'violin' => ['orig' => 0, 'drop2' => 12],
        'strings' => ['orig' => 0, 'drop2' => 0],
        'cello' => ['orig' => -12, 'drop2' => 0],
        'saxophone' => ['orig' => 0, 'drop2' => 12],
        'trombone' => ['orig' => 0, 'drop2' => 0],
        'trumpet' => ['orig' => 0, 'drop2' => 12],
        'voice' => ['orig' => 0, 'drop2' => 0],
        'tropar-choir' => ['orig' => 0, 'drop2' => 0],
        'ensemble-choir' => ['orig' => 0, 'drop2' => 0],
        'sustained-choir' => ['orig' => 0, 'drop2' => 12],
        'marimba' => ['orig' => 0, 'drop2' => 12],
        'steel-drum' => ['orig' => 0, 'drop2' => 0],
        'celesta' => ['orig' => 0, 'drop2' => 12],
        'vibraphone' => ['orig' => 0, 'drop2' => 12],
        'flute' => ['orig' => 0, 'drop2' => 12],
        'clarinet' => ['orig' => 0, 'drop2' => 12],
        'oboe' => ['orig' => 0, 'drop2' => 12],
        'bassoon' => ['orig' => -12, 'drop2' => 0],
        'organ' => ['orig' => 0, 'drop2' => 12],
    ];

    // Find the Note and Relative Tone Pitch #1 only (the two single-degree
    // branches in fromAnswer() below) — per explicit request,
    // piano/guitar/strings/brass/woodwind families play one octave higher
    // than their resolved default, with named exceptions per family that
    // already sit at the right register on their own: Bass Guitar (guitar
    // family), Upright Bass (strings family), Tuba (brass family), Clarinet
    // (woodwind family).
    private const SINGLE_DEGREE_OCTAVE_UP_SLUGS = [
        'piano', 'ep', 'organ', 'upright-piano',
        'guitar', 'electric-guitar', 'harpejji',
        'strings', 'cello', 'violin',
        'trombone', 'trumpet',
        'flute', 'oboe', 'bassoon',
    ];

    private static function baseOctave(?string $slug): int
    {
        return self::BASE_OCTAVE_OVERRIDES[$slug] ?? self::BASE_OCTAVE;
    }

    /**
     * @return list<array{slug: string, degree: string, octave: int, offset: float}>|null
     *                                                                                    null when $path doesn't match either known shape (caller should
     *                                                                                    fall back to playing $path directly as one pre-rendered file).
     */
    public static function resolve(string $path): ?array
    {
        if (preg_match('#^notes/([a-z-]+)/midi-(\d{1,3})\.wav$#', $path, $match)) {
            $note = self::fromRecordedMidiPitch((int) $match[2], $match[1]);

            return $note !== null && self::usesMidiBank($match[1]) ? [['slug' => $match[1], ...$note]] : null;
        }

        if (str_starts_with($path, IonianLydianScaleSamples::PREFIX.'/')) {
            return IonianLydianScaleSamples::resolve($path);
        }
        if (str_starts_with($path, DorianAeolianScaleSamples::PREFIX.'/')) {
            return DorianAeolianScaleSamples::resolve($path);
        }
        if (str_starts_with($path, PhrygianLocrianScaleSamples::PREFIX.'/')) {
            return PhrygianLocrianScaleSamples::resolve($path);
        }
        if (str_starts_with($path, MajorScaleSamples::PREFIX.'/')) {
            return MajorScaleSamples::resolve($path);
        }
        if (str_starts_with($path, MajorScaleAllKeysSamples::PREFIX.'/')) {
            return MajorScaleAllKeysSamples::resolve($path);
        }

        if (str_starts_with($path, TonalModeScaleSamples::PREFIX.'/')) {
            return TonalModeScaleSamples::resolve($path);
        }
        if (str_starts_with($path, TonalModeAllKeysSamples::PREFIX.'/')) {
            return TonalModeAllKeysSamples::resolve($path);
        }
        if (str_starts_with($path, DorianScaleSamples::PREFIX.'/')) {
            return DorianScaleSamples::resolve($path);
        }
        if (str_starts_with($path, DorianScaleAllKeysSamples::PREFIX.'/')) {
            return DorianScaleAllKeysSamples::resolve($path);
        }
        if (str_starts_with($path, LydianScaleSamples::PREFIX.'/')) {
            return LydianScaleSamples::resolve($path);
        }
        if (str_starts_with($path, LydianScaleAllKeysSamples::PREFIX.'/')) {
            return LydianScaleAllKeysSamples::resolve($path);
        }
        if (str_starts_with($path, PhrygianScaleSamples::PREFIX.'/')) {
            return PhrygianScaleSamples::resolve($path);
        }
        if (str_starts_with($path, PhrygianScaleAllKeysSamples::PREFIX.'/')) {
            return PhrygianScaleAllKeysSamples::resolve($path);
        }
        if (str_starts_with($path, MixolydianScaleSamples::PREFIX.'/')) {
            return MixolydianScaleSamples::resolve($path);
        }
        if (str_starts_with($path, MixolydianScaleAllKeysSamples::PREFIX.'/')) {
            return MixolydianScaleAllKeysSamples::resolve($path);
        }
        if (str_starts_with($path, AeolianScaleSamples::PREFIX.'/')) {
            return AeolianScaleSamples::resolve($path);
        }
        if (str_starts_with($path, AeolianScaleAllKeysSamples::PREFIX.'/')) {
            return AeolianScaleAllKeysSamples::resolve($path);
        }
        if (str_starts_with($path, LocrianScaleSamples::PREFIX.'/')) {
            return LocrianScaleSamples::resolve($path);
        }
        if (str_starts_with($path, LocrianScaleAllKeysSamples::PREFIX.'/')) {
            return LocrianScaleAllKeysSamples::resolve($path);
        }
        if (str_starts_with($path, IonianScaleSamples::PREFIX.'/')) {
            return IonianScaleSamples::resolve($path);
        }
        if (str_starts_with($path, IonianScaleAllKeysSamples::PREFIX.'/')) {
            return IonianScaleAllKeysSamples::resolve($path);
        }

        if (str_starts_with($path, ModalHarmonySamples::PREFIX.'/') || str_starts_with($path, ModalHarmonySamples::MINOR_PREFIX.'/')) {
            return ModalHarmonySamples::resolve($path);
        }

        if (str_starts_with($path, ModalVoicingLessons::PREFIX.'/')) {
            return ModalVoicingLessons::resolve($path);
        }

        if (str_starts_with($path, MajorModeVoicingSamples::PREFIX.'/')) {
            return MajorModeVoicingSamples::resolve($path);
        }
        if (str_starts_with($path, MinorModeVoicingSamples::PREFIX.'/')) {
            return MinorModeVoicingSamples::resolve($path);
        }

        if (str_starts_with($path, IonianVoicingSamples::PREFIX.'/')) {
            return IonianVoicingSamples::resolve($path);
        }

        if (str_starts_with($path, AeolianVoicingSamples::PREFIX.'/')) {
            return AeolianVoicingSamples::resolve($path);
        }

        if (str_starts_with($path, DorianVoicingSamples::PREFIX.'/')) {
            return DorianVoicingSamples::resolve($path);
        }

        if (str_starts_with($path, LocrianVoicingSamples::PREFIX.'/')) {
            return LocrianVoicingSamples::resolve($path);
        }

        if (str_starts_with($path, PhrygianVoicingSamples::PREFIX.'/')) {
            return PhrygianVoicingSamples::resolve($path);
        }

        if (str_starts_with($path, MixolydianVoicingSamples::PREFIX.'/')) {
            return MixolydianVoicingSamples::resolve($path);
        }

        if (str_starts_with($path, ModalVoicingSamples::PREFIX.'/')) {
            return ModalVoicingSamples::resolve($path);
        }

        if (str_starts_with($path, TwoFiveOneExtensionSamples::PREFIX.'/')) {
            return TwoFiveOneExtensionSamples::resolve($path);
        }

        if (str_starts_with($path, ProgressionRecognitionInversionSamples::PREFIX.'/') || str_starts_with($path, ProgressionRecognitionInversionSamples::PART_FOUR_PREFIX.'/')) {
            return ProgressionRecognitionInversionSamples::resolve($path);
        }

        if (str_starts_with($path, ProgressionRecognitionSamples::PREFIX.'/') || str_starts_with($path, ProgressionRecognitionSamples::PART_TWO_PREFIX.'/')) {
            return ProgressionRecognitionSamples::resolve($path);
        }

        if (str_starts_with($path, ChordalProgressionSamples::PREFIX.'/')) {
            return ChordalProgressionSamples::resolve($path);
        }

        if (str_starts_with($path, 'progression-build-two/')) {
            return SeventhProgressionBuildSamples::resolve($path);
        }

        if (str_starts_with($path, 'progression-build-one/')) {
            return ProgressionBuildSamples::resolve($path);
        }

        if (str_starts_with($path, 'diatonic-seventh-progressions/')) {
            return DiatonicSeventhProgressionSamples::resolve($path);
        }

        if (str_starts_with($path, 'diatonic-progressions/')) {
            return DiatonicProgressionSamples::resolve($path);
        }

        if (str_starts_with($path, 'cadences/')) {
            return CadenceSamples::resolve($path);
        }

        if (str_starts_with($path, 'plain-chord-extensions/')) {
            return PlainChordExtensionSamples::resolve($path);
        }

        if (str_starts_with($path, 'add9/')) {
            return AddNineSamples::resolve($path);
        }

        if (str_starts_with($path, 'basic-triads/')) {
            return BasicTriadSamples::resolve($path);
        }

        if (str_starts_with($path, 'seventh-degree-chords/')) {
            return SeventhDegreeChordSamples::resolve($path);
        }

        if (str_starts_with($path, 'secondary-seventh-chords/')) {
            return SecondarySeventhChordSamples::resolve($path);
        }

        if (str_starts_with($path, 'ninth-degree-chords/')) {
            return NinthDegreeChordSamples::resolve($path);
        }

        if (str_starts_with($path, 'secondary-ninth-chords/')) {
            return SecondaryNinthChordSamples::resolve($path);
        }

        if (str_starts_with($path, 'eleventh-degree-chords/')) {
            return EleventhDegreeChordSamples::resolve($path);
        }

        if (str_starts_with($path, 'secondary-eleventh-chords/')) {
            return SecondaryEleventhChordSamples::resolve($path);
        }

        if (str_starts_with($path, 'thirteenth-degree-chords/')) {
            return ThirteenthDegreeChordSamples::resolve($path);
        }

        if (str_starts_with($path, 'chord-tone-recognition-')) {
            return ChordToneRecognitionSamples::resolve($path);
        }

        if (str_starts_with($path, 'chord-naming/basic-v1/') || str_starts_with($path, 'chord-naming/basic-group2-v1/') || str_starts_with($path, 'chord-naming/basic-group3-v1/')) {
            return BasicChordNamingSamples::resolve($path);
        }

        if (str_starts_with($path, 'dominant-chord-extensions/')) {
            return DominantChordExtensionSamples::resolve($path);
        }

        if (str_starts_with($path, 'minor-chord-extensions/')) {
            return MinorChordExtensionSamples::resolve($path);
        }

        if (str_starts_with($path, 'major-chord-extensions/')) {
            return MajorChordExtensionSamples::resolve($path);
        }

        if (preg_match('#^notes/([^/]+)/([a-z]+)(?:-c(\d))?\.wav$#', $path, $m)) {
            $degree = array_search($m[2], self::DEGREE_SLUGS, true);
            $degree = $degree !== false ? $degree : array_search($m[2], self::CHROMATIC_SLUGS, true);
            if ($degree === false) {
                return null;
            }

            return [[
                'slug' => $m[1],
                'degree' => $degree,
                // A bare filename (no "-cN" suffix) always means BASE_OCTAVE —
                // that's path()'s own convention, independent of any per-
                // instrument BASE_OCTAVE_OVERRIDES (those only steer where
                // fromAnswer() starts searching, never change what a stored
                // filename itself means). Using baseOctave($m[1]) here used to
                // silently misread e.g. Cello's "do.wav" (true octave 3) as
                // octave 2, landing on a different, real "do-c2.wav" instead —
                // wrong pitch, no error, since that file coincidentally exists.
                'octave' => ($m[3] ?? '') !== '' ? (int) $m[3] : self::BASE_OCTAVE,
                'offset' => 0.0,
            ]];
        }

        if (preg_match('#^reference-intervals/live-v1/(melodic|harmonic)/([a-z-]+)/(\d+)-(\d+)\.wav$#', $path, $matches)) {
            [, $timing, $slug, $first, $second] = $matches;
            $notes = IntervalSamples::notesForPair((int) $first, (int) $second, $timing, $slug);

            return $notes === null ? null : array_map(fn (array $note) => ['slug' => $slug, ...$note], $notes);
        }

        if (preg_match('#^intervals/(melodic|harmonic)-[a-z]+/([^/]+)/([a-z]+)-([a-z]+)(2?)\.wav$#', $path, $m)) {
            [$timing, $slug, $slug1, $slug2, $explicitWrap] = [$m[1], $m[2], $m[3], $m[4], $m[5]];
            $degree1 = array_search($slug1, self::DEGREE_SLUGS, true);
            $degree2 = array_search($slug2, self::DEGREE_SLUGS, true);
            if ($degree1 === false || $degree2 === false) {
                return null;
            }

            $base = self::baseOctave($slug);
            $wraps = $explicitWrap === '2' || self::degreeIndex($degree2) < self::degreeIndex($degree1);
            $octave2 = $base + ($wraps ? 1 : 0);

            return [
                ['slug' => $slug, 'degree' => $degree1, 'octave' => $base, 'offset' => 0.0],
                ['slug' => $slug, 'degree' => $degree2, 'octave' => $octave2, 'offset' => $timing === 'melodic' ? self::MELODIC_NOTE_SPACING_SECONDS : 0.0],
            ];
        }

        // melodic-dictation/{3-note-stepwise,3-note-skips,5-note-melodies}/{slug}/
        // {degree1}-{degree2}-...-{degreeN}-o{slot}.wav — every melodic-dictation
        // quiz whose patterns are a plain note sequence (as opposed to Dyad/Chordal
        // quizzes' voicings) reuses stepwiseSlugForIndex() directly for its
        // filenames (three_note_stepwise_pool.php, three_note_skips_pool.php,
        // five_note_melodies_pool.php), so they all share this one shape
        // regardless of note count. Each degree slug optionally carries a run of
        // "h" (lands an octave above the pattern's own first note) or "l" (an
        // octave below), same convention as the interval quizzes'
        // apostrophe/comma markers, just applied within the melody's own window —
        // this just reverses stepwiseSlugForIndex(). "o{slot}" is which of the
        // pool's starting octaves this row is — slot directly adds to the base
        // octave since each slot shifts the whole pattern up exactly one diatonic
        // octave.
        if (preg_match('#^melodic-dictation/(?:3-note-stepwise|3-note-skips|5-note-melodies)/([^/]+)/((?:[a-z]+(?:h+|l+)?-)+[a-z]+(?:h+|l+)?)-o(\d)\.wav$#', $path, $m)) {
            [, $slug, $noteSlugs, $slot] = $m;
            $base = self::baseOctave($slug) + (int) $slot;

            $notes = [];
            foreach (explode('-', $noteSlugs) as $i => $noteSlug) {
                if (! preg_match('#^([a-z]+?)(h+|l+)?$#', $noteSlug, $nm)) {
                    return null;
                }
                $degree = array_search($nm[1], self::DEGREE_SLUGS, true);
                if ($degree === false) {
                    return null;
                }
                $shift = match (true) {
                    ($nm[2] ?? '') === '' => 0,
                    $nm[2][0] === 'h' => strlen($nm[2]),
                    default => -strlen($nm[2]),
                };

                $notes[] = [
                    'slug' => $slug,
                    'degree' => $degree,
                    'octave' => $base + $shift,
                    'offset' => $i * self::MELODIC_NOTE_SPACING_SECONDS,
                ];
            }

            return $notes;
        }

        // melodic-dictation/dyad-melodies/{slug}/{fileSlug}-o{slot}.wav — each
        // position is TWO simultaneous notes (a diatonic 3rd or 4th harmony
        // below a top melody note), but the filename only encodes the TOP
        // line (dyadMelodyPatterns() reuses 5-Note Melodies' fileSlug
        // verbatim), so which interval each position uses isn't recoverable
        // from the filename text alone — it depends on that pattern's
        // position in the pool (scripts/audio/dyad_melodies_pool.php
        // alternates 3rd/4th per note based on a running pattern index).
        // Look the pattern up by its fileSlug instead of regex-decoding it.
        if (preg_match('#^melodic-dictation/dyad-melodies/([^/]+)/(.+)-o(\d)\.wav$#', $path, $m)) {
            [, $slug, $fileSlug, $slot] = $m;

            static $dyadPatternsByFileSlug = null;
            if ($dyadPatternsByFileSlug === null) {
                require_once base_path('scripts/audio/dyad_melodies_pool.php');
                $dyadPatternsByFileSlug = [];
                foreach (dyadMelodyPatterns() as $pattern) {
                    $dyadPatternsByFileSlug[$pattern['fileSlug']] = $pattern;
                }
            }

            $pattern = $dyadPatternsByFileSlug[$fileSlug] ?? null;
            if (! $pattern) {
                return null;
            }

            $base = self::baseOctave($slug) + (int) $slot;
            $notes = [];
            foreach ($pattern['dyads'] as $i => $dyad) {
                foreach (['harmonyIdx', 'topIdx'] as $key) {
                    $idx = $dyad[$key];
                    $octaveShift = intdiv($idx, 7);
                    $degreeIndex = $idx % 7;
                    if ($degreeIndex < 0) {
                        $degreeIndex += 7;
                        $octaveShift--;
                    }

                    $notes[] = [
                        'slug' => $slug,
                        'degree' => self::DEGREE_ORDER[$degreeIndex],
                        'octave' => $base + $octaveShift,
                        'offset' => $i * self::MELODIC_NOTE_SPACING_SECONDS,
                    ];
                }
            }

            return $notes;
        }

        // melodic-dictation/dual-tonic-melodies/{slug}/{fileSlug}-o{slot}.wav
        // — same "look the pattern up by its fileSlug" approach as Dyad
        // Melodies above, for the same reason: a chromatic passing tone's
        // filename segment ("dos", "fis", ...) only encodes WHICH diatonic
        // note it sits a half-step above, not which octave slot — that's
        // recovered from the pattern's own 'notes' definition instead.
        // Chromatic notes map to a nominal octave via plain MIDI//12-1
        // bucketing (matching how the chromatic sample bank itself is
        // suffixed) rather than the diatonic degree-index math above, since
        // a chromatic passing tone isn't on the 7-degree scale at all.
        if (preg_match('#^melodic-dictation/dual-tonic-melodies/([^/]+)/(.+)-o(\d)\.wav$#', $path, $m)) {
            [, $slug, $fileSlug, $slot] = $m;

            static $dualTonicPatternsByFileSlug = null;
            if ($dualTonicPatternsByFileSlug === null) {
                require_once base_path('scripts/audio/dual_tonic_melodies_pool.php');
                $dualTonicPatternsByFileSlug = [];
                foreach (dualTonicMelodyPatterns() as $pattern) {
                    $dualTonicPatternsByFileSlug[$pattern['fileSlug']] = $pattern;
                }
            }

            $pattern = $dualTonicPatternsByFileSlug[$fileSlug] ?? null;
            if (! $pattern) {
                return null;
            }

            $base = self::baseOctave($slug) + (int) $slot;
            $absoluteMidiBase = ($base + 1) * 12; // octave number -> absolute MIDI of that octave's Do
            $chromaticNames = [1 => 'C#/Db', 3 => 'D#/Eb', 6 => 'F#/Gb', 8 => 'G#/Ab', 10 => 'A#/Bb'];

            $notes = [];
            foreach ($pattern['notes'] as $i => $n) {
                if ($n['type'] === 'diatonic') {
                    $octaveShift = intdiv($n['idx'], 7);
                    $degreeIndex = $n['idx'] % 7;
                    if ($degreeIndex < 0) {
                        $degreeIndex += 7;
                        $octaveShift--;
                    }

                    $notes[] = [
                        'slug' => $slug,
                        'degree' => self::DEGREE_ORDER[$degreeIndex],
                        'octave' => $base + $octaveShift,
                        'offset' => $i * self::MELODIC_NOTE_SPACING_SECONDS,
                    ];

                    continue;
                }

                $midi = self::stepwiseMidiForDegreeIndex($n['idx'], $absoluteMidiBase) + 1;
                $trueOctave = intdiv($midi, 12) - 1;
                $remainder = $midi - ($trueOctave + 1) * 12;
                if (! isset($chromaticNames[$remainder])) {
                    return null;
                }

                // Legacy chromatic sample files are recorded a full octave
                // higher than their suffix implies (confirmed by
                // measurement: a "bare"/no-suffix file like asharp.wav is
                // really A#4, not A#3 like a bare diatonic file would be) —
                // path()'s suffix rule still only omits the suffix at
                // octave 3, so the octave handed to it must be shifted down
                // by 1 to land on the file that actually sounds at
                // $trueOctave. Instruments in FULL_CHROMATIC_BANK_SLUGS have
                // real recordings at every octave instead, with no such
                // offset — their bare file genuinely is octave 3.
                $octaveShift = self::usesMidiBank($slug) || in_array($slug, self::FULL_CHROMATIC_BANK_SLUGS, true) ? 0 : 1;
                $notes[] = [
                    'slug' => $slug,
                    'degree' => $chromaticNames[$remainder],
                    'octave' => $trueOctave - $octaveShift,
                    'offset' => $i * self::MELODIC_NOTE_SPACING_SECONDS,
                ];
            }

            return $notes;
        }

        // melodic-dictation/chordal-melodies/{slug}/p{i}-(orig|drop2).wav —
        // shared verbatim by Chordal Bass Lines too (same audio file, just a
        // different stored answer/label pointing at it — see
        // scripts/audio/share_chordal_melody_bass_audio.php), so both quizzes'
        // paths always live under the chordal-melodies folder regardless of
        // which quiz's row references them. Each "note" here is actually a
        // simultaneous CHORD (several notes sharing one offset), unlike every
        // other branch above. $i indexes chordalLiteralProgressions();
        // "orig" is that pattern's close-position voicing, "drop2" is the
        // same progression with its 2nd-from-top voice dropped an octave.
        // An optional "-up12" suffix raises the whole progression one octave
        // without changing shared files used by the other chordal quiz.
        if (preg_match('#^melodic-dictation/chordal-melodies/([^/]+)/p(\d+)-(orig|drop2)(?:-up(12))?\.wav$#', $path, $m)) {
            [, $slug, $i, $variant] = $m;

            static $chordalProgressions = null;
            if ($chordalProgressions === null) {
                require_once base_path('scripts/audio/chordal_melodies_pool.php');
                $chordalProgressions = chordalLiteralProgressions();
            }

            $steps = $chordalProgressions[(int) $i] ?? null;
            if ($steps === null) {
                return null;
            }

            $voicings = chordalBuildVoicings($steps);
            if ($variant === 'drop2') {
                $voicings = chordalDrop2Voicings($voicings);
            }

            $transpose = (self::CHORDAL_TRANSPOSE_SEMITONES[$slug][$variant] ?? 0) + (int) ($m[4] ?? 0);
            foreach ([0, 12, -12, 24, -24] as $shift) {
                $notes = [];
                foreach ($voicings as $step => $chord) {
                    foreach ($chord as $midi) {
                        $note = self::fromRecordedMidiPitch($midi + $transpose + $shift, $slug);
                        if ($note === null) {
                            continue 3;
                        }
                        $notes[] = ['slug' => $slug, ...$note, 'offset' => $step * self::MELODIC_NOTE_SPACING_SECONDS];
                    }
                }

                return $notes;
            }

            return null;
        }

        // melodic-dictation/chordal-bass-lines/{slug}/cbl{n}.wav — independent
        // bass-first content (scripts/audio/chordal_bass_lines_pool.php), NOT
        // derived from and not shared with the chordal-melodies pool above.
        // $n is 1-based, indexing chordalBassLineLiteralProgressions() at
        // $n-1. Every CBL sequence IS its own final sounding (already-drop2)
        // voicing — there is no separate "orig" variant for this quiz. Each
        // "note" here is a simultaneous CHORD, like the branch above.
        if (preg_match('#^melodic-dictation/chordal-bass-lines/([^/]+)/cbl(\d+)\.wav$#', $path, $m)) {
            [, $slug, $n] = $m;

            static $chordalBassLines = null;
            if ($chordalBassLines === null) {
                require_once base_path('scripts/audio/chordal_bass_lines_pool.php');
                $chordalBassLines = chordalBassLineLiteralProgressions();
            }

            $steps = $chordalBassLines[(int) $n - 1] ?? null;
            if ($steps === null) {
                return null;
            }

            $voicings = chordalDrop2Voicings(chordalBuildVoicings($steps));

            foreach ([0, 12, -12, 24, -24] as $shift) {
                $notes = [];
                foreach ($voicings as $step => $chord) {
                    foreach ($chord as $midi) {
                        $note = self::fromRecordedMidiPitch($midi + $shift, $slug);
                        if ($note === null) {
                            continue 3;
                        }
                        $notes[] = ['slug' => $slug, ...$note, 'offset' => $step * self::MELODIC_NOTE_SPACING_SECONDS];
                    }
                }

                return $notes;
            }

            return null;
        }

        return null;
    }

    // Mirrors scripts/audio/three_note_stepwise_pool.php's stepwiseMidiForIndex()
    // — duplicated here (rather than requiring that file for one function) since
    // this is the only place in resolve() that needs raw MIDI instead of a
    // degree+octave pair.
    private static function stepwiseMidiForDegreeIndex(int $idx, int $base): int
    {
        $octave = intdiv($idx, 7);
        $degree = $idx % 7;
        if ($degree < 0) {
            $degree += 7;
            $octave--;
        }

        return $base + $octave * 12 + self::DEGREE_SEMITONES[$degree];
    }

    /**
     * The sample path for a single degree+octave, exactly as the generated-
     * audio pipeline names it — storage/app/public/notes/{slug}/... relative
     * path (no leading slash, no disk URL wrapper; callers add that).
     */
    public static function path(string $slug, string $degree, int $octave): string
    {
        if (self::usesMidiBank($slug)) {
            $index = array_search($degree, self::DEGREE_ORDER, true);
            $semitone = $index === false ? array_search($degree, array_keys(self::CHROMATIC_SLUGS), true) : self::DEGREE_SEMITONES[$index];
            $midi = ($octave + 1) * 12 + $semitone;

            return "notes/{$slug}/midi-{$midi}.wav";
        }

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

    /**
     * Builds live-mix note data straight from a question's own answer text
     * ("Re", "Do - Mi") instead of an existing audio_path — lets an
     * instrument become playable the moment it has a note sample library on
     * disk, with no quiz_question_audio row ever attached for it. Mirrors
     * the mobile app's src/lib/note-sample.ts parseSolfegeAnswer() exactly
     * (keep the two in sync by hand) — both rely only on the diatonic
     * scale-degree wrap fallback (no explicit-marker filename to read),
     * which is safe here: every interval file in storage/app/public/
     * intervals/ that DOES carry an explicit marker agrees with what this
     * fallback alone would have computed (checked across every 2nd-7th —
     * see NoteSample's class doc), so there's no case where skipping the
     * marker changes the answer.
     *
     * $slug picks the instrument's live-mix base octave (see
     * BASE_OCTAVE_OVERRIDES) — optional and defaulting to the standard
     * octave 3 so existing call sites that don't care which instrument
     * (or don't yet know it) keep behaving exactly as before.
     *
     * @return list<array{degree: string, octave: int, offset: float}>|null
     *                                                                      null when $answer isn't a plain solfège degree or pair.
     */
    public static function fromAnswer(string $answer, string $timing, ?string $slug = null): ?array
    {
        $base = self::baseOctave($slug);
        // Fourth-and-wider interval answers that wrap into the next octave
        // carry a trailing "'" on the second degree (e.g. "So - Do'") -
        // confirmed redundant with the index-comparison wrap rule below
        // (checked against every apostrophe-marked answer in quiz_questions:
        // 0 disagreements), so it's only ever a parsing concern here, not a
        // second source of truth - stripped before any degree lookup.
        $parts = array_map(fn ($part) => rtrim(trim($part), "'"), explode(' - ', $answer));

        if (count($parts) === 1) {
            if (isset(self::DEGREE_SLUGS[$parts[0]])) {
                // $base itself can be missing for some instrument/degree
                // combinations (e.g. Tuba has no "do-c1.wav" at its own
                // override octave 1, only octave 2 upward) — search upward
                // to the always-present octave 3 for the lowest real file,
                // same fallback the letter-scheme branch below already has.
                $octave = $base;
                if ($slug !== null) {
                    foreach (range($base, self::BASE_OCTAVE) as $candidate) {
                        if (self::sampleExists(self::path($slug, $parts[0], $candidate))) {
                            $octave = $candidate;
                            break;
                        }
                    }
                }

                // Relative Tone Pitch #1 only, per the same explicit request
                // as Find the Note — bump one octave up from there when a
                // real file exists a step higher, else leave it alone. This
                // branch is only reached by RTP1 (interval quizzes use the
                // two-degree branch below; Melodic Dictation doesn't call
                // fromAnswer() at all), so it can't leak into anything else.
                if ($slug !== null && in_array($slug, self::SINGLE_DEGREE_OCTAVE_UP_SLUGS, true)
                    && self::sampleExists(self::path($slug, $parts[0], $octave + 1))
                ) {
                    $octave++;
                }

                return [['degree' => $parts[0], 'octave' => $octave, 'offset' => 0.0]];
            }
            // Find the Note's chromatic letter scheme (C, C#/Db, D, ...)
            // isn't built for every octave on every instrument the way the
            // solfège scheme is — some instruments (e.g. Bass Guitar) do
            // have a real lower-octave letter set matching their solfège
            // baseOctave() override, others only ever got one fixed octave
            // (path()'s "bare" octave 3), and the 16 instruments rebuilt
            // from real recordings this session got no letter files at all,
            // only solfège ones. Try, in order: this instrument's own
            // (possibly overridden) base octave upward to the always-present
            // octave 3 — not straight to octave 3, since a bass instrument's
            // override octave can be missing just one or two degrees (e.g.
            // Tuba has no "Do" at octave 1, but does at octave 2) and should
            // still land as low as real content allows, not jump all the
            // way up — trying the natural-letter's pitch-identical solfège
            // equivalent at each octave too before moving on.
            if (isset(self::CHROMATIC_SLUGS[$parts[0]])) {
                $degree = $parts[0];
                $equivalent = self::equivalentDegree($degree);
                $found = null;
                foreach (range($base, self::BASE_OCTAVE) as $octave) {
                    if ($slug === null) {
                        break;
                    }
                    if (self::sampleExists(self::path($slug, $degree, $octave))) {
                        $found = ['degree' => $degree, 'octave' => $octave];
                        break;
                    }
                    if ($equivalent !== null && self::sampleExists(self::path($slug, $equivalent, $octave))) {
                        $found = ['degree' => $equivalent, 'octave' => $octave];
                        break;
                    }
                }
                $found ??= ['degree' => $equivalent ?? $degree, 'octave' => self::BASE_OCTAVE];

                // Find the Note only, per explicit request: these instruments
                // sat too low/neutral by default, so bump up one octave —
                // only if a real file exists a step higher (trying the
                // solfège equivalent too, same as the search above, since
                // e.g. Trombone has "do-c4.wav" but no letter-scheme
                // "c-c4.wav"), else leave the found octave alone rather
                // than silently fail.
                if ($slug !== null && in_array($slug, self::SINGLE_DEGREE_OCTAVE_UP_SLUGS, true)) {
                    $nextOctave = $found['octave'] + 1;
                    if (self::sampleExists(self::path($slug, $found['degree'], $nextOctave))) {
                        $found['octave'] = $nextOctave;
                    } elseif ($equivalent !== null && self::sampleExists(self::path($slug, $equivalent, $nextOctave))) {
                        $found = ['degree' => $equivalent, 'octave' => $nextOctave];
                    }
                }

                return [['degree' => $found['degree'], 'octave' => $found['octave'], 'offset' => 0.0]];
            }

            return null;
        }

        if (count($parts) !== 2 || ! isset(self::DEGREE_SLUGS[$parts[0]]) || ! isset(self::DEGREE_SLUGS[$parts[1]])) {
            return null;
        }

        [$degree1, $degree2] = $parts;
        $octave1 = $base;
        // Same fallback as the single-degree branch above: $base itself can
        // be missing (e.g. Tuba has no "do-c1.wav" at its own override
        // octave 1) — search upward to octave 3 for the lowest real file,
        // shifting both notes together so the interval itself stays intact.
        if ($slug !== null) {
            foreach (range($base, self::BASE_OCTAVE) as $candidate) {
                if (self::sampleExists(self::path($slug, $degree1, $candidate))) {
                    $octave1 = $candidate;
                    break;
                }
            }
        }
        $octave2 = $octave1 + (self::degreeIndex($degree2) < self::degreeIndex($degree1) ? 1 : 0);

        // A wrap-up pair can land on an octave a given instrument never
        // recorded (e.g. Tropar Choir has no la-c4.wav, so "Ti - La'" fails
        // at the usual octave) — shift both notes down one octave together
        // (preserving the interval) when that lower pair does exist, rather
        // than excluding the instrument from the whole quiz over one gap.
        if ($slug !== null
            && (! self::sampleExists(self::path($slug, $degree1, $octave1)) || ! self::sampleExists(self::path($slug, $degree2, $octave2)))
            && self::sampleExists(self::path($slug, $degree1, $octave1 - 1))
            && self::sampleExists(self::path($slug, $degree2, $octave2 - 1))
        ) {
            $octave1--;
            $octave2--;
        }

        // The Melodic/Harmonic interval quizzes, same explicit request as
        // Find the Note/RTP1 — bump both notes up one octave together (never
        // just one, or the interval itself would shrink/grow) only when real
        // files exist for both at the higher octave.
        if ($slug !== null && in_array($slug, self::SINGLE_DEGREE_OCTAVE_UP_SLUGS, true)
            && self::sampleExists(self::path($slug, $degree1, $octave1 + 1))
            && self::sampleExists(self::path($slug, $degree2, $octave2 + 1))
        ) {
            $octave1++;
            $octave2++;
        }

        return [
            ['degree' => $degree1, 'octave' => $octave1, 'offset' => 0.0],
            ['degree' => $degree2, 'octave' => $octave2, 'offset' => $timing === 'melodic' ? self::MELODIC_NOTE_SPACING_SECONDS : 0.0],
        ];
    }

    /**
     * Resolve an explicit octave without the register guessing used by fromAnswer().
     *
     * @return list<array{degree: string, octave: int, offset: float}>|null
     */
    public static function fromScientificPitchAnswer(string $answer, ?string $slug = null): ?array
    {
        if (! preg_match('/^(.+?)(\d+)$/', $answer, $matches) || ! isset(self::CHROMATIC_SLUGS[$matches[1]])) {
            return null;
        }

        $degree = $matches[1];
        $octave = (int) $matches[2];
        $range = self::SCIENTIFIC_PITCH_RANGES[$slug] ?? null;
        $pitch = ($octave + 1) * 12 + array_search($degree, array_keys(self::CHROMATIC_SLUGS), true);
        if ($range !== null && ($pitch < $range[0] || $pitch > $range[1])) {
            return null;
        }

        if ($slug !== null && (self::usesMidiBank($slug) || in_array($slug, ['celesta', 'vibraphone'], true))) {
            $note = self::fromRecordedMidiPitch($pitch, $slug);

            return $note === null ? null : [$note];
        }

        // Legacy sharp files sound one octave above their filename's register
        // (the same storage offset resolve() handles for dual-tonic melodies).
        // This is a deterministic filename conversion, never a pitch fallback.
        if ($slug !== null && str_contains($degree, '#') && ! in_array($slug, self::FULL_CHROMATIC_BANK_SLUGS, true)) {
            $octave--;
        }
        if ($slug !== null && ! self::sampleExists(self::path($slug, $degree, $octave))) {
            $degree = self::equivalentDegree($degree);
            if ($degree === null || ! self::sampleExists(self::path($slug, $degree, $octave))) {
                return null;
            }
        }

        return [['degree' => $degree, 'octave' => $octave, 'offset' => 0.0]];
    }

    /**
     * An actual MIDI pitch mapped to a recorded file, without quiz register
     * guessing or Find the Pitch's presentation-only instrument limits.
     *
     * @return array{degree: string, octave: int, offset: float}|null
     */
    public static function fromRecordedMidiPitch(int $midi, string $slug): ?array
    {
        if ($midi < 0 || $midi > 127) {
            return null;
        }
        $label = array_keys(self::CHROMATIC_SLUGS)[$midi % 12];
        $degree = self::equivalentDegree($label) ?? $label;
        if (self::usesMidiBank($slug)) {
            $octave = intdiv($midi, 12) - 1;

            return self::sampleExists(self::path($slug, $degree, $octave)) ? ['degree' => $degree, 'octave' => $octave, 'offset' => 0.0] : null;
        }
        // Measured register offsets in these current recorded banks. This
        // mapping is isolated to Intervals; existing quiz registers are unchanged.
        $registerOffset = ['organ' => 1, 'harpejji' => 1, 'upright-bass' => 2][$slug] ?? 0;
        $octave = intdiv($midi, 12) - 1 + $registerOffset;
        if (str_contains($degree, '#') && ! in_array($slug, self::FULL_CHROMATIC_BANK_SLUGS, true)) {
            // These two banks have octave-3 bare sharps, while every suffixed
            // sharp sounds one octave above its suffix (verified from the audio).
            if (in_array($slug, ['celesta', 'vibraphone'], true)) {
                if ($octave === 4) {
                    return null;
                }
                if ($octave === 3 && self::sampleExists(self::path($slug, $degree, 3))) {
                    return ['degree' => $degree, 'octave' => 3, 'offset' => 0.0];
                }
            }
            $octave--;
        }
        if (! self::sampleExists(self::path($slug, $degree, $octave))) {
            return null;
        }

        return ['degree' => $degree, 'octave' => $octave, 'offset' => 0.0];
    }

    private static function degreeIndex(string $degree): int
    {
        return array_search($degree, self::DEGREE_ORDER, true);
    }

    /**
     * The fixed diatonic semitone distance a solfège-pair answer spans (e.g.
     * "Do - Re" => 2, "Mi - Fa" => 1, "So - Do'" => 5) — independent of any
     * particular key/register, same math fromAnswer()'s two-degree branch
     * already uses to decide whether the pair wraps into the next octave.
     * Lets IntervalSamples::rootsForSemitones() place these same solfège-pair
     * quizzes (Melodic/Harmonic Second-Seventh) in any of the 12 keys a given
     * instrument's range allows, the same way it already does for named
     * intervals (Major 2nd, Perfect 5th, …).
     */
    public static function semitonesForDegreePair(string $answer): ?int
    {
        $parts = array_map(fn ($part) => rtrim(trim($part), "'"), explode(' - ', $answer));
        if (count($parts) !== 2 || ! isset(self::DEGREE_SLUGS[$parts[0]]) || ! isset(self::DEGREE_SLUGS[$parts[1]])) {
            return null;
        }
        [$degree1, $degree2] = $parts;
        $wraps = self::degreeIndex($degree2) < self::degreeIndex($degree1);

        return self::DEGREE_SEMITONES[self::degreeIndex($degree2)] + ($wraps ? 12 : 0) - self::DEGREE_SEMITONES[self::degreeIndex($degree1)];
    }

    // Whether a sample file exists for $path (storage/app/public/-relative,
    // same shape path()/resolve() use). Checking every instrument's sample
    // existence with a live Storage::exists() call for every question on
    // every quiz in a category listing was measured taking ~10s on a single
    // page load (29 instruments × ~17 quizzes, each a filesystem stat) —
    // this instead lists each instrument's notes/ folder once (cached) and
    // checks the filename in memory, since that folder only changes when a
    // sample bank is rebuilt, not on every request.
    public static function sampleExists(string $path): bool
    {
        if (! preg_match('#^notes/([^/]+)/([^/]+)$#', $path, $m)) {
            return Storage::disk('public')->exists($path);
        }

        [, $slug, $filename] = $m;

        return array_key_exists($filename, self::instrumentFiles($slug));
    }

    // The disk URL for a note sample, with a `?v={mtime}` cache-buster — a
    // sample bank gets rebuilt in place at the same filename whenever a
    // range gets corrected or a recording gets re-processed (this project
    // is mid-content-build, so that's routine, not rare), and without this
    // every browser that already loaded the old audio keeps playing it from
    // cache indefinitely, even though the quiz prompt and the tappable
    // keyboard both resolve to the exact same underlying file — confirmed
    // by direct comparison, it's a caching issue, not a resolution bug.
    //
    // Pre-rendered interval files (intervals/{melodic|harmonic}-{ordinal}/
    // {slug}/{pair}.wav), melodic-dictation/{quiz-slug}/{slug}/{file}.wav,
    // and every other pre-rendered-combination shape this project has added
    // since) get rebuilt in place the exact same way — same staleness risk,
    // just a differently-shaped path, so any of them is handled here too,
    // via a direct stat instead of the cached per-instrument directory
    // listing notes/ uses (these paths come from specific known
    // quiz_question_audio rows one at a time, not scanned/checked in bulk
    // per quiz like notes/ is, so there's no hot loop here to justify the
    // same caching). Keeping this a blanket "anything outside notes/" rule,
    // rather than one regex per content folder, so a future new folder
    // shape doesn't silently fall through uncached the way intervals/ and
    // melodic-dictation/ both already did before this existed.
    public static function url(string $path): string
    {
        $url = Storage::disk('public')->url($path);
        $disk = Storage::disk('public');

        if (preg_match('#^notes/([^/]+)/([^/]+)$#', $path, $m)) {
            [, $slug, $filename] = $m;
            $mtime = self::instrumentFiles($slug)[$filename] ?? null;

            return $mtime ? "{$url}?v={$mtime}" : $url;
        }

        if ($disk->exists($path)) {
            return "{$url}?v=".$disk->lastModified($path);
        }

        return $url;
    }

    // Cached {filename => mtime} for one instrument's notes/ folder —
    // shared by sampleExists() and url() so a sample bank rebuild only
    // costs one fresh directory listing (next cache miss), not a stat()
    // call per note per quiz per request.
    //
    // Cache::remember() still round-trips to the cache STORE (here, the
    // `cache` DB table — see the `khordquiz-cache-note-sample-files:*`
    // query spam that caused a 30s timeout on Chord Tone Recognition #2's
    // admin page) on every call, hit or miss; it only skips the expensive
    // closure. The newer chord-category Samples classes (7-note chords,
    // up to 7 octave-shift attempts per instrument per question) call this
    // far more times per request than the simpler content types this was
    // originally measured against, so a per-request in-memory memo on top
    // — not just the cross-request store — is needed to keep a page with
    // hundreds/thousands of questions off the cache table entirely after
    // its first note-check per instrument.
    private static array $instrumentFilesMemo = [];

    private static function usesMidiBank(string $slug): bool
    {
        return in_array($slug, ['celesta', 'vibraphone', 'saxophone', 'voice'], true)
            && Storage::disk('public')->exists("notes/{$slug}/gm-bank.json");
    }

    private static function instrumentFiles(string $slug): array
    {
        return self::$instrumentFilesMemo[$slug] ??= Cache::remember("note-sample-files:{$slug}", 3600, function () use ($slug) {
            $disk = Storage::disk('public');

            return collect($disk->files("notes/{$slug}"))
                ->mapWithKeys(fn ($file) => [basename($file) => $disk->lastModified($file)])
                ->all();
        });
    }
}
