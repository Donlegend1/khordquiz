import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';
import { useTrans } from '@/lib/i18n';

function NoteIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 18V9l9-2v9M9 18a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 019 18zM18 16a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 0118 16z" />
        </svg>
    );
}

// Relative Pitch: listen for the sound.
function WaveformIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 12v2M8 9v8M12 5v16M16 9v8M20 12v2" />
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

function IntervalIcon(props) {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
        <path d="M5 6h4M5 18h4M7 6v12M14 8l3-3 3 3M17 5v14M14 16l3 3 3-3" />
    </svg>;
}

function TriadIcon(props) {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
        <circle cx="5" cy="17" r="2.5" /><circle cx="12" cy="6" r="2.5" /><circle cx="19" cy="17" r="2.5" />
        <path d="m6.5 14.8 4-6.5m3 0 4 6.5M8 17h8" />
    </svg>;
}

function DegreeIcon({ degree, secondary = false, ...props }) {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
        <path d="M3 7h5M3 12h5M3 17h5" />
        <text x="16" y="17" textAnchor="middle" fill="currentColor" stroke="none" fontSize={degree.length > 1 ? '11' : '14'} fontWeight="700" fontFamily="inherit">{degree}</text>
        {secondary && <path d="m17 2 2 2-2 2-2-2z" fill="currentColor" stroke="none" />}
    </svg>;
}

function ExtensionIcon({ quality, ...props }) {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" {...props}>
        <rect x="3" y="4" width="13" height="16" rx="2" />
        <text x="9.5" y="15.5" textAnchor="middle" fill="currentColor" stroke="none" fontSize="10" fontWeight="700" fontFamily="inherit">{quality}</text>
        <path d="M20 8v8M17 12h6" />
    </svg>;
}

function ToneIcon(props) {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
        <path d="M4 6h16M4 12h16M4 18h16" /><circle cx="12" cy="12" r="3" fill="white" />
    </svg>;
}

function NamingIcon(props) {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
        <path d="M3 4h9l9 9-8 8-10-10z" /><circle cx="7.5" cy="8" r="1" /><path d="m11 12 4 4M14 11l3 3" />
    </svg>;
}

function HarmonyIcon(props) {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
        <path d="M4 7h16M4 12h16M4 17h16" /><circle cx="8" cy="17" r="2.5" fill="white" /><circle cx="16" cy="7" r="2.5" fill="white" />
    </svg>;
}

const CATEGORY_ICONS = {
    'Relative Pitch': WaveformIcon,
    'Melodic Dictation': NoteIcon,
    Intervals: IntervalIcon,
    'Basic Triads': TriadIcon,
    'Add 9': (props) => <DegreeIcon degree="+9" {...props} />,
    '7th Degree Chords': (props) => <DegreeIcon degree="7" {...props} />,
    'Secondary 7th Chords': (props) => <DegreeIcon degree="7" secondary {...props} />,
    '9th Degree Chords': (props) => <DegreeIcon degree="9" {...props} />,
    'Secondary 9th Chords': (props) => <DegreeIcon degree="9" secondary {...props} />,
    '11th Degree Chords': (props) => <DegreeIcon degree="11" {...props} />,
    'Secondary 11th Chords': (props) => <DegreeIcon degree="11" secondary {...props} />,
    '13th Degree Chords': (props) => <DegreeIcon degree="13" {...props} />,
    'Chord Tone Recognition': ToneIcon,
    'Major Chord Extensions': (props) => <ExtensionIcon quality="M" {...props} />,
    'Minor Chord Extensions': (props) => <ExtensionIcon quality="m" {...props} />,
    'Dominant Chord Extensions': (props) => <ExtensionIcon quality="7" {...props} />,
    'Chord Naming': NamingIcon,
    'Chord Progressions': ProgressionIcon,
    'Modal Harmony': HarmonyIcon,
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
    const t = useTrans();

    return (
        <AppShell eyebrow={t('Member')} title={t('Start Quiz')} intro={t('Choose a category to begin practising.')}>
            <Head title={t('Start Quiz')} />

            {categories.length === 0 ? (
                <div className="flex flex-col items-center rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-16 text-center">
                    <p className="text-sm text-neutral-500">{t('No quizzes are published yet. Check back soon.')}</p>
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
                                    className="group flex items-center gap-3 rounded-2xl border border-neutral-200 bg-white p-4 transition-colors duration-150 hover:border-neutral-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand active:bg-neutral-100 motion-reduce:transition-none"
                                >
                                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-600">
                                        <CategoryIcon aria-hidden="true" className="h-5 w-5" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-base font-bold leading-snug text-neutral-900">{category.name}</p>
                                        <p className="mt-0.5 text-sm text-neutral-600">
                                            {category.done > 0
                                                ? `${category.done} of ${category.quizCount} completed`
                                                : `${category.quizCount} ${category.quizCount === 1 ? 'quiz' : 'quizzes'} · Not started`}
                                        </p>
                                        {category.percent > 0 && (
                                            <div className="mt-1.5 flex items-center gap-2">
                                                <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-neutral-200/70">
                                                    <div className="h-full rounded-full bg-brand" style={{ width: `${category.percent}%` }} />
                                                </div>
                                                <span className="shrink-0 text-sm font-medium text-neutral-600">{category.percent}%</span>
                                            </div>
                                        ) : (
                                            <p className="mt-0.5 text-sm text-neutral-400">{t('Not attempted yet')}</p>
                                        )}
                                    </div>
                                    <ChevronIcon aria-hidden="true" className="h-4 w-4 shrink-0 text-neutral-500 transition-colors group-hover:text-brand group-focus-visible:text-brand motion-reduce:transition-none" />
                                </Link>
                            );
                        })}
                    </div>

                    <div className="hidden sm:grid sm:grid-cols-2 sm:gap-4 xl:grid-cols-3">
                        {categories.map((category) => {
                            const CategoryIcon = CATEGORY_ICONS[category.name] ?? NoteIcon;
                            return (
                                <Link
                                    key={category.slug}
                                    href={route('quiz.category', category.slug)}
                                    className="group flex flex-col justify-between gap-5 rounded-2xl border border-neutral-200 bg-white p-5 shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition-[border-color,box-shadow,background-color] duration-150 hover:border-neutral-400 hover:bg-neutral-50 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand active:bg-neutral-100 motion-reduce:transition-none"
                                >
                                    <div className="flex items-center gap-3">
                                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                                            <CategoryIcon aria-hidden="true" className="h-5 w-5" />
                                        </span>
                                        <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                                    </div>

                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-[15px] font-bold text-neutral-900">{t(category.name)}</p>
                                        <p className="mt-0.5 text-sm text-neutral-400">
                                            {category.quizCount === 1 ? t(':count quiz', { count: category.quizCount }) : t(':count quizzes', { count: category.quizCount })}
                                        </p>
                                    </div>

                                    <div>
                                        <div className="h-1.5 w-full overflow-hidden rounded-full bg-neutral-200/70">
                                            {category.percent > 0 && <div className="h-full rounded-full bg-brand" style={{ width: `${category.percent}%` }} />}
                                        </div>
                                        <div className="mt-2 flex items-center justify-between gap-2 text-xs font-medium text-neutral-600">
                                            <span>{category.done > 0 ? `${category.done} of ${category.quizCount} completed` : 'Not started'}</span>
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
