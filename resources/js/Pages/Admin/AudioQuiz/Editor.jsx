import AppShell from '@/Layouts/AppShell';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const SOLFEGE_PRESET = ['Doh', 'Reh', 'Mi', 'Fah', 'Sol', 'Lah', 'Ti'];
const TABS = ['Video', 'Difficulty', 'Reference Audio'];

// Fixed answer options for the Relative Pitch category, matching the real app's
// choices exactly (app/practice.tsx) — melodic and harmonic twins of an interval
// share the same solfège-pair options, only the listening prompt differs.
const KEY_CHOICES = ['C', 'C#/Db', 'D', 'D#/Eb', 'E', 'F', 'F#/Gb', 'G', 'G#/Ab', 'A', 'A#/Bb', 'B'];
const SOLFEGE_NOTE_CHOICES = ['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti'];

// Piano-style keyboard layout, matching app/practice.tsx's SolfegeKeyboard.
// 8 white keys (the 8th completes the octave visually, same "Do" as the 1st)
// with 5 black keys in between — none between Mi/Fa or Ti/Do, matching a real
// keyboard's semitone gaps.
const KEYBOARD_WHITE_KEYS = ['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti', 'Do'];
const KEYBOARD_BLACK_KEYS = [
    { afterIndex: 0, note: 'Di' },
    { afterIndex: 1, note: 'Ri' },
    { afterIndex: 3, note: 'Fi' },
    { afterIndex: 4, note: 'Si' },
    { afterIndex: 5, note: 'Li' },
];
const BLACK_KEY_RATIO = 0.64;
const blackKeyWidth = (whiteKeys) => `${(100 / whiteKeys) * BLACK_KEY_RATIO}%`;
const blackKeyLeft = (afterIndex, whiteKeys) => `${((afterIndex + 1) * 100) / whiteKeys - (100 / whiteKeys) * (BLACK_KEY_RATIO / 2)}%`;
const SOLFEGE_PAIR_CHOICES = {
    Second: ['Do - Re', 'Re - Mi', 'Mi - Fa', 'Fa - So', 'So - La', 'La - Ti', 'Ti - Do'],
    Third: ['Do - Mi', 'Re - Fa', 'Mi - So', 'Fa - La', 'So - Ti', 'La - Do', 'Ti - Re'],
    Fourth: ["Do - Fa", "Re - So", "Mi - La", "Fa - Ti", "So - Do'", "La - Re'", "Ti - Mi'"],
    Fifth: ["Do - So", "Re - La", "Mi - Ti", "Fa - Do'", "So - Re'", "La - Mi'", "Ti - Fa'"],
    Sixth: ["Do - La", "Re - Ti", "Mi - Do'", "Fa - Re'", "So - Mi'", "La - Fa'", "Ti - So'"],
    Seventh: ["Do - Ti", "Re - Do'", "Mi - Re'", "Fa - Mi'", "So - Fa'", "La - So'", "Ti - La'"],
};

const TRIAD_CHOICES = ['Major', 'Minor', 'Diminished', 'Augmented', 'Suspended'];
const SEVENTH_CHOICES = ['Diminished 7th', 'Dominant 7th', 'Minor 7(♭5)', 'Major 7th', 'Minor 7th'];
const SECONDARY_SEVENTH_CHOICES = ['Augmented 7th', 'Minor-Major 7th', 'Diminished-Major 7th', 'Dominant 7(♭5)', 'Major 7(♯5)', 'Major 7(♭5)'];
const NINTH_CHOICES = ['Major 9th', 'Minor 9th', 'Diminished 7(9)', 'Dominant 9th', 'Minor 9(♭5)', 'Dominant 7(♭9)'];
const SECONDARY_NINTH_CHOICES = ['Major 9(♯5)', 'Diminished-Major 9th', 'Dominant 9(♭5)', 'Minor-Major 9th', 'Augmented 9th', 'Major 9(♭5)'];
const ELEVENTH_CHOICES = ['Major 9(♯11)', 'Minor 11th', 'Diminished 7(9,11)', 'Dominant 9(♯11)', 'Minor 11(♭5)', 'Dominant 7(♭9,♯11)'];
const SECONDARY_ELEVENTH_CHOICES = ['Major 9(♯5,♯11)', 'Diminished 7(♭9,11)', 'Minor-Major 11th', 'Augmented 9(♯11)'];
const THIRTEENTH_CHOICES = ['Major 13(♯11)', 'Minor 13th', 'Dominant 13(♯11)', 'Dominant 13(♭9,♯11)'];

// Fixed answer options matching the real app exactly (app/practice.tsx). Quizzes
// not listed here use a non-MCQ UI in the app (compound quality+inversion
// pickers, multi-select, sequence builders, free-form chord naming) that this
// simple single-answer form can't represent — they fall back to a generic preset.
// Melodic Dictation has no fixed multiple-choice answer in the app at all — the
// admin instead builds the correct note-by-note sequence on a solfège keyboard,
// matching app/practice.tsx's SequenceBoard + SolfegeKeyboard (sequence.length).
// Fixed lengths for most lessons; Dual-tonic/Chordal melodies vary question to
// question, so those list the lengths the admin can pick from per question.
const MELODIC_DICTATION_LENGTHS = {
    '3-Note Stepwise': 3,
    '3-Note with Skips': 3,
    '5-Note Melodies': 5,
    'Dual-tonic melodies': [4, 5, 6],
    'Chordal melodies': [4, 5, 6],
};

const TRIAD_INVERSIONS = ['Root Position', '1st Inversion', '2nd Inversion'];
const SEVENTH_INVERSIONS = ['Root Position', '1st Inversion', '2nd Inversion', '3rd Inversion'];

// Quizzes with no single fixed-choice answer — the app has the student pick a
// chord quality AND an inversion separately (compound: { qualities, inversions }
// in app/practice.tsx), so the admin needs both pickers too, not one flat list.
const COMPOUND_ANSWER_PRESETS = {
    'Basic Triads': {
        'Basic Triads · Level 5': { qualities: TRIAD_CHOICES, inversions: TRIAD_INVERSIONS },
        'Basic Triads · Level 6': { qualities: TRIAD_CHOICES, inversions: TRIAD_INVERSIONS },
        'Basic Triads · Level 7': { qualities: TRIAD_CHOICES, inversions: TRIAD_INVERSIONS },
        'Basic Triads · Level 8': { qualities: TRIAD_CHOICES, inversions: TRIAD_INVERSIONS },
    },
    '7th Degree Chords': {
        '7th Degree Chords · Level 5': { qualities: SEVENTH_CHOICES, inversions: SEVENTH_INVERSIONS },
        '7th Degree Chords · Level 6': { qualities: SEVENTH_CHOICES, inversions: SEVENTH_INVERSIONS },
        '7th Degree Chords · Level 7': { qualities: SEVENTH_CHOICES, inversions: SEVENTH_INVERSIONS },
        '7th Degree Chords · Level 8': { qualities: SEVENTH_CHOICES, inversions: SEVENTH_INVERSIONS },
    },
    'Secondary 7th Chords': {
        'Secondary 7th Chords · Level 5': { qualities: SECONDARY_SEVENTH_CHOICES, inversions: SEVENTH_INVERSIONS },
        'Secondary 7th Chords · Level 6': { qualities: SECONDARY_SEVENTH_CHOICES, inversions: SEVENTH_INVERSIONS },
        'Secondary 7th Chords · Level 7': { qualities: SECONDARY_SEVENTH_CHOICES, inversions: SEVENTH_INVERSIONS },
        'Secondary 7th Chords · Level 8': { qualities: SECONDARY_SEVENTH_CHOICES, inversions: SEVENTH_INVERSIONS },
    },
};

