<?php

// Sets Reference Audio for "Secondary 7th Chords · Level 9" (General) — C-
// key subset of its own 12-key pool (all 11 qualities x 4 positions),
// same convention as every other 12-key compound level (6/8 in both source
// categories): reuses the quiz's own existing audio files directly, no new
// rendering. Grouped "{Quality} · {Position}" labels so
// ReferenceAudioModal (QuizCategory.jsx) auto-groups them.

use App\Models\Quiz;
use Illuminate\Support\Str;

const SEC7_L9_REF_QUALITIES = [
    'diminished7' => 'Diminished 7th', 'dominant7' => 'Dominant 7th', 'm7b5' => 'Minor 7(♭5)',
    'major7' => 'Major 7th', 'minor7' => 'Minor 7th',
    'aug7' => 'Augmented 7th', 'minmaj7' => 'Minor-Major 7th', 'dimmaj7' => 'Diminished-Major 7th',
    'dom7b5' => 'Dominant 7(♭5)', 'maj7s5' => 'Major 7(♯5)', 'maj7b5' => 'Major 7(♭5)',
];

const SEC7_L9_REF_POSITIONS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];

const SEC7_L9_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'General')->where('category', 'Secondary 7th Chords')->firstOrFail();

$tracks = [];
foreach (SEC7_L9_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (SEC7_L9_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (SEC7_L9_REF_POSITIONS as $posSlug => $posLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "secondary-seventh-chords/level9/{$folderSlug}/c-{$qualitySlug}-{$posSlug}.wav",
                'label' => $qualityLabel.' · '.$posLabel,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);
echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
