<?php

// Computes MIDI voicings for 3 custom Chordal Melodies sequences the user
// dictated by hand (melody solfège + chord name + inversion per step), using
// the SAME voicing engine as the project's existing 35 patterns
// (chordal_melodies_pool.php's chordalBuildVoicings/chordalRawVoicing),
// just with bassIdx (from the stated inversion) and melodyIdx (from the
// stated solfège, resolved to a pitch class via CHORDAL_SOLFEGE) forced
// instead of auto-searched. Prints one line per sequence: space-separated
// comma-joined MIDI note groups, ready for render_chord_sequence.sh.
//
// Solfège used (fixed-do, chromatic, matches CHORDAL_SOLFEGE indices):
//   d=Do(0) r=Re(2) m=Mi(4) f=Fa(5) s=So(7) toh=Li(10) ze=Si(8)
// Chord quality names map to CHORDAL_QUALITY_INTERVALS: maj, min, dim,
// dom7 ("7"), min7, dim7.

require __DIR__.'/chordal_melodies_pool.php';

function pcFor(string $solfege): int
{
    $map = ['d' => 0, 'r' => 2, 'm' => 4, 'f' => 5, 's' => 7, 'toh' => 10, 'ze' => 8];
    return $map[$solfege];
}

function chordToneIndexForPc(int $root, string $quality, int $pc): int
{
    foreach (CHORDAL_QUALITY_INTERVALS[$quality] as $idx => $interval) {
        if (($root + $interval) % 12 === $pc) return $idx;
    }
    throw new RuntimeException("pc {$pc} not in {$quality} chord at root {$root}");
}

// inversion label => bass chord-tone index
const INV_IDX = ['root' => 0, '1st' => 1, '2nd' => 2, '3rd' => 3];

// Each step: [root pc (0-11), quality, inversion|null, melody solfège]
// inversion=null means "not stated" -> root position (bassIdx forced to 0),
// EXCEPT where explicitly marked 'auto' -> bass left free (the repeated-
// chord "reinversion" steps at the end of sequence 3).
function buildSteps(array $defs): array
{
    $steps = [];
    foreach ($defs as [$root, $quality, $inv, $solfege]) {
        $pc = pcFor($solfege);
        $melodyIdx = chordToneIndexForPc($root, $quality, $pc);
        $step = ['root' => $root, 'quality' => $quality, 'melodyIdx' => $melodyIdx];
        if ($inv === 'auto') {
            // bassIdx left unforced (null) -> engine auto-searches for smoothest voice leading.
        } else {
            $step['bassIdx'] = INV_IDX[$inv ?? 'root'];
        }
        $steps[] = $step;
    }
    return $steps;
}

// Sequence 1: m(C7 2nd) r(Fmin6) f(Fmin6 1st) m(Edim 1st) r(Ddim 1st) m(Cmaj 2nd)
$seq1 = buildSteps([
    [0, 'dom7', '2nd', 'm'],
    [5, 'min6', null, 'r'],
    [5, 'min6', '1st', 'f'],
    [4, 'dim', '1st', 'm'],
    [2, 'dim', '1st', 'r'],
    [0, 'maj', '2nd', 'm'],
]);

// Sequence 2: toh(Edim) ze(Ddim) s(Edim 2nd) f(Ddim 2nd) m(Cmaj 2nd)
$seq2 = buildSteps([
    [4, 'dim', null, 'toh'],
    [2, 'dim', null, 'ze'],
    [4, 'dim', '2nd', 's'],
    [2, 'dim', '2nd', 'f'],
    [0, 'maj', '2nd', 'm'],
]);

// Sequence 3: d(Amin 2nd) d(Cmaj 1st) d(Cdim7 1st) d(Dmin7 root) then
// C maj arpeggiated d-m-s-d (same chord re-voiced, bass auto-searched).
$seq3 = buildSteps([
    [9, 'min', '2nd', 'd'],
    [0, 'maj', '1st', 'd'],
    [0, 'dim7', '1st', 'd'],
    [2, 'min7', null, 'd'],
    [0, 'maj', 'auto', 'd'],
    [0, 'maj', 'auto', 'm'],
    [0, 'maj', 'auto', 's'],
    [0, 'maj', 'auto', 'd'],
]);

foreach (['seq1' => $seq1, 'seq2' => $seq2, 'seq3' => $seq3] as $name => $steps) {
    $voicings = chordalBuildVoicings($steps);
    $chordArgs = array_map(fn ($v) => implode(',', $v), $voicings);
    echo "{$name}\t".implode(' ', $chordArgs).PHP_EOL;
}
