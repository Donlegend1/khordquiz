<?php

// Builds "13th Degree Chords · Level 5" — key of C only, "avoid the root":
// LEFT hand (root-position triad) and RIGHT hand (upper 7th-structure) both
// inverted together, 1st and 2nd inversion only — see
// gen_thirteenth_level5_voicings.php. 2 variants x 4 qualities = 8 rows —
// the admin shows exactly these 8 rows (not padded); QuizController::
// buildQuestionSequence() fair-shuffles a random 25 per attempt from the
// real pool. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const THIRTEENTH_L5_QUALITIES = ['Major 13(♯11)', 'Minor 13th', 'Dominant 13(♯11)', 'Dominant 13(♭9,♯11)'];

const THIRTEENTH_L5_SLUGS = [
    'Major 13(♯11)' => 'maj13s11', 'Minor 13th' => 'minor13',
    'Dominant 13(♯11)' => 'dom13s11', 'Dominant 13(♭9,♯11)' => 'dom13b9s11',
];

const THIRTEENTH_L5_VARIANTS = ['v1', 'v2'];

const THIRTEENTH_L5_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '13th Degree Chords · Level 5')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (THIRTEENTH_L5_QUALITIES as $quality) {
    $qualitySlug = THIRTEENTH_L5_SLUGS[$quality];

    foreach (THIRTEENTH_L5_VARIANTS as $variantSlug) {
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => THIRTEENTH_L5_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (THIRTEENTH_L5_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "thirteenth-degree-chords/level5/{$folderSlug}/{$qualitySlug}-{$variantSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of 4 qualities x 2 variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(THIRTEENTH_L5_INSTRUMENTS)." instruments.".PHP_EOL;
