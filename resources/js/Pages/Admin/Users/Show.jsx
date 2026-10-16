import AppShell from '@/Layouts/AppShell';
import { Head, Link, router, usePage } from '@inertiajs/react';

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function InfoRow({ label, value }) {
    return (
        <div className="flex items-center justify-between gap-4 px-5 py-3.5">
            <p className="text-sm text-neutral-500">{label}</p>
            <p className="text-sm font-medium text-neutral-900">{value ?? '—'}</p>
        </div>
    );
}

export default function Show({ member, recentAttempts, awards }) {
    const { auth } = usePage().props;
    const isSelf = auth.user.id === member.id;

    const toggleSuspend = () => {
        if (member.suspended) {
            router.post(route('admin.users.unsuspend', member.id));
            return;
        }
        if (confirm(`Suspend ${member.name}? They'll be signed out and can't log back in until you unsuspend them.`)) {
            router.post(route('admin.users.suspend', member.id));
        }
    };

    const toggleRole = () => {
        const next = member.role === 'admin' ? 'member' : 'admin';
        if (confirm(`Make ${member.name} ${next === 'admin' ? 'an admin' : 'a member'}?`)) {
            router.post(route('admin.users.role', member.id), { role: next });
        }
    };

    return (
        <AppShell
            eyebrow="Admin"
            title={member.name}
            intro={member.email}
            action={
                <Link href={route('admin.users.index')} className="text-sm font-medium text-neutral-600 hover:text-neutral-900">
                    ← Back to users
                </Link>
            }
        >
            <Head title={member.name} />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="lg:col-span-1">
                    <div className="rounded-2xl border border-black/5 bg-white p-6 text-center">
                        <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand/10 text-lg font-semibold text-brand">
                            {initials(member.name)}
                        </span>
                        <p className="mt-3 text-lg font-bold text-neutral-900">{member.name}</p>
                        <p className="text-sm text-neutral-500">{member.email}</p>

                        <div className="mt-3 flex items-center justify-center gap-2">
                            <span
                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                                    member.role === 'admin' ? 'bg-brand/10 text-brand' : 'bg-neutral-100 text-neutral-600'
                                }`}
                            >
                                {member.role === 'admin' ? 'Admin' : 'Member'}
                            </span>
                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${member.suspended ? 'bg-rose-50 text-brand' : 'bg-emerald-50 text-emerald-700'}`}>
                                {member.suspended ? 'Suspended' : 'Active'}
                            </span>
                        </div>

                        {isSelf ? (
                            <p className="mt-5 text-xs text-neutral-400">You can&apos;t change your own role or suspend yourself.</p>
                        ) : (
                            <div className="mt-5 space-y-2">
                                <button
                                    type="button"
                                    onClick={toggleRole}
                                    className="w-full rounded-xl border border-black/10 bg-white py-2.5 text-sm font-semibold text-neutral-900 transition-colors duration-150 hover:bg-neutral-50"
                                >
                                    {member.role === 'admin' ? 'Make Member' : 'Make Admin'}
                                </button>
                                <button
                                    type="button"
                                    onClick={toggleSuspend}
                                    className={`w-full rounded-xl py-2.5 text-sm font-bold transition-colors duration-150 ${
                                        member.suspended ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-brand text-white hover:bg-brand-dark'
                                    }`}
                                >
                                    {member.suspended ? 'Unsuspend' : 'Suspend Account'}
                                </button>
                            </div>
                        )}
                    </div>

                    <div className="mt-6 overflow-hidden rounded-2xl border border-black/5 bg-white">
                        <InfoRow label="Joined" value={member.joined_at} />
                        <div className="border-t border-black/5" />
                        <InfoRow label="Country" value={member.country} />
                        <div className="border-t border-black/5" />
                        <InfoRow label="Skill level" value={member.skill_level} />
                        <div className="border-t border-black/5" />
                        <InfoRow label="Current streak" value={member.current_streak > 0 ? `${member.current_streak} days` : '0 days'} />
                        <div className="border-t border-black/5" />
                        <InfoRow label="Longest streak" value={member.longest_streak > 0 ? `${member.longest_streak} days` : '0 days'} />
                        <div className="border-t border-black/5" />
                        <InfoRow label="Last practiced" value={member.last_practiced_on} />
                        {member.plan_choice && (
                            <>
                                <div className="border-t border-black/5" />
                                <InfoRow label="Plan interest" value={member.plan_choice} />
                            </>
                        )}
                    </div>
                    {member.plan_choice && (
                        <p className="mt-2 px-1 text-xs text-neutral-400">
                            &quot;Plan interest&quot; reflects what the member selected at signup — there&apos;s no billing system connected yet, so this isn&apos;t a confirmed paid subscription.
                        </p>
                    )}
                </div>

                <div className="space-y-6 lg:col-span-2">
                    <div className="grid grid-cols-3 gap-4">
                        <div className="rounded-2xl border border-black/5 bg-white px-5 py-4">
                            <p className="text-sm text-neutral-500">Quizzes attempted</p>
                            <p className="mt-2 text-2xl font-bold tracking-tight">{member.quizzes_attempted}</p>
                        </div>
                        <div className="rounded-2xl border border-black/5 bg-white px-5 py-4">
                            <p className="text-sm text-neutral-500">Completed</p>
                            <p className="mt-2 text-2xl font-bold tracking-tight">{member.quizzes_completed}</p>
                        </div>
                        <div className="rounded-2xl border border-black/5 bg-white px-5 py-4">
                            <p className="text-sm text-neutral-500">Avg. accuracy</p>
                            <p className="mt-2 text-2xl font-bold tracking-tight">{member.accuracy !== null ? `${member.accuracy}%` : '—'}</p>
                        </div>
                    </div>

                    <section>
                        <h2 className="text-lg font-semibold">Recent completed quizzes</h2>
                        <div className="mt-3 overflow-hidden rounded-2xl border border-black/5 bg-white">
                            {recentAttempts.length === 0 ? (
                                <p className="px-5 py-8 text-sm text-neutral-500">No completed quizzes yet.</p>
                            ) : (
                                <ul className="divide-y divide-black/5">
                                    {recentAttempts.map((attempt) => (
                                        <li key={attempt.id} className="flex items-center justify-between gap-4 px-5 py-3.5">
                                            <div>
                                                <p className="font-medium text-neutral-900">{attempt.quiz_title}</p>
                                                <p className="text-xs text-neutral-500">{attempt.completed_at}</p>
                                            </div>
                                            <p className={`text-sm font-bold ${attempt.score >= 70 ? 'text-emerald-700' : 'text-brand'}`}>{attempt.score}%</p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </section>

                    <section>
                        <h2 className="text-lg font-semibold">Awards</h2>
                        <div className="mt-3 overflow-hidden rounded-2xl border border-black/5 bg-white">
                            {awards.length === 0 ? (
                                <p className="px-5 py-8 text-sm text-neutral-500">No awards earned yet.</p>
                            ) : (
                                <ul className="divide-y divide-black/5">
                                    {awards.map((award) => (
                                        <li key={award.title} className="px-5 py-3.5">
                                            <p className="font-medium text-neutral-900">{award.title}</p>
                                            <p className="text-xs text-neutral-500">{award.description} · {award.earned_at}</p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </section>
                </div>
            </div>
        </AppShell>
    );
}
