<?php

// Sets Reference Audio for "13th Degree Chords · Level 3" — 4 qualities x
// 2 variants across all 16 instruments. Reuses the quiz's own existing
// audio files directly, no new rendering.

use App\Models\Quiz;
use Illuminate\Support\Str;

const THIRTEENTH_L3_REF_QUALITIES = [
    'maj13s11' => 'Major 13(♯11)', 'minor13' => 'Minor 13th',
    'dom13s11' => 'Dominant 13(♯11)', 'dom13b9s11' => 'Dominant 13(♭9,♯11)',
];
const THIRTEENTH_L3_REF_VARIANTS = ['v1' => '1st Inversion', 'v2' => '2nd Inversion'];
const THIRTEENTH_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '13th Degree Chords · Level 3')->firstOrFail();

$tracks = [];
foreach (THIRTEENTH_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (THIRTEENTH_L3_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (THIRTEENTH_L3_REF_VARIANTS as $variantSlug => $variantLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "thirteenth-degree-chords/level3/{$folderSlug}/{$qualitySlug}-{$variantSlug}.wav",
                'label' => $qualityLabel.' · '.$variantLabel,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);
echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
