// Mirrors the KhordQuiz app: categories and quizzes from src/lib/quiz-catalog.ts, grouped into the
// learning paths from src/lib/learning-path.ts (in teaching order). Keep in sync when quizzes change.
// `levels` stands for quizzes the app names "Level 1", "Level 2", …; `general` adds its "General" quiz.

export const QUESTIONS_PER_QUIZ = 25;

export const paths = [
    {
        level: 'Beginner',
        intro: 'Start here. Train your ear on single notes, intervals, melodies and simple chords.',
        categories: [
            {
                name: 'Relative Pitch',
                description: 'Compare notes to a reference tone, then find the key.',
                quizzes: [
                    'Find the Note',
                    'Relative Tone Pitch #1',
                    'Relative Tone Pitch #2',
                    'Melodic Second',
                    'Harmonic Second',
                    'Melodic Third',
                    'Harmonic Third',
                    'Melodic Fourth',
                    'Harmonic Fourth',
                    'Melodic Fifth',
                    'Harmonic Fifth',
                    'Melodic Sixth',
                    'Harmonic Sixth',
                    'Melodic Seventh',
                    'Harmonic Seventh',
                    'Find the Key #1',
                    'Find the Key #2',
                ],
            },
            {
                name: 'Intervals',
                description: 'Learn to hear the distance between two notes.',
                quizzes: [
                    'Melodic Diatonic Intervals',
                    'Harmonic Diatonic Intervals',
                    'Melodic Non-diatonic Intervals',
                    'Harmonic Non-diatonic Intervals',
                    'Melodic Intervals: General',
                    'Harmonic Intervals: General',
                ],
            },
            {
                name: 'Basic Triads',
                description: 'Hear triads in root position and every inversion, in all 12 keys.',
                levels: 8,
            },
            {
                name: 'Melodic Dictation',
                description: 'Listen to short melodies and identify the notes played.',
                quizzes: ['3-Note Stepwise', '3-Note with Skips', '5-Note Melodies', 'Dual-tonic melodies', 'Chordal melodies'],
            },
        ],
    },
    {
        level: 'Intermediate',
        intro: 'Add colour and movement: sevenths, added ninths, progressions and the modes.',
        categories: [
            {
                name: 'Add 9',
                description: 'Hear the colour an added 9th or ♭9 brings to a chord.',
                levels: 8,
            },
            {
                name: '7th Degree Chords',
                description: 'Recognise seventh chords by their position in a key.',
                levels: 8,
            },
            {
                name: 'Secondary 7th Chords',
                description: 'Hear secondary seventh chords and where they lead.',
                levels: 8,
                general: true,
            },
            {
                name: 'Chord Progressions',
                description: 'From cadences and 2-5-1s to building whole progressions yourself.',
                quizzes: [
                    'Cadences',
                    'Diatonic Progression',
                    'Progression Build #1',
                    'Progression Build #2',
                    'Passing Progression',
                    'Progression recognition',
                    '2-5-1 chord degree',
                    'Dom resolution',
                ],
            },
            {
                name: 'Scales',
                description: 'Recognise the tonal modes by their sound.',
                quizzes: [
                    'Tonal Modes',
                    'Major mode',
                    'Dorian Mode',
                    'Lydian Mode',
                    'Locrian Mode',
                    'Phrygian Mode',
                    'Mixolydian Mode',
                ],
            },
        ],
    },
    {
        level: 'Advanced',
        intro: 'Extended harmony: 9ths, 11ths and 13ths, chord extensions and modal voicings.',
        categories: [
            {
                name: '9th Degree Chords',
                description: 'Identify ninth chords by their degree in a key.',
                levels: 6,
            },
            {
                name: 'Secondary 9th Chords',
                description: 'Hear secondary chords coloured with ninths.',
                levels: 6,
                general: true,
            },
            {
                name: '11th Degree Chords',
                description: 'Identify eleventh chords by their degree in a key.',
                levels: 6,
            },
            {
                name: 'Secondary 11th Chords',
                description: 'Hear secondary chords coloured with elevenths.',
                levels: 6,
                general: true,
            },
            {
                name: '13th Degree Chords',
                description: 'The fullest chords of all — identify thirteenths by ear.',
                levels: 6,
            },
            {
                name: 'Extensions',
                description: 'Name the chord tone you hear, then hear the extensions on major, minor and dominant chords.',
                quizzes: [
                    'Chord Tone Recognition #1',
                    'Chord Tone Recognition #2',
                    'Major Chord Extentions',
                    'Minor Chord Extentions',
                    'Dominant Chord Extentions',
                    'Chord naming',
                ],
            },
            {
                name: 'Modal Voicings',
                description: 'Recognise chord voicings built from different modes.',
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
