<?php

// Builds "Secondary 7th Chords · Level 9" (General) — all 11 seventh-chord
// qualities (5 from 7th Degree Chords + 6 from Secondary 7th Chords), all
// 12 keys, all 4 rotated positions for audio VARIETY, but flat quality-only
// answer — NOT compound quality+inversion, per explicit instruction: this
// quiz never asks the member to identify an inversion, only the quality.
// NO qualities excluded — Diminished 7th and Dominant 7(b5) (both symmetric
// chords with otherwise-ambiguous inversions) are included since inversion
// is never tested here anyway. 11 qualities x 12 keys x 4 positions = 528
// rows — MORE than the quiz's question_count (25), same pool-larger-than-
// question_count pattern used throughout. Rebuilds the quiz's questions and
// every instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEC7_L9_QUALITIES = ['Diminished 7th', 'Dominant 7th', 'Minor 7(♭5)', 'Major 7th', 'Minor 7th', 'Augmented 7th', 'Minor-Major 7th', 'Diminished-Major 7th', 'Dominant 7(♭5)', 'Major 7(♯5)', 'Major 7(♭5)'];

const SEC7_L9_QUALITY_SLUGS = [
    'diminished7' => 'Diminished 7th', 'dominant7' => 'Dominant 7th', 'm7b5' => 'Minor 7(♭5)',
    'major7' => 'Major 7th', 'minor7' => 'Minor 7th',
    'aug7' => 'Augmented 7th', 'minmaj7' => 'Minor-Major 7th', 'dimmaj7' => 'Diminished-Major 7th',
    'dom7b5' => 'Dominant 7(♭5)', 'maj7s5' => 'Major 7(♯5)', 'maj7b5' => 'Major 7(♭5)',
];

const SEC7_L9_POSITION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];

const SEC7_L9_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const SEC7_L9_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

// The DB record is literally titled "General" (not "Level 9" — that's
// mobile's internal subcategory slug, remapped to the "General" display
// label; the web catalog's own subcategory list — QuizCatalog::subcategories()
// — uses the bare string "General" too), scoped by category since multiple
// categories each have their own "General" quiz.
$quiz = Quiz::where('title', 'General')->where('category', 'Secondary 7th Chords')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEC7_L9_KEYS as $keySlug => $keyName) {
    foreach (SEC7_L9_QUALITY_SLUGS as $qualitySlug => $qualityLabel) {
        foreach (SEC7_L9_POSITION_LABELS as $posSlug => $posLabel) {
            $position++;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the chord and identify its quality.',
                'choices' => SEC7_L9_QUALITIES,
                'answer' => $qualityLabel,
            ]);

            foreach (SEC7_L9_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "secondary-seventh-chords/level9/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$posSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(SEC7_L9_KEYS)." keys x 11 qualities x 4 positions; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(SEC7_L9_INSTRUMENTS)." instruments.".PHP_EOL;
