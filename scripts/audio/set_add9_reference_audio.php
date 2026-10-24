<?php

// Sets Reference Audio for all 8 "Add 9" levels, same convention as Basic
// Triads: a 12-key level reuses its single-key sibling's reference tracks
// directly (same file paths) rather than duplicating audio, since both
// levels share the exact same register/voicing for the key of C — the
// reference demos what a quality (and position, where applicable) sounds
// like, not every one of the 12 keys a quiz tests. Unlike Basic Triads
// Levels 6/8, Add 9's quality set never changes between sibling levels, so
// every 12-key level can reuse its sibling's tracks (Level 1 -> 2, 3 -> 4,
// 5 -> 6, 7 -> 8), not just the flat ones.
//
// Compound (quality+position) levels use "{Quality} · {Position}" labels so
// ReferenceAudioModal (QuizCategory.jsx) auto-groups them, same as Basic
// Triads Levels 5-8.

use App\Models\Quiz;
use Illuminate\Support\Str;

const ADD9_REF_QUALITIES = ['major' => 'Major add 9', 'minor' => 'Minor add 9', 'diminished' => 'Diminished add 9', 'augmented' => 'Augmented add 9'];
const ADD9_REF_POSITIONS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];
const ADD9_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

function setFlatReference(string $title, string $pathPrefix): array
{
    $quiz = Quiz::where('title', $title)->firstOrFail();

    $tracks = [];
    foreach (ADD9_REF_INSTRUMENTS as $sound => $folderSlug) {
        foreach (ADD9_REF_QUALITIES as $qualitySlug => $qualityLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "{$pathPrefix}/{$folderSlug}/{$qualitySlug}.wav",
                'label' => $qualityLabel,
                'sound' => $sound,
            ];
        }
    }

    $quiz->update(['reference_audio_tracks' => $tracks]);
    echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;

    return $tracks;
}

function setCompoundReference(string $title, string $pathPrefix): array
{
    $quiz = Quiz::where('title', $title)->firstOrFail();

    $tracks = [];
    foreach (ADD9_REF_INSTRUMENTS as $sound => $folderSlug) {
        foreach (ADD9_REF_QUALITIES as $qualitySlug => $qualityLabel) {
            foreach (ADD9_REF_POSITIONS as $posSlug => $posLabel) {
                $tracks[] = [
                    'id' => (string) Str::uuid(),
                    'path' => "{$pathPrefix}/{$folderSlug}/{$qualitySlug}-{$posSlug}.wav",
                    'label' => $qualityLabel.' · '.$posLabel,
                    'sound' => $sound,
                ];
            }
        }
    }

    $quiz->update(['reference_audio_tracks' => $tracks]);
    echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;

    return $tracks;
}

function copyReference(string $toTitle, array $tracks): void
{
    $quiz = Quiz::where('title', $toTitle)->firstOrFail();
    // Fresh UUIDs so each quiz's tracks are independently identifiable.
    $copy = array_map(fn ($t) => [...$t, 'id' => (string) Str::uuid()], $tracks);
    $quiz->update(['reference_audio_tracks' => $copy]);
    echo "Copied ".count($copy)." reference tracks to \"{$quiz->title}\".".PHP_EOL;
}

// Level 1 (C, close, root only) -> Level 2 (12 keys, close, root only)
$level1Tracks = setFlatReference('Add 9 · Level 1', 'add9');
copyReference('Add 9 · Level 2', $level1Tracks);

// Level 3 (C, close, all positions) -> Level 4 (12 keys, close, all positions)
$level3Tracks = setCompoundReference('Add 9 · Level 3', 'add9/level3');
copyReference('Add 9 · Level 4', $level3Tracks);

// Level 5 (C, drop-2, root only) -> Level 6 (12 keys, drop-2, root only)
$level5Tracks = setFlatReference('Add 9 · Level 5', 'add9/level5');
copyReference('Add 9 · Level 6', $level5Tracks);

// Level 7 (C, drop-2, all positions) -> Level 8 (12 keys, drop-2, all positions)
$level7Tracks = setCompoundReference('Add 9 · Level 7', 'add9/level7');
copyReference('Add 9 · Level 8', $level7Tracks);
