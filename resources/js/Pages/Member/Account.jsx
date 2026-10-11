import AppShell from '@/Layouts/AppShell';
import { PLAN_INFO, sampleSubscription } from '@/lib/subscription';
import { Head, Link, usePage } from '@inertiajs/react';

function DiamondIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 3h12l4 6-10 12L2 9Z" />
            <path d="M11 3 8 9l4 12 4-12-3-6" />
            <path d="M2 9h20" />
        </svg>
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

function Row({ icon, label, value, first, href }) {
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

    return href ? (
        <Link href={href} className="block transition-colors duration-150 hover:bg-neutral-50">
            {content}
        </Link>
    ) : (
        content
    );
}

export default function Account() {
    const { auth } = usePage().props;
    const user = auth.user;
    const sub = sampleSubscription();
    const plan = PLAN_INFO[sub.planId];

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
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
                        <DiamondIcon className="h-3 w-3" />
                        Premium
                    </span>
                </div>

                {/* There's no real billing system on the web (no Stripe/Paystack, no
                    subscriptions table) — this is sample preview data, same idea as the
                    mobile app's own preview subscription, not a real plan. */}
                <div className="rounded-2xl bg-neutral-900 p-5 text-white sm:p-6">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <DiamondIcon className="h-[18px] w-[18px] text-amber-300" />
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
                    <Link
                        href={route('subscription')}
                        className="mt-4 block rounded-xl bg-white py-3 text-center text-sm font-bold text-neutral-900 transition-opacity duration-150 hover:opacity-90"
                    >
                        Manage Subscription
                    </Link>
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
                    </div>
                </div>

                <div>
                    <SectionLabel title="Account" />
                    <div className="overflow-hidden rounded-2xl border border-black/5 bg-white">
                        <Link href={route('logout')} method="post" as="button" className="block w-full text-left transition-colors duration-150 hover:bg-rose-50">
                            <div className="flex items-center gap-3 px-4 py-3.5">
                                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-brand">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4">
                                        <path d="M15 17l5-5-5-5M20 12H9" />
                                        <path d="M13 4H6a2 2 0 00-2 2v12a2 2 0 002 2h7" />
                                    </svg>
                                </span>
                                <span className="text-sm font-semibold text-brand">Sign out</span>
                            </div>
                        </Link>
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
        </AppShell>
    );
}
