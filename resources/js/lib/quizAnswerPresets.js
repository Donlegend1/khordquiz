import { TWO_FIVE_ONE_CONFIG } from './twoFiveOneExtensions.js';
// Fixed answer options, ported 1:1 from the real app (app/practice.tsx), shared
// by the Audio Quiz admin editor (which uses these to pick the correct answer
// when authoring a question) and the member-facing quiz-play screen (which
// uses the same sets to show the member the real fixed choices, and the same
// compound/multi/naming/progression/sequence builders to let the member
// construct an answer for question types that aren't flat multiple-choice).
export const SOLFEGE_PRESET = ['Doh', 'Reh', 'Mi', 'Fah', 'Sol', 'Lah', 'Ti'];

export const KEY_CHOICES = ['C', 'C#/Db', 'D', 'D#/Eb', 'E', 'F', 'F#/Gb', 'G', 'G#/Ab', 'A', 'A#/Bb', 'B'];
export const SOLFEGE_NOTE_CHOICES = ['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti'];

// Piano-style keyboard layout. 7 white keys, one per solfège degree — no
// trailing "Do" to complete the octave visually: these quizzes test solfège
// (scale-degree) recognition only, never which octave a note is in (stored
// answers never carry an octave marker either — see stepwiseLabelForIndex()),
// so a second "Do" key would only invite confusion about why it's there.
export const KEYBOARD_WHITE_KEYS = ['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti'];
export const KEYBOARD_BLACK_KEYS = [
    { afterIndex: 0, note: 'Di' },
    { afterIndex: 1, note: 'Ri' },
    { afterIndex: 3, note: 'Fi' },
    { afterIndex: 4, note: 'Si' },
    { afterIndex: 5, note: 'Li' },
];
export const NOTE_WHITE_KEYS = ['C', 'D', 'E', 'F', 'G', 'A', 'B'];
export const NOTE_BLACK_KEYS = [
    { afterIndex: 0, note: 'C#/Db' },
    { afterIndex: 1, note: 'D#/Eb' },
    { afterIndex: 3, note: 'F#/Gb' },
    { afterIndex: 4, note: 'G#/Ab' },
    { afterIndex: 5, note: 'A#/Bb' },
];

export const BLACK_KEY_RATIO = 0.64;
export const blackKeyWidth = (whiteKeys) => `${(100 / whiteKeys) * BLACK_KEY_RATIO}%`;
export const blackKeyLeft = (afterIndex, whiteKeys) =>
    `${((afterIndex + 1) * 100) / whiteKeys - (100 / whiteKeys) * (BLACK_KEY_RATIO / 2)}%`;

export const SOLFEGE_PAIR_CHOICES = {
    Second: ['Do - Re', 'Re - Mi', 'Mi - Fa', 'Fa - So', 'So - La', 'La - Ti', 'Ti - Do'],
    Third: ['Do - Mi', 'Re - Fa', 'Mi - So', 'Fa - La', 'So - Ti', 'La - Do', 'Ti - Re'],
    Fourth: ["Do - Fa", "Re - So", "Mi - La", "Fa - Ti", "So - Do'", "La - Re'", "Ti - Mi'"],
    Fifth: ["Do - So", "Re - La", "Mi - Ti", "Fa - Do'", "So - Re'", "La - Mi'", "Ti - Fa'"],
    Sixth: ["Do - La", "Re - Ti", "Mi - Do'", "Fa - Re'", "So - Mi'", "La - Fa'", "Ti - So'"],
    Seventh: ["Do - Ti", "Re - Do'", "Mi - Re'", "Fa - Mi'", "So - Fa'", "La - So'", "Ti - La'"],
};

