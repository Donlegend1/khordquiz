<?php

use App\Models\Quiz;
use App\Support\DiatonicSeventhProgressionSamples;

$progressionSampleClass = DiatonicSeventhProgressionSamples::class;
$progressionTitle = 'Diatonic Progression #2';
$progressionPrefix = 'diatonic-seventh-progressions/c-major-v1';
$progressionExportName = 'diatonic-seventh-progressions';

require __DIR__.'/build_diatonic_progressions.php';

Quiz::where('title', $progressionTitle)->update([
    'description' => 'Identify four diatonic seventh chords in all 12 major keys. A root-position major triad and tonic note introduce each sequence in its key. Includes Imaj7, ii7, iii7, IVmaj7, V7, vi7, and vii half-diminished, with five-note voicings that double the second-lowest chord tone as the top melody, close movement, and comfortable instrument registers.',
    'is_published' => true,
]);
