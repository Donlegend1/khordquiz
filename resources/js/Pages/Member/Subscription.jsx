import AppShell from '@/Layouts/AppShell';
import { INCLUDED, PLAN_INFO, PLAN_ORDER, sampleSubscription } from '@/lib/subscription';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

const UPGRADE_PERKS = ['Every quiz category and level, forever', 'All future quizzes and features', 'Free updates and upgrades for life', 'No renewals, no reminders to pay'];

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

function CheckCircleIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="8.5" />
            <path d="M8.5 12.5l2.3 2.3L15.8 9.5" />
        </svg>
    );
}

function HeartOffIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M12 20s-7-4.4-9.3-9A5 5 0 0112 6.5" />
            <path d="M16.5 5a5 5 0 014.8 6.4" />
            <path d="M3 3l18 18" />
        </svg>
    );
}

function ArrowRightIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 12h14M13 6l6 6-6 6" />
        </svg>
    );
}

function InfoIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 11v5M12 8v.01" />
        </svg>
    );
}

function TagIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M12 3l7.5 7.5a2 2 0 010 2.8L13 20a2 2 0 01-2.8 0L3 12.3V3z" />
            <circle cx="7.5" cy="7.5" r="1.3" fill="currentColor" stroke="none" />
        </svg>
    );
}

function LockIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <rect x="5" y="10.5" width="14" height="9" rx="2" />
            <path d="M8 10.5V7a4 4 0 118 0v3.5" />
        </svg>
    );
}

function Section({ title, action, children }) {
    return (
        <div className="mb-6">
            <div className="mb-2 flex items-center justify-between px-1">
                <p className="text-xs font-bold uppercase tracking-wide text-neutral-400">{title}</p>
                {action}
            </div>
            <div className="overflow-hidden rounded-2xl border border-black/5 bg-white">{children}</div>
        </div>
    );
}

function Modal({ open, onClose, children, wide }) {
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/40 p-4" onClick={onClose}>
            <div className={`w-full ${wide ? 'max-w-md' : 'max-w-sm'} max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6`} onClick={(e) => e.stopPropagation()}>
                {children}
            </div>
        </div>
    );
}

const amount = (price) => Number(price.replace(/[^0-9.]/g, ''));
const money = (value) => `$${value.toFixed(2)}`;
// What a recurring plan costs over a year, for comparing Monthly with Yearly.
const yearlyCost = (planId) => amount(PLAN_INFO[planId].price) * (planId === 'monthly' ? 12 : 1);

function PlanCard({ label, planId, highlight }) {
    const info = PLAN_INFO[planId];
    return (
        <div className={`flex-1 rounded-2xl border p-3.5 ${highlight ? 'border-brand/60 bg-rose-50' : 'border-black/5 bg-neutral-50'}`}>
            <p className={`text-[11px] font-extrabold tracking-wide ${highlight ? 'text-brand' : 'text-neutral-400'}`}>{label}</p>
            <p className="mt-1 text-[15px] font-extrabold text-neutral-900">{info.label}</p>
            <p className="text-xs text-neutral-500">{info.tier}</p>
            <p className="mt-1.5 text-sm font-bold tabular-nums text-neutral-900">{info.price}</p>
        </div>
    );
}

