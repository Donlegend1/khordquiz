import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

export default function AppShell({ title, eyebrow, intro, action, children }) {
    const { auth, flash } = usePage().props;
    const user = auth.user;
    const isAdmin = user.role === 'admin';
    const [open, setOpen] = useState(false);

    const items = isAdmin
        ? [
              {
                  label: 'Overview',
                  href: route('dashboard'),
                  current: route().current('dashboard'),
              },
              {
                  label: 'Quiz list',
                  href: route('admin.quizzes.index'),
                  current: route().current('admin.quizzes.index') || route().current('admin.quizzes.edit'),
              },
          ]
        : [
              {
                  label: 'My training',
                  href: route('dashboard'),
                  current: route().current('dashboard'),
              },
          ];

    const close = () => setOpen(false);

    return (
        <div className="min-h-screen bg-[#F6F1EC] text-neutral-900">
            {open && (
                <button
                    type="button"
                    aria-label="Close menu"
                    className="fixed inset-0 z-30 bg-neutral-900/40 lg:hidden"
                    onClick={close}
                />
            )}

            <aside
                className={`fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-black/5 bg-white transition-transform lg:translate-x-0 ${
                    open ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-16 items-center px-5">
                    <Link href={route('dashboard')} className="text-[22px] leading-none tracking-tight" onClick={close}>
                        <span className="font-sans font-bold text-black">Khord</span>
                        <span className="font-serif font-semibold text-brand">Quiz</span>
                    </Link>
                </div>

                <nav className="flex-1 space-y-1 px-3" aria-label="Account">
                    <p className="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                        {isAdmin ? 'Admin' : 'Member'}
                    </p>
                    {items.map((item) => (
                        <Link
                            key={item.label}
                            href={item.href}
                            onClick={close}
                            className={`block rounded-lg px-3 py-2.5 text-sm font-medium ${
                                item.current
                                    ? 'bg-brand/10 text-brand'
                                    : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900'
                            }`}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                <div className="border-t border-black/5 p-4">
                    <div className="flex items-center gap-3">
                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand/10 text-xs font-semibold text-brand">
                            {initials(user.name)}
                        </span>
                        <div className="min-w-0">
                            <p className="truncate text-sm font-medium">{user.name}</p>
                            <p className="truncate text-xs text-neutral-500">{user.email}</p>
                        </div>
                    </div>
                    <div className="mt-3 flex gap-3 text-sm">
                        <Link href={route('profile.edit')} className="font-medium text-neutral-600 hover:text-neutral-900" onClick={close}>
                            Profile
                        </Link>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="font-medium text-neutral-600 hover:text-neutral-900"
                        >
                            Log out
                        </Link>
                    </div>
                </div>
            </aside>

            <div className="lg:pl-64">
                <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-black/5 bg-[#F6F1EC]/90 px-4 backdrop-blur sm:px-8 lg:hidden">
                    <button
                        type="button"
                        className="rounded-md px-2 py-1 text-sm font-medium text-neutral-700"
                        onClick={() => setOpen(true)}
                    >
                        Menu
                    </button>
                    <Link href={route('dashboard')} className="text-lg tracking-tight">
                        <span className="font-bold">Khord</span>
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
