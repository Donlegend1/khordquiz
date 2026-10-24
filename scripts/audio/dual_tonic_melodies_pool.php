<?php

// Pure pool-definition helpers for "Dual-tonic melodies" — reuses the same 5
// diatonic melodic-shape templates as 5-Note Melodies, but inserts a chromatic
// (non-diatonic) passing tone into every whole-step gap in the shape (Do-Re,
// Re-Mi, Fa-So, So-La, La-Ti). Mi-Fa and Ti-Do are already half-steps so no
// passing tone fits there. Each template has exactly 2 step-moves, so a
// pattern ends up with 0, 1, or 2 chromatic notes added depending on where its
// starting degree places those steps on the scale — "up to two" non-diatonic
// notes per melody, never a hard-coded count. Still in the key of C: every
// chromatic note is a passing tone between two diatonic scale notes, not a
// modulation, so the melody keeps resolving back into C major.

require_once __DIR__.'/five_note_melodies_pool.php';

function dualTonicDegreeOf(int $idx): int
{
    $degree = $idx % 7;
    if ($degree < 0) {
        $degree += 7;
    }

    return $degree;
}

// True when the (idx, idx+1) pair is a whole step in the C major scale —
// i.e. anywhere except Mi-Fa (degree 2) and Ti-Do (degree 6).
function dualTonicIsWholeStepPair(int $lowIdx): bool
{
    $degree = dualTonicDegreeOf($lowIdx);

    return $degree !== 2 && $degree !== 6;
}

// No octave marker — see stepwiseLabelForIndex()'s comment: these quizzes
// test solfège recognition only, and the member's tap keyboard has no way to
// express an octave, so the stored answer can't require one either.
// Curwen chromatic solfège (Di/Ri/Fi/Si/Li) — matches the member-facing
// keyboard's black-key labels exactly (KEYBOARD_BLACK_KEYS in
// quizAnswerPresets.js), unlike a "degree#" name like "So#" which the
// keyboard has no way to produce no matter which key is tapped. Only Do, Re,
// Fa, So, and La ever appear here (dualTonicIsWholeStepPair() already
// excludes Mi and Ti, which have no whole-step gap above them).
const DUAL_TONIC_CURWEN_SHARPS = ['Do' => 'Di', 'Re' => 'Ri', 'Fa' => 'Fi', 'So' => 'Si', 'La' => 'Li'];

function dualTonicChromaticLabelForIndex(int $lowIdx): string
{
    $degree = $lowIdx % 7;
    if ($degree < 0) {
        $degree += 7;
    }

    return DUAL_TONIC_CURWEN_SHARPS[STEPWISE_DEGREE_NAMES[$degree]];
}

function dualTonicChromaticSlugForIndex(int $lowIdx): string
{
    $octaveShift = intdiv($lowIdx, 7);
    $degree = $lowIdx % 7;
    if ($degree < 0) {
        $degree += 7;
        $octaveShift--;
    }

    $slug = STEPWISE_DEGREE_SLUGS[$degree].'s';
    if ($octaveShift > 0) return $slug.str_repeat('h', $octaveShift);
    if ($octaveShift < 0) return $slug.str_repeat('l', abs($octaveShift));

    return $slug;
}

