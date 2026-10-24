<?php

// Builds "13th Degree Chords · Level 2" — same 4 qualities as Level 1,
// root position, but across ALL 12 keys. 12 keys x 4 qualities = 48 rows —
// MORE than question_count (25), same pool-larger-than-question_count
// pattern used throughout. Rebuilds the quiz's questions and every
// instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const THIRTEENTH_L2_QUALITIES = ['Major 13(♯11)', 'Minor 13th', 'Dominant 13(♯11)', 'Dominant 13(♭9,♯11)'];

const THIRTEENTH_L2_SLUGS = [
    'Major 13(♯11)' => 'maj13s11', 'Minor 13th' => 'minor13',
    'Dominant 13(♯11)' => 'dom13s11', 'Dominant 13(♭9,♯11)' => 'dom13b9s11',
];

const THIRTEENTH_L2_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const THIRTEENTH_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '13th Degree Chords · Level 2')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (THIRTEENTH_L2_KEYS as $keySlug => $keyName) {
    foreach (THIRTEENTH_L2_QUALITIES as $quality) {
        $qualitySlug = THIRTEENTH_L2_SLUGS[$quality];
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => THIRTEENTH_L2_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (THIRTEENTH_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "thirteenth-degree-chords/level2/{$folderSlug}/{$keySlug}-{$qualitySlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(THIRTEENTH_L2_KEYS)." keys x 4 qualities; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(THIRTEENTH_INSTRUMENTS)." instruments.".PHP_EOL;
