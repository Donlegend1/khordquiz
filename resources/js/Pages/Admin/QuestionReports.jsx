import AppShell from '@/Layouts/AppShell';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const button = 'inline-flex min-h-11 items-center justify-center rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700 disabled:cursor-default disabled:opacity-40';
const formatDate = value => new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value));

export default function QuestionReports({ reports, filters, counts }) {
    const [search, setSearch] = useState(filters.search);
    const [busy, setBusy] = useState(null);
    const [notice, setNotice] = useState('');
    useEffect(() => setSearch(filters.search), [filters.search]);
    function apply(status = filters.status, term = search) {
        router.get(route('admin.question-reports.index'), { ...(status ? { status } : {}), ...(term.trim() ? { search: term.trim() } : {}) }, { preserveState: true });
    }
    function changeStatus(report) {
        setBusy(report.id);
        setNotice('');
        const status = report.status === 'open' ? 'resolved' : 'open';
        router.patch(route('admin.question-reports.update', report.id), { status }, {
            preserveScroll: true,
            onSuccess: () => setNotice(`Report #${report.id} ${status === 'resolved' ? 'resolved' : 'reopened'}.`),
            onError: () => setNotice('The report could not be updated. Please try again.'),
            onFinish: () => setBusy(null),
        });
    }
    return <AppShell eyebrow="Admin" title="Question reports" intro="Find, review and resolve problems reported by your learners.">
        <Head title="Question reports" />
        <div className="mb-6 grid gap-3 sm:grid-cols-3">{[['all', 'Total reports'], ['open', 'Needs attention'], ['resolved', 'Resolved']].map(([key, label]) => <div key={key} className={`rounded-2xl border p-5 ${key === 'open' ? 'border-amber-100 bg-amber-50' : key === 'resolved' ? 'border-emerald-100 bg-emerald-50' : 'border-neutral-200 bg-white'}`}><p className="text-sm text-neutral-600">{label}</p><p className="mt-2 text-2xl font-bold tabular-nums">{counts[key]}</p></div>)}</div>
        <section aria-label="Report filters" className="mb-5 rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
            <div className="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">
                <div className="flex flex-wrap gap-1" aria-label="Filter by status">{[['', 'All', counts.all], ['open', 'Open', counts.open], ['resolved', 'Resolved', counts.resolved]].map(([value, label, total]) => <button key={label} aria-pressed={filters.status === value} onClick={() => apply(value)} className={`min-h-11 rounded-xl px-4 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 ${filters.status === value ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:bg-neutral-100'}`}>{label} <span className="ml-1 opacity-65">{total}</span></button>)}</div>
                <form onSubmit={event => { event.preventDefault(); apply(); }} className="flex min-w-0 flex-1 gap-2 xl:max-w-lg"><label className="min-w-0 flex-1"><span className="sr-only">Search quiz, member name, email, reason or question ID</span><input maxLength={100} value={search} onChange={event => setSearch(event.target.value)} placeholder="Search quiz, member or question ID" type="search" className="min-h-11 w-full rounded-xl border-neutral-300 text-sm" /></label><button className={button}>Search</button></form>
            </div>
            {(filters.search || filters.status) && <button onClick={() => { setSearch(''); apply('', ''); }} className="mt-3 min-h-11 text-sm font-semibold text-sky-700">Clear filters</button>}
        </section>
        <div className="mb-3 flex items-center justify-between gap-3 text-xs text-neutral-500"><p>{reports.total} matching {reports.total === 1 ? 'report' : 'reports'}</p><p>Newest first</p></div>
        {notice && <p role="status" className="mb-4 rounded-xl border border-sky-200 bg-sky-50 p-3 text-sm text-sky-900">{notice}</p>}
        <div className="space-y-4">{reports.data.length === 0 && <div className="rounded-2xl border border-neutral-200 bg-white px-5 py-12 text-center"><h2 className="font-semibold">{counts.all ? 'No matching reports' : 'No reports yet'}</h2><p className="mt-2 text-sm text-neutral-500">{counts.all ? 'Try a different search or clear your filters.' : 'Problems reported by learners will appear here.'}</p></div>}{reports.data.map(report => <article key={report.id} className="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
            <div className="flex flex-wrap items-start justify-between gap-3 border-b border-neutral-100 px-5 py-4 sm:px-6"><div><div className="mb-2 flex items-center gap-2"><span className="text-xs font-medium text-neutral-500">Report #{report.id}</span><span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${report.status === 'resolved' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800'}`}>{report.status === 'resolved' ? 'Resolved' : 'Open'}</span></div><h2 className="font-bold text-neutral-900">{report.quiz_title}</h2></div><time dateTime={report.created_at} className="text-xs text-neutral-500">{formatDate(report.created_at)}</time></div>
            <div className="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_minmax(220px,.6fr)]">
                <div className="min-w-0"><p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">Reported problem</p><h3 className="mt-2 text-sm font-semibold">{report.reason}</h3><p className="mt-2 whitespace-pre-wrap break-words text-sm leading-relaxed text-neutral-600">{report.message || 'No additional message provided.'}</p><details className="mt-4 rounded-xl bg-neutral-50 p-3"><summary className="min-h-7 cursor-pointer text-sm font-semibold text-sky-700">Question #{report.question_id} · View question text</summary><p className="mt-3 whitespace-pre-wrap break-words text-sm leading-relaxed text-neutral-600">{report.question_prompt}</p></details></div>
                <div className="min-w-0 rounded-xl border border-neutral-100 p-4"><p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">Submitted by</p>{report.user ? <><Link href={route('admin.users.show', report.user.id)} className="mt-2 inline-flex min-h-8 items-center font-semibold text-neutral-900 underline-offset-4 hover:underline">{report.user.name}</Link><p className="break-all text-sm text-neutral-600">{report.user.email}</p></> : <p className="mt-2 text-sm text-neutral-500">Member no longer available</p>}<p className="mt-4 text-xs font-semibold text-neutral-500">Selected instrument</p><p className="mt-1 text-sm text-neutral-700">{report.instrument || 'Not recorded'}</p></div>
            </div>
            <div className="flex flex-wrap items-center justify-between gap-3 border-t border-neutral-100 bg-neutral-50/50 px-5 py-3 sm:px-6"><span className="text-xs text-neutral-500">{report.status === 'open' ? 'Ready for review' : 'Resolved · reopen if further investigation is needed'}</span><button disabled={busy !== null} onClick={() => changeStatus(report)} className={button}>{busy === report.id ? 'Updating…' : report.status === 'open' ? 'Mark resolved' : 'Reopen report'}</button></div>
        </article>)}</div>
        {reports.last_page > 1 && <nav aria-label="Report pages" className="mt-6"><p className="mb-3 text-center text-sm text-neutral-500">Showing {reports.from}–{reports.to} of {reports.total} reports</p><div className="flex items-center justify-between gap-2">{reports.prev_page_url ? <Link className={button} href={reports.prev_page_url}>Previous</Link> : <button disabled className={button}>Previous</button>}<span className="text-sm text-neutral-600">Page {reports.current_page} of {reports.last_page}</span>{reports.next_page_url ? <Link className={button} href={reports.next_page_url}>Next</Link> : <button disabled className={button}>Next</button>}</div></nav>}
    </AppShell>;
}
