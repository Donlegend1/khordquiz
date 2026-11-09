import VideoEmbed from '@/Components/Quiz/VideoEmbed';
import { QuizVideoContext } from '@/Components/Quiz/QuizQuickGuide';
import { shuffled, reshuffleAvoiding } from '@/lib/referenceAudio';
import InstrumentIcon, { FAMILY_INSTRUMENTS } from '@/Components/Quiz/InstrumentIcon';
import instrumentIcons from '@/data/instrumentIcons.json';
import illustratedGuides from '@/data/illustratedGuides.json';
import IllustratedGuideDialog from '@/Components/Quiz/IllustratedGuideDialog';
import ScaleGuide from '@/Components/Quiz/ScaleGuide';
import scaleGuides from '@/data/scaleGuides.json';
import modalVoicingGuides from '@/data/modalVoicingGuides.json';
import ModalHarmonyGuide from '@/Components/Quiz/ModalHarmonyGuide';
import modalHarmonyGuides from '@/data/modalHarmonyGuides.json';
import ProgressionGuide from '@/Components/Quiz/ProgressionGuide';
import progressionGuides from '@/data/progressionGuides.json';
import chordNamingGuides from '@/data/chordNamingGuides.json';
import dominantExtensionGuides from '@/data/dominantExtensionGuides.json';
import minorExtensionGuides from '@/data/minorExtensionGuides.json';
import ChordExtensionGuide from '@/Components/Quiz/ChordExtensionGuide';
import majorExtensionGuides from '@/data/majorExtensionGuides.json';
import ChordToneGuide from '@/Components/Quiz/ChordToneGuide';
import chordToneGuides from '@/data/chordToneGuides.json';
import thirteenthChordGuides from '@/data/thirteenthChordGuides.json';
import secondaryEleventhGuides from '@/data/secondaryEleventhGuides.json';
import eleventhChordGuides from '@/data/eleventhChordGuides.json';
import secondaryNinthGuides from '@/data/secondaryNinthGuides.json';
import ninthChordGuides from '@/data/ninthChordGuides.json';
import secondarySeventhGuides from '@/data/secondarySeventhGuides.json';
import seventhChordGuides from '@/data/seventhChordGuides.json';
import addNineGuides from '@/data/addNineGuides.json';
import BasicTriadGuide from '@/Components/Quiz/BasicTriadGuide';
import basicTriadGuides from '@/data/basicTriadGuides.json';
import IntervalGuide from '@/Components/Quiz/IntervalGuide';
import intervalGuides from '@/data/intervalGuides.json';
import MelodicDictationGuide from '@/Components/Quiz/MelodicDictationGuide';
import melodicDictationGuides from '@/data/melodicDictationGuides.json';
import AudioStimulus from '@/Components/Quiz/AudioStimulus';
import AppShell from '@/Layouts/AppShell';
import ChordNamingGuide from '@/Components/Quiz/ChordNamingGuide';
import PitchQuizGuide from '@/Components/Quiz/PitchQuizGuide';
import RelativeTonePitchGuide from '@/Components/Quiz/RelativeTonePitchGuide';
import NotePairGuide from '@/Components/Quiz/NotePairGuide';
import KeyFindingGuide from '@/Components/Quiz/KeyFindingGuide';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { tk, useTrans } from '@/lib/i18n';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

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

function BulbIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 18h6M10 21h4M8 11a4 4 0 118 0c0 1.8-1 2.6-2 3.5-.6.6-1 1.1-1 1.5h-2c0-.4-.4-.9-1-1.5-1-.9-2-1.7-2-3.5z" />
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

