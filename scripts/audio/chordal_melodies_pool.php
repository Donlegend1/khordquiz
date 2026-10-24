<?php

// Pure pool-definition helpers for "Chordal melodies" — each question plays a
// short progression of BLOCK CHORDS (triads or seventh chords, in root
// position or an inversion), and the melody the member taps back is the TOP
// note of each chord in order (the "soprano line").
//
// IMPORTANT voicing model: a chord's "inversion" fixes ONLY which chord tone
// is in the BASS. The melody/top note is a SEPARATE, independent choice among
// the chord's own tones (it can even share the bass's pitch class, doubled an
// octave up) — it is NOT mechanically determined by rotating the chord
// upward from the bass. For each step we search every (bass tone x melody
// tone x octave) combination and pick whichever keeps the melody's leap from
// the previous chord's melody note smallest (ties broken by the smallest bass
// leap), while staying within [CHORDAL_RANGE_MIN, CHORDAL_RANGE_MAX].
//
// Chord vocabulary:
//   - triads: maj [0,4,7], min [0,3,7], dim [0,3,6]
//   - sevenths: maj7 [0,4,7,11], min7 [0,3,7,10], dom7 [0,4,7,10],
//     m7b5 (half-diminished) [0,3,6,10], dim7 (fully diminished) [0,3,6,9]
//   - sixths: maj6 [0,4,7,9], min6 [0,3,7,9]
//
// Most chords in a progression are diatonic; 1-2 (occasionally more, for
// denser jazz-flavored progressions) are deliberately non-diatonic, via:
//   - secondary dominant (triad or dom7, a 5th above its target)
//   - leading-tone diminished / half-diminished / fully-diminished (a
//     half-step below its target, resolving up a half-step)
//   - chromatic neighbor (the same half-step-below chord used as a passing
//     decoration around a chord about to repeat/extend rather than a fixed
//     resolution)
//   - borrowed / modal mixture (iv, iv6, bIII, bVI, bVII from parallel C minor)
//   - secondary ii-V (a secondary m7b5 followed by its own secondary dom7,
//     resolving together into a diatonic minor target)
//   - secondary dominant chains (back-to-back secondary dominants moving by
//     descending 5ths, e.g. E7 -> Am -> D7 -> G -> C)

// Curwen chromatic solfège (Di/Ri/Fi/Si/Li) — matches the member-facing
// keyboard's black-key labels (KEYBOARD_BLACK_KEYS in quizAnswerPresets.js)
// exactly, unlike "Te" which the keyboard has no way to produce.
const CHORDAL_SOLFEGE = ['Do', 'Di', 'Re', 'Ri', 'Mi', 'Fa', 'Fi', 'So', 'Si', 'La', 'Li', 'Ti'];

const CHORDAL_QUALITY_INTERVALS = [
    'maj' => [0, 4, 7], 'min' => [0, 3, 7], 'dim' => [0, 3, 6],
    'maj7' => [0, 4, 7, 11], 'min7' => [0, 3, 7, 10], 'dom7' => [0, 4, 7, 10],
    'm7b5' => [0, 3, 6, 10], 'dim7' => [0, 3, 6, 9],
    'maj6' => [0, 4, 7, 9], 'min6' => [0, 3, 7, 9],
    'maj9' => [0, 4, 7, 11, 14],   // major 7 add 9 (5-note: root,3,5,7,9)
    'majAdd9' => [0, 4, 7, 14],    // major add 9, no 7th (4-note: root,3,5,9)
    'minMaj7' => [0, 3, 7, 11],    // minor-major 7 (minor triad + major 7th)
    'dimMaj7' => [0, 3, 6, 11],    // diminished-major 7 (diminished triad + major 7th)
];

const CHORDAL_DIATONIC_PITCH_CLASSES = [0, 2, 4, 5, 7, 9, 11]; // C D E F G A B

const CHORDAL_RANGE_MIN = 31; // G1
const CHORDAL_RANGE_MAX = 72; // C5
const CHORDAL_HOME_BASE = 48; // C3 — reference octave for solfège labeling and the default register

function chordalIsNonDiatonic(int $root, string $quality): bool
{
    foreach (CHORDAL_QUALITY_INTERVALS[$quality] as $interval) {
        $pc = ($root + $interval) % 12;
        if ($pc < 0) $pc += 12;
        if (!in_array($pc, CHORDAL_DIATONIC_PITCH_CLASSES, true)) {
            return true;
        }
    }

    return false;
}

