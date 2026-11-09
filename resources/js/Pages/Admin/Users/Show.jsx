import AppShell from '@/Layouts/AppShell';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AvatarBadge from '@/Components/AvatarBadge';

function InfoRow({ label, value }) {
    return (
        <div className="flex items-center justify-between gap-4 px-5 py-3.5">
            <p className="text-sm text-neutral-500">{label}</p>
            <p className="min-w-0 break-words text-right text-sm font-medium text-neutral-900">{value ?? '—'}</p>
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
        if (confirm(`Cancel ${member.name}'s plan? This removes their plan and access dates.`)) {
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
                            <InfoRow label="Recorded payment method" value={member.payment_method === 'stripe' ? 'Stripe' : member.payment_method === 'paypal' ? 'PayPal' : null} />
                            <InfoRow label="Access start date" value={member.plan_started_at} />
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

const actionButton = 'inline-flex min-h-11 items-center justify-center rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700 disabled:cursor-default disabled:opacity-40';

function DetailSection({ title, detail, children }) {
    return <section className="min-w-0 rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6"><h2 className="font-semibold text-neutral-900">{title}</h2>{detail && <p className="mt-1 text-sm leading-relaxed text-neutral-500">{detail}</p>}<div className="mt-4">{children}</div></section>;
}

export default function Show({ member, history, reports, awards, activeTab = 'Account' }) {
    const { auth } = usePage().props;
    const isSelf = auth.user.id === member.id;
    const [tab, setTab] = useState(activeTab);
    const [note, setNote] = useState('');
    useEffect(() => setTab(activeTab), [activeTab]);
    const toggleSuspend = () => {
        if (confirm(`${member.suspended ? 'Restore access for' : 'Suspend'} ${member.name}? ${member.suspended ? 'They will be able to sign in again.' : 'They will lose access until you restore their account.'}`)) {
            router.post(route(member.suspended ? 'admin.users.unsuspend' : 'admin.users.suspend', member.id), {}, { preserveScroll: true });
        }
    };
    return <AppShell eyebrow="Admin" title="User account" intro="Profile, learning activity and account support." action={<Link href={route('admin.users.index')} className={actionButton}>Back to users</Link>}>
        <Head title={member.name} />
        <section className="mb-6 flex flex-wrap items-center justify-between gap-5 rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6">
            <div className="flex min-w-0 items-center gap-4"><AvatarBadge id={member.avatar} name={member.name} size={56} /><div className="min-w-0"><h2 className="break-words text-xl font-bold">{member.name}</h2><p className="mt-1 break-all text-sm text-neutral-500">{member.email}</p><p className="mt-2 text-xs text-neutral-400">User #{member.id} · Joined {member.joined_at}</p></div></div>
            <div className="flex flex-wrap gap-2"><span className="rounded-full bg-neutral-100 px-3 py-1.5 text-xs font-semibold text-neutral-600">{member.role === 'admin' ? 'Admin' : 'Member'}</span><span className={`rounded-full px-3 py-1.5 text-xs font-semibold ${member.suspended ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'}`}>{member.suspended ? 'Suspended' : 'Active account'}</span></div>
        </section>
        <nav aria-label="Account sections" className="mb-6 flex flex-wrap gap-1 rounded-xl border border-neutral-200 bg-white p-1">{['Account', 'Activity', 'Subscription', 'Support'].map(item => <button key={item} aria-pressed={tab === item} onClick={() => setTab(item)} className={`min-h-11 rounded-lg px-4 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 ${tab === item ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:bg-neutral-50'}`}>{item}</button>)}</nav>
        {tab === 'Account' && <div className="space-y-6">
            <div className="grid gap-6 lg:grid-cols-2"><DetailSection title="Profile details"><div className="divide-y divide-neutral-100"><InfoRow label="User ID" value={`#${member.id}`} /><InfoRow label="Joined" value={member.joined_at} /><InfoRow label="Country" value={member.country || 'Not provided'} /><InfoRow label="Language" value={member.language || 'Not provided'} /><InfoRow label="Skill level" value={member.skill_level || 'Not provided'} /><InfoRow label="Default instrument" value={member.default_instrument || 'Not provided'} /></div></DetailSection><DetailSection title="Activity and preferences"><div className="divide-y divide-neutral-100"><InfoRow label="Last practice" value={member.last_practiced_on || 'No practice recorded'} /><InfoRow label="Last login" value="Not tracked yet" /><InfoRow label="Saved quizzes" value={member.saved_count} /><InfoRow label="Marketing emails" value="Not tracked yet" /><InfoRow label="Email preference updated" value="Not tracked yet" /></div><p className="mt-4 rounded-xl bg-neutral-50 p-3 text-xs leading-relaxed text-neutral-500">Login history and marketing preferences need backend support. Missing information is not treated as consent.</p></DetailSection></div>
            <DetailSection title="Account access" detail="Manage this user’s access to the site.">{isSelf ? <p className="text-sm text-neutral-500">You cannot suspend your own account.</p> : <div><div className="rounded-xl border border-rose-100 p-4"><h3 className="text-sm font-semibold">Account access</h3><p className="mt-1 text-sm text-neutral-500">{member.suspended ? 'Restore this user’s access.' : 'Temporarily stop this user from signing in.'}</p><button onClick={toggleSuspend} className="mt-4 min-h-11 rounded-xl border border-rose-200 px-4 text-sm font-semibold text-rose-700 hover:bg-rose-50">{member.suspended ? 'Restore account' : 'Suspend account'}</button></div></div>}</DetailSection>
        </div>}
        {tab === 'Activity' && <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{[['Completed quizzes', member.completed_quizzes], ['Completed attempts', member.completed_runs], ['Average accuracy', member.accuracy === null ? '—' : `${member.accuracy}%`], ['Current streak', `${member.current_streak} days`]].map(([label, value]) => <div key={label} className="rounded-2xl border border-neutral-200 bg-white p-5"><p className="text-sm text-neutral-500">{label}</p><p className="mt-3 text-2xl font-bold">{value}</p></div>)}</div>
            <DetailSection title="Attempt history" detail="All completed runs, newest first. Retries remain separate.">{history.data.length ? <><div className="divide-y divide-neutral-100">{history.data.map(item => <article key={item.id} className="flex flex-wrap items-center justify-between gap-3 py-4 first:pt-0"><div className="min-w-0"><h3 className="font-semibold">{item.quiz_title}</h3><p className="mt-1 text-xs text-neutral-500">{item.category} · {new Date(item.completed_at).toLocaleString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>{item.sound && <p className="mt-1 text-xs text-neutral-500">{item.sound}</p>}</div><div className="text-right"><span className={`rounded-full px-3 py-1 text-sm font-bold ${item.score < 40 ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'}`}>{item.score}%</span><p className="mt-2 text-xs text-neutral-500">{item.correct} of {item.total} correct</p></div></article>)}</div>{history.last_page > 1 && <nav aria-label="User history pages" className="mt-5"><p className="mb-3 text-center text-xs text-neutral-500">Showing {history.from}–{history.to} of {history.total} attempts</p><div className="flex items-center justify-between gap-2">{history.prev_page_url ? <Link className={actionButton} href={history.prev_page_url}>Previous</Link> : <button disabled className={actionButton}>Previous</button>}<span className="text-xs text-neutral-500">Page {history.current_page} of {history.last_page}</span>{history.next_page_url ? <Link className={actionButton} href={history.next_page_url}>Next</Link> : <button disabled className={actionButton}>Next</button>}</div></nav>}</> : <p className="py-4 text-sm text-neutral-500">No completed attempts yet.</p>}</DetailSection>
            <DetailSection title="Awards">{awards.length ? <ul className="divide-y divide-neutral-100">{awards.map(award => <li key={award.title} className="py-3"><p className="font-semibold">{award.title}</p><p className="mt-1 text-sm text-neutral-500">{award.description} · {award.earned_at}</p></li>)}</ul> : <p className="text-sm text-neutral-500">No awards earned yet.</p>}</DetailSection>
        </div>}
        {tab === 'Subscription' && <div className="space-y-6"><SubscriptionCard member={member} /><DetailSection title="Access record"><div className="divide-y divide-neutral-100"><InfoRow label="Access source" value={member.plan_choice ? 'Manual / recorded plan · payment unverified' : 'No plan recorded'} /><InfoRow label="Granted by" value="Not recorded" /><InfoRow label="Last changed" value="Not tracked yet" /></div><p className="mt-4 text-xs leading-relaxed text-neutral-500">A selected payment method is not proof of payment. Grant attribution and change history need backend support.</p></DetailSection></div>}
        {tab === 'Support' && <div className="space-y-6">
            <DetailSection title="Question reports" detail={`${reports.total} submitted · ${reports.open} open`}><div className="divide-y divide-neutral-100">{reports.recent.map(report => <div key={report.id} className="flex items-start justify-between gap-3 py-3"><div><p className="text-sm font-semibold">{report.quiz_title}</p><p className="mt-1 text-xs text-neutral-500">#{report.id} · {report.reason}</p></div><span className={`rounded-full px-2 py-1 text-xs font-semibold ${report.status === 'open' ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800'}`}>{report.status === 'open' ? 'Open' : 'Resolved'}</span></div>)}</div>{!reports.total && <p className="text-sm text-neutral-500">This member has not submitted any reports.</p>}<Link href={route('admin.question-reports.index', { search: member.email })} className={`${actionButton} mt-4`}>View this member’s reports</Link></DetailSection>
            <div className="grid gap-6 lg:grid-cols-2"><DetailSection title="Internal notes" detail="Design preview · notes are not saved yet."><label className="text-sm font-semibold text-neutral-600">Private support note<textarea value={note} onChange={event => setNote(event.target.value)} maxLength={2000} rows={4} placeholder="Add context for your support team…" className="mt-2 w-full rounded-xl border-neutral-300 text-sm" /></label><button disabled className={`${actionButton} mt-3`}>Save note</button><p className="mt-3 text-xs text-neutral-500">Backend integration must record the author and date and restrict notes to admins.</p></DetailSection><DetailSection title="Admin activity log" detail="Not connected yet."><p className="rounded-xl bg-neutral-50 p-4 text-sm leading-relaxed text-neutral-500">Role, access and subscription changes will appear here with the admin, timestamp and reason once audit logging is implemented. Existing changes are not currently logged.</p></DetailSection></div>
        </div>}
    </AppShell>;
}
