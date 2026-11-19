import AppShell from '@/Layouts/AppShell';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const button = 'inline-flex min-h-11 items-center justify-center rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700 disabled:cursor-default disabled:opacity-40';
const formatDate = (value) => new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value));

export const STATUS = {
    open: { label: 'Needs reply', className: 'bg-amber-50 text-amber-800' },
    answered: { label: 'Answered', className: 'bg-sky-50 text-sky-800' },
    resolved: { label: 'Resolved', className: 'bg-emerald-50 text-emerald-800' },
};

const FILTERS = [
    ['', 'All', 'all'],
    ['open', 'Needs reply', 'open'],
    ['answered', 'Answered', 'answered'],
    ['resolved', 'Resolved', 'resolved'],
];

export default function SupportIndex({ requests, filters, counts }) {
    const [search, setSearch] = useState(filters.search);
    useEffect(() => setSearch(filters.search), [filters.search]);

    function apply(status = filters.status, term = search) {
        router.get(route('admin.support.index'), { ...(status ? { status } : {}), ...(term.trim() ? { search: term.trim() } : {}) }, { preserveState: true });
    }

    return (
        <AppShell eyebrow="Admin" title="Support inbox" intro="Requests from the website and the mobile app. Reply here so members see the answer in their thread.">
            <Head title="Support inbox" />

            <div className="mb-6 grid gap-3 sm:grid-cols-3">
                <div className="rounded-2xl border border-amber-100 bg-amber-50 p-5">
                    <p className="text-sm text-neutral-600">Needs reply</p>
                    <p className="mt-2 text-2xl font-bold tabular-nums">{counts.open}</p>
                </div>
                <div className="rounded-2xl border border-sky-100 bg-sky-50 p-5">
                    <p className="text-sm text-neutral-600">Waiting on member</p>
                    <p className="mt-2 text-2xl font-bold tabular-nums">{counts.answered}</p>
                </div>
                <div className="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
                    <p className="text-sm text-neutral-600">Resolved</p>
                    <p className="mt-2 text-2xl font-bold tabular-nums">{counts.resolved}</p>
                </div>
            </div>

            <section aria-label="Request filters" className="mb-5 rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                <div className="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">
                    <div className="flex flex-wrap gap-1" aria-label="Filter by status">
                        {FILTERS.map(([value, label, key]) => (
                            <button
                                key={label}
                                type="button"
                                aria-pressed={filters.status === value}
                                onClick={() => apply(value)}
                                className={`min-h-11 rounded-xl px-4 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 ${filters.status === value ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:bg-neutral-100'}`}
                            >
                                {label} <span className="ml-1 opacity-65">{counts[key]}</span>
                            </button>
                        ))}
                    </div>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            apply();
                        }}
                        className="flex min-w-0 flex-1 gap-2 xl:max-w-lg"
                    >
                        <label className="min-w-0 flex-1">
                            <span className="sr-only">Search subject, message, member or request number</span>
                            <input maxLength={100} value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search subject, member or #" type="search" className="min-h-11 w-full rounded-xl border-neutral-300 text-sm" />
                        </label>
                        <button className={button}>Search</button>
                    </form>
                </div>
                {(filters.search || filters.status) && (
                    <button
                        type="button"
                        onClick={() => {
                            setSearch('');
                            apply('', '');
                        }}
                        className="mt-3 min-h-11 text-sm font-semibold text-sky-700"
                    >
                        Clear filters
                    </button>
                )}
            </section>

            <div className="mb-3 flex items-center justify-between gap-3 text-xs text-neutral-500">
                <p>
                    {requests.total} matching {requests.total === 1 ? 'request' : 'requests'}
                </p>
                <p>Latest activity first</p>
            </div>

            {requests.data.length === 0 ? (
                <div className="rounded-2xl border border-neutral-200 bg-white px-5 py-12 text-center">
                    <h2 className="font-semibold">{counts.all ? 'No matching requests' : 'No support requests yet'}</h2>
                    <p className="mt-2 text-sm text-neutral-500">{counts.all ? 'Try a different search or clear your filters.' : 'Messages members send from Help & Support will appear here.'}</p>
                </div>
            ) : (
                <ul className="divide-y divide-neutral-100 overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                    {requests.data.map((request) => {
                        const status = STATUS[request.status] ?? STATUS.open;
                        return (
                            <li key={request.id}>
                                <Link href={route('admin.support.show', request.id)} className="flex flex-col gap-2 px-5 py-4 hover:bg-neutral-50 sm:flex-row sm:items-center sm:gap-4">
                                    <div className="min-w-0 flex-1">
                                        <div className="mb-1 flex flex-wrap items-center gap-2">
                                            <span className="text-xs font-medium text-neutral-500">#{request.id}</span>
                                            <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${status.className}`}>{status.label}</span>
                                            <span className="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-600">{request.topic}</span>
                                            <span className="text-xs text-neutral-400">{request.source === 'web' ? 'Website' : 'App'}</span>
                                        </div>
                                        <p className="truncate font-semibold text-neutral-900">{request.title}</p>
                                        <p className="truncate text-sm text-neutral-500">{request.preview}</p>
                                    </div>
                                    <div className="shrink-0 text-left text-xs text-neutral-500 sm:text-right">
                                        <p className="font-semibold text-neutral-700">{request.member?.name ?? 'Deleted member'}</p>
                                        <p>{formatDate(request.last_activity_at)}</p>
                                        <p>
                                            {request.replies_count} {request.replies_count === 1 ? 'reply' : 'replies'}
                                        </p>
                                    </div>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            )}

            {requests.last_page > 1 && (
                <nav aria-label="Request pages" className="mt-6 flex items-center justify-between gap-2">
                    {requests.prev_page_url ? <Link className={button} href={requests.prev_page_url}>Previous</Link> : <button disabled className={button}>Previous</button>}
                    <span className="text-sm text-neutral-600">
                        Page {requests.current_page} of {requests.last_page}
                    </span>
                    {requests.next_page_url ? <Link className={button} href={requests.next_page_url}>Next</Link> : <button disabled className={button}>Next</button>}
                </nav>
            )}
        </AppShell>
    );
}
