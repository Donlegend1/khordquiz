<?php

// Sets Reference Audio for all 6 "Secondary 9th Chords" levels. Level 2
// reuses Level 1's tracks (12-key root-position audio is identical for the
// key of C); Level 4 reuses Level 3's. Level 5 and 6 each build their own
// (Level 6 is a C-key subset of its own 12-key pool, since it's a
// different/larger combo set than Level 5).

use App\Models\Quiz;
use Illuminate\Support\Str;

const SEC9_REF_QUALITIES = [
    'maj9s5' => 'Major 9(♯5)', 'dimmaj9' => 'Diminished-Major 9th', 'dom9b5' => 'Dominant 9(♭5)',
    'minmaj9' => 'Minor-Major 9th', 'aug9' => 'Augmented 9th', 'maj9b5' => 'Major 9(♭5)',
];
const SEC9_REF_POSITIONS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];
const SEC9_REF_VARIANTS = [
    'p1v1' => 'Triad, 1st Inversion', 'p1v2' => 'Triad, 2nd Inversion',
    'p2v1' => '7th Chord, 1st Inversion', 'p2v2' => '7th Chord, 2nd Inversion', 'p2v3' => '7th Chord, 3rd Inversion',
];
const SEC9_REF_INSTRUMENTS = [
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
    foreach (SEC9_REF_INSTRUMENTS as $sound => $folderSlug) {
        foreach (SEC9_REF_QUALITIES as $qualitySlug => $qualityLabel) {
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

function copyReference(string $toTitle, array $tracks): void
{
    $quiz = Quiz::where('title', $toTitle)->firstOrFail();
    $copy = array_map(fn ($t) => [...$t, 'id' => (string) Str::uuid()], $tracks);
    $quiz->update(['reference_audio_tracks' => $copy]);
    echo "Copied ".count($copy)." reference tracks to \"{$quiz->title}\".".PHP_EOL;
}

function setLevel3Reference(): void
{
    $quiz = Quiz::where('title', 'Secondary 9th Chords · Level 3')->firstOrFail();
    $tracks = [];
    foreach (SEC9_REF_INSTRUMENTS as $sound => $folderSlug) {
        foreach (SEC9_REF_QUALITIES as $qualitySlug => $qualityLabel) {
            foreach (SEC9_REF_POSITIONS as $posSlug => $posLabel) {
                $tracks[] = [
                    'id' => (string) Str::uuid(),
                    'path' => "secondary-ninth-chords/level3/{$folderSlug}/{$qualitySlug}-{$posSlug}.wav",
                    'label' => $qualityLabel.' · '.$posLabel,
                    'sound' => $sound,
                ];
            }
        }
    }
    $quiz->update(['reference_audio_tracks' => $tracks]);
    echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
}

function setVariantReference(string $title, string $pathPrefix, bool $keyPrefixed = false): void
{
    $quiz = Quiz::where('title', $title)->firstOrFail();
    $tracks = [];
    foreach (SEC9_REF_INSTRUMENTS as $sound => $folderSlug) {
        foreach (SEC9_REF_QUALITIES as $qualitySlug => $qualityLabel) {
            foreach (SEC9_REF_VARIANTS as $variantSlug => $variantLabel) {
                $filename = $keyPrefixed ? "c-{$qualitySlug}-{$variantSlug}.wav" : "{$qualitySlug}-{$variantSlug}.wav";
                $tracks[] = [
                    'id' => (string) Str::uuid(),
                    'path' => "{$pathPrefix}/{$folderSlug}/{$filename}",
                    'label' => $qualityLabel.' · '.$variantLabel,
                    'sound' => $sound,
                ];
            }
        }
    }
    $quiz->update(['reference_audio_tracks' => $tracks]);
    echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\".".PHP_EOL;
}

// Level 1 -> Level 2
$level1Tracks = setFlatReference('Secondary 9th Chords · Level 1', 'secondary-ninth-chords/level1');
copyReference('Secondary 9th Chords · Level 2', $level1Tracks);

// Level 3 -> Level 4 (C-key subset of Level 4's own 12-key pool path, but
// since Level 3 and Level 4's C-key audio are identical, reuse Level 3's
// own (non-key-prefixed) paths directly for both).
setLevel3Reference();
$level3 = Quiz::where('title', 'Secondary 9th Chords · Level 3')->firstOrFail();
copyReference('Secondary 9th Chords · Level 4', $level3->reference_audio_tracks);

// Level 5 — standalone
setVariantReference('Secondary 9th Chords · Level 5', 'secondary-ninth-chords/level5');

// Level 6 — standalone, C-key subset of its own 12-key pool
setVariantReference('Secondary 9th Chords · Level 6', 'secondary-ninth-chords/level6', true);
