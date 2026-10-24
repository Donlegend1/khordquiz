<?php

// Builds "Secondary 7th Chords · Level 5" — compound quality+inversion
// answer (COMPOUND_ANSWER_PRESETS['Secondary 7th Chords'] in
// resources/js/lib/quizAnswerPresets.js, answer composed as
// "{quality} · {inversion}"), key of C only, all 6 qualities x 4 positions
// (root/1st/2nd/3rd, built by rotation) = 24 rows. Matches 7th Degree
// Chords Level 5's shape. Rebuilds the quiz's questions and every
// instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEC7_L5_QUALITY_INTERVALS = [
    'Augmented 7th' => [0, 4, 8, 10],
    'Minor-Major 7th' => [0, 3, 7, 11],
    'Diminished-Major 7th' => [0, 3, 6, 11],
    'Dominant 7(♭5)' => [0, 4, 6, 10],
    'Major 7(♯5)' => [0, 4, 8, 11],
    'Major 7(♭5)' => [0, 4, 6, 11],
];

const SEC7_L5_QUALITY_SLUGS = [
    'Augmented 7th' => 'aug7', 'Minor-Major 7th' => 'minmaj7', 'Diminished-Major 7th' => 'dimmaj7',
    'Dominant 7(♭5)' => 'dom7b5', 'Major 7(♯5)' => 'maj7s5', 'Major 7(♭5)' => 'maj7b5',
];

const SEC7_L5_POSITION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];

const SEC7_L5_BASE = 48; // C3

const SEC7_L5_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Secondary 7th Chords · Level 5')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEC7_L5_QUALITY_INTERVALS as $qualityLabel => $intervals) {
    $qualitySlug = SEC7_L5_QUALITY_SLUGS[$qualityLabel];

    foreach (SEC7_L5_POSITION_LABELS as $posSlug => $posLabel) {
        $position++;
        $answer = $qualityLabel.' · '.$posLabel;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality and inversion.',
            'choices' => [$answer],
            'answer' => $answer,
        ]);

        foreach (SEC7_L5_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "secondary-seventh-chords/level5/{$folderSlug}/{$qualitySlug}-{$posSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(SEC7_L5_INSTRUMENTS)." instruments.".PHP_EOL;
