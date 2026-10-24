<?php

// Builds "3-Note Stepwise"'s real pool for Piano (C2-C5, 3 octaves) — replacing
// the 25 placeholder rows (all identically "Do Re Mi") with 84 distinct stepwise
// triplets: 7 starting degrees x 4 direction combos (up-up, up-down, down-up,
// down-down) x 3 starting octaves within the range.
//
// Each note is labeled relative to the triplet's own first note (not the
// instrument's absolute octave) — an apostrophe marks a note landing an octave
// above the triplet's start, a comma marks one landing an octave below — same
// convention as the interval quizzes' "Ti - Mi'" labeling, just applied within
// a 3-note window instead of a 2-note pair. Writes a manifest for the bash
// render step (scripts/audio/render_note_midi.py expects raw MIDI numbers, not
// solfège), since this script doesn't render audio itself.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;

const DEGREE_NAMES = ['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti'];
const DEGREE_SLUGS = ['do', 're', 'mi', 'fa', 'so', 'la', 'ti'];
const OFFSETS = [0, 2, 4, 5, 7, 9, 11];
const DIRECTIONS = ['uu' => [1, 1], 'ud' => [1, -1], 'du' => [-1, 1], 'dd' => [-1, -1]];

function midiForIndex(int $idx, int $base): int
{
    $octave = intdiv($idx, 7);
    $degree = $idx % 7;
    if ($degree < 0) {
        $degree += 7;
        $octave--;
    }

    return $base + $octave * 12 + OFFSETS[$degree];
}

function labelForIndex(int $relativeIdx): string
{
    $octaveShift = intdiv($relativeIdx, 7);
    $degree = $relativeIdx % 7;
    if ($degree < 0) {
        $degree += 7;
        $octaveShift--;
    }

    $name = DEGREE_NAMES[$degree];
    if ($octaveShift > 0) return $name.str_repeat("'", $octaveShift);
    if ($octaveShift < 0) return $name.str_repeat(',', abs($octaveShift));

    return $name;
}

function slugForIndex(int $relativeIdx): string
{
    $octaveShift = intdiv($relativeIdx, 7);
    $degree = $relativeIdx % 7;
    if ($degree < 0) {
        $degree += 7;
        $octaveShift--;
    }

    $slug = DEGREE_SLUGS[$degree];
    if ($octaveShift > 0) return $slug.str_repeat('h', $octaveShift); // h = "higher"
    if ($octaveShift < 0) return $slug.str_repeat('l', abs($octaveShift)); // l = "lower"

    return $slug;
}

$base = 36; // C2
$octaveStarts = [0, 1, 2]; // C2, C3, C4 — the three starting octaves within C2-C5

$quiz = Quiz::where('title', '3-Note Stepwise')->firstOrFail();

// Safety guard: this is the ONE-TIME pool creator. Every other instrument
// (build_three_note_stepwise_instrument.php) attaches to these same 84 rows —
// rerunning this would cascade-delete every instrument's already-attached
// audio, not just Piano's.
if ($quiz->questions()->count() > 0) {
    echo 'Pool already exists ('.$quiz->questions()->count()." rows) — refusing to recreate it. Use build_three_note_stepwise_instrument.php for other instruments instead.".PHP_EOL;
    return;
}

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();

$manifest = [];
$created = 0;
$position = 0;

foreach (range(0, 6) as $startDegree) {
    foreach (DIRECTIONS as $dirSlug => [$d1, $d2]) {
        // Relative to the triplet's own first note, not the instrument's absolute
        // octave — labelForIndex/slugForIndex take startDegree + r (so the degree
        // NAME reflects which degree this pattern actually starts on — "Re Mi Fa",
        // not always "Do Re Mi") and mark octave crossings off that local start
        // (startDegree itself never carries a shift marker, only the 2nd/3rd notes
        // can, exactly when they cross a degree-0/degree-6 boundary).
        $relativeIndices = [0, $d1, $d1 + $d2];
        $labelIndices = array_map(fn ($r) => $startDegree + $r, $relativeIndices);
        $label = implode(' ', array_map('labelForIndex', $labelIndices));
        $fileSlug = implode('-', array_map('slugForIndex', $labelIndices));

        foreach ($octaveStarts as $octaveStart) {
            $absoluteStart = $octaveStart * 7 + $startDegree;
            $midiNotes = array_map(fn ($r) => midiForIndex($absoluteStart + $r, $base), $relativeIndices);

            $position++;
            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Tap the notes in the order you hear them.',
                'choices' => [$label],
                'answer' => $label,
            ]);

            $manifest[] = [
                'question_id' => $question->id,
                'file_slug' => "{$fileSlug}-o{$octaveStart}",
                'notes' => $midiNotes,
            ];
            $created++;
        }
    }
}

file_put_contents(__DIR__.'/three_note_stepwise_piano_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
echo "Created {$created} pool rows. Manifest written.".PHP_EOL;