function CheckCircleIcon({ filled = false, ...props }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="9" fill={filled ? 'currentColor' : 'none'} />
            <path d="M8 12.5l2.5 2.5L16 9.5" stroke={filled ? 'white' : 'currentColor'} />
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

// Unicode's only brass-instrument emoji is 🎺 (trumpet) — no trombone or tuba
// of their own — so both are drawn instead, same reasoning as Marimba/Strings/Organ above.
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
// label now that Harpejji and Bass Guitar sit in the same category.
const INSTRUMENT_LABELS = { EP: tk('Rhodes'), Guitar: tk('Acoustic Guitar'), Saxophone: tk('Tenor Saxophone'), Voice: tk('Solo Choir') };

const INSTRUMENT_EMOJI = {
    All: '🔀',
    'Tropar Choir': '🎤',
    'Bass Guitar': '🎸',
    Bassoon: '🪈',
    Cello: '🎻',
    Celesta: '🔔',
    Clarinet: '🪈',
    Ensemble: '🎻',
    'Ensemble Choir': '🎤',
    EP: '🎹',
    'Electric Guitar': '🎸',
    Flute: '🪈',
    'Full Song': '💿',
    Guitar: '🎸',
    Harpejji: '🎸',
    Instruments: '🎹',
    Monophonic: '🪈',
    Oboe: '🪈',
    Piano: '🎹',
    Saxophone: '🎷',
    'Steel Drum': '🥁',
    'Sustained Choir': '🎤',
    Trumpet: '🎺',
    'Upright Bass': '🎻',
    'Vocal Stems': '🎙️',
    Violin: '🎻',
    Voice: '🎤',
};

// The instrument-category groupings members choose a sound within — "just the
// design" pass: UI-only, grouping whatever sounds a quiz already offers
// (QuizCatalog::soundsFor on the backend) into families, nothing about which
// sounds a quiz offers changes here. Several of these sounds don't exist for
// any quiz yet (Organ, Cello, Upright Bass, Trumpet, Flute, Celesta, Vibraphone,
// Bass Guitar) — they simply won't appear until a quiz's `sounds` list
// actually includes them.
// `name` doubles as an identifier (compared, keyed into FAMILY_INSTRUMENTS) — it's
// only translated where it's displayed; `pickPrompt` is the drill-in subtitle.
const SOUND_CATEGORIES = [
    { name: tk('Piano'), pickPrompt: tk('Pick which piano instrument plays this quiz.'), sounds: ['Piano', 'EP', 'Upright Piano', 'Organ'] },
    { name: tk('Guitar'), pickPrompt: tk('Pick which guitar instrument plays this quiz.'), sounds: ['Guitar', 'Electric Guitar', 'Harpejji', 'Bass Guitar'] },
    { name: tk('Strings'), pickPrompt: tk('Pick which strings instrument plays this quiz.'), sounds: ['Violin', 'Strings', 'Cello', 'Upright Bass'] },
    { name: tk('Brass'), pickPrompt: tk('Pick which brass instrument plays this quiz.'), sounds: ['Saxophone', 'Trombone', 'Tuba', 'Trumpet'] },
    { name: tk('Vocals'), pickPrompt: tk('Pick which vocals instrument plays this quiz.'), sounds: ['Voice', 'Tropar Choir', 'Ensemble Choir', 'Sustained Choir'] },
    { name: tk('Percussion'), pickPrompt: tk('Pick which percussion instrument plays this quiz.'), sounds: ['Marimba', 'Steel Drum', 'Celesta', 'Vibraphone'] },
    { name: tk('Woodwinds'), pickPrompt: tk('Pick which woodwinds instrument plays this quiz.'), sounds: ['Flute', 'Clarinet', 'Oboe', 'Bassoon'] },
];

function categoryFor(sound) {
    return SOUND_CATEGORIES.find((category) => category.sounds.includes(sound))?.name ?? null;
}

// The question sound picker's initial selection — deliberately strict, unlike
// any instrument-family fallback. Pre-fills the member's Default Instrument only
// when it's an exact match for something this quiz actually has real audio
// for; otherwise leaves the picker empty rather than silently substituting a
// family sibling or "whatever's first". A silent substitution used to mean a
// member defaulted to Bass Guitar could open a quiz with no Bass Guitar audio
// and find themselves already set to Guitar (or something else) without ever
// choosing it — and separately, Reference Audio resolved its own instrument
// independently, so the two could disagree with nothing to tell the member
// why. Requiring an explicit tap here means there's exactly one decision
// instead of two silent ones, and Reference Audio (see ReferenceAudioModal)
// now follows this same selection instead of re-deciding on its own.
function resolveExactSound(defaultInstrument, quizSounds, availableSounds) {
    if (!defaultInstrument) return null;
    const real = availableSounds?.length ? availableSounds : quizSounds;
    return real?.includes(defaultInstrument) ? defaultInstrument : null;
}

// Find the Key #1/#2's "sounds" are recording types (Monophonic, Full Song,
// …), not instruments — resolveExactSound above never has anything to match
// a Default Instrument against there, so it always comes back empty and the
// member would always have to tap a tile before starting. "All" exists
// specifically as this picker's no-filter option, so pre-select it instead
// of leaving the picker blank for a category where there's no real default
// to resolve in the first place.
function resolveInitialSound(defaultInstrument, quizSounds, availableSounds) {
    const exact = resolveExactSound(defaultInstrument, quizSounds, availableSounds);
    if (exact) return exact;

    const soundsAreInstruments = (quizSounds?.length ?? 0) > 0 && categoryFor(quizSounds[0]) !== null;
    if (!soundsAreInstruments && quizSounds?.includes('All')) return 'All';

    return null;
}

// Mostly mallet instruments (Marimba, Celesta, Vibraphone), so the category tab
// reuses MarimbaIcon rather than a generic 🥁 drum emoji — Steel Drum gets its
// own 🥁 at the per-sound level instead (see INSTRUMENT_EMOJI above).
function CategoryIcon({ name, size = 24 }) {
    return <InstrumentIcon sound={FAMILY_INSTRUMENTS[name] ?? 'Piano'} size={size} />;
}

function SoundIcon({ sound, size = 24 }) {
    if (instrumentIcons[sound]) return <InstrumentIcon sound={sound} size={size} />;
    if (sound === 'Marimba') return <MarimbaIcon size={size} />;
    if (sound === 'Strings') return <StringsIcon size={size} />;
    if (sound === 'Organ') return <OrganIcon size={size} />;
    if (sound === 'Trombone') return <TromboneIcon size={size} />;
    if (sound === 'Tuba') return <TubaIcon size={size} />;
    return <span style={{ fontSize: size * 0.85 }}>{INSTRUMENT_EMOJI[sound] ?? '🎵'}</span>;
}

function CompletionCheck({ quiz }) {
    const t = useTrans();
    const completed = quiz.status === 'completed' || quiz.manuallyCompleted || quiz.score != null;
    if (!completed) return null;

    return (
        <span className="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
            <CheckIcon aria-hidden="true" className="h-2.5 w-2.5" />
            <span className="sr-only">{t('Completed')}</span>
        </span>
    );
}

function SidebarRow({ quiz, categorySlug, selected }) {
    const t = useTrans();

    return (
        <Link
            href={route('quiz.category', { category: categorySlug, quiz: quiz.slug })}
            aria-current={selected ? 'page' : undefined}
            preserveScroll
            preserveState
            replace
            className={`relative flex w-full items-center gap-2.5 rounded-lg border px-2.5 py-2 text-left transition-all duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand ${
                selected ? 'border-neutral-300 bg-neutral-100 shadow-sm' : 'border-neutral-200 bg-white hover:border-neutral-300 hover:bg-neutral-50'
            }`}
        >
            {selected && <span aria-hidden="true" className="absolute inset-y-2 left-0 w-[3px] rounded-r-full bg-brand" />}
            <span
                className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${
                    selected ? 'bg-white text-neutral-700' : 'bg-neutral-50 text-neutral-600'
                }`}
            >
                <PlayIcon className="h-3.5 w-3.5" />
            </span>
            <p className={`min-w-0 flex-1 truncate text-sm font-semibold text-neutral-900`}>
                {t(quiz.title)}
            </p>
            <CompletionCheck quiz={quiz} />
        </Link>
    );
}

// Same row style as the desktop SidebarRow (gray fill and a red accent when selected,
// with green checks for completed quizzes) — tapping still navigates to its own preview screen rather than
// opening inline, so unlike SidebarRow this is a plain navigation, not preserveState.
function MobileCategoryRow({ quiz, categorySlug, selected }) {
    const t = useTrans();

    return (
        <Link
            href={route('quiz.category', { category: categorySlug, quiz: quiz.slug })}
            aria-current={selected ? 'page' : undefined}
            className={`relative flex w-full items-center gap-2.5 rounded-lg border px-2.5 py-2 text-left transition-all duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand ${
                selected ? 'border-neutral-300 bg-neutral-100 shadow-sm' : 'border-neutral-200 bg-white hover:border-neutral-300 hover:bg-neutral-50'
            }`}
        >
            {selected && <span aria-hidden="true" className="absolute inset-y-2 left-0 w-[3px] rounded-r-full bg-brand" />}
            <span
                className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${
                    selected ? 'bg-white text-neutral-700' : 'bg-neutral-50 text-neutral-600'
                }`}
            >
                <PlayIcon className="h-3.5 w-3.5" />
            </span>
            <p className={`min-w-0 flex-1 truncate text-sm font-semibold text-neutral-900`}>
                {t(quiz.title)}
            </p>
            <CompletionCheck quiz={quiz} />
        </Link>
    );
}

