import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { scoreTone } from '@/lib/scoreColor';

function FlameIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M12 2.5c1 3 .5 4.5-1 6-2 2-3 3.8-3 6a4 4 0 008 0c0-1.3-.5-2-1-2.8.9.4 2 1.6 2 3.8a5 5 0 01-10 0c0-4.2 3-6 4-8.2.6-1.3.7-2.8 1-4.8z" />
        </svg>
    );
}

function NoteIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
            <path d="M9 17V4.5l10-2v12.5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
            <circle cx="6.5" cy="17" r="2.5" />
            <circle cx="16.5" cy="14.5" r="2.5" />
        </svg>
    );
}

function TrophyIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M8 4h8v5a4 4 0 01-8 0V4z" />
            <path d="M8 5H5a3 3 0 003 3M16 5h3a3 3 0 01-3 3" />
            <path d="M12 13v3M9 20h6M10 20v-2.5h4V20" />
        </svg>
    );
}

function TuneIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 6h10M17 6h3M4 12h3M9 12h11M4 18h7M14 18h6" />
            <circle cx="14" cy="6" r="2" fill="white" />
            <circle cx="6" cy="12" r="2" fill="white" />
            <circle cx="11" cy="18" r="2" fill="white" />
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

function QuizNoteIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="8.5" />
            <path d="M10 9.3l5.5 2.7-5.5 2.7z" fill="currentColor" stroke="none" />
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

// Sample pairs, same as the dedicated Mistakes page — there's no per-question
// mistake tracking on the web yet (only each attempt's final correct/total
// tally), so this can't be computed from real data. Drawn from actual answer
// choices in the seeded quizzes so it at least reads true to the app.
const confusions = [
    { picked: 'Eb', answer: 'E', count: 4 },
    { picked: 'F', answer: 'F#', count: 3 },
    { picked: 'A', answer: 'G#', count: 3 },
];

function StarIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6-4.5-4.2 6.1-.7z" />
        </svg>
    );
}

function StatTile({ icon: Icon, value, label }) {
    return (
        <div className="rounded-2xl bg-neutral-100 px-4 py-4 text-center">
            <Icon className="mx-auto h-5 w-5 text-neutral-500" />
            <p className="mt-2 text-2xl font-bold tracking-tight text-neutral-900">{value}</p>
            <p className="mt-0.5 text-xs text-neutral-500">{label}</p>
        </div>
    );
}

