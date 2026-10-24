<?php

// Builds "Basic Triads · Level 1" — 6 distinct audios per instrument (Major,
// Minor, Diminished, Augmented, Sus2, Sus4 triads, root position, key of C,
// from C3). Sus2 and Sus4 are two different audio files but share the SAME
// correct answer ("Suspended" — the quiz tests quality, not which
// suspension), so there are 5 distinct answers drawn from 6 audio variants.
// The admin shows exactly these 6 rows — NOT padded/cycled up to the
// quiz's question_count (25); QuizController::buildQuestionSequence()
// already fair-shuffles a pool smaller than question_count (repeating
// across laps with no immediate repeats) to fill a real attempt. Flat
// multiple-choice quiz (quality only, no inversion/degree) — matches
// TRIAD_CHOICES in resources/js/lib/quizAnswerPresets.js. Rebuilds the
// quiz's questions (and every instrument's attached audio) from scratch
// every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const BASIC_TRIADS_QUALITIES = ['Major', 'Minor', 'Diminished', 'Augmented', 'Suspended'];

// [answer quality, audio file slug] — cycled in this order across the rows.
const BASIC_TRIADS_VARIANTS = [
    ['Major', 'major'],
    ['Minor', 'minor'],
    ['Diminished', 'diminished'],
    ['Augmented', 'augmented'],
    ['Suspended', 'sus4'],
    ['Suspended', 'sus2'],
];

// sound => storage folder slug, matching attach_reference_scales.php's naming.
const BASIC_TRIADS_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Basic Triads · Level 1')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$created = 0;
$audioAttached = 0;
$position = 0;
foreach (BASIC_TRIADS_VARIANTS as [$quality, $slug]) {
    $position++;

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Listen to the triad and identify its quality.',
        'choices' => BASIC_TRIADS_QUALITIES,
        'answer' => $quality,
    ]);

    foreach (BASIC_TRIADS_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "basic-triads/{$folderSlug}/{$slug}.wav"]
        );
        $audioAttached++;
    }
    $created++;
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(BASIC_TRIADS_INSTRUMENTS)." instruments.".PHP_EOL;
