import AppShell from '@/Layouts/AppShell';
import { Head, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { tk, useTrans } from '@/lib/i18n';

// Sample ideas, same as the mobile app's own Suggestions screen (see
// src/lib/suggestions.ts there): "Sample ideas until suggestions come from the
// server." Votes and new ideas here are kept in local state only, same as the
// app — they reset on page reload since there's no suggestions table yet.
// Sample title/description/reason are translation keys; ideas a member submits
// here are marked `own` and shown exactly as typed.
const INITIAL_SUGGESTIONS = [
    { id: 's1', author: 'Philip O.', title: tk('Video explanations for failed questions'), description: tk('After getting a question wrong, show a short video clip explaining the concept in detail.'), status: 'pending', votes: 13 },
    { id: 's2', author: 'Matthew L.', title: tk('Dark/Light Theme Toggle'), description: tk('Let me switch the whole app between a dark and a light look.'), status: 'pending', votes: 11 },
    { id: 's3', author: 'Ada N.', title: tk('More instrument sounds'), description: tk('Play each chord on guitar or strings as well as piano so I can train my ear on other instruments.'), status: 'pending', votes: 9 },
    { id: 's10', author: 'Chioma A.', title: tk('Streak-about-to-break alert'), description: tk("Notify me in the evening if I still haven't hit my daily goal, so I don't lose my streak overnight."), status: 'pending', votes: 18 },
    { id: 's11', author: 'Tunde B.', title: tk('Celebrate achievements I unlock'), description: tk('Send a notification the moment I unlock a badge, the same way a perfect score does.'), status: 'pending', votes: 10 },
    { id: 's12', author: 'David E.', title: tk('Notify me when a challenge arrives'), description: tk('Right now I only get notified about a challenge result — tell me as soon as a friend sends me one too.'), status: 'pending', votes: 14 },
    { id: 's13', author: 'Bisi K.', title: tk('"Come back" reminder after a few days away'), description: tk("If I haven't practised in a few days, send a gentle nudge to pick it back up."), status: 'pending', votes: 7 },
    { id: 's4', author: 'Samuel K.', title: tk('Daily practice reminders'), description: tk('Send a reminder at a time I choose so I keep my daily streak going.'), status: 'approved', votes: 21 },
    { id: 's5', author: 'Grace T.', title: tk('Slower playback option'), description: tk('Slow down the playback speed for melodic dictation on the harder levels.'), status: 'approved', votes: 17 },
    { id: 's6', author: 'Daniel B.', title: tk('Live quiz battles with friends'), description: tk('Compete live against a friend on the same quiz in real time.'), status: 'declined', votes: 8, reason: tk("Real-time multiplayer requires significant infrastructure investment beyond our current scope. We'll revisit in a future version.") },
    { id: 's7', author: 'Ruth A.', title: tk('Upload my own audio'), description: tk('Let me upload my own recordings and quiz myself on them.'), status: 'declined', votes: 5, reason: tk("We can't check custom recordings for correct answers yet, so results wouldn't be reliable.") },
    { id: 's8', author: 'Philip O.', title: tk('Most-confused answers on Progress'), description: tk('Show which answers I mix up most so I know what to practise.'), status: 'done', votes: 24 },
    { id: 's9', author: 'Matthew L.', title: tk('Review my mistakes'), description: tk('Replay the questions I got wrong in one short review session.'), status: 'done', votes: 19 },
];

const TABS = [
    { label: tk('Pending'), value: 'pending' },
    { label: tk('Approved'), value: 'approved' },
    { label: tk('Declined'), value: 'declined' },
    { label: tk('Done'), value: 'done' },
];
const BADGES = {
    pending: 'bg-amber-50 text-amber-800 border-amber-200',
    approved: 'bg-sky-50 text-sky-700 border-sky-200',
    declined: 'bg-neutral-100 text-neutral-600 border-neutral-200',
    done: 'bg-emerald-50 text-emerald-700 border-emerald-200',
};
const BADGE_LABEL = { pending: tk('Pending Review'), approved: tk('Approved'), declined: tk('Declined'), done: tk('Done') };
const EMPTY = {
    pending: tk('No ideas waiting for review. Be the first to submit one!'),
    approved: tk('No approved ideas yet.'),
    declined: tk('No declined ideas.'),
    done: tk('Nothing shipped yet — stay tuned.'),
};

function PlusIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" {...props}>
            <path d="M12 5v14M5 12h14" />
        </svg>
    );
}

function VoteIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="9" />
            <path d="M12 16V8M8.5 11.5L12 8l3.5 3.5" />
        </svg>
    );
}