export const TRIAD_CHOICES = ['Major', 'Minor', 'Diminished', 'Augmented', 'Suspended'];
// Basic Triads Levels 6 & 8 only — these are the all-12-keys inversion
// levels, with no fixed key to anchor against (unlike Levels 5/7, which stay
// on C throughout). Augmented and Suspended are left out: an augmented triad
// is symmetric (stacked major 3rds), so root/1st/2nd inversion all have the
// identical relative interval shape — "E augmented root position" and "C
// augmented 1st inversion" are the same pitch classes, indistinguishable
// without absolute pitch. A Sus2 chord's 2nd inversion has that same
// relative shape as some other root's Sus4 in root position, and Sus4 was
// never one of this quiz's own answer options to tell them apart with.
// Major/Minor/Diminished don't have this problem — each inversion keeps its
// own distinct shape regardless of root.
export const TRIAD_CHOICES_NO_SYMMETRIC = ['Major', 'Minor', 'Diminished'];
export const SEVENTH_CHOICES = ['Diminished 7th', 'Dominant 7th', 'Minor 7(♭5)', 'Major 7th', 'Minor 7th'];
// 7th Degree Chords Levels 6 & 8 only — these are the all-12-keys inversion
// levels, with no fixed key to anchor against. Diminished 7th is left out:
// it's a fully symmetric chord (stacked minor 3rds), so root/1st/2nd/3rd
// inversion all have the identical relative interval shape — same reasoning
// as Basic Triads excluding Augmented/Suspended from its own Levels 6/8.
export const SEVENTH_CHOICES_NO_SYMMETRIC = ['Dominant 7th', 'Minor 7(♭5)', 'Major 7th', 'Minor 7th'];
export const SECONDARY_SEVENTH_CHOICES = ['Augmented 7th', 'Minor-Major 7th', 'Diminished-Major 7th', 'Dominant 7(♭5)', 'Major 7(♯5)', 'Major 7(♭5)'];
// Secondary 7th Chords Levels 6 & 8 only — these are the all-12-keys
// inversion levels, with no fixed key to anchor against. Dominant 7(♭5) is
// left out: it's a tritone-symmetric chord (two tritones stacked a major
// 3rd apart — [0,4,6,10] maps onto itself transposed a tritone), so its
// inversions are indistinguishable from each other by ear — same reasoning
// as 7th Degree Chords excluding Diminished 7th from its own Levels 6/8.
export const SECONDARY_SEVENTH_CHOICES_NO_SYMMETRIC = ['Augmented 7th', 'Minor-Major 7th', 'Diminished-Major 7th', 'Major 7(♯5)', 'Major 7(♭5)'];
export const NINTH_CHOICES = ['Major 9th', 'Minor 9th', 'Diminished 7(9)', 'Dominant 9th', 'Minor 9(♭5)', 'Dominant 7(♭9)'];
export const SECONDARY_NINTH_CHOICES = ['Major 9(♯5)', 'Diminished-Major 9th', 'Dominant 9(♭5)', 'Minor-Major 9th', 'Augmented 9th', 'Major 9(♭5)'];
export const ELEVENTH_CHOICES = ['Major 9(♯11)', 'Minor 11th', 'Diminished 7(9,11)', 'Dominant 9(♯11)', 'Minor 11(♭5)', 'Dominant 7(♭9,♯11)'];
export const SECONDARY_ELEVENTH_CHOICES = ['Major 9(♯5,♯11)', 'Diminished 7(♭9,11)', 'Minor-Major 11th', 'Augmented 9(♯11)'];
export const THIRTEENTH_CHOICES = ['Major 13(♯11)', 'Minor 13th', 'Dominant 13(♯11)', 'Dominant 13(♭9,♯11)'];

// Fixed lengths for most Melodic Dictation lessons; Dual-tonic/Chordal melodies
// vary question to question, so those list the lengths to pick from per question.
export const MELODIC_DICTATION_LENGTHS = {
    '3-Note Stepwise': 3,
    '3-Note with Skips': 3,
    '5-Note Melodies': 5,
    'Dual-tonic melodies': [4, 5, 6],
    'Chordal melodies': [4, 5, 6],
    'Chordal Bass Lines': [4, 5, 6],
    'Dyad Melodies': 5,
};

export const TRIAD_INVERSIONS = ['Root Position', '1st Inversion', '2nd Inversion'];
export const SEVENTH_INVERSIONS = ['Root Position', '1st Inversion', '2nd Inversion', '3rd Inversion'];