// Matches the app's own plan-switch flow: moving between Monthly and Yearly is
// scheduled for the next renewal (nothing charged today), not applied
// immediately — this mirrors src/components/plan-switch-sheet.tsx exactly.
function SwitchModal({ planId, currentId, renews, onClose, onConfirm }) {
    if (!planId) return null;
    const current = PLAN_INFO[currentId];
    const next = PLAN_INFO[planId];
    const difference = yearlyCost(planId) - yearlyCost(currentId);

    return (
        <Modal open wide onClose={onClose}>
            <div className="text-center">
                <p className="text-xl font-extrabold text-neutral-900">Switch to {next.label}?</p>
                <p className="mt-1.5 text-sm leading-relaxed text-neutral-500">Your plan changes at your next renewal. Until then nothing changes.</p>
            </div>

            <div className="relative mt-5 flex items-center gap-2">
                <PlanCard label="NOW" planId={currentId} />
                <span className="absolute left-1/2 top-1/2 flex h-8 w-8 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border border-black/5 bg-white">
                    <ArrowRightIcon className="h-4 w-4 text-neutral-700" />
                </span>
                <PlanCard label={`FROM ${renews.toUpperCase()}`} planId={planId} highlight />
            </div>

            <div className="mt-4 space-y-2 rounded-xl bg-neutral-50 px-4 py-3">
                <div className="flex items-center justify-between text-sm">
                    <span className="text-neutral-500">Switches on</span>
                    <span className="font-semibold text-neutral-900">{renews}</span>
                </div>
                <div className="flex items-center justify-between text-sm">
                    <span className="text-neutral-500">Charged today</span>
                    <span className="font-semibold text-emerald-600">$0.00</span>
                </div>
                <div className="flex items-center justify-between text-sm">
                    <span className="text-neutral-500">Then billed</span>
                    <span className="font-semibold text-neutral-900">{next.price}</span>
                </div>
            </div>

            {Math.abs(difference) >= 0.01 && (
                <div className="mt-4 flex items-start gap-2.5">
                    {difference > 0 ? (
                        <InfoIcon className="mt-0.5 h-[18px] w-[18px] shrink-0 text-amber-600" />
                    ) : (
                        <TagIcon className="mt-0.5 h-[18px] w-[18px] shrink-0 text-emerald-600" />
                    )}
                    <p className="text-sm leading-relaxed text-neutral-600">
                        {difference > 0 ? (
                            <>
                                {next.label} works out to <span className="font-bold text-neutral-900">{money(yearlyCost(planId))} a year</span> — {money(difference)} more than staying on {current.label}.
                            </>
                        ) : (
                            <>
                                You&apos;ll save <span className="font-bold text-emerald-600">{money(-difference)} a year</span> compared with {current.label}.
                            </>
                        )}
                    </p>
                </div>
            )}

            <div className="mt-6 space-y-2">
                <button type="button" onClick={onConfirm} className="w-full rounded-xl bg-neutral-900 py-3.5 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90">
                    Switch to {next.label}
                </button>
                <button type="button" onClick={onClose} className="w-full rounded-xl py-3 text-sm font-semibold text-neutral-700 hover:bg-neutral-50">
                    Keep {current.label}
                </button>
            </div>
        </Modal>
    );
}

// Matches the app's dedicated upgrade screen (app/upgrade.tsx): a one-time
// purchase, so this takes effect immediately rather than being scheduled.
function UpgradeModal({ open, currentId, renews, onClose, onConfirm }) {
    const current = PLAN_INFO[currentId];
    const lifetime = PLAN_INFO.lifetime;
    const payoff = currentId === 'yearly' ? 'Pays for itself in about 2 years' : 'Pays for itself in about 17 months';

    return (
        <Modal open={open} wide onClose={onClose}>
            <div className="flex flex-col items-center text-center">
                <span className="flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                    <DiamondIcon className="h-6 w-6" />
                </span>
                <p className="mt-3 text-xl font-extrabold text-neutral-900">Upgrade to Lifetime</p>
                <p className="mt-1 text-sm text-neutral-500">Pay once, keep Premium forever.</p>
            </div>

            <div className="mt-5 flex gap-2.5">
                <div className="flex-1 rounded-2xl border border-black/5 bg-neutral-50 p-3.5">
                    <p className="text-[11px] font-extrabold tracking-wide text-neutral-400">NOW</p>
                    <p className="mt-1 text-[15px] font-extrabold text-neutral-900">{current.label}</p>
                    <p className="text-sm tabular-nums text-neutral-600">{current.price}</p>
                    <p className="mt-1 text-xs text-neutral-400">{currentId === 'monthly' ? 'Charged every month' : 'Charged every year'}</p>
                </div>
                <div className="flex-1 rounded-2xl border border-brand/60 bg-rose-50 p-3.5">
                    <p className="text-[11px] font-extrabold tracking-wide text-brand">LIFETIME</p>
                    <p className="mt-1 text-[15px] font-extrabold text-neutral-900">One payment</p>
                    <p className="text-sm font-bold tabular-nums text-neutral-900">{lifetime.price}</p>
                    <p className="mt-1 text-xs text-neutral-400">{payoff}</p>
                </div>
            </div>

            <div className="mt-5 space-y-2.5">
                {UPGRADE_PERKS.map((perk) => (
                    <div key={perk} className="flex items-center gap-2.5">
                        <CheckCircleIcon className="h-5 w-5 shrink-0 text-emerald-600" />
                        <p className="text-sm text-neutral-700">{perk}</p>
                    </div>
                ))}
            </div>

            <div className="mt-5 flex items-start gap-2.5 rounded-xl bg-neutral-50 px-4 py-3">
                <InfoIcon className="mt-0.5 h-[18px] w-[18px] shrink-0 text-neutral-500" />
                <p className="text-sm leading-relaxed text-neutral-600">
                    Your {current.label} plan won&apos;t renew on {renews}. You won&apos;t be charged for it again.
                </p>
            </div>

            <div className="mt-6 space-y-2">
                <button
                    type="button"
                    onClick={onConfirm}
                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-brand py-3.5 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90"
                >
                    <LockIcon className="h-4 w-4" />
                    Buy Lifetime · {lifetime.price}
                </button>
                <button type="button" onClick={onClose} className="w-full rounded-xl py-3 text-sm font-semibold text-neutral-700 hover:bg-neutral-50">
                    Not now
                </button>
            </div>
        </Modal>
    );
}

