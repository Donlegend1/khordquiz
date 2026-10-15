import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

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

function scoreTone(score) {
    if (score < 40) return 'bg-rose-50 text-brand';
    if (score < 65) return 'bg-amber-50 text-amber-700';
    return 'bg-emerald-50 text-emerald-700';
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
    const { data, setData, patch, processing } = useForm({ daily_goal: current });

    useEffect(() => {
        if (!open) return;
        const onPointerDown = (event) => {
            if (ref.current && !ref.current.contains(event.target)) setOpen(false);
        };
        document.addEventListener('mousedown', onPointerDown);
        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [open]);

    const choose = (value) => {
        setData('daily_goal', value);
        patch(route('progress.goal'), { preserveScroll: true, onSuccess: () => setOpen(false) });
    };

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="inline-flex items-center gap-1.5 rounded-full bg-neutral-100 px-3.5 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-200"
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
                                value === data.daily_goal ? 'font-semibold text-brand' : 'text-neutral-700'
                            }`}
                        >
                            {value} questions
                            {value === data.daily_goal && <span>✓</span>}
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

            <section className="mt-6 rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-semibold">Today&apos;s goal</h2>
                    <GoalPicker current={goal.target} options={goalOptions} />
                </div>
                <p className="mt-1 text-sm text-neutral-500">
                    {goalRemaining === 0 ? "You're there — nice work." : `${goalRemaining} questions to go`}
                </p>
                <div className="mt-4 flex items-center gap-3">
                    <div className="h-2 flex-1 overflow-hidden rounded-full bg-neutral-100">
                        <div className="h-full rounded-full bg-amber-500" style={{ width: `${goalPercent}%` }} />
                    </div>
                    <span className="shrink-0 text-sm font-semibold text-neutral-700">
                        {goal.done}/{goal.target}
                    </span>
                </div>
            </section>

            {resume && (
                <div title="Playing a quiz from the dashboard is coming soon" className="mt-6 rounded-2xl bg-brand p-5 text-white">
                    <div className="flex items-center gap-4">
                        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/15">
                            <PlayIcon className="h-4 w-4 translate-x-0.5" />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-semibold uppercase tracking-[0.1em] text-white/70">
                                Continue where you left off
                            </p>
                            <p className="truncate text-lg font-bold">{resume.quiz.title}</p>
                            <p className="text-sm text-white/70">{resume.quiz.category}</p>
                        </div>
                        <ChevronIcon className="h-5 w-5 shrink-0 text-white/60" />
                    </div>
                </div>
            )}

            <section className="mt-6 rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <div className="flex items-baseline justify-between">
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

            <section className="mt-6 rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <div className="flex items-baseline justify-between">
                    <h2 className="flex items-center gap-2 text-lg font-semibold">
                        <StarIcon className="h-4 w-4 text-amber-500" />
                        Strongest
                    </h2>
                    <span className="text-sm text-neutral-400">Above 65% accuracy</span>
                </div>
                {strongest.length > 0 ? (
                    <RankedQuizList items={strongest} tone={() => 'bg-emerald-50 text-emerald-700'} />
                ) : (
                    <p className="mt-3 text-sm text-neutral-500">Your best quizzes will show here once you&apos;ve practised a few.</p>
                )}
            </section>

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
            </section>
        </AppShell>
    );
}