// Quizzes with no single fixed-choice answer — the app has the student pick a
// chord quality AND an inversion separately (compound: { qualities, inversions }
// in app/practice.tsx).
export const ADD9_CHOICES = ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'];

export const COMPOUND_ANSWER_PRESETS = {
    'Basic Triads': {
        'Basic Triads · Level 5': { qualities: TRIAD_CHOICES, inversions: TRIAD_INVERSIONS },
        'Basic Triads · Level 6': { qualities: TRIAD_CHOICES_NO_SYMMETRIC, inversions: TRIAD_INVERSIONS },
        'Basic Triads · Level 7': { qualities: TRIAD_CHOICES, inversions: TRIAD_INVERSIONS },
        'Basic Triads · Level 8': { qualities: TRIAD_CHOICES_NO_SYMMETRIC, inversions: TRIAD_INVERSIONS },
    },
    // Add 9 never asks the member to identify an inversion/position —
    // per explicit instruction, that's Basic Triads' thing only. Levels 3/4/
    // 7/8 still play chords in all 4 rotated positions for audio variety,
    // but the answer is flat quality-only — see CATEGORY_ANSWER_PRESETS
    // below, not here.
    '7th Degree Chords': {
        '7th Degree Chords · Level 5': { qualities: SEVENTH_CHOICES, inversions: SEVENTH_INVERSIONS },
        '7th Degree Chords · Level 6': { qualities: SEVENTH_CHOICES_NO_SYMMETRIC, inversions: SEVENTH_INVERSIONS },
        '7th Degree Chords · Level 7': { qualities: SEVENTH_CHOICES, inversions: SEVENTH_INVERSIONS },
        '7th Degree Chords · Level 8': { qualities: SEVENTH_CHOICES_NO_SYMMETRIC, inversions: SEVENTH_INVERSIONS },
    },
    'Secondary 7th Chords': {
        'Secondary 7th Chords · Level 5': { qualities: SECONDARY_SEVENTH_CHOICES, inversions: SEVENTH_INVERSIONS },
        'Secondary 7th Chords · Level 6': { qualities: SECONDARY_SEVENTH_CHOICES_NO_SYMMETRIC, inversions: SEVENTH_INVERSIONS },
        'Secondary 7th Chords · Level 7': { qualities: SECONDARY_SEVENTH_CHOICES, inversions: SEVENTH_INVERSIONS },
        'Secondary 7th Chords · Level 8': { qualities: SECONDARY_SEVENTH_CHOICES_NO_SYMMETRIC, inversions: SEVENTH_INVERSIONS },
        // General does NOT use this compound picker — see
        // CATEGORY_ANSWER_PRESETS['Secondary 7th Chords'].General below. It
        // plays all 4 rotated positions for audio variety, but only tests
        // quality, never inversion.
    },
};

export function compoundFor(category, title) {
    return COMPOUND_ANSWER_PRESETS[category]?.[title] ?? null;
}

export const EXTENSION_CHOICES = ['6', '7', '♭9', '9', '11', '♯11', '♭13', '13'];
export const DOMINANT_EXTENSION_CHOICES = ['♭9', '9', '♯9', '11', '♯11', '♭13', '13'];

// Quizzes where the student picks several extensions at once rather than one
// single answer (multi: true in app/practice.tsx).
export const MULTI_ANSWER_PRESETS = {
    'Major Chord Extensions': {
        'Major Chord Extentions': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 1': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 2': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 3': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 4': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 5': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 6': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 7': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 8': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 9': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 10': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 11': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 12': EXTENSION_CHOICES,
        'Major Chord Extentions · Level 13': EXTENSION_CHOICES,
    },
    'Minor Chord Extensions': {
        'Minor Chord Extentions': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 1': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 2': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 3': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 4': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 5': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 6': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 7': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 8': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 9': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 10': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 11': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 12': EXTENSION_CHOICES,
        'Minor Chord Extentions · Level 13': EXTENSION_CHOICES,
    },
    'Dominant Chord Extensions': {
        'Dominant Chord Extentions': DOMINANT_EXTENSION_CHOICES,
        'Dominant Chord Extentions · Level 1': DOMINANT_EXTENSION_CHOICES,
        'Dominant Chord Extentions · Level 2': DOMINANT_EXTENSION_CHOICES,
        'Dominant Chord Extentions · Level 3': DOMINANT_EXTENSION_CHOICES,
        'Dominant Chord Extentions · Level 4': DOMINANT_EXTENSION_CHOICES,
        'Dominant Chord Extentions · Level 5': DOMINANT_EXTENSION_CHOICES,
        'Dominant Chord Extentions · Level 6': DOMINANT_EXTENSION_CHOICES,
        'Dominant Chord Extentions · Level 7': DOMINANT_EXTENSION_CHOICES,
        'Dominant Chord Extentions · Level 8': DOMINANT_EXTENSION_CHOICES,
    },
};

