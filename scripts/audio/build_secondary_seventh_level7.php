<?php

// Builds "Secondary 7th Chords · Level 7" — same shape as Level 5 (key of C
// only, all 6 qualities x 4 positions, compound quality+inversion answer),
// but every position is additionally voiced in DROP-2 form based on C4
// (MIDI 60) instead of Level 5's close-position C3. Rebuilds the quiz's
// questions and every instrument's attached audio from scratch every run.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEC7_L7_QUALITY_INTERVALS = [
    'Augmented 7th' => [0, 4, 8, 10],
    'Minor-Major 7th' => [0, 3, 7, 11],
    'Diminished-Major 7th' => [0, 3, 6, 11],
    'Dominant 7(♭5)' => [0, 4, 6, 10],
    'Major 7(♯5)' => [0, 4, 8, 11],
    'Major 7(♭5)' => [0, 4, 6, 11],
];

const SEC7_L7_QUALITY_SLUGS = [
    'Augmented 7th' => 'aug7', 'Minor-Major 7th' => 'minmaj7', 'Diminished-Major 7th' => 'dimmaj7',
    'Dominant 7(♭5)' => 'dom7b5', 'Major 7(♯5)' => 'maj7s5', 'Major 7(♭5)' => 'maj7b5',
];

const SEC7_L7_POSITION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];

const SEC7_L7_BASE = 60; // C4

const SEC7_L7_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

function sec7L7Rotations(array $intervals, int $root): array
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

$quiz = Quiz::where('title', 'Secondary 7th Chords · Level 7')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEC7_L7_QUALITY_INTERVALS as $qualityLabel => $intervals) {
    $qualitySlug = SEC7_L7_QUALITY_SLUGS[$qualityLabel];

    foreach (SEC7_L7_POSITION_LABELS as $posSlug => $posLabel) {
        $position++;
        $answer = $qualityLabel.' · '.$posLabel;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality and inversion.',
            'choices' => [$answer],
            'answer' => $answer,
        ]);

        foreach (SEC7_L7_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "secondary-seventh-chords/level7/{$folderSlug}/{$qualitySlug}-{$posSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

// Sanity check the rendered files actually match chordalDrop2() — logged, not enforced.
foreach (SEC7_L7_QUALITY_INTERVALS as $qualityLabel => $intervals) {
    $qualitySlug = SEC7_L7_QUALITY_SLUGS[$qualityLabel];
    $rotations = sec7L7Rotations($intervals, SEC7_L7_BASE);
    foreach ($rotations as $posSlug => $voicing) {
        $drop = chordalDrop2($voicing);
        echo "  {$qualitySlug}-{$posSlug}: rotation ".implode(',', $voicing).' -> drop-2 '.implode(',', $drop).PHP_EOL;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(SEC7_L7_INSTRUMENTS)." instruments.".PHP_EOL;
