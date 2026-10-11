import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm } from '@inertiajs/react';

const DIFFICULTY_TONE = {
    Beginner: 'bg-emerald-50 text-emerald-700',
    Intermediate: 'bg-amber-50 text-amber-700',
    Advanced: 'bg-rose-50 text-brand',
};

function GaugeIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 15a8 8 0 0116 0" />
            <path d="M12 15l3.5-4.5" />
            <circle cx="12" cy="15" r="1.3" fill="currentColor" stroke="none" />
        </svg>
    );
}

function HelpIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="8.5" />
            <path d="M9.5 9.5a2.5 2.5 0 014.8 1c0 1.7-2.3 1.9-2.3 3.5" />
            <path d="M12 17.2v.01" />
        </svg>
    );
}

function BulbIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 18h6M10 21h4M8 11a4 4 0 118 0c0 1.8-1 2.6-2 3.5-.6.6-1 1.1-1 1.5h-2c0-.4-.4-.9-1-1.5-1-.9-2-1.7-2-3.5z" />
        </svg>
    );
}

export default function QuizPreview({ quiz, attempt }) {
    const { post, processing } = useForm();

    function start() {
        post(route('quiz.start', quiz.slug));
    }

    const resuming = attempt?.status === 'in_progress';
    const tips = [
        'Read each prompt carefully before choosing an answer.',
        'There is one correct answer for every question.',
        'Take your time — your progress is saved as you go.',
    ];

    return (
        <AppShell eyebrow={quiz.category} title={quiz.title} intro={quiz.description}>
            <Head title={quiz.title} />

            <div className="mx-auto max-w-2xl">
                <div className="grid grid-cols-2 gap-3">
                    <div className="flex items-center gap-3 rounded-2xl border border-black/5 bg-white p-4">
                        <GaugeIcon className="h-5 w-5 text-neutral-400" />
                        <div>
                            <p className="text-xs text-neutral-400">Difficulty</p>
                            <span className={`mt-0.5 inline-block rounded-full px-2 py-0.5 text-xs font-semibold ${DIFFICULTY_TONE[quiz.difficulty] ?? 'bg-neutral-100 text-neutral-600'}`}>
                                {quiz.difficulty}
                            </span>
                        </div>
                    </div>
                    <div className="flex items-center gap-3 rounded-2xl border border-black/5 bg-white p-4">
                        <HelpIcon className="h-5 w-5 text-neutral-400" />
                        <div>
                            <p className="text-xs text-neutral-400">Questions</p>
                            <p className="text-base font-bold text-neutral-900">{quiz.question_count}</p>
                        </div>
                    </div>
                </div>

                <div className="mt-4 rounded-2xl border border-black/5 bg-white p-4">
                    <div className="flex items-center gap-2 text-neutral-400">
                        <BulbIcon className="h-4 w-4" />
                        <p className="text-xs font-semibold">Tips</p>
                    </div>
                    <ul className="mt-2 space-y-1.5">
                        {tips.map((tip) => (
                            <li key={tip} className="text-sm text-neutral-600">
                                &bull; {tip}
                            </li>
                        ))}
                    </ul>
                </div>

                {attempt?.status === 'completed' && (
                    <div className="mt-4 flex items-center justify-between rounded-2xl bg-emerald-50 px-4 py-3">
                        <p className="text-sm font-semibold text-emerald-800">Last attempt: {attempt.score}% correct</p>
                        <Link href={route('quiz.results', quiz.slug)} className="text-sm font-semibold text-emerald-700 hover:underline">
                            View results
                        </Link>
                    </div>
                )}

                {resuming && (
                    <div className="mt-4 rounded-2xl bg-rose-50 px-4 py-3">
                        <p className="text-sm font-semibold text-brand">
                            In progress — question {attempt.stopped_at_question} of {quiz.question_count}
                        </p>
                    </div>
                )}

                <button
                    type="button"
                    disabled={processing}
                    onClick={start}
                    className="mt-6 w-full rounded-xl bg-brand py-3.5 text-sm font-bold text-white transition-opacity duration-150 disabled:opacity-60"
                >
                    {resuming ? 'Continue Quiz' : attempt?.status === 'completed' ? 'Retry Quiz' : 'Start Quiz'}
                </button>
            </div>
        </AppShell>
    );
}
