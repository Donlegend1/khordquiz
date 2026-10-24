<?php

// Sets "Basic Triads · Level 7"'s Reference Audio tracks — same as Level 5
// (same 5 qualities x 3 inversions = 15 grouped clips, "{Quality} · {Inversion}"
// labeled so ReferenceAudioModal auto-groups them), but drop-2 voicing
// instead of close-position — same relationship Level 7's own quiz content
// has to Level 5's. Reuses the quiz's own existing audio files directly (no
// new rendering) — same files basic-triads/level7/{instrument}/
// {quality}-{inversion}.wav the questions themselves already play.

use App\Models\Quiz;
use Illuminate\Support\Str;

const BASIC_TRIADS_L7_REF_QUALITIES = [
    'major' => 'Major', 'minor' => 'Minor', 'diminished' => 'Diminished',
    'augmented' => 'Augmented', 'suspended' => 'Suspended',
];

const BASIC_TRIADS_L7_REF_INVERSIONS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion'];

const BASIC_TRIADS_L7_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Basic Triads · Level 7')->firstOrFail();

$tracks = [];
foreach (BASIC_TRIADS_L7_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (BASIC_TRIADS_L7_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (BASIC_TRIADS_L7_REF_INVERSIONS as $invSlug => $invLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "basic-triads/level7/{$folderSlug}/{$qualitySlug}-{$invSlug}.wav",
                'label' => $qualityLabel.' · '.$invLabel,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);

echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\" across ".count(BASIC_TRIADS_L7_REF_INSTRUMENTS)." instruments x ".(count(BASIC_TRIADS_L7_REF_QUALITIES) * count(BASIC_TRIADS_L7_REF_INVERSIONS))." clips.".PHP_EOL;