export function multiFor(category, title) {
    return MULTI_ANSWER_PRESETS[category]?.[title] ?? null;
}

export const PROGRESSION_DEGREES = ['1', '2', '3', '4', '5', '6', '7'];
export const PROGRESSION_QUALITIES = ['Major', 'Minor', 'Diminished', 'Augmented', 'Sus 2', 'Sus 4'];
export const PROGRESSION_SEVENTH_QUALITIES = ['Major 7th', 'Minor 7th', 'Dominant 7th', 'Minor 7(♭5)', '7 Sus4'];
export const DIATONIC_TRIADS = { 1: 'Major', 2: 'Minor', 3: 'Minor', 4: 'Major', 5: 'Major', 6: 'Minor', 7: 'Diminished' };
export const DEGREE_EXTENSIONS = ['♭9', '9', '♯9', '11', '♯11', '♭13', '13', 'Sus 4', 'Sus 2', '♭5'];
export const RECOGNITION_QUALITIES = ['Major 7th', 'Minor 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Minor 6', 'Major 6'];

// Chord-progression quizzes with no fixed answer at all — the app has the
// student build each chord in the progression slot by slot (progression: {
// degrees, qualities, fixedDegrees, fixedQualities, extensions } in
// app/practice.tsx).
// "Chordal Progressions" answers each chord with just its QUALITY (no
// root/degree — this tests chord-quality recognition only), via
// `qualityOnly: true`, reusing TRIAD_CHOICES/SEVENTH_CHOICES' exact labels so
// the vocabulary stays consistent with the rest of this category. Its chord
// count is five per question, using only the five-chord sources from
// Chordal Melodies / Chordal Bass Lines.
export const CHORDAL_PROGRESSION_QUALITIES = ['Major', 'Minor', 'Diminished', 'Augmented', ...SEVENTH_CHOICES, ...SECONDARY_SEVENTH_CHOICES];

export const PROGRESSION_ANSWER_PRESETS = {
    'Chord Progressions': {
        'Diatonic Progression #1': { length: 4, degreeOptions: PROGRESSION_DEGREES, fixedQualities: DIATONIC_TRIADS },
        'Diatonic Progression #2': { length: 4, degreeOptions: PROGRESSION_DEGREES, fixedQualities: { 1: 'Major 7th', 2: 'Minor 7th', 3: 'Minor 7th', 4: 'Major 7th', 5: 'Dominant 7th', 6: 'Minor 7th', 7: 'Minor 7(♭5)' } },
        'Chordal Progressions': { length: 5, qualityOnly: true, qualityOptions: CHORDAL_PROGRESSION_QUALITIES },
        'Progression Build #1': { length: 4, degreeOptions: [...PROGRESSION_DEGREES, '♭7'], qualityOptions: ['Major', 'Minor', 'Diminished', 'Sus4'], suspendedEquivalents: true },
        'Progression Build #2': { length: 4, degreeOptions: PROGRESSION_DEGREES, qualityOptions: PROGRESSION_SEVENTH_QUALITIES },
        'Progression Recognition #1': { length: 3, fixedDegrees: ['2', '5', '1'], qualityOptions: RECOGNITION_QUALITIES, qualityOptionsByDegree: { 2: ['Minor 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Minor 6'], 5: ['Dominant 7th', 'Minor 7th', 'Minor 7(♭5)'], 1: ['Dominant 7th', 'Minor 7th', 'Major 7th', 'Major 6', 'Minor 6'] } },
        'Progression Recognition #2': { length: 3, fixedDegrees: ['2', '5', '1'], qualityOptions: RECOGNITION_QUALITIES, qualityOptionsByDegree: { 2: ['Minor 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Minor 6'], 5: ['Dominant 7th', 'Minor 7th', 'Minor 7(♭5)'], 1: ['Dominant 7th', 'Minor 7th', 'Major 7th', 'Major 6', 'Minor 6'] } },
        'Progression Recognition #3': { length: 3, fixedDegrees: ['2', '5', '1'], qualityOptions: RECOGNITION_QUALITIES, qualityOptionsByDegree: { 2: ['Minor 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Minor 6'], 5: ['Dominant 7th', 'Minor 7th', 'Minor 7(♭5)'], 1: ['Dominant 7th', 'Minor 7th', 'Major 7th', 'Major 6', 'Minor 6'] } },
        'Progression Recognition #4': { length: 3, fixedDegrees: ['2', '5', '1'], qualityOptions: RECOGNITION_QUALITIES, qualityOptionsByDegree: { 2: ['Minor 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Minor 6'], 5: ['Dominant 7th', 'Minor 7th', 'Minor 7(♭5)'], 1: ['Dominant 7th', 'Minor 7th', 'Major 7th', 'Major 6', 'Minor 6'] } },
        '2-5-1 chord degree': TWO_FIVE_ONE_CONFIG,
    },
};

