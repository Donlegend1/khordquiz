<?php

// Replicates "Chordal melodies" into "Chordal Bass Lines" — same chords, same
// audio files (reused, not re-rendered), but the answer is each chord's
// BOTTOM note (bass line) instead of its top note (melody). Re-run this
// script any time Chordal melodies gets new/changed content; it rebuilds
// Chordal Bass Lines' pool to match from scratch (safe — Chordal Bass Lines
// never has its own independent content, it's purely derived).

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

$source = Quiz::where('title', 'Chordal melodies')->firstOrFail();
$target = Quiz::where('title', 'Chordal Bass Lines')->firstOrFail();

QuizAttempt::where('quiz_id', $target->id)->whereDoesntHave('answers')->delete();
$target->questions()->delete();

// A question only qualifies for Chordal Bass Lines when every chord's bass
// note stays at or below the app's "C4" (MIDI 48 — same convention as the
// reference scales, where MIDI 36 is "C3") — above that it's not really
// reading as a "bass line" anymore, it's just the bottom of a mid-register
// chord voicing. Chordal melodies' own voicings are centered right on this
// note, so most progressions clear it; this still filters out anything that
// climbs meaningfully above it.
const BASS_LINE_MAX_NOTE = 48; // app's "C4"

// Chordal melodies' rows were built from a fixed, ordered list of progressions
// (chordalLiteralProgressions() right now — chordalAllProgressions() once the
// larger generated/rebuilt sets are turned back on), in the same order as the
// rows themselves (see build_chordal_melodies_piano.php). Recompute each
// pattern's voicings the same way to get the bass line, rather than trying to
// derive it from the stored top-note answer text.
$patterns = array_map(
    fn ($steps) => ['voicings' => chordalBuildVoicings($steps)],
    chordalLiteralProgressions()
);

$sourceRows = $source->questions()->orderBy('position')->get();
if ($sourceRows->count() !== count($patterns)) {
    echo "Row count mismatch: Chordal melodies has {$sourceRows->count()} rows, but chordalLiteralProgressions() has ".count($patterns).". Update the \$patterns source above to match current Chordal melodies content before re-running.".PHP_EOL;
    return;
}

$position = 0;
$created = 0;
$skipped = 0;
$audioCopied = 0;

foreach ($sourceRows as $i => $sourceQuestion) {
    $voicings = $patterns[$i]['voicings'];
    $bassNotes = array_map(fn ($v) => $v[0], $voicings);

    if (max($bassNotes) > BASS_LINE_MAX_NOTE) {
        echo "  skipping '{$sourceQuestion->answer}' — bass note reaches ".max($bassNotes)." (above C4/".BASS_LINE_MAX_NOTE.").".PHP_EOL;
        $skipped++;
        continue;
    }

    $label = chordalStripOctaves(implode(' ', array_map('chordalLabelForTop', $bassNotes)));

    $position++;
    $targetQuestion = QuizQuestion::create([
        'quiz_id' => $target->id,
        'position' => $position,
        'prompt' => 'Tap the bass line (bottom note of each chord) in the order you hear it.',
        'choices' => [$label],
        'answer' => $label,
    ]);
    $created++;

    foreach (QuizQuestionAudio::where('quiz_question_id', $sourceQuestion->id)->get() as $sourceAudio) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $targetQuestion->id, 'sound' => $sourceAudio->sound],
            ['audio_path' => $sourceAudio->audio_path] // same file, reused directly
        );
        $audioCopied++;
    }
}

echo "Created {$created} rows, skipped {$skipped} (bass too high), copied {$audioCopied} audio attachments (reusing Chordal melodies' files).".PHP_EOL;
