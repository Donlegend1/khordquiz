import Eyebrow from '@/Components/Site/Eyebrow';
import { tk, useTrans } from '@/lib/i18n';

// Mirrors the app's own paywall exactly (app/paywall/[screen].tsx PLANS,
// src/lib/subscription.ts PLAN_INFO/TRIAL_DAYS/INCLUDED) — same plans,
// prices, discount and trial length. Purchases only happen through the App
// Store / Google Play inside the app, so every CTA here just leads to the
// download section rather than a web checkout that doesn't exist yet.
const PLANS = [
    { id: 'monthly', tier: tk('Standard'), title: tk('Monthly'), price: '$5.99', period: tk('/month'), subtitle: tk('Billed every month'), trial: true },
    {
        id: 'yearly',
        tier: tk('Standard'),
        title: tk('Yearly'),
        price: '$49.99',
        period: tk('/year'),
        originalPrice: '$70.99', // shown with the period, struck through
        badge: tk('30% OFF'),
        subtitle: tk('Billed every year'),
        trial: true,
        highlight: true,
    },
    { id: 'lifetime', tier: tk('Premium'), title: tk('Lifetime'), price: '$99.99', period: tk('once'), subtitle: tk('Pay once, use forever'), trial: false },
];

const INCLUDED = [
    tk('Every quiz category and level'),
    tk('Unlimited practice and reviews'),
    tk('Progress tracking and daily goals'),
    tk('Personal For You recommendations'),
];

export default function Pricing() {
    const t = useTrans();

    return (
        <section id="pricing" className="scroll-mt-[88px] bg-gradient-to-b from-[#F3F6FB] via-[#FBF5F1] to-[#FDEFE7]">
            <div className="mx-auto max-w-7xl px-6 py-16 lg:px-[100px] lg:py-20">
                <div className="mx-auto max-w-2xl text-center">
                    <Eyebrow>{t('Simple pricing')}</Eyebrow>

                    <h2 className="mt-5 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl lg:text-[44px]">
                        {t('Start your')}{' '}
                        <span className="font-display font-semibold tracking-normal text-brand">{t('7-day free trial.')}</span>
                    </h2>

                    <p className="mx-auto mt-4 max-w-[480px] text-base leading-relaxed text-neutral-700">
                        {t("Full access to every quiz, category and feature from day one. Your 7-day trial is completely free — you won't be charged until it ends.")}
                    </p>
                </div>

                <div className="mx-auto mt-12 grid max-w-5xl gap-6 lg:mt-16 lg:grid-cols-3">
                    {PLANS.map((plan) => (
                        <PlanCard key={plan.id} plan={plan} />
                    ))}
                </div>

                <div className="mx-auto mt-10 max-w-2xl rounded-2xl border border-black/5 bg-white p-6 sm:p-8">
                    <p className="text-xs font-bold uppercase tracking-wide text-neutral-400">{t("What's included")}</p>
                    <ul className="mt-4 grid gap-3 sm:grid-cols-2">
                        {INCLUDED.map((item) => (
                            <li key={item} className="flex items-start gap-2.5 text-[15px] text-neutral-800">
                                <CheckIcon />
                                {t(item)}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </section>
    );
}

function PlanCard({ plan }) {
    const t = useTrans();
    const isLifetime = plan.id === 'lifetime';

    return (
        <div
            className={`relative flex min-h-[340px] flex-col rounded-2xl p-6 sm:p-7 ${
                isLifetime
                    ? 'overflow-hidden border border-amber-400/30 bg-neutral-900 shadow-[0_18px_40px_-16px_rgba(0,0,0,0.5)]'
                    : plan.highlight
                      ? 'border border-brand bg-white shadow-[0_18px_40px_-16px_rgba(193,39,31,0.35)]'
                      : 'border border-black/5 bg-white'
            }`}
        >
            {isLifetime && (
                <div
                    className="pointer-events-none absolute inset-0"
                    style={{ background: 'radial-gradient(140% 100% at 100% 0%, rgba(251,191,36,0.16) 0%, transparent 60%)' }}
                />
            )}

            {plan.badge && (
                <span className="absolute -top-3 right-6 rounded-full bg-brand px-3 py-1 text-xs font-bold text-white">
                    {t(plan.badge)}
                </span>
            )}

            {isLifetime && (
                <span className="absolute right-6 top-6 flex h-7 w-7 items-center justify-center rounded-full bg-amber-400/15 text-amber-300 sm:right-7 sm:top-7">
                    <DiamondIcon className="h-3.5 w-3.5" />
                </span>
            )}

            <p className={`relative text-xs font-bold uppercase tracking-wide ${isLifetime ? 'text-amber-300' : 'text-neutral-400'}`}>
                {t(plan.tier)}
            </p>
            <p className={`relative mt-1 text-xl font-bold ${isLifetime ? 'text-white' : 'text-neutral-900'}`}>{t(plan.title)}</p>

            {/* Price sits in the middle of the card's free space, between the
                header above and the badge+button group pinned below. */}
            <div className="relative flex flex-1 flex-col justify-center">
                {/* Reserved even when empty, so every card's price sits on the same line. */}
                <p className="mt-4 text-sm text-neutral-400 line-through">
                    {plan.originalPrice ? `${plan.originalPrice}${t(plan.period)}` : <span className="invisible">placeholder</span>}
                </p>
                <p className="-mt-0.5">
                    <span className={`text-3xl font-extrabold ${isLifetime ? 'text-white' : 'text-neutral-900'}`}>{plan.price}</span>
                    <span className={`text-sm ${isLifetime ? 'text-white/50' : 'text-neutral-500'}`}>{plan.period.startsWith('/') ? t(plan.period) : ` ${t(plan.period)}`}</span>
                </p>
                <p className={`mt-1 text-sm ${isLifetime ? 'text-white/50' : 'text-neutral-500'}`}>{t(plan.subtitle)}</p>
            </div>

            {/* Badge and button move as one unit, anchored to the card's
                bottom — the gap this leaves lives above them, not between
                them, so the badge never floats on its own. */}
            <div className="relative mt-3">
                {plan.trial && (
                    <span className="inline-block w-fit rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">
                        {t('7 DAYS FREE')}
                    </span>
                )}
                {isLifetime && (
                    <span className="inline-block w-fit rounded-full bg-amber-400/15 px-2.5 py-1 text-xs font-bold text-amber-300">
                        {t('BEST VALUE')}
                    </span>
                )}
            </div>

            <a
                href={route('signup', { plan: plan.id })}
                className={`relative mt-6 block rounded-xl py-3 text-center text-sm font-bold transition ${
                    isLifetime
                        ? 'bg-amber-400 text-neutral-900 hover:bg-amber-300'
                        : plan.highlight
                          ? 'bg-brand text-white hover:bg-brand-dark'
                          : 'bg-neutral-100 text-neutral-900 hover:bg-neutral-200'
                }`}
            >
                {plan.trial ? t('Start Free Trial') : t('Get Lifetime')}
            </a>
        </div>
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

function CheckIcon() {
    return (
        <svg className="mt-0.5 h-5 w-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M4 10.5l4 4 8-9" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
