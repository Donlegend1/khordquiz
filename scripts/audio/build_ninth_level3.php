<?php

// Builds "9th Degree Chords · Level 3" — key of C only, 6 qualities, flat
// quality-only answer (NOT compound quality+position — per explicit
// instruction, matches how Add 9 was corrected). Root stays fixed in the
// bass; the upper 4 notes (3rd/5th/7th/9th) form a 7th chord a 3rd above
// the root and get voiced through all 4 of ITS inversions for audio
// variety (see gen_ninth_level3_voicings.php) — the member is only asked
// to identify the quality, never which position. 6 qualities x 4 positions
// = 24 rows (less than question_count=25, same "pool smaller than
// question_count, repeats across laps" pattern as Basic Triads Level 5).
// Rebuilds the quiz's questions and every instrument's attached audio from
// scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const NINTH_L3_QUALITIES = ['Major 9th', 'Minor 9th', 'Diminished 7(9)', 'Dominant 9th', 'Minor 9(♭5)', 'Dominant 7(♭9)'];

const NINTH_L3_QUALITY_SLUGS = [
    'Major 9th' => 'major9', 'Minor 9th' => 'minor9', 'Diminished 7(9)' => 'dim7add9',
    'Dominant 9th' => 'dominant9', 'Minor 9(♭5)' => 'minor9b5', 'Dominant 7(♭9)' => 'dom7b9',
];

const NINTH_L3_POSITIONS = ['root', '1st', '2nd', '3rd'];

const NINTH_L3_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', '9th Degree Chords · Level 3')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (NINTH_L3_QUALITIES as $qualityLabel) {
    $qualitySlug = NINTH_L3_QUALITY_SLUGS[$qualityLabel];

    foreach (NINTH_L3_POSITIONS as $posSlug) {
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => NINTH_L3_QUALITIES,
            'answer' => $qualityLabel,
        ]);

        foreach (NINTH_L3_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "ninth-degree-chords/level3/{$folderSlug}/{$qualitySlug}-{$posSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(NINTH_L3_INSTRUMENTS)." instruments.".PHP_EOL;
