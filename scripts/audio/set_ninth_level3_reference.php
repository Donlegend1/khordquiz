<?php

// Sets Reference Audio for "9th Degree Chords · Level 3" — grouped
// "{Quality} · {Position}" labels so ReferenceAudioModal (QuizCategory.jsx)
// auto-groups them (even though the quiz's own answer is flat quality-only
// — this is just a demo convenience showing the 4 upper-structure
// voicings per quality). Reuses the quiz's own existing audio files
// directly, no new rendering.

use App\Models\Quiz;
use Illuminate\Support\Str;

const NINTH_L3_REF_QUALITIES = [
    'major9' => 'Major 9th', 'minor9' => 'Minor 9th', 'dim7add9' => 'Diminished 7(9)',
    'dominant9' => 'Dominant 9th', 'minor9b5' => 'Minor 9(♭5)', 'dom7b9' => 'Dominant 7(♭9)',
];
const NINTH_L3_REF_POSITIONS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];
const NINTH_L3_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '9th Degree Chords · Level 3')->firstOrFail();

$tracks = [];
foreach (NINTH_L3_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (NINTH_L3_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (NINTH_L3_REF_POSITIONS as $posSlug => $posLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "ninth-degree-chords/level3/{$folderSlug}/{$qualitySlug}-{$posSlug}.wav",
                'label' => $qualityLabel.' · '.$posLabel,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);
echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
