<?php

// Sets "Basic Triads · Level 8"'s Reference Audio tracks — same as Level 7
// (grouped "{Quality} · {Inversion}" clips, drop-2 voicing), but Major/Minor/
// Diminished only — Augmented and Sus2 left out, matching Level 8's own quiz
// content (same symmetric-chord ambiguity reasoning documented on
// build_basic_triads_level8.php) — and key of C only (same convention as
// Level 6 reusing its C-key subset — the reference demos the quality/
// inversion sound, not every one of the 12 keys the quiz tests). Reuses the
// quiz's own existing C-key audio files directly (no new rendering).

use App\Models\Quiz;
use Illuminate\Support\Str;

const BASIC_TRIADS_L8_REF_QUALITIES = ['major' => 'Major', 'minor' => 'Minor', 'diminished' => 'Diminished'];

const BASIC_TRIADS_L8_REF_INVERSIONS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion'];

const BASIC_TRIADS_L8_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Basic Triads · Level 8')->firstOrFail();

$tracks = [];
foreach (BASIC_TRIADS_L8_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (BASIC_TRIADS_L8_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (BASIC_TRIADS_L8_REF_INVERSIONS as $invSlug => $invLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "basic-triads/level8/{$folderSlug}/c-{$qualitySlug}-{$invSlug}.wav",
                'label' => $qualityLabel.' · '.$invLabel,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);

echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\" across ".count(BASIC_TRIADS_L8_REF_INSTRUMENTS)." instruments x ".(count(BASIC_TRIADS_L8_REF_QUALITIES) * count(BASIC_TRIADS_L8_REF_INVERSIONS))." clips.".PHP_EOL;
