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
                    'Cadences',
                    'Diatonic Progression #1',
                    'Diatonic Progression #2',
                    'Progression Build #1',
                    'Progression Build #2',
                    'Progression Recognition #1', 'Progression Recognition #2', 'Progression Recognition #3', 'Progression Recognition #4',
                    '2-5-1 chord degree',
                    'Chordal Progressions',
                ],
            },
            {
                name: tk('Scales'),
                description: tk('Recognise the tonal modes by their sound.'),
                quizzes: [
                    'Ionian and Lydian',
                    'Dorian and Aeolian',
                    'Phrygian and Locrian',
                    'Major Scales #1',
                    'Major Scales #2',
                    'Tonal Modes #1',
                    'Tonal Modes #2',
                    'Dorian Scales #1',
                    'Dorian Scales #2',
                    'Lydian Scales #1',
                    'Lydian Scales #2',
                    'Phrygian Scales #1',
                    'Phrygian Scales #2',
                    'Mixolydian Scales #1',
                    'Mixolydian Scales #2',
                    'Aeolian Scales #1',
                    'Aeolian Scales #2',
                    'Locrian Scales #1',
                    'Locrian Scales #2',
                    'Ionian Scales #1',
                    'Ionian Scales #2',
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
                name: 'Chord Tone Recognition',
                description: 'Identify the highlighted tone within a chord.',
                quizzes: [
                    'Chord Tone Recognition #1',
                    'Chord Tone Recognition #2',
                    'Chord Tone Recognition #3',
                    'Chord Tone Recognition #4',
                    'Chord Tone Recognition #5',
                    'Chord Tone Recognition #6',
                ],
            },
            {
                name: 'Major Chord Extensions',
                description: 'Hear the extensions on major chords.',
                quizzes: [
                    'Major Chord Extentions · Level 1',
                    'Major Chord Extentions · Level 2',
                    'Major Chord Extentions · Level 3',
                    'Major Chord Extentions · Level 4',
                    'Major Chord Extentions · Level 5',
                    'Major Chord Extentions · Level 6',
                    'Major Chord Extentions · Level 7',
                    'Major Chord Extentions · Level 8',
                    'Major Chord Extentions · Level 9',
                    'Major Chord Extentions · Level 10',
                    'Major Chord Extentions · Level 11',
                    'Major Chord Extentions · Level 12',
                    'Major Chord Extentions · Level 13',
                ],
            },
            {
                name: 'Minor Chord Extensions',
                description: 'Hear the extensions on minor chords.',
                quizzes: [
                    'Minor Chord Extentions · Level 1',
                    'Minor Chord Extentions · Level 2',
                    'Minor Chord Extentions · Level 3',
                    'Minor Chord Extentions · Level 4',
                    'Minor Chord Extentions · Level 5',
                    'Minor Chord Extentions · Level 6',
                    'Minor Chord Extentions · Level 7',
                    'Minor Chord Extentions · Level 8',
                    'Minor Chord Extentions · Level 9',
                    'Minor Chord Extentions · Level 10',
                    'Minor Chord Extentions · Level 11',
                    'Minor Chord Extentions · Level 12',
                    'Minor Chord Extentions · Level 13',
                ],
            },
            {
                name: 'Dominant Chord Extensions',
                description: 'Hear the extensions on dominant chords.',
                quizzes: [
                    'Dominant Chord Extentions · Level 1',
                    'Dominant Chord Extentions · Level 2',
                    'Dominant Chord Extentions · Level 3',
                    'Dominant Chord Extentions · Level 4',
                    'Dominant Chord Extentions · Level 5',
                    'Dominant Chord Extentions · Level 6',
                    'Dominant Chord Extentions · Level 7',
                    'Dominant Chord Extentions · Level 8',
                ],
            },
            {
                name: 'Chord Naming',
                description: 'Identify chord qualities, extensions and omitted tones.',
                quizzes: ['Chord Naming · Level 1', 'Chord Naming · Level 2', 'Chord Naming · Level 3', 'Chord Naming · Level 4', 'Chord Naming · Level 5', 'Chord Naming · Level 6'],
            },
            {
                name: 'Modal Harmony',
                description: 'Explore modal colors with triads over a bass note.',
                quizzes: ['Modal Harmony #1', 'Modal Harmony #2', 'Modal Harmony #3', 'Modal Harmony #4', 'Modal Harmony #5', 'Modal Harmony #6', 'Modal Harmony #7', 'Modal Harmony #8'],
            },
            {
                name: 'Modal Voicings',
                description: 'Recognise chord voicings built from different modes.',
                quizzes: ['Lydian Mode #1', 'Lydian Mode #2', 'Mixolydian Mode #1', 'Mixolydian Mode #2', 'Phrygian Mode #1', 'Phrygian Mode #2', 'Locrian Mode #1', 'Locrian Mode #2', 'Dorian Mode #1', 'Dorian Mode #2', 'Aeolian Mode #1', 'Aeolian Mode #2', 'Ionian Mode #1', 'Ionian Mode #2', 'Major Modes #1', 'Major Modes #2', 'Minor Modes #1', 'Minor Modes #2'],
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
