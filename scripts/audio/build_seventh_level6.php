<?php

// Builds "7th Degree Chords · Level 6" — same compound quality+inversion
// answer shape as Level 5, but across ALL 12 keys instead of just C.
// Diminished 7th is EXCLUDED here (matches Level 8, the drop-2 version of
// this level): it's a fully symmetric chord (stacked minor 3rds), so with
// no fixed key to anchor against, its inversions are indistinguishable from
// each other by ear — e.g. "Eb diminished 7th root position" and "C
// diminished 7th 1st inversion" are the exact same pitch classes. Same
// reasoning as Basic Triads excluding Augmented/Suspended from its own
// Levels 6/8. 4 qualities x 4 positions x 12 keys = 192 rows — MORE than
// the quiz's question_count (25), same pool-larger-than-question_count
// pattern as Basic Triads Level 6. Rebuilds the quiz's questions and every
// instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

// Diminished 7th deliberately excluded — see comment above.
const SEVENTH_L6_QUALITY_SLUGS = [
    'dominant7' => 'Dominant 7th', 'm7b5' => 'Minor 7(♭5)',
    'major7' => 'Major 7th', 'minor7' => 'Minor 7th',
];

const SEVENTH_L6_POSITION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];

const SEVENTH_L6_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const SEVENTH_L6_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', '7th Degree Chords · Level 6')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEVENTH_L6_KEYS as $keySlug => $keyName) {
    foreach (SEVENTH_L6_QUALITY_SLUGS as $qualitySlug => $qualityLabel) {
        foreach (SEVENTH_L6_POSITION_LABELS as $posSlug => $posLabel) {
            $position++;
            $answer = $qualityLabel.' · '.$posLabel;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the chord and identify its quality and inversion.',
                'choices' => [$answer],
                'answer' => $answer,
            ]);

            foreach (SEVENTH_L6_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "seventh-degree-chords/level6/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$posSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(SEVENTH_L6_KEYS)." keys x 4 qualities x 4 positions; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(SEVENTH_L6_INSTRUMENTS)." instruments.".PHP_EOL;
