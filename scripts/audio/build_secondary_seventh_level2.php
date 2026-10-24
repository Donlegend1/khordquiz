<?php

// Builds "Secondary 7th Chords · Level 2" — same 6 qualities as Level 1,
// root position, but across ALL 12 keys instead of just C. 12 keys x 6
// qualities = 72 rows — MORE than the quiz's question_count (25), same
// pool-larger-than-question_count pattern as 7th Degree Chords Level 2.
// Rebuilds the quiz's questions and every instrument's attached audio from
// scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEC7_L2_QUALITIES = ['Augmented 7th', 'Minor-Major 7th', 'Diminished-Major 7th', 'Dominant 7(♭5)', 'Major 7(♯5)', 'Major 7(♭5)'];

const SEC7_L2_VARIANTS = [
    ['Augmented 7th', 'aug7'],
    ['Minor-Major 7th', 'minmaj7'],
    ['Diminished-Major 7th', 'dimmaj7'],
    ['Dominant 7(♭5)', 'dom7b5'],
    ['Major 7(♯5)', 'maj7s5'],
    ['Major 7(♭5)', 'maj7b5'],
];

const SEC7_L2_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const SEC7_L2_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Secondary 7th Chords · Level 2')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEC7_L2_KEYS as $keySlug => $keyName) {
    foreach (SEC7_L2_VARIANTS as [$quality, $qualitySlug]) {
        $position++;
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => SEC7_L2_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (SEC7_L2_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "secondary-seventh-chords/level2/{$folderSlug}/{$keySlug}-{$qualitySlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(SEC7_L2_KEYS)." keys x ".count(SEC7_L2_VARIANTS)." variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(SEC7_L2_INSTRUMENTS)." instruments.".PHP_EOL;
