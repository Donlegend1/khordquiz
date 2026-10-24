<?php

// Builds "Secondary 9th Chords · Level 4" — same shape as Level 3, but
// across ALL 12 keys. 12 keys x 6 qualities x 4 positions = 288 rows.
// Rebuilds the quiz's questions and every instrument's attached audio from
// scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEC9_L4_QUALITIES = ['Major 9(♯5)', 'Diminished-Major 9th', 'Dominant 9(♭5)', 'Minor-Major 9th', 'Augmented 9th', 'Major 9(♭5)'];

const SEC9_L4_SLUGS = [
    'Major 9(♯5)' => 'maj9s5', 'Diminished-Major 9th' => 'dimmaj9', 'Dominant 9(♭5)' => 'dom9b5',
    'Minor-Major 9th' => 'minmaj9', 'Augmented 9th' => 'aug9', 'Major 9(♭5)' => 'maj9b5',
];

const SEC9_L4_POSITIONS = ['root', '1st', '2nd', '3rd'];

const SEC9_L4_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const SEC9_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Secondary 9th Chords · Level 4')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEC9_L4_KEYS as $keySlug => $keyName) {
    foreach (SEC9_L4_QUALITIES as $quality) {
        $qualitySlug = SEC9_L4_SLUGS[$quality];

        foreach (SEC9_L4_POSITIONS as $posSlug) {
            $position++;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the chord and identify its quality.',
                'choices' => SEC9_L4_QUALITIES,
                'answer' => $quality,
            ]);

            foreach (SEC9_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "secondary-ninth-chords/level4/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$posSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(SEC9_L4_KEYS)." keys x 6 qualities x 4 positions; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(SEC9_INSTRUMENTS)." instruments.".PHP_EOL;
