const DAY_MS = 24 * 60 * 60 * 1000;

// Same three plans as the mobile app's PLAN_INFO (src/lib/subscription.ts).
export const PLAN_INFO = {
    lifetime: { tier: 'Premium', label: 'Lifetime', price: '$99.99', subtitle: 'Pay once, use forever' },
    monthly: { tier: 'Standard', label: 'Monthly', price: '$5.99/month', subtitle: 'Billed every month' },
    yearly: { tier: 'Standard', label: 'Yearly', price: '$49.99/year', originalPrice: '$70.99/year', discount: '30% OFF', subtitle: 'Billed every year' },
};

export const PLAN_ORDER = ['monthly', 'yearly', 'lifetime'];

export const INCLUDED = [
    'Every quiz category and level',
    'Unlimited practice and reviews',
    'Progress tracking and daily goals',
    'Personal For You recommendations',
];

function formatDate(ms) {
    return new Date(ms).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
}

// Sample subscription — mirrors the mobile app's own preview data: a paid
// Premium · Yearly member who bought 60 days ago. There's no real billing
// system on the web (no Stripe/Paystack, no subscriptions table), so this is
// for design preview only, computed fresh each load rather than hardcoded
// dates that would drift into the past.
export function sampleSubscription() {
    const paidSince = Date.now() - 60 * DAY_MS;

    return {
        planId: 'yearly',
        status: 'active',
        since: formatDate(paidSince),
        renews: formatDate(paidSince + 365 * DAY_MS),
        history: [{ plan: 'Premium · Yearly', amount: '$49.99', date: formatDate(paidSince), status: 'Paid' }],
    };
}
