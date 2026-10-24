<?php

// Sets Reference Audio for 9th Degree Chords Levels 1-2 — Level 2 (12 keys,
// root only) reuses Level 1's tracks directly since the key-of-C audio is
// identical, same convention as every other category this session.

use App\Models\Quiz;
use Illuminate\Support\Str;

const NINTH_REF_QUALITIES = [
    'major9' => 'Major 9th', 'minor9' => 'Minor 9th', 'dim7add9' => 'Diminished 7(9)',
    'dominant9' => 'Dominant 9th', 'minor9b5' => 'Minor 9(♭5)', 'dom7b9' => 'Dominant 7(♭9)',
];
const NINTH_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '9th Degree Chords · Level 1')->firstOrFail();

$tracks = [];
foreach (NINTH_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (NINTH_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        $tracks[] = [
            'id' => (string) Str::uuid(),
            'path' => "ninth-degree-chords/level1/{$folderSlug}/{$qualitySlug}.wav",
            'label' => $qualityLabel,
            'sound' => $sound,
        ];
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);
echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;

$level2 = Quiz::where('title', '9th Degree Chords · Level 2')->firstOrFail();
$copy = array_map(fn ($t) => [...$t, 'id' => (string) Str::uuid()], $tracks);
$level2->update(['reference_audio_tracks' => $copy]);
echo "Copied ".count($copy)." reference tracks to \"{$level2->title}\".".PHP_EOL;
