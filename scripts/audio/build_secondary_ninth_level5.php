<?php

// Builds "Secondary 9th Chords · Level 5" — key of C only, flat
// quality-only answer. "Two-hands, no-root" voicing, same rule as 9th
// Degree Chords Level 5 (see gen_secondary_ninth_level5_voicings.php). 5
// variants per quality (2 triad-left + 3 seventh-left) x 6 qualities = 30
// rows. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEC9_L5_QUALITIES = ['Major 9(♯5)', 'Diminished-Major 9th', 'Dominant 9(♭5)', 'Minor-Major 9th', 'Augmented 9th', 'Major 9(♭5)'];

const SEC9_L5_SLUGS = [
    'Major 9(♯5)' => 'maj9s5', 'Diminished-Major 9th' => 'dimmaj9', 'Dominant 9(♭5)' => 'dom9b5',
    'Minor-Major 9th' => 'minmaj9', 'Augmented 9th' => 'aug9', 'Major 9(♭5)' => 'maj9b5',
];

const SEC9_L5_VARIANTS = ['p1v1', 'p1v2', 'p2v1', 'p2v2', 'p2v3'];

const SEC9_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Secondary 9th Chords · Level 5')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEC9_L5_QUALITIES as $quality) {
    $qualitySlug = SEC9_L5_SLUGS[$quality];

    foreach (SEC9_L5_VARIANTS as $variantSlug) {
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => SEC9_L5_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (SEC9_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "secondary-ninth-chords/level5/{$folderSlug}/{$qualitySlug}-{$variantSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of 6 qualities x 5 variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(SEC9_INSTRUMENTS)." instruments.".PHP_EOL;
