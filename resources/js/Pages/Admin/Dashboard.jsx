import QuizTable from '@/Components/Admin/QuizTable';
import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';

function PeopleIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="9" cy="8" r="3" />
            <path d="M3 20c.9-3 3-4.8 6-4.8s5.1 1.8 6 4.8" />
            <path d="M15.5 8.5a2.5 2.5 0 110-5" />
            <path d="M17 15.3c2 .5 3.3 1.9 4 4.7" />
        </svg>
    );
}

function NoteIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 18V9l9-2v9M9 18a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 019 18zM18 16a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 0118 16z" />
        </svg>
    );
}

function PlayIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="9" />
            <path d="M10 9l5 3-5 3z" fill="currentColor" stroke="none" />
        </svg>
    );
}

function FlameIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M12 2.5c1 3 .5 4.5-.8 6.2C9.8 10.6 8 12 8 15a4 4 0 008 0c0-1.3-.4-2-1-2.8.6 2-.2 3.3-1.5 3.3-1.6 0-2-1.3-1.5-2.7.8-2.3-.5-3.8-1-4.8-.4 1.4-1.3 2-2 3.2-.5.9-.8 1.8-.8 2.8" />
        </svg>
    );
}

function GlobeIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18M12 3c2.5 2.5 3.8 5.7 3.8 9s-1.3 6.5-3.8 9c-2.5-2.5-3.8-5.7-3.8-9S9.5 5.5 12 3z" />
        </svg>
    );
}

const STAT_TINTS = {
    blue: { bg: 'bg-blue-50', icon: 'bg-white text-blue-600' },
    brand: { bg: 'bg-rose-50', icon: 'bg-white text-brand' },
    green: { bg: 'bg-emerald-50', icon: 'bg-white text-emerald-600' },
    amber: { bg: 'bg-amber-50', icon: 'bg-white text-amber-600' },
};

