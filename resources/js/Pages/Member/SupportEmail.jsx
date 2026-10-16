import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const SUPPORT_EMAIL = 'support@khordquiz.com';
const MAX_MESSAGE = 2000;
const MAX_ATTACHMENT_BYTES = 10 * 1024 * 1024;
const TOPICS = [
    { label: 'Account', hint: 'Sign in, profile or password' },
    { label: 'Billing', hint: 'Payments and subscription' },
    { label: 'Quiz issue', hint: 'A question, answer or sound' },
    { label: 'Bug report', hint: "Something isn't working" },
    { label: 'Other', hint: 'Anything else' },
];

function MailIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <rect x="3.5" y="5.5" width="17" height="13" rx="2" />
            <path d="M4 7l8 6 8-6" />
        </svg>
    );
}

function TagIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M12 3l7.5 7.5a2 2 0 010 2.8L13 20a2 2 0 01-2.8 0L3 12.3V3z" />
            <circle cx="7.5" cy="7.5" r="1.3" fill="currentColor" stroke="none" />
        </svg>
    );
}

function PencilIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 20h4l10.5-10.5a2.1 2.1 0 00-3-3L5 17v3z" />
        </svg>
    );
}

function PhoneIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <rect x="6.5" y="3" width="11" height="18" rx="2" />
            <path d="M11 18h2" />
        </svg>
    );
}

function SendIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" stroke="none" {...props}>
            <path d="M3 11l18-8-8 18-2.5-7L3 11z" />
        </svg>
    );
}

function CheckIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 13l4 4L19 7" />
        </svg>
    );
}

function PaperclipIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M8.5 12.5l6-6a3.2 3.2 0 014.5 4.5l-8 8a5.5 5.5 0 01-7.8-7.8l7.3-7.3a1.9 1.9 0 012.7 2.7l-7 7" />
        </svg>
    );
}

function CloseIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 6l12 12M18 6L6 18" />
        </svg>
    );
}

function formatFileSize(bytes) {
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function Toggle({ value, onChange }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={value}
            onClick={() => onChange(!value)}
            className={`inline-flex h-7 w-12 shrink-0 items-center rounded-full p-1 transition-colors duration-150 ${value ? 'bg-brand' : 'bg-neutral-200'}`}
        >
            <span className={`h-5 w-5 rounded-full bg-white shadow transition-transform duration-150 ${value ? 'translate-x-5' : 'translate-x-0'}`} />
        </button>
    );
}

function detectBrowser() {
    if (typeof navigator === 'undefined') return 'Web';
    const ua = navigator.userAgent;
    if (ua.includes('Edg/')) return 'Edge';
    if (ua.includes('Chrome/')) return 'Chrome';
    if (ua.includes('Firefox/')) return 'Firefox';
    if (ua.includes('Safari/')) return 'Safari';
    return 'Web';
}

