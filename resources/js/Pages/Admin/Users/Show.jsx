import AppShell from '@/Layouts/AppShell';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

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

const PLAN_LABELS = { lifetime: 'Lifetime', monthly: 'Monthly', yearly: 'Yearly' };
const EXTEND_OPTIONS = [
    { days: 7, label: '+7 days' },
    { days: 30, label: '+30 days' },
    { days: 90, label: '+90 days' },
    { days: 365, label: '+1 year' },
];

// Admin-managed plan controls — set, extend or cancel a member's plan and expiry date.
// There's still no real billing system behind this (no Stripe/PayPal subscriptions
// table), so this is a manual override for grants, comps and support cases, not a
// record of actual payment. Every write here is clearly a human decision, not billing.
function SubscriptionCard({ member }) {
    const [editing, setEditing] = useState(false);
    const form = useForm({
        plan_choice: member.plan_choice ?? 'monthly',
        payment_method: member.payment_method ?? '',
        plan_expires_at: '',
    });

    const savePlan = (event) => {
        event.preventDefault();
        form.post(route('admin.users.plan', member.id), {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    };

    const extend = (days) => {
        router.post(route('admin.users.plan.extend', member.id), { days }, { preserveScroll: true });
    };

    const cancelPlan = () => {
        if (confirm(`Cancel ${member.name}'s plan? This clears their plan, payment method and dates.`)) {
            router.post(route('admin.users.plan.cancel', member.id), {}, { preserveScroll: true });
        }
    };

    return (
        <section>
            <div className="flex items-center gap-2.5">
                <h2 className="text-lg font-semibold">Subscription</h2>
                {member.plan_choice && (
                    <span
                        className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                            member.plan_expired ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'
                        }`}
                    >
                        {member.plan_expired ? 'Expired' : 'Granted'}
                    </span>
                )}
            </div>
            <p className="mt-1 text-sm text-neutral-500">
                Admin-managed — there&apos;s no billing system connected yet, so this is a manual grant, not a confirmed payment.
            </p>

            <div className="mt-3 overflow-hidden rounded-2xl border border-black/5 bg-white">
                {!editing ? (
                    <div className="grid sm:grid-cols-2">
                        <div className="divide-y divide-black/5 sm:border-r sm:border-black/5">
                            <InfoRow label="Plan" value={member.plan_choice ? PLAN_LABELS[member.plan_choice] : 'No plan'} />
                            <InfoRow label="Payment method" value={member.payment_method === 'stripe' ? 'Stripe' : member.payment_method === 'paypal' ? 'PayPal' : null} />
                            <InfoRow label="Member since" value={member.plan_started_at} />
                            <InfoRow label="Expires" value={member.plan_choice === 'lifetime' ? 'Never' : member.plan_expires_at} />
                        </div>

                        <div className="border-t border-black/5 p-4 sm:border-t-0">
                            <button
                                type="button"
                                onClick={() => setEditing(true)}
                                className="w-full rounded-xl border border-black/10 bg-white py-2.5 text-sm font-semibold text-neutral-900 transition-colors duration-150 hover:bg-neutral-50"
                            >
                                {member.plan_choice ? 'Change plan' : 'Assign a plan'}
                            </button>
                            {member.plan_choice && member.plan_choice !== 'lifetime' && (
                                <>
                                    <p className="mb-2 mt-4 text-xs font-medium text-neutral-500">Extend</p>
                                    <div className="grid grid-cols-4 gap-2">
                                        {EXTEND_OPTIONS.map((option) => (
                                            <button
                                                key={option.days}
                                                type="button"
                                                onClick={() => extend(option.days)}
                                                className="rounded-lg border border-black/10 bg-white py-2 text-xs font-semibold text-neutral-700 transition-colors duration-150 hover:bg-neutral-50"
                                            >
                                                {option.label}
                                            </button>
                                        ))}
                                    </div>
                                </>
                            )}
                            {member.plan_choice && (
                                <button
                                    type="button"
                                    onClick={cancelPlan}
                                    className="mt-4 w-full rounded-xl py-2.5 text-sm font-bold text-brand transition-colors duration-150 hover:bg-rose-50"
                                >
                                    Cancel plan
                                </button>
                            )}
                        </div>
                    </div>
                ) : (
                    <form onSubmit={savePlan} className="grid gap-4 p-5 sm:grid-cols-3 sm:items-end">
                        <div>
                            <label className="text-xs font-medium text-neutral-500">Plan</label>
                            <select
                                value={form.data.plan_choice}
                                onChange={(e) => form.setData('plan_choice', e.target.value)}
                                className="mt-1 w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                            >
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="lifetime">Lifetime</option>
                            </select>
                        </div>
                        <div>
                            <label className="text-xs font-medium text-neutral-500">Payment method</label>
                            <select
                                value={form.data.payment_method}
                                onChange={(e) => form.setData('payment_method', e.target.value)}
                                className="mt-1 w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                            >
                                <option value="">Not set</option>
                                <option value="stripe">Stripe</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>
                        {form.data.plan_choice !== 'lifetime' ? (
                            <div>
                                <label className="text-xs font-medium text-neutral-500">Expires on</label>
                                <input
                                    type="date"
                                    value={form.data.plan_expires_at}
                                    onChange={(e) => form.setData('plan_expires_at', e.target.value)}
                                    className="mt-1 w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                                />
                                <p className="mt-1 text-xs text-neutral-400">Blank = no expiry.</p>
                            </div>
                        ) : (
                            <div />
                        )}

                        <div className="flex gap-2 sm:col-span-3">
                            <button
                                type="submit"
                                disabled={form.processing}
                                className="flex-1 rounded-xl bg-brand py-2.5 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90 disabled:opacity-60 sm:flex-none sm:px-8"
                            >
                                Save
                            </button>
                            <button
                                type="button"
                                onClick={() => setEditing(false)}
                                className="flex-1 rounded-xl border border-black/10 bg-white py-2.5 text-sm font-semibold text-neutral-700 transition-colors duration-150 hover:bg-neutral-50 sm:flex-none sm:px-8"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </section>
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

                    <section className="mt-6">
                        <h2 className="text-lg font-semibold">Account</h2>
                        <div className="mt-3 divide-y divide-black/5 overflow-hidden rounded-2xl border border-black/5 bg-white">
                            <InfoRow label="Joined" value={member.joined_at} />
                            <InfoRow label="Country" value={member.country} />
                            <InfoRow label="Skill level" value={member.skill_level} />
                            <InfoRow label="Last practiced" value={member.last_practiced_on} />
                        </div>
                    </section>
                </div>

                <div className="space-y-6 lg:col-span-2">
                    <SubscriptionCard member={member} />

                    <section>
                        <h2 className="text-lg font-semibold">Performance</h2>
                        <div className="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div className="rounded-2xl border border-black/5 bg-white px-5 py-4">
                                <p className="text-sm text-neutral-500">Attempted</p>
                                <p className="mt-2 text-2xl font-bold tracking-tight">{member.quizzes_attempted}</p>
                            </div>
                            <div className="rounded-2xl border border-black/5 bg-white px-5 py-4">
                                <p className="text-sm text-neutral-500">Completed</p>
                                <p className="mt-2 text-2xl font-bold tracking-tight">{member.quizzes_completed}</p>
                            </div>
                            <div className="rounded-2xl border border-black/5 bg-white px-5 py-4">
                                <p className="text-sm text-neutral-500">Accuracy</p>
                                <p className="mt-2 text-2xl font-bold tracking-tight">{member.accuracy !== null ? `${member.accuracy}%` : '—'}</p>
                            </div>
                            <div className="rounded-2xl border border-black/5 bg-white px-5 py-4">
                                <p className="text-sm text-neutral-500">Streak</p>
                                <p className="mt-2 text-2xl font-bold tracking-tight">
                                    {member.current_streak}
                                    <span className="ml-1 text-sm font-medium text-neutral-400">best {member.longest_streak}</span>
                                </p>
                            </div>
                        </div>
                    </section>

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
