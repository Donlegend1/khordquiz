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
                    tk('Diatonic Progression'),
                    tk('Progression Build #1'),
                    tk('Progression Build #2'),
                    tk('Passing Progression'),
                    tk('Progression recognition'),
                    tk('2-5-1 chord degree'),
                    tk('Dom resolution'),
                ],
            },
            {
                name: tk('Scales'),
                description: tk('Recognise the tonal modes by their sound.'),
                quizzes: [
                    tk('Tonal Modes'),
                    tk('Major mode'),
                    tk('Dorian Mode'),
                    tk('Lydian Mode'),
                    tk('Locrian Mode'),
                    tk('Phrygian Mode'),
                    tk('Mixolydian Mode'),
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
                name: tk('Extensions'),
                description: tk('Name the chord tone you hear, then hear the extensions on major, minor and dominant chords.'),
                quizzes: [
                    tk('Chord Tone Recognition #1'),
                    tk('Chord Tone Recognition #2'),
                    tk('Major Chord Extentions'),
                    tk('Minor Chord Extentions'),
                    tk('Dominant Chord Extentions'),
                    tk('Chord naming'),
                ],
            },
            {
                name: tk('Modal Voicings'),
                description: tk('Recognise chord voicings built from different modes.'),
                levels: 4,
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