function chordalLabelForTop(int $midi): string
{
    $octaveShift = intdiv($midi - CHORDAL_HOME_BASE, 12);
    $pc = ($midi - CHORDAL_HOME_BASE) % 12;
    if ($pc < 0) {
        $pc += 12;
        $octaveShift--;
    }

    $name = CHORDAL_SOLFEGE[$pc];
    if ($octaveShift > 0) return $name.str_repeat("'", $octaveShift);
    if ($octaveShift < 0) return $name.str_repeat(',', abs($octaveShift));

    return $name;
}

function chordalSlugForTop(int $midi): string
{
    return strtolower(chordalLabelForTop($midi));
}

// These quizzes test solfège (scale-degree) recognition only, never which
// octave a note is in — the member-facing tap keyboard has no way to express
// octave, so a stored answer/label containing chordalLabelForTop()'s "'"/","
// octave-shift suffixes can never be matched by anything the member taps,
// making the question unwinnable. File slugs (chordalSlugForTop, used for
// audio filenames) deliberately keep the octave markers for uniqueness —
// only answer/label text meant for grading or display goes through this.
function chordalStripOctaves(string $label): string
{
    return str_replace(["'", ','], '', $label);
}

// Builds one chord's voicing (ascending, absolute MIDI) for a given bass
// tone index and melody (top) tone index, relative-to-bass-zero before the
// caller's $root/octave-shift search applies.
function chordalRawVoicing(string $quality, int $bassIdx, int $melodyIdx): array
{
    $intervals = CHORDAL_QUALITY_INTERVALS[$quality];
    $n = count($intervals);
    $bassPc = $intervals[$bassIdx];

    // Collect the middle (non-melody, non-bass-pitch-class) tones as plain
    // 0-11 pitch classes and sort them ascending FIRST, then stack — closed
    // position among themselves. Processing them in chord-tone order instead
    // (the original bug) could force a tone needing +12 just because an
    // earlier-processed tone happened to land above it, producing voicings
    // spread over 2+ octaves for no musical reason.
    $middle = [];
    foreach (range(0, $n - 1) as $idx) {
        if ($idx === $melodyIdx) continue;
        $rel = ($intervals[$idx] - $bassPc) % 12;
        if ($rel < 0) $rel += 12;
        if ($rel === 0) continue; // same pitch class as bass — skip from the middle
        $middle[] = $rel;
    }
    sort($middle);

    $voicing = [0, ...$middle]; // bass, relative to itself, then closed-position middle tones

    $melodyRel = ($intervals[$melodyIdx] - $bassPc) % 12;
    if ($melodyRel < 0) $melodyRel += 12;
    if ($melodyRel === 0) $melodyRel = 12; // melody doubles the bass — place an octave above
    while ($melodyRel <= $voicing[count($voicing) - 1]) $melodyRel += 12;
    $voicing[] = $melodyRel;

    // Every value above is relative to the BASS tone (bass = 0). Callers add
    // the chord's ROOT to convert to absolute pitch, so re-reference onto the
    // root here by adding back $bassPc — otherwise any non-root-position bass
    // (1st/2nd/3rd inversion) silently transposes the whole chord down by
    // $bassPc semitones.
    return array_map(fn ($v) => $v + $bassPc, $voicing);
}

