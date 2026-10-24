<?php

// Sets Reference Audio for all 8 "7th Degree Chords" levels, same
// convention as Basic Triads: Level 2 (12 keys, root only) reuses Level 1's
// tracks directly since the key-of-C audio is identical; Level 4 reuses
// Level 3's. Levels 5-8 each build their own (different quality sets
// between 5/7 vs 6/8, and different registers between close/drop-2, so none
// of them can reuse another's files). Compound levels use
// "{Quality} · {Position}" labels so ReferenceAudioModal (QuizCategory.jsx)
// auto-groups them, same as Basic Triads Levels 5-8.

use App\Models\Quiz;
use Illuminate\Support\Str;

const SEVENTH_REF_QUALITIES = ['diminished7' => 'Diminished 7th', 'dominant7' => 'Dominant 7th', 'm7b5' => 'Minor 7(♭5)', 'major7' => 'Major 7th', 'minor7' => 'Minor 7th'];
const SEVENTH_REF_QUALITIES_NO_SYMMETRIC = ['dominant7' => 'Dominant 7th', 'm7b5' => 'Minor 7(♭5)', 'major7' => 'Major 7th', 'minor7' => 'Minor 7th'];
const SEVENTH_REF_POSITIONS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];
const SEVENTH_REF_INSTRUMENTS = [
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
    foreach (SEVENTH_REF_INSTRUMENTS as $sound => $folderSlug) {
        foreach (SEVENTH_REF_QUALITIES as $qualitySlug => $qualityLabel) {
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

function setCompoundReference(string $title, string $pathPrefix, array $qualities, bool $keyPrefixed = false): void
{
    $quiz = Quiz::where('title', $title)->firstOrFail();

    $tracks = [];
    foreach (SEVENTH_REF_INSTRUMENTS as $sound => $folderSlug) {
        foreach ($qualities as $qualitySlug => $qualityLabel) {
            foreach (SEVENTH_REF_POSITIONS as $posSlug => $posLabel) {
                $filename = $keyPrefixed ? "c-{$qualitySlug}-{$posSlug}.wav" : "{$qualitySlug}-{$posSlug}.wav";
                $tracks[] = [
                    'id' => (string) Str::uuid(),
                    'path' => "{$pathPrefix}/{$folderSlug}/{$filename}",
                    'label' => $qualityLabel.' · '.$posLabel,
                    'sound' => $sound,
                ];
            }
        }
    }

    $quiz->update(['reference_audio_tracks' => $tracks]);
    echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
}

function copyReference(string $toTitle, array $tracks): void
{
    $quiz = Quiz::where('title', $toTitle)->firstOrFail();
    $copy = array_map(fn ($t) => [...$t, 'id' => (string) Str::uuid()], $tracks);
    $quiz->update(['reference_audio_tracks' => $copy]);
    echo "Copied ".count($copy)." reference tracks to \"{$quiz->title}\".".PHP_EOL;
}

// Level 1 (C, close, root only) -> Level 2 (12 keys, close, root only)
$level1Tracks = setFlatReference('7th Degree Chords · Level 1', 'seventh-degree-chords/level1');
copyReference('7th Degree Chords · Level 2', $level1Tracks);

// Level 3 (C, drop-2, root only) -> Level 4 (12 keys, drop-2, root only)
$level3Tracks = setFlatReference('7th Degree Chords · Level 3', 'seventh-degree-chords/level3');
copyReference('7th Degree Chords · Level 4', $level3Tracks);

// Level 5 (C, close, all inversions, 5 qualities) — standalone
setCompoundReference('7th Degree Chords · Level 5', 'seventh-degree-chords/level5', SEVENTH_REF_QUALITIES);

// Level 6 (12 keys, close, all inversions, 4 qualities) — standalone, C-key
// subset of its own 12-key pool (paths are key-prefixed).
setCompoundReference('7th Degree Chords · Level 6', 'seventh-degree-chords/level6', SEVENTH_REF_QUALITIES_NO_SYMMETRIC, true);

// Level 7 (C, drop-2, all inversions, 5 qualities) — standalone
setCompoundReference('7th Degree Chords · Level 7', 'seventh-degree-chords/level7', SEVENTH_REF_QUALITIES);

// Level 8 (12 keys, drop-2, all inversions, 4 qualities) — standalone, C-key
// subset of its own 12-key pool (paths are key-prefixed).
setCompoundReference('7th Degree Chords · Level 8', 'seventh-degree-chords/level8', SEVENTH_REF_QUALITIES_NO_SYMMETRIC, true);
