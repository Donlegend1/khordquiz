import AppShell from '@/Layouts/AppShell';
import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const iconProps = {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 1.8,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    'aria-hidden': true,
};

function FlameIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <path d="M12 2.5c1 3 .5 4.5-1 6-2 2-3 3.8-3 6a4 4 0 008 0c0-1.3-.5-2-1-2.8.9.4 2 1.6 2 3.8a5 5 0 01-10 0c0-4.2 3-6 4-8.2.6-1.3.7-2.8 1-4.8z" />
        </svg>
    );
}

function TargetIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <circle cx="12" cy="12" r="8.5" />
            <circle cx="12" cy="12" r="4.5" />
            <circle cx="12" cy="12" r="0.8" fill="currentColor" stroke="none" />
        </svg>
    );
}

function BookIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <path d="M4 5.5A2.5 2.5 0 016.5 3H12v18H6.5A2.5 2.5 0 014 18.5v-13z" />
            <path d="M20 5.5A2.5 2.5 0 0017.5 3H12v18h5.5a2.5 2.5 0 002.5-2.5v-13z" />
        </svg>
    );
}

function BellIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <path d="M6 9a6 6 0 1112 0c0 4.2 1.3 5.6 1.5 6H4.5c.2-.4 1.5-1.8 1.5-6z" />
            <path d="M10.3 19a1.8 1.8 0 003.4 0" />
        </svg>
    );
}

function PlayIcon(props) {
    return (
        <svg {...iconProps} fill="currentColor" stroke="none" {...props}>
            <path d="M8 5.5v13l11-6.5-11-6.5z" />
        </svg>
    );
}

function ChevronIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <path d="M9 5l7 7-7 7" />
        </svg>
    );
}

function LightbulbIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <path d="M9 18h6M10 21h4M8 11a4 4 0 118 0c0 1.8-1 2.6-2 3.5-.6.6-1 1.1-1 1.5h-2c0-.4-.4-.9-1-1.5-1-.9-2-1.7-2-3.5z" />
        </svg>
    );
}

function RefreshIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <path d="M4 12a8 8 0 0113.7-5.7L20 8M20 4v4h-4" />
            <path d="M20 12a8 8 0 01-13.7 5.7L4 16M4 20v-4h4" />
        </svg>
    );
}

function BookmarkIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <path d="M6 3.5h12v17l-6-4-6 4v-17z" />
        </svg>
    );
}

function BoltIcon(props) {
    return (
        <svg {...iconProps} fill="currentColor" stroke="none" {...props}>
            <path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" />
        </svg>
    );
}

function FootstepsIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <ellipse cx="8" cy="7" rx="2.3" ry="3.2" transform="rotate(-18 8 7)" />
            <path d="M6 12c-.5 2 .5 3.5 2 3.5s2-1 1.6-2.8" />
            <ellipse cx="16" cy="13" rx="2.3" ry="3.2" transform="rotate(18 16 13)" />
            <path d="M18 18c.5 2-.5 3.5-2 3.5s-2-1-1.6-2.8" />
        </svg>
    );
}

function EarIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <path d="M13 3a6 6 0 00-6 6c0 2 1 2.8 1 4.5A2.5 2.5 0 0010.5 16" />
            <path d="M13 3a6 6 0 016 6c0 4-3 4-3 8a3 3 0 01-6 0" />
            <path d="M10 9a2 2 0 012 2c0 1.2-1 1.3-1 2.5" />
        </svg>
    );
}

function MusicalNotesIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <path d="M9 17V6l10-2v9" />
            <circle cx="7" cy="17" r="2.5" />
            <circle cx="17" cy="13" r="2.5" />
        </svg>
    );
}

function CalendarIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <rect x="3.5" y="5" width="17" height="15.5" rx="2" />
            <path d="M3.5 9.5h17M8 3v4M16 3v4" />
        </svg>
    );
}

function TrophyIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <path d="M8 4h8v5a4 4 0 01-8 0V4z" />
            <path d="M8 5H5a3 3 0 003 3M16 5h3a3 3 0 01-3 3" />
            <path d="M12 13v3M9 20h6M10 20v-2.5h4V20" />
        </svg>
    );
}

