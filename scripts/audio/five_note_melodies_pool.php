<?php

// Pure pool-definition helpers for "5-Note Melodies" — reuses the note-index math
// from three_note_stepwise_pool.php. Unlike Stepwise/Skips (exhaustive over every
// valid move combination), this is a curated set: 5 melodic shape templates (each
// a 4-move sequence mixing steps (+/-1) and skips (+/-2 a 3rd, +/-3 a 4th)),
// applied across all 7 starting degrees = 35 total patterns. User-approved shapes,
// not exhaustive — 5 notes x every move combination would be ~9,000 patterns,
// unworkable at the per-instrument/per-octave/per-17-instruments scale this
// pipeline already runs at.

require_once __DIR__.'/three_note_stepwise_pool.php';

// Each template is the 4 moves between the 5 notes.
const FIVE_NOTE_TEMPLATES = [
    'arch' => [2, 1, -1, -2],
    'descending-arch' => [-2, -1, 1, 2],
    'rising-skips-falling-steps' => [2, 2, -1, -1],
    'rising-steps-falling-skips' => [1, 1, -2, -2],
    'zigzag' => [2, -1, 2, -1],
];

// Same return shape as stepwisePatterns()/skipsPatterns(): ['label', 'fileSlug', 'startDegree', 'relativeIndices'].
function fiveNoteMelodyPatterns(): array
{
    $patterns = [];
    foreach (range(0, 6) as $startDegree) {
        foreach (FIVE_NOTE_TEMPLATES as $moves) {
            $relativeIndices = [0];
            $running = 0;
            foreach ($moves as $move) {
                $running += $move;
                $relativeIndices[] = $running;
            }

            $labelIndices = array_map(fn ($r) => $startDegree + $r, $relativeIndices);
            $patterns[] = [
                'label' => implode(' ', array_map('stepwiseLabelForIndex', $labelIndices)),
                'fileSlug' => implode('-', array_map('stepwiseSlugForIndex', $labelIndices)),
                'startDegree' => $startDegree,
                'relativeIndices' => $relativeIndices,
            ];
        }
    }

    return $patterns;
}
