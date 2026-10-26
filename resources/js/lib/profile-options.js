// Shared between Settings.jsx (where a member can change these later) and
// Auth/ProfileSetup.jsx (the one-time onboarding step right after signup) —
// single source so the two pickers never drift apart. Keep in sync by hand
// with the mobile app's own copies (app/settings.tsx's LANGUAGES, and
// src/lib/quiz-progress.ts's INSTRUMENT_GROUPS, trimmed there to the
// instruments that app actually has).
export const LANGUAGES = ['English', 'Français', 'Español', 'Português', 'Deutsch', 'Italiano', '한국어', '中文', 'हिन्दी', '日本語'];

// Same families as the quiz-preview sound picker (resources/js/Pages/Member/QuizCategory.jsx's
// SOUND_CATEGORIES) — kept as a separate constant since each picker's shell is its
// own component, but the groupings themselves should stay identical.
export const INSTRUMENT_GROUPS = [
    { name: 'Piano', icon: '🎹', options: ['Piano', 'EP', 'Upright Piano', 'Organ'] },
    { name: 'Guitar', icon: '🎸', options: ['Guitar', 'Electric Guitar', 'Harpejji', 'Bass Guitar'] },
    { name: 'Strings', icon: '🎻', options: ['Violin', 'Strings', 'Cello', 'Upright Bass'] },
    { name: 'Brass', icon: '🎺', options: ['Saxophone', 'Trombone', 'Tuba', 'Trumpet'] },
    { name: 'Vocals', icon: '🎤', options: ['Voice', 'Audience Choir', 'Ensemble Choir', 'Sustained Choir'] },
    { name: 'Percussion', icon: '🥁', options: ['Marimba', 'Steel Drum', 'Celesta', 'Vibraphone'] },
    { name: 'Woodwinds', icon: '🪈', options: ['Flute', 'Clarinet', 'Oboe', 'Bassoon'] },
];

// Display-only — the underlying value stays "Piano"/"EP" everywhere else; this just
// makes the two piano options clearly distinct from each other in any picker.
export const INSTRUMENT_LABELS = { Piano: 'Grand Piano', EP: 'Rhodes', Guitar: 'Acoustic Guitar', Saxophone: 'Tenor Saxophone', Voice: 'Solo Choir' };

export const SKILL_LEVELS = ['Beginner', 'Intermediate', 'Advanced'];

// Same 10 options (ids, colors, emoji standing in for the mobile app's
// MaterialCommunityIcons glyphs) as the mobile app's AVATAR_OPTIONS
// (src/components/avatar.tsx) — kept in sync by hand.
export const AVATAR_OPTIONS = [
    { id: 'piano', label: 'Piano', emoji: '🎹', bg: '#FDEDEE', fg: '#C81E1E' },
    { id: 'electric-guitar', label: 'Electric guitar', emoji: '🎸', bg: '#E8F0FE', fg: '#1A56DB' },
    { id: 'note', label: 'Music note', emoji: '🎵', bg: '#F3EDFF', fg: '#6D4AFF' },
    { id: 'headphones', label: 'Headphones', emoji: '🎧', bg: '#E6F6F8', fg: '#0E7490' },
    { id: 'metronome', label: 'Metronome', emoji: '⏱️', bg: '#FFF4E5', fg: '#B45309' },
    { id: 'saxophone', label: 'Saxophone', emoji: '🎷', bg: '#FEF7D9', fg: '#A16207' },
    { id: 'trumpet', label: 'Trumpet', emoji: '🎺', bg: '#FDEEF6', fg: '#BE185D' },
    { id: 'violin', label: 'Violin', emoji: '🎻', bg: '#F5EDE6', fg: '#9A4F1E' },
    { id: 'acoustic-guitar', label: 'Acoustic guitar', emoji: '🎸', bg: '#EAF7EE', fg: '#1E8E3E' },
    { id: 'microphone', label: 'Microphone', emoji: '🎤', bg: '#EEF0F3', fg: '#334155' },
];
