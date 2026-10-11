import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const SECTIONS = [
    {
        title: 'Getting started',
        tone: 'bg-rose-50 text-brand',
        icon: (props) => (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
                <path d="M12 2.5c3.5 1 6 4.5 6 8.5 0 2-.7 3.6-1.5 5l-1 4-3.5-2-3.5 2-1-4C6.7 14.6 6 13 6 11c0-4 2.5-7.5 6-8.5z" />
                <circle cx="12" cy="10.5" r="2" />
            </svg>
        ),
        faqs: [
            { q: 'How do I start a quiz?', a: 'Open the Quiz tab, choose a category, then pick a level and start. Listen with the play button and choose your answer.' },
            { q: 'Which quiz should I begin with?', a: 'If you are new to ear training, start with Relative Pitch and Intervals, then move on to Basic Triads and the chord categories.' },
            { q: 'Can I change the instrument sound?', a: 'Yes. Choose an instrument on a quiz before you start, or set a default for all quizzes in Settings → Default instrument.' },
        ],
    },
    {
        title: 'Quizzes',
        tone: 'bg-sky-50 text-sky-600',
        icon: (props) => (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
                <path d="M9 18V9l9-2v9M9 18a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 019 18zM18 16a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 0118 16z" />
            </svg>
        ),
        faqs: [
            { q: 'How many questions are in a quiz?', a: 'Each quiz has 25 questions. Finish all of them to mark the quiz as completed.' },
            { q: 'How do I practise my mistakes?', a: 'On the Progress page, mistakes you recently got wrong are grouped so you can review them. Answer one correctly and it leaves the list.' },
            { q: 'How do I save a quiz for later?', a: 'Tap the bookmark icon on a quiz. Your saved quizzes appear under Bookmarks.' },
        ],
    },
    {
        title: 'Progress & goals',
        tone: 'bg-emerald-50 text-emerald-600',
        icon: (props) => (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
                <path d="M4 20V11M12 20V4M20 20v-7" />
            </svg>
        ),
        faqs: [
            { q: 'How is my accuracy calculated?', a: 'Accuracy is the share of all your answers that were correct, across every quiz you have played.' },
            { q: 'How does the daily streak work?', a: 'Answer at least one question each day to keep your streak going. Missing a full day resets it.' },
            { q: 'Can I change my daily goal?', a: 'Yes. Tap Change on the Today’s goal card in Progress, or go to Settings → Daily goal.' },
            { q: 'Will I lose my progress if I reset it?', a: 'Resetting progress in Settings permanently deletes your completed quizzes, scores and streaks. It can’t be undone.' },
        ],
    },
    {
        title: 'Account & billing',
        tone: 'bg-violet-50 text-violet-600',
        icon: (props) => (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
                <rect x="3.5" y="5.5" width="17" height="13" rx="2" />
                <path d="M3.5 10h17" />
            </svg>
        ),
        faqs: [
            { q: 'How do I manage my subscription?', a: 'Go to Account to see your plan. Plans and subscriptions aren’t available on the web yet.' },
            { q: 'How do I change my password?', a: 'Go to Account → Change password and enter your new password.' },
            { q: 'How do I turn notifications on or off?', a: 'Go to Settings and use the switches under Notifications.' },
        ],
    },
];

function SearchIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="11" cy="11" r="7" />
            <path d="M21 21l-4.3-4.3" />
        </svg>
    );
}

function ChevronDownIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 9l6 6 6-6" />
        </svg>
    );
}

function ChatIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 5.5h16v10H9l-4 3.5v-3.5H4z" />
        </svg>
    );
}

