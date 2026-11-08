import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';
import { scoreTone } from '@/lib/scoreColor';

const dateLabel = value => new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value));
const pageButton = 'inline-flex min-h-11 items-center justify-center rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700';

export default function AttemptHistory({ history }) {
    return <AppShell title="Attempt history" eyebrow="Your progress" intro="See your scores and review your previous answers.">
        <Head title="Attempt history" />
        <div className="mx-auto max-w-5xl">
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link href={route('progress')} className={pageButton}>Back to Progress</Link>
            </div>
            <section aria-label="Completed attempts" className="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                <div className="flex items-center justify-between gap-4 border-b border-neutral-100 px-5 py-4 sm:px-6"><h2 className="text-sm font-semibold text-neutral-700">{history.total} completed {history.total === 1 ? 'attempt' : 'attempts'}</h2><span className="text-xs text-neutral-500">Newest first</span></div>
                <div className="divide-y divide-neutral-100 px-5 sm:px-6">
                    {history.data.length === 0 && <p className="py-10 text-center text-neutral-600">Your completed quizzes will appear here.</p>}
                    {history.data.map(item => <article key={item.id} className="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-x-4 gap-y-4 py-5 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:gap-x-6 sm:py-6">
                        <div className="min-w-0"><h3 className="text-base font-semibold leading-snug text-neutral-900">{item.title}</h3><p className="mt-1 text-sm text-neutral-600">{item.category}</p><div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2"><time dateTime={item.date} className="text-xs text-neutral-500">{dateLabel(item.date)}</time>{item.sound && <span className="rounded-md bg-neutral-100 px-2 py-1 text-xs text-neutral-600">{item.sound}</span>}</div></div>
                        <div className="text-right"><span className={`inline-flex rounded-full px-3 py-1 text-sm font-bold ${scoreTone(item.percent)}`}>{item.percent}%</span><p className="mt-1.5 text-xs text-neutral-500">{item.correct} of {item.total} correct</p></div>
                        {item.review_url ? <Link className={`${pageButton} col-span-2 w-full sm:col-span-1 sm:w-auto`} href={item.review_url}>Review answers</Link> : <span className="col-span-2 text-xs text-neutral-400 sm:col-span-1">Sample attempt</span>}
                    </article>)}
                </div>
            </section>
            {history.last_page > 1 && <nav aria-label="History pages" className="mt-5">
                <p className="mb-3 text-center text-sm text-neutral-500">Showing {history.from}–{history.to} of {history.total} attempts</p>
                <div className="flex items-center justify-between gap-2">
                    {history.prev_page_url ? <Link className={pageButton} href={history.prev_page_url}>Previous</Link> : <button disabled className={`${pageButton} cursor-default opacity-40`}>Previous</button>}
                    <span aria-live="polite" className="text-center text-sm font-medium text-neutral-600">Page {history.current_page} of {history.last_page}</span>
                    {history.next_page_url ? <Link className={pageButton} href={history.next_page_url}>Next</Link> : <button disabled className={`${pageButton} cursor-default opacity-40`}>Next</button>}
                </div>
            </nav>}
        </div>
    </AppShell>;
}
