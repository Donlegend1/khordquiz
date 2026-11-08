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
    public const LANGUAGES = ['English', 'Français', 'Español', 'Português', 'Deutsch', 'Nederlands', 'Italiano', '한국어', '中文', 'हिन्दी', '日本語'];

    // Every instrument's storage folder slug (storage/app/public/notes/{slug}/…) —
    // mirrors QuizCategory.jsx's SOUND_CATEGORIES / Settings.jsx's INSTRUMENT_GROUPS
    // membership, kept in sync by hand same as allInstruments(). Was previously
    // QuizController's own private copy — moved here so AudioQuizController (and
    // anything else that needs "instrument name → storage slug") has one source
    // instead of a second private duplicate.
    public const SOUND_SLUGS = [
        'Piano' => 'piano', 'EP' => 'ep', 'Upright Piano' => 'upright-piano', 'Organ' => 'organ',
        'Guitar' => 'guitar', 'Electric Guitar' => 'electric-guitar', 'Harpejji' => 'harpejji', 'Bass Guitar' => 'bass-guitar',
        'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Upright Bass' => 'upright-bass',
        'Saxophone' => 'saxophone', 'Trombone' => 'trombone', 'Tuba' => 'tuba', 'Trumpet' => 'trumpet',
        'Voice' => 'voice', 'Tropar Choir' => 'tropar-choir', 'Ensemble Choir' => 'ensemble-choir', 'Sustained Choir' => 'sustained-choir',
        'Marimba' => 'marimba', 'Steel Drum' => 'steel-drum', 'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
        'Flute' => 'flute', 'Clarinet' => 'clarinet', 'Oboe' => 'oboe', 'Bassoon' => 'bassoon',
    ];

    /**
     * @return array<string, list<string>>
     */
    public static function all(): array
    {
        return [
            'Relative Pitch' => [
                'Find the Note', 'Find the Pitch', 'Relative Tone Pitch #1', 'Relative Tone Pitch #2',
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
            'Chord Tone Recognition' => [
                'Chord Tone Recognition #1', 'Chord Tone Recognition #2',
                'Chord Tone Recognition #3', 'Chord Tone Recognition #4',
                'Chord Tone Recognition #5', 'Chord Tone Recognition #6',
            ],
            'Major Chord Extensions' => [
                'Major Chord Extentions · Level 1', 'Major Chord Extentions · Level 2', 'Major Chord Extentions · Level 3',
                'Major Chord Extentions · Level 4', 'Major Chord Extentions · Level 5', 'Major Chord Extentions · Level 6',
                'Major Chord Extentions · Level 7',
                'Major Chord Extentions · Level 8',
                'Major Chord Extentions · Level 9',
                'Major Chord Extentions · Level 10',
                'Major Chord Extentions · Level 11',
                'Major Chord Extentions · Level 12',
                'Major Chord Extentions · Level 13',
            ],
            'Minor Chord Extensions' => [
                'Minor Chord Extentions · Level 1', 'Minor Chord Extentions · Level 2', 'Minor Chord Extentions · Level 3',
                'Minor Chord Extentions · Level 4', 'Minor Chord Extentions · Level 5', 'Minor Chord Extentions · Level 6',
                'Minor Chord Extentions · Level 7',
                'Minor Chord Extentions · Level 8',
                'Minor Chord Extentions · Level 9',
                'Minor Chord Extentions · Level 10',
                'Minor Chord Extentions · Level 11',
                'Minor Chord Extentions · Level 12',
                'Minor Chord Extentions · Level 13',
            ],
            'Dominant Chord Extensions' => [
                'Dominant Chord Extentions · Level 1',
                'Dominant Chord Extentions · Level 2',
                'Dominant Chord Extentions · Level 3',
                'Dominant Chord Extentions · Level 4',
                'Dominant Chord Extentions · Level 5',
                'Dominant Chord Extentions · Level 6',
                'Dominant Chord Extentions · Level 7',
                'Dominant Chord Extentions · Level 8',
            ],
            'Chord Naming' => ['Chord Naming · Level 1', 'Chord Naming · Level 2', 'Chord Naming · Level 3', 'Chord Naming · Level 4', 'Chord Naming · Level 5', 'Chord Naming · Level 6'],
            'Chord Progressions' => [
                'Cadences', 'Diatonic Progression #1', 'Diatonic Progression #2', 'Progression Build #1', 'Progression Build #2',
                'Progression Recognition #1', 'Progression Recognition #2', 'Progression Recognition #3', 'Progression Recognition #4', '2-5-1 chord degree', 'Chordal Progressions',
            ],
            'Modal Harmony' => ModalHarmonySamples::titles(),
            'Modal Voicings' => ModalVoicingLessons::titles(),
            'Scales' => [
                IonianLydianScaleSamples::TITLE, DorianAeolianScaleSamples::TITLE, PhrygianLocrianScaleSamples::TITLE, MajorScaleSamples::TITLE, MajorScaleAllKeysSamples::TITLE, TonalModeScaleSamples::TITLE, TonalModeAllKeysSamples::TITLE, DorianScaleSamples::TITLE, DorianScaleAllKeysSamples::TITLE, LydianScaleSamples::TITLE, LydianScaleAllKeysSamples::TITLE, PhrygianScaleSamples::TITLE, PhrygianScaleAllKeysSamples::TITLE, MixolydianScaleSamples::TITLE, MixolydianScaleAllKeysSamples::TITLE, AeolianScaleSamples::TITLE, AeolianScaleAllKeysSamples::TITLE, LocrianScaleSamples::TITLE, LocrianScaleAllKeysSamples::TITLE, IonianScaleSamples::TITLE, IonianScaleAllKeysSamples::TITLE,
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
        if ($category === TonalModeScaleSamples::CATEGORY && in_array($title, [TonalModeScaleSamples::TITLE, TonalModeAllKeysSamples::TITLE, DorianScaleSamples::TITLE, DorianScaleAllKeysSamples::TITLE, LydianScaleSamples::TITLE, LydianScaleAllKeysSamples::TITLE, PhrygianScaleSamples::TITLE, PhrygianScaleAllKeysSamples::TITLE, MixolydianScaleSamples::TITLE, MixolydianScaleAllKeysSamples::TITLE, AeolianScaleSamples::TITLE, AeolianScaleAllKeysSamples::TITLE, LocrianScaleSamples::TITLE, LocrianScaleAllKeysSamples::TITLE, IonianScaleSamples::TITLE, IonianScaleAllKeysSamples::TITLE, IonianLydianScaleSamples::TITLE, DorianAeolianScaleSamples::TITLE, PhrygianLocrianScaleSamples::TITLE, MajorScaleSamples::TITLE, MajorScaleAllKeysSamples::TITLE], true)) {
            return TonalModeScaleSamples::sounds();
        }

        if ($category === ModalHarmonySamples::CATEGORY) {
            $index = array_search($title, ModalHarmonySamples::titles(), true);
            if ($index !== false) {
                return ModalHarmonySamples::sounds($index + 1);
            }
        }

        if ($category === ModalVoicingSamples::CATEGORY && in_array($title, ModalVoicingLessons::titles(), true)) {
            return ModalVoicingLessons::sounds($title);
        }

        if ($category === ModalVoicingSamples::CATEGORY && in_array($title, [ModalVoicingSamples::TITLE, MixolydianVoicingSamples::TITLE, PhrygianVoicingSamples::TITLE, LocrianVoicingSamples::TITLE, DorianVoicingSamples::TITLE, AeolianVoicingSamples::TITLE, IonianVoicingSamples::TITLE, MajorModeVoicingSamples::TITLE, MinorModeVoicingSamples::TITLE], true)) {
            return array_values(array_diff(self::allInstruments(), ['Bass Guitar', 'Upright Bass', 'Tuba']));
        }

        $defaultInstruments = ['Piano', 'Violin', 'Saxophone', 'Guitar'];
        $singleLineInstruments = ['Piano', 'Violin', 'Saxophone', 'Marimba', 'Guitar', 'Voice'];
        $intervalInstruments = ['Piano', 'Violin', 'Strings', 'Guitar', 'Marimba', 'Voice', 'Saxophone'];
        $chordInstruments = ['Piano', 'Guitar', 'Strings', 'Voice', 'Organ', 'Marimba'];

        // Relative Pitch (Find the Note, RTP1/RTP2, Melodic/Harmonic 2nd-7th)
        // was pinned to the small $singleLineInstruments/$intervalInstruments
        // lists above from when only a handful of instruments had real
        // audio — every instrument in allInstruments() now does, so this
        // quiz category gets the full roster instead.
        $relativePitchMelodic = self::allInstruments();
        // Harmonic quizzes play two notes at once — a solo voice/choir
        // recording can't realistically harmonize with itself the way an
        // instrument can, so the vocal family stays out of this one list
        // (matches the original reasoning for excluding Voice here, just
        // extended to the newer vocal instruments).
        $relativePitchHarmonic = array_values(array_diff(
            $relativePitchMelodic,
            ['Voice', 'Tropar Choir', 'Ensemble Choir', 'Sustained Choir']
        ));

        // Find the Key #1/#2 use types of RECORDING, not instruments — kept
        // as category options even with no real content behind them yet, so
        // the admin "sound" picker still has somewhere to upload audio
        // against per recording type. Restored after being briefly emptied —
        // that hid the picker, but the user wants these upload targets to
        // keep existing regardless of whether content's been recorded yet.
        $keyFindingSounds = ['Monophonic', 'Vocal Stems', 'Instruments', 'Ensemble', 'Full Song', 'All'];

        $soundsByQuiz = [
            'Diatonic Progression #1' => array_values(array_diff(self::allInstruments(), ['Bass Guitar', 'Upright Bass', 'Tuba'])),
            'Diatonic Progression #2' => array_values(array_diff(self::allInstruments(), ['Bass Guitar', 'Upright Bass', 'Tuba'])),
            'Progression Build #1' => array_values(array_diff(self::allInstruments(), ['Bass Guitar', 'Upright Bass', 'Tuba'])),
            'Progression Build #2' => array_values(array_diff(self::allInstruments(), ['Bass Guitar', 'Upright Bass', 'Tuba'])),
            'Chordal Progressions' => array_values(array_diff(self::allInstruments(), ['Bass Guitar', 'Upright Bass', 'Tuba'])),
            'Progression Recognition #1' => array_values(array_diff(self::allInstruments(), ['Bass Guitar', 'Upright Bass', 'Tuba'])),
            'Progression Recognition #2' => array_values(array_diff(self::allInstruments(), ['Bass Guitar', 'Upright Bass', 'Tuba'])),
            'Progression Recognition #3' => array_values(array_diff(self::allInstruments(), ['Bass Guitar', 'Upright Bass', 'Tuba'])),
            'Progression Recognition #4' => array_values(array_diff(self::allInstruments(), ['Bass Guitar', 'Upright Bass', 'Tuba'])),
            '2-5-1 chord degree' => array_values(array_diff(self::allInstruments(), ['Bass Guitar', 'Upright Bass', 'Tuba'])),
            'Cadences' => array_values(array_diff(self::allInstruments(), ['Bass Guitar', 'Upright Bass', 'Tuba'])),
            'Find the Key #1' => $keyFindingSounds,
            'Find the Key #2' => $keyFindingSounds,
            'Find the Note' => $relativePitchMelodic,
            'Find the Pitch' => $relativePitchMelodic,
            'Relative Tone Pitch #1' => $relativePitchMelodic,
            'Relative Tone Pitch #2' => $relativePitchMelodic,
            'Melodic Second' => $relativePitchMelodic,
            'Melodic Third' => $relativePitchMelodic,
            'Melodic Fourth' => $relativePitchMelodic,
            'Melodic Fifth' => $relativePitchMelodic,
            'Melodic Sixth' => $relativePitchMelodic,
            'Melodic Seventh' => $relativePitchMelodic,
            'Harmonic Second' => $relativePitchHarmonic,
            'Harmonic Third' => $relativePitchHarmonic,
            'Harmonic Fourth' => $relativePitchHarmonic,
            'Harmonic Fifth' => $relativePitchHarmonic,
            'Harmonic Sixth' => $relativePitchHarmonic,
            'Harmonic Seventh' => $relativePitchHarmonic,
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
            'Chord Tone Recognition' => $chordInstruments,
            'Major Chord Extensions' => $chordInstruments,
            'Minor Chord Extensions' => $chordInstruments,
            'Dominant Chord Extensions' => $chordInstruments,
            'Chord Naming' => $chordInstruments,
            'Chord Progressions' => $chordInstruments,
            'Modal Harmony', 'Modal Voicings' => $chordInstruments,
            'Intervals' => self::allInstruments(),
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
            'Guitar', 'Electric Guitar', 'Harpejji', 'Bass Guitar',
            'Violin', 'Strings', 'Cello', 'Upright Bass',
            'Saxophone', 'Trombone', 'Tuba', 'Trumpet',
            'Voice', 'Tropar Choir', 'Ensemble Choir', 'Sustained Choir',
            'Marimba', 'Steel Drum', 'Celesta', 'Vibraphone',
            'Flute', 'Clarinet', 'Oboe', 'Bassoon',
        ];
    }
}
