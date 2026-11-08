import ReportQuestion from '@/Components/Quiz/ReportQuestion';
import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import AudioStimulus from '@/Components/Quiz/AudioStimulus';
import AnswerBuilder, { answerTypeFor, baseChordFor } from '@/Components/Quiz/AnswerBuilder';
import { reviewAnswerLabel } from '@/lib/reviewAnswers';

function CheckIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 12l5 5L19 7" />
        </svg>
    );
}

function XIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 6l12 12M18 6L6 18" />
        </svg>
    );
}

function HistoryButton() {
    return <Link href={route('quiz.history')} className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-3 text-sm font-semibold text-neutral-700 transition-colors hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
        <svg aria-hidden="true" className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M3 11a9 9 0 1 1 2.6 7M3 4v7h7M12 7v5l3 2" /></svg>
        Attempt history
    </Link>;
}

function AnswerReview({ quiz, records, keyboardAudio, sessionId, onClose }) {
    const [index, setIndex] = useState(0);
    const headingRef = useRef(null);
    const record = records[index];
    const type = answerTypeFor(quiz.category, quiz.title);
    const baseChord = baseChordFor(quiz.category, quiz.title);

    useEffect(() => {
        headingRef.current?.focus();
    }, [index]);

    if (!record) return null;

    return (
        <section aria-label="Answer review">
            <nav aria-label="Review navigation" className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <button type="button" onClick={onClose} className="inline-flex min-h-11 items-center gap-2 rounded-lg px-2 text-sm font-semibold text-neutral-600 hover:bg-neutral-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700">
                    <span aria-hidden="true">←</span> Back to results
                </button>
                <HistoryButton />
            </nav>
            <div className="rounded-2xl border border-black/5 bg-white p-5 sm:p-7">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 ref={headingRef} tabIndex={-1} className="scroll-mt-6 text-sm font-bold text-neutral-600 outline-none">Question {record.position} of {records.length}</h2>
                    <span className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold ${record.correct ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}`}>
                        {record.correct ? <CheckIcon aria-hidden="true" className="h-3.5 w-3.5" /> : <XIcon aria-hidden="true" className="h-3.5 w-3.5" />}
                        {record.correct ? 'You got this right' : 'Incorrect answer'}
                    </span>
                </div>
                <details className="mt-4 border-b border-neutral-100 pb-4">
                    <summary className="cursor-pointer text-xs font-semibold text-sky-600 hover:text-sky-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-700">Jump to a question</summary>
                    <div className="mt-3 grid grid-cols-5 gap-2 sm:grid-cols-8">
                        {records.map((item, itemIndex) => (
                            <button
                                key={item.position}
                                type="button"
                                aria-label={`Question ${item.position}: ${item.correct ? 'correct' : 'incorrect'}`}
                                aria-current={itemIndex === index ? 'step' : undefined}
                                onClick={() => setIndex(itemIndex)}
                                className={`flex min-h-11 items-center justify-center gap-1 rounded-lg border text-xs font-bold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900 ${item.correct ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'} ${itemIndex === index ? 'ring-2 ring-neutral-900 ring-offset-2' : ''}`}
                            >
                                {item.position}{item.correct ? <CheckIcon aria-hidden="true" className="h-3 w-3" /> : <XIcon aria-hidden="true" className="h-3 w-3" />}
                            </button>
                        ))}
                    </div>
                </details>
                <h3 className="mt-6 text-center text-lg font-extrabold text-neutral-900">{record.prompt}</h3>
                <div className="mt-5">
                    <AudioStimulus key={record.position} questionKey={record.position} src={record.audio_url} notes={record.notes} />
                </div>
                <div className="my-6">
                    {!record.correct && (
                        <div className="mb-4 min-w-0 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
                            <p className="text-xs font-semibold">Your answer</p>
                            <p className="mt-1 break-words text-sm font-bold">{reviewAnswerLabel(record.picked, type)}</p>
                        </div>
                    )}
                    <p className="text-sm font-bold text-emerald-700">{record.correct ? 'Your answer is correct' : 'Correct answer'}</p>
                </div>
                {baseChord && <p className="mb-3 text-sm font-bold text-neutral-700">{baseChord}</p>}
                <AnswerBuilder
                    key={record.position}
                    review
                    answered
                    quiz={quiz}
                    choices={record.choices ?? []}
                    chosen={record.picked}
                    correctAnswer={record.answer}
                    keyboardAudio={keyboardAudio}
                    referenceNote={record.reference_note}
                    answerLength={record.answer_length}
                    resetToken={record.position}
                />
                <div className="mt-7 flex gap-3 border-t border-neutral-100 pt-5">
                    <button type="button" disabled={index === 0} onClick={() => setIndex((value) => value - 1)} className="min-h-11 flex-1 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-bold text-neutral-700 hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand disabled:cursor-default disabled:opacity-35">Previous</button>
                    <button type="button" onClick={() => index === records.length - 1 ? onClose() : setIndex((value) => value + 1)} className="min-h-11 flex-1 rounded-xl bg-neutral-900 px-4 text-sm font-bold text-white hover:bg-neutral-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900">{index === records.length - 1 ? 'Back to results' : 'Next'}</button>
                </div>
                <ReportQuestion key={`report-${record.position}`} quizSlug={quiz.slug} questionId={record.question_id} sessionId={sessionId} />
            </div>
        </section>
    );
}

function PeopleIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="9" cy="8" r="3" />
            <path d="M3 20c.9-3 3-4.8 6-4.8s5.1 1.8 6 4.8" />
            <path d="M15.5 8.5a2.5 2.5 0 110-5" />
            <path d="M17 15.3c2 .5 3.3 1.9 4 4.7" />
        </svg>
    );
}

function SendIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" stroke="none" {...props}>
            <path d="M3 11l18-8-8 18-2.5-7.5L3 11z" />
        </svg>
    );
}

// Same bands and wording as the mobile app's quiz-results screen (app/quiz-results.tsx).
const scoreColor = (value) => (value <= 30 ? '#E00000' : value <= 65 ? '#D9A400' : '#1E8E3E');
const headline = (value) => (value >= 90 ? 'Outstanding!' : value >= 65 ? 'Nice work!' : value >= 30 ? 'Good effort' : 'Keep practising');

function ScoreRing({ correct, total, percent }) {
    const size = 148;
    const stroke = 12;
    const radius = (size - stroke) / 2;
    const circumference = 2 * Math.PI * radius;
    const color = scoreColor(percent);

    return (
        <div className="relative flex items-center justify-center" style={{ height: size, width: size }}>
            <svg height={size} width={size} style={{ transform: 'rotate(-90deg)' }}>
                <circle cx={size / 2} cy={size / 2} fill="none" r={radius} stroke="#F1F1F1" strokeWidth={stroke} />
                <circle
                    cx={size / 2}
                    cy={size / 2}
                    fill="none"
                    r={radius}
                    stroke={color}
                    strokeDasharray={circumference}
                    strokeDashoffset={circumference * (1 - (total ? correct / total : 0))}
                    strokeLinecap="round"
                    strokeWidth={stroke}
                />
            </svg>
            <div className="absolute flex flex-col items-center">
                <span className="text-4xl font-extrabold tracking-tight text-neutral-900">{percent}%</span>
                <span className="text-sm font-semibold tabular-nums text-neutral-500">{correct} of {total}</span>
            </div>
        </div>
    );
}

function Stat({ color, label, value }) {
    return (
        <div className="flex flex-1 flex-col items-center gap-0.5 rounded-2xl bg-neutral-50 py-3">
            <span className="text-xl font-extrabold tabular-nums" style={{ color }}>{value}</span>
            <span className="text-xs font-semibold text-neutral-500">{label}</span>
        </div>
    );
}

export default function QuizResults({ quiz, result, review, review_keyboard_audio = {}, initial_review = false, history_notice, history_session_id }) {
    const { post, processing } = useForm({ sound: result.sound });
    const [reviewing, setReviewing] = useState(Boolean(initial_review && review?.length));

    return (
        <AppShell eyebrow={quiz.category} title={quiz.title} intro={reviewing ? 'Review your answers' : 'Quiz results'}>
            <Head title={`${quiz.title} — Results`} />

            <div className={`mx-auto ${reviewing ? 'max-w-3xl' : 'max-w-xl'}`}>
                {!reviewing && <nav aria-label="Results navigation" className="mb-4 flex justify-end"><HistoryButton /></nav>}
                {history_notice && <p className="mb-5 rounded-xl border border-neutral-200 bg-white p-4 text-sm leading-relaxed text-neutral-600">{history_notice}</p>}
                {reviewing ? <AnswerReview quiz={quiz} records={review ?? []} keyboardAudio={review_keyboard_audio} sessionId={history_session_id} onClose={() => setReviewing(false)} /> : <>

                <div className="flex flex-col items-center rounded-2xl border border-black/5 bg-white px-6 py-8 text-center">
                    <ScoreRing correct={result.correct} total={result.total} percent={result.percent} />
                    <p className="mt-3.5 text-xl font-extrabold text-neutral-900">{headline(result.percent)}</p>
                    <p className="mt-1 text-sm text-neutral-500">
                        {quiz.title} · {quiz.category}
                    </p>
                </div>

                <div className="mt-4 flex gap-2.5">
                    <Stat color="#1E8E3E" label="Correct" value={result.correct} />
                    <Stat color="#E00000" label="Wrong" value={result.total - result.correct} />
                    <Stat color="#171717" label="Questions" value={result.total} />
                </div>

                <div className="mt-4 rounded-2xl border border-black/5 bg-white p-4">
                    <div className="flex items-center gap-3">
                        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-brand">
                            <PeopleIcon className="h-5 w-5" />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-extrabold text-neutral-900">Challenge a friend</p>
                            <p className="text-xs text-neutral-500">
                                Same questions, same order. Can they beat your {result.correct}/{result.total}?
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        className="mt-3.5 flex w-full items-center justify-center gap-2 rounded-xl bg-brand py-3 text-sm font-bold text-white hover:bg-brand-dark disabled:opacity-50"
                    >
                        <SendIcon className="h-3.5 w-3.5" />
                        Send Challenge
                    </button>
                </div>

                {Boolean(review?.length) && (
                    <button type="button" onClick={() => setReviewing(true)} className="mt-6 flex min-h-12 w-full items-center justify-between rounded-xl border border-neutral-300 bg-white px-5 text-sm font-bold text-neutral-900 hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand">
                        Review answers <span aria-hidden="true">→</span>
                    </button>
                )}

                <div className="mt-6 flex gap-2.5">
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => post(route('quiz.start', quiz.slug))}
                        className="flex flex-1 items-center justify-center rounded-xl bg-brand py-3.5 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90 disabled:opacity-60"
                    >
                        Try Again
                    </button>
                    <Link
                        href={route('quiz.category', quiz.categorySlug)}
                        className="flex flex-1 items-center justify-center rounded-xl bg-neutral-900 py-3.5 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90"
                    >
                        Done
                    </Link>
                </div>
                </>}
            </div>
        </AppShell>
    );
}
