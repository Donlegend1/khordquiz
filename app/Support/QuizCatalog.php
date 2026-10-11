<?php

namespace App\Support;

/**
 * The full quiz catalog from the mobile app (src/lib/quiz-catalog.ts), ported so the
 * web's category and quiz lists show everything the app promises — not just the
 * handful of quizzes that have real questions authored in the web database so far.
 * Entries here with no matching Quiz row render as "Coming soon" rather than being
 * hidden or backed by invented content.
 */
class QuizCatalog
{
    /**
     * @return array<string, list<string>>
     */
    public static function all(): array
    {
        return [
            'Relative Pitch' => [
                'Find the Note', 'Relative Tone Pitch #1', 'Relative Tone Pitch #2',
                'Melodic Second', 'Harmonic Second', 'Melodic Third', 'Harmonic Third',
                'Melodic Fourth', 'Harmonic Fourth', 'Melodic Fifth', 'Harmonic Fifth',
                'Melodic Sixth', 'Harmonic Sixth', 'Melodic Seventh', 'Harmonic Seventh',
                'Find the Key #1', 'Find the Key #2',
            ],
            'Melodic Dictation' => [
                '3-Note Stepwise', '3-Note with Skips', '5-Note Melodies',
                'Dual-tonic melodies', 'Chordal melodies',
            ],
            'Intervals' => [
                'Melodic Diatonic Intervals', 'Harmonic Diatonic Intervals',
                'Melodic Non-diatonic Intervals', 'Harmonic Non-diatonic Intervals',
                'Melodic Intervals: General', 'Harmonic Intervals: General', 'Interval - Patterns',
            ],
            'Basic Triads' => [
                'Basic Triads · Level 1', 'Basic Triads · Level 2', 'Basic Triads · Level 3',
                'Basic Triads · Level 4', 'Basic Triads · Level 5', 'Basic Triads · Level 6',
                'Basic Triads · Level 7', 'Basic Triads · Level 8',
            ],
            'Add 9' => [
                'Add 9 · Level 1', 'Add 9 · Level 2', 'Add 9 · Level 3', 'Add 9 · Level 4',
            ],
            '7th Degree Chords' => [
                '7th Degree Chords · Level 1', '7th Degree Chords · Level 2', '7th Degree Chords · Level 3',
                '7th Degree Chords · Level 4', '7th Degree Chords · Level 5', '7th Degree Chords · Level 6',
                '7th Degree Chords · Level 7', '7th Degree Chords · Level 8',
            ],
            'Secondary 7th Chords' => [
                'Secondary 7th Chords · Level 1', 'Secondary 7th Chords · Level 2', 'Secondary 7th Chords · Level 3',
                'Secondary 7th Chords · Level 4', 'Secondary 7th Chords · Level 5', 'Secondary 7th Chords · Level 6',
                'Secondary 7th Chords · Level 7', 'Secondary 7th Chords · Level 8', 'General',
            ],
            '9th Degree Chords' => [
                '9th Degree Chords · Level 1', '9th Degree Chords · Level 2',
                '9th Degree Chords · Level 3', '9th Degree Chords · Level 4',
            ],
            'Secondary 9th Chords' => [
                'Secondary 9th Chords · Level 1', 'Secondary 9th Chords · Level 2',
                'Secondary 9th Chords · Level 3', 'Secondary 9th Chords · Level 4', 'General',
            ],
            '11th Degree Chords' => [
                '11th Degree Chords · Level 1', '11th Degree Chords · Level 2',
                '11th Degree Chords · Level 3', '11th Degree Chords · Level 4',
            ],
            'Secondary 11th Chords' => [
                'Secondary 11th Chords · Level 1', 'Secondary 11th Chords · Level 2',
                'Secondary 11th Chords · Level 3', 'Secondary 11th Chords · Level 4', 'General',
            ],
            '13th Degree Chords' => [
                '13th Degree Chords · Level 1', '13th Degree Chords · Level 2',
                '13th Degree Chords · Level 3', '13th Degree Chords · Level 4',
            ],
            'Extensions' => [
                'Chord Tone Recognition #1', 'Chord Tone Recognition #2', 'Major Chord Extentions',
                'Minor Chord Extentions', 'Dominant Chord Extentions', 'Chord naming',
            ],
            'Chord Progressions' => [
                'Cadences', 'Diatonic Progression', 'Progression Build #1', 'Progression Build #2',
                'Passing Progression', 'Progression recognition', '2-5-1 chord degree', 'Dom resolution',
            ],
            'Modal Voicings' => [
                'Modal Voicings · Level 1', 'Modal Voicings · Level 2',
                'Modal Voicings · Level 3', 'Modal Voicings · Level 4',
            ],
            'Scales' => [
                'Tonal Modes', 'Major mode', 'Dorian Mode', 'Lydian Mode',
                'Locrian Mode', 'Phrygian Mode', 'Mixolydian Mode',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function subcategories(string $category): array
    {
        return self::all()[$category] ?? [];
    }
}
