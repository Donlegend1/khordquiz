<?php

// Doubles both "Chordal melodies" and "Chordal Bass Lines" so each quiz uses
// BOTH voicing formats (original close-position AND drop-2) for every
// progression, reusing the exact same audio files between the two quizzes
// instead of rendering anything new:
//   - Chordal melodies gets its existing original-voicing rows (melody/top
//     note answer) PLUS one new row per pattern that reuses Chordal Bass
//     Lines' drop-2 audio (same melody/top note answer — drop-2 doesn't
//     touch the top note, only the voicing underneath it).
//   - Chordal Bass Lines gets its existing drop-2 rows (bass/bottom note
//     answer) PLUS one new row per pattern that reuses Chordal melodies'
//     original audio, answered by THAT voicing's bottom note instead.
// Existing rows/audio are left untouched; this only appends. Safe to re-run
// only if the appended rows are deleted first (it does not dedupe).

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

$melodies = Quiz::where('title', 'Chordal melodies')->firstOrFail();
$bassLines = Quiz::where('title', 'Chordal Bass Lines')->firstOrFail();

$melodyRows = $melodies->questions()->orderBy('position')->get();
$bassRows = $bassLines->questions()->orderBy('position')->get();

$patterns = chordalLiteralProgressions();

if ($melodyRows->count() !== count($patterns) || $bassRows->count() !== count($patterns)) {
    echo "Row count mismatch — Chordal melodies has {$melodyRows->count()}, Chordal Bass Lines has {$bassRows->count()}, chordalLiteralProgressions() has ".count($patterns).". Aborting.".PHP_EOL;
    return;
}

$melodyPosition = $melodyRows->max('position');
$bassPosition = $bassRows->max('position');
$addedToMelodies = 0;
$addedToBassLines = 0;

foreach ($patterns as $i => $steps) {
    $originalVoicings = chordalBuildVoicings($steps);
    $dropVoicings = chordalDrop2Voicings($originalVoicings);

    $melodyRow = $melodyRows[$i];
    $bassRow = $bassRows[$i];

    $originalAudio = QuizQuestionAudio::where('quiz_question_id', $melodyRow->id)->where('sound', 'Piano')->first();
    $dropAudio = QuizQuestionAudio::where('quiz_question_id', $bassRow->id)->where('sound', 'Piano')->first();

    if (!$originalAudio || !$dropAudio) {
        echo "  pattern {$i}: missing Piano audio on source row — skipping.".PHP_EOL;
        continue;
    }

    // New Chordal melodies row: drop-2 audio, same top-note answer as the
    // original-voicing row (drop-2 never changes the melody/top note).
    $melodyPosition++;
    $newMelodyRow = QuizQuestion::create([
        'quiz_id' => $melodies->id,
        'position' => $melodyPosition,
        'prompt' => $melodyRow->prompt,
        'choices' => $melodyRow->choices,
        'answer' => $melodyRow->answer,
    ]);
    QuizQuestionAudio::updateOrCreate(
        ['quiz_question_id' => $newMelodyRow->id, 'sound' => 'Piano'],
        ['audio_path' => $dropAudio->audio_path]
    );
    $addedToMelodies++;

    // New Chordal Bass Lines row: original (non-drop-2) audio, answered by
    // THAT voicing's own bottom note.
    $bassNotes = array_map(fn ($v) => $v[0], $originalVoicings);
    $bassLabel = chordalStripOctaves(implode(' ', array_map('chordalLabelForTop', $bassNotes)));

    $bassPosition++;
    $newBassRow = QuizQuestion::create([
        'quiz_id' => $bassLines->id,
        'position' => $bassPosition,
        'prompt' => $bassRow->prompt,
        'choices' => [$bassLabel],
        'answer' => $bassLabel,
    ]);
    QuizQuestionAudio::updateOrCreate(
        ['quiz_question_id' => $newBassRow->id, 'sound' => 'Piano'],
        ['audio_path' => $originalAudio->audio_path]
    );
    $addedToBassLines++;
}

echo "Added {$addedToMelodies} rows to Chordal melodies (now {$melodies->questions()->count()}).".PHP_EOL;
echo "Added {$addedToBassLines} rows to Chordal Bass Lines (now {$bassLines->questions()->count()}).".PHP_EOL;
