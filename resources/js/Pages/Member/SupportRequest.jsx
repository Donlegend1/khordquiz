import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { useLocale, useTrans } from '@/lib/i18n';
import { formatWhen, REQUEST_STATUS } from './Support';

const MAX_REPLY = 5000;

function Bubble({ fromStaff, author, body, at, locale }) {
    return (
        <div className={`flex ${fromStaff ? 'justify-start' : 'justify-end'}`}>
            <div className={`max-w-[85%] rounded-2xl px-4 py-3 ${fromStaff ? 'border border-black/5 bg-white' : 'bg-brand text-white'}`}>
                <p className={`text-xs font-semibold ${fromStaff ? 'text-brand' : 'text-white/80'}`}>{author}</p>
                <p className={`mt-1 whitespace-pre-wrap break-words text-sm leading-relaxed ${fromStaff ? 'text-neutral-800' : 'text-white'}`}>{body}</p>
                <p className={`mt-1.5 text-[11px] ${fromStaff ? 'text-neutral-400' : 'text-white/70'}`}>{formatWhen(at, locale)}</p>
            </div>
        </div>
    );
}

export default function SupportRequest({ request }) {
    const t = useTrans();
    const locale = useLocale();
    const status = REQUEST_STATUS[request.status] ?? REQUEST_STATUS.open;
    const { data, setData, post, processing, errors, reset } = useForm({ body: '' });

    function submit(event) {
        event.preventDefault();
        if (!data.body.trim() || processing) return;
        post(route('support.requests.reply', request.id), { preserveScroll: true, onSuccess: () => reset() });
    }

    return (
        <AppShell eyebrow={t('Help & Support')} title={request.subject || t(request.topic)} intro={t('Request #:id · :topic', { id: request.id, topic: t(request.topic) })}>
            <Head title={t('Support request')} />

            <div className="mx-auto max-w-2xl">
                <div className="mb-4 flex items-center justify-between gap-3">
                    <Link href={route('support')} className="text-sm font-semibold text-brand hover:text-brand-dark">
                        ← {t('All requests')}
                    </Link>
                    <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${status.className}`}>{t(status.label)}</span>
                </div>

                <div className="space-y-3">
                    <Bubble fromStaff={false} author={t('You')} body={request.message} at={request.created_at} locale={locale} />
                    {request.attachment && (
                        <div className="flex justify-end">
                            <a href={request.attachment.url} target="_blank" rel="noreferrer" className="text-xs font-semibold text-brand underline-offset-4 hover:underline">
                                📎 {request.attachment.name}
                            </a>
                        </div>
                    )}
                    {request.replies.map((reply) => (
                        <Bubble
                            key={reply.id}
                            fromStaff={reply.from_staff}
                            author={reply.from_staff ? t('KhordQuiz Support') : t('You')}
                            body={reply.body}
                            at={reply.created_at}
                            locale={locale}
                        />
                    ))}
                    {request.replies.length === 0 && (
                        <p className="py-2 text-center text-sm text-neutral-500">{t('Our team usually replies within 24 hours. We’ll notify you when we do.')}</p>
                    )}
                </div>

                <form onSubmit={submit} className="mt-6 rounded-2xl border border-black/5 bg-white p-4">
                    <label htmlFor="support-reply" className="text-sm font-semibold text-neutral-900">
                        {request.status === 'resolved' ? t('Still need help? Reply to reopen this request.') : t('Add a reply')}
                    </label>
                    <textarea
                        id="support-reply"
                        rows={4}
                        maxLength={MAX_REPLY}
                        value={data.body}
                        onChange={(event) => setData('body', event.target.value)}
                        placeholder={t('Write your message…')}
                        className="mt-2 w-full rounded-xl border-neutral-200 text-sm focus:border-brand focus:ring-brand"
                    />
                    {errors.body && <p className="mt-1 text-sm text-brand">{errors.body}</p>}
                    <div className="mt-3 flex items-center justify-between gap-3">
                        <span className="text-xs text-neutral-400">
                            {data.body.length}/{MAX_REPLY}
                        </span>
                        <button
                            type="submit"
                            disabled={processing || !data.body.trim()}
                            className="rounded-xl bg-brand px-5 py-2.5 text-sm font-bold text-white transition-opacity duration-150 disabled:opacity-50"
                        >
                            {processing ? t('Sending…') : t('Send reply')}
                        </button>
                    </div>
                </form>
            </div>
        </AppShell>
    );
}
