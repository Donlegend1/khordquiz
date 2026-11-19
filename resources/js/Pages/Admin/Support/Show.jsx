import AppShell from '@/Layouts/AppShell';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { STATUS } from './Index';

const button = 'inline-flex min-h-11 items-center justify-center rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700 disabled:cursor-default disabled:opacity-40';
const formatDate = (value) => new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value));

function Message({ fromStaff, author, body, at }) {
    return (
        <article className={`rounded-2xl border p-4 sm:p-5 ${fromStaff ? 'border-sky-100 bg-sky-50/60' : 'border-neutral-200 bg-white'}`}>
            <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                <p className="text-sm font-semibold text-neutral-900">
                    {author}
                    {fromStaff && <span className="ml-2 rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-800">Team</span>}
                </p>
                <time dateTime={at} className="text-xs text-neutral-500">
                    {formatDate(at)}
                </time>
            </div>
            <p className="whitespace-pre-wrap break-words text-sm leading-relaxed text-neutral-700">{body}</p>
        </article>
    );
}

export default function SupportShow({ request }) {
    const status = STATUS[request.status] ?? STATUS.open;
    const [updating, setUpdating] = useState(false);
    const { data, setData, post, processing, errors, reset, transform } = useForm({ body: '' });

    function send(resolve) {
        if (!data.body.trim() || processing) return;
        transform((form) => ({ ...form, resolve }));
        post(route('admin.support.reply', request.id), { preserveScroll: true, onSuccess: () => reset() });
    }

    function changeStatus(next) {
        setUpdating(true);
        router.patch(route('admin.support.status', request.id), { status: next }, { preserveScroll: true, onFinish: () => setUpdating(false) });
    }

    return (
        <AppShell eyebrow={`Support · #${request.id}`} title={request.title} intro={`${request.topic} · sent from the ${request.source === 'web' ? 'website' : 'app'}`}>
            <Head title={`Support #${request.id}`} />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link href={route('admin.support.index')} className="text-sm font-semibold text-sky-700">
                    ← Support inbox
                </Link>
                <div className="flex items-center gap-2">
                    <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${status.className}`}>{status.label}</span>
                    {request.status === 'resolved' ? (
                        <button type="button" disabled={updating} onClick={() => changeStatus('open')} className={button}>
                            Reopen
                        </button>
                    ) : (
                        <button type="button" disabled={updating} onClick={() => changeStatus('resolved')} className={button}>
                            Mark resolved
                        </button>
                    )}
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_260px]">
                <div className="min-w-0 space-y-3">
                    <Message fromStaff={false} author={request.member?.name ?? 'Deleted member'} body={request.message} at={request.created_at} />
                    {request.attachment && (
                        <a href={request.attachment.url} target="_blank" rel="noreferrer" className="inline-flex min-h-9 items-center text-sm font-semibold text-sky-700 underline-offset-4 hover:underline">
                            Attachment: {request.attachment.name}
                        </a>
                    )}
                    {request.replies.map((reply) => (
                        <Message key={reply.id} fromStaff={reply.from_staff} author={reply.from_staff ? reply.author : (request.member?.name ?? 'Member')} body={reply.body} at={reply.created_at} />
                    ))}

                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            send(false);
                        }}
                        className="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5"
                    >
                        <label htmlFor="admin-reply" className="text-sm font-semibold text-neutral-900">
                            Reply to {request.member?.name ?? 'the member'}
                        </label>
                        <p className="mt-0.5 text-xs text-neutral-500">They’ll get a notification and see it in their support thread (plus an email if they chose email notifications).</p>
                        <textarea
                            id="admin-reply"
                            rows={6}
                            maxLength={5000}
                            value={data.body}
                            onChange={(event) => setData('body', event.target.value)}
                            className="mt-3 w-full rounded-xl border-neutral-300 text-sm"
                        />
                        {errors.body && <p className="mt-1 text-sm text-red-700">{errors.body}</p>}
                        <div className="mt-3 flex flex-wrap justify-end gap-2">
                            <button type="button" disabled={processing || !data.body.trim()} onClick={() => send(true)} className={button}>
                                Send and resolve
                            </button>
                            <button type="submit" disabled={processing || !data.body.trim()} className="inline-flex min-h-11 items-center justify-center rounded-xl bg-neutral-900 px-5 text-sm font-semibold text-white hover:bg-neutral-800 disabled:opacity-40">
                                {processing ? 'Sending…' : 'Send reply'}
                            </button>
                        </div>
                    </form>
                </div>

                <aside className="h-fit rounded-2xl border border-neutral-200 bg-white p-4">
                    <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">Member</p>
                    {request.member ? (
                        <>
                            <Link href={route('admin.users.show', request.member.id)} className="mt-2 inline-flex min-h-8 items-center font-semibold text-neutral-900 underline-offset-4 hover:underline">
                                {request.member.name}
                            </Link>
                            <a href={`mailto:${request.member.email}`} className="block break-all text-sm text-sky-700">
                                {request.member.email}
                            </a>
                        </>
                    ) : (
                        <p className="mt-2 text-sm text-neutral-500">Account deleted</p>
                    )}
                    <p className="mt-4 text-xs font-semibold text-neutral-500">Opened</p>
                    <p className="mt-1 text-sm text-neutral-700">{formatDate(request.created_at)}</p>
                    <p className="mt-3 text-xs font-semibold text-neutral-500">Last activity</p>
                    <p className="mt-1 text-sm text-neutral-700">{formatDate(request.last_activity_at)}</p>
                </aside>
            </div>
        </AppShell>
    );
}
