import QuizTable from '@/Components/Admin/QuizTable';
import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';

function Stat({ label, value, detail }) {
    return (
        <div className="rounded-2xl border border-black/5 bg-white px-5 py-4">
            <p className="text-sm text-neutral-500">{label}</p>
            <p className="mt-2 text-3xl font-bold tracking-tight">{value}</p>
            {detail && <p className="mt-1 text-sm text-neutral-500">{detail}</p>}
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

export default function Dashboard({ stats, recentStreaks, recentSignups, quizzes }) {
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
                <Stat label="Members" value={stats.members} detail={`+${stats.newSignups} this week`} />
                <Stat label="Quizzes" value={stats.quizzes} detail={`${stats.publishedQuizzes} published`} />
                <Stat label="Attempts" value={stats.attempts} detail={`${stats.completedAttempts} completed`} />
                <Stat label="Active streaks" value={stats.activeStreaks} detail="Members practicing" />
            </div>

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
