import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function CheckCircleIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="8.5" />
            <path d="M8.5 12.5l2.3 2.3L15.8 9.5" />
        </svg>
    );
}

function XCircleIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="8.5" />
            <path d="M9 9l6 6M15 9l-6 6" />
        </svg>
    );
}

export default function QuizPlay({ quiz, progress, question, finished, feedback }) {
    const [showingFeedback, setShowingFeedback] = useState(Boolean(feedback));
    const { data, setData, post, processing, reset } = useForm({ choice: null });

    useEffect(() => {
        setShowingFeedback(Boolean(feedback));
        reset();
    }, [feedback]);

    function submit() {
        if (!data.choice || processing) return;
        post(route('quiz.answer', quiz.slug), { preserveScroll: true });
    }

    const answeredSoFar = Math.max(0, progress.current - 1);
    const percent = progress.total > 0 ? Math.round((answeredSoFar / progress.total) * 100) : 0;

    return (
        <AppShell
            eyebrow={quiz.category}
            title={quiz.title}
            intro={finished && showingFeedback ? 'Quiz complete' : `Question ${progress.current} of ${progress.total}`}
        >
            <Head title={quiz.title} />

            <div className="mx-auto max-w-2xl">
                <div className="mb-6 h-2 w-full overflow-hidden rounded-full bg-neutral-100">
                    <div className="h-full rounded-full bg-brand transition-all duration-300" style={{ width: `${finished ? 100 : percent}%` }} />
                </div>

                {showingFeedback && feedback && (
                    <div className={`mb-6 rounded-2xl p-5 ${feedback.correct ? 'bg-emerald-50' : 'bg-rose-50'}`}>
                        <div className="flex items-center gap-3">
                            <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${feedback.correct ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-brand'}`}>
                                {feedback.correct ? <CheckCircleIcon className="h-5 w-5" /> : <XCircleIcon className="h-5 w-5" />}
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className={`text-base font-extrabold ${feedback.correct ? 'text-emerald-800' : 'text-brand'}`}>
                                    {feedback.correct ? 'Correct!' : 'Not quite'}
                                </p>
                                {!feedback.correct && (
                                    <p className="text-sm text-neutral-600">
                                        The correct answer was <span className="font-semibold text-neutral-900">{feedback.correctAnswer}</span>.
                                    </p>
                                )}
                            </div>
                        </div>

                        {finished ? (
                            <Link
                                href={route('quiz.results', quiz.slug)}
                                className="mt-4 block w-full rounded-xl bg-neutral-900 py-3 text-center text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90"
                            >
                                See Results
                            </Link>
                        ) : (
                            <button
                                type="button"
                                onClick={() => setShowingFeedback(false)}
                                className="mt-4 w-full rounded-xl bg-neutral-900 py-3 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90"
                            >
                                Continue
                            </button>
                        )}
                    </div>
                )}

                {!showingFeedback && question && (
                    <>
                        <div className="rounded-2xl border border-black/5 bg-white p-5">
                            <p className="text-[11px] font-semibold uppercase tracking-wide text-neutral-400">Score so far</p>
                            <p className="mt-0.5 text-sm text-neutral-600">
                                <span className="font-bold text-neutral-900">{progress.correct}</span> correct of {answeredSoFar} answered
                            </p>
                        </div>

                        {question.audio_path && (
                            <audio controls src={question.audio_path} className="mt-4 w-full">
                                Your browser does not support audio playback.
                            </audio>
                        )}

                        <p className="mt-6 text-lg font-bold leading-snug text-neutral-900">{question.prompt}</p>

                        <div className="mt-5 space-y-2.5">
                            {question.choices.map((choice) => {
                                const active = data.choice === choice;
                                return (
                                    <button
                                        key={choice}
                                        type="button"
                                        onClick={() => setData('choice', choice)}
                                        className={`flex w-full items-center gap-3 rounded-2xl border px-4 py-3.5 text-left text-sm font-semibold transition-colors duration-150 ${
                                            active ? 'border-brand bg-rose-50 text-brand' : 'border-black/5 bg-white text-neutral-900 hover:border-neutral-200'
                                        }`}
                                    >
                                        <span
                                            className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 ${
                                                active ? 'border-brand bg-brand' : 'border-neutral-300'
                                            }`}
                                        >
                                            {active && <span className="h-2 w-2 rounded-full bg-white" />}
                                        </span>
                                        {choice}
                                    </button>
                                );
                            })}
                        </div>

                        <button
                            type="button"
                            disabled={!data.choice || processing}
                            onClick={submit}
                            className="mt-6 w-full rounded-xl bg-brand py-3.5 text-sm font-bold text-white transition-opacity duration-150 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            Submit Answer
                        </button>
                    </>
                )}
            </div>
        </AppShell>
    );
}
