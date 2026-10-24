<?php

// Builds "Secondary 11th Chords · Level 5" — key of C only, flat
// quality-only answer. Same "avoid the root" voicing as 11th Degree
// Chords Level 5. 8 variants per quality x 4 qualities = 32 rows.
// Rebuilds the quiz's questions and every instrument's attached audio from
// scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEC11_QUALITIES = ['Major 9(♯5,♯11)', 'Diminished 7(♭9,11)', 'Minor-Major 11th', 'Augmented 9(♯11)'];

const SEC11_SLUGS = [
    'Major 9(♯5,♯11)' => 'maj9s5s11', 'Diminished 7(♭9,11)' => 'dim7b911',
    'Minor-Major 11th' => 'minmaj11', 'Augmented 9(♯11)' => 'aug9s11',
];

const SEC11_VARIANTS = ['tt1', 'tt2', 'ts1', 'ts2', 'st1', 'st2', 'ss1', 'ss2'];

const SEC11_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Secondary 11th Chords · Level 5')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEC11_QUALITIES as $quality) {
    $qualitySlug = SEC11_SLUGS[$quality];

    foreach (SEC11_VARIANTS as $variantSlug) {
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => SEC11_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (SEC11_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "secondary-eleventh-chords/level5/{$folderSlug}/{$qualitySlug}-{$variantSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of 4 qualities x 8 variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(SEC11_INSTRUMENTS)." instruments.".PHP_EOL;
