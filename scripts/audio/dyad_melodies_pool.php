<?php

// Pure pool-definition helpers for "Dyad Melodies" — reuses the same 5
// melodic-shape templates and 7 starting degrees as 5-Note Melodies for the
// TOP-note line (the member's answer, same as Chordal melodies: the melody
// is the top note of each simultaneous dyad). Each top note is harmonized by
// a diatonic 3rd or 4th BELOW it, alternating per note position (shifted per
// pattern for variety across the 35 patterns), so a single question mixes
// both interval types rather than using only one throughout.

require_once __DIR__.'/five_note_melodies_pool.php';

// Returns the 35 patterns, each as:
//   ['label', 'fileSlug', 'startDegree', 'dyads' => [ ['harmonyIdx' => int, 'topIdx' => int], ... ] ]
// Indices are diatonic scale-degree indices (stepwiseMidiForIndex/stepwiseLabelForIndex).
function dyadMelodyPatterns(): array
{
    $patterns = [];
    $patternIndex = 0;
    foreach (range(0, 6) as $startDegree) {
        foreach (FIVE_NOTE_TEMPLATES as $moves) {
            $relativeIndices = [0];
            $running = 0;
            foreach ($moves as $move) {
                $running += $move;
                $relativeIndices[] = $running;
            }

            $labelIndices = array_map(fn ($r) => $startDegree + $r, $relativeIndices);

            $dyads = [];
            foreach ($labelIndices as $k => $topIdx) {
                $isFourth = ($k + $patternIndex) % 2 === 0;
                $harmonyIdx = $topIdx - ($isFourth ? 3 : 2);
                $dyads[] = ['harmonyIdx' => $harmonyIdx, 'topIdx' => $topIdx];
            }

            $label = implode(' ', array_map(fn ($i) => stepwiseLabelForIndex($i), $labelIndices));
            $fileSlug = implode('-', array_map(fn ($i) => stepwiseSlugForIndex($i), $labelIndices));

            $patterns[] = [
                'label' => $label,
                'fileSlug' => $fileSlug,
                'startDegree' => $startDegree,
                'dyads' => $dyads,
            ];
            $patternIndex++;
        }
    }

    return $patterns;
}

// Returns an array of 2-element [harmonyMidi, topMidi] pairs (low-to-high),
// ready to feed render_chord_sequence.sh as comma-separated simultaneous notes.
function dyadMelodyVoicings(array $dyads, int $base): array
{
    return array_map(
        fn ($d) => [stepwiseMidiForIndex($d['harmonyIdx'], $base), stepwiseMidiForIndex($d['topIdx'], $base)],
        $dyads
    );
}
