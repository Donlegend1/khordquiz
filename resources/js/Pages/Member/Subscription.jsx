import AppShell from '@/Layouts/AppShell';
import { INCLUDED, PLAN_INFO, PLAN_ORDER, sampleSubscription } from '@/lib/subscription';
import { tk, useLocale, useTrans } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import { Fragment, useState } from 'react';

const UPGRADE_PERKS = [
    tk('Every quiz category and level, forever'),
    tk('All future quizzes and features'),
    tk('Free updates and upgrades for life'),
    tk('No renewals, no reminders to pay'),
];

// Renders a translated sentence whose :placeholders are React nodes (e.g. a bold
// amount), so the whole sentence stays one translation key.
function fill(text, nodes) {
    return text.split(/(:[A-Za-z_]+)/).map((part, i) =>
        part.startsWith(':') && part.slice(1) in nodes ? <Fragment key={i}>{nodes[part.slice(1)]}</Fragment> : part,
    );
}

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
    const t = useTrans();
    const info = PLAN_INFO[planId];
    return (
        <div className={`flex-1 rounded-2xl border p-3.5 ${highlight ? 'border-brand/60 bg-rose-50' : 'border-black/5 bg-neutral-50'}`}>
            <p className={`text-[11px] font-extrabold tracking-wide ${highlight ? 'text-brand' : 'text-neutral-400'}`}>{label}</p>
            <p className="mt-1 text-[15px] font-extrabold text-neutral-900">{t(info.label)}</p>
            <p className="text-xs text-neutral-500">{t(info.tier)}</p>
            <p className="mt-1.5 text-sm font-bold tabular-nums text-neutral-900">{t(info.price)}</p>
        </div>
    );
}

// Matches the app's own plan-switch flow: moving between Monthly and Yearly is
// scheduled for the next renewal (nothing charged today), not applied
// immediately — this mirrors src/components/plan-switch-sheet.tsx exactly.
function SwitchModal({ planId, currentId, renews, onClose, onConfirm }) {
    const t = useTrans();
    if (!planId) return null;
    const current = PLAN_INFO[currentId];
    const next = PLAN_INFO[planId];
    const difference = yearlyCost(planId) - yearlyCost(currentId);

    return (
        <Modal open wide onClose={onClose}>
            <div className="text-center">
                <p className="text-xl font-extrabold text-neutral-900">{t('Switch to :plan?', { plan: t(next.label) })}</p>
                <p className="mt-1.5 text-sm leading-relaxed text-neutral-500">{t('Your plan changes at your next renewal. Until then nothing changes.')}</p>
            </div>

            <div className="relative mt-5 flex items-center gap-2">
                <PlanCard label={t('NOW')} planId={currentId} />
                <span className="absolute left-1/2 top-1/2 flex h-8 w-8 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border border-black/5 bg-white">
                    <ArrowRightIcon className="h-4 w-4 text-neutral-700" />
                </span>
                <PlanCard label={t('FROM :date', { date: renews.toUpperCase() })} planId={planId} highlight />
            </div>

            <div className="mt-4 space-y-2 rounded-xl bg-neutral-50 px-4 py-3">
                <div className="flex items-center justify-between text-sm">
                    <span className="text-neutral-500">{t('Switches on')}</span>
                    <span className="font-semibold text-neutral-900">{renews}</span>
                </div>
                <div className="flex items-center justify-between text-sm">
                    <span className="text-neutral-500">{t('Charged today')}</span>
                    <span className="font-semibold text-emerald-600">$0.00</span>
                </div>
                <div className="flex items-center justify-between text-sm">
                    <span className="text-neutral-500">{t('Then billed')}</span>
                    <span className="font-semibold text-neutral-900">{t(next.price)}</span>
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
                            fill(t(':plan works out to :yearly — :difference more than staying on :current.'), {
                                plan: t(next.label),
                                yearly: <span className="font-bold text-neutral-900">{t(':amount a year', { amount: money(yearlyCost(planId)) })}</span>,
                                difference: money(difference),
                                current: t(current.label),
                            })
                        ) : (
                            fill(t("You'll save :savings compared with :current."), {
                                savings: <span className="font-bold text-emerald-600">{t(':amount a year', { amount: money(-difference) })}</span>,
                                current: t(current.label),
                            })
                        )}
                    </p>
                </div>
            )}

            <div className="mt-6 space-y-2">
                <button type="button" onClick={onConfirm} className="w-full rounded-xl bg-neutral-900 py-3.5 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90">
                    {t('Switch to :plan', { plan: t(next.label) })}
                </button>
                <button type="button" onClick={onClose} className="w-full rounded-xl py-3 text-sm font-semibold text-neutral-700 hover:bg-neutral-50">
                    {t('Keep :plan', { plan: t(current.label) })}
                </button>
            </div>
        </Modal>
    );
}

