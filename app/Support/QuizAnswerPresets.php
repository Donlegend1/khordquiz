<?php

namespace App\Support;

/**
 * Fixed multiple-choice answer options, ported from the real app
 * (app/practice.tsx) — same source as the JS copy used by the Audio Quiz
 * admin editor and the member-facing quiz-play screen
 * (resources/js/lib/quizAnswerPresets.js). Kept here too because only the
 * backend can see a question's real answer, and the member-facing choices
 * list should only switch to this canonical preset when the real answer is
 * actually one of its options — otherwise an already-seeded question whose
 * answer doesn't match the preset would become unanswerable.
 *
 * Only covers quizzes with a flat, single-answer multiple-choice design in
 * the app. Quizzes with a compound (quality+inversion), multi-select,
 * progression-builder, chord-naming or note-sequence design are handled
 * entirely client-side (resources/js/Components/Quiz/AnswerPicker.jsx) since
 * they don't use this kind of fixed choices list at all.
 */
class QuizAnswerPresets
{
    private const SOLFEGE_PAIRS = [
        'Second' => ['Do - Re', 'Re - Mi', 'Mi - Fa', 'Fa - So', 'So - La', 'La - Ti', 'Ti - Do'],
        'Third' => ['Do - Mi', 'Re - Fa', 'Mi - So', 'Fa - La', 'So - Ti', 'La - Do', 'Ti - Re'],
        'Fourth' => ['Do - Fa', 'Re - So', 'Mi - La', 'Fa - Ti', "So - Do'", "La - Re'", "Ti - Mi'"],
        'Fifth' => ['Do - So', 'Re - La', 'Mi - Ti', "Fa - Do'", "So - Re'", "La - Mi'", "Ti - Fa'"],
        'Sixth' => ['Do - La', 'Re - Ti', "Mi - Do'", "Fa - Re'", "So - Mi'", "La - Fa'", "Ti - So'"],
        'Seventh' => ['Do - Ti', "Re - Do'", "Mi - Re'", "Fa - Mi'", "So - Fa'", "La - So'", "Ti - La'"],
    ];

