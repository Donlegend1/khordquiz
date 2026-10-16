import AnswerBuilder, { answerLabelFor } from '@/Components/Quiz/AnswerBuilder';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

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

function PlayTriangleIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" stroke="none" {...props}>
            <path d="M8 5.5v13l11-6.5z" />
        </svg>
    );
}

function PauseIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" stroke="none" {...props}>
            <rect x="6" y="5" width="4" height="14" rx="1" />
            <rect x="14" y="5" width="4" height="14" rx="1" />
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

function ExitIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 21H6a2 2 0 01-2-2V5a2 2 0 012-2h3" />
            <path d="M16 17l5-5-5-5M21 12H9" />
        </svg>
    );
}

// Matches app/practice.tsx's cancel-quiz confirmation (ConfirmSheet with icon="exit-outline").
function CancelQuizModal({ current, onCancel, onClose, open, total }) {
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/40 p-4" onClick={onClose}>
            <div className="w-full max-w-sm rounded-2xl bg-white p-6" onClick={(e) => e.stopPropagation()}>
                <div className="flex flex-col items-center text-center">
                    <span className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-700">
                        <ExitIcon className="h-5 w-5" />
                    </span>
                    <p className="mt-3 text-base font-bold text-neutral-900">Cancel quiz?</p>
                    <p className="mt-1 text-sm text-neutral-500">
                        You&apos;re on question {current} of {total}. Your answers in this set won&apos;t be saved.
                    </p>
                </div>

                <div className="mt-5 space-y-2">
                    <button
                        type="button"
                        onClick={onClose}
                        className="w-full rounded-xl bg-neutral-900 py-3 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90"
                    >
                        Keep Going
                    </button>
                    <button
                        type="button"
                        onClick={onCancel}
                        className="block w-full rounded-xl py-3 text-center text-sm font-bold text-brand hover:bg-rose-50"
                    >
                        Cancel Quiz
                    </button>
                </div>
            </div>
        </div>
    );
}

// Fixed, decorative bar heights — not a real waveform render of the audio
// (that would need actual waveform analysis), just visual texture around the
// play button that holds steady instead of jumping around on every question.
const WAVEFORM_BARS = [8, 14, 10, 18, 22, 13, 9, 16, 20, 11, 15, 24, 12, 9, 17, 21, 14, 10, 18, 13, 8, 16, 22, 11, 9];