// Searches every (bass tone x melody tone x octave) combination for one
// chord step and returns the voicing that minimizes the melody-top leap from
// $prevTop (ties broken by smallest bass leap from $prevBass), within range.
function chordalBestVoicingForStep(int $root, string $quality, ?int $prevTop, ?int $prevBass, bool $sameChordAsPrev, ?int $forceBassIdx = null, ?int $forceMelodyIdx = null, ?int $forceShift = null): array
{
    $n = count(CHORDAL_QUALITY_INTERVALS[$quality]);
    $best = null;
    $bestTopDist = null;
    $bestBassDist = null;

    $bassRange = $forceBassIdx !== null ? [$forceBassIdx] : range(0, $n - 1);
    $melodyRange = $forceMelodyIdx !== null ? [$forceMelodyIdx] : range(0, $n - 1);
    $shiftRange = $forceShift !== null ? [$forceShift] : [-24, -12, 0, 12, 24];

    foreach ($bassRange as $bassIdx) {
        foreach ($melodyRange as $melodyIdx) {
            $raw = chordalRawVoicing($quality, $bassIdx, $melodyIdx);
            foreach ($shiftRange as $shift) {
                $absolute = array_map(fn ($r) => $root + $r + $shift, $raw);
                if (min($absolute) < CHORDAL_RANGE_MIN || max($absolute) > CHORDAL_RANGE_MAX) {
                    continue;
                }
                $top = $absolute[count($absolute) - 1];
                $bass = $absolute[0];
                $topDist = $prevTop === null ? abs($shift) : abs($top - $prevTop);
                // A literal repeated top note only reads as melodic when it's
                // the SAME chord re-voiced (the "reinversion" trick); across a
                // genuine chord change, nudge the search away from landing on
                // the exact same pitch every time, or the melody goes static.
                if ($topDist === 0 && $prevTop !== null && !$sameChordAsPrev) {
                    $topDist += 1.5;
                }
                $bassDist = $prevBass === null ? 0 : abs($bass - $prevBass);
                if ($bestTopDist === null || $topDist < $bestTopDist
                    || ($topDist === $bestTopDist && $bassDist < $bestBassDist)) {
                    $bestTopDist = $topDist;
                    $bestBassDist = $bassDist;
                    $best = $absolute;
                }
            }
        }
    }

    return $best;
}

// $steps: array of ['root' => offset from C, 'quality' => key].
function chordalBuildVoicings(array $steps): array
{
    $voicings = [];
    $prevTop = null;
    $prevBass = null;
    $prevRoot = null;
    $prevQuality = null;

    foreach ($steps as $step) {
        $sameChordAsPrev = $prevRoot === $step['root'] % 12 && $prevQuality === $step['quality'];
        $voicing = chordalBestVoicingForStep(
            $step['root'] + CHORDAL_HOME_BASE,
            $step['quality'],
            $prevTop,
            $prevBass,
            $sameChordAsPrev,
            $step['bassIdx'] ?? null,
            $step['melodyIdx'] ?? null,
            $step['shift'] ?? null
        );
        $voicings[] = $voicing;
        $prevTop = $voicing[count($voicing) - 1];
        $prevBass = $voicing[0];
        $prevRoot = $step['root'] % 12;
        $prevQuality = $step['quality'];
    }

    return $voicings;
}

function chordalTransposeVoicings(array $voicings, int $semitones): array
{
    return array_map(fn ($v) => array_map(fn ($n) => $n + $semitones, $v), $voicings);
}

// Drop 2: take a close-position voicing's 2nd-from-the-top note and move it
// down an octave, opening up the chord. The melody (top note) is untouched —
// only the bass and inner voicing change, which is exactly why this works
// for Chordal Bass Lines: same melody as Chordal melodies, different (and
// usually lower) bass line.
function chordalDrop2(array $voicing): array
{
    $n = count($voicing);
    if ($n < 2) return $voicing;
    $dropped = $voicing[$n - 2] - 12;
    $rest = $voicing;
    unset($rest[$n - 2]);
    $rest[] = $dropped;
    sort($rest);

    return array_values($rest);
}

function chordalDrop2Voicings(array $voicings): array
{
    return array_map('chordalDrop2', $voicings);
}

// ---------------------------------------------------------------------------
// Progression skeletons (root + quality only — bass/melody/octave are all
// auto-searched for smoothness). CHORDAL_PROGRESSIONS holds the original 35
// (rebuilt under the corrected model); CHORDAL_PROGRESSIONS_EXTRA holds the
// ~100 generated from the expanded technique palette.
// ---------------------------------------------------------------------------

function chordalStep(int $root, string $quality): array
{
    return ['root' => $root, 'quality' => $quality];
}

