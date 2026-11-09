import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';
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

function RefreshIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" {...props}>
            <path d="M20 12a8 8 0 11-5-7.4M15 2v4h4" />
        </svg>
    );
}

function PlayIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
            <path d="M8 5.5v13l11-6.5-11-6.5z" />
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

export default function ForYou({ paths }) {
    const t = useTrans();
    const [level, setLevel] = useState('Beginner');
    const path = paths[level];
    const practiceAgain = path.practiceAgain;

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

            <div className="mt-6 rounded-2xl bg-neutral-900 p-5 text-white sm:p-6">
                <div className="flex items-center justify-between">
                    <p className="text-xs font-semibold uppercase tracking-[0.14em] text-white/60">
                        {t(PATH_LABELS[level])}
                    </p>
                    <p className="text-xs text-white/60">
                        {path.done}/{path.total}
                    </p>
                </div>
                <h2 className="mt-2 text-xl font-bold tracking-tight sm:text-2xl">{path.upNext ? t(path.upNext.title) : path.total > 0 ? t('Path completed') : t('No quizzes available yet')}</h2>
                <p className="mt-0.5 text-sm text-white/70">{path.upNext ? t(path.upNext.category) : path.total > 0 ? t('You have completed every quiz in this path.') : t('Try another skill level.')}</p>
                <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-white/15">
                    <div
                        className="h-full rounded-full bg-white"
                        style={{ width: `${Math.round((path.done / Math.max(1, path.total)) * 100)}%` }}
                    />
                </div>
                <Link
                    href={path.upNext ? route('quiz.category', { category: path.upNext.categorySlug, quiz: path.upNext.slug }) : route('quiz.categories')}
                    className="mt-5 inline-flex items-center gap-2 rounded-full bg-brand px-4 py-2.5 text-sm font-semibold transition hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                >
                    <PlayIcon className="h-3.5 w-3.5" />
                    {path.upNext ? t('Start quiz') : t('Browse quizzes')}
                </Link>
            </div>

            {practiceAgain && (
                <section className="mt-8" aria-labelledby="practice-again-heading">
                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="practice-again-heading" className="text-lg font-semibold">{t('Practise again')}</h2>
                        <span className="text-sm text-neutral-500">{t('From your results')}</span>
                    </div>
                    <div className="mt-3 overflow-hidden rounded-2xl border border-black/5 bg-white">
                        <Link
                            href={practiceAgain.reviewUrl}
                            className="flex items-center gap-3 px-4 py-4 transition hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand sm:px-5"
                        >
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                                <RefreshIcon className="h-4 w-4" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-semibold text-neutral-900">{t('Review your mistakes')}</p>
                                <p className="mt-0.5 text-xs text-neutral-500 sm:text-sm">{t(':count questions to try again', { count: practiceAgain.mistakesCount })}</p>
                            </div>
                            <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-400" />
                        </Link>
                        <Link
                            href={route('quiz.category', { category: practiceAgain.categorySlug, quiz: practiceAgain.slug })}
                            className="flex items-center gap-3 border-t border-black/5 px-4 py-4 transition hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand sm:px-5"
                        >
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-semibold text-neutral-900">{t(practiceAgain.title)}</p>
                                <p className="mt-0.5 text-xs text-neutral-500 sm:text-sm">{t(practiceAgain.category)}</p>
                            </div>
                            <span className="shrink-0 rounded-full bg-brand/10 px-2.5 py-1 text-xs font-semibold tabular-nums text-brand">{practiceAgain.score}%</span>
                            <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-400" />
                        </Link>
                    </div>
                </section>
            )}

            {path.comingUp.length > 0 && (
                <section className="mt-8">
                    <div className="flex items-baseline justify-between">
                        <h2 className="text-lg font-semibold">{t(COMING_UP_HEADINGS[level])}</h2>
                        <span className="text-sm text-neutral-400">{t(':count shown', { count: path.comingUp.length })}</span>
                    </div>

                    <div className="mt-3 overflow-hidden rounded-2xl border border-black/5 bg-white">
                        {path.comingUp.map((quiz, index) => (
                            <Link
                                key={quiz.slug}
                                href={route('quiz.category', { category: quiz.categorySlug, quiz: quiz.slug })}
                                className={`flex items-center gap-3 px-5 py-4 transition hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand ${index > 0 ? 'border-t border-black/5' : ''}`}
                            >
                                <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-xs font-semibold text-neutral-500">
                                    {quiz.position}
                                </span>
                                <p className="flex-1 text-sm font-medium text-neutral-900">{t(quiz.title)}</p>
                                <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                            </Link>
                        ))}
                    </div>
                </section>
            )}
        </AppShell>
    );
}