export function progressionFor(category, title) {
    return PROGRESSION_ANSWER_PRESETS[category]?.[title] ?? null;
}

// Builds the composed answer string for a chord progression from its slots,
// e.g. "2 Minor 7th - 5 Dominant 7th(♭9, 13) - 1 Major 7th". Returns '' while
// any slot is still missing a degree or quality. `qualityOnly` configs (e.g.
// Chordal Progressions) skip the degree entirely and compose from quality alone.
export function composeProgression(config, slots) {
    const parts = slots.map((slot, index) => {
        if (config.qualityOnly) {
            if (!slot.quality) return null;
            const extensions = slot.extensions ?? [];
            return `${slot.quality}${extensions.length ? `(${extensions.join(', ')})` : ''}`;
        }
        const degree = config.fixedDegrees ? config.fixedDegrees[index] : slot.degree;
        const quality = config.fixedQualities ? (degree ? config.fixedQualities[degree] : null) : slot.quality;
        if (!degree || !quality) return null;
        const extensions = slot.extensions ?? [];
        if (config.requireExtensions && extensions.length === 0) return null;
        return `${degree} ${quality}${extensions.length ? `(${extensions.join(', ')})` : ''}`;
    });
    return parts.every(Boolean) ? parts.join(' - ') : '';
}

export {
    NAMING_QUALITIES, NAMING_DEGREES, NAMING_EXCLUDES,
    namingDegreeOptions, namingExcludeOptions, namingDisplayQuality, toggleNamingDegree, validNamingSelection,
    encodeNamingAnswer, decodeNamingAnswer, buildNamedChord,
} from './chordNaming.js';

// Chord Tone Recognition asks which scale degree (1/3/5/7) is highlighted, but
// that's only meaningful with a reference chord quality — the admin picks one
// per question so the member hears e.g. "a Major 7th chord" before answering
// (baked into the question's prompt text), instead of guessing the degree with
// no harmonic context. Covers both the 5 primary 7th-chord qualities and the 6
// secondary ones.
export const CHORD_TONE_QUALITIES = [...SEVENTH_CHOICES, ...SECONDARY_SEVENTH_CHOICES];

