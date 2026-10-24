import AppShell from '@/Layouts/AppShell';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

// "No plan" rather than "Free trial" for a null plan_choice — there's no trial-tracking
// field anywhere in the schema, so that would be a guess, not a fact (see the Filters
// dropdown's own "Free trials" option, which has the same honest caveat).
const PLAN_LABELS = { monthly: 'Monthly', yearly: 'Yearly', lifetime: 'Lifetime' };
const PAYMENT_LABELS = { stripe: 'Stripe', paypal: 'PayPal' };

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

function ChevronDownIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 9l6 6 6-6" />
        </svg>
    );
}

function FilterOption({ active, onClick, children }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors duration-150 ${
                active ? 'bg-brand/10 text-brand' : 'text-neutral-700 hover:bg-neutral-50'
            }`}
        >
            {children}
        </button>
    );
}

function FilterGroup({ label, children }) {
    return (
        <div className="px-2 py-2">
            <p className="px-1 pb-1 text-[11px] font-semibold uppercase tracking-wide text-neutral-400">{label}</p>
            <div className="flex flex-col">{children}</div>
        </div>
    );
}

// A single "Filters" dropdown covering account status, plan, payment method and sort —
// Free trials and In-app payment are included for forward compatibility even though
// nothing in the schema can populate them yet (no trial field, and the mobile app isn't
// connected to this database), so they'll always show 0 members until that data exists.
function FiltersDropdown({ filters, setFilter, setSort }) {
    const [open, setOpen] = useState(false);
    const containerRef = useRef(null);

    useEffect(() => {
        const onClickOutside = (event) => {
            if (containerRef.current && !containerRef.current.contains(event.target)) setOpen(false);
        };
        document.addEventListener('mousedown', onClickOutside);
        return () => document.removeEventListener('mousedown', onClickOutside);
    }, []);

    const activeCount = [filters.status, filters.plan, filters.payment].filter(Boolean).length;

    return (
        <div ref={containerRef} className="relative">
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-neutral-700 shadow-sm transition-colors duration-150 hover:bg-neutral-50"
            >
                Filters
                {activeCount > 0 && (
                    <span className="flex h-5 min-w-[20px] items-center justify-center rounded-full bg-brand px-1 text-xs font-bold text-white">
                        {activeCount}
                    </span>
                )}
                <ChevronDownIcon className={`h-4 w-4 text-neutral-400 transition-transform duration-150 ${open ? 'rotate-180' : ''}`} />
            </button>

            {open && (
                <div className="absolute right-0 z-20 mt-2 w-64 rounded-2xl border border-black/5 bg-white py-1 shadow-lg">
                    <FilterGroup label="Account">
                        <FilterOption active={filters.status === 'active'} onClick={() => setFilter('status', 'active')}>
                            Active
                        </FilterOption>
                        <FilterOption active={filters.status === 'suspended'} onClick={() => setFilter('status', 'suspended')}>
                            Suspended
                        </FilterOption>
                        <FilterOption active={filters.plan === 'inactive'} onClick={() => setFilter('plan', 'inactive')}>
                            Inactive
                        </FilterOption>
                        <FilterOption active={filters.status === 'canceled'} onClick={() => setFilter('status', 'canceled')}>
                            Canceled
                        </FilterOption>
                    </FilterGroup>
                    <div className="mx-2 border-t border-black/5" />
                    <FilterGroup label="Plan">
                        <FilterOption active={filters.plan === 'monthly'} onClick={() => setFilter('plan', 'monthly')}>
                            Monthly sub
                        </FilterOption>
                        <FilterOption active={filters.plan === 'yearly'} onClick={() => setFilter('plan', 'yearly')}>
                            Yearly sub
                        </FilterOption>
                        <FilterOption active={filters.plan === 'lifetime'} onClick={() => setFilter('plan', 'lifetime')}>
                            Lifetime sub
                        </FilterOption>
                        <FilterOption active={filters.plan === 'trial'} onClick={() => setFilter('plan', 'trial')}>
                            Free trials
                        </FilterOption>
                    </FilterGroup>
                    <div className="mx-2 border-t border-black/5" />
                    <FilterGroup label="Payment method">
                        <FilterOption active={filters.payment === 'stripe'} onClick={() => setFilter('payment', 'stripe')}>
                            Stripe
                        </FilterOption>
                        <FilterOption active={filters.payment === 'paypal'} onClick={() => setFilter('payment', 'paypal')}>
                            PayPal
                        </FilterOption>
                        <FilterOption active={filters.payment === 'in-app'} onClick={() => setFilter('payment', 'in-app')}>
                            In-app
                        </FilterOption>
                    </FilterGroup>
                    <div className="mx-2 border-t border-black/5" />
                    <FilterGroup label="Sort">
                        <FilterOption active={filters.sort !== 'oldest'} onClick={() => setSort('latest')}>
                            Latest
                        </FilterOption>
                        <FilterOption active={filters.sort === 'oldest'} onClick={() => setSort('oldest')}>
                            Oldest
                        </FilterOption>
                    </FilterGroup>
                </div>
            )}
        </div>
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
                <Stat label="Active members" value={stats.activeMembers} />
                <Stat label="Inactive members" value={stats.inactiveMembers} />
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
                    <FiltersDropdown
                        filters={filters}
                        setFilter={setFilter}
                        setSort={(value) => router.get(route('admin.users.index'), { ...filters, sort: value }, { preserveState: true })}
                    />
                </div>

                <div className="mt-4 overflow-hidden rounded-2xl border border-black/5 bg-white">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <thead className="border-b border-black/5 text-xs uppercase tracking-wide text-neutral-500">
                                <tr>
                                    <th className="px-5 py-3 font-medium">Member</th>
                                    <th className="px-5 py-3 font-medium">Plan</th>
                                    <th className="px-5 py-3 font-medium">Payment</th>
                                    <th className="px-5 py-3 font-medium">Country</th>
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
                                                    user.plan_choice ? 'bg-brand/10 text-brand' : 'bg-neutral-100 text-neutral-500'
                                                }`}
                                            >
                                                {PLAN_LABELS[user.plan_choice] ?? 'No plan'}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-neutral-600">
                                            {PAYMENT_LABELS[user.payment_method] ?? '—'}
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-neutral-600">{user.country || '—'}</td>
                                        <td className="whitespace-nowrap px-5 py-4 text-neutral-600">{user.joined_at}</td>
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
