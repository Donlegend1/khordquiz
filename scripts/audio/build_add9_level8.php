<?php

// Builds "Add 9 · Level 8" — same shape as Level 4 (12 keys x 4 qualities x
// 4 rotated positions = 192 rows for audio variety, flat quality-only
// answer — NOT compound quality+position, per explicit instruction — pool
// larger than question_count (25) so QuizController::buildQuestionSequence()
// fair-shuffles a random 25 per attempt), but every position is additionally
// voiced in DROP-2 form (2nd-from-top note dropped an octave, applied AFTER
// the rotation — see gen_add9_level8_voicings.php) based on C4 through B4
// instead of Level 4's close-position C3-B3 — same relationship Level 7 has
// to Level 3, and Basic Triads Level 8 has to Level 6. Rebuilds the quiz's
// questions and every instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const ADD9_L8_QUALITIES = ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'];

const ADD9_L8_POSITION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];

const ADD9_L8_QUALITY_LABELS = [
    'major' => 'Major add 9', 'minor' => 'Minor add 9',
    'diminished' => 'Diminished add 9', 'augmented' => 'Augmented add 9',
];

const ADD9_L8_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

// sound => storage folder slug, matching Levels 1-7's naming.
const ADD9_L8_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Add 9 · Level 8')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (ADD9_L8_KEYS as $keySlug => $keyName) {
    foreach (ADD9_L8_QUALITY_LABELS as $qualitySlug => $qualityLabel) {
        foreach (ADD9_L8_POSITION_LABELS as $posSlug => $posLabel) {
            $position++;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the chord and identify its quality.',
                'choices' => ADD9_L8_QUALITIES,
                'answer' => $qualityLabel,
            ]);

            foreach (ADD9_L8_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "add9/level8/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$posSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(ADD9_L8_KEYS)." keys x 4 qualities x 4 positions; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(ADD9_L8_INSTRUMENTS)." instruments.".PHP_EOL;
