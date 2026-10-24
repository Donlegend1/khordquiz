<?php

// Builds "Secondary 9th Chords · Level 2" — same 6 qualities as Level 1,
// root position, but across ALL 12 keys. 12 keys x 6 qualities = 72 rows —
// MORE than question_count (25), same pool-larger-than-question_count
// pattern used throughout. Rebuilds the quiz's questions and every
// instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEC9_L2_QUALITIES = ['Major 9(♯5)', 'Diminished-Major 9th', 'Dominant 9(♭5)', 'Minor-Major 9th', 'Augmented 9th', 'Major 9(♭5)'];

const SEC9_L2_SLUGS = [
    'Major 9(♯5)' => 'maj9s5', 'Diminished-Major 9th' => 'dimmaj9', 'Dominant 9(♭5)' => 'dom9b5',
    'Minor-Major 9th' => 'minmaj9', 'Augmented 9th' => 'aug9', 'Major 9(♭5)' => 'maj9b5',
];

const SEC9_L2_KEYS = [
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

$quiz = Quiz::where('title', 'Secondary 9th Chords · Level 2')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEC9_L2_KEYS as $keySlug => $keyName) {
    foreach (SEC9_L2_QUALITIES as $quality) {
        $qualitySlug = SEC9_L2_SLUGS[$quality];
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => SEC9_L2_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (SEC9_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "secondary-ninth-chords/level2/{$folderSlug}/{$keySlug}-{$qualitySlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(SEC9_L2_KEYS)." keys x 6 qualities; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(SEC9_INSTRUMENTS)." instruments.".PHP_EOL;
