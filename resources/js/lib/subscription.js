import { tk } from '@/lib/i18n';

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

// Billing-history rows come from the server in English ("Premium · Yearly",
// "Paid"); translate each part of the plan name, and the status.
export const PAYMENT_STATUSES = [tk('Paid')];
export const paymentPlanLabel = (t, plan) => plan.split(' · ').map((part) => t(part)).join(' · ');

// Badge + one-line summary for a member's plan, shared by the Account card and
// the Subscription page header. `sub` is SubscriptionPageController::forWeb().
export function planStatus(sub) {
    const trialing = sub.access === 'trial';
    const cancelling = trialing ? sub.trial?.cancelled : sub.status === 'cancelling';
    const pastDue = sub.status === 'past_due';

    const label = pastDue ? tk('Payment due') : trialing ? (cancelling ? tk('Trial ending') : tk('Free trial')) : cancelling ? tk('Cancelling') : tk('Active');
    const tone = pastDue || cancelling ? 'amber' : 'emerald';

    return {
        label,
        trialing,
        cancelling: !!cancelling,
        badge: tone === 'amber' ? 'bg-amber-400/20 text-amber-300' : 'bg-emerald-400/20 text-emerald-300',
        dot: tone === 'amber' ? 'bg-amber-400' : 'bg-emerald-400',
        line(t, plan) {
            if (sub.provider === 'KingsleyKhordPiano') {
                return sub.renews
                    ? t('Included with your Kingsleykhordpiano membership until :date.', { date: sub.renews })
                    : t('Included with your Kingsleykhordpiano membership.');
            }
            if (sub.planId === 'lifetime') return t('One-time payment. Premium never expires.');
            if (pastDue) return t('We couldn’t take your last payment. Update your card to keep your plan.');
            if (trialing) {
                const date = sub.trial?.ends_on ?? sub.renews;
                return cancelling
                    ? t('Your free trial ends on :date. You won’t be charged.', { date })
                    : t('Free trial until :date, then :price.', { date, price: t(plan.price) });
            }
            if (cancelling) return t(":tier ends on :date. You won't be charged again.", { tier: t(plan.tier), date: sub.renews });
            if (sub.nextPlan && PLAN_INFO[sub.nextPlan]) {
                return t('Switches to :plan (:price) on :date.', { plan: t(PLAN_INFO[sub.nextPlan].label), price: t(PLAN_INFO[sub.nextPlan].price), date: sub.renews });
            }
            return sub.renews ? t('Renews on :date.', { date: sub.renews }) : t('Active');
        },
    };
}
