<?php

// Builds "9th Degree Chords · Level 5" — key of C only, flat quality-only
// answer (matches Levels 1-4's answer shape). "Two-hands, no-root" voicing:
// left hand plays a lower triad or 7th chord (root/3rd/5th, or
// root/3rd/5th/7th) in an inversion that is NEVER root position; right
// hand always plays the upper triad (5th/7th/9th), placed above the left
// hand, with its inversion = (left hand's inversion + 1) mod 3 — see
// gen_ninth_level5_voicings.php for the full rule and confirmation. 5
// variants per quality (2 from the triad-left pattern, 3 from the 7th-
// chord-left pattern) x 6 qualities = 30 rows (less than question_count=25
// ... wait, 30 > 25, so still the pool-larger-than-question_count pattern).
// Rebuilds the quiz's questions and every instrument's attached audio from
// scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const NINTH_L5_QUALITIES = ['Major 9th', 'Minor 9th', 'Diminished 7(9)', 'Dominant 9th', 'Minor 9(♭5)', 'Dominant 7(♭9)'];

const NINTH_L5_QUALITY_SLUGS = [
    'Major 9th' => 'major9', 'Minor 9th' => 'minor9', 'Diminished 7(9)' => 'dim7add9',
    'Dominant 9th' => 'dominant9', 'Minor 9(♭5)' => 'minor9b5', 'Dominant 7(♭9)' => 'dom7b9',
];

const NINTH_L5_VARIANTS = ['p1v1', 'p1v2', 'p2v1', 'p2v2', 'p2v3'];

const NINTH_L5_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', '9th Degree Chords · Level 5')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (NINTH_L5_QUALITIES as $qualityLabel) {
    $qualitySlug = NINTH_L5_QUALITY_SLUGS[$qualityLabel];

    foreach (NINTH_L5_VARIANTS as $variantSlug) {
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => NINTH_L5_QUALITIES,
            'answer' => $qualityLabel,
        ]);

        foreach (NINTH_L5_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "ninth-degree-chords/level5/{$folderSlug}/{$qualitySlug}-{$variantSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of 6 qualities x 5 variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(NINTH_L5_INSTRUMENTS)." instruments.".PHP_EOL;