// Matches the app's dedicated upgrade screen (app/upgrade.tsx): a one-time
// purchase, so this takes effect immediately rather than being scheduled.
function UpgradeModal({ open, currentId, renews, onClose, onConfirm }) {
    const t = useTrans();
    const current = PLAN_INFO[currentId];
    const lifetime = PLAN_INFO.lifetime;
    const payoff = currentId === 'yearly' ? t('Pays for itself in about 2 years') : t('Pays for itself in about 17 months');

    return (
        <Modal open={open} wide onClose={onClose}>
            <div className="flex flex-col items-center text-center">
                <span className="flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                    <DiamondIcon className="h-6 w-6" />
                </span>
                <p className="mt-3 text-xl font-extrabold text-neutral-900">{t('Upgrade to Lifetime')}</p>
                <p className="mt-1 text-sm text-neutral-500">{t('Pay once, keep Premium forever.')}</p>
            </div>

            <div className="mt-5 flex gap-2.5">
                <div className="flex-1 rounded-2xl border border-black/5 bg-neutral-50 p-3.5">
                    <p className="text-[11px] font-extrabold tracking-wide text-neutral-400">{t('NOW')}</p>
                    <p className="mt-1 text-[15px] font-extrabold text-neutral-900">{t(current.label)}</p>
                    <p className="text-sm tabular-nums text-neutral-600">{t(current.price)}</p>
                    <p className="mt-1 text-xs text-neutral-400">{currentId === 'monthly' ? t('Charged every month') : t('Charged every year')}</p>
                </div>
                <div className="flex-1 rounded-2xl border border-brand/60 bg-rose-50 p-3.5">
                    <p className="text-[11px] font-extrabold tracking-wide text-brand">{t('LIFETIME')}</p>
                    <p className="mt-1 text-[15px] font-extrabold text-neutral-900">{t('One payment')}</p>
                    <p className="text-sm font-bold tabular-nums text-neutral-900">{lifetime.price}</p>
                    <p className="mt-1 text-xs text-neutral-400">{payoff}</p>
                </div>
            </div>

            <div className="mt-5 space-y-2.5">
                {UPGRADE_PERKS.map((perk) => (
                    <div key={perk} className="flex items-center gap-2.5">
                        <CheckCircleIcon className="h-5 w-5 shrink-0 text-emerald-600" />
                        <p className="text-sm text-neutral-700">{t(perk)}</p>
                    </div>
                ))}
            </div>

            <div className="mt-5 flex items-start gap-2.5 rounded-xl bg-neutral-50 px-4 py-3">
                <InfoIcon className="mt-0.5 h-[18px] w-[18px] shrink-0 text-neutral-500" />
                <p className="text-sm leading-relaxed text-neutral-600">
                    {t("Your :plan plan won't renew on :date. You won't be charged for it again.", { plan: t(current.label), date: renews })}
                </p>
            </div>

            <div className="mt-6 space-y-2">
                <button
                    type="button"
                    onClick={onConfirm}
                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-brand py-3.5 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90"
                >
                    <LockIcon className="h-4 w-4" />
                    {t('Buy Lifetime · :price', { price: lifetime.price })}
                </button>
                <button type="button" onClick={onClose} className="w-full rounded-xl py-3 text-sm font-semibold text-neutral-700 hover:bg-neutral-50">
                    {t('Not now')}
                </button>
            </div>
        </Modal>
    );
}

const CANCEL_REASONS = [
    tk('Too expensive'),
    tk("I'm not using it enough"),
    tk("It's missing a feature I need"),
    tk('I found a better alternative'),
    tk('Technical issues or bugs'),
    tk('Other'),
];

