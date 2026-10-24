import AppShell from '@/Layouts/AppShell';
import { PLAN_INFO, sampleSubscription } from '@/lib/subscription';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

function DiamondIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 3h12l4 6-10 12L2 9Z" />
            <path d="M11 3 8 9l4 12 4-12-3-6" />
            <path d="M2 9h20" />
        </svg>
    );
}

function StarIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
            <path d="M12 2.5l2.9 6.4 6.9.7-5.2 4.7 1.5 6.8L12 17.8 5.9 21.1l1.5-6.8-5.2-4.7 6.9-.7L12 2.5z" />
        </svg>
    );
}

function LogOutIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M15 17l5-5-5-5M20 12H9" />
            <path d="M13 4H6a2 2 0 00-2 2v12a2 2 0 002 2h7" />
        </svg>
    );
}

function SignOutModal({ open, onClose, user }) {
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/40 p-4" onClick={onClose}>
            <div className="w-full max-w-sm rounded-2xl bg-white p-6" onClick={(e) => e.stopPropagation()}>
                <div className="flex flex-col items-center text-center">
                    <span className="flex h-16 w-16 items-center justify-center rounded-full bg-rose-50 text-brand">
                        <LogOutIcon className="h-7 w-7" />
                    </span>
                    <p className="mt-3 text-xl font-extrabold text-neutral-900">Sign out?</p>
                    <p className="mt-1 text-sm text-neutral-500">You&apos;ll need to sign in again to keep training. You can come back any time.</p>
                </div>

                <div className="mt-5 flex items-center gap-3 rounded-2xl bg-neutral-50 p-3.5">
                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand/10 text-sm font-bold text-brand">{initials(user.name)}</span>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs font-semibold text-neutral-500">Signed in as</p>
                        <p className="truncate text-sm font-bold text-neutral-900">{user.name}</p>
                        <p className="truncate text-xs text-neutral-500">{user.email}</p>
                    </div>
                </div>

                <div className="mt-5 space-y-2">
                    <Link href={route('logout')} method="post" as="button" className="flex w-full items-center justify-center gap-2 rounded-xl bg-brand py-3.5 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90">
                        <LogOutIcon className="h-4 w-4" />
                        Sign Out
                    </Link>
                    <button type="button" onClick={onClose} className="w-full rounded-xl bg-neutral-100 py-3.5 text-sm font-bold text-neutral-900 transition-colors duration-150 hover:bg-neutral-200">
                        Cancel
                    </button>
                </div>
            </div>
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

function SectionLabel({ title, action }) {
    return (
        <div className="mb-2 flex items-center justify-between px-1">
            <p className="text-xs font-semibold uppercase tracking-wide text-neutral-400">{title}</p>
            {action}
        </div>
    );
}

function Row({ icon, label, value, first, href, download }) {
    const content = (
        <div className={`flex items-center gap-3 px-4 py-3.5 ${first ? '' : 'border-t border-black/5'}`}>
            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600">
                {icon}
            </span>
            <span className="text-sm font-semibold text-neutral-900">{label}</span>
            <span className="flex-1" />
            <span className="max-w-[55%] truncate text-sm text-neutral-500">{value}</span>
            {href && (
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="2" className="h-4 w-4 shrink-0 text-neutral-300">
                    <path d="M7.5 5l5 5-5 5" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            )}
        </div>
    );

    if (!href) return content;

    // A real data download needs a native navigation, not an Inertia visit.
    if (download) {
        return (
            <a href={href} className="block transition-colors duration-150 hover:bg-neutral-50">
                {content}
            </a>
        );
    }

    return (
        <Link href={href} className="block transition-colors duration-150 hover:bg-neutral-50">
            {content}
        </Link>
    );
}

export default function Account() {
    const { auth } = usePage().props;
    const user = auth.user;
    const sub = sampleSubscription();
    const plan = PLAN_INFO[sub.planId];
    const [signingOut, setSigningOut] = useState(false);

    return (
        <AppShell eyebrow="Member" title="Account" intro="An overview of your profile and subscription.">
            <Head title="Account" />

            <div className="mx-auto max-w-2xl space-y-6">
                <div className="flex flex-col items-center gap-3 py-2 text-center">
                    <span className="flex h-20 w-20 items-center justify-center rounded-full bg-brand/10 text-2xl font-bold text-brand ring-4 ring-white">
                        {initials(user.name)}
                    </span>
                    <div>
                        <p className="text-xl font-extrabold text-neutral-900">{user.name}</p>
                        <p className="text-sm text-neutral-500">{user.email}</p>
                    </div>
                    <span
                        className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ${
                            plan.tier === 'Premium' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600'
                        }`}
                    >
                        {plan.tier === 'Premium' ? <DiamondIcon className="h-3 w-3" /> : <StarIcon className="h-3 w-3" />}
                        {plan.tier}
                    </span>
                </div>

                {/* There's no real billing system on the web (no Stripe/Paystack, no
                    subscriptions table) — this is sample preview data, same idea as the
                    mobile app's own preview subscription, not a real plan. */}
                <div className="rounded-2xl bg-neutral-900 p-5 text-white sm:p-6">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            {plan.tier === 'Premium' ? (
                                <DiamondIcon className="h-[18px] w-[18px] text-amber-300" />
                            ) : (
                                <StarIcon className="h-[18px] w-[18px] text-slate-300" />
                            )}
                            <p className="text-[17px] font-extrabold">
                                {plan.tier} · {plan.label}
                            </p>
                        </div>
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/20 px-2 py-1 text-xs font-bold text-emerald-300">
                            <span className="h-1.5 w-1.5 rounded-full bg-emerald-400" />
                            Active
                        </span>
                    </div>
                    <p className="mt-3 text-2xl font-extrabold tabular-nums">{plan.price}</p>
                    <p className="mt-1 text-sm text-white/60">Renews on {sub.renews}.</p>
                    {sub.provider === 'App Store' || sub.provider === 'Google Play' ? (
                        <p className="mt-4 text-xs text-white/50">This subscription is managed through the {sub.provider}.</p>
                    ) : (
                        <Link
                            href={route('subscription')}
                            className="mt-4 block rounded-xl bg-white py-3 text-center text-sm font-bold text-neutral-900 transition-opacity duration-150 hover:opacity-90"
                        >
                            Manage Subscription
                        </Link>
                    )}
                </div>

                <div>
                    <SectionLabel
                        title="Personal information"
                        action={
                            <Link
                                href={route('profile.edit')}
                                className="inline-flex items-center gap-1 rounded-full bg-brand/10 px-2.5 py-1 text-xs font-bold text-brand transition-colors duration-150 hover:bg-brand/15"
                            >
                                Edit
                            </Link>
                        }
                    />
                    <div className="overflow-hidden rounded-2xl border border-black/5 bg-white">
                        <Row
                            first
                            label="Name"
                            value={user.name}
                            icon={
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4">
                                    <circle cx="12" cy="8" r="3.5" />
                                    <path d="M4.5 20c1.3-3.5 4.2-5.5 7.5-5.5s6.2 2 7.5 5.5" />
                                </svg>
                            }
                        />
                        <Row
                            label="Email"
                            value={user.email}
                            icon={
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4">
                                    <rect x="3.5" y="5.5" width="17" height="13" rx="2" />
                                    <path d="M4 7l8 6 8-6" />
                                </svg>
                            }
                        />
                        <Row
                            label="Country"
                            value={user.country || 'Not set'}
                            icon={
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4">
                                    <circle cx="12" cy="12" r="8.5" />
                                    <path d="M3.5 12h17M12 3.5c2.5 2.3 3.8 5.3 3.8 8.5s-1.3 6.2-3.8 8.5c-2.5-2.3-3.8-5.3-3.8-8.5S9.5 5.8 12 3.5z" />
                                </svg>
                            }
                        />
                        <Row
                            label="Skill level"
                            value={user.skill_level || 'Not set'}
                            icon={
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4">
                                    <path d="M9 18V9M15 18V5" strokeLinecap="round" />
                                </svg>
                            }
                        />
                    </div>
                </div>

                <div>
                    <SectionLabel title="Security" />
                    <div className="overflow-hidden rounded-2xl border border-black/5 bg-white">
                        <Row
                            first
                            href={route('account.password')}
                            label="Change password"
                            value=""
                            icon={
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4">
                                    <rect x="5" y="10.5" width="14" height="9" rx="2" />
                                    <path d="M8 10.5V7a4 4 0 118 0v3.5" />
                                </svg>
                            }
                        />
                        <Row
                            href={route('profile.export')}
                            download
                            label="Download my data"
                            value=""
                            icon={
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4">
                                    <path d="M12 4v11m0 0l-4-4m4 4l4-4" strokeLinecap="round" strokeLinejoin="round" />
                                    <path d="M5 17v2a2 2 0 002 2h10a2 2 0 002-2v-2" />
                                </svg>
                            }
                        />
                    </div>
                </div>

                <div>
                    <SectionLabel title="Account" />
                    <div className="overflow-hidden rounded-2xl border border-black/5 bg-white">
                        <button type="button" onClick={() => setSigningOut(true)} className="block w-full text-left transition-colors duration-150 hover:bg-rose-50">
                            <div className="flex items-center gap-3 px-4 py-3.5">
                                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-brand">
                                    <LogOutIcon className="h-4 w-4" />
                                </span>
                                <span className="text-sm font-semibold text-brand">Sign out</span>
                            </div>
                        </button>
                        <Link href={route('account.delete')} className="block w-full text-left transition-colors duration-150 hover:bg-rose-50">
                            <div className="flex items-center gap-3 border-t border-black/5 px-4 py-3.5">
                                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-brand">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4">
                                        <path d="M5 7h14M9 7V5.5A1.5 1.5 0 0110.5 4h3A1.5 1.5 0 0115 5.5V7m-7 0v12a2 2 0 002 2h4a2 2 0 002-2V7" />
                                    </svg>
                                </span>
                                <span className="text-sm font-semibold text-brand">Delete account</span>
                            </div>
                        </Link>
                    </div>
                </div>

                <p className="pb-2 text-center text-xs text-neutral-400">KhordQuiz web</p>
            </div>

            <SignOutModal open={signingOut} onClose={() => setSigningOut(false)} user={user} />
        </AppShell>
    );
}
