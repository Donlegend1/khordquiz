<?php

// Builds "7th Degree Chords · Level 5" — compound quality+inversion answer
// (COMPOUND_ANSWER_PRESETS['7th Degree Chords'] in
// resources/js/lib/quizAnswerPresets.js, answer composed as
// "{quality} · {inversion}"), key of C only, all 5 qualities x 4 positions
// (root/1st/2nd/3rd, built by rotation) = 20 rows. Matches Basic Triads
// Level 5's shape, just 4 positions instead of 3 since 7th chords have 4
// notes. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEVENTH_L5_QUALITY_INTERVALS = [
    'Diminished 7th' => [0, 3, 6, 9],
    'Dominant 7th' => [0, 4, 7, 10],
    'Minor 7(♭5)' => [0, 3, 6, 10],
    'Major 7th' => [0, 4, 7, 11],
    'Minor 7th' => [0, 3, 7, 10],
];

const SEVENTH_L5_QUALITY_SLUGS = [
    'Diminished 7th' => 'diminished7', 'Dominant 7th' => 'dominant7', 'Minor 7(♭5)' => 'm7b5',
    'Major 7th' => 'major7', 'Minor 7th' => 'minor7',
];

const SEVENTH_L5_POSITION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];

const SEVENTH_L5_BASE = 48; // C3

const SEVENTH_L5_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', '7th Degree Chords · Level 5')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEVENTH_L5_QUALITY_INTERVALS as $qualityLabel => $intervals) {
    $qualitySlug = SEVENTH_L5_QUALITY_SLUGS[$qualityLabel];

    foreach (SEVENTH_L5_POSITION_LABELS as $posSlug => $posLabel) {
        $position++;
        $answer = $qualityLabel.' · '.$posLabel;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality and inversion.',
            'choices' => [$answer],
            'answer' => $answer,
        ]);

        foreach (SEVENTH_L5_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "seventh-degree-chords/level5/{$folderSlug}/{$qualitySlug}-{$posSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(SEVENTH_L5_INSTRUMENTS)." instruments.".PHP_EOL;
