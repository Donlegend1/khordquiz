import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

// Member sidebar, one list. Items without a `route` entry don't have a page yet —
// they render as a disabled row with a "Soon" tag instead of a 404 link. Settings and
// Support (marked `pinned`) sit apart from the rest — pushed down to sit just above
// Log out and the profile card, instead of flowing with the main list.
const memberNav = [
    { label: 'Dashboard', icon: 'dashboard', route: 'dashboard' },
    { label: 'Quiz', icon: 'quiz', route: 'quiz.categories', alsoCurrent: ['quiz.category', 'quiz.preview', 'quiz.play', 'quiz.results'] },
    { label: 'Progress', icon: 'progress', route: 'progress' },
    { label: 'Rank', icon: 'rank', route: 'leaderboard' },
    { label: 'Account', icon: 'account', route: 'account' },
    { label: 'Notifications', icon: 'notifications', route: 'notifications' },
    { label: 'Bookmarks', icon: 'bookmarks', route: 'saved' },
    { label: 'Suggestions', icon: 'suggestions', route: 'suggestions' },
    { label: 'Settings', icon: 'settings', route: 'settings', pinned: true },
    { label: 'Support', icon: 'support', route: 'support', pinned: true },
];

const adminNav = [
    { label: 'Overview', icon: 'dashboard', route: 'dashboard' },
    {
        label: 'Audio Quiz',
        icon: 'progress',
        route: 'admin.audio-quiz.index',
        alsoCurrent: ['admin.audio-quiz.show', 'admin.audio-quiz.lesson', 'admin.quizzes.index', 'admin.quizzes.edit', 'admin.quizzes.create'],
    },
];

