import AppShell from '@/Layouts/AppShell';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

function PlayIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="9" />
            <path d="M10 9l5 3-5 3z" fill="currentColor" stroke="none" />
        </svg>
    );
}

function CheckIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 13l4 4L19 7" />
        </svg>
    );
}

function SpeakerIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 9v6h4l5 4V5L8 9H4z" />
            <path d="M16.5 8.5a5 5 0 010 7M19 6a8.5 8.5 0 010 12" />
        </svg>
    );
}

function ChevronDownIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 9l6 6 6-6" />
        </svg>
    );
}

function GaugeIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 15a8 8 0 0116 0" />
            <path d="M12 15l3.5-4.5" />
            <circle cx="12" cy="15" r="1.3" fill="currentColor" stroke="none" />
        </svg>
    );
}

function HelpIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="8.5" />
            <path d="M9.5 9.5a2.5 2.5 0 014.8 1c0 1.7-2.3 1.9-2.3 3.5" />
            <path d="M12 17.2v.01" />
        </svg>
    );
}

function RepeatIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 7h11a4 4 0 014 4v1M20 17H9a4 4 0 01-4-4v-1" />
            <path d="M8 4L4 7l4 3M16 14l4 3-4 3" />
        </svg>
    );
}

function BulbIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 18h6M10 21h4M8 11a4 4 0 118 0c0 1.8-1 2.6-2 3.5-.6.6-1 1.1-1 1.5h-2c0-.4-.4-.9-1-1.5-1-.9-2-1.7-2-3.5z" />
        </svg>
    );
}

function PlayTriangleIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" stroke="none" {...props}>
            <path d="M8 5.5v13l11-6.5z" />
        </svg>
    );
}

function PauseIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" stroke="none" {...props}>
            <rect x="6" y="5" width="4" height="14" rx="1" />
            <rect x="14" y="5" width="4" height="14" rx="1" />
        </svg>
    );
}

function RestartIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M3 12a9 9 0 1 1 2.6 6.3" />
            <path d="M3 7v5h5" />
        </svg>
    );
}

function CloseIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 6l12 12M18 6L6 18" />
        </svg>
    );
}

function ChevronLeftIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M15 5l-7 7 7 7" />
        </svg>
    );
}

function ChevronRightIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 5l7 7-7 7" />
        </svg>
    );
}

function CheckCircleIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="9" />
            <path d="M8 12.5l2.5 2.5L16 9.5" />
        </svg>
    );
}

function BookmarkIcon({ filled, ...props }) {
    return (
        <svg viewBox="0 0 24 24" fill={filled ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 3.5h12v17l-6-4-6 4v-17z" />
        </svg>
    );
}

function HeadsetIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 13v-1a8 8 0 0116 0v1" />
            <rect x="2.5" y="13" width="4" height="6" rx="1.5" />
            <rect x="17.5" y="13" width="4" height="6" rx="1.5" />
            <path d="M19.5 19v.5a3 3 0 01-3 3H13" />
        </svg>
    );
}

// Marimba, Strings, Organ and Harp have no clean single emoji of their own
// (🪕, Harp's old placeholder here, is actually a banjo), so they're drawn
// here instead, same as the mobile app does.
function MarimbaIcon({ size = 22 }) {
    const bars = ['#D7845B', '#C96F42', '#BB5E35', '#A94E2C', '#974226', '#833720'];
    return (
        <svg width={size} height={size} viewBox="0 0 26 26">
            <rect fill="#8D6E63" height={2} rx={1} width={24} x={1} y={8} />
            <rect fill="#8D6E63" height={2} rx={1} width={24} x={1} y={17} />
            {bars.map((fill, index) => {
                const height = 20 - index * 2;
                return <rect fill={fill} height={height} key={fill} rx={1.2} width={3} x={2 + index * 4} y={13 - height / 2} />;
            })}
        </svg>
    );
}

function OrganIcon({ size = 22 }) {
    const pipes = [10, 14, 18, 20, 18, 14, 10];
    return (
        <svg width={size} height={size} viewBox="0 0 26 26">
            {pipes.map((height, index) => (
                <rect
                    fill={index % 2 ? '#B0B7C3' : '#D5DAE1'}
                    key={index}
                    height={height}
                    rx={1.4}
                    stroke="#7C8594"
                    strokeWidth={0.6}
                    width={2.8}
                    x={2.6 + index * 3}
                    y={21 - height}
                />
            ))}
            <rect fill="#8D6E63" height={3.5} rx={1} width={24} x={1} y={21} />
        </svg>
    );
}

function StringsIcon({ size = 22 }) {
    return (
        <span className="flex items-center justify-center" style={{ height: size }}>
            <span style={{ fontSize: size * 0.7, marginRight: -size * 0.35, marginTop: size * 0.15 }}>🎻</span>
            <span style={{ fontSize: size * 0.7 }}>🎻</span>
        </span>
    );
}

// Unicode has no dedicated harp emoji — 🪕 (the one previously used here) is a
// banjo — so this is drawn instead, same reasoning as Marimba/Strings/Organ above.
function HarpIcon({ size = 22 }) {
    const strings = [5, 7.5, 10, 12.5, 15, 17.5];
    return (
        <svg width={size} height={size} viewBox="0 0 26 26">
            <path d="M5 4 C5 2 8 1 10 2 L21 7 C22 7.5 22 9 20.5 9 L19 9 L19 22 L16.5 22 L16.5 9.5 L7.5 9.5 L7.5 22 L5 22 Z" fill="#C9A24B" stroke="#8D6E34" strokeWidth={0.6} />
            {strings.map((x) => (
                <line key={x} x1={x} y1={6} x2={x} y2={21} stroke="#F4E4B0" strokeWidth={0.6} />
            ))}
        </svg>
    );
}

