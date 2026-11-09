// Mirrors the KhordQuiz app: categories and quizzes from src/lib/quiz-catalog.ts, grouped into the
// learning paths from src/lib/learning-path.ts (in teaching order). Keep in sync when quizzes change.
// `levels` stands for quizzes the app names "Level 1", "Level 2", …; `general` adds its "General" quiz.

import { tk } from '@/lib/i18n';

// Display strings are wrapped in tk() so the key extractor finds them; render them with t().
export const QUESTIONS_PER_QUIZ = 25;

export const paths = [
    {
        level: tk('Beginner'),
        intro: tk('Start here. Train your ear on single notes, intervals, melodies and simple chords.'),
        categories: [
            {
                name: tk('Relative Pitch'),
                description: tk('Compare notes to a reference tone, then find the key.'),
                quizzes: [
                    tk('Find the Note'),
                    tk('Relative Tone Pitch #1'),
                    tk('Relative Tone Pitch #2'),
                    tk('Melodic Second'),
                    tk('Harmonic Second'),
                    tk('Melodic Third'),
                    tk('Harmonic Third'),
                    tk('Melodic Fourth'),
                    tk('Harmonic Fourth'),
                    tk('Melodic Fifth'),
                    tk('Harmonic Fifth'),
                    tk('Melodic Sixth'),
                    tk('Harmonic Sixth'),
                    tk('Melodic Seventh'),
                    tk('Harmonic Seventh'),
                    tk('Find the Key #1'),
                    tk('Find the Key #2'),
                ],
            },
            {
                name: tk('Intervals'),
                description: tk('Learn to hear the distance between two notes.'),
                quizzes: [
                    tk('Melodic Diatonic Intervals'),
                    tk('Harmonic Diatonic Intervals'),
                    tk('Melodic Non-diatonic Intervals'),
                    tk('Harmonic Non-diatonic Intervals'),
                    tk('Melodic Intervals: General'),
                    tk('Harmonic Intervals: General'),
                ],
            },
            {
                name: tk('Basic Triads'),
                description: tk('Hear triads in root position and every inversion, in all 12 keys.'),
                levels: 8,
            },
            {
                name: tk('Melodic Dictation'),
                description: tk('Listen to short melodies and identify the notes played.'),
                quizzes: [tk('3-Note Stepwise'), tk('3-Note with Skips'), tk('5-Note Melodies'), tk('Dual-tonic melodies'), tk('Chordal melodies')],
            },
        ],
    },
    {
        level: tk('Intermediate'),
        intro: tk('Add colour and movement: sevenths, added ninths, progressions and the modes.'),
        categories: [
            {
                name: tk('Add 9'),
                description: tk('Hear the colour an added 9th or ♭9 brings to a chord.'),
                levels: 8,
            },
            {
                name: tk('7th Degree Chords'),
                description: tk('Recognise seventh chords by their position in a key.'),
                levels: 8,
            },
            {
                name: tk('Secondary 7th Chords'),
                description: tk('Hear secondary seventh chords and where they lead.'),
                levels: 8,
                general: true,
            },
            {
                name: tk('Chord Progressions'),
                description: tk('From cadences and 2-5-1s to building whole progressions yourself.'),
                quizzes: [
                    tk('Cadences'),
                    tk('Diatonic Progression #1'),
                    tk('Diatonic Progression #2'),
                    tk('Progression Build #1'),
                    tk('Progression Build #2'),
                    tk('Progression Recognition #1'), tk('Progression Recognition #2'), tk('Progression Recognition #3'), tk('Progression Recognition #4'),
                    tk('2-5-1 chord degree'),
                    tk('Chordal Progressions'),
                ],
            },
            {
                name: tk('Scales'),
                description: tk('Recognise the tonal modes by their sound.'),
                quizzes: [
                    tk('Ionian and Lydian'),
                    tk('Dorian and Aeolian'),
                    tk('Phrygian and Locrian'),
                    tk('Major Scales #1'),
                    tk('Major Scales #2'),
                    tk('Tonal Modes #1'),
                    tk('Tonal Modes #2'),
                    tk('Dorian Scales #1'),
                    tk('Dorian Scales #2'),
                    tk('Lydian Scales #1'),
                    tk('Lydian Scales #2'),
                    tk('Phrygian Scales #1'),
                    tk('Phrygian Scales #2'),
                    tk('Mixolydian Scales #1'),
                    tk('Mixolydian Scales #2'),
                    tk('Aeolian Scales #1'),
                    tk('Aeolian Scales #2'),
                    tk('Locrian Scales #1'),
                    tk('Locrian Scales #2'),
                    tk('Ionian Scales #1'),
                    tk('Ionian Scales #2'),
                ],
            },
        ],
    },
    {
        level: tk('Advanced'),
        intro: tk('Extended harmony: 9ths, 11ths and 13ths, chord extensions and modal voicings.'),
        categories: [
            {
                name: tk('9th Degree Chords'),
                description: tk('Identify ninth chords by their degree in a key.'),
                levels: 6,
            },
            {
                name: tk('Secondary 9th Chords'),
                description: tk('Hear secondary chords coloured with ninths.'),
                levels: 6,
                general: true,
            },
            {
                name: tk('11th Degree Chords'),
                description: tk('Identify eleventh chords by their degree in a key.'),
                levels: 6,
            },
            {
                name: tk('Secondary 11th Chords'),
                description: tk('Hear secondary chords coloured with elevenths.'),
                levels: 6,
                general: true,
            },
            {
                name: tk('13th Degree Chords'),
                description: tk('The fullest chords of all — identify thirteenths by ear.'),
                levels: 6,
            },
            {
                name: tk('Chord Tone Recognition'),
                description: tk('Identify the highlighted tone within a chord.'),
                quizzes: [
                    tk('Chord Tone Recognition #1'),
                    tk('Chord Tone Recognition #2'),
                    tk('Chord Tone Recognition #3'),
                    tk('Chord Tone Recognition #4'),
                    tk('Chord Tone Recognition #5'),
                    tk('Chord Tone Recognition #6'),
                ],
            },
            {
                name: tk('Major Chord Extensions'),
                description: tk('Hear the extensions on major chords.'),
                quizzes: [
                    tk('Major Chord Extentions · Level 1'),
                    tk('Major Chord Extentions · Level 2'),
                    tk('Major Chord Extentions · Level 3'),
                    tk('Major Chord Extentions · Level 4'),
                    tk('Major Chord Extentions · Level 5'),
                    tk('Major Chord Extentions · Level 6'),
                    tk('Major Chord Extentions · Level 7'),
                    tk('Major Chord Extentions · Level 8'),
                    tk('Major Chord Extentions · Level 9'),
                    tk('Major Chord Extentions · Level 10'),
                    tk('Major Chord Extentions · Level 11'),
                    tk('Major Chord Extentions · Level 12'),
                    tk('Major Chord Extentions · Level 13'),
                ],
            },
            {
                name: tk('Minor Chord Extensions'),
                description: tk('Hear the extensions on minor chords.'),
                quizzes: [
                    tk('Minor Chord Extentions · Level 1'),
                    tk('Minor Chord Extentions · Level 2'),
                    tk('Minor Chord Extentions · Level 3'),
                    tk('Minor Chord Extentions · Level 4'),
                    tk('Minor Chord Extentions · Level 5'),
                    tk('Minor Chord Extentions · Level 6'),
                    tk('Minor Chord Extentions · Level 7'),
                    tk('Minor Chord Extentions · Level 8'),
                    tk('Minor Chord Extentions · Level 9'),
                    tk('Minor Chord Extentions · Level 10'),
                    tk('Minor Chord Extentions · Level 11'),
                    tk('Minor Chord Extentions · Level 12'),
                    tk('Minor Chord Extentions · Level 13'),
                ],
            },
            {
                name: tk('Dominant Chord Extensions'),
                description: tk('Hear the extensions on dominant chords.'),
                quizzes: [
                    tk('Dominant Chord Extentions · Level 1'),
                    tk('Dominant Chord Extentions · Level 2'),
                    tk('Dominant Chord Extentions · Level 3'),
                    tk('Dominant Chord Extentions · Level 4'),
                    tk('Dominant Chord Extentions · Level 5'),
                    tk('Dominant Chord Extentions · Level 6'),
                    tk('Dominant Chord Extentions · Level 7'),
                    tk('Dominant Chord Extentions · Level 8'),
                ],
            },
            {
                name: tk('Chord Naming'),
                description: tk('Identify chord qualities, extensions and omitted tones.'),
                quizzes: [tk('Chord Naming · Level 1'), tk('Chord Naming · Level 2'), tk('Chord Naming · Level 3'), tk('Chord Naming · Level 4'), tk('Chord Naming · Level 5'), tk('Chord Naming · Level 6')],
            },
            {
                name: tk('Modal Harmony'),
                description: tk('Explore modal colors with triads over a bass note.'),
                quizzes: [tk('Modal Harmony #1'), tk('Modal Harmony #2'), tk('Modal Harmony #3'), tk('Modal Harmony #4'), tk('Modal Harmony #5'), tk('Modal Harmony #6'), tk('Modal Harmony #7'), tk('Modal Harmony #8')],
            },
            {
                name: tk('Modal Voicings'),
                description: tk('Recognise chord voicings built from different modes.'),
                quizzes: [tk('Lydian Mode #1'), tk('Lydian Mode #2'), tk('Mixolydian Mode #1'), tk('Mixolydian Mode #2'), tk('Phrygian Mode #1'), tk('Phrygian Mode #2'), tk('Locrian Mode #1'), tk('Locrian Mode #2'), tk('Dorian Mode #1'), tk('Dorian Mode #2'), tk('Aeolian Mode #1'), tk('Aeolian Mode #2'), tk('Ionian Mode #1'), tk('Ionian Mode #2'), tk('Major Modes #1'), tk('Major Modes #2'), tk('Minor Modes #1'), tk('Minor Modes #2')],
            },
        ],
    },
];

export const quizCount = (category) => category.quizzes?.length ?? category.levels + (category.general ? 1 : 0);

export const pathQuizCount = (path) => path.categories.reduce((sum, category) => sum + quizCount(category), 0);

export const totals = {
    categories: paths.reduce((sum, path) => sum + path.categories.length, 0),
    quizzes: paths.reduce((sum, path) => sum + pathQuizCount(path), 0),
};
