import AppShell from '@/Layouts/AppShell';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const HOUR = 60 * 60 * 1000;
const DAY = 24 * HOUR;

// Sample activity, same idea as the mobile app's own Notifications screen (see
// src/lib/notification-history.ts there): a sample feed so the page isn't empty,
// since there's no notifications table on the web yet. Read state here is local
// to this visit only.
const SAMPLE_NOTIFICATIONS = [
    { id: 'n1', kind: 'perfect', title: 'Perfect score!', body: 'You scored 100% on Relative Tone Pitch #1. Keep that ear sharp!', at: Date.now() - 2 * HOUR, read: false },
    { id: 'n2', kind: 'challenge', title: 'Ada beat your score', body: 'Ada O. scored 19/25 on Diatonic Intervals, beating your 17/25. Fancy a rematch?', at: Date.now() - 1 * DAY, read: false },
    { id: 'n3', kind: 'friend-perfect', title: 'Tunde scored 100%!', body: 'Tunde B. just aced Diatonic Intervals with a perfect score. Think you can match that?', at: Date.now() - 2 * DAY, read: false },
    { id: 'n4', kind: 'friend-perfect', title: 'Chioma scored 100%!', body: 'Chioma A. just aced Secondary 7th Chords · Level 2 with a perfect score. Think you can match that?', at: Date.now() - 2 * DAY - HOUR, read: false },
    { id: 'n5', kind: 'perfect', title: 'Perfect score!', body: 'You scored 100% on C Root Position. Keep that ear sharp!', at: Date.now() - 3 * DAY, read: true },
    { id: 'n6', kind: 'payment', title: 'Your trial is ending soon', body: 'Your free trial ends in 2 days. Premium Monthly ($9.99) starts after that — cancel anytime in Account.', at: Date.now() - 4 * DAY, read: false },
    { id: 'n7', kind: 'challenge', title: "It's a draw!", body: 'You and David both scored 20/25 on 3-Note Stepwise.', at: Date.now() - 5 * DAY, read: true },
];

const BADGES = {
    challenge: { bg: 'bg-rose-50', color: 'text-brand' },
    'friend-perfect': { bg: 'bg-violet-50', color: 'text-violet-600' },
    general: { bg: 'bg-neutral-100', color: 'text-neutral-500' },
    payment: { bg: 'bg-sky-50', color: 'text-sky-600' },
    perfect: { bg: 'bg-amber-50', color: 'text-amber-700' },
};

const BUCKETS = ['Today', 'Yesterday', 'This Week', 'Earlier'];
function bucketFor(at) {
    const diff = Date.now() - at;
    if (diff < DAY) return 'Today';
    if (diff < 2 * DAY) return 'Yesterday';
    if (diff < 7 * DAY) return 'This Week';
    return 'Earlier';
}

function relativeTime(at) {
    const diff = Date.now() - at;
    if (diff < 60_000) return 'Just now';
    if (diff < HOUR) return `${Math.floor(diff / 60_000)}m ago`;
    if (diff < DAY) return `${Math.floor(diff / HOUR)}h ago`;
    return `${Math.floor(diff / DAY)}d ago`;
}

function BadgeIcon({ kind, className }) {
    switch (kind) {
        case 'perfect':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className={className}>
                    <path d="M8 4h8v5a4 4 0 01-8 0V4z" />
                    <path d="M8 5H5a3 3 0 003 3M16 5h3a3 3 0 01-3 3" />
                    <path d="M12 13v3M9 20h6M10 20v-2.5h4V20" />
                </svg>
            );
        case 'challenge':
            return (
                <svg viewBox="0 0 24 24" fill="currentColor" stroke="none" className={className}>
                    <path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" />
                </svg>
            );
        case 'friend-perfect':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className={className}>
                    <path d="M12 3l1.6 3.8L17 8l-3.4 1.6L12 13l-1.6-3.4L7 8l3.4-1.2z" />
                    <path d="M18 14l.9 2 2.1.9-2.1.9-.9 2-.9-2L15 16.9l2.1-.9z" />
                </svg>
            );
        case 'payment':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className={className}>
                    <rect x="3.5" y="5.5" width="17" height="13" rx="2" />
                    <path d="M3.5 10h17" />
                </svg>
            );
        default:
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className={className}>
                    <path d="M6 9a6 6 0 1112 0c0 4.2 1.3 5.6 1.5 6H4.5c.2-.4 1.5-1.8 1.5-6z" />
                    <path d="M10.3 19a1.8 1.8 0 003.4 0" />
                </svg>
            );
    }
}

function BellOffIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 9a6 6 0 1112 0c0 4.2 1.3 5.6 1.5 6H4.5c.2-.4 1.5-1.8 1.5-6z" />
            <path d="M10.3 19a1.8 1.8 0 003.4 0" />
        </svg>
    );
}

export default function Notifications() {
    const [notifications, setNotifications] = useState(SAMPLE_NOTIFICATIONS);
    const hasUnread = notifications.some((item) => !item.read);

    const groups = useMemo(
        () =>
            BUCKETS.map((bucket) => ({
                bucket,
                items: notifications.filter((item) => bucketFor(item.at) === bucket),
            })).filter((group) => group.items.length > 0),
        [notifications],
    );

    function markAllRead() {
        setNotifications((current) => current.map((item) => (item.read ? item : { ...item, read: true })));
    }

    function markRead(id) {
        setNotifications((current) => current.map((item) => (item.id === id ? { ...item, read: true } : item)));
    }

    return (
        <AppShell
            eyebrow="Member"
            title="Notifications"
            intro="Perfect scores, challenge results and other updates."
            action={
                hasUnread && (
                    <button type="button" onClick={markAllRead} className="text-sm font-semibold text-brand hover:text-brand-dark">
                        Mark all read
                    </button>
                )
            }
        >
            <Head title="Notifications" />

            {groups.length === 0 ? (
                <div className="flex flex-col items-center rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-16 text-center">
                    <span className="flex h-16 w-16 items-center justify-center rounded-full bg-rose-50 text-brand">
                        <BellOffIcon className="h-7 w-7" />
                    </span>
                    <p className="mt-4 text-base font-semibold text-neutral-900">No notifications yet</p>
                    <p className="mt-1 max-w-xs text-sm text-neutral-500">
                        Perfect scores, challenge results and other updates will show up here once you&apos;ve got some.
                    </p>
                </div>
            ) : (
                <div className="space-y-6">
                    {groups.map((group) => (
                        <div key={group.bucket}>
                            <p className="mb-2 px-1 text-xs font-bold uppercase tracking-wide text-neutral-400">{group.bucket}</p>
                            <div className="space-y-2.5">
                                {group.items.map((item) => {
                                    const badge = BADGES[item.kind] ?? BADGES.general;
                                    return (
                                        <button
                                            key={item.id}
                                            type="button"
                                            onClick={() => markRead(item.id)}
                                            className={`relative flex w-full items-start gap-3 rounded-2xl border px-4 py-3.5 text-left transition-colors duration-150 ${
                                                item.read ? 'border-black/5 bg-white hover:bg-neutral-50' : 'border-rose-100 bg-rose-50/60 hover:bg-rose-50'
                                            }`}
                                        >
                                            <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${badge.bg} ${badge.color}`}>
                                                <BadgeIcon kind={item.kind} className="h-4 w-4" />
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2">
                                                    <p className="flex-1 truncate text-sm font-bold text-neutral-900">{item.title}</p>
                                                    <span className="shrink-0 text-xs text-neutral-400">{relativeTime(item.at)}</span>
                                                </div>
                                                <p className="mt-0.5 line-clamp-2 text-sm text-neutral-600">{item.body}</p>
                                            </div>
                                            {!item.read && <span className="absolute right-3 top-3 h-2 w-2 rounded-full bg-sky-500" />}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </AppShell>
    );
}
