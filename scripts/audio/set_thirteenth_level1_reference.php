<?php

// Sets Reference Audio for "13th Degree Chords · Level 1" — 4 qualities
// across all 16 instruments. Reuses the quiz's own existing audio files
// directly, no new rendering.

use App\Models\Quiz;
use Illuminate\Support\Str;

const THIRTEENTH_L1_REF_QUALITIES = [
    'maj13s11' => 'Major 13(♯11)', 'minor13' => 'Minor 13th',
    'dom13s11' => 'Dominant 13(♯11)', 'dom13b9s11' => 'Dominant 13(♭9,♯11)',
];
const THIRTEENTH_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '13th Degree Chords · Level 1')->firstOrFail();

$tracks = [];
foreach (THIRTEENTH_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (THIRTEENTH_L1_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        $tracks[] = [
            'id' => (string) Str::uuid(),
            'path' => "thirteenth-degree-chords/level1/{$folderSlug}/{$qualitySlug}.wav",
            'label' => $qualityLabel,
            'sound' => $sound,
        ];
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);
echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
