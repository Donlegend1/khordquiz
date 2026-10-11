import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';

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
    { icon: LifeBuoyIcon, title: 'Help Center', body: 'Browse frequently asked questions', route: 'help-center' },
    { icon: MailIcon, title: 'Email Support', body: 'Message our team · replies within 24 hours', route: 'support-email' },
];

export default function Support() {
    return (
        <AppShell eyebrow="Member" title="Help & Support" intro="Find answers or get in touch with our team.">
            <Head title="Help & Support" />

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
                            <p className="text-[15px] font-bold text-neutral-900">{option.title}</p>
                            <p className="text-sm text-neutral-500">{option.body}</p>
                        </div>
                        <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                    </Link>
                ))}
            </div>
        </AppShell>
    );
}