function RibbonIcon(props) {
    return (
        <svg {...iconProps} fill="currentColor" stroke="none" {...props}>
            <path d="M12 2.5a5 5 0 100 10 5 5 0 000-10zm0 2a3 3 0 110 6 3 3 0 010-6z" />
            <path d="M9 11l-3 9 6-2.5L18 20l-3-9-1.2.8a5 5 0 01-3.6 0z" />
        </svg>
    );
}

function LockIcon(props) {
    return (
        <svg {...iconProps} {...props}>
            <rect x="5" y="10.5" width="14" height="9" rx="2" />
            <path d="M8 10.5V7a4 4 0 118 0v3.5" />
        </svg>
    );
}

function QuizNoteIcon(props) {
    return (
        <svg {...iconProps} {...props}>
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

const ACHIEVEMENT_ICONS = {
    footsteps: FootstepsIcon,
    ear: EarIcon,
    'musical-notes': MusicalNotesIcon,
    flame: FlameIcon,
    calendar: CalendarIcon,
    trophy: TrophyIcon,
};
const ACHIEVEMENTS_PREVIEW = 3;

function Achievements({ achievements }) {
    const [showAll, setShowAll] = useState(false);
    const unlocked = achievements.filter((a) => a.current >= a.target);
    // Closest to unlocking first, then the ones already earned — same order as the app.
    const ordered = [
        ...achievements.filter((a) => a.current < a.target).sort((a, b) => b.current / b.target - a.current / a.target),
        ...unlocked,
    ];
    const visible = showAll ? ordered : ordered.slice(0, ACHIEVEMENTS_PREVIEW);

    return (
        <section className="lg:col-span-2">
            <div className="flex items-baseline justify-between">
                <h2 className="text-lg font-semibold">
                    Achievements <span className="ml-1 text-sm font-normal text-neutral-400">{unlocked.length}/{achievements.length}</span>
                </h2>
                <button type="button" onClick={() => setShowAll((v) => !v)} className="text-sm font-semibold text-brand hover:text-brand-dark">
                    {showAll ? 'Show less' : 'See all'}
                </button>
            </div>

            <div className="mt-4 space-y-3">
                {visible.map((achievement) => {
                    const earned = achievement.current >= achievement.target;
                    const progress = Math.min(achievement.current, achievement.target);
                    const Icon = ACHIEVEMENT_ICONS[achievement.icon] ?? TrophyIcon;

                    return (
                        <article key={achievement.name} className="flex items-center gap-3 rounded-2xl border border-black/5 bg-white px-4 py-3.5">
                            <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${earned ? 'bg-amber-50 text-amber-600' : 'bg-neutral-100 text-neutral-400'}`}>
                                <Icon className="h-[18px] w-[18px]" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="font-bold text-neutral-800">{achievement.name}</p>
                                <p className="text-sm text-neutral-500">{achievement.detail}</p>
                                {!earned && achievement.target > 1 && (
                                    <div className="mt-1.5 flex items-center gap-2">
                                        <div className="h-1 flex-1 overflow-hidden rounded-full bg-neutral-100">
                                            <div className="h-full rounded-full bg-neutral-400" style={{ width: `${(progress / achievement.target) * 100}%` }} />
                                        </div>
                                        <span className="shrink-0 text-xs text-neutral-400">{progress}/{achievement.target}</span>
                                    </div>
                                )}
                            </div>
                            {earned ? (
                                <RibbonIcon className="h-[22px] w-[22px] shrink-0 text-amber-600" />
                            ) : (
                                <LockIcon className="h-[18px] w-[18px] shrink-0 text-neutral-300" />
                            )}
                        </article>
                    );
                })}
            </div>
        </section>
    );
}

// Badge counts match the exact row totals on each page: 5 confused pairs on
// Mistakes, 4 challenges (1 your turn + 1 pending + 2 completed) on Challenges.
const quickAccess = [
    { label: 'For You', icon: LightbulbIcon, route: 'for-you' },
    { label: 'Mistakes', icon: RefreshIcon, route: 'mistakes', count: 5 },
    { label: 'Saved', icon: BookmarkIcon, route: 'saved' },
    { label: 'Challenges', icon: BoltIcon, route: 'challenges', count: 4 },
];

// Sample content only — there's no notifications feature built yet, so nothing here
// is tied to real activity.
const demoNotifications = [
    {
        id: 1,
        icon: FlameIcon,
        tone: 'bg-orange-50 text-orange-600',
        title: '7-day streak!',
        body: "You've practiced seven days in a row. Keep it going.",
        time: '2 hours ago',
    },
    {
        id: 2,
        icon: BoltIcon,
        tone: 'bg-brand/10 text-brand',
        title: 'Jordan challenged you',
        body: 'Diatonic Intervals · 25 questions',
        time: '5 hours ago',
    },
    {
        id: 3,
        icon: BookIcon,
        tone: 'bg-sky-50 text-sky-600',
        title: 'New category added',
        body: 'Secondary 9th Chords is now live.',
        time: 'Yesterday',
    },
    {
        id: 4,
        icon: PlayIcon,
        tone: 'bg-neutral-100 text-neutral-600',
        title: 'Pick up where you left off',
        body: 'Diatonic Intervals is 3/4 of the way done.',
        time: '2 days ago',
    },
    {
        id: 5,
        icon: TargetIcon,
        tone: 'bg-amber-50 text-amber-700',
        title: 'Accuracy is up',
        body: 'Your weekly accuracy climbed to 75%.',
        time: '3 days ago',
    },
];

function NotificationsBell() {
    const [open, setOpen] = useState(false);
    const containerRef = useRef(null);

    useEffect(() => {
        if (!open) return;
        const onPointerDown = (event) => {
            if (containerRef.current && !containerRef.current.contains(event.target)) setOpen(false);
        };
        const onKeyDown = (event) => event.key === 'Escape' && setOpen(false);
        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);
        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    return (
        <div className="relative" ref={containerRef}>
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                aria-expanded={open}
                aria-haspopup="true"
                className="relative flex h-11 w-11 items-center justify-center rounded-full border border-black/5 bg-white text-neutral-500 transition hover:bg-neutral-50"
            >
                <BellIcon className="h-5 w-5" />
                {demoNotifications.length > 0 && (
                    <span className="absolute -right-1 -top-1 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-brand px-1 text-[11px] font-semibold text-white ring-2 ring-white">
                        {demoNotifications.length > 9 ? '9+' : demoNotifications.length}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute right-0 top-full z-30 mt-2 w-80 overflow-hidden rounded-2xl border border-black/5 bg-white shadow-xl">
                    <div className="flex items-center justify-between border-b border-black/5 px-4 py-3">
                        <p className="font-semibold text-neutral-900">Notifications</p>
                    </div>

                    <ul className="max-h-80 divide-y divide-black/5 overflow-y-auto">
                        {demoNotifications.map((item) => {
                            const Icon = item.icon;
                            return (
                                <li key={item.id} className="flex gap-3 px-4 py-3">
                                    <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${item.tone}`}>
                                        <Icon className="h-4 w-4" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-medium text-neutral-900">{item.title}</p>
                                        <p className="mt-0.5 text-sm text-neutral-500">{item.body}</p>
                                        <p className="mt-1 text-xs text-neutral-400">{item.time}</p>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>

                </div>
            )}
        </div>
    );
}

export default function Dashboard({ streak, stats, resume, recentQuizzes, achievements }) {
    const { auth } = usePage().props;
    const firstName = auth.user.name.split(' ')[0];

    return (
        <AppShell
            eyebrow="Member"
            title="Dashboard"
            intro={`Welcome back, ${firstName}.`}
            action={<NotificationsBell />}
        >
            <Head title="Your training" />

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="rounded-2xl bg-neutral-100 px-5 py-4">
                    <FlameIcon className="h-6 w-6 text-brand" />
                    <p className="mt-3 text-3xl font-bold tracking-tight text-neutral-900">{streak.current}</p>
                    <p className="mt-1 text-sm text-neutral-500">
                        Day streak <span className="text-neutral-400">· best {streak.longest}</span>
                    </p>
                </div>
                <div className="rounded-2xl bg-neutral-100 px-5 py-4">
                    <TargetIcon className="h-6 w-6 text-brand" />
                    <p className="mt-3 text-3xl font-bold tracking-tight text-neutral-900">
                        {stats.accuracy === null ? '—' : `${stats.accuracy}%`}
                    </p>
                    <p className="mt-1 text-sm text-neutral-500">Accuracy</p>
                </div>
                <div className="rounded-2xl bg-neutral-100 px-5 py-4">
                    <BookIcon className="h-6 w-6 text-brand" />
                    <p className="mt-3 text-3xl font-bold tracking-tight text-neutral-900">{stats.completed}</p>
                    <p className="mt-1 text-sm text-neutral-500">
                        Quizzes done
                        {stats.inProgress > 0 && (
                            <span className="text-neutral-400"> · {stats.inProgress} in progress</span>
                        )}
                    </p>
                </div>
            </div>

            <section className="mt-8">
                {resume ? (
                    <div
                        title="Playing a quiz from the dashboard is coming soon"
                        className="mt-4 rounded-2xl bg-brand p-5 text-white sm:p-6"
                    >
                        <div className="flex items-start gap-4">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/15">
                                <PlayIcon className="h-5 w-5 translate-x-0.5" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="text-xs font-semibold uppercase tracking-[0.14em] text-white/70">
                                    Continue · {resume.quiz.category}
                                </p>
                                <h3 className="mt-1 truncate text-xl font-bold tracking-tight sm:text-2xl">
                                    {resume.quiz.title}
                                </h3>
                                <div className="mt-4 flex items-center gap-3">
                                    <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-white/25">
                                        <div className="h-full rounded-full bg-white" style={{ width: `${resume.progress}%` }} />
                                    </div>
                                    <span className="shrink-0 text-sm text-white/80">
                                        {resume.stopped_at_question}/{resume.quiz.question_count}
                                    </span>
                                </div>
                            </div>
                            <ChevronIcon className="h-5 w-5 shrink-0 text-white/60" />
                        </div>
                    </div>
                ) : (
                    <div className="mt-4 rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-8 text-sm text-neutral-600">
                        You don&apos;t have a quiz in progress.
                        {recentQuizzes[0] && <span> Last one you touched was {recentQuizzes[0].quiz.title}.</span>}
                    </div>
                )}
            </section>

            <section className="mt-8">
                <h2 className="text-lg font-semibold">Quick Access</h2>

                <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {quickAccess.map((item) => (
                        <Link
                            key={item.label}
                            href={route(item.route)}
                            className="relative flex flex-col items-center gap-2 rounded-2xl border border-black/5 bg-white px-3 py-5 text-center transition hover:border-brand/15 hover:bg-brand/[0.04]"
                        >
                            <ChevronIcon className="absolute right-3 top-3 h-3.5 w-3.5 text-neutral-300" />
                            <span className="relative">
                                <item.icon className="h-6 w-6 text-neutral-500" />
                                {!!item.count && (
                                    <span className="absolute -right-2.5 -top-2 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-white ring-2 ring-white">
                                        {item.count > 99 ? '99+' : item.count}
                                    </span>
                                )}
                            </span>
                            <span className="text-sm font-medium text-neutral-700">{item.label}</span>
                        </Link>
                    ))}
                </div>
            </section>

            <div className="mt-8 grid gap-8 lg:grid-cols-5">
                <section className="lg:col-span-3">
                    <h2 className="text-lg font-semibold">Recent quizzes</h2>

                    <div className="mt-4 overflow-hidden rounded-2xl border border-black/5 bg-white">
                        {recentQuizzes.length === 0 ? (
                            <p className="px-5 py-8 text-sm text-neutral-500">No completed quizzes yet. Finish one and it&apos;ll show up here.</p>
                        ) : (
                            <ul className="divide-y divide-black/5">
                                {recentQuizzes.map((attempt) => (
                                    <li key={attempt.id} className="flex items-center gap-4 px-5 py-4">
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
                    </div>
                </section>

                <Achievements achievements={achievements} />
            </div>
        </AppShell>
    );
}