// Two steps: first capture why the member is leaving (premade reasons, so we
// can see patterns across cancellations), then the existing confirm step.
// There's no feedback table on the web yet, so the reason stays local to this
// session rather than being persisted — same honesty-about-scope approach as
// the rest of this sample/preview page.
function CancelModal({ open, renews, tier, onClose, onConfirm }) {
    const t = useTrans();
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
                    <p className="text-base font-bold text-neutral-900">{t('Before you go, why are you cancelling?')}</p>
                    <p className="mt-1 text-sm text-neutral-500">{t('Your answer helps us improve KhordQuiz for everyone.')}</p>
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
                            <span className="text-neutral-700">{t(option)}</span>
                        </label>
                    ))}
                    {reason === 'Other' && (
                        <textarea
                            value={otherText}
                            onChange={(e) => setOtherText(e.target.value)}
                            rows={2}
                            placeholder={t('Tell us a bit more…')}
                            className="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                        />
                    )}
                </div>

                <div className="mt-6 space-y-2">
                    <button type="button" onClick={() => setStep('confirm')} disabled={!reason} className="w-full rounded-xl bg-neutral-900 py-3 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90 disabled:opacity-40">
                        {t('Continue')}
                    </button>
                    <button type="button" onClick={close} className="w-full rounded-xl py-3 text-sm font-semibold text-neutral-600 hover:bg-neutral-50">
                        {t('Keep :plan', { plan: t(tier) })}
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
                <p className="mt-3 text-base font-bold text-neutral-900">{t('Cancel subscription?')}</p>
                <p className="mt-1 text-sm text-neutral-500">{t("You'll keep :tier until :date and won't be charged again.", { tier: t(tier), date: renews })}</p>
            </div>

            <div className="mt-5 rounded-2xl bg-neutral-50 p-4">
                <p className="text-xs font-semibold text-neutral-500">{t("After :date you'll lose", { date: renews })}</p>
                <ul className="mt-2.5 space-y-2.5 text-sm">
                    <li className="flex items-center gap-2.5">
                        <LockIcon className="h-4 w-4 shrink-0 text-neutral-400" />
                        <span className="text-neutral-700">{t('Most quiz categories and levels')}</span>
                    </li>
                    <li className="flex items-center gap-2.5">
                        <LockIcon className="h-4 w-4 shrink-0 text-neutral-400" />
                        <span className="text-neutral-700">{t('Unlimited practice and reviews')}</span>
                    </li>
                    <li className="flex items-center gap-2.5">
                        <LockIcon className="h-4 w-4 shrink-0 text-neutral-400" />
                        <span className="text-neutral-700">{t('Your personal learning path')}</span>
                    </li>
                </ul>
            </div>

            <div className="mt-6 space-y-2">
                <button type="button" onClick={close} className="w-full rounded-xl bg-neutral-900 py-3 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90">
                    {t('Keep :plan', { plan: t(tier) })}
                </button>
                <button type="button" onClick={confirm} className="w-full rounded-xl py-3 text-sm font-bold text-brand hover:bg-rose-50">
                    {t('Cancel Subscription')}
                </button>
            </div>
        </Modal>
    );
}

