<?php

// Sets Reference Audio for "11th Degree Chords · Level 4" — C-key subset of
// its own 12-key pool. Reuses the quiz's own existing audio files directly,
// no new rendering.

use App\Models\Quiz;
use Illuminate\Support\Str;

const ELEVENTH_L4_REF_QUALITIES = [
    'maj9s11' => 'Major 9(♯11)', 'minor11' => 'Minor 11th', 'dim7911' => 'Diminished 7(9,11)',
    'dom9s11' => 'Dominant 9(♯11)', 'minor11b5' => 'Minor 11(♭5)', 'dom7b9s11' => 'Dominant 7(♭9,♯11)',
];
const ELEVENTH_L4_REF_VARIANTS = [
    'pAv1' => 'Triad, 1st Inversion', 'pAv2' => 'Triad, 2nd Inversion',
    'pBv1' => '7th Chord, 1st Inversion', 'pBv2' => '7th Chord, 2nd Inversion', 'pBv3' => '7th Chord, 3rd Inversion',
];
const ELEVENTH_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '11th Degree Chords · Level 4')->firstOrFail();

$tracks = [];
foreach (ELEVENTH_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (ELEVENTH_L4_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (ELEVENTH_L4_REF_VARIANTS as $variantSlug => $variantLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "eleventh-degree-chords/level4/{$folderSlug}/c-{$qualitySlug}-{$variantSlug}.wav",
                'label' => $qualityLabel.' · '.$variantLabel,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);
echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
