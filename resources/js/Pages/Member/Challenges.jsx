import AppShell from '@/Layouts/AppShell';
import { Head } from '@inertiajs/react';

// Sample rows — there's no challenges table on the web yet, so this can't be
// computed from real data. Quiz titles are drawn from the real seeded quizzes
// so it at least reads true to the app.
const yourTurn = [{ name: 'Jordan M.', meta: 'Find the Note · 4 questions' }];
const pending = [{ name: 'Chioma A.', meta: 'Diatonic Intervals · 4 questions' }];
const completed = [
    { name: 'David E.', meta: 'Cadences · 4 questions', won: true, result: 'You won 3–2' },
    { name: 'Tunde B.', meta: 'Basic Triads · Level 1 · 4 questions', won: false, result: 'You lost 1–3' },
];

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function BoltIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
            <path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" />
        </svg>
    );
}

function ChallengeRow({ name, meta, badge }) {
    return (
        <li className="flex items-center gap-3 rounded-2xl border border-black/5 bg-white px-5 py-4">
            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand/10 text-xs font-semibold text-brand">
                {initials(name)}
            </span>
            <div className="min-w-0 flex-1">
                <p className="font-medium text-neutral-900">{name}</p>
                <p className="mt-0.5 text-sm text-neutral-500">{meta}</p>
            </div>
            {badge}
        </li>
    );
}

function ChallengeGroup({ title, items, badge }) {
    if (items.length === 0) return null;

    return (
        <section>
            <h2 className="text-lg font-semibold">{title}</h2>
            <ul className="mt-3 space-y-3">
                {items.map((item) => (
                    <ChallengeRow key={item.name} {...item} badge={badge(item)} />
                ))}
            </ul>
        </section>
    );
}

export default function Challenges() {
    const hasAny = yourTurn.length > 0 || pending.length > 0 || completed.length > 0;

    return (
        <AppShell eyebrow="Member" title="Challenges" intro="Head-to-head quizzes with other students.">
            <Head title="Challenges" />

            {!hasAny ? (
                <div className="flex flex-col items-center rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-12 text-center">
                    <span className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                        <BoltIcon className="h-5 w-5" />
                    </span>
                    <p className="mt-3 text-sm font-medium text-neutral-700">No challenges yet</p>
                    <p className="mt-1 max-w-xs text-sm text-neutral-500">
                        Finish a quiz and send it as a challenge to a friend — it&apos;ll show up here.
                    </p>
                </div>
            ) : (
                <div className="space-y-8">
                    <ChallengeGroup
                        title="Your turn"
                        items={yourTurn}
                        badge={() => (
                            <span className="shrink-0 rounded-full bg-brand px-3 py-1 text-xs font-semibold text-white">
                                Play now
                            </span>
                        )}
                    />
                    <ChallengeGroup
                        title="Pending"
                        items={pending}
                        badge={() => (
                            <span className="shrink-0 rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-500">
                                Waiting on them
                            </span>
                        )}
                    />
                    <ChallengeGroup
                        title="Completed"
                        items={completed}
                        badge={(item) => (
                            <span className={`shrink-0 text-sm font-medium ${item.won ? 'text-emerald-600' : 'text-neutral-500'}`}>
                                {item.result}
                            </span>
                        )}
                    />
                </div>
            )}
        </AppShell>
    );
}
