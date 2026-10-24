<?php

// Sets Reference Audio for "13th Degree Chords · Level 2" by reusing
// Level 1's exact tracks — the key-of-C close-position audio is identical
// between the two levels, same convention used throughout this session.

use App\Models\Quiz;
use Illuminate\Support\Str;

$level1 = Quiz::where('title', '13th Degree Chords · Level 1')->firstOrFail();
$level2 = Quiz::where('title', '13th Degree Chords · Level 2')->firstOrFail();

$copy = array_map(fn ($t) => [...$t, 'id' => (string) Str::uuid()], $level1->reference_audio_tracks);
$level2->update(['reference_audio_tracks' => $copy]);

echo "Copied ".count($copy)." reference tracks to \"{$level2->title}\".".PHP_EOL;
