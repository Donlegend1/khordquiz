import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';
import { tk, useLocale, useTrans } from '@/lib/i18n';

function LifeBuoyIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="8.5" />
            <circle cx="12" cy="12" r="3.5" />
            <path d="M5.5 5.5l3.2 3.2M18.5 5.5l-3.2 3.2M5.5 18.5l3.2-3.2M18.5 18.5l-3.2-3.2" />
        </svg>
    );
}

function MailIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <rect x="3.5" y="5.5" width="17" height="13" rx="2" />
            <path d="M4 7l8 6 8-6" />
        </svg>
    );
}

function ChevronIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 5l7 7-7 7" />
        </svg>
    );
}

const OPTIONS = [
    { icon: LifeBuoyIcon, title: tk('Help Center'), body: tk('Browse frequently asked questions'), route: 'help-center' },
    { icon: MailIcon, title: tk('Email Support'), body: tk('Message our team · replies within 24 hours'), route: 'support-email' },
];

// Member-facing wording for a request's status (SupportMessage::STATUSES).
export const REQUEST_STATUS = {
    open: { label: tk('Waiting for reply'), className: 'bg-amber-50 text-amber-700' },
    answered: { label: tk('Replied'), className: 'bg-emerald-50 text-emerald-700' },
    resolved: { label: tk('Resolved'), className: 'bg-neutral-100 text-neutral-500' },
};

export function formatWhen(value, locale) {
    return new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }).format(new Date(value));
}

export default function Support({ requests = [] }) {
    const t = useTrans();
    const locale = useLocale();
    return (
        <AppShell eyebrow={t('Member')} title={t('Help & Support')} intro={t('Find answers or get in touch with our team.')}>
            <Head title={t('Help & Support')} />

            <div className="mx-auto max-w-2xl space-y-3">
                {OPTIONS.map((option) => (
                    <Link
                        key={option.title}
                        href={route(option.route)}
                        className="flex items-center gap-4 rounded-2xl border border-black/5 bg-white p-4 transition-colors duration-150 hover:border-brand/15 hover:bg-brand/[0.03]"
                    >
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-50 text-brand">
                            <option.icon className="h-5 w-5" />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-[15px] font-bold text-neutral-900">{t(option.title)}</p>
                            <p className="text-sm text-neutral-500">{t(option.body)}</p>
                        </div>
                        <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                    </Link>
                ))}

                <div className="pt-5">
                    <h2 className="mb-2 px-1 text-xs font-bold uppercase tracking-wide text-neutral-400">{t('Your requests')}</h2>
                    {requests.length === 0 ? (
                        <p className="rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-8 text-center text-sm text-neutral-500">
                            {t("Messages you send to our team will show here, with our replies.")}
                        </p>
                    ) : (
                        <ul className="divide-y divide-black/5 rounded-2xl border border-black/5 bg-white">
                            {requests.map((request) => {
                                const status = REQUEST_STATUS[request.status] ?? REQUEST_STATUS.open;
                                return (
                                    <li key={request.id}>
                                        <Link
                                            href={route('support.requests.show', request.id)}
                                            className="flex items-center gap-3 px-4 py-3.5 transition-colors duration-150 hover:bg-neutral-50"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-semibold text-neutral-900">{request.subject || t(request.topic)}</p>
                                                <p className="truncate text-xs text-neutral-500">
                                                    {t(request.topic)} · {formatWhen(request.last_activity_at, locale)}
                                                </p>
                                            </div>
                                            <span className={`shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ${status.className}`}>{t(status.label)}</span>
                                            <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </div>
            </div>
        </AppShell>
    );
}