const CHORDAL_PROGRESSIONS = [
    [['root' => 0, 'quality' => 'maj'], ['root' => 2, 'quality' => 'min'], ['root' => 2, 'quality' => 'maj'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 4, 'quality' => 'maj'], ['root' => 9, 'quality' => 'min'], ['root' => 5, 'quality' => 'maj'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 9, 'quality' => 'min'], ['root' => 9, 'quality' => 'maj'], ['root' => 2, 'quality' => 'min'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 5, 'quality' => 'maj'], ['root' => 4, 'quality' => 'maj'], ['root' => 9, 'quality' => 'min'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 11, 'quality' => 'maj'], ['root' => 4, 'quality' => 'min'], ['root' => 5, 'quality' => 'maj'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 1, 'quality' => 'dim'], ['root' => 2, 'quality' => 'min'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 5, 'quality' => 'maj'], ['root' => 6, 'quality' => 'dim'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 9, 'quality' => 'min'], ['root' => 4, 'quality' => 'min'], ['root' => 4, 'quality' => 'dim'], ['root' => 5, 'quality' => 'maj'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 5, 'quality' => 'maj'], ['root' => 8, 'quality' => 'dim'], ['root' => 9, 'quality' => 'min'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 9, 'quality' => 'min'], ['root' => 0, 'quality' => 'maj'], ['root' => 8, 'quality' => 'maj'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 5, 'quality' => 'maj'], ['root' => 5, 'quality' => 'min'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 3, 'quality' => 'maj'], ['root' => 5, 'quality' => 'maj'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 9, 'quality' => 'min'], ['root' => 2, 'quality' => 'min'], ['root' => 5, 'quality' => 'maj'], ['root' => 10, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 4, 'quality' => 'maj'], ['root' => 9, 'quality' => 'min'], ['root' => 6, 'quality' => 'dim'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 1, 'quality' => 'dim'], ['root' => 2, 'quality' => 'min'], ['root' => 2, 'quality' => 'maj'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 9, 'quality' => 'min'], ['root' => 9, 'quality' => 'maj'], ['root' => 2, 'quality' => 'min'], ['root' => 10, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 8, 'quality' => 'maj'], ['root' => 4, 'quality' => 'maj'], ['root' => 9, 'quality' => 'min'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 4, 'quality' => 'dim'], ['root' => 4, 'quality' => 'dim'], ['root' => 2, 'quality' => 'dim'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 9, 'quality' => 'min'], ['root' => 8, 'quality' => 'dim'], ['root' => 9, 'quality' => 'min7'], ['root' => 6, 'quality' => 'm7b5'], ['root' => 6, 'quality' => 'm7b5']],
    [['root' => 0, 'quality' => 'maj7'], ['root' => 4, 'quality' => 'dom7'], ['root' => 9, 'quality' => 'min7'], ['root' => 2, 'quality' => 'min7'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 2, 'quality' => 'min7'], ['root' => 9, 'quality' => 'min7'], ['root' => 6, 'quality' => 'dim7'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj7']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 1, 'quality' => 'dim7'], ['root' => 2, 'quality' => 'min7'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj7']],
    [['root' => 4, 'quality' => 'min7'], ['root' => 4, 'quality' => 'dom7'], ['root' => 9, 'quality' => 'min'], ['root' => 5, 'quality' => 'maj7'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 9, 'quality' => 'min'], ['root' => 0, 'quality' => 'maj'], ['root' => 5, 'quality' => 'min7'], ['root' => 10, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj7']],
    [['root' => 0, 'quality' => 'maj7'], ['root' => 11, 'quality' => 'dom7'], ['root' => 4, 'quality' => 'min7'], ['root' => 9, 'quality' => 'min'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 2, 'quality' => 'min7'], ['root' => 3, 'quality' => 'dim'], ['root' => 4, 'quality' => 'min7'], ['root' => 9, 'quality' => 'min'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 9, 'quality' => 'min7'], ['root' => 0, 'quality' => 'maj'], ['root' => 8, 'quality' => 'maj7'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj7']],
    [['root' => 11, 'quality' => 'm7b5'], ['root' => 0, 'quality' => 'maj'], ['root' => 4, 'quality' => 'dom7'], ['root' => 9, 'quality' => 'min7'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 2, 'quality' => 'min'], ['root' => 7, 'quality' => 'maj'], ['root' => 6, 'quality' => 'dim'], ['root' => 7, 'quality' => 'maj'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 5, 'quality' => 'maj'], ['root' => 4, 'quality' => 'dim'], ['root' => 5, 'quality' => 'maj7'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 9, 'quality' => 'min'], ['root' => 8, 'quality' => 'dim'], ['root' => 9, 'quality' => 'min7'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 4, 'quality' => 'min'], ['root' => 3, 'quality' => 'dim'], ['root' => 4, 'quality' => 'min7'], ['root' => 9, 'quality' => 'min'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 0, 'quality' => 'maj7'], ['root' => 1, 'quality' => 'dim7'], ['root' => 2, 'quality' => 'min7'], ['root' => 6, 'quality' => 'dim'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj']],
    [['root' => 9, 'quality' => 'min7'], ['root' => 9, 'quality' => 'dom7'], ['root' => 2, 'quality' => 'min7'], ['root' => 10, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj7']],
    [['root' => 0, 'quality' => 'maj'], ['root' => 6, 'quality' => 'm7b5'], ['root' => 11, 'quality' => 'dom7'], ['root' => 4, 'quality' => 'min'], ['root' => 7, 'quality' => 'dom7'], ['root' => 0, 'quality' => 'maj']],
];

// --- generated technique palette for the extra ~100 progressions ---

const CHORDAL_DIATONIC_TARGETS = [
    'I' => ['root' => 0, 'quality' => 'maj', 'quality7' => 'maj7'],
    'ii' => ['root' => 2, 'quality' => 'min', 'quality7' => 'min7'],
    'iii' => ['root' => 4, 'quality' => 'min', 'quality7' => 'min7'],
    'IV' => ['root' => 5, 'quality' => 'maj', 'quality7' => 'maj7'],
    'V' => ['root' => 7, 'quality' => 'maj', 'quality7' => 'dom7'],
    'vi' => ['root' => 9, 'quality' => 'min', 'quality7' => 'min7'],
];

const CHORDAL_DIATONIC_PREDECESSOR = [
    'I' => ['root' => 7, 'quality' => 'maj'],   // V -> I
    'ii' => ['root' => 9, 'quality' => 'min'],  // vi -> ii
    'iii' => ['root' => 11, 'quality' => 'dim'], // vii -> iii
    'IV' => ['root' => 0, 'quality' => 'maj'],  // I -> IV
    'V' => ['root' => 2, 'quality' => 'min'],   // ii -> V
    'vi' => ['root' => 4, 'quality' => 'min'],  // iii -> vi
];

function chordalGenerateExtraProgressions(): array
{
    $progressions = [];
    $targets = array_keys(CHORDAL_DIATONIC_TARGETS);

    // TA: diatonic predecessor -> non-diatonic approach -> target -> V -> I,
    // across every target and every approach-chord type.
    $approachTypes = ['secDomTriad', 'secDom7', 'leadDim', 'leadDim7', 'leadM7b5'];
    foreach ($targets as $target) {
        if ($target === 'I') continue;
        $t = CHORDAL_DIATONIC_TARGETS[$target];
        $pred = CHORDAL_DIATONIC_PREDECESSOR[$target];
        foreach ($approachTypes as $type) {
            // V/IV collapses to I (both diatonic) as a plain triad — skip the
            // one combination that wouldn't actually introduce a chromatic note.
            if ($target === 'IV' && $type === 'secDomTriad') continue;
            $approach = match ($type) {
                'secDomTriad' => chordalStep(($t['root'] + 7) % 12, 'maj'),
                'secDom7' => chordalStep(($t['root'] + 7) % 12, 'dom7'),
                'leadDim' => chordalStep(($t['root'] + 11) % 12, 'dim'),
                'leadDim7' => chordalStep(($t['root'] + 11) % 12, 'dim7'),
                'leadM7b5' => chordalStep(($t['root'] + 11) % 12, 'm7b5'),
            };
            $targetStep = chordalStep($t['root'], $t['quality']);
            $home = [chordalStep(7, 'maj'), chordalStep(0, 'maj')];

            // 5-chord version
            $progressions[] = array_merge([$pred, $approach, $targetStep], $home);
            // 6-chord version with a tonic pickup at the start
            $progressions[] = array_merge([chordalStep(0, 'maj'), $pred, $approach, $targetStep], $home);
        }
    }

    // TB: secondary dominant -> target -> target-as-7th (same chord,
    // extended, keeps melody moving) -> diatonic approach to V -> V -> I.
    foreach ($targets as $target) {
        if ($target === 'I') continue;
        $t = CHORDAL_DIATONIC_TARGETS[$target];
        $secDom = chordalStep(($t['root'] + 7) % 12, 'dom7');
        $plain = chordalStep($t['root'], $t['quality']);
        $seventh = chordalStep($t['root'], $t['quality7']);
        $progressions[] = [chordalStep(0, 'maj'), $secDom, $plain, $seventh, chordalStep(7, 'dom7'), chordalStep(0, 'maj')];
    }

    // TD: secondary ii-V into a minor target (iii or vi).
    foreach (['iii', 'vi'] as $target) {
        $t = CHORDAL_DIATONIC_TARGETS[$target];
        $secII = chordalStep(($t['root'] + 2) % 12, 'm7b5');
        $secV = chordalStep(($t['root'] + 7) % 12, 'dom7');
        $progressions[] = [chordalStep(0, 'maj7'), $secII, $secV, chordalStep($t['root'], $t['quality7']), chordalStep(7, 'dom7'), chordalStep(0, 'maj')];
        $progressions[] = [$secII, $secV, chordalStep($t['root'], $t['quality']), chordalStep(7, 'dom7'), chordalStep(0, 'maj')];
    }

    // TE: chromatic-neighbor-then-extend around each target (Am -> Ab-dim ->
    // Am7 style), then resolve home via V.
    foreach ($targets as $target) {
        if ($target === 'I') continue;
        $t = CHORDAL_DIATONIC_TARGETS[$target];
        $pred = CHORDAL_DIATONIC_PREDECESSOR[$target];
        $plain = chordalStep($t['root'], $t['quality']);
        $neighbor = chordalStep(($t['root'] + 11) % 12, 'dim');
        $seventh = chordalStep($t['root'], $t['quality7']);
        $progressions[] = [$pred, $plain, $neighbor, $seventh, chordalStep(7, 'dom7'), chordalStep(0, 'maj')];
    }

    // TF: borrowed / modal-mixture chords (iv, iv6, bIII, bVI, bVII) resolving
    // to V or I the way they conventionally do.
    $borrowed = [
        [chordalStep(9, 'min'), chordalStep(0, 'maj'), chordalStep(5, 'min'), chordalStep(7, 'maj'), chordalStep(0, 'maj')],
        [chordalStep(9, 'min'), chordalStep(0, 'maj'), chordalStep(5, 'min6'), chordalStep(7, 'dom7'), chordalStep(0, 'maj')],
        [chordalStep(0, 'maj'), chordalStep(3, 'maj'), chordalStep(5, 'maj'), chordalStep(7, 'maj'), chordalStep(0, 'maj')],
        [chordalStep(0, 'maj'), chordalStep(3, 'maj7'), chordalStep(5, 'min7'), chordalStep(7, 'dom7'), chordalStep(0, 'maj7')],
        [chordalStep(9, 'min'), chordalStep(0, 'maj'), chordalStep(8, 'maj'), chordalStep(7, 'maj'), chordalStep(0, 'maj')],
        [chordalStep(9, 'min7'), chordalStep(0, 'maj'), chordalStep(8, 'maj7'), chordalStep(7, 'dom7'), chordalStep(0, 'maj7')],
        [chordalStep(9, 'min'), chordalStep(2, 'min'), chordalStep(5, 'maj'), chordalStep(10, 'maj'), chordalStep(0, 'maj')],
        [chordalStep(9, 'min7'), chordalStep(2, 'min7'), chordalStep(5, 'maj7'), chordalStep(10, 'dom7'), chordalStep(0, 'maj7')],
        [chordalStep(2, 'min'), chordalStep(5, 'min6'), chordalStep(7, 'maj'), chordalStep(10, 'maj'), chordalStep(0, 'maj')],
        [chordalStep(0, 'maj'), chordalStep(5, 'maj'), chordalStep(5, 'min'), chordalStep(10, 'dom7'), chordalStep(0, 'maj7')],
    ];
    foreach ($borrowed as $p) {
        $progressions[] = $p;
    }

    // TG: descending secondary-dominant chains (circle of fifths), 2
    // non-diatonic chords back-to-back.
    $chains = [
        [chordalStep(0, 'maj'), chordalStep(4, 'dom7'), chordalStep(9, 'min'), chordalStep(2, 'dom7'), chordalStep(7, 'maj'), chordalStep(0, 'maj')],
        [chordalStep(0, 'maj7'), chordalStep(11, 'dom7'), chordalStep(4, 'min'), chordalStep(9, 'dom7'), chordalStep(2, 'min'), chordalStep(7, 'dom7')],
        [chordalStep(9, 'min'), chordalStep(1, 'dom7'), chordalStep(2, 'min'), chordalStep(9, 'dom7'), chordalStep(2, 'min'), chordalStep(0, 'maj')],
        [chordalStep(2, 'min7'), chordalStep(4, 'dom7'), chordalStep(9, 'min7'), chordalStep(2, 'dom7'), chordalStep(7, 'dom7'), chordalStep(0, 'maj')],
        [chordalStep(0, 'maj'), chordalStep(9, 'dom7'), chordalStep(2, 'min'), chordalStep(4, 'dom7'), chordalStep(9, 'min'), chordalStep(0, 'maj')],
        [chordalStep(4, 'min'), chordalStep(9, 'dom7'), chordalStep(2, 'min'), chordalStep(7, 'dom7'), chordalStep(0, 'maj')],
        [chordalStep(7, 'dom7'), chordalStep(0, 'maj'), chordalStep(4, 'dom7'), chordalStep(9, 'min'), chordalStep(2, 'dom7'), chordalStep(7, 'maj')],
        [chordalStep(9, 'min'), chordalStep(2, 'dom7'), chordalStep(7, 'maj'), chordalStep(11, 'dom7'), chordalStep(4, 'min'), chordalStep(0, 'maj')],
        [chordalStep(0, 'maj'), chordalStep(6, 'dim7'), chordalStep(7, 'dom7'), chordalStep(1, 'dim7'), chordalStep(2, 'min'), chordalStep(0, 'maj')],
        [chordalStep(9, 'min'), chordalStep(6, 'm7b5'), chordalStep(11, 'dom7'), chordalStep(4, 'min'), chordalStep(1, 'dim7'), chordalStep(2, 'min')],
    ];
    foreach ($chains as $p) {
        $progressions[] = $p;
    }

    return $progressions;
}

const CHORDAL_PROGRESSIONS_EXTRA_CACHE_KEY = null; // (no caching needed — generation is cheap)

// Literal user-composed progressions — each step carries an explicit
// bassIdx/melodyIdx (not auto-searched) so these reproduce exactly what was
// specified, rather than whatever the smoothing search would have picked.
function chordalLit(int $root, string $quality, int $bassIdx, int $melodyIdx, ?int $shift = null): array
{
    $step = ['root' => $root, 'quality' => $quality, 'bassIdx' => $bassIdx, 'melodyIdx' => $melodyIdx];
    if ($shift !== null) $step['shift'] = $shift;

    return $step;
}

function chordalLiteralProgressions(): array
{
    return [
        // Example 1: re mi fa mi re do
        [chordalLit(2, 'min', 1, 0), chordalLit(0, 'maj', 2, 1), chordalLit(2, 'min', 2, 1), chordalLit(6, 'm7b5', 0, 3), chordalLit(5, 'dim7', 0, 3), chordalLit(9, 'min7', 2, 1)],
        // Example 2: re mi fa mi re do (Bb maj -> C7 -> Dm7b5 -> C7 -> Dm7b5 -> C maj)
        [chordalLit(10, 'maj', 2, 1), chordalLit(0, 'dom7', 2, 1), chordalLit(2, 'm7b5', 2, 1), chordalLit(0, 'dom7', 2, 1), chordalLit(2, 'm7b5', 1, 0), chordalLit(0, 'maj', 1, 0)],
        // Example 3: do re mi fa so — first chord pinned an octave lower
        // (shift -12) so the melody starts on Do' and walks up, instead of
        // the auto-search's default landing on Do'' and leaping down to Re'.
        [chordalLit(9, 'min7', 2, 1, -12), chordalLit(5, 'dim7', 0, 3), chordalLit(9, 'min7', 3, 2), chordalLit(8, 'dim7', 0, 3), chordalLit(9, 'min7', 0, 3)],
        // Example 4: do so te si so
        [chordalLit(0, 'maj', 1, 0), chordalLit(0, 'dom7', 3, 2), chordalLit(4, 'dim', 0, 2), chordalLit(2, 'm7b5', 3, 2), chordalLit(0, 'maj', 0, 2)],
        // Example 5: la ti do mi do ti — pinned to a proper arch (La Ti Do'
        // Mi' walking up, then Do' Ti walking back down); the un-pinned
        // auto-search had put La' Ti' Do'' too high, then leapt down to Mi'
        // mid-ascent instead of continuing upward.
        [chordalLit(9, 'min', 1, 0, -12), chordalLit(2, 'dim7', 0, 3, 0), chordalLit(9, 'min7', 2, 1, -12), chordalLit(6, 'm7b5', 0, 3, 0), chordalLit(6, 'm7b5', 3, 2, -12), chordalLit(7, 'dom7', 2, 1, -12)],
        // Example 8: so fa mi mo re do (mo = Eb/D#)
        [chordalLit(4, 'min', 2, 1), chordalLit(2, 'min', 2, 1), chordalLit(4, 'min', 1, 0), chordalLit(3, 'min', 1, 0), chordalLit(2, 'min', 1, 0), chordalLit(0, 'maj', 1, 0)],
        // Example 9: re di re mi fa so
        [chordalLit(2, 'min', 1, 0), chordalLit(9, 'dom7', 2, 1), chordalLit(2, 'min', 1, 0), chordalLit(4, 'dim', 1, 0), chordalLit(2, 'm7b5', 2, 1), chordalLit(0, 'maj7', 3, 2)],
        // Batch 2, Example 1: so fa mo re do
        [chordalLit(9, 'm7b5', 0, 3), chordalLit(2, 'm7b5', 2, 1), chordalLit(9, 'm7b5', 3, 2), chordalLit(2, 'm7b5', 1, 0), chordalLit(0, 'maj', 1, 0)],
        // Batch 2, Example 2: di re mi fa fa mi
        [chordalLit(9, 'maj', 2, 1), chordalLit(2, 'min', 1, 0), chordalLit(1, 'm7b5', 2, 1), chordalLit(2, 'min7', 2, 1), chordalLit(2, 'm7b5', 2, 1), chordalLit(0, 'maj', 2, 1)],
        // Batch 2, Example 3: do re mo re do do
        [chordalLit(0, 'maj', 1, 0), chordalLit(2, 'min', 1, 0), chordalLit(3, 'maj', 1, 0), chordalLit(10, 'maj', 2, 1), chordalLit(5, 'maj', 0, 2), chordalLit(0, 'maj', 1, 0)],
        // Batch 2, Example 5: so mi fi re fa mi — shifted up a full octave
        // (was sitting noticeably lower than every other pattern).
        [chordalLit(0, 'maj', 0, 2, 12), chordalLit(0, 'maj', 2, 1, 0), chordalLit(2, 'maj', 2, 1, 0), chordalLit(2, 'maj', 1, 0, 0), chordalLit(2, 'm7b5', 2, 1, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // Batch 2, Example 6: do re mo re do do
        [chordalLit(0, 'maj', 1, 0), chordalLit(10, 'maj', 2, 1), chordalLit(3, 'maj', 1, 0), chordalLit(10, 'maj', 2, 1), chordalLit(9, 'dim', 2, 1), chordalLit(0, 'maj', 1, 0)],
        // Batch 2, Example 7 (corrected): toh si so fa mi — last chord
        // changed from C major 7 to a plain C major triad, then shifted up
        // a full octave to match the register of the other patterns.
        [chordalLit(4, 'dim', 0, 2, 12), chordalLit(2, 'dim', 0, 2, 12), chordalLit(4, 'dim', 2, 1, 0), chordalLit(2, 'dim', 2, 1, 0), chordalLit(0, 'maj', 2, 1, 0)],
    ];
}

// Only the literal, user-composed progressions are active right now — the
// earlier hand-designed (CHORDAL_PROGRESSIONS) and generated
// (chordalGenerateExtraProgressions) batches are being redone example-by-
// example, so they're excluded here rather than deleted outright (both
// functions are kept below in case any of that work is wanted again later).
function chordalAllProgressions(): array
{
    return chordalLiteralProgressions();
}

// Returns every curated+generated pattern as:
//   ['label', 'fileSlug', 'steps', 'voicings' => [[notes...], ...], 'nonDiatonicCount' => int]
function chordalMelodyPatterns(): array
{
    $patterns = [];
    foreach (chordalAllProgressions() as $steps) {
        $voicings = chordalBuildVoicings($steps);
        $tops = array_map(fn ($v) => $v[count($v) - 1], $voicings);
        $nonDiatonicCount = count(array_filter($steps, fn ($s) => chordalIsNonDiatonic($s['root'], $s['quality'])));

        $patterns[] = [
            'label' => chordalStripOctaves(implode(' ', array_map('chordalLabelForTop', $tops))),
            'fileSlug' => implode('-', array_map('chordalSlugForTop', $tops)),
            'steps' => $steps,
            'voicings' => $voicings,
            'nonDiatonicCount' => $nonDiatonicCount,
        ];
    }

    return $patterns;
}
