import AppShell from '@/Layouts/AppShell';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { useTrans } from '@/lib/i18n';

// Sample pairs — there's no per-question mistake tracking on the web yet
// (only each attempt's final correct/total tally), so this can't be computed
// from real data. Drawn from actual answer choices in the seeded quizzes
// (Find the Note, Diatonic Intervals) so it at least reads true to the app.
const confusions = [
    { picked: 'Eb', answer: 'E', count: 4 },
    { picked: 'F', answer: 'F#', count: 3 },
    { picked: 'A', answer: 'G#', count: 3 },
    { picked: 'Bb', answer: 'B', count: 2 },
    { picked: 'C', answer: 'C#', count: 1 },
];

function RefreshIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 12a8 8 0 0113.7-5.7L20 8M20 4v4h-4" />
            <path d="M20 12a8 8 0 01-13.7 5.7L4 16M4 20v-4h4" />
        </svg>
    );
}

function HeadsetIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 13v-1a8 8 0 0116 0v1" />
            <rect x="3" y="13" width="4" height="6" rx="1.5" />
            <rect x="17" y="13" width="4" height="6" rx="1.5" />
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

function PlayTriangleIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" stroke="none" {...props}>
            <path d="M8 5.5v13l11-6.5z" />
        </svg>
    );
}

function RestartIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M3 12a9 9 0 1 1 2.6 6.3" />
            <path d="M3 7v5h5" />
        </svg>
    );
}

// Visual twin of QuizCategory.jsx's ReferenceAudioModal (same dark panel,
// headset header, restart/play/scrub transport row) so "listen and compare"
// feels like one consistent interaction across the app — but swapping its
// track-list switcher for a two-way "What you picked" / "Correct answer"
// tab, since a mistake only ever has those two clips to compare. Design
// only for now: the transport is inert (no audio wired up yet — that's the
// separate backend task in docs/mistakes-feature-spec.md).
function MistakeListenModal({ item, onClose }) {
    const t = useTrans();
    const [tab, setTab] = useState('picked');
    const active = tab === 'picked' ? { label: item.picked, color: 'text-brand' } : { label: item.answer, color: 'text-emerald-400' };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" onClick={(e) => e.target === e.currentTarget && onClose()}>
            <div className="w-full max-w-lg rounded-2xl bg-neutral-900 p-8 text-white">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2 text-white/50">
                        <HeadsetIcon className="h-[18px] w-[18px]" />
                        <p className="text-xs font-semibold">{t('Compare the mistake')}</p>
                    </div>
                    <button type="button" onClick={onClose} aria-label={t('Close')} className="text-white/50 hover:text-white">
                        <CloseIcon className="h-5 w-5" />
                    </button>
                </div>

                <div className="mt-5 flex items-center gap-3 rounded-xl bg-white/5 px-3 py-2.5 opacity-60">
                    <span className="shrink-0 text-white/70">
                        <RestartIcon className="h-5 w-5" />
                    </span>
                    <span className="shrink-0 text-white">
                        <PlayTriangleIcon className="h-5 w-5" />
                    </span>
                    <input type="range" min={0} max={100} value={0} disabled readOnly className="h-1.5 w-full accent-brand" />
                </div>

                <p className="mt-6 text-center text-sm text-white/50">{t('Listen and compare what you picked with the correct answer.')}</p>
                <p className={`font-display mt-3 text-center text-4xl font-semibold ${active.color}`}>{active.label}</p>

                <div className="mt-5 flex flex-wrap items-center justify-center gap-2">
                    <button
                        type="button"
                        onClick={() => setTab('picked')}
                        className={`rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150 ${
                            tab === 'picked' ? 'bg-brand text-white' : 'bg-white/10 text-white/70 hover:bg-white/15'
                        }`}
                    >
                        {t('What you picked')}
                    </button>
                    <button
                        type="button"
                        onClick={() => setTab('correct')}
                        className={`rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150 ${
                            tab === 'correct' ? 'bg-emerald-600 text-white' : 'bg-white/10 text-white/70 hover:bg-white/15'
                        }`}
                    >
                        {t('Correct answer')}
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function Mistakes() {
    const t = useTrans();
    const total = confusions.reduce((sum, item) => sum + item.count, 0);
    const [openItem, setOpenItem] = useState(null);

    return (
        <AppShell eyebrow={t('Member')} title={t('Mistakes')} intro={t('Your most-confused pairs, ranked by how often it happens.')}>
            <Head title={t('Mistakes')} />

            <section className="rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <h2 className="text-lg font-semibold">{t('Most confused')}</h2>

                {confusions.length === 0 ? (
                    <div className="mt-4 flex flex-col items-center rounded-2xl border border-dashed border-neutral-300 px-5 py-10 text-center">
                        <span className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                            <RefreshIcon className="h-5 w-5" />
                        </span>
                        <p className="mt-3 text-sm font-medium text-neutral-700">{t('No mistakes yet')}</p>
                        <p className="mt-1 max-w-xs text-sm text-neutral-500">
                            {t('Finish a few quizzes and the pairs you keep mixing up will show up here.')}
                        </p>
                    </div>
                ) : (
                    <>
                        <ul className="mt-4 space-y-3">
                            {confusions.map((item) => (
                                <li
                                    key={`${item.picked}-${item.answer}`}
                                    className="flex items-center justify-between gap-3 rounded-2xl bg-[#FBEDEC] px-4 py-3.5 text-sm text-neutral-800"
                                >
                                    <div className="min-w-0">
                                        {t('You picked :picked when it was :answer')
                                            .split(/(:picked|:answer)/)
                                            .map((part, i) =>
                                                part === ':picked' ? (
                                                    <span key={i} className="font-semibold text-brand">{item.picked}</span>
                                                ) : part === ':answer' ? (
                                                    <span key={i} className="font-semibold text-emerald-700">{item.answer}</span>
                                                ) : (
                                                    part
                                                ),
                                            )}
                                        <p className="mt-0.5 text-neutral-500">
                                            {item.count === 1 ? t(':count time', { count: item.count }) : t(':count times', { count: item.count })}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() => setOpenItem(item)}
                                        className="flex shrink-0 items-center gap-1.5 rounded-full bg-neutral-900 px-3.5 py-1.5 text-xs font-semibold text-white"
                                    >
                                        <HeadsetIcon className="h-3.5 w-3.5" />
                                        {t('Listen')}
                                    </button>
                                </li>
                            ))}
                        </ul>

                        <button
                            type="button"
                            title={t('Coming soon')}
                            className="mt-5 flex w-full cursor-default items-center justify-center gap-2 rounded-xl bg-neutral-900 px-5 py-3.5 text-sm font-semibold text-white"
                        >
                            <RefreshIcon className="h-4 w-4" />
                            {t('Review mistakes (:total)', { total })}
                        </button>
                    </>
                )}
            </section>

            {openItem && <MistakeListenModal item={openItem} onClose={() => setOpenItem(null)} />}
        </AppShell>
    );
}
