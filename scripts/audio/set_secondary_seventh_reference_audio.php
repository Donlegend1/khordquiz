<?php

// Sets Reference Audio for all 8 "Secondary 7th Chords" levels, same
// convention as Basic Triads / 7th Degree Chords: Level 2 (12 keys, root
// only) reuses Level 1's tracks directly since the key-of-C audio is
// identical; Level 4 reuses Level 3's. Levels 5-8 each build their own
// (different quality sets between 5/7 vs 6/8, and different registers
// between close/drop-2). Compound levels use "{Quality} · {Position}"
// labels so ReferenceAudioModal (QuizCategory.jsx) auto-groups them.

use App\Models\Quiz;
use Illuminate\Support\Str;

const SEC7_REF_QUALITIES = [
    'aug7' => 'Augmented 7th', 'minmaj7' => 'Minor-Major 7th', 'dimmaj7' => 'Diminished-Major 7th',
    'dom7b5' => 'Dominant 7(♭5)', 'maj7s5' => 'Major 7(♯5)', 'maj7b5' => 'Major 7(♭5)',
];
const SEC7_REF_QUALITIES_NO_SYMMETRIC = [
    'aug7' => 'Augmented 7th', 'minmaj7' => 'Minor-Major 7th', 'dimmaj7' => 'Diminished-Major 7th',
    'maj7s5' => 'Major 7(♯5)', 'maj7b5' => 'Major 7(♭5)',
];
const SEC7_REF_POSITIONS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];
const SEC7_REF_INSTRUMENTS = [
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
    foreach (SEC7_REF_INSTRUMENTS as $sound => $folderSlug) {
        foreach (SEC7_REF_QUALITIES as $qualitySlug => $qualityLabel) {
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
    foreach (SEC7_REF_INSTRUMENTS as $sound => $folderSlug) {
        foreach ($qualities as $qualitySlug => $qualityLabel) {
            foreach (SEC7_REF_POSITIONS as $posSlug => $posLabel) {
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
$level1Tracks = setFlatReference('Secondary 7th Chords · Level 1', 'secondary-seventh-chords/level1');
copyReference('Secondary 7th Chords · Level 2', $level1Tracks);

// Level 3 (C, drop-2, root only) -> Level 4 (12 keys, drop-2, root only)
$level3Tracks = setFlatReference('Secondary 7th Chords · Level 3', 'secondary-seventh-chords/level3');
copyReference('Secondary 7th Chords · Level 4', $level3Tracks);

// Level 5 (C, close, all inversions, 6 qualities) — standalone
setCompoundReference('Secondary 7th Chords · Level 5', 'secondary-seventh-chords/level5', SEC7_REF_QUALITIES);

// Level 6 (12 keys, close, all inversions, 5 qualities) — standalone, C-key subset
setCompoundReference('Secondary 7th Chords · Level 6', 'secondary-seventh-chords/level6', SEC7_REF_QUALITIES_NO_SYMMETRIC, true);

// Level 7 (C, drop-2, all inversions, 6 qualities) — standalone
setCompoundReference('Secondary 7th Chords · Level 7', 'secondary-seventh-chords/level7', SEC7_REF_QUALITIES);

// Level 8 (12 keys, drop-2, all inversions, 5 qualities) — standalone, C-key subset
setCompoundReference('Secondary 7th Chords · Level 8', 'secondary-seventh-chords/level8', SEC7_REF_QUALITIES_NO_SYMMETRIC, true);