function compoundFor(category, title) {
    return COMPOUND_ANSWER_PRESETS[category]?.[title] ?? null;
}

const EXTENSION_CHOICES = ['7', '♭9', '9', '11', '♯11', '♭13', '13'];
const DOMINANT_EXTENSION_CHOICES = ['♭9', '9', '♯9', '11', '♯11', '♭13', '13'];

// Quizzes where the student picks several extensions at once rather than one
// single answer (multi: true in app/practice.tsx) — the admin needs a
// multi-select, not a single radio list.
const MULTI_ANSWER_PRESETS = {
    Extensions: {
        'Major Chord Extentions': EXTENSION_CHOICES,
        'Minor Chord Extentions': EXTENSION_CHOICES,
        'Dominant Chord Extentions': DOMINANT_EXTENSION_CHOICES,
    },
};

function multiFor(category, title) {
    return MULTI_ANSWER_PRESETS[category]?.[title] ?? null;
}

const PROGRESSION_DEGREES = ['1', '2', '3', '4', '5', '6', '7'];
const PROGRESSION_QUALITIES = ['Major', 'Minor', 'Diminished', 'Augmented', 'Sus 2', 'Sus 4'];
const PROGRESSION_SEVENTH_QUALITIES = ['Major 7th', 'Minor 7th', 'Dominant 7th', 'Minor 7(♭5)'];
const DIATONIC_TRIADS = { 1: 'Major', 2: 'Minor', 3: 'Minor', 4: 'Major', 5: 'Major', 6: 'Minor', 7: 'Diminished' };
const DEGREE_EXTENSIONS = ['♭9', '9', '♯9', '11', '♯11', '♭13', '13', 'Sus 4', 'Sus 2', '♭5'];
const RECOGNITION_QUALITIES = ['Major 7th', 'Minor 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Minor 6', 'Major 6'];

// Chord-progression quizzes with no fixed answer at all — the app has the
// student build each chord in the progression slot by slot (progression: {
// degrees, qualities, fixedDegrees, fixedQualities, extensions } in
// app/practice.tsx), so the admin needs the same slot-by-slot builder.
const PROGRESSION_ANSWER_PRESETS = {
    'Chord Progressions': {
        'Diatonic Progression': { length: 4, degreeOptions: PROGRESSION_DEGREES, fixedQualities: DIATONIC_TRIADS },
        'Progression Build #1': { length: 4, degreeOptions: PROGRESSION_DEGREES, qualityOptions: PROGRESSION_QUALITIES },
        'Progression Build #2': { length: 5, degreeOptions: PROGRESSION_DEGREES, qualityOptions: PROGRESSION_SEVENTH_QUALITIES },
        'Progression recognition': { length: 3, fixedDegrees: ['2', '5', '1'], qualityOptions: RECOGNITION_QUALITIES },
        '2-5-1 chord degree': { length: 3, fixedDegrees: ['2', '5', '1'], qualityOptions: RECOGNITION_QUALITIES, extensionOptions: DEGREE_EXTENSIONS },
    },
};

function progressionFor(category, title) {
    return PROGRESSION_ANSWER_PRESETS[category]?.[title] ?? null;
}

// "Chord naming" (Extensions category) has no fixed answer at all in the app —
// the student builds the chord name from three steps (naming: { qualities,
// degrees, excludes } in app/practice.tsx) that get composed into a name via
// buildNamedChord(). Ported here 1:1 so the admin's answer matches exactly.
const NAMING_QUALITIES = ['Major', 'Minor', 'Augmented', 'Dominant', 'Diminished'];
// Expands the app's base degree pool with the flat/sharp variants of 9, 11 and
// 13 it lets the player cycle through, so each is its own pickable chip here.
const NAMING_DEGREES = ['♭5', '6', '7', '9', '♭9', '♯9', '11', '♭11', '♯11', '13', '♭13', '♯13', 'Sus 2', 'Sus 4'];
const NAMING_EXCLUDES = ['No 3', 'No 5', 'None'];

// Chord Tone Recognition asks which scale degree (1/3/5/7) is highlighted, but
// that's only meaningful with a reference chord quality — the admin picks one
// per question so the member hears e.g. "a Major 7th chord" before answering,
// instead of guessing the degree with no harmonic context. Covers both the
// 5 primary 7th-chord qualities and the 6 secondary ones.
const CHORD_TONE_QUALITIES = [...SEVENTH_CHOICES, ...SECONDARY_SEVENTH_CHOICES];

function buildNamedChord(quality, degrees, exclude) {
    const has = (degree) => degrees.includes(degree);
    const seventh = has('7') || quality === 'Dominant';
    const naturals = ['9', '11', '13'].filter(has);
    const altered = ['♭5', '♭9', '♯9', '♭11', '♯11', '♭13', '♯13'].filter(has);
    const core = seventh ? (naturals.length ? naturals[naturals.length - 1] : '7') : has('6') ? (has('9') ? '6/9' : '6') : '';
    const bracketed = seventh ? altered : altered.filter((degree) => degree === '♭5');
    const adds = [
        ...(seventh ? (has('6') ? ['6'] : []) : naturals.filter((degree) => !(has('6') && degree === '9'))),
        ...(seventh ? [] : altered.filter((degree) => degree !== '♭5')),
    ];
    return [
        quality,
        core ? ` ${core}` : '',
        bracketed.length ? `(${bracketed.join(', ')})` : '',
        adds.length ? ` add ${adds.join(', ')}` : '',
        has('Sus 2') ? ' Sus 2' : '',
        has('Sus 4') ? ' Sus 4' : '',
        exclude && exclude !== 'None' ? ` (${exclude})` : '',
    ].join('');
}