export default function AppShell({ title, eyebrow, intro, action, children }) {
    const { auth, flash } = usePage().props;
    const user = auth.user;
    const isAdmin = user.role === 'admin';
    const [open, setOpen] = useState(false);

    // Lets the nav items animate in once, right after the shell mounts, instead of
    // appearing fully formed — a small touch that makes each page load feel considered.
    const [mounted, setMounted] = useState(false);
    useEffect(() => {
        const frame = requestAnimationFrame(() => setMounted(true));
        return () => cancelAnimationFrame(frame);
    }, []);

    const items = (isAdmin ? adminNav : memberNav).map((item, index) => ({
        ...item,
        href: item.route ? route(item.route) : null,
        current: item.route
            ? route().current(item.route) || (item.alsoCurrent ?? []).some((name) => route().current(name))
            : false,
        index,
    }));
    const topItems = items.filter((item) => !item.pinned);
    const pinnedItems = items.filter((item) => item.pinned);

    const close = () => setOpen(false);

    return (
        <div className="min-h-screen bg-[#F6F1EC] text-neutral-900">
            {open && (
                <button
                    type="button"
                    aria-label="Close menu"
                    className="fixed inset-0 z-30 bg-neutral-900/40 backdrop-blur-[2px] lg:hidden"
                    onClick={close}
                />
            )}

            <aside
                className={`fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-black/5 bg-white transition-transform duration-300 ease-out lg:translate-x-0 ${
                    open ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-16 shrink-0 items-center border-b border-black/5 px-5">
                    <Link href={route('dashboard')} aria-label="Dashboard" className="flex items-baseline text-[21px] leading-none tracking-tight" onClick={close}>
                        <span className="font-sans font-bold text-black">Khord</span>
                        <span className="font-serif font-semibold text-brand">Quiz</span>
                    </Link>
                </div>

                <nav className="flex flex-1 flex-col overflow-y-auto px-3 py-3" aria-label="Account">
                    <div className="space-y-1">
                        {topItems.map((item) => (
                            <NavRow key={item.label} item={item} mounted={mounted} onNavigate={close} />
                        ))}
                    </div>

                    {/* Settings, Support and Log out stay pinned near the profile card below,
                        however little or much space the main list above takes up. */}
                    <div className="mt-auto space-y-1 pt-3">
                        <div className="mb-1 border-t border-black/5" />
                        {pinnedItems.map((item) => (
                            <NavRow key={item.label} item={item} mounted={mounted} onNavigate={close} />
                        ))}

                        <div className="my-1 border-t border-black/5" />

                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            onClick={close}
                            style={{ transitionDelay: mounted ? `${items.length * 35}ms` : '0ms' }}
                            className={`group flex w-full items-center gap-3 rounded-xl border border-transparent px-2.5 py-2 text-left text-sm font-semibold text-brand transition-all duration-200 ease-out hover:bg-neutral-50 ${
                                mounted ? 'translate-x-0 opacity-100' : '-translate-x-1.5 opacity-0'
                            }`}
                        >
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand text-white shadow-sm transition-all duration-200 group-hover:bg-brand-dark">
                                <NavIcon name="logout" />
                            </span>
                            Log out
                        </Link>
                    </div>
                </nav>

                <Link
                    href={route('profile.edit')}
                    onClick={close}
                    className="group shrink-0 border-t border-black/5 p-3"
                >
                    <div className="flex items-center gap-3 rounded-xl p-1.5 transition-colors duration-200 group-hover:bg-neutral-50">
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand/10 text-xs font-semibold text-brand ring-2 ring-white transition-transform duration-200 group-hover:scale-105">
                            {initials(user.name)}
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-semibold text-neutral-900">{user.name}</p>
                            <p className="truncate text-xs text-neutral-500">{user.email}</p>
                        </div>
                        <svg
                            className="h-4 w-4 shrink-0 text-neutral-300 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-neutral-500"
                            viewBox="0 0 20 20"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            aria-hidden="true"
                        >
                            <path d="M7.5 5l5 5-5 5" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </div>
                </Link>
            </aside>

            <div className="lg:pl-72">
                <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-black/5 bg-[#F6F1EC]/90 px-4 backdrop-blur sm:px-8 lg:hidden">
                    <button
                        type="button"
                        className="rounded-md px-2 py-1 text-sm font-medium text-neutral-700 transition-colors duration-200 hover:bg-black/5"
                        onClick={() => setOpen(true)}
                    >
                        Menu
                    </button>
                    <Link href={route('dashboard')} aria-label="Dashboard" className="flex items-baseline text-lg leading-none tracking-tight">
                        <span className="font-bold text-black">Khord</span>
                        <span className="font-serif font-semibold text-brand">Quiz</span>
                    </Link>
                    <span className="w-12" />
                </header>

                <main className="px-4 py-6 sm:px-8 sm:py-8">
                    {flash?.success && (
                        <div className="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                            {flash.success}
                        </div>
                    )}

                    <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            {eyebrow && (
                                <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand">{eyebrow}</p>
                            )}
                            <h1 className="mt-1 text-3xl font-bold tracking-tight text-neutral-900">{title}</h1>
                            {intro && <p className="mt-1 max-w-2xl text-[15px] text-neutral-600">{intro}</p>}
                        </div>
                        {action}
                    </div>

                    {children}
                </main>
            </div>
        </div>
    );
}

function NavRow({ item, mounted, onNavigate }) {
    const delay = mounted ? `${item.index * 35}ms` : '0ms';

    if (!item.href) {
        return (
            <span
                aria-disabled="true"
                title="Coming soon"
                style={{ transitionDelay: delay }}
                className={`flex cursor-default items-center gap-3 rounded-xl border border-transparent px-2.5 py-2 text-sm font-medium text-neutral-400 transition-all duration-200 ease-out ${
                    mounted ? 'translate-x-0 opacity-100' : '-translate-x-1.5 opacity-0'
                }`}
            >
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-neutral-50 text-neutral-300">
                    <NavIcon name={item.icon} />
                </span>
                <span className="flex-1">{item.label}</span>
                <span className="rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-neutral-400">
                    Soon
                </span>
            </span>
        );
    }

    return (
        <Link
            href={item.href}
            onClick={onNavigate}
            style={{ transitionDelay: delay }}
            className={`group flex items-center gap-3 rounded-xl border px-2.5 py-2 text-sm font-medium transition-all duration-200 ease-out ${
                mounted ? 'translate-x-0 opacity-100' : '-translate-x-1.5 opacity-0'
            } ${
                item.current
                    ? 'border-brand/15 bg-brand/[0.06] text-brand shadow-[0_1px_3px_rgba(17,24,39,0.06)]'
                    : 'border-transparent text-neutral-600 hover:border-black/5 hover:bg-neutral-50 hover:text-neutral-900'
            }`}
        >
            <span
                className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-all duration-200 ${
                    item.current
                        ? 'bg-white text-brand shadow-sm ring-1 ring-brand/10'
                        : 'bg-neutral-100 text-neutral-500 group-hover:bg-white group-hover:text-neutral-700 group-hover:shadow-sm'
                }`}
            >
                <NavIcon name={item.icon} />
            </span>
            {item.label}
        </Link>
    );
}

