<?php

// Replaces each Relative Pitch quiz's single Piano-only "C Major Scale" reference
// track with one track per built instrument, tagged by `sound` — the member-facing
// Reference Audio modal picks the right one by the member's Default Instrument
// (with the same category-primary fallback as the question sound picker), not by
// stepping through them manually. All 15 quizzes point at the same shared files
// under reference-scales/ (identical content, same C major scale) rather than
// duplicating the audio into each quiz's own reference-audio folder.

use App\Models\Quiz;
use Illuminate\Support\Str;

$instruments = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Marimba' => 'marimba', 'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
    'Saxophone' => 'saxophone', 'Trombone' => 'trombone', 'Tuba' => 'tuba',
];

// Find the Note, Find the Key #1 and Find the Key #2 are deliberately excluded —
// explicitly decided these quizzes have no reference audio / Listen Mode at all
// (each had stale tracks removed already; don't let a rerun of this script
// silently bring any of them back).
$quizTitles = [
    'Relative Tone Pitch #1', 'Relative Tone Pitch #2',
    'Melodic Second', 'Harmonic Second', 'Melodic Third', 'Harmonic Third',
    'Melodic Fourth', 'Harmonic Fourth', 'Melodic Fifth', 'Harmonic Fifth',
    'Melodic Sixth', 'Harmonic Sixth', 'Melodic Seventh', 'Harmonic Seventh',
    '3-Note Stepwise', '3-Note with Skips', '5-Note Melodies', 'Dual-tonic melodies', 'Chordal melodies',
    'Dyad Melodies', 'Chordal Bass Lines',
];

$tracks = collect($instruments)->map(fn ($slug, $sound) => [
    'id' => (string) Str::uuid(),
    'label' => 'C Major Scale',
    'name' => 'C3 to C4, ascending and descending',
    'sound' => $sound,
    'path' => "reference-scales/{$slug}.wav",
])->values()->all();

foreach ($quizTitles as $title) {
    $quiz = Quiz::where('title', $title)->first();
    if (!$quiz) {
        echo "Skipping {$title}: not found.".PHP_EOL;
        continue;
    }

    $quiz->update(['reference_audio_tracks' => $tracks]);
    echo "{$title}: set ".count($tracks)." reference tracks.".PHP_EOL;
}
