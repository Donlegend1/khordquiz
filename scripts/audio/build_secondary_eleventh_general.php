<?php

// Builds "Secondary 11th Chords" General — all 10 qualities (6 from 11th
// Degree Chords + 4 from Secondary 11th Chords), all 12 keys, using the
// SAME "avoid the root" voicing as Level 6 in both source categories (8
// variants per quality). NO new audio rendered — reuses the existing Level
// 6 files directly from both categories' own storage folders, since
// together they already cover every key/quality/variant combo General
// needs. Flat quality-only answer, matches the existing 10-choice picker
// exactly. 12 keys x 10 qualities x 8 variants = 960 rows — the admin
// shows exactly these 960 rows (not padded); QuizController::
// buildQuestionSequence() fair-shuffles a random 25 per attempt from the
// real pool. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

// quality slug => [label, source category path prefix]
const GENERAL_QUALITIES = [
    'maj9s11' => ['Major 9(♯11)', 'eleventh-degree-chords/level6'],
    'minor11' => ['Minor 11th', 'eleventh-degree-chords/level6'],
    'dim7911' => ['Diminished 7(9,11)', 'eleventh-degree-chords/level6'],
    'dom9s11' => ['Dominant 9(♯11)', 'eleventh-degree-chords/level6'],
    'minor11b5' => ['Minor 11(♭5)', 'eleventh-degree-chords/level6'],
    'dom7b9s11' => ['Dominant 7(♭9,♯11)', 'eleventh-degree-chords/level6'],
    'maj9s5s11' => ['Major 9(♯5,♯11)', 'secondary-eleventh-chords/level6'],
    'dim7b911' => ['Diminished 7(♭9,11)', 'secondary-eleventh-chords/level6'],
    'minmaj11' => ['Minor-Major 11th', 'secondary-eleventh-chords/level6'],
    'aug9s11' => ['Augmented 9(♯11)', 'secondary-eleventh-chords/level6'],
];

const GENERAL_VARIANTS = ['tt1', 'tt2', 'ts1', 'ts2', 'st1', 'st2', 'ss1', 'ss2'];

const GENERAL_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const GENERAL_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'General')->where('category', 'Secondary 11th Chords')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$allQualities = array_map(fn ($q) => $q[0], GENERAL_QUALITIES);
$position = 0;
$created = 0;
$audioAttached = 0;

foreach (GENERAL_KEYS as $keySlug => $keyName) {
    foreach (GENERAL_QUALITIES as $qualitySlug => [$qualityLabel, $sourcePrefix]) {
        foreach (GENERAL_VARIANTS as $variantSlug) {
            $position++;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the chord and identify its quality.',
                'choices' => $allQualities,
                'answer' => $qualityLabel,
            ]);

            foreach (GENERAL_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "{$sourcePrefix}/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$variantSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(GENERAL_KEYS)." keys x 10 qualities x 8 variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(GENERAL_INSTRUMENTS)." instruments.".PHP_EOL;