export const CATEGORY_ANSWER_PRESETS = {
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
    },
    'Basic Triads': {
        'Basic Triads · Level 1': TRIAD_CHOICES,
        'Basic Triads · Level 2': TRIAD_CHOICES,
        'Basic Triads · Level 3': TRIAD_CHOICES,
        'Basic Triads · Level 4': TRIAD_CHOICES,
    },
    // Flat quality-only for all 8 levels — Add 9 never tests inversion/
    // position, even on the levels whose audio cycles through all 4
    // rotated positions (3/4/7/8) for variety.
    'Add 9': {
        'Add 9 · Level 1': ADD9_CHOICES,
        'Add 9 · Level 2': ADD9_CHOICES,
        'Add 9 · Level 3': ADD9_CHOICES,
        'Add 9 · Level 4': ADD9_CHOICES,
        'Add 9 · Level 5': ADD9_CHOICES,
        'Add 9 · Level 6': ADD9_CHOICES,
        'Add 9 · Level 7': ADD9_CHOICES,
        'Add 9 · Level 8': ADD9_CHOICES,
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
        // Combines 7th Degree Chords' 5 qualities + Secondary 7th Chords'
        // 6 = 11 total. Quality-only — never tests inversion, even though
        // the underlying audio cycles through all 4 rotated positions.
        General: [...SEVENTH_CHOICES, ...SECONDARY_SEVENTH_CHOICES],
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
    'Chord Tone Recognition': {
        'Chord Tone Recognition #1': ['1', '3', '5', '7'],
        'Chord Tone Recognition #2': ['1', '3', '5', '7'],
    },
    'Chord Progressions': {
        Cadences: ['Authentic', 'Plagal', 'Minor Plagal', 'Sub-Plagal'],
    },
    'Modal Harmony': {
        'Modal Harmony #1': ['Phrygian · ♭II Major', 'Lydian Dominant · II Major', 'Aeolian · ♭III Major', 'Lydian Augmented · III Major', 'Ionian · V Major'],
        'Modal Harmony #2': ['Phrygian · ♭II Major', 'Lydian Dominant · II Major', 'Aeolian · ♭III Major', 'Lydian Augmented · III Major', 'Ionian · V Major'],
        'Modal Harmony #3': ['Ionian · IV Major', 'Altered Dominant · ♭V Major', 'Dominant Diminished · VI Major', 'Mixolydian · ♭VII Major', 'Lydian Diminished · VII Major'],
        'Modal Harmony #4': ['Ionian · IV Major', 'Altered Dominant · ♭V Major', 'Dominant Diminished · VI Major', 'Mixolydian · ♭VII Major', 'Lydian Diminished · VII Major'],
        'Modal Harmony #5': ['Phrygian Dominant · ♭II Minor', 'Dorian · II Minor', 'Locrian · ♭III Minor', 'Ionian · III Minor', 'Aeolian · IV Minor'],
        'Modal Harmony #6': ['Phrygian Dominant · ♭II Minor', 'Dorian · II Minor', 'Locrian · ♭III Minor', 'Ionian · III Minor', 'Aeolian · IV Minor'],
        'Modal Harmony #7': ['Locrian ♮6 · ♭V Minor', 'Mixolydian · V Minor', 'Harmonic Minor · ♭VI Minor', 'Ionian · VI Minor', 'Phrygian · ♭VII Minor', 'Lydian · VII Minor'],
        'Modal Harmony #8': ['Locrian ♮6 · ♭V Minor', 'Mixolydian · V Minor', 'Harmonic Minor · ♭VI Minor', 'Ionian · VI Minor', 'Phrygian · ♭VII Minor', 'Lydian · VII Minor'],
    },
    'Modal Voicings': {
        'Lydian Mode': ['Lydian', 'Lydian ♯2', 'Lydian Augmented', 'Lydian Dominant', 'Lydian Diminished'],
        'Lydian Mode #1': ['Lydian', 'Lydian ♯2', 'Lydian Augmented', 'Lydian Dominant', 'Lydian Diminished'],
        'Lydian Mode #2': ['Lydian', 'Lydian ♯2', 'Lydian Augmented', 'Lydian Dominant', 'Lydian Diminished'],
        'Mixolydian Mode': ['Mixolydian', 'Mixolydian ♭2', 'Mixolydian ♭6', 'Mixolydian ♭2 ♭6', 'Mixolydian ♯4'],
        'Mixolydian Mode #1': ['Mixolydian', 'Mixolydian ♭2', 'Mixolydian ♭6', 'Mixolydian ♭2 ♭6', 'Mixolydian ♯4'],
        'Mixolydian Mode #2': ['Mixolydian', 'Mixolydian ♭2', 'Mixolydian ♭6', 'Mixolydian ♭2 ♭6', 'Mixolydian ♯4'],
        'Phrygian Mode': ['Phrygian', 'Phrygian Dominant', 'Phrygian (♭4)', 'Phrygian ♮6', 'Phrygian ♮7'],
        'Phrygian Mode #1': ['Phrygian', 'Phrygian Dominant', 'Phrygian (♭4)', 'Phrygian ♮6', 'Phrygian ♮7'],
        'Phrygian Mode #2': ['Phrygian', 'Phrygian Dominant', 'Phrygian (♭4)', 'Phrygian ♮6', 'Phrygian ♮7'],
        'Locrian Mode': ['Locrian', 'Locrian ♮2', 'Locrian ♮6', 'Locrian Dominant', 'Super Locrian'],
        'Locrian Mode #1': ['Locrian', 'Locrian ♮2', 'Locrian ♮6', 'Locrian Dominant', 'Super Locrian'],
        'Locrian Mode #2': ['Locrian', 'Locrian ♮2', 'Locrian ♮6', 'Locrian Dominant', 'Super Locrian'],
        'Dorian Mode': ['Dorian', 'Dorian ♭2', 'Dorian ♯4', 'Dorian ♭5', 'Dorian ♮7'],
        'Dorian Mode #1': ['Dorian', 'Dorian ♭2', 'Dorian ♯4', 'Dorian ♭5', 'Dorian ♮7'],
        'Dorian Mode #2': ['Dorian', 'Dorian ♭2', 'Dorian ♯4', 'Dorian ♭5', 'Dorian ♮7'],
        'Aeolian Mode': ['Aeolian', 'Aeolian ♭2', 'Aeolian ♮6', 'Aeolian ♮7', 'Aeolian Dominant', 'Aeolian ♭5'],
        'Aeolian Mode #1': ['Aeolian', 'Aeolian ♭2', 'Aeolian ♮6', 'Aeolian ♮7', 'Aeolian Dominant', 'Aeolian ♭5'],
        'Aeolian Mode #2': ['Aeolian', 'Aeolian ♭2', 'Aeolian ♮6', 'Aeolian ♮7', 'Aeolian Dominant', 'Aeolian ♭5'],
        'Ionian Mode': ['Ionian', 'Ionian ♭2', 'Ionian ♯2', 'Ionian Augmented', 'Ionian ♭6', 'Ionian ♭2 ♭6'],
        'Ionian Mode #1': ['Ionian', 'Ionian ♭2', 'Ionian ♯2', 'Ionian Augmented', 'Ionian ♭6', 'Ionian ♭2 ♭6'],
        'Ionian Mode #2': ['Ionian', 'Ionian ♭2', 'Ionian ♯2', 'Ionian Augmented', 'Ionian ♭6', 'Ionian ♭2 ♭6'],
        'Major Modes': ['Romanian Major', 'Hungarian Major', 'Byzantine Major', 'Harmonic Major', 'Melodic Major', 'Major Augmented'],
        'Major Modes #1': ['Romanian Major', 'Hungarian Major', 'Byzantine Major', 'Harmonic Major', 'Melodic Major', 'Major Augmented'],
        'Major Modes #2': ['Romanian Major', 'Hungarian Major', 'Byzantine Major', 'Harmonic Major', 'Melodic Major', 'Major Augmented'],
        'Minor Modes': ['Natural Minor', 'Harmonic Minor', 'Melodic Minor', 'Hungarian Minor', 'Romanian Minor', 'Neapolitan Minor'],
        'Minor Modes #1': ['Natural Minor', 'Harmonic Minor', 'Melodic Minor', 'Hungarian Minor', 'Romanian Minor', 'Neapolitan Minor'],
        'Minor Modes #2': ['Natural Minor', 'Harmonic Minor', 'Melodic Minor', 'Hungarian Minor', 'Romanian Minor', 'Neapolitan Minor'],
    },
    Scales: {
        'Ionian and Lydian': ['Ionian', 'Lydian'],
        'Dorian and Aeolian': ['Dorian', 'Aeolian'],
        'Phrygian and Locrian': ['Phrygian', 'Locrian'],
        'Tonal Modes #1': ['Ionian', 'Harmonic Major', 'Harmonic Minor', 'Melodic Minor', 'Aeolian'],
        'Tonal Modes #2': ['Ionian', 'Harmonic Major', 'Harmonic Minor', 'Melodic Minor', 'Aeolian'],
        'Major Scales #1': ['Ionian', 'Dorian', 'Phrygian', 'Lydian', 'Mixolydian', 'Aeolian', 'Locrian'],
        'Major Scales #2': ['Ionian', 'Dorian', 'Phrygian', 'Lydian', 'Mixolydian', 'Aeolian', 'Locrian'],
        'Dorian Scales #1': ['Dorian', 'Dorian ♭2', 'Dorian ♯4', 'Dorian ♭5', 'Dorian ♮7'],
        'Dorian Scales #2': ['Dorian', 'Dorian ♭2', 'Dorian ♯4', 'Dorian ♭5', 'Dorian ♮7'],
        'Lydian Scales #1': ['Lydian', 'Lydian ♯2', 'Lydian Augmented', 'Lydian Dominant', 'Lydian Diminished'],
        'Lydian Scales #2': ['Lydian', 'Lydian ♯2', 'Lydian Augmented', 'Lydian Dominant', 'Lydian Diminished'],
        'Locrian Scales #1': ['Locrian', 'Locrian ♮2', 'Locrian ♮6', 'Locrian Dominant', 'Super Locrian'],
        'Locrian Scales #2': ['Locrian', 'Locrian ♮2', 'Locrian ♮6', 'Locrian Dominant', 'Super Locrian'],
        'Ionian Scales #1': ['Ionian', 'Ionian ♭2', 'Ionian ♯2', 'Ionian Augmented', 'Ionian ♭6', 'Ionian ♭2 ♭6'],
        'Ionian Scales #2': ['Ionian', 'Ionian ♭2', 'Ionian ♯2', 'Ionian Augmented', 'Ionian ♭6', 'Ionian ♭2 ♭6'],
        'Phrygian Scales #1': ['Phrygian', 'Phrygian Dominant', 'Phrygian (♭4)', 'Phrygian ♮6', 'Phrygian ♮7'],
        'Phrygian Scales #2': ['Phrygian', 'Phrygian Dominant', 'Phrygian (♭4)', 'Phrygian ♮6', 'Phrygian ♮7'],
        'Mixolydian Scales #1': ['Mixolydian', 'Mixolydian ♭2', 'Mixolydian ♭6', 'Mixolydian ♭2 ♭6', 'Mixolydian ♯4'],
        'Mixolydian Scales #2': ['Mixolydian', 'Mixolydian ♭2', 'Mixolydian ♭6', 'Mixolydian ♭2 ♭6', 'Mixolydian ♯4'],
        'Aeolian Scales #1': ['Aeolian', 'Aeolian ♭2', 'Aeolian ♮6', 'Aeolian ♮7', 'Aeolian Dominant', 'Aeolian ♭5'],
        'Aeolian Scales #2': ['Aeolian', 'Aeolian ♭2', 'Aeolian ♮6', 'Aeolian ♮7', 'Aeolian Dominant', 'Aeolian ♭5'],
    },
};

export function presetFor(category, title) {
    return CATEGORY_ANSWER_PRESETS[category]?.[title] ?? SOLFEGE_PRESET;
}

export function suspendedChordMatches(submitted, correct) {
    if (submitted === correct) return true;
    const pitches = (chord) => {
        const match = /^(♭7|[1-7]) Sus ?([24])$/.exec(chord ?? '');
        if (!match) return null;
        const root = { 1: 0, 2: 2, 3: 4, 4: 5, 5: 7, 6: 9, 7: 11, '♭7': 10 }[match[1]];
        return [root, (root + (match[2] === '2' ? 2 : 5)) % 12, (root + 7) % 12].sort((a, b) => a - b).join(',');
    };
    const expected = pitches(correct);
    return expected !== null && expected === pitches(submitted);
}
