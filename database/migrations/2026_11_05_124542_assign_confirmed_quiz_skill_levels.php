<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $groups = [
            'Beginner' => ['Relative Pitch', 'Melodic Dictation', 'Intervals', 'Basic Triads'],
            'Intermediate' => ['Add 9', '7th Degree Chords', 'Secondary 7th Chords', '9th Degree Chords', 'Secondary 9th Chords', '11th Degree Chords', 'Secondary 11th Chords', '13th Degree Chords', 'Chord Tone Recognition'],
            'Advanced' => ['Major Chord Extensions', 'Minor Chord Extensions', 'Dominant Chord Extensions', 'Chord Naming', 'Chord Progressions', 'Modal Harmony', 'Modal Voicings', 'Scales'],
        ];

        foreach ($groups as $difficulty => $categories) {
            DB::table('quizzes')->whereIn('category', $categories)->update(['difficulty' => $difficulty]);
        }
    }

    public function down(): void
    {
        // Earlier individual difficulty assignments cannot be inferred from category.
    }
};
