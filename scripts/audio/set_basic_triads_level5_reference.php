<?php

// Sets "Basic Triads · Level 5"'s Reference Audio tracks — key of C, all 5
// qualities x all 3 inversions (Root Position, 1st Inversion, 2nd Inversion)
// = 15 clips per instrument, matching the quiz's own 15 rows exactly. Uses
// the existing single-track Reference Audio player (Next/Previous through
// every clip) rather than one player per clip, so 15 clips per instrument
// stay navigable instead of cluttering the page. Reuses the quiz's own
// existing audio files directly (no new rendering) — same files
// basic-triads/level5/{instrument}/{quality}-{inversion}.wav the questions
// themselves already play.

use App\Models\Quiz;
use Illuminate\Support\Str;

const BASIC_TRIADS_L5_REF_QUALITIES = [
    'major' => 'Major', 'minor' => 'Minor', 'diminished' => 'Diminished',
    'augmented' => 'Augmented', 'suspended' => 'Suspended',
];

const BASIC_TRIADS_L5_REF_INVERSIONS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion'];

const BASIC_TRIADS_L5_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Basic Triads · Level 5')->firstOrFail();

$tracks = [];
foreach (BASIC_TRIADS_L5_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (BASIC_TRIADS_L5_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (BASIC_TRIADS_L5_REF_INVERSIONS as $invSlug => $invLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "basic-triads/level5/{$folderSlug}/{$qualitySlug}-{$invSlug}.wav",
                'label' => $qualityLabel.' · '.$invLabel,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);

echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\" across ".count(BASIC_TRIADS_L5_REF_INSTRUMENTS)." instruments x ".(count(BASIC_TRIADS_L5_REF_QUALITIES) * count(BASIC_TRIADS_L5_REF_INVERSIONS))." clips.".PHP_EOL;