function Stat({ label, value, detail, icon: Icon, tint }) {
    const colors = STAT_TINTS[tint];
    return (
        <div className={`rounded-2xl ${colors.bg} px-5 py-4`}>
            <div className="flex items-center justify-between">
                <p className="text-sm font-medium text-neutral-600">{label}</p>
                <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full shadow-sm ${colors.icon}`}>
                    <Icon className="h-4.5 w-4.5" />
                </span>
            </div>
            <p className="mt-3 text-3xl font-bold tracking-tight text-neutral-900">{value}</p>
            {detail && <p className="mt-1 text-sm text-neutral-500">{detail}</p>}
        </div>
    );
}

// Horizontal bars ranked by member count, highest first — real country data, no map library.
function TopCountries({ countries }) {
    if (countries.length === 0) {
        return <p className="text-sm text-neutral-500">No members have set a country yet.</p>;
    }

    const max = Math.max(...countries.map((row) => row.count));

    return (
        <div className="space-y-3">
            {countries.map((row) => (
                <div key={row.country} className="flex items-center gap-3">
                    <span className="w-28 shrink-0 truncate text-sm font-medium text-neutral-700">{row.country}</span>
                    <div className="h-2 flex-1 overflow-hidden rounded-full bg-neutral-100">
                        <div className="h-full rounded-full bg-brand" style={{ width: `${(row.count / max) * 100}%` }} />
                    </div>
                    <span className="w-6 shrink-0 text-right text-sm font-semibold tabular-nums text-neutral-900">{row.count}</span>
                </div>
            ))}
        </div>
    );
}

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

export default function Dashboard({ stats, recentStreaks, recentSignups, quizzes, topCountries }) {
    return (
        <AppShell
            eyebrow="Admin"
            title="Overview"
            intro="Member totals, quiz catalog, and who is on a streak."
            action={
                <div className="flex items-center gap-2.5">
                    <Link
                        href={route('admin.users.index')}
                        className="inline-flex items-center rounded-md border border-black/10 bg-white px-4 py-2.5 text-sm font-medium text-neutral-900 hover:bg-neutral-50"
                    >
                        Manage users
                    </Link>
                    <Link
                        href={route('admin.quizzes.create')}
                        className="inline-flex items-center rounded-md bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-dark"
                    >
                        Add quiz
                    </Link>
                </div>
            }
        >
            <Head title="Overview" />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Stat label="Members" value={stats.members} detail={`+${stats.newSignups} this week`} icon={PeopleIcon} tint="blue" />
                <Stat label="Quizzes" value={stats.quizzes} detail={`${stats.publishedQuizzes} published`} icon={NoteIcon} tint="brand" />
                <Stat label="Attempts" value={stats.attempts} detail={`${stats.completedAttempts} completed`} icon={PlayIcon} tint="green" />
                <Stat label="Active streaks" value={stats.activeStreaks} detail="Members practicing" icon={FlameIcon} tint="amber" />
            </div>

            <section className="mt-8 rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <div className="flex items-center gap-2 text-neutral-500">
                    <GlobeIcon className="h-4 w-4" />
                    <h2 className="text-sm font-semibold uppercase tracking-wide">Top countries</h2>
                </div>
                <div className="mt-4">
                    <TopCountries countries={topCountries} />
                </div>
            </section>

            <div className="mt-8 grid gap-6 lg:grid-cols-2">
                <section>
                    <h2 className="text-lg font-semibold">Recent streaks</h2>
                    <p className="mt-1 text-sm text-neutral-500">Members ordered by their latest practice day.</p>

                    <div className="mt-4 overflow-hidden rounded-2xl border border-black/5 bg-white">
                        {recentStreaks.length === 0 ? (
                            <p className="px-5 py-8 text-sm text-neutral-500">No members yet.</p>
                        ) : (
                            <ul className="divide-y divide-black/5">
                                {recentStreaks.map((member) => (
                                    <li key={member.id} className="flex items-center justify-between gap-4 px-5 py-4">
                                        <div className="flex min-w-0 items-center gap-3">
                                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand/10 text-xs font-semibold text-brand">
                                                {initials(member.name)}
                                            </span>
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">{member.name}</p>
                                                <p className="text-sm text-neutral-500">Last practice {member.last_practiced_on}</p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-lg font-semibold text-brand">
                                                {member.current_streak}
                                                <span className="ms-1 text-sm font-medium text-neutral-500">
                                                    {member.current_streak === 1 ? 'day' : 'days'}
                                                </span>
                                            </p>
                                            <p className="text-xs text-neutral-500">Best {member.longest_streak}</p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>

                <section>
                    <div className="mb-1 flex items-end justify-between gap-3">
                        <h2 className="text-lg font-semibold">New signups</h2>
                        <Link href={route('admin.users.index')} className="text-sm font-medium text-brand hover:underline">
                            View all users
                        </Link>
                    </div>
                    <p className="text-sm text-neutral-500">The most recently joined members.</p>

                    <div className="mt-4 overflow-hidden rounded-2xl border border-black/5 bg-white">
                        {recentSignups.length === 0 ? (
                            <p className="px-5 py-8 text-sm text-neutral-500">No members yet.</p>
                        ) : (
                            <ul className="divide-y divide-black/5">
                                {recentSignups.map((member) => (
                                    <li key={member.id}>
                                        <Link href={route('admin.users.show', member.id)} className="flex items-center justify-between gap-4 px-5 py-4 hover:bg-neutral-50">
                                            <div className="flex min-w-0 items-center gap-3">
                                                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand/10 text-xs font-semibold text-brand">
                                                    {initials(member.name)}
                                                </span>
                                                <div className="min-w-0">
                                                    <p className="truncate font-medium">{member.name}</p>
                                                    <p className="truncate text-sm text-neutral-500">{member.email}</p>
                                                </div>
                                            </div>
                                            <p className="shrink-0 text-sm text-neutral-500">{member.joined_ago}</p>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>
            </div>

            <section className="mt-8">
                <div className="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <h2 className="text-lg font-semibold">Quiz list</h2>
                        <p className="mt-1 text-sm text-neutral-500">Everything in the catalog.</p>
                    </div>
                    <Link href={route('admin.quizzes.index')} className="text-sm font-medium text-brand hover:underline">
                        Manage quizzes
                    </Link>
                </div>
                <QuizTable quizzes={quizzes} />
            </section>
        </AppShell>
    );
}
