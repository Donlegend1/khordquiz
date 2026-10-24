<?php

// Builds "Secondary 9th Chords · Level 3" — key of C only, flat
// quality-only answer. Root fixed in bass; the upper 4 notes (3rd/5th/7th/
// 9th) form a 7th chord a 3rd above the root and get voiced through all 4
// of ITS inversions for audio variety (see
// gen_secondary_ninth_level3_voicings.php) — the member only identifies
// the quality, never the position. 6 qualities x 4 positions = 24 rows.
// Rebuilds the quiz's questions and every instrument's attached audio from
// scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEC9_L3_QUALITIES = ['Major 9(♯5)', 'Diminished-Major 9th', 'Dominant 9(♭5)', 'Minor-Major 9th', 'Augmented 9th', 'Major 9(♭5)'];

const SEC9_L3_SLUGS = [
    'Major 9(♯5)' => 'maj9s5', 'Diminished-Major 9th' => 'dimmaj9', 'Dominant 9(♭5)' => 'dom9b5',
    'Minor-Major 9th' => 'minmaj9', 'Augmented 9th' => 'aug9', 'Major 9(♭5)' => 'maj9b5',
];

const SEC9_L3_POSITIONS = ['root', '1st', '2nd', '3rd'];

const SEC9_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Secondary 9th Chords · Level 3')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEC9_L3_QUALITIES as $quality) {
    $qualitySlug = SEC9_L3_SLUGS[$quality];

    foreach (SEC9_L3_POSITIONS as $posSlug) {
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => SEC9_L3_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (SEC9_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "secondary-ninth-chords/level3/{$folderSlug}/{$qualitySlug}-{$posSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(SEC9_INSTRUMENTS)." instruments.".PHP_EOL;
