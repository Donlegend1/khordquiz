<?php

// Sets Reference Audio for "Secondary 11th Chords" General — C-key subset
// of its own 12-key pool. Reuses the SAME existing Level 6 files (from
// both source categories) the questions themselves use, no new rendering.

use App\Models\Quiz;
use Illuminate\Support\Str;

const GENERAL_REF_QUALITIES = [
    'maj9s11' => ['Major 9(♯11)', 'eleventh-degree-chords/level6'],
    'minor11' => ['Minor 11th', 'eleventh-degree-chords/level6'],
    'dim7911' => ['Diminished 7(9,11)', 'eleventh-degree-chords/level6'],
    'dom9s11' => ['Dominant 9(♯11)', 'eleventh-degree-chords/level6'],
    'minor11b5' => ['Minor 11(♭5)', 'eleventh-degree-chords/level6'],
    'dom7b9s11' => ['Dominant 7(♭9,♯11)', 'eleventh-degree-chords/level6'],
    'maj9s5s11' => ['Major 9(♯5,♯11)', 'secondary-eleventh-chords/level6'],
    'dim7b911' => ['Diminished 7(♭9,11)', 'secondary-eleventh-chords/level6'],
    'minmaj11' => ['Minor-Major 11th', 'secondary-eleventh-chords/level6'],
    'aug9s11' => ['Augmented 9(♯11)', 'secondary-eleventh-chords/level6'],
];

const GENERAL_REF_VARIANTS = [
    'tt1' => 'Triad+Triad, 1st Inversion', 'tt2' => 'Triad+Triad, 2nd Inversion',
    'ts1' => 'Triad+7th, 1st Inversion', 'ts2' => 'Triad+7th, 2nd Inversion',
    'st1' => '7th+Triad, 1st Inversion', 'st2' => '7th+Triad, 2nd Inversion',
    'ss1' => '7th+7th, 1st Inversion', 'ss2' => '7th+7th, 2nd Inversion',
];

const GENERAL_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'General')->where('category', 'Secondary 11th Chords')->firstOrFail();

$tracks = [];
foreach (GENERAL_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (GENERAL_REF_QUALITIES as $qualitySlug => [$qualityLabel, $sourcePrefix]) {
        foreach (GENERAL_REF_VARIANTS as $variantSlug => $variantLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "{$sourcePrefix}/{$folderSlug}/c-{$qualitySlug}-{$variantSlug}.wav",
                'label' => $qualityLabel.' · '.$variantLabel,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);
echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