const CATEGORY_ANSWER_PRESETS = {
    'Melodic Dictation': {
        '3-Note Stepwise': SOLFEGE_NOTE_CHOICES,
        '3-Note with Skips': SOLFEGE_NOTE_CHOICES,
        '5-Note Melodies': SOLFEGE_NOTE_CHOICES,
        'Dual-tonic melodies': SOLFEGE_NOTE_CHOICES,
        'Chordal melodies': SOLFEGE_NOTE_CHOICES,
    },
    'Relative Pitch': {
        'Find the Note': KEY_CHOICES,
        'Relative Tone Pitch #1': SOLFEGE_NOTE_CHOICES,
        'Relative Tone Pitch #2': SOLFEGE_NOTE_CHOICES,
        'Melodic Second': SOLFEGE_PAIR_CHOICES.Second,
        'Harmonic Second': SOLFEGE_PAIR_CHOICES.Second,
        'Melodic Third': SOLFEGE_PAIR_CHOICES.Third,
        'Harmonic Third': SOLFEGE_PAIR_CHOICES.Third,
        'Melodic Fourth': SOLFEGE_PAIR_CHOICES.Fourth,
        'Harmonic Fourth': SOLFEGE_PAIR_CHOICES.Fourth,
        'Melodic Fifth': SOLFEGE_PAIR_CHOICES.Fifth,
        'Harmonic Fifth': SOLFEGE_PAIR_CHOICES.Fifth,
        'Melodic Sixth': SOLFEGE_PAIR_CHOICES.Sixth,
        'Harmonic Sixth': SOLFEGE_PAIR_CHOICES.Sixth,
        'Melodic Seventh': SOLFEGE_PAIR_CHOICES.Seventh,
        'Harmonic Seventh': SOLFEGE_PAIR_CHOICES.Seventh,
        'Find the Key #1': KEY_CHOICES,
        'Find the Key #2': KEY_CHOICES,
    },
    Intervals: {
        'Melodic Diatonic Intervals': ['Major 2nd', 'Major 3rd', 'Perfect 4th', 'Perfect 5th', 'Major 6th', 'Major 7th', 'Octave'],
        'Harmonic Diatonic Intervals': ['Major 2nd', 'Major 3rd', 'Perfect 4th', 'Perfect 5th', 'Major 6th', 'Major 7th', 'Octave'],
        'Melodic Non-diatonic Intervals': ['Minor 2nd', 'Minor 3rd', 'Tri Tone', 'Minor 6th', 'Minor 7th'],
        'Harmonic Non-diatonic Intervals': ['Minor 2nd', 'Minor 3rd', 'Tri Tone', 'Minor 6th', 'Minor 7th'],
        'Melodic Intervals: General': ['Minor 2nd', 'Major 2nd', 'Minor 3rd', 'Major 3rd', 'Perfect 4th', 'Tri Tone', 'Perfect 5th', 'Minor 6th', 'Major 6th', 'Minor 7th', 'Major 7th', 'Octave'],
        'Harmonic Intervals: General': ['Minor 2nd', 'Major 2nd', 'Minor 3rd', 'Major 3rd', 'Perfect 4th', 'Tri Tone', 'Perfect 5th', 'Minor 6th', 'Major 6th', 'Minor 7th', 'Major 7th', 'Octave'],
        'Interval - Patterns': ['Thirds', 'Fourths', 'Fifths', 'Sixths'],
    },
    'Basic Triads': {
        'Basic Triads · Level 1': TRIAD_CHOICES,
        'Basic Triads · Level 2': TRIAD_CHOICES,
        'Basic Triads · Level 3': TRIAD_CHOICES,
        'Basic Triads · Level 4': TRIAD_CHOICES,
    },
    'Add 9': {
        'Add 9 · Level 1': ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'],
        'Add 9 · Level 2': ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'],
        'Add 9 · Level 3': ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'],
        'Add 9 · Level 4': ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'],
    },
    '7th Degree Chords': {
        '7th Degree Chords · Level 1': SEVENTH_CHOICES,
        '7th Degree Chords · Level 2': SEVENTH_CHOICES,
        '7th Degree Chords · Level 3': SEVENTH_CHOICES,
        '7th Degree Chords · Level 4': SEVENTH_CHOICES,
    },
    'Secondary 7th Chords': {
        'Secondary 7th Chords · Level 1': SECONDARY_SEVENTH_CHOICES,
        'Secondary 7th Chords · Level 2': SECONDARY_SEVENTH_CHOICES,
        'Secondary 7th Chords · Level 3': SECONDARY_SEVENTH_CHOICES,
        'Secondary 7th Chords · Level 4': SECONDARY_SEVENTH_CHOICES,
        General: ['Diminished 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Augmented 7th', 'Minor-Major 7th', 'Dominant 7(♭5)', 'Minor 7th', 'Major 7th', 'Major 7(♯5)', 'Diminished-Major 7th', 'Major 7(♭5)'],
    },
    '9th Degree Chords': {
        '9th Degree Chords · Level 1': NINTH_CHOICES,
        '9th Degree Chords · Level 2': NINTH_CHOICES,
        '9th Degree Chords · Level 3': NINTH_CHOICES,
        '9th Degree Chords · Level 4': NINTH_CHOICES,
    },
    'Secondary 9th Chords': {
        'Secondary 9th Chords · Level 1': SECONDARY_NINTH_CHOICES,
        'Secondary 9th Chords · Level 2': SECONDARY_NINTH_CHOICES,
        'Secondary 9th Chords · Level 3': SECONDARY_NINTH_CHOICES,
        'Secondary 9th Chords · Level 4': SECONDARY_NINTH_CHOICES,
        General: [...NINTH_CHOICES, ...SECONDARY_NINTH_CHOICES],
    },
    '11th Degree Chords': {
        '11th Degree Chords · Level 1': ELEVENTH_CHOICES,
        '11th Degree Chords · Level 2': ELEVENTH_CHOICES,
        '11th Degree Chords · Level 3': ELEVENTH_CHOICES,
        '11th Degree Chords · Level 4': ELEVENTH_CHOICES,
    },
    'Secondary 11th Chords': {
        'Secondary 11th Chords · Level 1': SECONDARY_ELEVENTH_CHOICES,
        'Secondary 11th Chords · Level 2': SECONDARY_ELEVENTH_CHOICES,
        'Secondary 11th Chords · Level 3': SECONDARY_ELEVENTH_CHOICES,
        'Secondary 11th Chords · Level 4': SECONDARY_ELEVENTH_CHOICES,
        General: ['Minor-Major 11th', 'Dominant 9(♯11)', 'Major 9(♯5,♯11)', 'Diminished 7(♭9,11)', 'Major 9(♯11)', 'Dominant 7(♭9,♯11)', 'Minor 11th', 'Diminished 7(9,11)', 'Augmented 9(♯11)', 'Minor 11(♭5)'],
    },
    '13th Degree Chords': {
        '13th Degree Chords · Level 1': THIRTEENTH_CHOICES,
        '13th Degree Chords · Level 2': THIRTEENTH_CHOICES,
        '13th Degree Chords · Level 3': THIRTEENTH_CHOICES,
        '13th Degree Chords · Level 4': THIRTEENTH_CHOICES,
    },
    Extensions: {
        'Chord Tone Recognition #1': ['1', '3', '5', '7'],
        'Chord Tone Recognition #2': ['1', '3', '5', '7'],
    },
    'Chord Progressions': {
        Cadences: ['Authentic', 'Plagal', 'Minor Plagal', 'Sub-Plagal', 'Deceptive'],
        'Passing Progression': ['2-5-1', '3-6-2', '♭5-7-3', '5-1-4', '6-2-5', '7-3-6'],
        'Dom resolution': ['Authentic', 'Tri-tone', 'Back-door', 'Double Back-door', 'Double Plagal', 'Screen door', 'Double Predominant'],
    },
    'Modal Voicings': {
        'Modal Voicings · Level 1': ['Aeolian', 'Lydian Augmented', 'Altered Dominant', 'Mixolydian', 'Lydian Diminished'],
        'Modal Voicings · Level 2': ['Gypsy major', 'Lydian Mode', 'Harmonic Major', 'Ionian mode'],
        'Modal Voicings · Level 3': ['Phrygian Dominant', 'Lydian Dominant', 'Hungarian Major', 'Romanian Major', 'Super Phrygian', 'Dominant Diminished'],
        'Modal Voicings · Level 4': ['Phrygian', 'Romanian Minor', 'Dorian', 'Altered Minor', 'Phrygian Blues'],
    },
    Scales: {
        'Tonal Modes': ['Ionian', 'Harmonic Major', 'Harmonic Minor', 'Melodic Minor', 'Aeolian'],
        'Major mode': ['Ionian', 'Dorian', 'Phrygian', 'Lydian', 'Mixolydian', 'Aeolian', 'Locrian'],
        'Dorian Mode': ['Dorian', 'Dorian ♭2', 'Dorian ♯4', 'Dorian ♯5'],
        'Lydian Mode': ['Lydian', 'Lydian Augmented', 'Lydian Dominant', 'Lydian ♯2', 'Lydian Diminished'],
        'Locrian Mode': ['Locrian', 'Locrian ♮2', 'Super Locrian', 'Locrian ♮6', 'Ultralocrian', 'Locrian 𝄫7'],
        'Phrygian Mode': ['Phrygian', 'Phrygian Dominant', 'Phrygian ♯6', 'Phrygian ♭4', 'Melodic Phrygian'],
        'Mixolydian Mode': ['Mixolydian', 'Mixolydian ♭9', 'Mixolydian ♯11', 'Mixolydian Altered', 'Mixolydian ♭13'],
    },
};

