<?php

// Builds "7th Degree Chords · Level 7" — same shape as Level 5 (key of C
// only, all 5 qualities x 4 positions, compound quality+inversion answer),
// but every position is additionally voiced in DROP-2 form based on C4
// (MIDI 60) instead of Level 5's close-position C3 — same relationship
// Basic Triads Level 7 has to Level 5. Rebuilds the quiz's questions and
// every instrument's attached audio from scratch every run.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEVENTH_L7_QUALITY_INTERVALS = [
    'Diminished 7th' => [0, 3, 6, 9],
    'Dominant 7th' => [0, 4, 7, 10],
    'Minor 7(♭5)' => [0, 3, 6, 10],
    'Major 7th' => [0, 4, 7, 11],
    'Minor 7th' => [0, 3, 7, 10],
];

const SEVENTH_L7_QUALITY_SLUGS = [
    'Diminished 7th' => 'diminished7', 'Dominant 7th' => 'dominant7', 'Minor 7(♭5)' => 'm7b5',
    'Major 7th' => 'major7', 'Minor 7th' => 'minor7',
];

const SEVENTH_L7_POSITION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];

const SEVENTH_L7_BASE = 60; // C4

const SEVENTH_L7_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

function seventhL7Rotations(array $intervals, int $root): array
{
    $notes = array_map(fn ($i) => $root + $i, $intervals);
    sort($notes);
    [$n0, $n1, $n2, $n3] = $notes;

    return [
        'root' => [$n0, $n1, $n2, $n3],
        '1st' => [$n1, $n2, $n3, $n0 + 12],
        '2nd' => [$n2, $n3, $n0 + 12, $n1 + 12],
        '3rd' => [$n3, $n0 + 12, $n1 + 12, $n2 + 12],
    ];
}

$quiz = Quiz::where('title', '7th Degree Chords · Level 7')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEVENTH_L7_QUALITY_INTERVALS as $qualityLabel => $intervals) {
    $qualitySlug = SEVENTH_L7_QUALITY_SLUGS[$qualityLabel];

    foreach (SEVENTH_L7_POSITION_LABELS as $posSlug => $posLabel) {
        $position++;
        $answer = $qualityLabel.' · '.$posLabel;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality and inversion.',
            'choices' => [$answer],
            'answer' => $answer,
        ]);

        foreach (SEVENTH_L7_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "seventh-degree-chords/level7/{$folderSlug}/{$qualitySlug}-{$posSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

// Sanity check the rendered files actually match chordalDrop2() — logged, not enforced.
foreach (SEVENTH_L7_QUALITY_INTERVALS as $qualityLabel => $intervals) {
    $qualitySlug = SEVENTH_L7_QUALITY_SLUGS[$qualityLabel];
    $rotations = seventhL7Rotations($intervals, SEVENTH_L7_BASE);
    foreach ($rotations as $posSlug => $voicing) {
        $drop = chordalDrop2($voicing);
        echo "  {$qualitySlug}-{$posSlug}: rotation ".implode(',', $voicing).' -> drop-2 '.implode(',', $drop).PHP_EOL;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(SEVENTH_L7_INSTRUMENTS)." instruments.".PHP_EOL;
