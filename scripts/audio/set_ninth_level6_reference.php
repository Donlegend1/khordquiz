<?php

// Sets Reference Audio for "9th Degree Chords · Level 6" — C-key subset of
// its own 12-key pool. Reuses the quiz's own existing audio files directly,
// no new rendering.

use App\Models\Quiz;
use Illuminate\Support\Str;

const NINTH_L6_REF_QUALITIES = [
    'major9' => 'Major 9th', 'minor9' => 'Minor 9th', 'dim7add9' => 'Diminished 7(9)',
    'dominant9' => 'Dominant 9th', 'minor9b5' => 'Minor 9(♭5)', 'dom7b9' => 'Dominant 7(♭9)',
];
const NINTH_L6_REF_VARIANTS = [
    'p1v1' => 'Triad, 1st Inversion', 'p1v2' => 'Triad, 2nd Inversion',
    'p2v1' => '7th Chord, 1st Inversion', 'p2v2' => '7th Chord, 2nd Inversion', 'p2v3' => '7th Chord, 3rd Inversion',
];
const NINTH_L6_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '9th Degree Chords · Level 6')->firstOrFail();

$tracks = [];
foreach (NINTH_L6_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (NINTH_L6_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (NINTH_L6_REF_VARIANTS as $variantSlug => $variantLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "ninth-degree-chords/level6/{$folderSlug}/c-{$qualitySlug}-{$variantSlug}.wav",
                'label' => $qualityLabel.' · '.$variantLabel,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);
echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
