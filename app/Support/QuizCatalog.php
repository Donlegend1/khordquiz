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
    // Same list as resources/js/Pages/Member/Settings.jsx's LANGUAGES — kept in
    // sync by hand (that copy is what the picker UI renders; this one is what
    // ProfileSetupController validates incoming language values against).
    public const LANGUAGES = ['English', 'Français', 'Español', 'Português', 'Deutsch', 'Italiano', '한국어', '中文', 'हिन्दी', '日本語'];

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
                '3-Note Stepwise', '3-Note with Skips', '5-Note Melodies', 'Dyad Melodies',
                'Dual-tonic melodies', 'Chordal melodies', 'Chordal Bass Lines',
            ],
            'Intervals' => [
                'Melodic Diatonic Intervals', 'Harmonic Diatonic Intervals',
                'Melodic Non-diatonic Intervals', 'Harmonic Non-diatonic Intervals',
                'Melodic Intervals: General', 'Harmonic Intervals: General',
            ],
            'Basic Triads' => [
                'Basic Triads · Level 1', 'Basic Triads · Level 2', 'Basic Triads · Level 3',
                'Basic Triads · Level 4', 'Basic Triads · Level 5', 'Basic Triads · Level 6',
                'Basic Triads · Level 7', 'Basic Triads · Level 8',
            ],
            'Add 9' => [
                'Add 9 · Level 1', 'Add 9 · Level 2', 'Add 9 · Level 3', 'Add 9 · Level 4',
                'Add 9 · Level 5', 'Add 9 · Level 6', 'Add 9 · Level 7', 'Add 9 · Level 8',
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
                '9th Degree Chords · Level 5', '9th Degree Chords · Level 6',
            ],
            'Secondary 9th Chords' => [
                'Secondary 9th Chords · Level 1', 'Secondary 9th Chords · Level 2',
                'Secondary 9th Chords · Level 3', 'Secondary 9th Chords · Level 4',
                'Secondary 9th Chords · Level 5', 'Secondary 9th Chords · Level 6', 'General',
            ],
            '11th Degree Chords' => [
                '11th Degree Chords · Level 1', '11th Degree Chords · Level 2',
                '11th Degree Chords · Level 3', '11th Degree Chords · Level 4',
                '11th Degree Chords · Level 5', '11th Degree Chords · Level 6',
            ],
            'Secondary 11th Chords' => [
                'Secondary 11th Chords · Level 1', 'Secondary 11th Chords · Level 2',
                'Secondary 11th Chords · Level 3', 'Secondary 11th Chords · Level 4',
                'Secondary 11th Chords · Level 5', 'Secondary 11th Chords · Level 6', 'General',
            ],
            '13th Degree Chords' => [
                '13th Degree Chords · Level 1', '13th Degree Chords · Level 2',
                '13th Degree Chords · Level 3', '13th Degree Chords · Level 4',
                '13th Degree Chords · Level 5', '13th Degree Chords · Level 6',
            ],
            'Extensions' => [
                'Chord Tone Recognition #1', 'Chord Tone Recognition #2', 'Major Chord Extentions',
                'Minor Chord Extentions', 'Dominant Chord Extentions', 'Chord naming',
            ],
            'Chord Progressions' => [
                'Cadences', 'Diatonic Progression', 'Chordal Progressions', 'Progression Build #1', 'Progression Build #2',
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

    /**
     * Sounds a quiz can be played with — ported from the mobile app's
     * instrumentsFor() (src/lib/quiz-catalog.ts). Relative Pitch and Melodic
     * Dictation share one set, chord categories share another, and the
     * Find the Key quizzes use types of recording instead of instruments.
     *
     * @return list<string>
     */
    public static function soundsFor(string $category, string $title): array
    {
        $defaultInstruments = ['Piano', 'Violin', 'Saxophone', 'Guitar'];
        $singleLineInstruments = ['Piano', 'Violin', 'Saxophone', 'Marimba', 'Guitar', 'Voice'];
        $intervalInstruments = ['Piano', 'Violin', 'Strings', 'Guitar', 'Marimba', 'Voice', 'Saxophone'];
        $chordInstruments = ['Piano', 'Guitar', 'Strings', 'Voice', 'Organ', 'Marimba'];
        $keyFindingSounds = ['Monophonic', 'Vocal Stems', 'Instruments', 'Ensemble', 'Full Song', 'All'];

        // 'All' mixes every type of recording across the quiz.
        $soundsByQuiz = [
            'Find the Key #1' => $keyFindingSounds,
            'Find the Key #2' => $keyFindingSounds,
            // Harmonic intervals sit in Relative Pitch but need two notes at
            // once, so no single-line instruments like Voice.
            'Harmonic Second' => $intervalInstruments,
            'Harmonic Third' => $intervalInstruments,
            'Harmonic Fourth' => $intervalInstruments,
            'Harmonic Fifth' => $intervalInstruments,
            'Harmonic Sixth' => $intervalInstruments,
            'Harmonic Seventh' => $intervalInstruments,
            // Dyad Melodies plays simultaneous 2-note dyads, same requirement
            // as the Harmonic interval quizzes above — no single-line instruments.
            'Dyad Melodies' => $intervalInstruments,
        ];

        if (isset($soundsByQuiz[$title])) {
            return $soundsByQuiz[$title];
        }

        // Basic Triads and Add 9 got Trombone, Saxophone (Brass) and Celesta,
        // Vibraphone (Percussion) recorded first — Marimba was already in
        // $chordInstruments. Other chord categories don't have this audio
        // yet, so they keep the shared $chordInstruments list until each
        // gets the same build.
        $fullBrassPercussionInstruments = [...$chordInstruments, 'Saxophone', 'Trombone', 'Celesta', 'Vibraphone'];

        $instrumentsByCategory = [
            'Basic Triads' => $fullBrassPercussionInstruments,
            'Add 9' => $fullBrassPercussionInstruments,
            '7th Degree Chords' => $fullBrassPercussionInstruments,
            'Secondary 7th Chords' => $fullBrassPercussionInstruments,
            '9th Degree Chords' => $fullBrassPercussionInstruments,
            'Secondary 9th Chords' => $fullBrassPercussionInstruments,
            '11th Degree Chords' => $fullBrassPercussionInstruments,
            'Secondary 11th Chords' => $fullBrassPercussionInstruments,
            '13th Degree Chords' => $fullBrassPercussionInstruments,
            'Extensions' => $chordInstruments,
            'Chord Progressions' => $chordInstruments,
            'Modal Voicings' => $chordInstruments,
            'Intervals' => $intervalInstruments,
            'Melodic Dictation' => $singleLineInstruments,
            'Relative Pitch' => $singleLineInstruments,
            'Scales' => $singleLineInstruments,
        ];

        return $instrumentsByCategory[$category] ?? $defaultInstruments;
    }

    /**
     * Every instrument a member can set as their Default Instrument, across every
     * family — including ones with no generated audio yet (Organ, Cello, Harp,
     * Trombone, Tuba, Celesta, Vibraphone, Bass Guitar, String Ensemble). Kept in
     * sync by hand with the category groupings in QuizCategory.jsx (SOUND_CATEGORIES)
     * and Settings.jsx (INSTRUMENT_GROUPS) — one list, three places, no shared
     * JS/PHP import between them.
     *
     * @return list<string>
     */
    public static function allInstruments(): array
    {
        return [
            'Piano', 'EP', 'Upright Piano', 'Organ',
            'Guitar', 'Jazz Guitar', 'Bass Guitar',
            'Violin', 'Strings', 'Cello', 'Harp',
            'Saxophone', 'Trombone', 'Tuba',
            'Voice',
            'Marimba', 'Celesta', 'Vibraphone',
        ];
    }
}
