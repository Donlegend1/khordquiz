<?php

// Sets Reference Audio for "Secondary 9th Chords" General — C-key subset of
// its own 12-key pool (12 qualities x 5 variants). Reuses the quiz's own
// existing audio files directly, no new rendering.

use App\Models\Quiz;
use Illuminate\Support\Str;

const GENERAL_REF_QUALITY_SLUGS = [
    'major9' => 'Major 9th', 'minor9' => 'Minor 9th', 'dim7add9' => 'Diminished 7(9)',
    'dominant9' => 'Dominant 9th', 'minor9b5' => 'Minor 9(♭5)', 'dom7b9' => 'Dominant 7(♭9)',
    'maj9s5' => 'Major 9(♯5)', 'dimmaj9' => 'Diminished-Major 9th', 'dom9b5' => 'Dominant 9(♭5)',
    'minmaj9' => 'Minor-Major 9th', 'aug9' => 'Augmented 9th', 'maj9b5' => 'Major 9(♭5)',
];
const GENERAL_REF_VARIANTS = [
    'p1v1' => 'Triad, 1st Inversion', 'p1v2' => 'Triad, 2nd Inversion',
    'p2v1' => '7th Chord, 1st Inversion', 'p2v2' => '7th Chord, 2nd Inversion', 'p2v3' => '7th Chord, 3rd Inversion',
];
const GENERAL_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'General')->where('category', 'Secondary 9th Chords')->firstOrFail();

$tracks = [];
foreach (GENERAL_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (GENERAL_REF_QUALITY_SLUGS as $qualitySlug => $qualityLabel) {
        foreach (GENERAL_REF_VARIANTS as $variantSlug => $variantLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "secondary-ninth-chords/general/{$folderSlug}/c-{$qualitySlug}-{$variantSlug}.wav",
                'label' => $qualityLabel.' · '.$variantLabel,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);
echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