// Unicode's only brass-instrument emoji is 🎺 (trumpet) — no trombone or tuba
// of their own — so both are drawn instead, same reasoning as Harp above.
function TromboneIcon({ size = 22 }) {
    return (
        <svg width={size} height={size} viewBox="0 0 26 26">
            <rect fill="#D4AF61" height={3} rx={1.2} width={13} x={2} y={11.5} transform="rotate(-18 2 11.5)" />
            <rect fill="#C9A24B" height={3.2} rx={1.2} width={9} x={13} y={8} transform="rotate(-18 13 8)" />
            <path d="M20 5 L25 3.5 L24.5 9.5 L20 8.5 Z" fill="#B88E3E" stroke="#8D6E34" strokeWidth={0.5} />
        </svg>
    );
}

function TubaIcon({ size = 22 }) {
    return (
        <svg width={size} height={size} viewBox="0 0 26 26">
            <circle cx={10} cy={16} r={7} fill="#D4AF61" stroke="#8D6E34" strokeWidth={0.6} />
            <circle cx={10} cy={16} r={3} fill="#B88E3E" />
            <path d="M14 11 L22 2 L24.5 3.5 L17 13 Z" fill="#C9A24B" stroke="#8D6E34" strokeWidth={0.5} />
            <path d="M20 1 L25 1.5 L24.5 6.5 Z" fill="#B88E3E" stroke="#8D6E34" strokeWidth={0.5} />
        </svg>
    );
}

// Display-only, same pattern as Settings.jsx's INSTRUMENT_LABELS — the stored
// sound value and every audio path stay "Guitar"; this just clarifies the tile's
// label now that Jazz Guitar and Bass Guitar sit in the same category.
const INSTRUMENT_LABELS = { Guitar: 'Acoustic Guitar' };

const INSTRUMENT_EMOJI = {
    All: '🔀',
    'Bass Guitar': '🎸',
    Cello: '🎻',
    Celesta: '🔔',
    Ensemble: '🎻',
    EP: '🎹',
    'Full Song': '💿',
    Guitar: '🎸',
    Instruments: '🎹',
    'Jazz Guitar': '🎸',
    Monophonic: '🪈',
    Piano: '🎹',
    Saxophone: '🎷',
    'Vocal Stems': '🎙️',
    Violin: '🎻',
    Voice: '🎤',
};

// The instrument-category groupings members choose a sound within — "just the
// design" pass: UI-only, grouping whatever sounds a quiz already offers
// (QuizCatalog::soundsFor on the backend) into families, nothing about which
// sounds a quiz offers changes here. Several of these sounds don't exist for
// any quiz yet (Organ, Cello, Harp, Trumpet, Flute, Celesta, Vibraphone,
// Bass Guitar) — they simply won't appear until a quiz's `sounds` list
// actually includes them.
const SOUND_CATEGORIES = [
    { name: 'Piano', sounds: ['Piano', 'EP', 'Upright Piano', 'Organ'] },
    { name: 'Guitar', sounds: ['Guitar', 'Jazz Guitar', 'Bass Guitar'] },
    { name: 'Strings', sounds: ['Violin', 'Strings', 'Cello', 'Harp'] },
    { name: 'Brass', sounds: ['Saxophone', 'Trombone', 'Tuba'] },
    { name: 'Vocals', sounds: ['Voice'] },
    { name: 'Percussion', sounds: ['Marimba', 'Celesta', 'Vibraphone'] },
];

const CATEGORY_EMOJI = {
    Piano: '🎹',
    Guitar: '🎸',
    Strings: '🎻',
    Brass: '🎷',
    Vocals: '🎤',
};

function categoryFor(sound) {
    return SOUND_CATEGORIES.find((category) => category.sounds.includes(sound))?.name ?? null;
}

// Settings → Default instrument is a specific instrument (e.g. "Bass Guitar"),
// not just a category — checked against `availableSounds` (every sound this quiz
// actually has generated audio for) rather than `quizSounds` (the picker's
// *pickable* tiles), since Default-Instrument-only variants like EP, Jazz
// Guitar, Bass Guitar and Organ get real audio without ever being a tile of
// their own — that's the whole point of the substitution (picking the Guitar
// category should play Bass Guitar if that's the member's default, not fall
// back to plain Guitar just because Bass Guitar isn't a selectable tile).
// Otherwise fall back to whichever sound this quiz offers within that
// instrument's *category* (e.g. default "EP" + a quiz that only has "Piano"
// built → "Piano", not some unrelated sound), so a member's category
// preference still carries over even before every instrument in it has
// generated audio. No match at all (no default set, or its category isn't
// offered here) falls back to the quiz's own first sound, same as before any
// of this existed.
// Every branch below is gated on actually having real audio (availableSounds)
// wherever it's known — never just the static catalog list (quizSounds) — so
// this can never resolve to a sound with nothing recorded behind it. Without
// this, a member defaulted to e.g. Marimba could silently start a quiz with
// `sound: 'Marimba'` even though that quiz's Percussion tile is hidden (no
// real Marimba audio exists for it): the category-match fallback used to
// search quizSounds alone, which only reflects what a category CLAIMS to
// offer, not what was actually recorded — the Start Quiz button would then
// submit a sound whose Play button does nothing.
function resolveDefaultSound(defaultInstrument, quizSounds, availableSounds) {
    if (!quizSounds || quizSounds.length === 0) return null;
    // Placeholder quizzes report no availableSounds at all — fall back to the
    // static list alone rather than resolving to nothing playable.
    const real = availableSounds?.length ? availableSounds : quizSounds;

    if (defaultInstrument && real.includes(defaultInstrument)) return defaultInstrument;

    const category = categoryFor(defaultInstrument);
    if (category) {
        const match = quizSounds.find((sound) => categoryFor(sound) === category && real.includes(sound));
        if (match) return match;
    }

    return quizSounds.find((sound) => real.includes(sound)) ?? quizSounds[0];
}

