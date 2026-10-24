<?php

// Sets Reference Audio for "Chord Tone Recognition #1" — C-key subset of
// its own 12-key pool. Reuses the quiz's own existing audio files directly,
// no new rendering.

use App\Models\Quiz;
use Illuminate\Support\Str;

const CTR1_REF_QUALITIES = [
    'diminished7' => 'Diminished 7th', 'dominant7' => 'Dominant 7th', 'm7b5' => 'Minor 7(♭5)',
    'major7' => 'Major 7th', 'minor7' => 'Minor 7th',
    'aug7' => 'Augmented 7th', 'minmaj7' => 'Minor-Major 7th', 'dimmaj7' => 'Diminished-Major 7th',
    'dom7b5' => 'Dominant 7(♭5)', 'maj7s5' => 'Major 7(♯5)', 'maj7b5' => 'Major 7(♭5)',
];
const CTR1_REF_DEGREES = ['1', '3', '5', '7'];
const CTR1_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Chord Tone Recognition #1')->firstOrFail();

$tracks = [];
foreach (CTR1_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (CTR1_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (CTR1_REF_DEGREES as $degree) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "chord-tone-recognition-1/{$folderSlug}/c-{$qualitySlug}-{$degree}.wav",
                'label' => $qualityLabel.' · Tone '.$degree,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);
echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
