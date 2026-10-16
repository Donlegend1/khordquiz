// Fixed answer options, ported 1:1 from the real app (app/practice.tsx), shared
// by the Audio Quiz admin editor (which uses these to pick the correct answer
// when authoring a question) and the member-facing quiz-play screen (which
// uses the same sets to show the member the real fixed choices, and the same
// compound/multi/naming/progression/sequence builders to let the member
// construct an answer for question types that aren't flat multiple-choice).
export const SOLFEGE_PRESET = ['Doh', 'Reh', 'Mi', 'Fah', 'Sol', 'Lah', 'Ti'];

export const KEY_CHOICES = ['C', 'C#/Db', 'D', 'D#/Eb', 'E', 'F', 'F#/Gb', 'G', 'G#/Ab', 'A', 'A#/Bb', 'B'];
export const SOLFEGE_NOTE_CHOICES = ['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti'];

// Piano-style keyboard layout, matching app/practice.tsx's SolfegeKeyboard.
// 8 white keys (the 8th completes the octave visually, same "Do" as the 1st)
// with 5 black keys in between — none between Mi/Fa or Ti/Do, matching a real
// keyboard's semitone gaps.
export const KEYBOARD_WHITE_KEYS = ['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti', 'Do'];
export const KEYBOARD_BLACK_KEYS = [
    { afterIndex: 0, note: 'Di' },
    { afterIndex: 1, note: 'Ri' },
    { afterIndex: 3, note: 'Fi' },
    { afterIndex: 4, note: 'Si' },
    { afterIndex: 5, note: 'Li' },
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
export const SEVENTH_CHOICES = ['Diminished 7th', 'Dominant 7th', 'Minor 7(♭5)', 'Major 7th', 'Minor 7th'];
export const SECONDARY_SEVENTH_CHOICES = ['Augmented 7th', 'Minor-Major 7th', 'Diminished-Major 7th', 'Dominant 7(♭5)', 'Major 7(♯5)', 'Major 7(♭5)'];
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
};

export const TRIAD_INVERSIONS = ['Root Position', '1st Inversion', '2nd Inversion'];
export const SEVENTH_INVERSIONS = ['Root Position', '1st Inversion', '2nd Inversion', '3rd Inversion'];

// Quizzes with no single fixed-choice answer — the app has the student pick a
// chord quality AND an inversion separately (compound: { qualities, inversions }
// in app/practice.tsx).
export const COMPOUND_ANSWER_PRESETS = {
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

export function compoundFor(category, title) {
    return COMPOUND_ANSWER_PRESETS[category]?.[title] ?? null;
}

export const EXTENSION_CHOICES = ['7', '♭9', '9', '11', '♯11', '♭13', '13'];
export const DOMINANT_EXTENSION_CHOICES = ['♭9', '9', '♯9', '11', '♯11', '♭13', '13'];

// Quizzes where the student picks several extensions at once rather than one
// single answer (multi: true in app/practice.tsx).
export const MULTI_ANSWER_PRESETS = {
    Extensions: {
        'Major Chord Extentions': EXTENSION_CHOICES,
        'Minor Chord Extentions': EXTENSION_CHOICES,
        'Dominant Chord Extentions': DOMINANT_EXTENSION_CHOICES,
    },
};

export function multiFor(category, title) {
    return MULTI_ANSWER_PRESETS[category]?.[title] ?? null;
}

export const PROGRESSION_DEGREES = ['1', '2', '3', '4', '5', '6', '7'];
export const PROGRESSION_QUALITIES = ['Major', 'Minor', 'Diminished', 'Augmented', 'Sus 2', 'Sus 4'];
export const PROGRESSION_SEVENTH_QUALITIES = ['Major 7th', 'Minor 7th', 'Dominant 7th', 'Minor 7(♭5)'];
export const DIATONIC_TRIADS = { 1: 'Major', 2: 'Minor', 3: 'Minor', 4: 'Major', 5: 'Major', 6: 'Minor', 7: 'Diminished' };
export const DEGREE_EXTENSIONS = ['♭9', '9', '♯9', '11', '♯11', '♭13', '13', 'Sus 4', 'Sus 2', '♭5'];
export const RECOGNITION_QUALITIES = ['Major 7th', 'Minor 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Minor 6', 'Major 6'];

// Chord-progression quizzes with no fixed answer at all — the app has the
// student build each chord in the progression slot by slot (progression: {
// degrees, qualities, fixedDegrees, fixedQualities, extensions } in
// app/practice.tsx).
export const PROGRESSION_ANSWER_PRESETS = {
    'Chord Progressions': {
        'Diatonic Progression': { length: 4, degreeOptions: PROGRESSION_DEGREES, fixedQualities: DIATONIC_TRIADS },
        'Progression Build #1': { length: 4, degreeOptions: PROGRESSION_DEGREES, qualityOptions: PROGRESSION_QUALITIES },
        'Progression Build #2': { length: 5, degreeOptions: PROGRESSION_DEGREES, qualityOptions: PROGRESSION_SEVENTH_QUALITIES },
        'Progression recognition': { length: 3, fixedDegrees: ['2', '5', '1'], qualityOptions: RECOGNITION_QUALITIES },
        '2-5-1 chord degree': { length: 3, fixedDegrees: ['2', '5', '1'], qualityOptions: RECOGNITION_QUALITIES, extensionOptions: DEGREE_EXTENSIONS },
    },
};

export function progressionFor(category, title) {
    return PROGRESSION_ANSWER_PRESETS[category]?.[title] ?? null;
}

// Builds the composed answer string for a chord progression from its slots,
// e.g. "2 Minor 7th - 5 Dominant 7th(♭9, 13) - 1 Major 7th". Returns '' while
// any slot is still missing a degree or quality.
export function composeProgression(config, slots) {
    const parts = slots.map((slot, index) => {
        const degree = config.fixedDegrees ? config.fixedDegrees[index] : slot.degree;
        const quality = config.fixedQualities ? (degree ? config.fixedQualities[degree] : null) : slot.quality;
        if (!degree || !quality) return null;
        const extensions = slot.extensions ?? [];
        return `${degree} ${quality}${extensions.length ? `(${extensions.join(', ')})` : ''}`;
    });
    return parts.every(Boolean) ? parts.join(' - ') : '';
}

// "Chord naming" (Extensions category) has no fixed answer at all in the app —
// the student builds the chord name from three steps (naming: { qualities,
// degrees, excludes } in app/practice.tsx) that get composed into a name via
// buildNamedChord().
export const NAMING_QUALITIES = ['Major', 'Minor', 'Augmented', 'Dominant', 'Diminished'];
// Expands the app's base degree pool with the flat/sharp variants of 9, 11 and
// 13 it lets the player cycle through, so each is its own pickable chip here.
export const NAMING_DEGREES = ['♭5', '6', '7', '9', '♭9', '♯9', '11', '♭11', '♯11', '13', '♭13', '♯13', 'Sus 2', 'Sus 4'];
export const NAMING_EXCLUDES = ['No 3', 'No 5', 'None'];

export function buildNamedChord(quality, degrees, exclude) {
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

export function presetFor(category, title) {
    return CATEGORY_ANSWER_PRESETS[category]?.[title] ?? SOLFEGE_PRESET;
}
