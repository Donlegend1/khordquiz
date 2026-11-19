import AppShell from '@/Layouts/AppShell';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { tk, useTrans } from '@/lib/i18n';

const HOUR = 60 * 60 * 1000;
const DAY = 24 * HOUR;

const BADGES = {
    challenge: { bg: 'bg-rose-50', color: 'text-brand' },
    'friend-perfect': { bg: 'bg-violet-50', color: 'text-violet-600' },
    general: { bg: 'bg-neutral-100', color: 'text-neutral-500' },
    payment: { bg: 'bg-sky-50', color: 'text-sky-600' },
    perfect: { bg: 'bg-amber-50', color: 'text-amber-700' },
    reminder: { bg: 'bg-emerald-50', color: 'text-emerald-700' },
    support: { bg: 'bg-sky-50', color: 'text-sky-600' },
    streak: { bg: 'bg-orange-50', color: 'text-amber-700' },
};

const BUCKETS = [tk('Today'), tk('Yesterday'), tk('This Week'), tk('Earlier')];
function bucketFor(at) {
    const diff = Date.now() - at;
    if (diff < DAY) return 'Today';
    if (diff < 2 * DAY) return 'Yesterday';
    if (diff < 7 * DAY) return 'This Week';
    return 'Earlier';
}

function relativeTime(at, t) {
    const diff = Date.now() - at;
    if (diff < 60_000) return t('Just now');
    if (diff < HOUR) return t(':countm ago', { count: Math.floor(diff / 60_000) });
    if (diff < DAY) return t(':counth ago', { count: Math.floor(diff / HOUR) });
    return t(':countd ago', { count: Math.floor(diff / DAY) });
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
        case 'support':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className={className}>
                    <path d="M4 5.5h16v10H9l-5 4z" />
                </svg>
            );
        case 'reminder':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className={className}>
                    <circle cx="12" cy="12" r="8.5" />
                    <path d="M12 7.5V12l3 2" />
                </svg>
            );
        case 'streak':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className={className}>
                    <path d="M12 2.5c1 3 .5 4.5-1 6-2 2-3 3.8-3 6a4 4 0 008 0c0-1.3-.5-2-1-2.8.9.4 2 1.6 2 3.8a5 5 0 01-10 0c0-4.2 3-6 4-8.2.6-1.3.7-2.8 1-4.8z" />
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

// The server sends ISO timestamps; the helpers above work in milliseconds.
function withTimestamps(items) {
    return items.map((item) => ({ ...item, at: Date.parse(item.at) }));
}

export default function Notifications({ notifications: initialNotifications }) {
    const t = useTrans();
    const [notifications, setNotifications] = useState(() => withTimestamps(initialNotifications));

    useEffect(() => setNotifications(withTimestamps(initialNotifications)), [initialNotifications]);
    const hasUnread = notifications.some((item) => !item.read);

    const groups = useMemo(
        () =>
            BUCKETS.map((bucket) => ({
                bucket,
                items: notifications.filter((item) => bucketFor(item.at) === bucket),
            })).filter((group) => group.items.length > 0),
        [notifications],
    );

    // Updated on screen straight away; the server request just persists it.
    function markAllRead() {
        setNotifications((current) => current.map((item) => (item.read ? item : { ...item, read: true })));
        router.post(route('notifications.read-all'), {}, { preserveScroll: true, preserveState: true });
    }

    // Notifications about something specific (e.g. a support reply) open it.
    function open(item) {
        if (!item.read) {
            setNotifications((current) => current.map((entry) => (entry.id === item.id ? { ...entry, read: true } : entry)));
            router.patch(route('notifications.read', item.id), {}, {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => item.url && router.visit(item.url),
            });
            return;
        }

        if (item.url) {
            router.visit(item.url);
        }
    }

    return (
        <AppShell
            eyebrow={t('Member')}
            title={t('Notifications')}
            intro={t('Perfect scores, challenge results and other updates.')}
            action={
                hasUnread && (
                    <button type="button" onClick={markAllRead} className="text-sm font-semibold text-brand hover:text-brand-dark">
                        {t('Mark all read')}
                    </button>
                )
            }
        >
            <Head title={t('Notifications')} />

            {groups.length === 0 ? (
                <div className="flex flex-col items-center rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-16 text-center">
                    <span className="flex h-16 w-16 items-center justify-center rounded-full bg-rose-50 text-brand">
                        <BellOffIcon className="h-7 w-7" />
                    </span>
                    <p className="mt-4 text-base font-semibold text-neutral-900">{t('No notifications yet')}</p>
                    <p className="mt-1 max-w-xs text-sm text-neutral-500">
                        {t("Perfect scores, challenge results and other updates will show up here once you've got some.")}
                    </p>
                </div>
            ) : (
                <div className="space-y-6">
                    {groups.map((group) => (
                        <div key={group.bucket}>
                            <p className="mb-2 px-1 text-xs font-bold uppercase tracking-wide text-neutral-400">{t(group.bucket)}</p>
                            <div className="space-y-2.5">
                                {group.items.map((item) => {
                                    const badge = BADGES[item.kind] ?? BADGES.general;
                                    return (
                                        <button
                                            key={item.id}
                                            type="button"
                                            onClick={() => open(item)}
                                            className={`relative flex w-full items-start gap-3 rounded-2xl border px-4 py-3.5 text-left transition-colors duration-150 ${
                                                item.read ? 'border-black/5 bg-white hover:bg-neutral-50' : 'border-rose-100 bg-rose-50/60 hover:bg-rose-50'
                                            }`}
                                        >
                                            <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${badge.bg} ${badge.color}`}>
                                                <BadgeIcon kind={item.kind} className="h-4 w-4" />
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2">
                                                    <p className="flex-1 truncate text-sm font-bold text-neutral-900">{t(item.title, item.params)}</p>
                                                    <span className="shrink-0 text-xs text-neutral-400">{relativeTime(item.at, t)}</span>
                                                </div>
                                                <p className="mt-0.5 line-clamp-2 text-sm text-neutral-600">{t(item.body, item.params)}</p>
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