    /**
     * The seven scale-degree answer pairs for a given interval size (e.g.
     * "Second".."Seventh"), shared with ReferenceAudioTracks so the
     * Melodic/Harmonic Second-Seventh reference audio plays this quiz's own
     * answer options instead of an unrelated recording.
     *
     * @return list<string>|null
     */
    public static function solfegePairs(string $name): ?array
    {
        return self::SOLFEGE_PAIRS[$name] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $soflegeNotes = ['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti'];
        $keyChoices = ['C', 'C#/Db', 'D', 'D#/Eb', 'E', 'F', 'F#/Gb', 'G', 'G#/Ab', 'A', 'A#/Bb', 'B'];

        $solfegePairs = self::SOLFEGE_PAIRS;

        $triad = ['Major', 'Minor', 'Diminished', 'Augmented', 'Suspended'];
        $seventh = ['Diminished 7th', 'Dominant 7th', 'Minor 7(♭5)', 'Major 7th', 'Minor 7th'];
        $secondarySeventh = ['Augmented 7th', 'Minor-Major 7th', 'Diminished-Major 7th', 'Dominant 7(♭5)', 'Major 7(♯5)', 'Major 7(♭5)'];
        $ninth = ['Major 9th', 'Minor 9th', 'Diminished 7(9)', 'Dominant 9th', 'Minor 9(♭5)', 'Dominant 7(♭9)'];
        $secondaryNinth = ['Major 9(♯5)', 'Diminished-Major 9th', 'Dominant 9(♭5)', 'Minor-Major 9th', 'Augmented 9th', 'Major 9(♭5)'];
        $eleventh = ['Major 9(♯11)', 'Minor 11th', 'Diminished 7(9,11)', 'Dominant 9(♯11)', 'Minor 11(♭5)', 'Dominant 7(♭9,♯11)'];
        $secondaryEleventh = ['Major 9(♯5,♯11)', 'Diminished 7(♭9,11)', 'Minor-Major 11th', 'Augmented 9(♯11)'];
        $thirteenth = ['Major 13(♯11)', 'Minor 13th', 'Dominant 13(♯11)', 'Dominant 13(♭9,♯11)'];

        return [
            'Melodic Dictation' => [
                '3-Note Stepwise' => $soflegeNotes,
                '3-Note with Skips' => $soflegeNotes,
                '5-Note Melodies' => $soflegeNotes,
                'Dual-tonic melodies' => $soflegeNotes,
                'Chordal melodies' => $soflegeNotes,
            ],
            'Relative Pitch' => [
                'Find the Note' => $keyChoices,
                'Relative Tone Pitch #1' => $soflegeNotes,
                'Relative Tone Pitch #2' => $soflegeNotes,
                'Melodic Second' => $solfegePairs['Second'],
                'Harmonic Second' => $solfegePairs['Second'],
                'Melodic Third' => $solfegePairs['Third'],
                'Harmonic Third' => $solfegePairs['Third'],
                'Melodic Fourth' => $solfegePairs['Fourth'],
                'Harmonic Fourth' => $solfegePairs['Fourth'],
                'Melodic Fifth' => $solfegePairs['Fifth'],
                'Harmonic Fifth' => $solfegePairs['Fifth'],
                'Melodic Sixth' => $solfegePairs['Sixth'],
                'Harmonic Sixth' => $solfegePairs['Sixth'],
                'Melodic Seventh' => $solfegePairs['Seventh'],
                'Harmonic Seventh' => $solfegePairs['Seventh'],
                'Find the Key #1' => $keyChoices,
                'Find the Key #2' => $keyChoices,
            ],
            'Intervals' => [
                'Melodic Diatonic Intervals' => ['Major 2nd', 'Major 3rd', 'Perfect 4th', 'Perfect 5th', 'Major 6th', 'Major 7th', 'Octave'],
                'Harmonic Diatonic Intervals' => ['Major 2nd', 'Major 3rd', 'Perfect 4th', 'Perfect 5th', 'Major 6th', 'Major 7th', 'Octave'],
                'Melodic Non-diatonic Intervals' => ['Minor 2nd', 'Minor 3rd', 'Tri Tone', 'Minor 6th', 'Minor 7th'],
                'Harmonic Non-diatonic Intervals' => ['Minor 2nd', 'Minor 3rd', 'Tri Tone', 'Minor 6th', 'Minor 7th'],
                'Melodic Intervals: General' => ['Minor 2nd', 'Major 2nd', 'Minor 3rd', 'Major 3rd', 'Perfect 4th', 'Tri Tone', 'Perfect 5th', 'Minor 6th', 'Major 6th', 'Minor 7th', 'Major 7th', 'Octave'],
                'Harmonic Intervals: General' => ['Minor 2nd', 'Major 2nd', 'Minor 3rd', 'Major 3rd', 'Perfect 4th', 'Tri Tone', 'Perfect 5th', 'Minor 6th', 'Major 6th', 'Minor 7th', 'Major 7th', 'Octave'],
            ],
            'Basic Triads' => [
                'Basic Triads · Level 1' => $triad,
                'Basic Triads · Level 2' => $triad,
                'Basic Triads · Level 3' => $triad,
                'Basic Triads · Level 4' => $triad,
            ],
            'Add 9' => [
                'Add 9 · Level 1' => ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'],
                'Add 9 · Level 2' => ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'],
                'Add 9 · Level 3' => ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'],
                'Add 9 · Level 4' => ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'],
            ],
            '7th Degree Chords' => [
                '7th Degree Chords · Level 1' => $seventh,
                '7th Degree Chords · Level 2' => $seventh,
                '7th Degree Chords · Level 3' => $seventh,
                '7th Degree Chords · Level 4' => $seventh,
            ],
            'Secondary 7th Chords' => [
                'Secondary 7th Chords · Level 1' => $secondarySeventh,
                'Secondary 7th Chords · Level 2' => $secondarySeventh,
                'Secondary 7th Chords · Level 3' => $secondarySeventh,
                'Secondary 7th Chords · Level 4' => $secondarySeventh,
                'General' => ['Diminished 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Augmented 7th', 'Minor-Major 7th', 'Dominant 7(♭5)', 'Minor 7th', 'Major 7th', 'Major 7(♯5)', 'Diminished-Major 7th', 'Major 7(♭5)'],
            ],
            '9th Degree Chords' => [
                '9th Degree Chords · Level 1' => $ninth,
                '9th Degree Chords · Level 2' => $ninth,
                '9th Degree Chords · Level 3' => $ninth,
                '9th Degree Chords · Level 4' => $ninth,
            ],
            'Secondary 9th Chords' => [
                'Secondary 9th Chords · Level 1' => $secondaryNinth,
                'Secondary 9th Chords · Level 2' => $secondaryNinth,
                'Secondary 9th Chords · Level 3' => $secondaryNinth,
                'Secondary 9th Chords · Level 4' => $secondaryNinth,
                'General' => [...$ninth, ...$secondaryNinth],
            ],
            '11th Degree Chords' => [
                '11th Degree Chords · Level 1' => $eleventh,
                '11th Degree Chords · Level 2' => $eleventh,
                '11th Degree Chords · Level 3' => $eleventh,
                '11th Degree Chords · Level 4' => $eleventh,
            ],
            'Secondary 11th Chords' => [
                'Secondary 11th Chords · Level 1' => $secondaryEleventh,
                'Secondary 11th Chords · Level 2' => $secondaryEleventh,
                'Secondary 11th Chords · Level 3' => $secondaryEleventh,
                'Secondary 11th Chords · Level 4' => $secondaryEleventh,
                'General' => ['Minor-Major 11th', 'Dominant 9(♯11)', 'Major 9(♯5,♯11)', 'Diminished 7(♭9,11)', 'Major 9(♯11)', 'Dominant 7(♭9,♯11)', 'Minor 11th', 'Diminished 7(9,11)', 'Augmented 9(♯11)', 'Minor 11(♭5)'],
            ],
            '13th Degree Chords' => [
                '13th Degree Chords · Level 1' => $thirteenth,
                '13th Degree Chords · Level 2' => $thirteenth,
                '13th Degree Chords · Level 3' => $thirteenth,
                '13th Degree Chords · Level 4' => $thirteenth,
            ],
            'Chord Tone Recognition' => [
                'Chord Tone Recognition #1' => ['1', '3', '5', '7'],
                'Chord Tone Recognition #2' => ['1', '3', '5', '7'],
            ],
            'Chord Progressions' => [
                'Cadences' => ['Authentic', 'Plagal', 'Minor Plagal', 'Sub-Plagal'],
            ],
            'Modal Harmony' => ModalHarmonySamples::presets(),
            'Modal Voicings' => [
                ModalVoicingSamples::TITLE => ModalVoicingSamples::choices(),
                MixolydianVoicingSamples::TITLE => MixolydianVoicingSamples::choices(),
                PhrygianVoicingSamples::TITLE => PhrygianVoicingSamples::choices(),
                LocrianVoicingSamples::TITLE => LocrianVoicingSamples::choices(),
                DorianVoicingSamples::TITLE => DorianVoicingSamples::choices(),
                AeolianVoicingSamples::TITLE => AeolianVoicingSamples::choices(),
                IonianVoicingSamples::TITLE => IonianVoicingSamples::choices(),
                MajorModeVoicingSamples::TITLE => MajorModeVoicingSamples::choices(),
                MinorModeVoicingSamples::TITLE => MinorModeVoicingSamples::choices(),
                ...ModalVoicingLessons::presets(),
            ],
            'Scales' => [
                IonianLydianScaleSamples::TITLE => IonianLydianScaleSamples::choices(),
                DorianAeolianScaleSamples::TITLE => DorianAeolianScaleSamples::choices(),
                PhrygianLocrianScaleSamples::TITLE => PhrygianLocrianScaleSamples::choices(),
                TonalModeScaleSamples::TITLE => TonalModeScaleSamples::choices(),
                TonalModeAllKeysSamples::TITLE => TonalModeAllKeysSamples::choices(),
                MajorScaleSamples::TITLE => MajorScaleSamples::choices(),
                MajorScaleAllKeysSamples::TITLE => MajorScaleAllKeysSamples::choices(),
                DorianScaleSamples::TITLE => DorianScaleSamples::choices(),
                DorianScaleAllKeysSamples::TITLE => DorianScaleAllKeysSamples::choices(),
                LydianScaleSamples::TITLE => LydianScaleSamples::choices(),
                LydianScaleAllKeysSamples::TITLE => LydianScaleAllKeysSamples::choices(),
                LocrianScaleSamples::TITLE => LocrianScaleSamples::choices(),
                LocrianScaleAllKeysSamples::TITLE => LocrianScaleAllKeysSamples::choices(),
                IonianScaleSamples::TITLE => IonianScaleSamples::choices(),
                IonianScaleAllKeysSamples::TITLE => IonianScaleAllKeysSamples::choices(),
                PhrygianScaleSamples::TITLE => PhrygianScaleSamples::choices(),
                PhrygianScaleAllKeysSamples::TITLE => PhrygianScaleAllKeysSamples::choices(),
                MixolydianScaleSamples::TITLE => MixolydianScaleSamples::choices(),
                MixolydianScaleAllKeysSamples::TITLE => MixolydianScaleAllKeysSamples::choices(),
                AeolianScaleSamples::TITLE => AeolianScaleSamples::choices(),
                AeolianScaleAllKeysSamples::TITLE => AeolianScaleAllKeysSamples::choices(),
            ],
        ];
    }

    /**
     * @return list<string>|null
     */
    public static function presetFor(string $category, string $title): ?array
    {
        return self::all()[$category][$title] ?? null;
    }
}
