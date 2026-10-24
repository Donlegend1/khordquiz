<?php

// Sets Reference Audio for "11th Degree Chords · Level 6" — C-key subset of
// its own 12-key pool. Reuses the quiz's own existing audio files directly,
// no new rendering.

use App\Models\Quiz;
use Illuminate\Support\Str;

const ELEVENTH_L6_REF_QUALITIES = [
    'maj9s11' => 'Major 9(♯11)', 'minor11' => 'Minor 11th', 'dim7911' => 'Diminished 7(9,11)',
    'dom9s11' => 'Dominant 9(♯11)', 'minor11b5' => 'Minor 11(♭5)', 'dom7b9s11' => 'Dominant 7(♭9,♯11)',
];
const ELEVENTH_L6_REF_VARIANTS = [
    'tt1' => 'Triad+Triad, 1st Inversion', 'tt2' => 'Triad+Triad, 2nd Inversion',
    'ts1' => 'Triad+7th, 1st Inversion', 'ts2' => 'Triad+7th, 2nd Inversion',
    'st1' => '7th+Triad, 1st Inversion', 'st2' => '7th+Triad, 2nd Inversion',
    'ss1' => '7th+7th, 1st Inversion', 'ss2' => '7th+7th, 2nd Inversion',
];
const ELEVENTH_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '11th Degree Chords · Level 6')->firstOrFail();

$tracks = [];
foreach (ELEVENTH_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (ELEVENTH_L6_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (ELEVENTH_L6_REF_VARIANTS as $variantSlug => $variantLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "eleventh-degree-chords/level6/{$folderSlug}/c-{$qualitySlug}-{$variantSlug}.wav",
                'label' => $qualityLabel.' · '.$variantLabel,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);
echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
