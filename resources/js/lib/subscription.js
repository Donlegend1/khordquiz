import { tk } from '@/lib/i18n';

const DAY_MS = 24 * 60 * 60 * 1000;

// Same three plans as the mobile app's PLAN_INFO (src/lib/subscription.ts).
// tier/label are compared against ('Premium') and price is parsed for its number,
// so the values stay English — callers translate them at render with t(value).
export const PLAN_INFO = {
    lifetime: { tier: tk('Premium'), label: tk('Lifetime'), price: '$99.99', subtitle: tk('Pay once, use forever') },
    monthly: { tier: tk('Standard'), label: tk('Monthly'), price: tk('$5.99/month'), subtitle: tk('Billed every month') },
    yearly: { tier: tk('Standard'), label: tk('Yearly'), price: tk('$49.99/year'), originalPrice: tk('$70.99/year'), discount: tk('30% OFF'), subtitle: tk('Billed every year') },
};

export const PLAN_ORDER = ['monthly', 'yearly', 'lifetime'];

export const INCLUDED = [
    tk('Every quiz category and level'),
    tk('Unlimited practice and reviews'),
    tk('Progress tracking and daily goals'),
    tk('Personal For You recommendations'),
];

function formatDate(ms, locale) {
    return new Date(ms).toLocaleDateString(locale, { day: 'numeric', month: 'short', year: 'numeric' });
}

// Sample subscription — mirrors the mobile app's own preview data: a paid
// Premium · Yearly member who bought 60 days ago. There's no real billing
// system on the web (no Stripe/Paystack, no subscriptions table), so this is
// for design preview only, computed fresh each load rather than hardcoded
// dates that would drift into the past. `locale` is a BCP 47 tag (useLocale()).
export function sampleSubscription(locale = 'en-US') {
    const paidSince = Date.now() - 60 * DAY_MS;

    return {
        planId: 'yearly',
        status: 'active',
        since: formatDate(paidSince, locale),
        renews: formatDate(paidSince + 365 * DAY_MS, locale),
        history: [{ plan: tk('Premium · Yearly'), amount: '$49.99', date: formatDate(paidSince, locale), status: tk('Paid') }],
    };
}
