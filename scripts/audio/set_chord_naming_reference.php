<?php

// Sets Reference Audio for "Chord naming" — the bare (no degrees, no
// exclude) voicing for each of the 5 qualities, one per instrument, reused
// directly from the quiz's own real audio (no new rendering).

use App\Models\Quiz;
use Illuminate\Support\Str;

const CHORD_NAMING_REF_QUALITIES = ['major', 'minor', 'augmented', 'diminished', 'dominant'];
const CHORD_NAMING_REF_LABELS = [
    'major' => 'Major', 'minor' => 'Minor', 'augmented' => 'Augmented',
    'diminished' => 'Diminished', 'dominant' => 'Dominant',
];
const CHORD_NAMING_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Chord naming')->firstOrFail();

$tracks = [];
foreach (CHORD_NAMING_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (CHORD_NAMING_REF_QUALITIES as $qualitySlug) {
        $tracks[] = [
            'id' => (string) Str::uuid(),
            'path' => "extensions/chord-naming/{$folderSlug}/{$qualitySlug}-bare-none-0.wav",
            'label' => CHORD_NAMING_REF_LABELS[$qualitySlug],
            'sound' => $sound,
        ];
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);
echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
