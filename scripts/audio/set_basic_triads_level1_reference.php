<?php

// Sets "Basic Triads · Level 1"'s Reference Audio tracks — the same 6
// root-position-in-C qualities the quiz itself tests (Major, Minor,
// Diminished, Augmented, Sus2, Sus4), across every instrument already built
// for this quiz. Reuses the quiz's own existing audio files directly (no new
// rendering) — same files basic-triads/{instrument}/{quality}.wav the
// questions themselves already play.

use App\Models\Quiz;
use Illuminate\Support\Str;

const BASIC_TRIADS_L1_REF_QUALITIES = [
    'major' => 'Major', 'minor' => 'Minor', 'diminished' => 'Diminished',
    'augmented' => 'Augmented', 'sus2' => 'Sus2', 'sus4' => 'Sus4',
];

const BASIC_TRIADS_L1_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Basic Triads · Level 1')->firstOrFail();

$tracks = [];
foreach (BASIC_TRIADS_L1_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (BASIC_TRIADS_L1_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        $tracks[] = [
            'id' => (string) Str::uuid(),
            'path' => "basic-triads/{$folderSlug}/{$qualitySlug}.wav",
            'label' => $qualityLabel,
            'sound' => $sound,
        ];
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);

echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\" across ".count(BASIC_TRIADS_L1_REF_INSTRUMENTS)." instruments x ".count(BASIC_TRIADS_L1_REF_QUALITIES)." qualities.".PHP_EOL;

// Level 2 (12 keys) reuses these same 6 C-key reference clips per
// instrument — the reference audio demos what each quality sounds like, not
// every key the quiz actually tests, so there's no need for a 72-clip
// (12 keys x 6 qualities) version.
$level2 = Quiz::where('title', 'Basic Triads · Level 2')->first();
if ($level2) {
    $level2->update(['reference_audio_tracks' => $tracks]);
    echo "Also set the same ".count($tracks)." tracks on \"{$level2->title}\".".PHP_EOL;
}
