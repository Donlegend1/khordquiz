<?php

// Pure pool-definition helpers for "3-Note Stepwise" — shared by every instrument's
// build script. The 28 patterns x 3 slots = 84 QuizQuestion rows are created ONCE
// (by build_three_note_stepwise_piano.php) and reused by every instrument after —
// never recreated, since that would cascade-delete every other instrument's
// already-attached audio. Each instrument just computes its OWN MIDI pitches for
// however many of the 3 slots fit its own octave range, and attaches to the
// matching existing row (found by `answer` text + creation order = slot index,
// verified: a pattern's 3 rows are always created in slot 0/1/2 order).

const STEPWISE_DEGREE_NAMES = ['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti'];
const STEPWISE_DEGREE_SLUGS = ['do', 're', 'mi', 'fa', 'so', 'la', 'ti'];
const STEPWISE_OFFSETS = [0, 2, 4, 5, 7, 9, 11];
const STEPWISE_DIRECTIONS = ['uu' => [1, 1], 'ud' => [1, -1], 'du' => [-1, 1], 'dd' => [-1, -1]];

function stepwiseMidiForIndex(int $idx, int $base): int
{
    $octave = intdiv($idx, 7);
    $degree = $idx % 7;
    if ($degree < 0) {
        $degree += 7;
        $octave--;
    }

    return $base + $octave * 12 + STEPWISE_OFFSETS[$degree];
}

// No octave marker — these quizzes test solfège (scale-degree) recognition
// only, not which octave a note lands in, so "Ti" one octave down from the
// pattern's start is still just "Ti" as far as the member's answer goes. The
// member-facing tap keyboard has no way to indicate an octave anyway (one
// "Do" key = one answer value), so the stored answer can't require one.
function stepwiseLabelForIndex(int $idx): string
{
    $degree = $idx % 7;
    if ($degree < 0) {
        $degree += 7;
    }

    return STEPWISE_DEGREE_NAMES[$degree];
}

function stepwiseSlugForIndex(int $idx): string
{
    $octaveShift = intdiv($idx, 7);
    $degree = $idx % 7;
    if ($degree < 0) {
        $degree += 7;
        $octaveShift--;
    }

    $slug = STEPWISE_DEGREE_SLUGS[$degree];
    if ($octaveShift > 0) return $slug.str_repeat('h', $octaveShift);
    if ($octaveShift < 0) return $slug.str_repeat('l', abs($octaveShift));

    return $slug;
}

// Returns the 28 patterns, each as ['label' => ..., 'relativeIndices' => [0, i2, i3]]
// — relativeIndices are offsets from the pattern's own start (startDegree already
// baked in), ready to feed straight into stepwiseMidiForIndex(slotStart + r, $base).
function stepwisePatterns(): array
{
    $patterns = [];
    foreach (range(0, 6) as $startDegree) {
        foreach (STEPWISE_DIRECTIONS as [$d1, $d2]) {
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
