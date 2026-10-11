import AppShell from '@/Layouts/AppShell';
import { Head } from '@inertiajs/react';

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

export default function Mistakes() {
    const total = confusions.reduce((sum, item) => sum + item.count, 0);

    return (
        <AppShell eyebrow="Member" title="Mistakes" intro="Your most-confused pairs, ranked by how often it happens.">
            <Head title="Mistakes" />

            <section className="rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <h2 className="text-lg font-semibold">Most confused</h2>

                {confusions.length === 0 ? (
                    <div className="mt-4 flex flex-col items-center rounded-2xl border border-dashed border-neutral-300 px-5 py-10 text-center">
                        <span className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                            <RefreshIcon className="h-5 w-5" />
                        </span>
                        <p className="mt-3 text-sm font-medium text-neutral-700">No mistakes yet</p>
                        <p className="mt-1 max-w-xs text-sm text-neutral-500">
                            Finish a few quizzes and the pairs you keep mixing up will show up here.
                        </p>
                    </div>
                ) : (
                    <>
                        <ul className="mt-4 space-y-3">
                            {confusions.map((item) => (
                                <li
                                    key={`${item.picked}-${item.answer}`}
                                    className="rounded-2xl bg-[#FBEDEC] px-4 py-3.5 text-sm text-neutral-800"
                                >
                                    You picked <span className="font-semibold text-brand">{item.picked}</span> when it was{' '}
                                    <span className="font-semibold text-emerald-700">{item.answer}</span>
                                    <p className="mt-0.5 text-neutral-500">
                                        {item.count} {item.count === 1 ? 'time' : 'times'}
                                    </p>
                                </li>
                            ))}
                        </ul>

                        <button
                            type="button"
                            title="Coming soon"
                            className="mt-5 flex w-full cursor-default items-center justify-center gap-2 rounded-xl bg-neutral-900 px-5 py-3.5 text-sm font-semibold text-white"
                        >
                            <RefreshIcon className="h-4 w-4" />
                            Review mistakes ({total})
                        </button>
                    </>
                )}
            </section>
        </AppShell>
    );
}
