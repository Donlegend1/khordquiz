<?php

// Pure pool-definition helpers for "3-Note with Skips" — same shape as
// three_note_stepwise_pool.php (reuses its note-index math directly), but with
// a wider move set: each of the 2 moves in a triplet is +/-1 (step, a 2nd),
// +/-2 (skip, a 3rd), or +/-3 (skip, a 4th), and at least one of the 2 moves
// must be a skip — pure step+step pairs are excluded (that's Stepwise's job).
// 6 possible values per move x 6 = 36 pairs, minus the 4 pure-step pairs
// (+/-1,+/-1) = 32 valid direction-pairs per starting degree x 7 degrees = 224
// total patterns.

require_once __DIR__.'/three_note_stepwise_pool.php';

function skipsDirectionPairs(): array
{
    $moves = [-3, -2, -1, 1, 2, 3];
    $pairs = [];
    foreach ($moves as $d1) {
        foreach ($moves as $d2) {
            if (abs($d1) === 1 && abs($d2) === 1) continue; // pure step+step — Stepwise's territory
            $pairs[] = [$d1, $d2];
        }
    }

    return $pairs;
}

// Same return shape as stepwisePatterns(): ['label', 'fileSlug', 'startDegree', 'relativeIndices'].
function skipsPatterns(): array
{
    $patterns = [];
    foreach (range(0, 6) as $startDegree) {
        foreach (skipsDirectionPairs() as [$d1, $d2]) {
            $relativeIndices = [0, $d1, $d1 + $d2];
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