function presetFor(category, title) {
    return CATEGORY_ANSWER_PRESETS[category]?.[title] ?? SOLFEGE_PRESET;
}

function QuizIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="8.5" />
            <path d="M10 9.3l5.5 2.7-5.5 2.7z" fill="currentColor" stroke="none" />
        </svg>
    );
}

function TrashIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 7h14M9 7V5.5A1.5 1.5 0 0110.5 4h3A1.5 1.5 0 0115 5.5V7m-7 0v12a2 2 0 002 2h4a2 2 0 002-2V7" />
        </svg>
    );
}

function LessonList({ category, categorySlug, lessons, selectedSlug }) {
    return (
        <div className="w-full shrink-0 lg:w-72">
            <p className="mb-2 px-1 text-xs font-bold uppercase tracking-wide text-neutral-400">{category}</p>
            <div className="max-h-[70vh] space-y-1.5 overflow-y-auto rounded-2xl border border-black/5 bg-white p-2">
                {lessons.map((lesson) => {
                    const active = lesson.slug === selectedSlug;
                    return (
                        <Link
                            key={lesson.slug}
                            href={route('admin.audio-quiz.lesson', [categorySlug, lesson.slug])}
                            className={`flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-sm transition-colors duration-150 ${
                                active ? 'bg-neutral-900 text-white' : lesson.configured ? 'text-neutral-900 hover:bg-neutral-50' : 'text-neutral-400 hover:bg-neutral-50'
                            }`}
                        >
                            <span className="min-w-0 truncate font-semibold">{lesson.title}</span>
                            <span className={`shrink-0 text-xs ${active ? 'text-white/60' : 'text-neutral-400'}`}>
                                {lesson.configured ? lesson.question_count : '—'}
                            </span>
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}

function SettingsTabs({ quiz, difficulties }) {
    const [tab, setTab] = useState('Video');
    const { data, setData, post, processing, errors } = useForm({
        difficulty: quiz.difficulty,
        description: quiz.description ?? '',
        video_embed: quiz.video_embed ?? '',
        reference_audio: null,
        keep_reference_audio: quiz.keep_reference_audio,
        is_published: quiz.is_published,
    });

    const save = () => {
        post(route('admin.audio-quiz.settings.update', quiz.id), { forceFormData: true, preserveScroll: true });
    };

    return (
        <div className="rounded-2xl border border-black/5 bg-white p-5">
            <div className="flex flex-wrap gap-1 rounded-xl bg-neutral-100 p-1">
                {TABS.map((item) => (
                    <button
                        key={item}
                        type="button"
                        onClick={() => setTab(item)}
                        className={`rounded-lg px-3.5 py-2 text-sm font-semibold transition-colors duration-150 ${
                            tab === item ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:text-neutral-900'
                        }`}
                    >
                        {item}
                    </button>
                ))}
            </div>

            <div className="mt-5">
                {tab === 'Video' && (
                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-neutral-700">Embed video</label>
                        <p className="mb-2 text-xs text-neutral-400">Paste a Wistia, Vimeo, or other iframe/script embed code.</p>
                        <textarea
                            rows={6}
                            value={data.video_embed}
                            onChange={(e) => setData('video_embed', e.target.value)}
                            placeholder='<iframe src="..."></iframe>'
                            className="w-full rounded-lg border border-neutral-300 px-3.5 py-2.5 font-mono text-xs text-neutral-900 focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                        />
                        {errors.video_embed && <p className="mt-1.5 text-sm text-brand">{errors.video_embed}</p>}
                        <button type="button" disabled={processing} onClick={save} className="mt-3 rounded-xl bg-neutral-900 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60">
                            Save Video
                        </button>
                    </div>
                )}

                {tab === 'Difficulty' && (
                    <div className="space-y-4">
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-neutral-700">Difficulty</label>
                            <select
                                value={data.difficulty}
                                onChange={(e) => setData('difficulty', e.target.value)}
                                className="w-full max-w-xs rounded-lg border border-neutral-300 px-3.5 py-2.5 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                            >
                                {difficulties.map((d) => (
                                    <option key={d} value={d}>
                                        {d}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-neutral-700">Description</label>
                            <textarea
                                rows={3}
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                placeholder="What should the ear be listening for?"
                                className="w-full rounded-lg border border-neutral-300 px-3.5 py-2.5 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                            />
                        </div>
                        <button type="button" disabled={processing} onClick={save} className="rounded-xl bg-neutral-900 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60">
                            Save Difficulty
                        </button>
                    </div>
                )}

                {tab === 'Reference Audio' && (
                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-neutral-700">Reference audio</label>
                        {data.keep_reference_audio && quiz.reference_audio_url && !data.reference_audio && (
                            <audio controls src={quiz.reference_audio_url} className="mb-3 w-full" />
                        )}
                        <input
                            type="file"
                            accept="audio/*"
                            onChange={(e) => setData('reference_audio', e.target.files?.[0] ?? null)}
                            className="block w-full text-sm text-neutral-600 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-neutral-800"
                        />
                        {errors.reference_audio && <p className="mt-1.5 text-sm text-brand">{errors.reference_audio}</p>}
                        {(data.reference_audio || data.keep_reference_audio) && (
                            <button
                                type="button"
                                onClick={() => {
                                    setData('reference_audio', null);
                                    setData('keep_reference_audio', false);
                                }}
                                className="mt-2 text-sm font-semibold text-brand hover:text-brand-dark"
                            >
                                Remove reference audio
                            </button>
                        )}
                        <div>
                            <button type="button" disabled={processing} onClick={save} className="mt-3 rounded-xl bg-neutral-900 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60">
                                Save Reference Audio
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

// Tap-to-build melody answer for Melodic Dictation questions, mirroring the
// app's SequenceBoard + SolfegeKeyboard: admin taps notes in order to build the
// exact correct sequence instead of picking one of a flat list of options.
// Plain single-select box grid — used for both the key and the sound pickers
// below, rather than a piano-style visual.
function BoxPicker({ options, value, onChange, columns = 4 }) {
    return (
        <div className="grid gap-2" style={{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }}>
            {options.map((option) => (
                <button
                    key={option}
                    type="button"
                    onClick={() => onChange(option)}
                    className={`rounded-lg border px-3 py-2.5 text-sm font-semibold transition-colors duration-100 ${
                        value === option ? 'border-brand bg-brand text-white' : 'border-neutral-200 bg-neutral-50 text-neutral-600 hover:bg-neutral-100'
                    }`}
                >
                    {option}
                </button>
            ))}
        </div>
    );
}

// Matches the app's own fixed sound categories for Find the Key (src/lib/quiz-catalog.ts
// KEY_FINDING_SOUNDS) minus "All" — that option means "pull from every sound below,"
// not a distinct bucket of audio to upload, so there's nothing to tag questions with.
const KEY_FINDING_SOUNDS = ['Monophonic', 'Vocal Stems', 'Instruments', 'Ensemble', 'Full Song'];

// Lets the admin pick a key and a sound once, then drop every audio file for
// that key+sound combination in one go — each file becomes its own question
// with that key as the answer, instead of repeating the single-question form
// for every song.
function BulkKeyUploader({ quizId, choices }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        choices,
        answer: null,
        sound: null,
        audio: [],
    });

    const upload = () => {
        post(route('admin.audio-quiz.questions.bulk-store', quizId), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset('audio'),
        });
    };

    return (
        <div>
            <p className="mb-1.5 text-xs font-semibold text-neutral-500">Key</p>
            <BoxPicker options={choices} value={data.answer} onChange={(note) => setData('answer', note)} columns={4} />
            {errors.answer && <p className="mt-1.5 text-sm text-brand">{errors.answer}</p>}

            <p className="mb-1.5 mt-4 text-xs font-semibold text-neutral-500">Sound</p>
            <BoxPicker options={KEY_FINDING_SOUNDS} value={data.sound} onChange={(sound) => setData('sound', sound)} columns={3} />
            {errors.sound && <p className="mt-1.5 text-sm text-brand">{errors.sound}</p>}

            <div className="mt-4">
                <p className="mb-1.5 text-xs font-semibold text-neutral-500">Audio files for this key and sound</p>
                <input
                    type="file"
                    accept="audio/*"
                    multiple
                    onChange={(e) => setData('audio', Array.from(e.target.files ?? []))}
                    className="block w-full text-sm text-neutral-600 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-neutral-800"
                />
                {data.audio.length > 0 && <p className="mt-1.5 text-xs text-neutral-500">{data.audio.length} file(s) selected.</p>}
                {errors.audio && <p className="mt-1.5 text-sm text-brand">{errors.audio}</p>}
            </div>

            <button
                type="button"
                disabled={processing || !data.answer || !data.sound || data.audio.length === 0}
                onClick={upload}
                className="mt-4 rounded-xl bg-brand px-5 py-2.5 text-sm font-bold text-white transition-opacity duration-150 disabled:opacity-60"
            >
                {processing ? 'Uploading…' : `Upload ${data.audio.length || ''} Question${data.audio.length === 1 ? '' : 's'}`}
            </button>
        </div>
    );
}

function SequenceKeyboard({ length, sequence, onTap, onUndo, onClear }) {
    const slots = Array.from({ length }, (_, index) => sequence[index] ?? null);

    return (
        <div>
            <div className="flex flex-wrap gap-2">
                {slots.map((note, index) => (
                    <span
                        key={index}
                        className={`flex h-11 w-16 items-center justify-center rounded-lg border text-sm font-bold ${
                            note ? 'border-brand/40 bg-rose-50 text-brand' : 'border-dashed border-neutral-300 text-neutral-300'
                        }`}
                    >
                        {note ?? index + 1}
                    </span>
                ))}
            </div>

            <div className="relative mt-3 flex h-28 overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                {KEYBOARD_WHITE_KEYS.map((note, index) => (
                    <button
                        key={index}
                        type="button"
                        disabled={sequence.length >= length}
                        onClick={() => onTap(note)}
                        className={`flex flex-1 items-end justify-center pb-2.5 text-sm font-semibold text-neutral-600 transition-colors duration-100 hover:bg-neutral-50 active:bg-brand active:text-white disabled:pointer-events-none disabled:opacity-30 ${
                            index < KEYBOARD_WHITE_KEYS.length - 1 ? 'border-r border-neutral-200' : ''
                        }`}
                    >
                        {note}
                    </button>
                ))}
                {KEYBOARD_BLACK_KEYS.map(({ afterIndex, note }) => (
                    <button
                        key={note}
                        type="button"
                        disabled={sequence.length >= length}
                        onClick={() => onTap(note)}
                        style={{ left: blackKeyLeft(afterIndex, KEYBOARD_WHITE_KEYS.length), width: blackKeyWidth(KEYBOARD_WHITE_KEYS.length) }}
                        className="absolute top-0 flex h-[62%] items-end justify-center rounded-b-md bg-neutral-900 pb-2 text-xs font-semibold text-white transition-colors duration-100 hover:bg-neutral-800 active:bg-brand disabled:pointer-events-none disabled:opacity-30"
                    >
                        {note}
                    </button>
                ))}
            </div>

            <div className="mt-3 flex gap-4">
                <button type="button" disabled={sequence.length === 0} onClick={onUndo} className="text-sm font-semibold text-neutral-600 hover:text-neutral-900 disabled:opacity-40">
                    Undo
                </button>
                <button type="button" disabled={sequence.length === 0} onClick={onClear} className="text-sm font-semibold text-neutral-600 hover:text-neutral-900 disabled:opacity-40">
                    Clear
                </button>
            </div>
        </div>
    );
}

// Two independent single-select groups for quizzes that need both a chord
// quality and an inversion to be marked correct, matching the app's compound
// quality+inversion picker (app/practice.tsx).
function CompoundPicker({ qualities, inversions, quality, inversion, onQuality, onInversion }) {
    return (
        <div className="space-y-4">
            <div>
                <p className="mb-1.5 text-xs font-semibold text-neutral-500">Quality</p>
                <div className="flex flex-wrap gap-2">
                    {qualities.map((option) => (
                        <button
                            key={option}
                            type="button"
                            onClick={() => onQuality(option)}
                            className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors duration-100 ${
                                quality === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                            }`}
                        >
                            {option}
                        </button>
                    ))}
                </div>
            </div>
            <div>
                <p className="mb-1.5 text-xs font-semibold text-neutral-500">Inversion</p>
                <div className="flex flex-wrap gap-2">
                    {inversions.map((option) => (
                        <button
                            key={option}
                            type="button"
                            onClick={() => onInversion(option)}
                            className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors duration-100 ${
                                inversion === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                            }`}
                        >
                            {option}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}

// Multi-select chip grid — several extensions can be the correct answer at
// once, matching the app's multi-select (multi: true in app/practice.tsx).
function MultiPicker({ options, selected, onToggle }) {
    return (
        <div className="flex flex-wrap gap-2">
            {options.map((option) => {
                const active = selected.includes(option);
                return (
                    <button
                        key={option}
                        type="button"
                        onClick={() => onToggle(option)}
                        className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors duration-100 ${
                            active ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                        }`}
                    >
                        {option}
                    </button>
                );
            })}
        </div>
    );
}

// Three-step chord-name builder for "Chord naming": quality (single-select),
// degrees (multi-select, including split sharp/flat variants), and an
// exclusion (single-select) — composed live into the final chord name below.
function NamingPicker({ quality, degrees, exclude, onQuality, onToggleDegree, onExclude, preview }) {
    return (
        <div className="space-y-4">
            <div>
                <p className="mb-1.5 text-xs font-semibold text-neutral-500">Quality</p>
                <div className="flex flex-wrap gap-2">
                    {NAMING_QUALITIES.map((option) => (
                        <button
                            key={option}
                            type="button"
                            onClick={() => onQuality(option)}
                            className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors duration-100 ${
                                quality === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                            }`}
                        >
                            {option}
                        </button>
                    ))}
                </div>
            </div>
            <div>
                <p className="mb-1.5 text-xs font-semibold text-neutral-500">Degrees</p>
                <MultiPicker options={NAMING_DEGREES} selected={degrees} onToggle={onToggleDegree} />
            </div>
            <div>
                <p className="mb-1.5 text-xs font-semibold text-neutral-500">Exclude</p>
                <div className="flex flex-wrap gap-2">
                    {NAMING_EXCLUDES.map((option) => (
                        <button
                            key={option}
                            type="button"
                            onClick={() => onExclude(option)}
                            className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors duration-100 ${
                                exclude === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                            }`}
                        >
                            {option}
                        </button>
                    ))}
                </div>
            </div>
            {preview && (
                <div className="rounded-lg bg-neutral-50 px-3.5 py-2.5 text-sm">
                    <span className="text-neutral-400">Chord name: </span>
                    <span className="font-bold text-neutral-900">{preview}</span>
                </div>
            )}
        </div>
    );
}

// Builds the composed answer string for a chord progression from its slots,
// e.g. "2 Minor 7th - 5 Dominant 7th(♭9, 13) - 1 Major 7th". Returns '' while
// any slot is still missing a degree or quality.
function composeProgression(config, slots) {
    const parts = slots.map((slot, index) => {
        const degree = config.fixedDegrees ? config.fixedDegrees[index] : slot.degree;
        const quality = config.fixedQualities ? (degree ? config.fixedQualities[degree] : null) : slot.quality;
        if (!degree || !quality) return null;
        const extensions = slot.extensions ?? [];
        return `${degree} ${quality}${extensions.length ? `(${extensions.join(', ')})` : ''}`;
    });
    return parts.every(Boolean) ? parts.join(' - ') : '';
}

// Slot-by-slot chord-progression builder — matches the app's progression
// builder (app/practice.tsx): each chord gets a degree and quality (either
// picked or fixed/derived), with an optional extensions multi-select.
function ProgressionBuilder({ config, slots, onChangeSlot, preview }) {
    return (
        <div>
            <div className="flex flex-wrap items-start gap-2">
                {Array.from({ length: config.length }, (_, index) => {
                    const slot = slots[index];
                    const fixedDegree = config.fixedDegrees?.[index];
                    const degree = fixedDegree ?? slot.degree;
                    const fixedQuality = config.fixedQualities && degree ? config.fixedQualities[degree] : null;
                    const complete = Boolean(degree && (fixedQuality || slot.quality));

                    return (
                        <div key={index} className="flex items-start gap-2">
                            <div className={`w-44 shrink-0 rounded-xl border p-3 ${complete ? 'border-brand/30 bg-rose-50/40' : 'border-neutral-200'}`}>
                                <div className="mb-2 flex items-center justify-between">
                                    <p className="text-[11px] font-bold uppercase tracking-wide text-neutral-400">Chord {index + 1}</p>
                                    {complete && <span className="h-1.5 w-1.5 rounded-full bg-brand" />}
                                </div>

                                <p className="mb-1 text-[11px] font-semibold text-neutral-400">Degree</p>
                                {fixedDegree ? (
                                    <span className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-neutral-900 text-xs font-bold text-white">{fixedDegree}</span>
                                ) : (
                                    <div className="flex flex-wrap gap-1">
                                        {config.degreeOptions.map((option) => (
                                            <button
                                                key={option}
                                                type="button"
                                                onClick={() => onChangeSlot(index, 'degree', option)}
                                                className={`flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold transition-colors duration-100 ${
                                                    slot.degree === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                                                }`}
                                            >
                                                {option}
                                            </button>
                                        ))}
                                    </div>
                                )}

                                <p className="mb-1 mt-2.5 text-[11px] font-semibold text-neutral-400">Quality</p>
                                {fixedQuality ? (
                                    <span className="inline-block rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">{fixedQuality}</span>
                                ) : config.fixedQualities ? (
                                    <span className="text-xs text-neutral-400">Pick a degree first</span>
                                ) : (
                                    <select
                                        value={slot.quality ?? ''}
                                        onChange={(e) => onChangeSlot(index, 'quality', e.target.value || null)}
                                        className="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-xs focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                                    >
                                        <option value="">Select…</option>
                                        {config.qualityOptions.map((option) => (
                                            <option key={option} value={option}>
                                                {option}
                                            </option>
                                        ))}
                                    </select>
                                )}

                                {config.extensionOptions && (
                                    <>
                                        <p className="mb-1 mt-2.5 text-[11px] font-semibold text-neutral-400">Extensions</p>
                                        <div className="flex flex-wrap gap-1">
                                            {config.extensionOptions.map((option) => {
                                                const active = slot.extensions.includes(option);
                                                return (
                                                    <button
                                                        key={option}
                                                        type="button"
                                                        onClick={() =>
                                                            onChangeSlot(
                                                                index,
                                                                'extensions',
                                                                active ? slot.extensions.filter((item) => item !== option) : [...slot.extensions, option],
                                                            )
                                                        }
                                                        className={`rounded-full px-2 py-0.5 text-[11px] font-semibold transition-colors duration-100 ${
                                                            active ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                                                        }`}
                                                    >
                                                        {option}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </>
                                )}
                            </div>
                            {index < config.length - 1 && <span className="mt-8 shrink-0 text-neutral-300">&rarr;</span>}
                        </div>
                    );
                })}
            </div>
            <div className="mt-3 rounded-lg bg-neutral-50 px-3.5 py-2.5 text-sm">
                <span className="text-neutral-400">Progression: </span>
                <span className="font-bold text-neutral-900">{preview || '—'}</span>
            </div>
        </div>
    );
}

function QuestionsPanel({ quiz }) {
    const preset = presetFor(quiz.category, quiz.title);
    const isBulkKey = quiz.title === 'Find the Key #1' || quiz.title === 'Find the Key #2';
    const lengthConfig = MELODIC_DICTATION_LENGTHS[quiz.title];
    const isSequence = Boolean(lengthConfig);
    const lengthOptions = Array.isArray(lengthConfig) ? lengthConfig : null;
    const [sequenceLength, setSequenceLength] = useState(lengthOptions ? lengthOptions[0] : lengthConfig);
    const [sequence, setSequence] = useState([]);

    const compound = compoundFor(quiz.category, quiz.title);
    const isCompound = Boolean(compound);
    const [quality, setQuality] = useState(null);
    const [inversion, setInversion] = useState(null);

    const multiOptions = multiFor(quiz.category, quiz.title);
    const isMulti = Boolean(multiOptions);
    const [selectedExtensions, setSelectedExtensions] = useState([]);

    const isNaming = quiz.title === 'Chord naming';
    const [namingQuality, setNamingQuality] = useState(null);
    const [namingDegrees, setNamingDegrees] = useState([]);
    const [namingExclude, setNamingExclude] = useState(null);
    const namedChord = isNaming && namingQuality ? buildNamedChord(namingQuality, namingDegrees, namingExclude) : '';

    const isChordTone = quiz.title === 'Chord Tone Recognition #1' || quiz.title === 'Chord Tone Recognition #2';
    const [chordQuality, setChordQuality] = useState(null);

    const progressionConfig = progressionFor(quiz.category, quiz.title);
    const isProgression = Boolean(progressionConfig);
    const emptySlots = (config) => Array.from({ length: config?.length ?? 0 }, () => ({ degree: null, quality: null, extensions: [] }));
    const [progressionSlots, setProgressionSlots] = useState(() => emptySlots(progressionConfig));
    const progressionPreview = isProgression ? composeProgression(progressionConfig, progressionSlots) : '';

    const { data, setData, post, processing, errors, reset } = useForm({
        choices: preset,
        correct: 0,
        quality: null,
        audio: null,
    });

    useEffect(() => {
        if (!isChordTone) return;
        setData('quality', chordQuality);
    }, [chordQuality]);

    useEffect(() => {
        if (!isProgression) return;
        setData('choices', progressionPreview ? [progressionPreview] : []);
        setData('correct', 0);
    }, [progressionPreview]);

    useEffect(() => {
        if (!isSequence) return;
        setData('choices', sequence.length ? [sequence.join(' ')] : []);
        setData('correct', 0);
    }, [sequence]);

    useEffect(() => {
        if (!isCompound) return;
        setData('choices', quality && inversion ? [`${quality} · ${inversion}`] : []);
        setData('correct', 0);
    }, [quality, inversion]);

    useEffect(() => {
        if (!isMulti) return;
        setData('choices', selectedExtensions.length ? [selectedExtensions.join(', ')] : []);
        setData('correct', 0);
    }, [selectedExtensions]);

    useEffect(() => {
        if (!isNaming) return;
        setData('choices', namedChord ? [namedChord] : []);
        setData('correct', 0);
    }, [namedChord]);

    const chooseLength = (value) => {
        setSequenceLength(value);
        setSequence([]);
    };

    const toggleExtension = (option) => {
        setSelectedExtensions((prev) => (prev.includes(option) ? prev.filter((item) => item !== option) : [...prev, option]));
    };

    const toggleNamingDegree = (option) => {
        setNamingDegrees((prev) => (prev.includes(option) ? prev.filter((item) => item !== option) : [...prev, option]));
    };

    const changeProgressionSlot = (index, field, value) => {
        setProgressionSlots((prev) => prev.map((slot, i) => (i === index ? { ...slot, [field]: value } : slot)));
    };

    const addQuestion = () => {
        post(route('admin.audio-quiz.questions.store', quiz.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset('audio');
                setData('correct', 0);
                setSequence([]);
                if (lengthOptions) setSequenceLength(lengthOptions[0]);
                setQuality(null);
                setInversion(null);
                setSelectedExtensions([]);
                setNamingQuality(null);
                setNamingDegrees([]);
                setNamingExclude(null);
                setChordQuality(null);
                setProgressionSlots(emptySlots(progressionConfig));
            },
        });
    };

    const deleteQuestion = (id) => {
        router.delete(route('admin.audio-quiz.questions.destroy', id), { preserveScroll: true });
    };

    return (
        <div className="mt-5 space-y-5">
            <div className="rounded-2xl border border-black/5 bg-white p-5">
                <p className="text-xs font-bold uppercase tracking-wide text-neutral-400">{isBulkKey ? 'Bulk add by key' : 'Add question'}</p>

                {isBulkKey ? (
                    <div className="mt-3">
                        <p className="mb-3 text-xs text-neutral-400">Pick a key, then drop every audio file for that key at once — each one becomes its own question.</p>
                        <BulkKeyUploader quizId={quiz.id} choices={preset} />
                    </div>
                ) : (
                <>
                <div className="mt-3">
                    <label className="mb-1.5 block text-sm font-medium text-neutral-700">Answers</label>
                    {isSequence ? (
                        <>
                            <p className="mb-2 text-xs text-neutral-400">Tap the notes in order to build the correct melody for this question.</p>
                            {lengthOptions && (
                                <div className="mb-3 flex items-center gap-2">
                                    <span className="text-xs font-semibold text-neutral-500">Notes:</span>
                                    {lengthOptions.map((option) => (
                                        <button
                                            key={option}
                                            type="button"
                                            onClick={() => chooseLength(option)}
                                            className={`h-7 w-7 rounded-full text-xs font-bold transition-colors duration-100 ${
                                                sequenceLength === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                                            }`}
                                        >
                                            {option}
                                        </button>
                                    ))}
                                </div>
                            )}
                            <SequenceKeyboard
                                length={sequenceLength}
                                sequence={sequence}
                                onTap={(note) => setSequence((prev) => (prev.length < sequenceLength ? [...prev, note] : prev))}
                                onUndo={() => setSequence((prev) => prev.slice(0, -1))}
                                onClear={() => setSequence([])}
                            />
                        </>
                    ) : isCompound ? (
                        <>
                            <p className="mb-2 text-xs text-neutral-400">Pick the correct quality and inversion for this question.</p>
                            <CompoundPicker
                                qualities={compound.qualities}
                                inversions={compound.inversions}
                                quality={quality}
                                inversion={inversion}
                                onQuality={setQuality}
                                onInversion={setInversion}
                            />
                        </>
                    ) : isMulti ? (
                        <>
                            <p className="mb-2 text-xs text-neutral-400">Select every extension heard in this chord — more than one can be correct.</p>
                            <MultiPicker options={multiOptions} selected={selectedExtensions} onToggle={toggleExtension} />
                        </>
                    ) : isNaming ? (
                        <>
                            <p className="mb-2 text-xs text-neutral-400">Build the correct chord name for this question.</p>
                            <NamingPicker
                                quality={namingQuality}
                                degrees={namingDegrees}
                                exclude={namingExclude}
                                onQuality={setNamingQuality}
                                onToggleDegree={toggleNamingDegree}
                                onExclude={setNamingExclude}
                                preview={namedChord}
                            />
                        </>
                    ) : isProgression ? (
                        <>
                            <p className="mb-2 text-xs text-neutral-400">Build the correct progression, chord by chord.</p>
                            <ProgressionBuilder config={progressionConfig} slots={progressionSlots} onChangeSlot={changeProgressionSlot} preview={progressionPreview} />
                        </>
                    ) : (
                        <>
                            {isChordTone && (
                                <div className="mb-4">
                                    <p className="mb-1.5 text-xs font-semibold text-neutral-500">Chord quality (reference for the member)</p>
                                    <div className="flex flex-wrap gap-2">
                                        {CHORD_TONE_QUALITIES.map((option) => (
                                            <button
                                                key={option}
                                                type="button"
                                                onClick={() => setChordQuality(option)}
                                                className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors duration-100 ${
                                                    chordQuality === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                                                }`}
                                            >
                                                {option}
                                            </button>
                                        ))}
                                    </div>
                                    {errors.quality && <p className="mt-1.5 text-sm text-brand">{errors.quality}</p>}
                                </div>
                            )}
                            <p className="mb-2 text-xs text-neutral-400">Pick the correct option for this question.</p>
                            <div className="space-y-2">
                                {data.choices.map((choice, index) => (
                                    <label
                                        key={choice}
                                        className={`flex items-center gap-2 rounded-lg border px-3.5 py-2 text-sm transition-colors duration-150 ${
                                            data.correct === index ? 'border-brand/40 bg-rose-50' : 'border-neutral-200 bg-neutral-50'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            checked={data.correct === index}
                                            onChange={() => setData('correct', index)}
                                            className="h-4 w-4 shrink-0 text-brand focus:ring-brand"
                                        />
                                        <span className="text-neutral-700">{choice}</span>
                                    </label>
                                ))}
                            </div>
                        </>
                    )}
                    {errors.choices && <p className="mt-1.5 text-sm text-brand">{errors.choices}</p>}
                </div>

                <div className="mt-3">
                    <label className="mb-1.5 block text-sm font-medium text-neutral-700">Audio file</label>
                    <input
                        type="file"
                        accept="audio/*"
                        onChange={(e) => setData('audio', e.target.files?.[0] ?? null)}
                        className="block w-full text-sm text-neutral-600 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-neutral-800"
                    />
                    {errors.audio && <p className="mt-1.5 text-sm text-brand">{errors.audio}</p>}
                </div>

                <button
                    type="button"
                    disabled={
                        processing ||
                        (isSequence && sequence.length !== sequenceLength) ||
                        (isCompound && (!quality || !inversion)) ||
                        (isMulti && selectedExtensions.length === 0) ||
                        (isNaming && !namingQuality) ||
                        (isChordTone && !chordQuality) ||
                        (isProgression && !progressionPreview)
                    }
                    onClick={addQuestion}
                    className="mt-4 rounded-xl bg-brand px-5 py-2.5 text-sm font-bold text-white transition-opacity duration-150 disabled:opacity-60"
                >
                    + Add Question
                </button>
                </>
                )}
            </div>

            {quiz.questions.length > 0 && (
                <div className="rounded-2xl border border-black/5 bg-white p-5">
                    <p className="text-xs font-bold uppercase tracking-wide text-neutral-400">
                        {quiz.questions.length} question{quiz.questions.length === 1 ? '' : 's'} configured
                    </p>
                    <ul className="mt-3 divide-y divide-black/5">
                        {quiz.questions.map((question) => (
                            <li key={question.id} className="flex items-start justify-between gap-4 py-3">
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-semibold text-neutral-900">
                                        {question.prompt}
                                        {question.sound && <span className="ml-2 rounded-full bg-neutral-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">{question.sound}</span>}
                                    </p>
                                    <div className="mt-1 flex flex-wrap gap-1.5">
                                        {question.choices.map((choice) => (
                                            <span
                                                key={choice}
                                                className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                                    choice === question.answer ? 'bg-emerald-50 text-emerald-700' : 'bg-neutral-100 text-neutral-500'
                                                }`}
                                            >
                                                {choice}
                                            </span>
                                        ))}
                                    </div>
                                    {question.audio_url && <audio controls src={question.audio_url} className="mt-2 h-8 w-full max-w-xs" />}
                                </div>
                                <button type="button" onClick={() => deleteQuestion(question.id)} className="shrink-0 text-neutral-300 hover:text-brand">
                                    <TrashIcon className="h-4 w-4" />
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}

export default function Editor({ category, categorySlug, categories, difficulties, lessons, selectedSlug, quiz }) {
    return (
        <AppShell eyebrow="Audio Quiz" title={category} intro="Choose a lesson to manage its questions.">
            <Head title={`${category} — Audio Quiz`} />

            <div className="mb-4 flex items-center justify-between">
                <Link href={route('admin.audio-quiz.index')} className="text-sm font-semibold text-neutral-500 hover:text-neutral-900">
                    &larr; Audio Quiz
                </Link>
                <select
                    value={categorySlug}
                    onChange={(e) => router.visit(route('admin.audio-quiz.show', e.target.value))}
                    className="rounded-full border border-neutral-200 bg-white px-4 py-2 text-sm font-semibold text-neutral-700 focus:border-neutral-900 focus:outline-none"
                >
                    {categories.map((c) => (
                        <option key={c.slug} value={c.slug}>
                            {c.name}
                        </option>
                    ))}
                </select>
            </div>

            <div className="flex flex-col gap-5 lg:flex-row">
                <LessonList category={category} categorySlug={categorySlug} lessons={lessons} selectedSlug={selectedSlug} />

                <div className="min-w-0 flex-1">
                    {!selectedSlug && (
                        <div className="flex flex-col items-center rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-16 text-center">
                            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                                <QuizIcon className="h-5 w-5" />
                            </span>
                            <p className="mt-3 text-sm text-neutral-500">Select a lesson from the list to edit it.</p>
                        </div>
                    )}

                    {quiz && (
                        <>
                            <div className="mb-4">
                                <h2 className="text-xl font-bold text-neutral-900">{quiz.title}</h2>
                                <p className="text-sm text-neutral-500">{quiz.questions.length} question(s) configured</p>
                            </div>

                            <SettingsTabs key={`settings-${quiz.id}`} quiz={quiz} difficulties={difficulties} />
                            <QuestionsPanel key={`questions-${quiz.id}`} quiz={quiz} />
                        </>
                    )}
                </div>
            </div>
        </AppShell>
    );
}
