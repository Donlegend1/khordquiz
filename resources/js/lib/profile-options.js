import { tk } from '@/lib/i18n';

// Shared between Settings.jsx (where a member can change these later) and
// Auth/ProfileSetup.jsx (the one-time onboarding step right after signup) —
// single source so the two pickers never drift apart. Keep in sync by hand
// with the mobile app's own copies (app/settings.tsx's LANGUAGES, and
// src/lib/quiz-progress.ts's INSTRUMENT_GROUPS, trimmed there to the
// instruments that app actually has).
//
// Values are what's stored/sent to the server, so they stay English; every
// display string is a tk() key — pickers render them with t(value). LANGUAGES
// are native names and are never translated.
export const LANGUAGES = ['English', 'Français', 'Español', 'Português', 'Deutsch', 'Nederlands', 'Italiano', '한국어', '中文', 'हिन्दी', '日本語'];

// Same families as the quiz-preview sound picker (resources/js/Pages/Member/QuizCategory.jsx's
// SOUND_CATEGORIES) — kept as a separate constant since each picker's shell is its
// own component, but the groupings themselves should stay identical.
export const INSTRUMENT_GROUPS = [
    { name: tk('Piano'), icon: '🎹', options: [tk('Piano'), tk('EP'), tk('Upright Piano'), tk('Organ')] },
    { name: tk('Guitar'), icon: '🎸', options: [tk('Guitar'), tk('Electric Guitar'), tk('Harpejji'), tk('Bass Guitar')] },
    { name: tk('Strings'), icon: '🎻', options: [tk('Violin'), tk('Strings'), tk('Cello'), tk('Upright Bass')] },
    { name: tk('Brass'), icon: '🎺', options: [tk('Saxophone'), tk('Trombone'), tk('Tuba'), tk('Trumpet')] },
    { name: tk('Vocals'), icon: '🎤', options: [tk('Voice'), tk('Tropar Choir'), tk('Ensemble Choir'), tk('Sustained Choir')] },
    { name: tk('Percussion'), icon: '🥁', options: [tk('Marimba'), tk('Steel Drum'), tk('Celesta'), tk('Vibraphone')] },
    { name: tk('Woodwinds'), icon: '🪈', options: [tk('Flute'), tk('Clarinet'), tk('Oboe'), tk('Bassoon')] },
];

// Display-only — the underlying value stays "Piano"/"EP" everywhere else; this just
// makes the two piano options clearly distinct from each other in any picker.
export const INSTRUMENT_LABELS = { Piano: tk('Grand Piano'), EP: tk('Rhodes'), Guitar: tk('Acoustic Guitar'), Saxophone: tk('Tenor Saxophone'), Voice: tk('Solo Choir') };

export const SKILL_LEVELS = [tk('Beginner'), tk('Intermediate'), tk('Advanced')];

// Ten illustrated music avatars, shared visually with the mobile app.
export const AVATAR_OPTIONS = [
    { id: 'piano', label: tk('Piano'), emoji: '🎹', bg: '#FDEDEE', fg: '#C81E1E' },
    { id: 'drum-kit', label: tk('Drum kit'), emoji: '🥁', bg: '#E8F0FE', fg: '#1A56DB' },
    { id: 'note', label: tk('Music note'), emoji: '🎵', bg: '#F3EDFF', fg: '#6D4AFF' },
    { id: 'headphones', label: tk('Headphones'), emoji: '🎧', bg: '#E6F6F8', fg: '#0E7490' },
    { id: 'metronome', label: tk('Metronome'), emoji: '⏱️', bg: '#FFF4E5', fg: '#B45309' },
    { id: 'saxophone', label: tk('Saxophone'), emoji: '🎷', bg: '#FEF7D9', fg: '#A16207' },
    { id: 'trumpet', label: tk('Trumpet'), emoji: '🎺', bg: '#FDEEF6', fg: '#BE185D' },
    { id: 'violin', label: tk('Violin'), emoji: '🎻', bg: '#F5EDE6', fg: '#9A4F1E' },
    { id: 'acoustic-guitar', label: tk('Acoustic guitar'), emoji: '🎸', bg: '#EAF7EE', fg: '#1E8E3E' },
    { id: 'microphone', label: tk('Microphone'), emoji: '🎤', bg: '#EEF0F3', fg: '#334155' },
];
