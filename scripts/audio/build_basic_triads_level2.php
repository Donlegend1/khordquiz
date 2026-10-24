<?php

// Builds "Basic Triads · Level 2" — same 6 triad/sus qualities as Level 1
// (Major, Minor, Diminished, Augmented, Sus4, Sus2 — Sus2/Sus4 both answer
// "Suspended"), root position, but now across ALL 12 keys (C3 through B3)
// instead of just C, and across all 11 instruments like Level 1. 12 keys x
// 6 qualities = 72 rows — MORE than the quiz's question_count (25), which is
// intentional: QuizController::buildQuestionSequence() already fair-shuffles
// a pool larger than question_count with no repeats within a lap, so each
// attempt draws 25 of the 72 key/quality combos at random instead of
// hard-cycling a fixed subset. Rebuilds the quiz's questions and every
// instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const BASIC_TRIADS_L2_QUALITIES = ['Major', 'Minor', 'Diminished', 'Augmented', 'Suspended'];

// [answer quality, audio file slug suffix]
const BASIC_TRIADS_L2_VARIANTS = [
    ['Major', 'major'],
    ['Minor', 'minor'],
    ['Diminished', 'diminished'],
    ['Augmented', 'augmented'],
    ['Suspended', 'sus4'],
    ['Suspended', 'sus2'],
];

// key slug => display name, C3 through B3 chromatically.
const BASIC_TRIADS_L2_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

// sound => storage folder slug, matching attach_reference_scales.php's naming
// (and Level 1's build_basic_triads_level1.php).
const BASIC_TRIADS_L2_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Basic Triads · Level 2')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (BASIC_TRIADS_L2_KEYS as $keySlug => $keyName) {
    foreach (BASIC_TRIADS_L2_VARIANTS as [$quality, $qualitySlug]) {
        $position++;
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the triad and identify its quality.',
            'choices' => BASIC_TRIADS_L2_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (BASIC_TRIADS_L2_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "basic-triads/level2/{$folderSlug}/{$keySlug}-{$qualitySlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(BASIC_TRIADS_L2_KEYS)." keys x ".count(BASIC_TRIADS_L2_VARIANTS)." variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(BASIC_TRIADS_L2_INSTRUMENTS)." instruments.".PHP_EOL;
