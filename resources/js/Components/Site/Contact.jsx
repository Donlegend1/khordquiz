import Eyebrow from '@/Components/Site/Eyebrow';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

// Quick picks that fill in the subject.
const topics = ['General question', 'Bug report', 'Billing & purchases', 'Account help'];

export default function Contact() {
    return (
        <section
            id="contact"
            className="scroll-mt-[88px] bg-gradient-to-b from-[#F3F6FB] via-[#FBF5F1] to-[#FDEFE7]"
        >
            <div className="mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[1fr_1.2fr] lg:items-center lg:gap-16 lg:px-[100px] lg:py-20">
                <div>
                    <Eyebrow>Contact</Eyebrow>

                    <h2 className="mt-5 text-3xl font-bold leading-tight tracking-tight text-neutral-900 sm:text-4xl">
                        We&apos;re here
                        <br />
                        <span className="font-display font-semibold tracking-normal text-brand">to help.</span>
                    </h2>
                </div>

                <ContactForm />
            </div>
        </section>
    );
}

function ContactForm() {
    const [sent, setSent] = useState(false);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        name: '',
        email: '',
        subject: '',
        message: '',
        website: '', // honeypot
    });

    const submit = (event) => {
        event.preventDefault();
        post(route('contact.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setSent(true);
            },
        });
    };

    if (sent) {
        return (
            <div className="flex flex-col items-center justify-center rounded-2xl border border-gray-100 bg-white p-8 text-center shadow-[0_6px_24px_-8px_rgba(17,24,39,0.12)] sm:p-10">
                <span className="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <svg className="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
                        <path d="M5 12.5l4.5 4.5L19 7.5" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                </span>
                <h3 className="mt-5 text-2xl font-bold text-neutral-900">Message sent</h3>
                <p className="mt-2 max-w-[340px] text-neutral-600">
                    Thanks for getting in touch. We&apos;ll reply to the email address you gave us.
                </p>
                <button
                    type="button"
                    onClick={() => setSent(false)}
                    className="mt-6 text-sm font-medium text-brand hover:underline"
                >
                    Send another message
                </button>
            </div>
        );
    }

    return (
        <form
            onSubmit={submit}
            noValidate
            className="rounded-2xl border border-gray-100 bg-white p-6 shadow-[0_6px_24px_-8px_rgba(17,24,39,0.12)] sm:p-8"
        >
            <div className="grid gap-5 sm:grid-cols-2">
                <Field label="Your name" error={errors.name}>
                    <input
                        type="text"
                        autoComplete="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        className={inputClass(errors.name)}
                    />
                </Field>
                <Field label="Email address" error={errors.email}>
                    <input
                        type="email"
                        autoComplete="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className={inputClass(errors.email)}
                    />
                </Field>
            </div>

            <div className="mt-5">
                <Field label="Subject" error={errors.subject}>
                    <input
                        type="text"
                        value={data.subject}
                        onChange={(e) => setData('subject', e.target.value)}
                        placeholder="What's this about?"
                        className={inputClass(errors.subject)}
                    />
                </Field>
                <div className="mt-2 flex flex-wrap gap-1.5">
                    {topics.map((topic) => (
                        <button
                            key={topic}
                            type="button"
                            onClick={() => {
                                setData('subject', topic);
                                clearErrors('subject');
                            }}
                            className={`rounded-full border px-3 py-1 text-xs font-medium transition ${
                                data.subject === topic
                                    ? 'border-brand bg-brand/10 text-brand'
                                    : 'border-gray-200 text-neutral-600 hover:border-gray-300 hover:text-neutral-900'
                            }`}
                        >
                            {topic}
                        </button>
                    ))}
                </div>
            </div>

            <div className="mt-5">
                <Field label="Message" error={errors.message}>
                    <textarea
                        rows={6}
                        value={data.message}
                        onChange={(e) => setData('message', e.target.value)}
                        placeholder={
                            data.subject === 'Bug report'
                                ? 'What went wrong? Include your device, operating system and app version if you can.'
                                : 'How can we help?'
                        }
                        className={`${inputClass(errors.message)} resize-y`}
                    />
                </Field>
            </div>

            {/* Honeypot — hidden from people and screen readers. */}
            <div className="absolute left-[-9999px]" aria-hidden="true">
                <label>
                    Website
                    <input
                        type="text"
                        tabIndex={-1}
                        autoComplete="off"
                        value={data.website}
                        onChange={(e) => setData('website', e.target.value)}
                    />
                </label>
            </div>

            <div className="mt-6 flex flex-col-reverse items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-xs text-neutral-500">We only use your details to reply to you.</p>
                <button
                    type="submit"
                    disabled={processing}
                    className="inline-flex w-full items-center justify-center gap-2 rounded-md bg-brand px-6 py-3 text-base font-medium text-white transition hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                >
                    {processing ? 'Sending…' : 'Send message'}
                    {!processing && <SendIcon />}
                </button>
            </div>
        </form>
    );
}

function Field({ label, error, children }) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-sm font-medium text-neutral-800">{label}</span>
            {children}
            {error && <span className="mt-1.5 block text-sm text-brand">{error}</span>}
        </label>
    );
}

const inputClass = (error) =>
    `block w-full rounded-lg border bg-white px-3.5 py-2.5 text-[15px] text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 ${
        error ? 'border-brand focus:border-brand focus:ring-brand/20' : 'border-gray-300 focus:border-neutral-900 focus:ring-neutral-900/10'
    }`;

function SendIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M5 12h14M13 6l6 6-6 6" />
        </svg>
    );
}
