import AppShell from '@/Layouts/AppShell';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { tk, useTrans } from '@/lib/i18n';

// Values double as keys into `paths` below — translate only at render.
const LEVELS = [tk('Beginner'), tk('Intermediate'), tk('Advanced')];

const COMING_UP_HEADINGS = {
    Beginner: tk('Coming up for beginners'),
    Intermediate: tk('Coming up for intermediates'),
    Advanced: tk('Coming up for advanced learners'),
};

const PATH_LABELS = {
    Beginner: tk('Up next · Beginner path'),
    Intermediate: tk('Up next · Intermediate path'),
    Advanced: tk('Up next · Advanced path'),
};

// Reflects a brand-new member: zero progress on every path, and nothing in "Practise
// again" yet, since that only fills in once you've actually answered something wrong.
// The path itself (what's first, what's next) mirrors the app's real category order.
const paths = {
    Beginner: {
        upNext: { title: tk('Find the Note'), category: tk('Relative Pitch'), done: 0, total: 28 },
        mistakesCount: 0,
        practiceAgain: [],
        comingUp: [tk('Relative Tone Pitch #1'), tk('Relative Tone Pitch #2'), tk('Harmonic 2nd'), tk('Harmonic 3rd')],
    },
    Intermediate: {
        upNext: { title: tk('Add 9 · Level 1'), category: tk('Add 9'), done: 0, total: 36 },
        mistakesCount: 0,
        practiceAgain: [],
        comingUp: [tk('Add 9 · Level 2'), tk('Add 9 · Level 3'), tk('Add 9 · Level 4'), tk('7th Degree Chords · Level 1')],
    },
    Advanced: {
        upNext: { title: tk('9th Degree Chords · Level 1'), category: tk('9th Degree Chords'), done: 0, total: 30 },
        mistakesCount: 0,
        practiceAgain: [],
        comingUp: [tk('9th Degree Chords · Level 2'), tk('9th Degree Chords · Level 3'), tk('9th Degree Chords · Level 4'), tk('Secondary 9th Chords · Level 1')],
    },
};

function PlayIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
            <path d="M8 5.5v13l11-6.5-11-6.5z" />
        </svg>
    );
}

function RefreshIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 12a8 8 0 0113.7-5.7L20 8M20 4v4h-4" />
            <path d="M20 12a8 8 0 01-13.7 5.7L4 16M4 20v-4h4" />
        </svg>
    );
}

function ChevronIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 5l7 7-7 7" />
        </svg>
    );
}

export default function ForYou() {
    const t = useTrans();
    const [level, setLevel] = useState('Beginner');
    const path = paths[level];

    return (
        <AppShell eyebrow={t('Member')} title={t('For You')} intro={t('Quizzes picked for your level and results.')}>
            <Head title={t('For You')} />

            <div className="inline-flex rounded-xl bg-neutral-100 p-1">
                {LEVELS.map((item) => (
                    <button
                        key={item}
                        type="button"
                        onClick={() => setLevel(item)}
                        className={`rounded-lg px-5 py-2 text-sm font-semibold transition ${
                            item === level ? 'bg-brand text-white shadow-sm' : 'text-neutral-500 hover:text-neutral-700'
                        }`}
                    >
                        {t(item)}
                    </button>
                ))}
            </div>

            <div
                title={t('Coming soon')}
                className="mt-6 rounded-2xl bg-neutral-900 p-5 text-white sm:p-6"
            >
                <div className="flex items-center justify-between">
                    <p className="text-xs font-semibold uppercase tracking-[0.14em] text-white/60">
                        {t(PATH_LABELS[level])}
                    </p>
                    <p className="text-xs text-white/60">
                        {path.upNext.done}/{path.upNext.total}
                    </p>
                </div>
                <h2 className="mt-2 text-xl font-bold tracking-tight sm:text-2xl">{t(path.upNext.title)}</h2>
                <p className="mt-0.5 text-sm text-white/70">{t(path.upNext.category)}</p>
                <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-white/15">
                    <div
                        className="h-full rounded-full bg-white"
                        style={{ width: `${Math.round((path.upNext.done / path.upNext.total) * 100)}%` }}
                    />
                </div>
                <span className="mt-5 inline-flex items-center gap-2 rounded-full bg-brand px-4 py-2.5 text-sm font-semibold">
                    <PlayIcon className="h-3.5 w-3.5" />
                    {t('Start quiz')}
                </span>
            </div>

            {(path.mistakesCount > 0 || path.practiceAgain.length > 0) && (
                <section className="mt-8">
                    <div className="flex items-baseline justify-between">
                        <h2 className="text-lg font-semibold">{t('Practise again')}</h2>
                        <span className="text-sm text-neutral-400">{t('From your results')}</span>
                    </div>

                    <div className="mt-3 overflow-hidden rounded-2xl border border-black/5 bg-white">
                        {path.mistakesCount > 0 && (
                            <div title={t('Coming soon')} className="flex items-center gap-3 border-b border-black/5 px-5 py-4">
                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                                    <RefreshIcon className="h-4 w-4" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-medium text-neutral-900">{t('Review your mistakes')}</p>
                                    <p className="mt-0.5 text-sm text-neutral-500">{t(':count questions to try again', { count: path.mistakesCount })}</p>
                                </div>
                                <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                            </div>
                        )}

                        {path.practiceAgain.map((quiz) => (
                            <div key={quiz.title} title={t('Coming soon')} className="flex items-center gap-3 px-5 py-4">
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-medium text-neutral-900">{t(quiz.title)}</p>
                                    <p className="mt-0.5 text-sm text-neutral-500">{t(quiz.category)}</p>
                                </div>
                                <span className="shrink-0 rounded-full bg-brand/10 px-2.5 py-1 text-xs font-semibold text-brand">
                                    {quiz.accuracy}%
                                </span>
                                <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                            </div>
                        ))}
                    </div>
                </section>
            )}

            <section className="mt-8">
                <div className="flex items-baseline justify-between">
                    <h2 className="text-lg font-semibold">{t(COMING_UP_HEADINGS[level])}</h2>
                    <span className="text-sm text-neutral-400">{t(':count shown', { count: path.comingUp.length })}</span>
                </div>

                <div className="mt-3 overflow-hidden rounded-2xl border border-black/5 bg-white">
                    {path.comingUp.map((title, index) => (
                        <div
                            key={title}
                            title={t('Coming soon')}
                            className={`flex items-center gap-3 px-5 py-4 ${index > 0 ? 'border-t border-black/5' : ''}`}
                        >
                            <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-xs font-semibold text-neutral-500">
                                {index + 2}
                            </span>
                            <p className="flex-1 text-sm font-medium text-neutral-900">{t(title)}</p>
                            <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                        </div>
                    ))}
                </div>
            </section>
        </AppShell>
    );
}