const CANCEL_REASONS = [
    'Too expensive',
    "I'm not using it enough",
    "It's missing a feature I need",
    'I found a better alternative',
    'Technical issues or bugs',
    'Other',
];

// Two steps: first capture why the member is leaving (premade reasons, so we
// can see patterns across cancellations), then the existing confirm step.
// There's no feedback table on the web yet, so the reason stays local to this
// session rather than being persisted — same honesty-about-scope approach as
// the rest of this sample/preview page.
function CancelModal({ open, renews, tier, onClose, onConfirm }) {
    const [step, setStep] = useState('reason');
    const [reason, setReason] = useState(null);
    const [otherText, setOtherText] = useState('');

    const close = () => {
        onClose();
        setStep('reason');
        setReason(null);
        setOtherText('');
    };

    const confirm = () => {
        onConfirm();
        setStep('reason');
        setReason(null);
        setOtherText('');
    };

    if (step === 'reason') {
        return (
            <Modal open={open} onClose={close}>
                <div className="text-center">
                    <p className="text-base font-bold text-neutral-900">Before you go, why are you cancelling?</p>
                    <p className="mt-1 text-sm text-neutral-500">Your answer helps us improve KhordQuiz for everyone.</p>
                </div>

                <div className="mt-5 space-y-2">
                    {CANCEL_REASONS.map((option) => (
                        <label
                            key={option}
                            className={`flex items-center gap-2.5 rounded-xl border px-3.5 py-2.5 text-sm transition-colors duration-150 ${
                                reason === option ? 'border-brand/40 bg-rose-50' : 'border-neutral-200 hover:bg-neutral-50'
                            }`}
                        >
                            <input type="radio" checked={reason === option} onChange={() => setReason(option)} className="h-4 w-4 shrink-0 text-brand focus:ring-brand" />
                            <span className="text-neutral-700">{option}</span>
                        </label>
                    ))}
                    {reason === 'Other' && (
                        <textarea
                            value={otherText}
                            onChange={(e) => setOtherText(e.target.value)}
                            rows={2}
                            placeholder="Tell us a bit more…"
                            className="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                        />
                    )}
                </div>

                <div className="mt-6 space-y-2">
                    <button type="button" onClick={() => setStep('confirm')} disabled={!reason} className="w-full rounded-xl bg-neutral-900 py-3 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90 disabled:opacity-40">
                        Continue
                    </button>
                    <button type="button" onClick={close} className="w-full rounded-xl py-3 text-sm font-semibold text-neutral-600 hover:bg-neutral-50">
                        Keep {tier}
                    </button>
                </div>
            </Modal>
        );
    }

    return (
        <Modal open={open} onClose={close}>
            <div className="flex flex-col items-center text-center">
                <span className="flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-brand">
                    <HeartOffIcon className="h-5 w-5" />
                </span>
                <p className="mt-3 text-base font-bold text-neutral-900">Cancel subscription?</p>
                <p className="mt-1 text-sm text-neutral-500">You&apos;ll keep {tier} until {renews} and won&apos;t be charged again.</p>
            </div>

            <div className="mt-5 rounded-2xl bg-neutral-50 p-4">
                <p className="text-xs font-semibold text-neutral-500">After {renews} you&apos;ll lose</p>
                <ul className="mt-2.5 space-y-2.5 text-sm">
                    <li className="flex items-center gap-2.5">
                        <LockIcon className="h-4 w-4 shrink-0 text-neutral-400" />
                        <span className="text-neutral-700">Most quiz categories and levels</span>
                    </li>
                    <li className="flex items-center gap-2.5">
                        <LockIcon className="h-4 w-4 shrink-0 text-neutral-400" />
                        <span className="text-neutral-700">Unlimited practice and reviews</span>
                    </li>
                    <li className="flex items-center gap-2.5">
                        <LockIcon className="h-4 w-4 shrink-0 text-neutral-400" />
                        <span className="text-neutral-700">Your personal learning path</span>
                    </li>
                </ul>
            </div>

            <div className="mt-6 space-y-2">
                <button type="button" onClick={close} className="w-full rounded-xl bg-neutral-900 py-3 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90">
                    Keep {tier}
                </button>
                <button type="button" onClick={confirm} className="w-full rounded-xl py-3 text-sm font-bold text-brand hover:bg-rose-50">
                    Cancel Subscription
                </button>
            </div>
        </Modal>
    );
}

