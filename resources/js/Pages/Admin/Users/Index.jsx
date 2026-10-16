import AppShell from '@/Layouts/AppShell';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function Stat({ label, value }) {
    return (
        <div className="rounded-2xl border border-black/5 bg-white px-5 py-4">
            <p className="text-sm text-neutral-500">{label}</p>
            <p className="mt-2 text-3xl font-bold tracking-tight">{value}</p>
        </div>
    );
}

function FilterChip({ active, onClick, children }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors duration-150 ${
                active ? 'bg-neutral-900 text-white' : 'bg-white text-neutral-600 hover:bg-neutral-100'
            }`}
        >
            {children}
        </button>
    );
}

export default function Index({ users, filters, stats }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const firstRender = useRef(true);

    // Debounced live search — pushes to the server as the admin types, without
    // losing the current role/status filters or reloading the full page shell.
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        const timeout = setTimeout(() => {
            router.get(route('admin.users.index'), { ...filters, search }, { preserveState: true, replace: true });
        }, 300);
        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const setFilter = (key, value) => {
        router.get(route('admin.users.index'), { ...filters, [key]: value === filters[key] ? '' : value }, { preserveState: true });
    };

    return (
        <AppShell eyebrow="Admin" title="Users" intro="Everyone signed up for KhordQuiz — manage roles and account access.">
            <Head title="Users" />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Stat label="Total members" value={stats.total} />
                <Stat label="Admins" value={stats.admins} />
                <Stat label="Suspended" value={stats.suspended} />
                <Stat label="New this week" value={stats.newThisWeek} />
            </div>

            <section className="mt-8">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by name or email"
                        className="w-full max-w-xs rounded-lg border border-neutral-300 px-3.5 py-2 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        <FilterChip active={filters.plan === 'inactive'} onClick={() => setFilter('plan', 'inactive')}>
                            Inactive
                        </FilterChip>
                        <FilterChip active={filters.role === 'member'} onClick={() => setFilter('role', 'member')}>
                            Members
                        </FilterChip>
                        <span className="h-4 w-px bg-black/10" />
                        <FilterChip active={filters.status === 'active'} onClick={() => setFilter('status', 'active')}>
                            Active
                        </FilterChip>
                        <FilterChip active={filters.status === 'suspended'} onClick={() => setFilter('status', 'suspended')}>
                            Suspended
                        </FilterChip>
                    </div>
                </div>

                <div className="mt-4 overflow-hidden rounded-2xl border border-black/5 bg-white">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <thead className="border-b border-black/5 text-xs uppercase tracking-wide text-neutral-500">
                                <tr>
                                    <th className="px-5 py-3 font-medium">Member</th>
                                    <th className="px-5 py-3 font-medium">Role</th>
                                    <th className="px-5 py-3 font-medium">Streak</th>
                                    <th className="px-5 py-3 font-medium">Quizzes</th>
                                    <th className="px-5 py-3 font-medium">Joined</th>
                                    <th className="px-5 py-3 font-medium">Status</th>
                                    <th className="px-5 py-3 font-medium">
                                        <span className="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-black/5">
                                {users.data.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="px-5 py-10 text-center text-neutral-500">
                                            No members match these filters.
                                        </td>
                                    </tr>
                                )}
                                {users.data.map((user) => (
                                    <tr key={user.id}>
                                        <td className="px-5 py-4">
                                            <Link href={route('admin.users.show', user.id)} className="flex min-w-0 items-center gap-3 hover:opacity-80">
                                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand/10 text-xs font-semibold text-brand">
                                                    {initials(user.name)}
                                                </span>
                                                <span className="min-w-0">
                                                    <p className="truncate font-medium text-neutral-900">{user.name}</p>
                                                    <p className="truncate text-xs text-neutral-500">{user.email}</p>
                                                </span>
                                            </Link>
                                        </td>
                                        <td className="px-5 py-4">
                                            <span
                                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                                                    user.role === 'admin' ? 'bg-brand/10 text-brand' : 'bg-neutral-100 text-neutral-600'
                                                }`}
                                            >
                                                {user.role === 'admin' ? 'Admin' : 'Member'}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-neutral-600">
                                            {user.current_streak > 0 ? `${user.current_streak} day${user.current_streak === 1 ? '' : 's'}` : '—'}
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-neutral-600">
                                            {user.quizzes_completed}/{user.quizzes_attempted} completed
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-neutral-600">{user.joined_ago}</td>
                                        <td className="px-5 py-4">
                                            <span className={user.suspended ? 'font-medium text-brand' : 'text-emerald-700'}>
                                                {user.suspended ? 'Suspended' : 'Active'}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-right">
                                            <Link href={route('admin.users.show', user.id)} className="font-medium text-neutral-700 hover:text-neutral-900">
                                                Manage
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {users.last_page > 1 && (
                    <nav className="mt-4 flex flex-wrap gap-2" aria-label="Pagination">
                        {users.links.map((link, index) =>
                            link.url ? (
                                <Link
                                    key={index}
                                    href={link.url}
                                    preserveState
                                    className={`rounded-md px-3 py-1.5 text-sm ${
                                        link.active ? 'bg-brand text-white' : 'bg-white text-neutral-700 hover:bg-neutral-100'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ) : (
                                <span
                                    key={index}
                                    className="rounded-md px-3 py-1.5 text-sm text-neutral-400"
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ),
                        )}
                    </nav>
                )}
            </section>
        </AppShell>
    );
}