export default function HelpCenter() {
    const [query, setQuery] = useState('');
    const [topic, setTopic] = useState(null);
    const [openQuestion, setOpenQuestion] = useState(null);

    const search = query.trim().toLowerCase();
    const sections = useMemo(
        () =>
            SECTIONS.filter((section) => !topic || section.title === topic)
                .map((section) => ({
                    ...section,
                    faqs: search ? section.faqs.filter((faq) => `${faq.q} ${faq.a}`.toLowerCase().includes(search)) : section.faqs,
                }))
                .filter((section) => section.faqs.length > 0),
        [search, topic],
    );
    const resultCount = sections.reduce((sum, section) => sum + section.faqs.length, 0);

    return (
        <AppShell eyebrow="Help Center" title="How can we help?" intro="Search our FAQs or browse by topic.">
            <Head title="Help Center" />

            <div className="mx-auto max-w-2xl">
                <div className="mb-5 flex items-center gap-3 rounded-2xl border border-black/5 bg-white px-4 py-3.5 shadow-sm">
                    <SearchIcon className="h-5 w-5 shrink-0 text-neutral-400" />
                    <input
                        type="text"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Search for answers"
                        className="w-full text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-none"
                    />
                </div>

                <div className="mb-6 grid grid-cols-2 gap-2.5">
                    {SECTIONS.map((section) => {
                        const on = topic === section.title;
                        return (
                            <button
                                key={section.title}
                                type="button"
                                onClick={() => setTopic(on ? null : section.title)}
                                className={`flex flex-col items-start gap-2.5 rounded-2xl border p-3.5 text-left transition-colors duration-150 ${
                                    on ? 'border-neutral-900 bg-neutral-900' : 'border-black/5 bg-white hover:border-neutral-200'
                                }`}
                            >
                                <span className={`flex h-9 w-9 items-center justify-center rounded-xl ${on ? 'bg-white/15 text-white' : section.tone}`}>
                                    <section.icon className="h-4.5 w-4.5" />
                                </span>
                                <span>
                                    <span className={`block text-sm font-bold ${on ? 'text-white' : 'text-neutral-900'}`}>{section.title}</span>
                                    <span className={`block text-xs ${on ? 'text-white/60' : 'text-neutral-400'}`}>{section.faqs.length} articles</span>
                                </span>
                            </button>
                        );
                    })}
                </div>

                <div className="mb-3 flex items-baseline justify-between">
                    <h2 className="text-lg font-bold text-neutral-900">
                        {search ? `${resultCount} ${resultCount === 1 ? 'result' : 'results'}` : (topic ?? 'Popular questions')}
                    </h2>
                    {topic && (
                        <button type="button" onClick={() => setTopic(null)} className="text-sm font-semibold text-brand">
                            Show all
                        </button>
                    )}
                </div>

                {resultCount === 0 ? (
                    <div className="flex flex-col items-center py-10 text-center">
                        <SearchIcon className="h-7 w-7 text-neutral-300" />
                        <p className="mt-2 text-base font-bold text-neutral-900">No results for &ldquo;{query.trim()}&rdquo;</p>
                        <p className="mt-1 text-sm text-neutral-500">Try another word, or contact us below.</p>
                    </div>
                ) : (
                    <div className="space-y-2.5">
                        {sections
                            .flatMap((section) => section.faqs)
                            .map((faq) => {
                                const expanded = openQuestion === faq.q;
                                return (
                                    <button
                                        key={faq.q}
                                        type="button"
                                        onClick={() => setOpenQuestion(expanded ? null : faq.q)}
                                        className={`block w-full rounded-2xl border p-4 text-left transition-colors duration-150 ${
                                            expanded ? 'border-neutral-300 bg-neutral-50' : 'border-black/5 bg-white hover:border-neutral-200'
                                        }`}
                                    >
                                        <div className="flex items-center gap-3">
                                            <span className="flex-1 text-sm font-semibold leading-snug text-neutral-900">{faq.q}</span>
                                            <span className={`flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-full ${expanded ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-500'}`}>
                                                <ChevronDownIcon className={`h-3.5 w-3.5 transition-transform duration-150 ${expanded ? 'rotate-180' : ''}`} />
                                            </span>
                                        </div>
                                        {expanded && <p className="mt-2.5 text-sm leading-relaxed text-neutral-600">{faq.a}</p>}
                                    </button>
                                );
                            })}
                    </div>
                )}

                <div className="mt-8 rounded-2xl bg-neutral-900 p-5 text-white">
                    <div className="flex items-center gap-3">
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10">
                            <ChatIcon className="h-5 w-5" />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-[15px] font-bold">Still need help?</p>
                            <p className="text-sm text-white/60">Our team usually replies within 24 hours.</p>
                        </div>
                    </div>
                    <Link
                        href={route('support-email')}
                        className="mt-4 flex items-center justify-center gap-2 rounded-xl bg-white py-3 text-sm font-bold text-neutral-900 transition-opacity duration-150 hover:opacity-90"
                    >
                        Email Support
                    </Link>
                </div>
            </div>
        </AppShell>
    );
}