export default function Subscription() {
    const t = useTrans();
    const sub = sampleSubscription(useLocale());
    const [planId, setPlanId] = useState(sub.planId);
    const [nextPlan, setNextPlan] = useState(null);
    const [cancelling, setCancelling] = useState(false);
    const [switchingTo, setSwitchingTo] = useState(null);
    const [upgradeOpen, setUpgradeOpen] = useState(false);
    const [cancelOpen, setCancelOpen] = useState(false);

    const plan = PLAN_INFO[planId];
    const isLifetime = planId === 'lifetime';
    // Billed and controlled entirely by the store the member bought through —
    // the website only mirrors status here, read-only. No in-app-style
    // plan-switching, upgrading, or cancelling to offer; that only happens on
    // the device, through the App Store or Google Play itself.
    const storeManaged = sub.provider === 'App Store' || sub.provider === 'Google Play';

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
        <AppShell eyebrow={t('Account')} title={t('Subscription')} intro={t('Manage your plan and billing.')}>
            <Head title={t('Subscription')} />

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
                                {t(plan.tier)} · {t(plan.label)}
                            </p>
                        </div>
                        <span
                            className={`inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-bold ${
                                cancelling ? 'bg-amber-400/20 text-amber-300' : 'bg-emerald-400/20 text-emerald-300'
                            }`}
                        >
                            <span className={`h-1.5 w-1.5 rounded-full ${cancelling ? 'bg-amber-400' : 'bg-emerald-400'}`} />
                            {cancelling ? t('Cancelling') : t('Active')}
                        </span>
                    </div>
                    <p className="mt-3 text-[26px] font-extrabold tabular-nums">{t(plan.price)}</p>
                    <p className="mt-1 text-sm text-white/60">
                        {isLifetime
                            ? t('One-time payment. Premium never expires.')
                            : cancelling
                              ? t(":tier ends on :date. You won't be charged again.", { tier: t(plan.tier), date: sub.renews })
                              : nextPlan
                                ? t('Switches to :plan (:price) on :date.', { plan: t(PLAN_INFO[nextPlan].label), price: t(PLAN_INFO[nextPlan].price), date: sub.renews })
                                : t('Renews on :date.', { date: sub.renews })}
                    </p>
                    {storeManaged ? (
                        <p className="mt-4 text-xs text-white/50">{t('This subscription is managed through the :provider.', { provider: sub.provider })}</p>
                    ) : (
                        cancelling && (
                            <button
                                type="button"
                                onClick={() => setCancelling(false)}
                                className="mt-4 w-full rounded-xl bg-white py-3 text-sm font-bold text-neutral-900 transition-opacity duration-150 hover:opacity-90"
                            >
                                {t('Resume Subscription')}
                            </button>
                        )
                    )}
                </div>

                {!isLifetime && !storeManaged && (
                    <Section title={t('Change plan')}>
                        {PLAN_ORDER.map((id, index) => {
                            const info = PLAN_INFO[id];
                            const current = id === planId;
                            const scheduled = id === nextPlan;
                            return (
                                <div key={id} className={`flex items-center gap-3 px-4 py-3.5 ${index ? 'border-t border-black/5' : ''}`}>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <p className="text-sm font-bold text-neutral-900">{t(info.label)}</p>
                                            {(id === 'lifetime' || info.discount) && (
                                                <span className="rounded-md bg-rose-50 px-1.5 py-0.5 text-[10px] font-extrabold text-brand">
                                                    {id === 'lifetime' ? t('BEST VALUE') : t(info.discount)}
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-xs text-neutral-400">
                                            {info.originalPrice && <span className="line-through">{t(info.originalPrice)} </span>}
                                            <span className={info.originalPrice ? 'font-semibold text-neutral-700' : ''}>{t(info.price)}</span>
                                            {!info.originalPrice && ` · ${t(info.subtitle)}`}
                                        </p>
                                    </div>
                                    {current ? (
                                        <span className="shrink-0 text-xs font-bold text-emerald-600">{t('Current')}</span>
                                    ) : scheduled ? (
                                        <span className="shrink-0 text-xs font-bold text-amber-600">{t('From :date', { date: sub.renews })}</span>
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={() => (id === 'lifetime' ? setUpgradeOpen(true) : setSwitchingTo(id))}
                                            className={`shrink-0 rounded-full px-3.5 py-1.5 text-xs font-bold transition-opacity duration-150 hover:opacity-90 ${
                                                id === 'lifetime' ? 'bg-brand text-white' : 'bg-neutral-100 text-neutral-700'
                                            }`}
                                        >
                                            {id === 'lifetime' ? t('Upgrade') : t('Switch')}
                                        </button>
                                    )}
                                </div>
                            );
                        })}
                    </Section>
                )}

                <Section
                    title={t('Billing history')}
                    action={
                        <button type="button" disabled title={t('Coming soon')} className="cursor-default text-xs font-bold text-brand/60">
                            {t('View all')}
                        </button>
                    }
                >
                    {sub.history.map((payment, index) => (
                        <div key={`${payment.date}-${index}`} className={`flex items-center gap-3 px-4 py-3.5 ${index ? 'border-t border-black/5' : ''}`}>
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-semibold text-neutral-900">{t(payment.plan)}</p>
                                <p className="text-xs text-neutral-400">{payment.date}</p>
                            </div>
                            <div className="shrink-0 text-right">
                                <p className="text-sm font-bold tabular-nums text-neutral-900">{payment.amount}</p>
                                <p className="text-xs font-semibold text-emerald-600">{t(payment.status)}</p>
                            </div>
                        </div>
                    ))}
                </Section>

                <Section title={t("What's included")}>
                    {INCLUDED.map((item, index) => (
                        <div key={item} className={`flex items-center gap-3 px-4 py-3 ${index ? 'border-t border-black/5' : ''}`}>
                            <CheckCircleIcon className="h-[18px] w-[18px] shrink-0 text-emerald-600" />
                            <p className="text-sm text-neutral-700">{t(item)}</p>
                        </div>
                    ))}
                </Section>

                {!cancelling && !isLifetime && !storeManaged && (
                    <button type="button" onClick={() => setCancelOpen(true)} className="block w-full py-2 text-center text-sm font-bold text-brand hover:underline">
                        {t('Cancel Subscription')}
                    </button>
                )}

                <p className="mt-6 text-center text-xs text-neutral-400">
                    {storeManaged
                        ? t('To change your plan or cancel, open the KhordQuiz app and manage it through the :provider.', { provider: sub.provider })
                        : t('Questions about billing? Contact us from Support.')}
                </p>
            </div>

            <SwitchModal planId={switchingTo} currentId={planId} renews={sub.renews} onClose={() => setSwitchingTo(null)} onConfirm={confirmSwitch} />
            <UpgradeModal open={upgradeOpen} currentId={planId} renews={sub.renews} onClose={() => setUpgradeOpen(false)} onConfirm={confirmUpgrade} />
            <CancelModal open={cancelOpen} renews={sub.renews} tier={plan.tier} onClose={() => setCancelOpen(false)} onConfirm={confirmCancel} />
        </AppShell>
    );
}