// Percussion here is all mallet instruments (Marimba, Celesta, Vibraphone) — no
// drums — so it reuses MarimbaIcon instead of a 🥁 drum emoji, which would
// misrepresent what's actually in the category.
function CategoryIcon({ name, size = 22 }) {
    if (name === 'Percussion') return <MarimbaIcon size={size} />;
    return <span style={{ fontSize: size * 0.85 }}>{CATEGORY_EMOJI[name] ?? '🎵'}</span>;
}

function SoundIcon({ sound, size = 22 }) {
    if (sound === 'Marimba') return <MarimbaIcon size={size} />;
    if (sound === 'Strings') return <StringsIcon size={size} />;
    if (sound === 'Organ') return <OrganIcon size={size} />;
    if (sound === 'Harp') return <HarpIcon size={size} />;
    if (sound === 'Trombone') return <TromboneIcon size={size} />;
    if (sound === 'Tuba') return <TubaIcon size={size} />;
    return <span style={{ fontSize: size * 0.85 }}>{INSTRUMENT_EMOJI[sound] ?? '🎵'}</span>;
}

function StatusDot({ quiz, inverted, large }) {
    const size = large ? 'h-7 w-7' : 'h-4 w-4';
    const dotSize = large ? 'h-2.5 w-2.5' : 'h-1.5 w-1.5';
    const checkSize = large ? 'h-4 w-4' : 'h-2.5 w-2.5';
    const borderWidth = large ? 'border-[2.5px]' : 'border-2';

    // A member's explicit "Mark as Completed" (or a genuinely finished attempt) wins over
    // a merely in-progress one — otherwise restarting an already-completed quiz would make
    // it look unfinished again in the sidebar, which reads as the completion being lost.
    if (quiz.status === 'completed' || quiz.manuallyCompleted) {
        return (
            <span className={`flex ${size} shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white`}>
                <CheckIcon className={checkSize} />
            </span>
        );
    }

    if (quiz.status === 'in_progress') {
        return (
            <span
                className={`flex ${size} shrink-0 items-center justify-center rounded-full ${borderWidth} ${inverted ? 'border-white' : 'border-brand'}`}
            >
                <span className={`${dotSize} rounded-full ${inverted ? 'bg-white' : 'bg-brand'}`} />
            </span>
        );
    }

    return <span className={`${size} shrink-0 rounded-full ${borderWidth} ${inverted ? 'border-white/40' : 'border-neutral-300'}`} />;
}

function SidebarRow({ quiz, categorySlug, selected }) {
    return (
        <Link
            href={route('quiz.category', { category: categorySlug, quiz: quiz.slug })}
            preserveScroll
            preserveState
            replace
            className={`flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left transition-all duration-150 ${
                selected ? 'bg-brand shadow-sm' : 'bg-neutral-100 hover:bg-neutral-200'
            }`}
        >
            <span
                className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${
                    selected ? 'bg-white/15 text-white' : 'bg-white text-neutral-600'
                }`}
            >
                <PlayIcon className="h-3.5 w-3.5" />
            </span>
            <p className={`min-w-0 flex-1 truncate text-sm font-semibold ${selected ? 'text-white' : 'text-neutral-900'}`}>
                {quiz.title}
            </p>
            <StatusDot quiz={quiz} inverted={selected} />
        </Link>
    );
}

// Same pill look as the desktop SidebarRow (left play icon, colored fill when selected,
// status dot on the right) — tapping still navigates to its own preview screen rather than
// opening inline, so unlike SidebarRow this is a plain navigation, not preserveState.
function MobileCategoryRow({ quiz, categorySlug, selected }) {
    return (
        <Link
            href={route('quiz.category', { category: categorySlug, quiz: quiz.slug })}
            className={`flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left transition-all duration-150 ${
                selected ? 'bg-brand shadow-sm' : 'bg-neutral-100 hover:bg-neutral-200'
            }`}
        >
            <span
                className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${
                    selected ? 'bg-white/15 text-white' : 'bg-white text-neutral-600'
                }`}
            >
                <PlayIcon className="h-3.5 w-3.5" />
            </span>
            <p className={`min-w-0 flex-1 truncate text-sm font-semibold ${selected ? 'text-white' : 'text-neutral-900'}`}>
                {quiz.title}
            </p>
            <StatusDot quiz={quiz} inverted={selected} />
        </Link>
    );
}

// dangerouslySetInnerHTML never executes <script> tags (browsers ignore
// script elements created via innerHTML), so embed codes that load a player
// via <script src="..."> — Wistia, Vimeo's JS embed, etc. — need their
// script tags recreated as real DOM nodes to actually run.
function VideoEmbed({ html, className }) {
    const containerRef = useRef(null);

    useEffect(() => {
        const container = containerRef.current;
        if (!container) return;

        container.innerHTML = html;

        container.querySelectorAll('script').forEach((oldScript) => {
            const newScript = document.createElement('script');
            for (const { name, value } of oldScript.attributes) {
                newScript.setAttribute(name, value);
            }
            newScript.textContent = oldScript.textContent;
            oldScript.replaceWith(newScript);
        });
    }, [html]);

    return <div ref={containerRef} className={className} />;
}

// Row of instrument tiles — the same tile design used before categories existed,
// now reused both as the flat fallback (Find the Key #1/#2, whose "sounds" are
// recording types, not instruments) and as the per-category tile row below the
// category tabs.
function SoundTiles({ sounds, sound, onSelect }) {
    return (
        <div className="mt-2.5 flex flex-wrap gap-2.5 sm:flex-nowrap">
            {sounds.map((option) => {
                const active = sound === option;
                return (
                    <button
                        key={option}
                        type="button"
                        onClick={() => onSelect(option)}
                        className={`flex w-[31%] flex-col items-center justify-center gap-1 rounded-xl border px-3 py-3 text-center transition-colors duration-150 sm:w-auto sm:flex-1 sm:py-4 ${
                            active ? 'border-brand/60 bg-brand/5' : 'border-black/10 bg-white hover:bg-neutral-50'
                        }`}
                    >
                        <SoundIcon sound={option} />
                        <span className={`truncate text-xs ${active ? 'font-bold text-brand' : 'font-medium text-neutral-600'}`}>
                            {INSTRUMENT_LABELS[option] ?? option}
                        </span>
                    </button>
                );
            })}
        </div>
    );
}

