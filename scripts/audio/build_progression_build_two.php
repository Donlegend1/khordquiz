<?php

use App\Models\Quiz;
use App\Support\SeventhProgressionBuildSamples;

$progressionSampleClass = SeventhProgressionBuildSamples::class;
$progressionTitle = 'Progression Build #2';
$progressionPrefix = 'progression-build-two/c-major-v1';
$progressionExportName = 'progression-build-two';

require __DIR__.'/build_diatonic_progressions.php';

Quiz::where('title', $progressionTitle)->update([
    'description' => 'Build four-chord seventh-chord sequences in all 12 major keys using Major 7, Minor 7, Dominant 7, Minor 7♭5, and 7 Sus4. Passing chords follow their approved destinations. Every example starts with a fixed octave-3 home major triad then its tonic note, followed by five-note chord voicings with close movement.',
    'is_published' => true,
]);

$quiz = Quiz::where('title', $progressionTitle)->firstOrFail();
foreach ($quiz->questions()->get() as $index => $question) {
    $question->update(['position' => $index + 1]);
}
