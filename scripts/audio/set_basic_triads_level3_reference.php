<?php

// Sets "Basic Triads · Level 3"'s Reference Audio tracks — drop-2 voicing,
// key of C, 5 qualities per the explicit list given (Major, Minor,
// Augmented, Diminished, Sus2 — Sus4 deliberately left out this time, unlike
// Level 1/2's reference set). Reuses the quiz's own existing audio files
// directly (no new rendering) — same files basic-triads/level3/{instrument}/
// {quality}.wav the questions themselves already play.

use App\Models\Quiz;
use Illuminate\Support\Str;

const BASIC_TRIADS_L3_REF_QUALITIES = [
    'major' => 'Major', 'minor' => 'Minor', 'augmented' => 'Augmented',
    'diminished' => 'Diminished', 'sus2' => 'Sus2',
];

const BASIC_TRIADS_L3_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Basic Triads · Level 3')->firstOrFail();

$tracks = [];
foreach (BASIC_TRIADS_L3_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (BASIC_TRIADS_L3_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        $tracks[] = [
            'id' => (string) Str::uuid(),
            'path' => "basic-triads/level3/{$folderSlug}/{$qualitySlug}.wav",
            'label' => $qualityLabel,
            'sound' => $sound,
        ];
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);

echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\" across ".count(BASIC_TRIADS_L3_REF_INSTRUMENTS)." instruments x ".count(BASIC_TRIADS_L3_REF_QUALITIES)." qualities.".PHP_EOL;

// Level 4 (12 keys, drop-2) reuses these same 5 C-key reference clips per
// instrument, same reasoning as Level 2 reusing Level 1's — the reference
// audio demos what each quality sounds like, not every key the quiz tests.
$level4 = Quiz::where('title', 'Basic Triads · Level 4')->first();
if ($level4) {
    $level4->update(['reference_audio_tracks' => $tracks]);
    echo "Also set the same ".count($tracks)." tracks on \"{$level4->title}\".".PHP_EOL;
}
