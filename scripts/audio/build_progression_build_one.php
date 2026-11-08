<?php

use App\Models\Quiz;
use App\Support\ProgressionBuildSamples;

$progressionSampleClass = ProgressionBuildSamples::class;
$progressionTitle = 'Progression Build #1';
$progressionPrefix = 'progression-build-one/f-major-v1';
$progressionExportName = 'progression-build-one';

require __DIR__.'/build_diatonic_progressions.php';

Quiz::where('title', $progressionTitle)->update([
    'description' => 'Build four-chord sequences in all 12 major keys using Major, Minor, Diminished, and Sus4 triads. Passing chords follow approved destinations, with Sus4 resolving to Major on the same degree. A tonic triad and tonic note introduce four-note voicings with close movement.',
    'is_published' => true,
]);

$quiz = Quiz::where('title', $progressionTitle)->firstOrFail();
foreach ($quiz->questions()->get() as $index => $question) {
    $question->update(['position' => $index + 1]);
}