export default function SupportEmail() {
    const [pickingTopic, setPickingTopic] = useState(false);
    const [sent, setSent] = useState(false);
    const [attachmentError, setAttachmentError] = useState(null);
    const pickerRef = useRef(null);
    const fileInputRef = useRef(null);
    const deviceInfo = `App version 1.0.0 · ${detectBrowser()}`;

    const { data, setData, post, processing, errors, reset } = useForm({
        topic: null,
        subject: '',
        message: '',
        include_device: true,
        device_info: deviceInfo,
        attachment: null,
    });

    useEffect(() => {
        if (!pickingTopic) return;
        const onPointerDown = (event) => {
            if (pickerRef.current && !pickerRef.current.contains(event.target)) setPickingTopic(false);
        };
        document.addEventListener('mousedown', onPointerDown);
        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [pickingTopic]);

    const ready = Boolean(data.topic) && data.message.trim().length > 0;

    function chooseFile(file) {
        setAttachmentError(null);
        if (!file) {
            setData('attachment', null);
            return;
        }
        if (file.size > MAX_ATTACHMENT_BYTES) {
            setAttachmentError('That file is over the 10MB limit.');
            if (fileInputRef.current) fileInputRef.current.value = '';
            return;
        }
        setData('attachment', file);
    }

    function send() {
        if (!ready || processing) return;
        post(route('support-email.store'), {
            forceFormData: true,
            onSuccess: () => {
                setSent(true);
                reset();
                if (fileInputRef.current) fileInputRef.current.value = '';
            },
        });
    }

    return (
        <AppShell eyebrow="Member" title="Email Support" intro="Send our team a message and we'll get back to you.">
            <Head title="Email Support" />

            <div className="mx-auto max-w-2xl">
                <div className="mb-6 flex items-center gap-3 rounded-2xl bg-rose-50 p-3.5">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-brand">
                        <MailIcon className="h-4.5 w-4.5" />
                    </span>
                    <div>
                        <p className="text-sm font-bold text-neutral-900">{SUPPORT_EMAIL}</p>
                        <p className="text-xs text-neutral-500">We usually reply within 24 hours.</p>
                    </div>
                </div>

                {sent ? (
                    <div className="flex flex-col items-center gap-3 py-12 text-center">
                        <span className="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <CheckIcon className="h-7 w-7" />
                        </span>
                        <p className="text-lg font-extrabold text-neutral-900">Message sent</p>
                        <p className="max-w-xs text-sm text-neutral-500">
                            We&apos;ve got your message and will reply by email soon.
                        </p>
                        <Link
                            href={route('support')}
                            className="mt-2 w-full max-w-xs rounded-xl bg-brand py-3 text-center text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90"
                        >
                            Back to Support
                        </Link>
                    </div>
                ) : (
                    <>
                        <p className="mb-2 px-1 text-xs font-bold uppercase tracking-wide text-neutral-400">Your request</p>
                        <div className="mb-6 overflow-hidden rounded-2xl border border-black/5 bg-white">
                            <div className="relative" ref={pickerRef}>
                                <button
                                    type="button"
                                    onClick={() => setPickingTopic((v) => !v)}
                                    className="flex w-full items-center gap-3 px-4 py-3.5 text-left transition-colors duration-150 hover:bg-neutral-50"
                                >
                                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600">
                                        <TagIcon className="h-4 w-4" />
                                    </span>
                                    <span className="flex-1 text-sm font-semibold text-neutral-900">Topic</span>
                                    <span className={`text-sm ${data.topic ? 'text-neutral-900' : 'text-neutral-400'}`}>{data.topic ?? 'Choose'}</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4 text-neutral-300">
                                        <path d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>

                                {pickingTopic && (
                                    <div className="absolute left-4 right-4 top-full z-20 mt-1 overflow-hidden rounded-xl border border-black/5 bg-white py-1 shadow-lg">
                                        {TOPICS.map((option) => {
                                            const on = option.label === data.topic;
                                            return (
                                                <button
                                                    key={option.label}
                                                    type="button"
                                                    onClick={() => {
                                                        setData('topic', option.label);
                                                        setPickingTopic(false);
                                                    }}
                                                    className="flex w-full items-center gap-3 px-3.5 py-2.5 text-left transition-colors duration-150 hover:bg-neutral-50"
                                                >
                                                    <div className="min-w-0 flex-1">
                                                        <p className={`text-sm font-semibold ${on ? 'text-brand' : 'text-neutral-900'}`}>{option.label}</p>
                                                        <p className="text-xs text-neutral-400">{option.hint}</p>
                                                    </div>
                                                    {on && <CheckIcon className="h-4 w-4 shrink-0 text-brand" />}
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>

                            <div className="flex items-center gap-3 border-t border-black/5 px-4 py-1">
                                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600">
                                    <PencilIcon className="h-4 w-4" />
                                </span>
                                <input
                                    type="text"
                                    value={data.subject}
                                    maxLength={100}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    placeholder="Subject (optional)"
                                    className="w-full border-0 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-0 focus:outline-none focus:ring-0"
                                />
                            </div>

                            <div className="border-t border-black/5">
                                <textarea
                                    value={data.message}
                                    maxLength={MAX_MESSAGE}
                                    onChange={(e) => setData('message', e.target.value)}
                                    placeholder="Describe your request."
                                    rows={6}
                                    className="w-full resize-none border-0 px-4 py-3.5 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-0 focus:outline-none focus:ring-0"
                                />
                                <p className="px-4 pb-2.5 text-right text-xs text-neutral-300">
                                    {data.message.length}/{MAX_MESSAGE}
                                </p>
                            </div>

                            <div className="border-t border-black/5 px-4 py-3.5">
                                <div className="flex items-center gap-3">
                                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600">
                                        <PaperclipIcon className="h-4 w-4" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        {data.attachment ? (
                                            <>
                                                <p className="truncate text-sm font-semibold text-neutral-900">{data.attachment.name}</p>
                                                <p className="text-xs text-neutral-400">{formatFileSize(data.attachment.size)}</p>
                                            </>
                                        ) : (
                                            <>
                                                <p className="text-sm font-semibold text-neutral-900">Attach a file</p>
                                                <p className="text-xs text-neutral-400">Image or PDF, up to 10MB</p>
                                            </>
                                        )}
                                    </div>
                                    {data.attachment ? (
                                        <button
                                            type="button"
                                            onClick={() => chooseFile(null)}
                                            aria-label="Remove attachment"
                                            className="shrink-0 text-neutral-300 hover:text-brand"
                                        >
                                            <CloseIcon className="h-4 w-4" />
                                        </button>
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={() => fileInputRef.current?.click()}
                                            className="shrink-0 rounded-full bg-neutral-100 px-3.5 py-1.5 text-xs font-bold text-neutral-700 transition-colors duration-150 hover:bg-neutral-200"
                                        >
                                            Choose file
                                        </button>
                                    )}
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp,image/gif,application/pdf"
                                        onChange={(e) => chooseFile(e.target.files?.[0] ?? null)}
                                        className="hidden"
                                    />
                                </div>
                                {(attachmentError || errors.attachment) && (
                                    <p className="mt-2 text-xs font-semibold text-brand">{attachmentError || errors.attachment}</p>
                                )}
                            </div>
                        </div>

                        <p className="mb-2 px-1 text-xs font-bold uppercase tracking-wide text-neutral-400">Options</p>
                        <div className="mb-6 overflow-hidden rounded-2xl border border-black/5 bg-white">
                            <div className="flex items-center gap-3 px-4 py-3.5">
                                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600">
                                    <PhoneIcon className="h-4 w-4" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-semibold text-neutral-900">Include device info</p>
                                    <p className="text-xs text-neutral-400">{deviceInfo}</p>
                                </div>
                                <Toggle value={data.include_device} onChange={(value) => setData('include_device', value)} />
                            </div>
                        </div>

                        <button
                            type="button"
                            disabled={!ready || processing}
                            onClick={send}
                            className="flex w-full items-center justify-center gap-2 rounded-xl bg-brand py-3.5 text-sm font-bold text-white transition-opacity duration-150 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <SendIcon className="h-4 w-4" />
                            {processing ? 'Sending…' : 'Send Message'}
                        </button>
                    </>
                )}
            </div>
        </AppShell>
    );
}