// Row of instrument tiles — the same tile design used before categories existed,
// now reused both as the flat fallback (Find the Key #1/#2, whose "sounds" are
// recording types, not instruments) and as the per-category tile row below the
// category tabs.
function SoundTiles({ sounds, sound, onSelect }) {
    const t = useTrans();

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
                            {t(INSTRUMENT_LABELS[option] ?? option)}
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
    const t = useTrans();
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
                <p className="text-xs font-semibold text-neutral-700">{t('Choose a sound')}</p>
                <SoundTiles sounds={sounds} sound={sound} onSelect={onSelect} />
            </div>
        );
    }

    return (
        <div className="mt-4">
            <p className="text-xs font-semibold text-neutral-700">{t('Choose a sound')}</p>

            <button
                type="button"
                onClick={() => setOpen(true)}
                className={`mt-2.5 flex w-full items-center gap-2.5 rounded-xl border px-3.5 py-3 text-left transition-colors duration-150 hover:bg-neutral-50 ${
                    sound ? 'border-black/10 bg-white' : 'border-brand/30 bg-brand/5'
                }`}
            >
                {sound ? (
                    <>
                        <SoundIcon sound={sound} />
                        <span className="flex-1 text-sm font-semibold text-neutral-900">{t(INSTRUMENT_LABELS[sound] ?? sound)}</span>
                        {sound === defaultInstrument && (
                            <span className="rounded-full bg-brand/10 px-2 py-0.5 text-[10px] font-semibold text-brand">{t('Default')}</span>
                        )}
                    </>
                ) : (
                    // The member's Default Instrument isn't available for this
                    // quiz (see resolveExactSound) — nothing pre-filled, so
                    // Start Quiz/Reference Audio stay disabled until they tap
                    // in and pick one of what IS actually available below.
                    <span className="flex-1 text-sm font-semibold text-brand">{t('Choose a sound to continue')}</span>
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
                        role="dialog"
                        aria-modal="true"
                        aria-label={t('Choose a sound')}
                        className={`flex max-h-[85vh] w-full flex-col overflow-hidden rounded-t-2xl bg-white shadow-xl transition-transform duration-300 ease-out sm:max-w-sm sm:rounded-2xl ${
                            entered ? 'translate-y-0' : 'translate-y-full'
                        }`}
                        onClick={(event) => event.stopPropagation()}
                    >
                        {/* Grab handle — mobile bottom-sheet only, matches the app's native sheets. */}
                        <div className="flex shrink-0 justify-center pb-1 pt-2.5 sm:hidden">
                            <div className="h-1.5 w-10 rounded-full bg-neutral-200" />
                        </div>

                        <div className="shrink-0 overflow-y-auto px-5 pb-4 pt-2 sm:pt-4">
                            <div className="flex flex-col items-center text-center">
                                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-brand">
                                    {drillCategory ? <CategoryIcon name={drillCategory} size={26} /> : <SpeakerIcon className="h-6 w-6" />}
                                </span>
                                <p className="mt-3 text-lg font-bold text-neutral-900">{drillCategory ? t(drillCategory) : t('Choose a sound')}</p>
                                <p className="mt-1 text-sm text-neutral-500">
                                    {drillCategory
                                        ? t(SOUND_CATEGORIES.find((category) => category.name === drillCategory)?.pickPrompt ?? 'Pick which instrument plays this quiz.')
                                        : t('Pick which instrument plays this quiz.')}
                                </p>
                            </div>

                            <p className="mt-3 text-center text-sm text-neutral-600">
                                {t('Current sound:')} <span className="font-semibold text-neutral-900">{sound ? t(INSTRUMENT_LABELS[sound] ?? sound) : t('Not selected')}</span>
                            </p>

                            {drillCategory && (
                                <button
                                    type="button"
                                    onClick={() => setDrillCategory(null)}
                                    className="mx-auto mt-3 flex items-center gap-1 text-xs font-semibold text-neutral-400 hover:text-neutral-600"
                                >
                                    <ChevronLeftIcon className="h-3.5 w-3.5" />
                                    {t('All categories')}
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
                                                  className="flex min-h-11 w-full items-center gap-2.5 px-3.5 py-3 text-left transition-colors duration-150 hover:bg-neutral-100"
                                              >
                                                  <CategoryIcon name={category.name} size={24} />
                                                  <span className="flex-1 text-sm font-medium text-neutral-800">{t(category.name)}</span>
                                                  {pickedHere && (
                                                      <span className="flex items-center gap-1.5 text-sm font-semibold text-brand">
                                                          {t(INSTRUMENT_LABELS[pickedHere] ?? pickedHere)}
                                                          <CheckIcon aria-hidden="true" className="h-4 w-4" />
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
                                                      aria-pressed={active}
                                                      onClick={() => {
                                                          onSelect(option);
                                                          closeMenu();
                                                      }}
                                                      className="flex min-h-11 w-full items-center gap-2.5 px-3.5 py-3 text-left transition-colors duration-150 hover:bg-neutral-100"
                                                  >
                                                      <SoundIcon sound={option} size={26} />
                                                      <span
                                                          className={`flex-1 text-sm ${active ? 'font-semibold text-brand' : 'font-medium text-neutral-800'}`}
                                                      >
                                                          {t(INSTRUMENT_LABELS[option] ?? option)}
                                                      </span>
                                                      {isDefault && (
                                                          <span className="rounded-full bg-brand/10 px-2 py-0.5 text-[10px] font-semibold text-brand">
                                                              {t('Default')}
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
                                {t('Cancel')}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

// Resolve one clip at a time for the exact selected instrument. Each shuffled
// pass visits the complete clip pool once; unavailable clips are skipped.
function ReferenceAudioModal({ quizSlug, pool, sound, onClose, isMelodicDictation }) {
    const t = useTrans();
    const poolIds = useMemo(() => pool.map((p) => p.id), [pool]);
    const labelById = useMemo(() => new Map(pool.map((p) => [p.id, p.label])), [pool]);

    const [queue, setQueue] = useState(() => shuffled(poolIds));
    const [position, setPosition] = useState(0);
    const [clip, setClip] = useState(null);
    const [loading, setLoading] = useState(true);
    const [unavailable, setUnavailable] = useState(false);
    const requestRef = useRef(0);

    // A clip id can fail to resolve for the current instrument (narrow-range
    // instruments don't cover every answer) — try the rest of this pass's
    // queue, in order, before giving up, so one gap doesn't look like the
    // whole quiz has no reference audio for this sound.
    const loadFrom = useCallback(async (q, startPosition) => {
        const requestId = ++requestRef.current;
        setLoading(true);
        setUnavailable(false);
        for (let tries = 0; tries < q.length; tries++) {
            const pos = (startPosition + tries) % q.length;
            const id = q[pos];
            let data;
            try {
                const response = await fetch(`${route('quiz.reference-audio-clip', [quizSlug, id])}?sound=${encodeURIComponent(sound)}`);
                data = await response.json();
            } catch {
                data = null;
            }
            if (requestRef.current !== requestId) return; // superseded by a newer Next press
            if (data?.clip) {
                setClip(data.clip);
                setPosition(pos);
                setLoading(false);
                return;
            }
        }
        setClip(null);
        setLoading(false);
        setUnavailable(true);
    }, [quizSlug, sound]);

    // A fresh shuffled pass whenever the pool or the chosen instrument changes.
    useEffect(() => {
        const next = shuffled(poolIds);
        setQueue(next);
        if (next.length === 0) {
            setClip(null);
            setLoading(false);
            setUnavailable(true);
            return;
        }
        loadFrom(next, 0);
        // loadFrom already depends on quizSlug/sound; poolIds is the real trigger here.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [poolIds, sound]);

    function next() {
        if (loading) return;
        const nextPosition = position + 1;
        if (nextPosition >= queue.length) {
            const reshuffledQueue = reshuffleAvoiding(poolIds, queue[position]);
            setQueue(reshuffledQueue);
            loadFrom(reshuffledQueue, 0);
        } else {
            loadFrom(queue, nextPosition);
        }
    }

    useEffect(() => {
        const onKeyDown = (event) => event.key === 'Escape' && onClose();
        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, [onClose]);

    const label = clip ? labelById.get(queue[position]) ?? clip.label : null;

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            onClick={(e) => e.target === e.currentTarget && onClose()}
        >
            <div className="w-full max-w-lg rounded-2xl bg-neutral-900 p-8 text-white">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2 text-white/50">
                        <HeadsetIcon className="h-[18px] w-[18px]" />
                        <p className="text-xs font-semibold">{isMelodicDictation ? t('Listen and learn') : t('Reference Audio')}</p>
                    </div>
                    <button type="button" onClick={onClose} aria-label={t('Close')} className="text-white/50 hover:text-white">
                        <CloseIcon className="h-5 w-5" />
                    </button>
                </div>

                {clip ? (
                    <>
                        <p className="mt-4 text-center text-xs font-semibold text-white/60">{t(INSTRUMENT_LABELS[sound] ?? sound)}</p>
                        <div className="mt-3">
                            <AudioStimulus key={`${sound}:${queue[position]}`} questionKey={`${sound}:${queue[position]}`} src={clip.url} notes={clip.notes} dark />
                        </div>

                        {!isMelodicDictation && (
                            <p className="mt-6 text-center text-sm text-white/50">{t('Listen and familiarize yourself with the sound.')}</p>
                        )}
                        <p className={`font-display text-center text-4xl font-semibold ${isMelodicDictation ? 'mt-6' : 'mt-3'}`}>{t(label)}</p>

                        {queue.length > 1 && (
                            <div className="mt-6 flex flex-col items-center gap-2.5">
                                <button
                                    type="button"
                                    disabled={loading}
                                    onClick={next}
                                    className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-emerald-700 disabled:opacity-60"
                                >
                                    {t('Next')}
                                </button>
                            </div>
                        )}
                    </>
                ) : loading ? (
                    <p className="mt-10 text-center text-sm text-white/50">{t('Loading…')}</p>
                ) : unavailable ? (
                    <p className="mt-10 text-center text-sm text-white/50">
                        {sound
                            ? t('Reference audio is not available for :sound yet.', { sound: t(INSTRUMENT_LABELS[sound] ?? sound) })
                            : t('Reference audio is not available for the selected sound yet.')}
                    </p>
                ) : null}
            </div>
        </div>
    );
}

function LastAttemptCard({ quiz, className }) {
    const t = useTrans();

    return (
        <div className={`items-center justify-between gap-4 rounded-2xl border border-black/5 bg-neutral-50 px-5 py-4 ${className}`}>
            <div>
                <p className="text-sm font-semibold text-neutral-900">{t('Last attempt')}</p>
                <p className="mt-1 text-xs text-neutral-500">{quiz.score != null ? t('Your latest completed quiz') : t('Not attempted yet')}</p>
            </div>
            <p className="text-3xl font-bold tabular-nums tracking-tight text-neutral-900">{quiz.score != null ? `${quiz.score}%` : '—'}</p>
        </div>
    );
}

function DetailPane({ quiz, standalone = false }) {
    const t = useTrans();
    const defaultInstrument = usePage().props.auth?.user?.default_instrument ?? null;
    const [processing, setProcessing] = useState(false);
    const [sound, setSound] = useState(resolveInitialSound(defaultInstrument, quiz?.sounds, quiz?.availableSounds));
    const [blockedNotice, setBlockedNotice] = useState(false);
    const [audioModalOpen, setAudioModalOpen] = useState(false);

    useEffect(() => {
        setSound(resolveInitialSound(defaultInstrument, quiz?.sounds, quiz?.availableSounds));
        setBlockedNotice(false);
        setAudioModalOpen(false);
    }, [quiz?.slug, defaultInstrument]);

    if (!quiz) {
        return (
            <div className="flex h-full min-h-[320px] items-center justify-center rounded-2xl border border-black/5 bg-white p-8 text-center text-sm text-neutral-500">
                {t('No quizzes in this category are ready yet. Check back soon.')}
            </div>
        );
    }

    const chordQualityGuide = basicTriadGuides[quiz.title] ?? addNineGuides[quiz.title] ?? seventhChordGuides[quiz.title] ?? ninthChordGuides[quiz.title] ?? eleventhChordGuides[quiz.title] ?? thirteenthChordGuides[quiz.title]
        ?? (quiz.category === 'Secondary 7th Chords' ? secondarySeventhGuides[quiz.title] : null)
        ?? (quiz.category === 'Secondary 9th Chords' ? secondaryNinthGuides[quiz.title] : null)
        ?? (quiz.category === 'Secondary 11th Chords' ? secondaryEleventhGuides[quiz.title] : null);
    const scaleGuide = scaleGuides[quiz.title];
    const modalHarmonyGuide = modalHarmonyGuides[quiz.title] ?? modalVoicingGuides[quiz.title];
    const progressionGuide = quiz.category === 'Chord Progressions' ? progressionGuides[quiz.title] : null;
    const extensionGuide = majorExtensionGuides[quiz.title] ?? minorExtensionGuides[quiz.title] ?? dominantExtensionGuides[quiz.title];
    const hasChordToneGuide = Object.hasOwn(chordToneGuides, quiz.title);
    const hasBasicTriadGuide = Boolean(chordQualityGuide);
    const hasIntervalGuide = Object.hasOwn(intervalGuides, quiz.title);
    const hasMelodicDictationGuide = Object.hasOwn(melodicDictationGuides, quiz.title);
    const hasNamingGuide = Object.hasOwn(chordNamingGuides, quiz.title);
    const hasPitchGuide = ['Find the Note', 'Find the Pitch'].includes(quiz.title);
    const hasRelativeTonePitchGuide = ['Relative Tone Pitch #1', 'Relative Tone Pitch #2'].includes(quiz.title);
    const hasNotePairGuide = /^(Melodic|Harmonic) (Second|Third|Fourth|Fifth|Sixth|Seventh)$/.test(quiz.title);
    const hasKeyFindingGuide = ['Find the Key #1', 'Find the Key #2'].includes(quiz.title);
    const resuming = quiz.status === 'in_progress';
    const completed = quiz.status === 'completed';
    // Cheap on purpose: whether this quiz has any reference pool at all, not
    // whether the current instrument specifically resolves — checking that
    // would mean resolving audio just to decide if the button should show.
    // If the current instrument truly has nothing, the modal itself says so.
    const hasReferenceAudio = (quiz.referenceAudioPool?.length ?? 0) > 0;
    // The top badge/sidebar checkmark reflect a manual mark too, but the
    // Start/Continue/Retry button and score text below stay tied to a real
    // attempt or completed score — manually marking a quiz complete doesn't mean it's been
    // played, so "Retry Quiz" would be the wrong thing to say.
    //
    // A completed/manually-marked quiz keeps showing as completed even with a
    // leftover in-progress attempt (e.g. after a cancelled retry) — otherwise
    // restarting it would make an already-earned completion look undone.

    // Only quizzes that actually offer a sound picker need one chosen before
    // starting — a quiz with no sounds at all (SoundPicker renders nothing)
    // shouldn't get blocked waiting on a selection that was never offered.
    const soundRequired = (quiz.sounds?.length ?? 0) > 0;
    const soundMissing = soundRequired && !sound;
    // Find the Key #1/#2's "sounds" are recording types (Monophonic, Full
    // Song, …), not instruments — categoryFor() only recognizes real
    // instrument names, so it comes back null for these. The member's
    // Default Instrument was never meant to apply here (there's nothing to
    // default to), so the missing-sound message shouldn't name it or imply
    // it was looked for and failed.
    const soundsAreInstruments = (quiz.sounds?.length ?? 0) > 0 && categoryFor(quiz.sounds[0]) !== null;

    function toggleCompleted() {
        router.post(route('quiz.mark-completed', quiz.slug), {}, { preserveScroll: true, preserveState: true });
    }

    function toggleBookmark() {
        router.post(route('quiz.bookmark', quiz.slug), {}, { preserveScroll: true, preserveState: true });
    }

    // Same DEFAULT_TIPS the mobile app falls back to for every quiz it has no
    // specific override for (app/quiz-preview.tsx) — not invented copy.
    const tips = [
        t('Use headphones for best results.'),
        t('Listen carefully before answering.'),
        t('Take your time and answer the questions.'),
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
        <div className={standalone ? '' : 'rounded-2xl border border-black/5 bg-white p-6 sm:p-7'}>
            <div className="flex items-start justify-between gap-4 xl:items-center">
                <div className="min-w-0 flex-1">
                    {standalone
                        ? <h1 className="text-3xl font-bold tracking-tight text-neutral-900">{t(quiz.title)}</h1>
                        : <h2 className="text-2xl font-bold text-neutral-900">{t(quiz.title)}</h2>}
                </div>
                <LastAttemptCard quiz={quiz} className="hidden shrink-0 xl:flex" />
            </div>

            <QuizVideoContext.Provider value={quiz.video_embed}>
            {scaleGuide ? (
                <ScaleGuide
                    guide={scaleGuide}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : modalHarmonyGuide ? (
                <ModalHarmonyGuide
                    guide={modalHarmonyGuide}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : progressionGuide ? (
                <ProgressionGuide
                    guide={progressionGuide}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : extensionGuide ? (
                <ChordExtensionGuide
                    guide={extensionGuide}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : hasChordToneGuide ? (
                <ChordToneGuide
                    quizTitle={quiz.title}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : hasBasicTriadGuide ? (
                <BasicTriadGuide
                    guide={chordQualityGuide}
                    quizTitle={quiz.title}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : hasIntervalGuide ? (
                <IntervalGuide
                    quizTitle={quiz.title}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : hasMelodicDictationGuide ? (
                <MelodicDictationGuide
                    quizTitle={quiz.title}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : hasNamingGuide ? (
                <ChordNamingGuide
                    quizTitle={quiz.title}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : hasPitchGuide ? (
                <PitchQuizGuide
                    quizTitle={quiz.title}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : hasRelativeTonePitchGuide ? (
                <RelativeTonePitchGuide
                    quizTitle={quiz.title}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : hasNotePairGuide ? (
                <NotePairGuide
                    quizTitle={quiz.title}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : hasKeyFindingGuide ? (
                <KeyFindingGuide
                    quizTitle={quiz.title}
                    difficulty={quiz.difficulty}
                    onListen={() => setAudioModalOpen(true)}
                    listenAvailable={hasReferenceAudio}
                    listenDisabled={soundMissing}
                />
            ) : quiz.video_embed ? (
                <VideoEmbed
                    html={quiz.video_embed}
                    className="-mx-6 mt-5 aspect-video overflow-hidden rounded-2xl bg-neutral-900 [&_iframe]:h-full [&_iframe]:w-full sm:-mx-7"
                />
            ) : (
                <div className="-mx-6 mt-5 flex aspect-video items-center justify-center rounded-2xl bg-neutral-100 text-sm text-neutral-400 sm:-mx-7">
                    {t('No video for this quiz yet')}
                </div>
            )}

            </QuizVideoContext.Provider>

            <div className="mt-4 rounded-2xl border border-black/5 bg-neutral-50 p-4">
                <div className="flex items-center gap-2 text-neutral-400">
                    <BulbIcon className="h-4 w-4" />
                    <p className="text-xs font-semibold">{t('Tips')}</p>
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

            {quiz.score != null && <LastAttemptCard quiz={quiz} className="mt-5 flex xl:hidden" />}

            <div className="mt-6 flex gap-2.5">
                <button
                    type="button"
                    disabled={processing || soundMissing}
                    onClick={start}
                    className="flex-1 rounded-xl bg-neutral-900 py-3.5 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90 disabled:opacity-60"
                >
                    {completed || quiz.score != null ? t('Retry Quiz') : resuming ? t('Continue Quiz') : t('Start Quiz')}
                </button>
                {hasReferenceAudio && (
                    <button
                        type="button"
                        disabled={soundMissing}
                        onClick={() => setAudioModalOpen(true)}
                        className="flex shrink-0 items-center justify-center gap-2 rounded-xl bg-green-600 px-4 py-3.5 text-sm font-extrabold text-white shadow-sm transition-all duration-150 hover:bg-green-700 hover:shadow active:scale-[0.98] disabled:opacity-50"
                    >
                        <HeadsetIcon className="h-5 w-5" />
                        {t('Listen Mode')}
                    </button>
                )}
            </div>
            {soundMissing && (
                <p className="mt-2.5 text-center text-sm text-brand">
                    {soundsAreInstruments && defaultInstrument
                        ? t(":sound isn't available for this quiz — choose a sound above to continue.", { sound: t(INSTRUMENT_LABELS[defaultInstrument] ?? defaultInstrument) })
                        : t('Choose a sound above to continue.')}
                </p>
            )}
            {blockedNotice && (
                <p className="mt-2.5 text-center text-sm text-neutral-500">{t("Questions for this quiz haven't been added yet.")}</p>
            )}

            {audioModalOpen && (
                <ReferenceAudioModal
                    quizSlug={quiz.slug}
                    pool={quiz.referenceAudioPool ?? []}
                    sound={sound}
                    onClose={() => setAudioModalOpen(false)}
                    isMelodicDictation={quiz.category === 'Melodic Dictation'}
                />
            )}

            {quiz.available ? (
                <div className="mt-3 flex gap-2.5">
                    <button
                        type="button"
                        onClick={toggleCompleted}
                        aria-pressed={Boolean(quiz.manuallyCompleted)}
                        className={`flex min-h-[48px] flex-1 items-center justify-center gap-2 rounded-xl border-[1.5px] text-sm font-bold transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700 ${
                            quiz.manuallyCompleted ? 'border-emerald-600 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'border-neutral-300 bg-white text-neutral-700 hover:border-neutral-400 hover:bg-neutral-50'
                        }`}
                    >
                        <CheckCircleIcon filled={quiz.manuallyCompleted} aria-hidden="true" className="h-5 w-5" />
                        {quiz.manuallyCompleted ? t('Completed') : t('Mark as Completed')}
                    </button>
                    <button
                        type="button"
                        onClick={toggleBookmark}
                        aria-pressed={Boolean(quiz.bookmarked)}
                        aria-label={quiz.bookmarked ? t('Remove bookmark') : t('Bookmark this quiz')}
                        className={`flex min-h-[48px] w-[52px] shrink-0 items-center justify-center rounded-xl border-[1.5px] transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand ${
                            quiz.bookmarked ? 'border-brand bg-red-50 text-brand hover:bg-red-100' : 'border-neutral-300 bg-white text-neutral-500 hover:border-neutral-400 hover:bg-neutral-50 hover:text-neutral-700'
                        }`}
                    >
                        <BookmarkIcon filled={quiz.bookmarked} aria-hidden="true" className="h-5 w-5" />
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
                        title={t('Coming soon')}
                        className="flex min-h-[48px] flex-1 cursor-default items-center justify-center gap-2 rounded-xl border-[1.5px] border-brand/40 text-sm font-bold text-brand/40"
                    >
                        <CheckCircleIcon className="h-5 w-5" />
                        {t('Mark as Completed')}
                    </button>
                    <button
                        type="button"
                        disabled
                        title={t('Coming soon')}
                        aria-label={t('Bookmark this quiz')}
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
    const t = useTrans();
    const [guideOpen, setGuideOpen] = useState(false);
    const illustratedGuide = illustratedGuides[category];
    const selected = quizzes.find((quiz) => quiz.slug === selectedSlug) ?? quizzes[0] ?? null;

    return (
        <AppShell
            eyebrow={t('Start Quiz')}
            title={t(category)}
            intro={t('Choose a quiz')}
            headerClassName={selectedSlug ? 'hidden xl:flex' : 'flex'}
            breadcrumbTitle={selectedSlug ? t(selected?.title) : t(category)}
            action={illustratedGuide && (
                <div className="w-full lg:w-auto lg:self-center">
                    <button type="button" onClick={() => setGuideOpen(true)} aria-haspopup="dialog" className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 transition-colors hover:border-neutral-400 hover:text-neutral-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4 shrink-0">
                            <path d="M14 3H6a1 1 0 00-1 1v16a1 1 0 001 1h12a1 1 0 001-1V8z" />
                            <path d="M14 3v5h5M8 12h8M8 16h6" />
                        </svg>
                        {t('View illustrated guide')}
                        <span className="sr-only"> {t('(PDF, :pages pages, opens inside KhordQuiz)', { pages: illustratedGuide.pages })}</span>
                    </button>
                </div>
            )}
        >
            <Head title={selected ? `${t(selected.title)} · ${t(category)}` : t(category)} />
            <IllustratedGuideDialog guide={illustratedGuide} open={guideOpen} onClose={() => setGuideOpen(false)} />


            {/* Phones and tablets: the list and preview are separate screens, like the app — never
                both stacked on one page. selectedSlug only exists once a specific quiz is in
                the URL (quiz.category route's optional {quiz} segment), so it alone decides
                which one shows. */}
            {selectedSlug ? (
                <div className="xl:hidden">
                    <DetailPane quiz={selected} standalone />
                </div>
            ) : (
                <div className="rounded-2xl border border-black/5 bg-white p-4 shadow-sm xl:hidden">
                    <div className="mb-4 flex items-center justify-between text-sm">
                        <span className="font-semibold text-neutral-700">
                            {t(':done/:total Completed', { done: progress.done, total: progress.total })}
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
                                selected={quiz.slug === selectedSlug}
                            />
                        ))}
                    </div>
                </div>
            )}

            <div className="hidden gap-6 xl:grid xl:grid-cols-[360px_minmax(0,1fr)]">
                <div className="rounded-2xl border border-black/5 bg-white p-4 shadow-sm">
                    <div className="mb-4 flex items-center justify-between text-sm">
                        <span className="font-semibold text-neutral-700">
                            {t(':done/:total Completed', { done: progress.done, total: progress.total })}
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
