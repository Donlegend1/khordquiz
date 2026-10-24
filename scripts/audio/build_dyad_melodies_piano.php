<?php

// Builds "Dyad Melodies"'s real pool for Piano — 35 patterns x 3 starting
// octaves = 105 rows. ONE-TIME pool creator: every other instrument attaches
// to these same rows via build_dyad_melodies_instrument.php.
//
// $base = 48 (C3), NOT the original 36 (C2) this file used when the pool was
// first built — the harmony voice (a 3rd/4th below the melody) was dipping
// as low as MIDI 28 (E1), confirmed too deep by ear for Piano/EP/Organ/
// Guitar/Jazz Guitar/Strings/Cello/Harp/Celesta, all since re-rendered at 48.
// Per-instrument register is NOT uniform — keep this in sync by hand if the
// pool or patterns ever change:
//   - base 48 (+12 from original): Piano, EP, Organ, Guitar, Jazz Guitar,
//     Strings, Cello, Harp, Celesta, Tuba, Voice (DRY=1).
//   - base 36 (original, untouched): Saxophone, Trombone, Marimba,
//     Vibraphone — explicitly NOT shifted.
//   - Bass Guitar: left at the ORIGINAL unshifted voicings on purpose — its
//     low range is idiomatic for a bass part, not a register problem.
//   - Violin: flagged but not yet fixed — even the 48 floor (MIDI 40) sits
//     below Violin's open G string (MIDI 55); needs a larger instrument-
//     specific shift, still pending a decision.

require __DIR__.'/dyad_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;

$quiz = Quiz::where('title', 'Dyad Melodies')->firstOrFail();

if ($quiz->questions()->count() > 0) {
    echo 'Pool already exists ('.$quiz->questions()->count()." rows) — refusing to recreate it.".PHP_EOL;
    return;
}

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();

$base = 48; // C3 — see the register note above; this only affects a fresh pool build
$octaveStarts = [0, 1, 2];
$patterns = dyadMelodyPatterns();

$manifest = [];
$created = 0;
$position = 0;

foreach ($patterns as $pattern) {
    foreach ($octaveStarts as $octaveStart) {
        $shiftedDyads = array_map(
            fn ($d) => ['harmonyIdx' => $d['harmonyIdx'] + $octaveStart * 7, 'topIdx' => $d['topIdx'] + $octaveStart * 7],
            $pattern['dyads']
        );
        $voicings = dyadMelodyVoicings($shiftedDyads, $base);

        $position++;
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Tap the melody (top note of each dyad) in the order you hear it.',
            'choices' => [$pattern['label']],
            'answer' => $pattern['label'],
        ]);

        $manifest[] = [
            'question_id' => $question->id,
            'file_slug' => "{$pattern['fileSlug']}-o{$octaveStart}",
            'voicings' => $voicings,
        ];
        $created++;
    }
}

file_put_contents(__DIR__.'/dyad_melodies_piano_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
echo "Created {$created} pool rows (".count($patterns)." patterns x 3 octaves). Manifest written.".PHP_EOL;
