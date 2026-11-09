import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';
import { scoreTone } from '@/lib/scoreColor';
import { useLocale, useTrans } from '@/lib/i18n';

const dateLabel = (value, locale) => new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value));
const pageButton = 'inline-flex min-h-11 items-center justify-center rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700';

export default function AttemptHistory({ history }) {
    const t = useTrans();
    const locale = useLocale();

    return <AppShell title={t('Attempt history')} eyebrow={t('Your progress')} intro={t('See your scores and review your previous answers.')}>
        <Head title={t('Attempt history')} />
        <div className="mx-auto max-w-5xl">
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link href={route('progress')} className={pageButton}>{t('Back to Progress')}</Link>
            </div>
            <section aria-label={t('Completed attempts')} className="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                <div className="flex items-center justify-between gap-4 border-b border-neutral-100 px-5 py-4 sm:px-6"><h2 className="text-sm font-semibold text-neutral-700">{history.total === 1 ? t(':count completed attempt', { count: history.total }) : t(':count completed attempts', { count: history.total })}</h2><span className="text-xs text-neutral-500">{t('Newest first')}</span></div>
                <div className="divide-y divide-neutral-100 px-5 sm:px-6">
                    {history.data.length === 0 && <p className="py-10 text-center text-neutral-600">{t('Your completed quizzes will appear here.')}</p>}
                    {history.data.map(item => <article key={item.id} className="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-x-4 gap-y-4 py-5 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:gap-x-6 sm:py-6">
                        <div className="min-w-0"><h3 className="text-base font-semibold leading-snug text-neutral-900">{t(item.title)}</h3><p className="mt-1 text-sm text-neutral-600">{t(item.category)}</p><div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2"><time dateTime={item.date} className="text-xs text-neutral-500">{dateLabel(item.date, locale)}</time>{item.sound && <span className="rounded-md bg-neutral-100 px-2 py-1 text-xs text-neutral-600">{t(item.sound)}</span>}</div></div>
                        <div className="text-right"><span className={`inline-flex rounded-full px-3 py-1 text-sm font-bold ${scoreTone(item.percent)}`}>{item.percent}%</span><p className="mt-1.5 text-xs text-neutral-500">{t(':correct of :answered correct', { correct: item.correct, answered: item.total })}</p></div>
                        {item.review_url ? <Link className={`${pageButton} col-span-2 w-full sm:col-span-1 sm:w-auto`} href={item.review_url}>{t('Review answers')}</Link> : <span className="col-span-2 text-xs text-neutral-400 sm:col-span-1">{t('Sample attempt')}</span>}
                    </article>)}
                </div>
            </section>
            {history.last_page > 1 && <nav aria-label={t('History pages')} className="mt-5">
                <p className="mb-3 text-center text-sm text-neutral-500">{t('Showing :from–:to of :total attempts', { from: history.from, to: history.to, total: history.total })}</p>
                <div className="flex items-center justify-between gap-2">
                    {history.prev_page_url ? <Link className={pageButton} href={history.prev_page_url}>{t('Previous')}</Link> : <button disabled className={`${pageButton} cursor-default opacity-40`}>{t('Previous')}</button>}
                    <span aria-live="polite" className="text-center text-sm font-medium text-neutral-600">{t('Page :current of :last', { current: history.current_page, last: history.last_page })}</span>
                    {history.next_page_url ? <Link className={pageButton} href={history.next_page_url}>{t('Next')}</Link> : <button disabled className={`${pageButton} cursor-default opacity-40`}>{t('Next')}</button>}
                </div>
            </nav>}
        </div>
    </AppShell>;
}
