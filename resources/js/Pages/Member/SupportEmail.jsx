import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const SUPPORT_EMAIL = 'support@khordquiz.com';
const MAX_MESSAGE = 2000;
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

function Toggle({ value, onChange }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={value}
            onClick={() => onChange(!value)}
            className={`relative h-7 w-12 shrink-0 rounded-full transition-colors duration-150 ${value ? 'bg-brand' : 'bg-neutral-200'}`}
        >
            <span className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow transition-transform duration-150 ${value ? 'translate-x-6' : 'translate-x-1'}`} />
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
    const [topic, setTopic] = useState(null);
    const [pickingTopic, setPickingTopic] = useState(false);
    const [subject, setSubject] = useState('');
    const [message, setMessage] = useState('');
    const [includeDevice, setIncludeDevice] = useState(true);
    const [sent, setSent] = useState(false);
    const pickerRef = useRef(null);

    useEffect(() => {
        if (!pickingTopic) return;
        const onPointerDown = (event) => {
            if (pickerRef.current && !pickerRef.current.contains(event.target)) setPickingTopic(false);
        };
        document.addEventListener('mousedown', onPointerDown);
        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [pickingTopic]);

    const ready = Boolean(topic) && message.trim().length > 0;
    const deviceInfo = `App version 1.0.0 · ${detectBrowser()}`;

    function send() {
        if (!ready) return;
        const title = `[${topic}] ${subject.trim() || 'Support request'}`;
        const body = `${message.trim()}${includeDevice ? `\n\n---\n${deviceInfo}` : ''}`;
        window.location.href = `mailto:${SUPPORT_EMAIL}?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(body)}`;
        setSent(true);
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
                        <p className="text-lg font-extrabold text-neutral-900">Your email is ready</p>
                        <p className="max-w-xs text-sm text-neutral-500">
                            We opened your email app with your message. Send it there and we&apos;ll be in touch soon.
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
                                    <span className={`text-sm ${topic ? 'text-neutral-900' : 'text-neutral-400'}`}>{topic ?? 'Choose'}</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4 text-neutral-300">
                                        <path d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>

                                {pickingTopic && (
                                    <div className="absolute left-4 right-4 top-full z-20 mt-1 overflow-hidden rounded-xl border border-black/5 bg-white py-1 shadow-lg">
                                        {TOPICS.map((option) => {
                                            const on = option.label === topic;
                                            return (
                                                <button
                                                    key={option.label}
                                                    type="button"
                                                    onClick={() => {
                                                        setTopic(option.label);
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
                                    value={subject}
                                    maxLength={100}
                                    onChange={(e) => setSubject(e.target.value)}
                                    placeholder="Subject (optional)"
                                    className="w-full py-3 text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-none"
                                />
                            </div>

                            <div className="border-t border-black/5">
                                <textarea
                                    value={message}
                                    maxLength={MAX_MESSAGE}
                                    onChange={(e) => setMessage(e.target.value)}
                                    placeholder="Describe the issue in as much detail as you can..."
                                    rows={6}
                                    className="w-full resize-none px-4 py-3.5 text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-none"
                                />
                                <p className="px-4 pb-2.5 text-right text-xs text-neutral-300">
                                    {message.length}/{MAX_MESSAGE}
                                </p>
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
                                <Toggle value={includeDevice} onChange={setIncludeDevice} />
                            </div>
                        </div>

                        <button
                            type="button"
                            disabled={!ready}
                            onClick={send}
                            className="flex w-full items-center justify-center gap-2 rounded-xl bg-brand py-3.5 text-sm font-bold text-white transition-opacity duration-150 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <SendIcon className="h-4 w-4" />
                            Send Message
                        </button>
                    </>
                )}
            </div>
        </AppShell>
    );
}
