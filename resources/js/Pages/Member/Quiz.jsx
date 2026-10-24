import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';

function NoteIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 18V9l9-2v9M9 18a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 019 18zM18 16a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 0118 16z" />
        </svg>
    );
}

// Ear-training family: Relative Pitch, Melodic Dictation, Intervals.
function WaveformIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 12v2M8 9v8M12 5v16M16 9v8M20 12v2" />
        </svg>
    );
}

// Chord-building family: triads, add 9, degree chords (7th-13th) + their secondaries, extensions.
function ChordStackIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <rect x="4" y="5.5" width="16" height="3.5" rx="1.3" />
            <rect x="4" y="10.5" width="16" height="3.5" rx="1.3" />
            <rect x="4" y="15.5" width="16" height="3.5" rx="1.3" />
        </svg>
    );
}

// Chord Progressions: a sequence of chords flowing into one another.
function ProgressionIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="5" cy="17" r="2" />
            <circle cx="12" cy="9" r="2" />
            <circle cx="19" cy="14" r="2" />
            <path d="M6.5 15.5L10.3 10.7M13.7 10.2L17.3 12.8" />
        </svg>
    );
}

// Modal Voicings: a small keyboard.
function KeysIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <rect x="4" y="6" width="16" height="12" rx="1.5" />
            <path d="M8.5 6v7M13 6v7M17.5 6v7" />
        </svg>
    );
}

// Scales: ascending steps.
function ScaleIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 18h3v-3M7 15h3v-4M10 11h3v-4M13 7h3V4M16 4h3v14h-3" />
        </svg>
    );
}

const CATEGORY_ICONS = {
    'Relative Pitch': WaveformIcon,
    'Melodic Dictation': WaveformIcon,
    Intervals: WaveformIcon,
    'Basic Triads': ChordStackIcon,
    'Add 9': ChordStackIcon,
    '7th Degree Chords': ChordStackIcon,
    'Secondary 7th Chords': ChordStackIcon,
    '9th Degree Chords': ChordStackIcon,
    'Secondary 9th Chords': ChordStackIcon,
    '11th Degree Chords': ChordStackIcon,
    'Secondary 11th Chords': ChordStackIcon,
    '13th Degree Chords': ChordStackIcon,
    Extensions: ChordStackIcon,
    'Chord Progressions': ProgressionIcon,
    'Modal Voicings': KeysIcon,
    Scales: ScaleIcon,
};

function ChevronIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 5l7 7-7 7" />
        </svg>
    );
}

export default function Quiz({ categories }) {
    return (
        <AppShell eyebrow="Member" title="Start Quiz" intro="Choose a category to begin practising.">
            <Head title="Start Quiz" />

            {categories.length === 0 ? (
                <div className="flex flex-col items-center rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-16 text-center">
                    <p className="text-sm text-neutral-500">No quizzes are published yet. Check back soon.</p>
                </div>
            ) : (
                <>
                    {/* Compact single-column rows on mobile, matching the app's Quiz tab. */}
                    <div className="flex flex-col gap-3 sm:hidden">
                        {categories.map((category) => {
                            const CategoryIcon = CATEGORY_ICONS[category.name] ?? NoteIcon;
                            return (
                                <Link
                                    key={category.slug}
                                    href={route('quiz.category', category.slug)}
                                    className="flex items-center gap-3 rounded-2xl border border-black/5 bg-white p-4 transition-colors duration-150 active:bg-neutral-50"
                                >
                                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-600">
                                        <CategoryIcon className="h-5 w-5" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-base font-bold text-neutral-900">{category.name}</p>
                                        {category.percent > 0 ? (
                                            <div className="mt-1.5 flex items-center gap-2">
                                                <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-neutral-200/70">
                                                    <div className="h-full rounded-full bg-brand" style={{ width: `${category.percent}%` }} />
                                                </div>
                                                <span className="shrink-0 text-sm text-neutral-500">{category.percent}%</span>
                                            </div>
                                        ) : (
                                            <p className="mt-0.5 text-sm text-neutral-400">Not attempted yet</p>
                                        )}
                                    </div>
                                    <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                                </Link>
                            );
                        })}
                    </div>

                    <div className="hidden sm:grid sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
                        {categories.map((category) => {
                            const CategoryIcon = CATEGORY_ICONS[category.name] ?? NoteIcon;
                            return (
                                <Link
                                    key={category.slug}
                                    href={route('quiz.category', category.slug)}
                                    className="flex flex-col gap-4 rounded-2xl border border-black/5 bg-gradient-to-br from-white via-neutral-50 to-neutral-100/70 p-5 shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition-all duration-150 hover:border-brand/15 hover:from-brand/[0.04] hover:via-brand/[0.02] hover:to-brand/[0.04] hover:shadow-sm"
                                >
                                    <div className="flex items-start justify-between">
                                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-white to-neutral-200 text-neutral-600 shadow-[inset_0_1px_1px_rgba(255,255,255,0.8)]">
                                            <CategoryIcon className="h-5 w-5" />
                                        </span>
                                        <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                                    </div>

                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-[15px] font-bold text-neutral-900">{category.name}</p>
                                        <p className="mt-0.5 text-sm text-neutral-400">
                                            {category.quizCount} {category.quizCount === 1 ? 'quiz' : 'quizzes'}
                                        </p>
                                    </div>

                                    <div>
                                        <div className="h-1.5 w-full overflow-hidden rounded-full bg-neutral-200/70">
                                            {category.percent > 0 && <div className="h-full rounded-full bg-brand" style={{ width: `${category.percent}%` }} />}
                                        </div>
                                        <div className="mt-1.5 flex items-center justify-between text-xs text-neutral-400">
                                            <span>{category.done > 0 ? `${category.done}/${category.quizCount} done` : 'Not attempted yet'}</span>
                                            <span>{category.percent}%</span>
                                        </div>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                </>
            )}
        </AppShell>
    );
}