const iconProps = {
    className: 'h-[18px] w-[18px] shrink-0',
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 1.8,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    'aria-hidden': true,
};

function NavIcon({ name }) {
    switch (name) {
        case 'dashboard':
            return (
                <svg {...iconProps}>
                    <rect x="3.5" y="3.5" width="7.5" height="7.5" rx="1.5" />
                    <rect x="13" y="3.5" width="7.5" height="4.5" rx="1.5" />
                    <rect x="13" y="10.5" width="7.5" height="10" rx="1.5" />
                    <rect x="3.5" y="13.5" width="7.5" height="7" rx="1.5" />
                </svg>
            );
        case 'quiz':
            return (
                <svg {...iconProps}>
                    <circle cx="12" cy="12" r="8.5" />
                    <path d="M10 9.3l5.5 2.7-5.5 2.7z" fill="currentColor" stroke="none" />
                </svg>
            );
        case 'progress':
            return (
                <svg {...iconProps}>
                    <path d="M4 20V11M12 20V4M20 20v-7" />
                </svg>
            );
        case 'rank':
            return (
                <svg {...iconProps}>
                    <path d="M8 4h8v5a4 4 0 01-8 0V4z" />
                    <path d="M8 5H5a3 3 0 003 3M16 5h3a3 3 0 01-3 3" />
                    <path d="M12 13v3M9 20h6M10 20v-2.5h4V20" />
                </svg>
            );
        case 'account':
            return (
                <svg {...iconProps}>
                    <circle cx="12" cy="8" r="3.5" />
                    <path d="M4.5 20c1.3-3.5 4.2-5.5 7.5-5.5s6.2 2 7.5 5.5" />
                </svg>
            );
        case 'settings':
            return (
                <svg {...iconProps}>
                    <circle cx="12" cy="12" r="3" />
                    <path d="M19.4 13.5a7.6 7.6 0 000-3l2-1.4-2-3.4-2.3.8a7.7 7.7 0 00-2.6-1.5L14 2.5h-4l-.5 2.5a7.7 7.7 0 00-2.6 1.5l-2.3-.8-2 3.4 2 1.4a7.6 7.6 0 000 3l-2 1.4 2 3.4 2.3-.8a7.7 7.7 0 002.6 1.5l.5 2.5h4l.5-2.5a7.7 7.7 0 002.6-1.5l2.3.8 2-3.4z" />
                </svg>
            );
        case 'notifications':
            return (
                <svg {...iconProps}>
                    <path d="M6 9a6 6 0 1112 0c0 4.2 1.3 5.6 1.5 6H4.5c.2-.4 1.5-1.8 1.5-6z" />
                    <path d="M10.3 19a1.8 1.8 0 003.4 0" />
                </svg>
            );
        case 'bookmarks':
            return (
                <svg {...iconProps}>
                    <path d="M6 3.5h12v17l-6-4-6 4v-17z" />
                </svg>
            );
        case 'suggestions':
            return (
                <svg {...iconProps}>
                    <path d="M9 18h6M10 21h4M8 11a4 4 0 118 0c0 1.8-1 2.6-2 3.5-.6.6-1 1.1-1 1.5h-2c0-.4-.4-.9-1-1.5-1-.9-2-1.7-2-3.5z" />
                    <path d="M12 2v1.5M4 11h1.5M18.5 11H20M6 5.5l1 1M18 5.5l-1 1" />
                </svg>
            );
        case 'support':
            return (
                <svg {...iconProps}>
                    <circle cx="12" cy="12" r="9" />
                    <circle cx="12" cy="12" r="3.5" />
                    <path d="M5.5 5.5l3.2 3.2M18.5 5.5l-3.2 3.2M5.5 18.5l3.2-3.2M18.5 18.5l-3.2-3.2" />
                </svg>
            );
        case 'logout':
            return (
                <svg {...iconProps}>
                    <path d="M15 17l5-5-5-5M20 12H9" />
                    <path d="M13 4H6a2 2 0 00-2 2v12a2 2 0 002 2h7" />
                </svg>
            );
        default:
            return null;
    }
}