// The real app has no recorded audio behind this button on most questions — every
// quiz still shows this same "listen" design (app/practice.tsx's SoundRingButton),
// it's just decorative there until a real clip exists. Matches that: with a real
// audio_path it actually plays; without one, it's the same design, just inert.
function AudioStimulus({ questionKey, src }) {
    const audioRef = useRef(null);
    const [playing, setPlaying] = useState(false);

    // A new question swaps the <audio> element's src — reset so the old
    // question's playing state doesn't linger on the new one.
    useEffect(() => {
        setPlaying(false);
    }, [questionKey]);

    function toggle() {
        if (!src) {
            setPlaying((current) => !current);
            return;
        }
        if (!audioRef.current) return;
        if (playing) {
            audioRef.current.pause();
        } else {
            audioRef.current.play();
        }
    }

    return (
        <div className="rounded-2xl bg-neutral-50 px-6 py-6 text-center">
            {src && (
                <audio
                    key={src}
                    ref={audioRef}
                    src={src}
                    onPlay={() => setPlaying(true)}
                    onPause={() => setPlaying(false)}
                    onEnded={() => setPlaying(false)}
                    className="hidden"
                />
            )}

            <div className="flex flex-col items-center gap-5 rounded-xl px-4 py-3.5">
                <button
                    type="button"
                    onClick={toggle}
                    aria-label={playing ? 'Pause' : 'Play'}
                    className="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-brand text-white transition-colors duration-150 hover:bg-brand-dark"
                >
                    {playing ? <PauseIcon className="h-9 w-9" /> : <PlayTriangleIcon className="h-9 w-9 translate-x-[2px]" />}
                </button>

                <div className="flex h-8 w-full items-center justify-center gap-[3px]">
                    {WAVEFORM_BARS.map((height, index) => (
                        <span
                            key={index}
                            className={`w-1 shrink-0 rounded-full transition-colors duration-150 ${playing ? 'bg-brand/50' : 'bg-neutral-300'}`}
                            style={{ height: `${height}px` }}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}

export default function QuizPlay({ quiz, progress, question, finished, feedback }) {
    const { data, setData, post, processing, reset } = useForm({ choice: null });
    const [advancing, setAdvancing] = useState(false);
    const [cancelling, setCancelling] = useState(false);

    // A fresh question (no feedback yet) clears whatever was selected for the last one.
    useEffect(() => {
        if (!feedback) reset();
        setAdvancing(false);
    }, [feedback, progress.current]);

    // Pressing the browser's back button mid-quiz should ask to cancel, same as the
    // in-card button — not silently drop the member back onto the quiz list. Pushing a
    // dummy history entry once on mount means the first "back" never actually leaves; it
    // just pops that entry, which we immediately replace and show the confirmation instead.
    // Keyed on whether there's a question at all (not the question itself), so it arms once
    // per quiz attempt rather than piling up a new history entry on every answer submitted.
    const hasQuestion = Boolean(question);
    useEffect(() => {
        if (!hasQuestion) return undefined;

        window.history.pushState(null, '', window.location.href);
        const onPopState = () => {
            window.history.pushState(null, '', window.location.href);
            setCancelling(true);
        };
        window.addEventListener('popstate', onPopState);
        return () => window.removeEventListener('popstate', onPopState);
    }, [hasQuestion]);

    function submit() {
        if (!data.choice || processing || feedback) return;
        // replace: true keeps every answer/next step as a single history entry for the whole
        // quiz, so the back-button trap above only ever needs to catch one real "back" press.
        post(route('quiz.answer', quiz.slug), { preserveScroll: true, replace: true });
    }

    function next() {
        if (advancing) return;
        setAdvancing(true);
        router.post(route('quiz.next', quiz.slug), {}, { preserveScroll: true, replace: true, onError: () => setAdvancing(false) });
    }

    const answeredSoFar = Math.max(0, progress.current - 1);
    const percent = progress.total > 0 ? Math.round((answeredSoFar / progress.total) * 100) : 0;
    const isLastQuestion = progress.current >= progress.total;

    return (
        <div className="min-h-screen bg-[#F6F1EC] px-4 py-8 text-neutral-900 sm:px-8 sm:py-10">
            <Head title={quiz.title} />

            <div className="mx-auto max-w-4xl">
                <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand">{quiz.category}</p>
                <h1 className="mt-1 text-3xl font-bold tracking-tight text-neutral-900">{quiz.title}</h1>
                {question && (
                    <div className="mt-6 rounded-2xl border border-black/5 bg-white p-6 sm:p-7">
                        <div className="flex items-center justify-between gap-4">
                            <button
                                type="button"
                                onClick={() => setCancelling(true)}
                                className="inline-flex items-center gap-1.5 rounded-full border border-black/10 bg-white px-3.5 py-2 text-sm font-bold text-neutral-900 transition-colors duration-150 hover:border-black/20"
                            >
                                <CloseIcon className="h-3.5 w-3.5" />
                                Cancel Quiz
                            </button>
                            <p className="shrink-0 text-sm text-neutral-400">
                                Question {progress.current} of {progress.total}
                            </p>
                        </div>
                        <div className="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-neutral-100">
                            <div className="h-full rounded-full bg-neutral-900 transition-all duration-300" style={{ width: `${percent}%` }} />
                        </div>
                        {answeredSoFar > 0 && (
                            <p className="mt-2 text-xs text-neutral-400">
                                {progress.correct} correct of {answeredSoFar} answered
                            </p>
                        )}

                        <p className="mt-6 text-center text-lg font-bold leading-snug text-neutral-900">{question.prompt}</p>

                        <div className="mt-5">
                            <AudioStimulus questionKey={progress.current} src={question.audio_path} />
                        </div>

                        <p className="mt-7 text-xs font-semibold text-neutral-400">
                            {answerLabelFor(quiz.category, quiz.title)}
                        </p>

                        <div className="mt-2.5">
                            <AnswerBuilder
                                answered={Boolean(feedback)}
                                chosen={feedback?.chosen}
                                correctAnswer={feedback?.correctAnswer}
                                quiz={quiz}
                                choices={question.choices}
                                resetToken={progress.current}
                                onChange={(value) => setData('choice', value)}
                            />
                        </div>

                        {feedback && (
                            <div className={`mt-5 flex items-center gap-3 rounded-2xl p-4 ${feedback.correct ? 'bg-emerald-50' : 'bg-rose-50'}`}>
                                <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${feedback.correct ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-brand'}`}>
                                    {feedback.correct ? <CheckCircleIcon className="h-4.5 w-4.5" /> : <XCircleIcon className="h-4.5 w-4.5" />}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className={`text-sm font-extrabold ${feedback.correct ? 'text-emerald-800' : 'text-brand'}`}>
                                        {feedback.correct ? 'You are Correct!' : 'Incorrect Answer'}
                                    </p>
                                    {!feedback.correct && (
                                        <p className="text-sm text-neutral-600">
                                            The correct answer is <span className="font-semibold text-emerald-700">{feedback.correctAnswer}</span>.
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}

                        <div className="mt-7 flex items-center justify-end gap-3">
                            {!feedback ? (
                                <button
                                    type="button"
                                    disabled={!data.choice || processing}
                                    onClick={submit}
                                    className="rounded-xl bg-brand px-8 py-3 text-sm font-bold text-white transition-opacity duration-150 disabled:cursor-not-allowed disabled:bg-neutral-200 disabled:text-neutral-400"
                                >
                                    Submit Answer
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    disabled={advancing}
                                    onClick={next}
                                    className="rounded-xl bg-neutral-900 px-8 py-3 text-sm font-bold text-white transition-opacity duration-150 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {isLastQuestion ? 'Finish' : 'Next Question'}
                                </button>
                            )}
                        </div>
                    </div>
                )}

                {!question && finished && (
                    <div className="mt-6 rounded-2xl border border-black/5 bg-white p-8 text-center">
                        <p className="text-base font-bold text-neutral-900">This quiz is already finished</p>
                        <Link
                            href={route('quiz.results', quiz.slug)}
                            className="mt-5 inline-block rounded-xl bg-neutral-900 px-6 py-3 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90"
                        >
                            See Results
                        </Link>
                    </div>
                )}

                {!question && !finished && (
                    <div className="mt-6 rounded-2xl border border-black/5 bg-white p-8 text-center">
                        <p className="text-base font-bold text-neutral-900">No questions yet</p>
                        <p className="mt-2 text-sm text-neutral-500">
                            This quiz doesn&apos;t have any questions added yet, so there&apos;s nothing to answer right now.
                        </p>
                        <Link
                            href={route('quiz.category', { category: quiz.categorySlug, quiz: quiz.slug })}
                            className="mt-5 inline-block rounded-xl bg-neutral-900 px-6 py-3 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90"
                        >
                            Back to quiz
                        </Link>
                    </div>
                )}
            </div>

            <CancelQuizModal
                current={progress.current}
                onCancel={() => router.post(route('quiz.cancel', quiz.slug))}
                onClose={() => setCancelling(false)}
                open={cancelling}
                total={progress.total}
            />
        </div>
    );
}
