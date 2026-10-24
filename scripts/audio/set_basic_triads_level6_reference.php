<?php

// Sets "Basic Triads · Level 6"'s Reference Audio tracks — same grouped
// quality+inversion shape as Level 5 (ReferenceAudioModal in QuizCategory.jsx
// auto-groups any "{Quality} · {Inversion}" labeled set), key of C only
// (same convention as Level 2 reusing Level 1's reference and Level 4
// reusing Level 3's — the reference demos what each quality/inversion
// sounds like, not every one of the 12 keys the quiz tests), but only
// Major/Minor/Diminished — Augmented and Sus2 are left out here, matching
// Level 6's own quiz content (excluded for the same symmetric-chord
// ambiguity reasoning documented on build_basic_triads_level6.php). Reuses
// the quiz's own existing C-key audio files directly (no new rendering).

use App\Models\Quiz;
use Illuminate\Support\Str;

const BASIC_TRIADS_L6_REF_QUALITIES = ['major' => 'Major', 'minor' => 'Minor', 'diminished' => 'Diminished'];

const BASIC_TRIADS_L6_REF_INVERSIONS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion'];

const BASIC_TRIADS_L6_REF_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Basic Triads · Level 6')->firstOrFail();

$tracks = [];
foreach (BASIC_TRIADS_L6_REF_INSTRUMENTS as $sound => $folderSlug) {
    foreach (BASIC_TRIADS_L6_REF_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (BASIC_TRIADS_L6_REF_INVERSIONS as $invSlug => $invLabel) {
            $tracks[] = [
                'id' => (string) Str::uuid(),
                'path' => "basic-triads/level6/{$folderSlug}/c-{$qualitySlug}-{$invSlug}.wav",
                'label' => $qualityLabel.' · '.$invLabel,
                'sound' => $sound,
            ];
        }
    }
}

$quiz->update(['reference_audio_tracks' => $tracks]);

echo "Set ".count($tracks)." reference tracks on \"{$quiz->title}\" across ".count(BASIC_TRIADS_L6_REF_INSTRUMENTS)." instruments x ".(count(BASIC_TRIADS_L6_REF_QUALITIES) * count(BASIC_TRIADS_L6_REF_INVERSIONS))." clips.".PHP_EOL;
