<?php

// Builds "Secondary 7th Chords · Level 6" — same compound quality+inversion
// answer shape as Level 5, but across ALL 12 keys instead of just C.
// Dominant 7(b5) is EXCLUDED here (matches Level 8, the drop-2 version of
// this level): it's a tritone-symmetric chord (two tritones stacked a major
// 3rd apart — [0,4,6,10] maps onto itself transposed a tritone), so with no
// fixed key to anchor against, its inversions are indistinguishable from
// each other by ear. Same reasoning as 7th Degree Chords excluding
// Diminished 7th from its own Levels 6/8. 5 qualities x 4 positions x 12
// keys = 240 rows — MORE than the quiz's question_count (25), same pool-
// larger-than-question_count pattern. Rebuilds the quiz's questions and
// every instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

// Dominant 7(b5) deliberately excluded — see comment above.
const SEC7_L6_QUALITY_SLUGS = [
    'aug7' => 'Augmented 7th', 'minmaj7' => 'Minor-Major 7th', 'dimmaj7' => 'Diminished-Major 7th',
    'maj7s5' => 'Major 7(♯5)', 'maj7b5' => 'Major 7(♭5)',
];

const SEC7_L6_POSITION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];

const SEC7_L6_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const SEC7_L6_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Secondary 7th Chords · Level 6')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEC7_L6_KEYS as $keySlug => $keyName) {
    foreach (SEC7_L6_QUALITY_SLUGS as $qualitySlug => $qualityLabel) {
        foreach (SEC7_L6_POSITION_LABELS as $posSlug => $posLabel) {
            $position++;
            $answer = $qualityLabel.' · '.$posLabel;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the chord and identify its quality and inversion.',
                'choices' => [$answer],
                'answer' => $answer,
            ]);

            foreach (SEC7_L6_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "secondary-seventh-chords/level6/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$posSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(SEC7_L6_KEYS)." keys x 5 qualities x 4 positions; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(SEC7_L6_INSTRUMENTS)." instruments.".PHP_EOL;