// Every sound a quiz actually offers, grouped under its instrument family as a
// section header — no more "pick the category, then it resolves to one
// instrument for you" indirection. Every pickable instrument gets its own
// tile, so switching from a Default Instrument's family member (e.g. Jazz
// Guitar) to any other specific instrument in the same or a different family
// (e.g. Strings specifically, not whichever Strings-family instrument
// resolves by default) is always exactly one tap.
function SoundPicker({ sounds, sound, onSelect, defaultInstrument, availableSounds }) {
    // `sounds` is a narrow, curated "one tile per family" list (e.g. just
    // "Piano", never EP/Upright Piano/Organ alongside it) — kept around only
    // as the fallback for placeholder quizzes with no real audio yet at all.
    // Once real audio exists, `availableSounds` (every sound this quiz
    // actually has recordings for) is the true source of what's pickable —
    // every family member with real audio gets its own tile, not just
    // whichever one `sounds` singled out.
    const categories = SOUND_CATEGORIES
        .map((category) => ({
            ...category,
            available: category.sounds.filter((option) =>
                availableSounds?.length ? availableSounds.includes(option) : sounds.includes(option)
            ),
        }))
        .filter((category) => category.available.length > 0);

    const [open, setOpen] = useState(false);
    // null = showing the 6 category rows; a category name = drilled into that
    // category's own instrument list (image the member sent: tap a category,
    // its specific instruments replace the list, not a side-by-side flyout).
    const [drillCategory, setDrillCategory] = useState(null);

    // Drives the slide-up entrance transition — starts translated off-screen,
    // then flips to its resting position a frame after mounting so the
    // browser actually animates the transform instead of snapping straight
    // to it (same "Sign out?" sheet treatment used elsewhere in the app).
    const [entered, setEntered] = useState(false);

    function closeMenu() {
        setOpen(false);
        setDrillCategory(null);
    }

    // A real popup (fixed overlay, own scroll) instead of an inline dropdown —
    // the inline version sat in normal page flow and could cover the Continue
    // Quiz / Mark as Complete buttons below it with no way to scroll past it
    // to reach them. Locking body scroll while it's open keeps the page from
    // scrolling underneath a modal that's meant to be the only thing active.
    useEffect(() => {
        if (!open) {
            setEntered(false);
            return;
        }
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        const frame = requestAnimationFrame(() => setEntered(true));
        return () => {
            document.body.style.overflow = previousOverflow;
            cancelAnimationFrame(frame);
        };
    }, [open]);

    if (sounds.length === 0) return null;

    // Find the Key #1/#2's "sounds" are recording types (Monophonic, Full Song,
    // etc.), not instruments — none of them match a category, so fall back to
    // the plain flat picker exactly as it worked before categories existed.
    if (categories.length === 0) {
        return (
            <div className="mt-4">
                <p className="text-xs font-semibold text-neutral-700">Choose a sound</p>
                <SoundTiles sounds={sounds} sound={sound} onSelect={onSelect} />
            </div>
        );
    }

    return (
        <div className="mt-4">
            <p className="text-xs font-semibold text-neutral-700">Choose a sound</p>

            <button
                type="button"
                onClick={() => setOpen(true)}
                className="mt-2.5 flex w-full items-center gap-2.5 rounded-xl border border-black/10 bg-white px-3.5 py-3 text-left transition-colors duration-150 hover:bg-neutral-50"
            >
                <SoundIcon sound={sound} />
                <span className="flex-1 text-sm font-semibold text-neutral-900">{INSTRUMENT_LABELS[sound] ?? sound}</span>
                {sound === defaultInstrument && (
                    <span className="rounded-full bg-brand/10 px-2 py-0.5 text-[10px] font-semibold text-brand">Default</span>
                )}
                <ChevronDownIcon className="h-4 w-4 text-neutral-400" />
            </button>

            {open && (
                <div
                    className={`fixed inset-0 z-40 flex items-end justify-center bg-neutral-900/40 transition-opacity duration-300 sm:items-center sm:p-4 ${
                        entered ? 'opacity-100' : 'opacity-0'
                    }`}
                    onClick={closeMenu}
                >
                    <div
                        className={`flex max-h-[85vh] w-full flex-col overflow-hidden rounded-t-2xl bg-white shadow-xl transition-transform duration-300 ease-out sm:max-w-sm sm:rounded-2xl ${
                            entered ? 'translate-y-0' : 'translate-y-full'
                        }`}
                        onClick={(event) => event.stopPropagation()}
                    >
                        {/* Grab handle — mobile bottom-sheet only, matches the app's native sheets. */}
                        <div className="flex shrink-0 justify-center pb-1 pt-2.5 sm:hidden">
                            <div className="h-1.5 w-10 rounded-full bg-neutral-200" />
                        </div>

                        <div className="shrink-0 overflow-y-auto px-6 pb-6 pt-2 sm:pt-5">
                            <div className="flex flex-col items-center text-center">
                                <span className="flex h-14 w-14 items-center justify-center rounded-full bg-rose-50 text-brand">
                                    {drillCategory ? <CategoryIcon name={drillCategory} size={26} /> : <SpeakerIcon className="h-6 w-6" />}
                                </span>
                                <p className="mt-3 text-xl font-extrabold text-neutral-900">{drillCategory ?? 'Choose a sound'}</p>
                                <p className="mt-1 text-sm text-neutral-500">
                                    {drillCategory
                                        ? `Pick which ${drillCategory.toLowerCase()} instrument plays this quiz.`
                                        : 'Pick which instrument plays this quiz.'}
                                </p>
                            </div>

                            {drillCategory && (
                                <button
                                    type="button"
                                    onClick={() => setDrillCategory(null)}
                                    className="mx-auto mt-3 flex items-center gap-1 text-xs font-semibold text-neutral-400 hover:text-neutral-600"
                                >
                                    <ChevronLeftIcon className="h-3.5 w-3.5" />
                                    All categories
                                </button>
                            )}

                            <div className="mt-5 divide-y divide-black/5 overflow-hidden rounded-2xl bg-neutral-50">
                                {!drillCategory
                                    ? categories.map((category) => {
                                          const pickedHere = categoryFor(sound) === category.name ? sound : null;
                                          return (
                                              <button
                                                  key={category.name}
                                                  type="button"
                                                  onClick={() => setDrillCategory(category.name)}
                                                  className="flex w-full items-center gap-2.5 px-3.5 py-3 text-left transition-colors duration-150 hover:bg-neutral-100"
                                              >
                                                  <CategoryIcon name={category.name} size={18} />
                                                  <span className="flex-1 text-sm font-medium text-neutral-800">{category.name}</span>
                                                  {pickedHere && (
                                                      <span className="text-sm font-semibold text-brand">
                                                          {INSTRUMENT_LABELS[pickedHere] ?? pickedHere}
                                                      </span>
                                                  )}
                                                  <ChevronDownIcon className="h-4 w-4 -rotate-90 text-neutral-400" />
                                              </button>
                                          );
                                      })
                                    : categories
                                          .find((category) => category.name === drillCategory)
                                          .available.map((option) => {
                                              const active = sound === option;
                                              const isDefault = option === defaultInstrument;
                                              return (
                                                  <button
                                                      key={option}
                                                      type="button"
                                                      onClick={() => {
                                                          onSelect(option);
                                                          closeMenu();
                                                      }}
                                                      className="flex w-full items-center gap-2.5 px-3.5 py-3 text-left transition-colors duration-150 hover:bg-neutral-100"
                                                  >
                                                      <SoundIcon sound={option} size={18} />
                                                      <span
                                                          className={`flex-1 text-sm ${active ? 'font-semibold text-brand' : 'font-medium text-neutral-800'}`}
                                                      >
                                                          {INSTRUMENT_LABELS[option] ?? option}
                                                      </span>
                                                      {isDefault && (
                                                          <span className="rounded-full bg-brand/10 px-2 py-0.5 text-[10px] font-semibold text-brand">
                                                              Default
                                                          </span>
                                                      )}
                                                      {active && <CheckIcon className="h-4 w-4 text-brand" />}
                                                  </button>
                                              );
                                          })}
                            </div>

                            <button
                                type="button"
                                onClick={closeMenu}
                                className="mt-4 w-full rounded-xl py-3 text-sm font-bold text-neutral-900 transition-colors duration-150 hover:bg-neutral-100"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

// Opened from the button next to Start Quiz. Always plays the one track matching
// the member's Default Instrument (same category-primary fallback as the question
// sound picker) — whatever sound the member separately picks for the quiz
// questions below has no bearing on this. Most quizzes have exactly one
// track per instrument (a single reference scale) and autoplay it directly;
// quizzes with several distinct clips per instrument (e.g. Melodic Diatonic
// Intervals' one demo per interval type) show the same single-track player
// with Next/Previous to step between clips, rather than a flat list.
//
// Quizzes whose clips are themselves two-part (quality + inversion, labeled
// "{Quality} · {Inversion}" — Basic Triads Level 5 and similar) group by the
// first part instead: Next/Previous steps between qualities (Major, Minor…),
// and small tabs switch inversion within the current quality — a flat
// "1 of 15" scrub was too many taps to compare a single chord's inversions,
// and doesn't match how a member actually wants to browse these.
function ReferenceAudioModal({ tracks, defaultInstrument, onClose }) {
    const matchingTracks = useMemo(() => {
        const trackSounds = tracks.map((t) => t.sound).filter(Boolean);
        const resolved = resolveDefaultSound(defaultInstrument, trackSounds);
        const forResolved = tracks.filter((t) => t.sound === resolved);
        return forResolved.length > 0 ? forResolved : tracks.length > 0 ? [tracks[0]] : [];
    }, [tracks, defaultInstrument]);

    // Grouped mode only kicks in when EVERY matching track has a "{X} · {Y}"
    // label — a quiz with a mix of plain and compound labels just falls back
    // to the flat list, same as before this existed.
    const groups = useMemo(() => {
        if (matchingTracks.length === 0 || !matchingTracks.every((t) => t.label.includes(' · '))) return null;
        const map = new Map();
        matchingTracks.forEach((t) => {
            const [groupLabel, subLabel] = t.label.split(' · ');
            if (!map.has(groupLabel)) map.set(groupLabel, []);
            map.get(groupLabel).push({ ...t, subLabel });
        });
        return [...map.entries()].map(([label, groupTracks]) => ({ label, tracks: groupTracks }));
    }, [matchingTracks]);

    const [clipIndex, setClipIndex] = useState(0);
    const [groupIndex, setGroupIndex] = useState(0);
    const [subIndex, setSubIndex] = useState(0);
    useEffect(() => {
        setClipIndex(0);
        setGroupIndex(0);
        setSubIndex(0);
    }, [matchingTracks]);

    const group = groups?.[groupIndex] ?? null;
    const track = groups ? (group?.tracks[subIndex] ?? null) : (matchingTracks[clipIndex] ?? null);
    const hasMultiple = groups ? groups.length > 1 : matchingTracks.length > 1;

    const [playing, setPlaying] = useState(false);
    const [currentTime, setCurrentTime] = useState(0);
    const [duration, setDuration] = useState(0);
    const audioRef = useRef(null);

    useEffect(() => {
        const onKeyDown = (event) => event.key === 'Escape' && onClose();
        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, [onClose]);

    const togglePlay = () => {
        if (!audioRef.current) return;
        if (playing) {
            audioRef.current.pause();
        } else {
            audioRef.current.play();
        }
    };

    const restart = () => {
        if (!audioRef.current) return;
        audioRef.current.currentTime = 0;
        audioRef.current.play();
    };

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            onClick={(e) => e.target === e.currentTarget && onClose()}
        >
            <div className="w-full max-w-lg rounded-2xl bg-neutral-900 p-8 text-white">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2 text-white/50">
                        <HeadsetIcon className="h-[18px] w-[18px]" />
                        <p className="text-xs font-semibold">Reference Audio</p>
                    </div>
                    <button type="button" onClick={onClose} aria-label="Close" className="text-white/50 hover:text-white">
                        <CloseIcon className="h-5 w-5" />
                    </button>
                </div>

                {track ? (
                    <>
                        <audio
                            key={track.id}
                            ref={audioRef}
                            src={track.url}
                            autoPlay
                            onPlay={() => setPlaying(true)}
                            onPause={() => setPlaying(false)}
                            onEnded={() => setPlaying(false)}
                            onLoadedMetadata={(e) => setDuration(e.currentTarget.duration)}
                            onTimeUpdate={(e) => setCurrentTime(e.currentTarget.currentTime)}
                        />

                        <div className="mt-5 flex items-center gap-3 rounded-xl bg-white/5 px-3 py-2.5">
                            <button type="button" onClick={restart} aria-label="Restart" className="shrink-0 text-white/70 hover:text-white">
                                <RestartIcon className="h-5 w-5" />
                            </button>
                            <button type="button" onClick={togglePlay} aria-label={playing ? 'Pause' : 'Play'} className="shrink-0 text-white">
                                {playing ? <PauseIcon className="h-5 w-5" /> : <PlayTriangleIcon className="h-5 w-5" />}
                            </button>
                            <input
                                type="range"
                                min={0}
                                max={duration || 0}
                                step={0.01}
                                value={currentTime}
                                onChange={(e) => {
                                    const value = Number(e.target.value);
                                    if (audioRef.current) audioRef.current.currentTime = value;
                                    setCurrentTime(value);
                                }}
                                className="h-1.5 w-full accent-brand"
                            />
                        </div>

                        <p className="mt-6 text-center text-sm text-white/50">Listen and familiarize yourself with the sound.</p>
                        <p className="font-display mt-3 text-center text-4xl font-semibold">{group ? group.label : track.label}</p>

                        {group && group.tracks.length > 1 && (
                            <div className="mt-4 flex flex-wrap items-center justify-center gap-2">
                                {group.tracks.map((groupTrack, index) => (
                                    <button
                                        key={groupTrack.id}
                                        type="button"
                                        onClick={() => setSubIndex(index)}
                                        className={`rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150 ${
                                            index === subIndex ? 'bg-brand text-white' : 'bg-white/10 text-white/70 hover:bg-white/15'
                                        }`}
                                    >
                                        {groupTrack.subLabel}
                                    </button>
                                ))}
                            </div>
                        )}

                        {hasMultiple && (
                            <div className="mt-6 flex flex-col items-center gap-2.5">
                                <p className="text-xs text-white/40">
                                    {group ? `${groupIndex + 1} of ${groups.length}` : `${clipIndex + 1} of ${matchingTracks.length}`}
                                </p>
                                <div className="flex items-center gap-3">
                                    {(group ? groupIndex > 0 : clipIndex > 0) && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (group) {
                                                    setGroupIndex((i) => Math.max(0, i - 1));
                                                    setSubIndex(0);
                                                } else {
                                                    setClipIndex((i) => Math.max(0, i - 1));
                                                }
                                            }}
                                            className="rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-rose-700"
                                        >
                                            Previous
                                        </button>
                                    )}
                                    {(group ? groupIndex < groups.length - 1 : clipIndex < matchingTracks.length - 1) && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (group) {
                                                    setGroupIndex((i) => Math.min(groups.length - 1, i + 1));
                                                    setSubIndex(0);
                                                } else {
                                                    setClipIndex((i) => Math.min(matchingTracks.length - 1, i + 1));
                                                }
                                            }}
                                            className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-emerald-700"
                                        >
                                            Next
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}
                    </>
                ) : (
                    <p className="mt-10 text-center text-sm text-white/50">No reference audio has been added for this quiz yet.</p>
                )}
            </div>
        </div>
    );
}

function DetailPane({ quiz }) {
    const defaultInstrument = usePage().props.auth?.user?.default_instrument ?? null;
    const [processing, setProcessing] = useState(false);
    const [sound, setSound] = useState(resolveDefaultSound(defaultInstrument, quiz?.sounds, quiz?.availableSounds));
    const [blockedNotice, setBlockedNotice] = useState(false);
    const [audioModalOpen, setAudioModalOpen] = useState(false);

    useEffect(() => {
        setSound(resolveDefaultSound(defaultInstrument, quiz?.sounds, quiz?.availableSounds));
        setBlockedNotice(false);
        setAudioModalOpen(false);
    }, [quiz?.slug]);

    if (!quiz) {
        return (
            <div className="flex h-full min-h-[320px] items-center justify-center rounded-2xl border border-black/5 bg-white p-8 text-center text-sm text-neutral-500">
                No quizzes in this category are ready yet. Check back soon.
            </div>
        );
    }

    const resuming = quiz.status === 'in_progress';
    const completed = quiz.status === 'completed';
    // The top badge/sidebar checkmark reflect a manual mark too, but the
    // Start/Continue/Retry button and score text below stay tied to a real
    // attempt only — manually marking a quiz complete doesn't mean it's been
    // played, so "Retry Quiz" would be the wrong thing to say.
    //
    // A completed/manually-marked quiz keeps showing as completed even with a
    // leftover in-progress attempt (e.g. after a cancelled retry) — otherwise
    // restarting it would make an already-earned completion look undone.
    const showsCompleted = completed || quiz.manuallyCompleted;

    function toggleCompleted() {
        router.post(route('quiz.mark-completed', quiz.slug), {}, { preserveScroll: true, preserveState: true });
    }

    function toggleBookmark() {
        router.post(route('quiz.bookmark', quiz.slug), {}, { preserveScroll: true, preserveState: true });
    }

    // Same DEFAULT_TIPS the mobile app falls back to for every quiz it has no
    // specific override for (app/quiz-preview.tsx) — not invented copy.
    const tips = [
        'Use headphones for best results.',
        'Listen carefully before answering.',
        'Take your time and answer the questions.',
    ];

    function start() {
        if (!quiz.available) {
            setBlockedNotice(true);
            return;
        }
        setProcessing(true);
        router.post(route('quiz.start', quiz.slug), { sound }, { onFinish: () => setProcessing(false) });
    }

    return (
        <div className="rounded-2xl border border-black/5 bg-white p-6 sm:p-7">
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <h2 className="text-2xl font-bold text-neutral-900">{quiz.title}</h2>
                </div>
                {showsCompleted && (
                    <span className="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">
                        <CheckIcon className="h-3.5 w-3.5" />
                        Completed
                    </span>
                )}
            </div>

            {quiz.video_embed ? (
                <VideoEmbed
                    html={quiz.video_embed}
                    className="-mx-6 mt-5 aspect-video overflow-hidden rounded-2xl bg-neutral-900 [&_iframe]:h-full [&_iframe]:w-full sm:-mx-7"
                />
            ) : (
                <div className="-mx-6 mt-5 flex aspect-video items-center justify-center rounded-2xl bg-neutral-100 text-sm text-neutral-400 sm:-mx-7">
                    No video for this quiz yet
                </div>
            )}

            <div className="mt-6 grid grid-cols-3 gap-2 sm:gap-3">
                <div className="flex flex-col items-center gap-1.5 rounded-xl border border-black/5 bg-neutral-50 px-2 py-3 text-center sm:flex-row sm:items-center sm:gap-3 sm:px-3.5 sm:text-left">
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-neutral-400 ring-1 ring-black/5 sm:h-9 sm:w-9">
                        <GaugeIcon className="h-4 w-4 sm:h-4.5 sm:w-4.5" />
                    </span>
                    <div className="min-w-0">
                        <p className="text-xs text-neutral-400">Difficulty</p>
                        <p className="text-sm font-bold text-neutral-900">{quiz.difficulty}</p>
                    </div>
                </div>
                <div className="flex flex-col items-center gap-1.5 rounded-xl border border-black/5 bg-neutral-50 px-2 py-3 text-center sm:flex-row sm:items-center sm:gap-3 sm:px-3.5 sm:text-left">
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-neutral-400 ring-1 ring-black/5 sm:h-9 sm:w-9">
                        <HelpIcon className="h-4 w-4 sm:h-4.5 sm:w-4.5" />
                    </span>
                    <div className="min-w-0">
                        <p className="text-xs text-neutral-400">Questions</p>
                        <p className="text-sm font-bold text-neutral-900">{quiz.question_count}</p>
                    </div>
                </div>
                <div className="flex flex-col items-center gap-1.5 rounded-xl border border-black/5 bg-neutral-50 px-2 py-3 text-center sm:flex-row sm:items-center sm:gap-3 sm:px-3.5 sm:text-left">
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-neutral-400 ring-1 ring-black/5 sm:h-9 sm:w-9">
                        <RepeatIcon className="h-4 w-4 sm:h-4.5 sm:w-4.5" />
                    </span>
                    <div className="min-w-0">
                        <p className="text-xs text-neutral-400">Attempts</p>
                        <p className="text-sm font-bold text-neutral-900">Unlimited</p>
                    </div>
                </div>
            </div>

            <div className="mt-4 rounded-2xl border border-black/5 bg-neutral-50 p-4">
                <div className="flex items-center gap-2 text-neutral-400">
                    <BulbIcon className="h-4 w-4" />
                    <p className="text-xs font-semibold">Tips</p>
                </div>
                <ul className="mt-2 space-y-1.5">
                    {tips.map((tip) => (
                        <li key={tip} className="text-sm text-neutral-600">
                            &bull; {tip}
                        </li>
                    ))}
                </ul>
            </div>

            <SoundPicker
                key={quiz.slug}
                sounds={quiz.sounds}
                sound={sound}
                onSelect={setSound}
                defaultInstrument={defaultInstrument}
                availableSounds={quiz.availableSounds}
            />

            {completed && quiz.score !== null && (
                <p className="mt-4 text-sm font-semibold text-emerald-700">Last attempt: {quiz.score}% correct</p>
            )}

            <div className="mt-6 flex gap-2.5">
                <button
                    type="button"
                    disabled={processing}
                    onClick={start}
                    className="flex-1 rounded-xl bg-neutral-900 py-3.5 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90 disabled:opacity-60"
                >
                    {resuming ? 'Continue Quiz' : completed ? 'Retry Quiz' : 'Start Quiz'}
                </button>
                {quiz.referenceAudioTracks.length > 0 && (
                    <button
                        type="button"
                        onClick={() => setAudioModalOpen(true)}
                        className="flex shrink-0 items-center justify-center gap-2 rounded-xl bg-green-600 px-4 py-3.5 text-sm font-extrabold text-white shadow-sm transition-all duration-150 hover:bg-green-700 hover:shadow active:scale-[0.98]"
                    >
                        <HeadsetIcon className="h-5 w-5" />
                        Listen Mode
                    </button>
                )}
            </div>
            {blockedNotice && (
                <p className="mt-2.5 text-center text-sm text-neutral-500">Questions for this quiz haven&apos;t been added yet.</p>
            )}

            {audioModalOpen && (
                <ReferenceAudioModal tracks={quiz.referenceAudioTracks} defaultInstrument={defaultInstrument} onClose={() => setAudioModalOpen(false)} />
            )}

            {quiz.available ? (
                <div className="mt-3 flex gap-2.5">
                    <button
                        type="button"
                        onClick={toggleCompleted}
                        className={`flex min-h-[48px] flex-1 items-center justify-center gap-2 rounded-xl border-[1.5px] text-sm font-bold transition-colors duration-150 ${
                            quiz.manuallyCompleted ? 'border-emerald-600 text-emerald-600' : 'border-brand text-brand'
                        }`}
                    >
                        <CheckCircleIcon className="h-5 w-5" />
                        {quiz.manuallyCompleted ? 'Completed' : 'Mark as Completed'}
                    </button>
                    <button
                        type="button"
                        onClick={toggleBookmark}
                        aria-label={quiz.bookmarked ? 'Remove bookmark' : 'Bookmark this quiz'}
                        className={`flex min-h-[48px] w-[52px] shrink-0 items-center justify-center rounded-xl border-[1.5px] transition-colors duration-150 ${
                            quiz.bookmarked ? 'border-brand text-brand' : 'border-neutral-300 text-neutral-400'
                        }`}
                    >
                        <BookmarkIcon filled={quiz.bookmarked} className="h-5 w-5" />
                    </button>
                </div>
            ) : (
                // Not-yet-built quizzes have no real Quiz row to attach a
                // mark to, so these stay inert rather than posting to
                // nothing (same "coming soon" treatment as other unbuilt-quiz UI).
                <div className="mt-3 flex gap-2.5">
                    <button
                        type="button"
                        disabled
                        title="Coming soon"
                        className="flex min-h-[48px] flex-1 cursor-default items-center justify-center gap-2 rounded-xl border-[1.5px] border-brand/40 text-sm font-bold text-brand/40"
                    >
                        <CheckCircleIcon className="h-5 w-5" />
                        Mark as Completed
                    </button>
                    <button
                        type="button"
                        disabled
                        title="Coming soon"
                        aria-label="Bookmark this quiz"
                        className="flex min-h-[48px] w-[52px] shrink-0 cursor-default items-center justify-center rounded-xl border-[1.5px] border-neutral-200 text-neutral-300"
                    >
                        <BookmarkIcon className="h-5 w-5" />
                    </button>
                </div>
            )}
        </div>
    );
}

export default function QuizCategory({ category, categorySlug, selectedSlug, quizzes, progress }) {
    const selected = quizzes.find((quiz) => quiz.slug === selectedSlug) ?? quizzes[0] ?? null;

    return (
        <AppShell eyebrow="Start Quiz" title={category} intro="Pick a quiz to begin.">
            <Head title={selected ? `${selected.title} · ${category}` : category} />

            {/* Mobile: the list and the preview are two separate screens, like the app — never
                both stacked on one page. selectedSlug only exists once a specific quiz is in
                the URL (quiz.category route's optional {quiz} segment), so it alone decides
                which one shows. */}
            {selectedSlug ? (
                <div className="lg:hidden">
                    <Link
                        href={route('quiz.category', categorySlug)}
                        className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-neutral-500 transition-colors duration-150 hover:text-neutral-900"
                    >
                        <ChevronLeftIcon className="h-4 w-4" />
                        {category}
                    </Link>
                    <DetailPane quiz={selected} />
                </div>
            ) : (
                <div className="rounded-2xl border border-black/5 bg-white p-4 shadow-sm lg:hidden">
                    <div className="mb-4 flex items-center justify-between text-sm">
                        <span className="font-semibold text-neutral-700">
                            {progress.done}/{progress.total} Completed
                        </span>
                        <span className="font-semibold text-neutral-500">{progress.percent}%</span>
                    </div>
                    <div className="mb-5 h-1.5 w-full overflow-hidden rounded-full bg-neutral-200">
                        <div className="h-full rounded-full bg-neutral-900" style={{ width: `${progress.percent}%` }} />
                    </div>

                    <div className="space-y-1">
                        {quizzes.map((quiz) => (
                            <MobileCategoryRow
                                key={quiz.slug}
                                quiz={quiz}
                                categorySlug={categorySlug}
                                selected={quiz.slug === selected?.slug}
                            />
                        ))}
                    </div>
                </div>
            )}

            <div className="hidden gap-6 lg:grid lg:grid-cols-[360px_1fr]">
                <div className="rounded-2xl border border-black/5 bg-white p-4 shadow-sm">
                    <div className="mb-4 flex items-center justify-between text-sm">
                        <span className="font-semibold text-neutral-700">
                            {progress.done}/{progress.total} Completed
                        </span>
                        <span className="font-semibold text-neutral-500">{progress.percent}%</span>
                    </div>
                    <div className="mb-5 h-1.5 w-full overflow-hidden rounded-full bg-neutral-200">
                        <div className="h-full rounded-full bg-neutral-900" style={{ width: `${progress.percent}%` }} />
                    </div>

                    <div className="space-y-1">
                        {quizzes.map((quiz) => (
                            <SidebarRow
                                key={quiz.slug}
                                quiz={quiz}
                                categorySlug={categorySlug}
                                selected={quiz.slug === selected?.slug}
                            />
                        ))}
                    </div>
                </div>

                <DetailPane quiz={selected} />
            </div>
        </AppShell>
    );
}
