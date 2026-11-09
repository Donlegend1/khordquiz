<?php

// Pure pool-definition helpers for "Dyad Melodies" — reuses the same 5
// melodic-shape templates and 7 starting degrees as 5-Note Melodies for the
// TOP-note line (the member's answer, same as Chordal melodies: the melody
// is the top note of each simultaneous dyad). Each top note is harmonized by
// a diatonic 3rd or 4th BELOW it, alternating per note position (shifted per
// pattern for variety across the 35 patterns), so a single question mixes
// both interval types rather than using only one throughout.

require_once __DIR__.'/five_note_melodies_pool.php';

// Frozen forever, independent of FIVE_NOTE_TEMPLATES' current membership —
// the original 5 templates this pool (and every instrument's already-built
// Dyad Melodies audio) was created against. Each original template's 3rd/4th
// alternation below is keyed off its position in THIS list, not its position
// in FIVE_NOTE_TEMPLATES, so adding a template there later (e.g. for 5-Note
// Melodies) can never silently reshuffle the harmony NoteSample::resolve()
// computes for already-built Dyad Melodies audio. New templates appended to
// FIVE_NOTE_TEMPLATES after this pool existed get their own stable index
// below instead (never reusing 0-4), so this function stays deterministic
// however many templates FIVE_NOTE_TEMPLATES grows to.
const DYAD_MELODY_ORIGINAL_TEMPLATE_ORDER = [
    'arch', 'descending-arch', 'rising-skips-falling-steps', 'rising-steps-falling-skips', 'zigzag',
];

// Returns the 35 patterns, each as:
//   ['label', 'fileSlug', 'startDegree', 'dyads' => [ ['harmonyIdx' => int, 'topIdx' => int], ... ] ]
// Indices are diatonic scale-degree indices (stepwiseMidiForIndex/stepwiseLabelForIndex).
function dyadMelodyPatterns(): array
{
    $templateOrder = DYAD_MELODY_ORIGINAL_TEMPLATE_ORDER;
    $originalCount = count($templateOrder);
    foreach (array_keys(FIVE_NOTE_TEMPLATES) as $template) {
        if (! in_array($template, $templateOrder, true)) {
            $templateOrder[] = $template;
        }
    }

    $patterns = [];
    foreach (range(0, 6) as $startDegree) {
        foreach (FIVE_NOTE_TEMPLATES as $template => $moves) {
            $templateIndex = array_search($template, $templateOrder, true);
            // Original 5 templates keep their exact historical patternIndex
            // (startDegree * 5 + their fixed 0-4 slot) no matter how many
            // templates exist today; anything appended after them gets its
            // own stable, non-colliding index instead.
            $patternIndex = $templateIndex < $originalCount
                ? $startDegree * $originalCount + $templateIndex
                : $startDegree * (count($templateOrder) - $originalCount) + ($templateIndex - $originalCount);

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