export default function Suggestions() {
    const t = useTrans();
    const { auth } = usePage().props;
    const [suggestions, setSuggestions] = useState(INITIAL_SUGGESTIONS);
    const [votedIds, setVotedIds] = useState(() => new Set());
    const [tab, setTab] = useState('pending');
    const [composing, setComposing] = useState(false);
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');

    const visible = useMemo(() => {
        const items = suggestions.filter((item) => item.status === tab);
        return tab === 'pending' ? [...items].sort((a, b) => b.votes - a.votes) : items;
    }, [suggestions, tab]);

    const ready = title.trim().length > 0 && description.trim().length > 0;

    function submitIdea() {
        if (!ready) return;
        const id = `s${Date.now()}`;
        setSuggestions((current) => [
            { id, author: auth.user.name, title: title.trim(), description: description.trim(), status: 'pending', votes: 1, own: true },
            ...current,
        ]);
        setVotedIds((current) => new Set(current).add(id));
        setTitle('');
        setDescription('');
        setComposing(false);
        setTab('pending');
    }

    function toggleVote(id) {
        const voted = votedIds.has(id);
        setSuggestions((current) => current.map((item) => (item.id === id ? { ...item, votes: item.votes + (voted ? -1 : 1) } : item)));
        setVotedIds((current) => {
            const next = new Set(current);
            voted ? next.delete(id) : next.add(id);
            return next;
        });
    }

    return (
        <AppShell eyebrow={t('Member')} title={t('Suggestions')} intro={t('Vote and submit ideas.')}>
            <Head title={t('Suggestions')} />

            <button
                type="button"
                onClick={() => setComposing((value) => !value)}
                className="mb-4 flex w-full items-center justify-center gap-2 rounded-xl border border-brand px-4 py-3 text-sm font-semibold text-brand transition-colors duration-150 hover:bg-brand/5"
            >
                {composing ? (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" className="h-4 w-4">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                ) : (
                    <PlusIcon className="h-4 w-4" />
                )}
                {composing ? t('Cancel') : t('Submit a Feature Idea')}
            </button>

            {composing && (
                <div className="mb-5 space-y-3 rounded-xl border border-black/5 bg-neutral-50 p-4">
                    <p className="text-sm font-bold text-neutral-900">{t('New Suggestion')}</p>
                    <div className="border-t border-black/10" />
                    <div className="space-y-1.5">
                        <label className="text-sm text-neutral-700">{t('Title')}</label>
                        <input
                            type="text"
                            value={title}
                            maxLength={80}
                            onChange={(e) => setTitle(e.target.value)}
                            placeholder={t('Title (e.g. Ear Training Mode)')}
                            className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <label className="text-sm text-neutral-700">{t('Describe Your Idea')}</label>
                        <textarea
                            value={description}
                            maxLength={500}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder={t('Describe your idea here...')}
                            rows={4}
                            className="w-full resize-none rounded-lg border border-neutral-200 px-3 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                        />
                    </div>
                    <button
                        type="button"
                        disabled={!ready}
                        onClick={submitIdea}
                        className="w-full rounded-lg bg-brand py-3 text-sm font-semibold text-white transition-opacity duration-150 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        {t('Submit Idea')}
                    </button>
                </div>
            )}

            <div className="mb-5 inline-flex w-full rounded-xl border border-neutral-200 bg-neutral-100 p-1">
                {TABS.map((item) => (
                    <button
                        key={item.value}
                        type="button"
                        onClick={() => setTab(item.value)}
                        className={`flex-1 rounded-lg py-2 text-sm font-semibold transition-colors duration-150 ${
                            tab === item.value ? 'bg-brand text-white shadow-sm' : 'text-neutral-600 hover:text-neutral-900'
                        }`}
                    >
                        {t(item.label)}
                    </button>
                ))}
            </div>

            {visible.length === 0 ? (
                <p className="py-10 text-center text-sm text-neutral-500">{t(EMPTY[tab])}</p>
            ) : (
                <div className="space-y-3">
                    {visible.map((item) => {
                        const voted = votedIds.has(item.id);
                        const show = (text) => (item.own ? text : t(text));
                        const canVote = item.status === 'pending';
                        return (
                            <div key={item.id} className="rounded-2xl border border-black/5 bg-white p-4">
                                <span className={`inline-block rounded-full border px-2.5 py-0.5 text-xs font-semibold ${BADGES[item.status]}`}>
                                    {t(BADGE_LABEL[item.status])}
                                </span>
                                <p className="mt-2.5 text-[15px] font-bold text-neutral-900">{show(item.title)}</p>
                                <p className="mt-1 text-sm leading-relaxed text-neutral-500">{show(item.description)}</p>
                                {item.reason && (
                                    <p className="mt-2 text-sm leading-relaxed text-orange-800">{t('Reason: :reason', { reason: show(item.reason) })}</p>
                                )}
                                <div className="mt-3 flex items-center justify-between border-t border-black/5 pt-3">
                                    <span className="text-sm text-neutral-600">{t('By :author', { author: item.author })}</span>
                                    <button
                                        type="button"
                                        disabled={!canVote}
                                        onClick={() => toggleVote(item.id)}
                                        className={`flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition-colors duration-150 ${
                                            voted ? 'bg-rose-50 text-brand' : 'bg-neutral-100 text-neutral-600'
                                        } ${canVote ? 'hover:opacity-80' : 'cursor-default opacity-70'}`}
                                    >
                                        {canVote && <VoteIcon className="h-3.5 w-3.5" />}
                                        {t(':count Votes', { count: item.votes })}
                                    </button>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </AppShell>
    );
}
