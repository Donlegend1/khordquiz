<?php

// Sets Reference Audio for all 6 "Secondary 11th Chords" levels. Level 2
// reuses Level 1's tracks; Level 4 reuses Level 3's. Level 5 and 6 each
// build their own (Level 6 is a C-key subset of its own 12-key pool).

use App\Models\Quiz;
use Illuminate\Support\Str;

const SEC11_REF_QUALITIES = [
    'maj9s5s11' => 'Major 9(♯5,♯11)', 'dim7b911' => 'Diminished 7(♭9,11)',
    'minmaj11' => 'Minor-Major 11th', 'aug9s11' => 'Augmented 9(♯11)',
];
const SEC11_REF_L3_VARIANTS = [
    'pAv1' => 'Triad, 1st Inversion', 'pAv2' => 'Triad, 2nd Inversion',
    'pBv1' => '7th Chord, 1st Inversion', 'pBv2' => '7th Chord, 2nd Inversion', 'pBv3' => '7th Chord, 3rd Inversion',
];
const SEC11_REF_L5_VARIANTS = [
    'tt1' => 'Triad+Triad, 1st Inversion', 'tt2' => 'Triad+Triad, 2nd Inversion',
    'ts1' => 'Triad+7th, 1st Inversion', 'ts2' => 'Triad+7th, 2nd Inversion',
    'st1' => '7th+Triad, 1st Inversion', 'st2' => '7th+Triad, 2nd Inversion',
    'ss1' => '7th+7th, 1st Inversion', 'ss2' => '7th+7th, 2nd Inversion',
];
const SEC11_REF_INSTRUMENTS = [
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
    foreach (SEC11_REF_INSTRUMENTS as $sound => $folderSlug) {
        foreach (SEC11_REF_QUALITIES as $qualitySlug => $qualityLabel) {
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

function setVariantReference(string $title, string $pathPrefix, array $variants, bool $keyPrefixed = false): array
{
    $quiz = Quiz::where('title', $title)->firstOrFail();
    $tracks = [];
    foreach (SEC11_REF_INSTRUMENTS as $sound => $folderSlug) {
        foreach (SEC11_REF_QUALITIES as $qualitySlug => $qualityLabel) {
            foreach ($variants as $variantSlug => $variantLabel) {
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

    return $tracks;
}

// Level 1 -> Level 2
$level1Tracks = setFlatReference('Secondary 11th Chords · Level 1', 'secondary-eleventh-chords/level1');
copyReference('Secondary 11th Chords · Level 2', $level1Tracks);

// Level 3 -> Level 4
$level3Tracks = setVariantReference('Secondary 11th Chords · Level 3', 'secondary-eleventh-chords/level3', SEC11_REF_L3_VARIANTS);
copyReference('Secondary 11th Chords · Level 4', $level3Tracks);

// Level 5 — standalone
setVariantReference('Secondary 11th Chords · Level 5', 'secondary-eleventh-chords/level5', SEC11_REF_L5_VARIANTS);

// Level 6 — standalone, C-key subset of its own 12-key pool
setVariantReference('Secondary 11th Chords · Level 6', 'secondary-eleventh-chords/level6', SEC11_REF_L5_VARIANTS, true);