// Returns the 35 patterns (same shape x starting-degree combinations as
// 5-Note Melodies), each as:
//   ['label', 'fileSlug', 'startDegree', 'notes' => [ ['type' => 'diatonic'|'chromatic', 'idx' => int], ... ]]
// 'idx' is a diatonic scale index for diatonic notes, or the LOWER of the two
// diatonic notes bracketing the passing tone for chromatic notes (pitch =
// stepwiseMidiForIndex($idx, $base) + 1).
//
// Every pattern gets exactly ONE chromatic passing tone, so the final note
// count is (diatonic skeleton length) + 1. To land on all of 4, 5, and 6
// notes (not just a uniform 6), the diatonic skeleton itself is truncated to
// the shape's first 2, 3, or 4 moves (3/4/5 diatonic notes), cycling through
// those three lengths across the 35 shape x degree combinations so the mix
// is even rather than clustering all of one length together.
function dualTonicMelodyPatterns(): array
{
    $patterns = [];
    $comboIndex = 0;
    foreach (range(0, 6) as $startDegree) {
        foreach (FIVE_NOTE_TEMPLATES as $moves) {
            $skeletonMoveCount = 2 + ($comboIndex % 3); // 2, 3, or 4 moves -> 3, 4, or 5 diatonic notes
            $comboIndex++;
            $truncatedMoves = array_slice($moves, 0, $skeletonMoveCount);

            $relativeIndices = [0];
            $running = 0;
            foreach ($truncatedMoves as $move) {
                $running += $move;
                $relativeIndices[] = $running;
            }

            $labelIndices = array_map(fn ($r) => $startDegree + $r, $relativeIndices);

            // Capped at ONE chromatic insertion per pattern — a 5-note
            // skeleton has 2 step-moves, and inserting at both would produce
            // a 7-note melody, past the quiz's 5-6 note cap. Only the first
            // eligible whole-step gap gets the passing tone.
            $notes = [['type' => 'diatonic', 'idx' => $labelIndices[0]]];
            $insertedChromatic = false;
            for ($k = 0; $k < count($labelIndices) - 1; $k++) {
                $a = $labelIndices[$k];
                $b = $labelIndices[$k + 1];
                if (!$insertedChromatic && abs($b - $a) === 1) {
                    $lowIdx = min($a, $b);
                    if (dualTonicIsWholeStepPair($lowIdx)) {
                        $notes[] = ['type' => 'chromatic', 'idx' => $lowIdx];
                        $insertedChromatic = true;
                    }
                }
                $notes[] = ['type' => 'diatonic', 'idx' => $b];
            }

            // Every pattern must carry at least one non-diatonic note. A few
            // shapes (arch/zigzag patterns whose only step-moves happen to
            // land on the scale's two existing half-steps, Mi-Fa and Ti-Do)
            // get zero chromatic insertions from the rule above — for those,
            // fall back to inserting a chromatic passing tone into the
            // pattern's first skip (a third) instead, anchored the same way
            // (half-step above the lower of the two bracketing notes).
            $hasChromatic = count(array_filter($notes, fn ($n) => $n['type'] === 'chromatic'))>0;
            if (!$hasChromatic) {
                for ($k = 0; $k < count($labelIndices) - 1; $k++) {
                    $a = $labelIndices[$k];
                    $b = $labelIndices[$k + 1];
                    // A chromatic note only exists above Do/Re/Fa/So/La (not
                    // Mi or Ti, which are already half-steps below their
                    // neighbor) — skip a skip whose lower note doesn't have
                    // a valid chromatic anchor, rather than inserting a
                    // nonexistent "Mi#"/"Ti#".
                    if (abs($b - $a) >= 2 && dualTonicIsWholeStepPair(min($a, $b))) {
                        $lowIdx = min($a, $b);
                        $insertAfter = null;
                        foreach ($notes as $pos => $n) {
                            if ($n['type'] === 'diatonic' && $n['idx'] === $a) {
                                $insertAfter = $pos;
                                break;
                            }
                        }
                        array_splice($notes, $insertAfter + 1, 0, [['type' => 'chromatic', 'idx' => $lowIdx]]);
                        break;
                    }
                }
            }

            $label = implode(' ', array_map(
                fn ($n) => $n['type'] === 'diatonic'
                    ? stepwiseLabelForIndex($n['idx'])
                    : dualTonicChromaticLabelForIndex($n['idx']),
                $notes
            ));
            $fileSlug = implode('-', array_map(
                fn ($n) => $n['type'] === 'diatonic'
                    ? stepwiseSlugForIndex($n['idx'])
                    : dualTonicChromaticSlugForIndex($n['idx']),
                $notes
            ));

            $patterns[] = [
                'label' => $label,
                'fileSlug' => $fileSlug,
                'startDegree' => $startDegree,
                'notes' => $notes,
            ];
        }
    }

    return $patterns;
}

function dualTonicMidiNotes(array $notes, int $base): array
{
    return array_map(
        fn ($n) => stepwiseMidiForIndex($n['idx'], $base) + ($n['type'] === 'chromatic' ? 1 : 0),
        $notes
    );
}