function GoalPicker({ current, options }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    const { transform, patch, processing } = useForm({ daily_goal: current });

    useEffect(() => {
        if (!open) return;
        const onPointerDown = (event) => {
            if (ref.current && !ref.current.contains(event.target)) setOpen(false);
        };
        document.addEventListener('mousedown', onPointerDown);
        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [open]);

    const choose = (value) => {
        transform(() => ({ daily_goal: value }));
        patch(route('progress.goal'), { preserveScroll: true, onSuccess: () => setOpen(false) });
    };

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                aria-label="Change daily goal"
                aria-expanded={open}
                onClick={() => setOpen((v) => !v)}
                className="inline-flex min-h-11 items-center gap-1.5 rounded-full bg-neutral-100 px-3.5 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-200"
            >
                <TuneIcon className="h-4 w-4" />
                Change
            </button>

            {open && (
                <div className="absolute right-0 top-full z-20 mt-2 w-40 overflow-hidden rounded-xl border border-black/5 bg-white shadow-lg">
                    {options.map((value) => (
                        <button
                            key={value}
                            type="button"
                            disabled={processing}
                            onClick={() => choose(value)}
                            className={`flex w-full items-center justify-between px-4 py-2.5 text-sm transition hover:bg-neutral-50 ${
                                value === current ? 'font-semibold text-brand' : 'text-neutral-700'
                            }`}
                        >
                            {value} questions
                            {value === current && <span>✓</span>}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

function RankedQuizList({ items, tone }) {
    return (
        <ul className="mt-3 divide-y divide-black/5">
            {items.map((item) => (
                <li key={item.quiz.title} title="Coming soon" className="flex items-center gap-3 py-3.5 first:pt-0 last:pb-0">
                    <div className="min-w-0 flex-1">
                        <p className="font-medium text-neutral-900">{item.quiz.title}</p>
                        <p className="mt-0.5 text-sm text-neutral-500">{item.quiz.category}</p>
                    </div>
                    <span className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ${tone(item.accuracy)}`}>
                        {item.accuracy}%
                    </span>
                    <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                </li>
            ))}
        </ul>
    );
}

export default function Progress({
    overall,
    quizzesCompleted,
    streak,
    goal,
    goalOptions,
    resume,
    needsWork,
    strongest,
    week,
    recentQuizzes,
}) {
    const goalRemaining = Math.max(0, goal.target - goal.done);
    const goalPercent = goal.target > 0 ? Math.min(100, Math.round((goal.done / goal.target) * 100)) : 0;
    const goalTone = goalPercent <= 30 ? 'bg-brand' : goalPercent <= 65 ? 'bg-amber-500' : 'bg-emerald-600';
    const weekTotal = week.reduce((sum, day) => sum + day.count, 0);
    const weekMax = Math.max(1, ...week.map((day) => day.count));

    return (
        <AppShell eyebrow="Member" title="Your progress" intro="How your practice is adding up.">
            <Head title="Your progress" />

            <section className="rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <p className="text-sm text-neutral-500">Overall accuracy</p>
                <div className="mt-1 flex flex-wrap items-baseline justify-between gap-2">
                    <p className="text-5xl font-bold tracking-tight text-emerald-600">
                        {overall.accuracy === null ? '—' : `${overall.accuracy}%`}
                    </p>
                    <p className="text-sm text-neutral-500">
                        {overall.correct} of {overall.answered} correct
                    </p>
                </div>

                <div className="mt-5 grid grid-cols-3 gap-3">
                    <StatTile icon={FlameIcon} value={streak} label="Days streak" />
                    <StatTile icon={NoteIcon} value={overall.answered} label="Questions answered" />
                    <StatTile icon={TrophyIcon} value={quizzesCompleted.done} label="Quizzes done" />
                </div>
            </section>

            <div className={`mt-6 grid gap-4 ${resume ? 'md:grid-cols-2' : ''}`}>
                <section className="flex min-w-0 flex-col justify-center rounded-2xl border border-black/5 bg-white p-4 xl:p-6">
                    <div className="flex items-center justify-between gap-3">
                        <div className="min-w-0">
                            <h2 id="progress-goal-heading" className="text-lg font-semibold">Today&apos;s goal</h2>
                            <p className={`mt-0.5 text-sm ${goalRemaining === 0 ? 'font-semibold text-emerald-700' : 'text-neutral-500'}`}>
                                {goalRemaining === 0 ? 'Goal reached — nice work!' : `${goalRemaining} questions to go`}
                            </p>
                        </div>
                        <GoalPicker current={goal.target} options={goalOptions} />
                    </div>
                    <div className="mt-4 flex items-center gap-3">
                        <div role="progressbar" aria-labelledby="progress-goal-heading" aria-valuemin={0} aria-valuemax={goal.target} aria-valuenow={Math.min(goal.done, goal.target)} className="h-1.5 flex-1 overflow-hidden rounded-full bg-neutral-200">
                            <div className={`h-full rounded-full ${goalTone}`} style={{ width: `${goalPercent}%` }} />
                        </div>
                        <span className="shrink-0 text-sm font-bold tabular-nums text-neutral-900">
                            {Math.min(goal.done, goal.target)}/{goal.target}
                        </span>
                    </div>
                </section>
                {resume && (
                    <Link
                        href={route('quiz.category', { category: resume.quiz.categorySlug, quiz: resume.quiz.slug })}
                        className="flex min-w-0 items-center gap-3 rounded-2xl bg-brand p-3.5 text-white sm:items-start sm:p-4 xl:gap-4 xl:p-6"
                    >
                        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/15 sm:h-9 sm:w-9 xl:h-12 xl:w-12">
                            <PlayIcon className="h-4 w-4 translate-x-0.5 sm:h-5 sm:w-5" />
                        </span>
                        <div className="min-w-0 flex-1 sm:hidden">
                            <p className="truncate text-xs text-white/80">Continue where you left off</p>
                            <h3 className="truncate text-base font-bold leading-tight">{resume.quiz.title}</h3>
                            <p className="truncate text-xs text-white/70">{resume.quiz.category}</p>
                        </div>
                        <ChevronIcon className="h-4 w-4 shrink-0 text-white/60 sm:hidden" />

                        <div className="hidden min-w-0 flex-1 sm:block">
                            <p className="text-xs font-semibold uppercase tracking-[0.14em] text-white/70">
                                Continue · {resume.quiz.category}
                            </p>
                            <h3 className="mt-1 break-words text-xl font-bold leading-snug tracking-tight xl:text-2xl">{resume.quiz.title}</h3>
                            <div className="mt-4 flex items-center gap-3">
                                <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-white/25">
                                    <div className="h-full rounded-full bg-white" style={{ width: `${resume.progress}%` }} />
                                </div>
                                <span className="shrink-0 text-sm text-white/80">
                                    {resume.stopped_at_question}/{resume.quiz.question_count}
                                </span>
                            </div>
                        </div>
                        <ChevronIcon className="hidden h-4 w-4 shrink-0 text-white/60 sm:block xl:h-5 xl:w-5" />
                    </Link>
                )}

            </div>

            <div className="mt-6 grid gap-4 md:grid-cols-2">
                <section className="min-w-0 rounded-2xl border border-black/5 bg-white p-4 xl:p-6">
                    <div className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                        <h2 className="text-lg font-semibold">Needs work</h2>
                        <span className="text-sm text-neutral-400">Below 65% accuracy</span>
                    </div>
                    {needsWork.length > 0 ? (
                        <RankedQuizList
                            items={needsWork}
                            tone={(accuracy) => (accuracy < 40 ? 'bg-red-50 text-red-700' : 'bg-orange-50 text-orange-700')}
                        />
                    ) : (
                        <p className="mt-3 text-sm text-neutral-500">Your weakest quizzes will show here once you&apos;ve practised a few.</p>
                    )}
                </section>

                <section className="min-w-0 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 text-neutral-900 xl:p-6">
                    <div className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <StarIcon className="h-4 w-4 text-amber-500" />
                            Strongest
                        </h2>
                        <span className="text-sm text-neutral-600">Above 65% accuracy</span>
                    </div>
                    {strongest.length > 0 ? (
                        <RankedQuizList items={strongest} tone={() => 'bg-emerald-50 text-emerald-700'} />
                    ) : (
                        <p className="mt-3 text-sm text-neutral-500">Your best quizzes will show here once you&apos;ve practised a few.</p>
                    )}
                </section>
            </div>

            <section className="mt-6 rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <div className="flex items-baseline justify-between">
                    <h2 className="text-lg font-semibold">Most confused</h2>
                    <Link href={route('mistakes')} className="text-sm font-semibold text-brand hover:text-brand-dark">
                        See all
                    </Link>
                </div>

                <ul className="mt-3 space-y-2.5">
                    {confusions.map((item) => (
                        <li key={`${item.picked}-${item.answer}`} className="rounded-2xl bg-[#FBEDEC] px-4 py-3 text-sm text-neutral-800">
                            You picked <span className="font-semibold text-brand">{item.picked}</span> when it was{' '}
                            <span className="font-semibold text-emerald-700">{item.answer}</span>
                            <span className="mt-0.5 block text-neutral-500">
                                {item.count} {item.count === 1 ? 'time' : 'times'}
                            </span>
                        </li>
                    ))}
                </ul>
            </section>

            <section className="mt-6 rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <div className="flex items-baseline justify-between">
                    <h2 className="text-lg font-semibold">This week</h2>
                    <span className="text-sm text-neutral-400">{weekTotal} this week</span>
                </div>

                <div className="mt-6 flex items-end justify-between gap-2" style={{ height: '120px' }}>
                    {week.map((day) => (
                        <div key={day.label} className="flex flex-1 flex-col items-center gap-2">
                            {day.count > 0 && <span className="text-xs font-medium text-neutral-500">{day.count}</span>}
                            <div
                                className={`w-full rounded-md ${day.count > 0 ? 'bg-brand' : 'bg-neutral-100'}`}
                                style={{ height: day.count > 0 ? `${Math.max(10, (day.count / weekMax) * 90)}px` : '6px' }}
                            />
                            <span className="text-xs text-neutral-400">{day.label}</span>
                        </div>
                    ))}
                </div>
            </section>

            <section className="mt-6 rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <h2 className="text-lg font-semibold">Completed quizzes</h2>
                <p className="mt-1 text-sm text-neutral-500">Your five most recent completed attempts.</p>

                {recentQuizzes.length === 0 ? (
                    <p className="mt-3 text-sm text-neutral-500">No completed quizzes yet. Finish one and it&apos;ll show up here.</p>
                ) : (
                    <ul className="mt-3 divide-y divide-black/5">
                        {recentQuizzes.map((attempt) => (
                            <li key={`${attempt.quiz.title}-${attempt.last_played_at}`} className="flex items-center gap-4 py-3.5 first:pt-0 last:pb-0">
                                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-500">
                                    <QuizNoteIcon className="h-5 w-5" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-semibold text-neutral-900">{attempt.quiz.title}</p>
                                    <p className="mt-0.5 truncate text-sm text-neutral-500">
                                        {attempt.quiz.category} · {attempt.last_played_at}
                                    </p>
                                </div>
                                <span className={`shrink-0 rounded-full px-3 py-1 text-sm font-bold ${scoreTone(attempt.score)}`}>
                                    {attempt.score}%
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
                {recentQuizzes.length > 0 && (
                    <div className="mt-5 border-t border-neutral-100 pt-4">
                        <Link href={route('quiz.history')} className="flex min-h-11 w-full items-center justify-center rounded-xl border border-neutral-300 bg-white px-4 py-3 text-center text-sm font-semibold text-neutral-700 transition-colors hover:border-neutral-400 hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
                            View attempt history
                        </Link>
                    </div>
                )}
            </section>
        </AppShell>
    );
}
