<?php

// Fixes the 4 "Dual-tonic melodies" patterns that had zero non-diatonic
// notes (every one of their step-moves happened to land on the scale's two
// existing half-steps) — dual_tonic_melodies_pool.php now inserts a
// chromatic passing tone into each pattern's first skip as a fallback.
// This script updates the existing QuizQuestion rows' answer/choices to the
// new (longer) labels and re-renders every already-attached instrument's
// audio for just these rows, overwriting the existing file paths in place.

require __DIR__.'/dual_tonic_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const OLD_LABELS = ['Do Mi Fa Mi Do', 'Mi Do Ti, Do Mi', "So Ti Do' Ti So", 'La Fa Mi Fa La'];

const INSTRUMENTS = [
    'Piano' => ['base' => 36, 'octaveCount' => 3, 'folder' => 'piano'],
    'EP' => ['base' => 36, 'octaveCount' => 3, 'folder' => 'ep'],
    'Organ' => ['base' => 36, 'octaveCount' => 3, 'folder' => 'organ'],
    'Guitar' => ['base' => 36, 'octaveCount' => 3, 'folder' => 'guitar'],
    'Jazz Guitar' => ['base' => 36, 'octaveCount' => 3, 'folder' => 'jazz-guitar'],
    'Bass Guitar' => ['base' => 36, 'octaveCount' => 2, 'folder' => 'bass-guitar'],
    'Violin' => ['base' => 60, 'octaveCount' => 2, 'folder' => 'violin'],
    'Strings' => ['base' => 36, 'octaveCount' => 3, 'folder' => 'strings'],
    'Voice' => ['base' => 48, 'octaveCount' => 2, 'folder' => 'voice'],
    'Marimba' => ['base' => 48, 'octaveCount' => 3, 'folder' => 'marimba'],
    'Celesta' => ['base' => 48, 'octaveCount' => 3, 'folder' => 'celesta'],
    'Vibraphone' => ['base' => 48, 'octaveCount' => 3, 'folder' => 'vibraphone'],
    'Saxophone' => ['base' => 48, 'octaveCount' => 3, 'folder' => 'saxophone'],
    'Cello' => ['base' => 36, 'octaveCount' => 3, 'folder' => 'cello'],
    'Harp' => ['base' => 36, 'octaveCount' => 3, 'folder' => 'harp'],
    'Trombone' => ['base' => 48, 'octaveCount' => 2, 'folder' => 'trombone'],
    'Tuba' => ['base' => 24, 'octaveCount' => 2, 'folder' => 'tuba'],
];

$quiz = Quiz::where('title', 'Dual-tonic melodies')->firstOrFail();
$patterns = dualTonicMelodyPatterns();

// Find the pattern objects matching the 4 old labels is not possible since
// labels already changed — instead, match by startDegree, which is stable.
// The 4 fixed patterns are known: 'Do Mi Fa Mi Do' etc. came from specific
// (startDegree, template) combos. We re-derive by re-running the OLD rule
// (no fallback) to find which of the 35 current patterns correspond.
$manifest = [];

foreach (OLD_LABELS as $oldLabel) {
    $rows = $quiz->questions()->where('answer', $oldLabel)->orderBy('position')->get();
    if ($rows->isEmpty()) {
        echo "No rows found for old label '{$oldLabel}' (already updated?).".PHP_EOL;
        continue;
    }

    // Find the new pattern whose label, with the fallback chromatic note
    // removed, equals the old label — i.e. the corresponding fixed pattern.
    $matchedPattern = null;
    foreach ($patterns as $p) {
        $diatonicOnly = implode(' ', array_map(
            fn ($n) => $n['type'] === 'diatonic' ? stepwiseLabelForIndex($n['idx']) : null,
            array_filter($p['notes'], fn ($n) => $n['type'] === 'diatonic')
        ));
        if ($diatonicOnly === $oldLabel) {
            $matchedPattern = $p;
            break;
        }
    }

    if (!$matchedPattern) {
        echo "Could not match old label '{$oldLabel}' to a current pattern.".PHP_EOL;
        continue;
    }

    echo "Fixing '{$oldLabel}' -> '{$matchedPattern['label']}' ({$rows->count()} rows).".PHP_EOL;

    foreach ($rows as $slot => $question) {
        $question->update(['answer' => $matchedPattern['label'], 'choices' => [$matchedPattern['label']]]);

        $audios = QuizQuestionAudio::where('quiz_question_id', $question->id)->get();
        foreach ($audios as $audio) {
            $sound = $audio->sound;
            if (!isset(INSTRUMENTS[$sound])) {
                echo "  unknown instrument '{$sound}', skipping.".PHP_EOL;
                continue;
            }
            $info = INSTRUMENTS[$sound];
            $shiftedNotes = array_map(
                fn ($n) => ['type' => $n['type'], 'idx' => $n['idx'] + $slot * 7],
                $matchedPattern['notes']
            );
            $midiNotes = dualTonicMidiNotes($shiftedNotes, $info['base']);

            $manifest[] = [
                'sound' => $sound,
                'folder' => $info['folder'],
                'audio_path' => $audio->audio_path,
                'notes' => $midiNotes,
            ];
        }
    }
}

file_put_contents(__DIR__.'/fix_dual_tonic_zero_chromatic_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
echo 'Manifest written: '.count($manifest).' audio files to re-render.'.PHP_EOL;