export default function Subscription() {
    const sub = sampleSubscription();
    const [planId, setPlanId] = useState(sub.planId);
    const [nextPlan, setNextPlan] = useState(null);
    const [cancelling, setCancelling] = useState(false);
    const [switchingTo, setSwitchingTo] = useState(null);
    const [upgradeOpen, setUpgradeOpen] = useState(false);
    const [cancelOpen, setCancelOpen] = useState(false);

    const plan = PLAN_INFO[planId];
    const isLifetime = planId === 'lifetime';

    const confirmSwitch = () => {
        setNextPlan(switchingTo === planId ? null : switchingTo);
        setCancelling(false);
        setSwitchingTo(null);
    };

    const confirmUpgrade = () => {
        setPlanId('lifetime');
        setNextPlan(null);
        setCancelling(false);
        setUpgradeOpen(false);
    };

    const confirmCancel = () => {
        setCancelling(true);
        setCancelOpen(false);
    };

    return (
        <AppShell eyebrow="Account" title="Subscription" intro="Manage your plan and billing.">
            <Head title="Subscription" />

            <div className="mx-auto max-w-2xl">
                <div className="mb-6 rounded-2xl bg-neutral-900 p-5 text-white sm:p-6">
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
                        <span
                            className={`inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-bold ${
                                cancelling ? 'bg-amber-400/20 text-amber-300' : 'bg-emerald-400/20 text-emerald-300'
                            }`}
                        >
                            <span className={`h-1.5 w-1.5 rounded-full ${cancelling ? 'bg-amber-400' : 'bg-emerald-400'}`} />
                            {cancelling ? 'Cancelling' : 'Active'}
                        </span>
                    </div>
                    <p className="mt-3 text-[26px] font-extrabold tabular-nums">{plan.price}</p>
                    <p className="mt-1 text-sm text-white/60">
                        {isLifetime
                            ? 'One-time payment. Premium never expires.'
                            : cancelling
                              ? `${plan.tier} ends on ${sub.renews}. You won't be charged again.`
                              : nextPlan
                                ? `Switches to ${PLAN_INFO[nextPlan].label} (${PLAN_INFO[nextPlan].price}) on ${sub.renews}.`
                                : `Renews on ${sub.renews}.`}
                    </p>
                    {cancelling && (
                        <button
                            type="button"
                            onClick={() => setCancelling(false)}
                            className="mt-4 w-full rounded-xl bg-white py-3 text-sm font-bold text-neutral-900 transition-opacity duration-150 hover:opacity-90"
                        >
                            Resume Subscription
                        </button>
                    )}
                </div>

                {!isLifetime && (
                    <Section title="Change plan">
                        {PLAN_ORDER.map((id, index) => {
                            const info = PLAN_INFO[id];
                            const current = id === planId;
                            const scheduled = id === nextPlan;
                            return (
                                <div key={id} className={`flex items-center gap-3 px-4 py-3.5 ${index ? 'border-t border-black/5' : ''}`}>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <p className="text-sm font-bold text-neutral-900">{info.label}</p>
                                            {(id === 'lifetime' || info.discount) && (
                                                <span className="rounded-md bg-rose-50 px-1.5 py-0.5 text-[10px] font-extrabold text-brand">
                                                    {id === 'lifetime' ? 'BEST VALUE' : info.discount}
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-xs text-neutral-400">
                                            {info.originalPrice && <span className="line-through">{info.originalPrice} </span>}
                                            <span className={info.originalPrice ? 'font-semibold text-neutral-700' : ''}>{info.price}</span>
                                            {!info.originalPrice && ` · ${info.subtitle}`}
                                        </p>
                                    </div>
                                    {current ? (
                                        <span className="shrink-0 text-xs font-bold text-emerald-600">Current</span>
                                    ) : scheduled ? (
                                        <span className="shrink-0 text-xs font-bold text-amber-600">From {sub.renews}</span>
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={() => (id === 'lifetime' ? setUpgradeOpen(true) : setSwitchingTo(id))}
                                            className={`shrink-0 rounded-full px-3.5 py-1.5 text-xs font-bold transition-opacity duration-150 hover:opacity-90 ${
                                                id === 'lifetime' ? 'bg-brand text-white' : 'bg-neutral-100 text-neutral-700'
                                            }`}
                                        >
                                            {id === 'lifetime' ? 'Upgrade' : 'Switch'}
                                        </button>
                                    )}
                                </div>
                            );
                        })}
                    </Section>
                )}

                <Section
                    title="Billing history"
                    action={
                        <button type="button" disabled title="Coming soon" className="cursor-default text-xs font-bold text-brand/60">
                            View all
                        </button>
                    }
                >
                    {sub.history.map((payment, index) => (
                        <div key={`${payment.date}-${index}`} className={`flex items-center gap-3 px-4 py-3.5 ${index ? 'border-t border-black/5' : ''}`}>
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-semibold text-neutral-900">{payment.plan}</p>
                                <p className="text-xs text-neutral-400">{payment.date}</p>
                            </div>
                            <div className="shrink-0 text-right">
                                <p className="text-sm font-bold tabular-nums text-neutral-900">{payment.amount}</p>
                                <p className="text-xs font-semibold text-emerald-600">{payment.status}</p>
                            </div>
                        </div>
                    ))}
                </Section>

                <Section title="What's included">
                    {INCLUDED.map((item, index) => (
                        <div key={item} className={`flex items-center gap-3 px-4 py-3 ${index ? 'border-t border-black/5' : ''}`}>
                            <CheckCircleIcon className="h-[18px] w-[18px] shrink-0 text-emerald-600" />
                            <p className="text-sm text-neutral-700">{item}</p>
                        </div>
                    ))}
                </Section>

                {!cancelling && !isLifetime && (
                    <button type="button" onClick={() => setCancelOpen(true)} className="block w-full py-2 text-center text-sm font-bold text-brand hover:underline">
                        Cancel Subscription
                    </button>
                )}

                <p className="mt-6 text-center text-xs text-neutral-400">Questions about billing? Contact us from Support.</p>
            </div>

            <SwitchModal planId={switchingTo} currentId={planId} renews={sub.renews} onClose={() => setSwitchingTo(null)} onConfirm={confirmSwitch} />
            <UpgradeModal open={upgradeOpen} currentId={planId} renews={sub.renews} onClose={() => setUpgradeOpen(false)} onConfirm={confirmUpgrade} />
            <CancelModal open={cancelOpen} renews={sub.renews} tier={plan.tier} onClose={() => setCancelOpen(false)} onConfirm={confirmCancel} />
        </AppShell>
    );
}
