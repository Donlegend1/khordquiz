import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

// The 12 chromatic piano notes used as literal answers by the note-based quizzes (Find the
// Note, Find the Key #1/#2) — same files and note names as the mobile app's assets/audio/piano,
// copied into public/audio/piano so the browser can play them directly.
const NOTE_FILES = {
    C: 'c3', 'C#/Db': 'csharp3', D: 'd3', 'D#/Eb': 'dsharp3', E: 'e3', F: 'f3',
    'F#/Gb': 'fsharp3', G: 'g3', 'G#/Ab': 'gsharp3', A: 'a3', 'A#/Bb': 'asharp3', B: 'b3',
};

function noteAudio(note) {
    const file = NOTE_FILES[note];
    return file ? new Audio(`/audio/piano/${file}.wav`) : null;
}

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

function ChevronIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 9l6 6 6-6" />
        </svg>
    );
}

function PlayIcon(props) {
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

// Collapsible per-question breakdown for the quiz just finished — real answers recorded
// server-side per question (QuizController::answer), not a fabricated summary.
function AnswerReview({ records }) {
    const [open, setOpen] = useState(false);
    const [playing, setPlaying] = useState(null);

    if (records.length === 0) return null;

    const toggleNote = (position, note) => {
        if (playing?.position === position) {
            playing.audio.pause();
            setPlaying(null);
            return;
        }
        playing?.audio.pause();

        const audio = noteAudio(note);
        if (!audio) return;
        audio.addEventListener('ended', () => setPlaying((current) => (current?.position === position ? null : current)));
        audio.play().catch(() => {});
        setPlaying({ audio, position });
    };

    return (
        <div className="mt-6 overflow-hidden rounded-2xl border border-black/5 bg-white">
            <button
                type="button"
                aria-expanded={open}
                onClick={() => setOpen((current) => !current)}
                className="flex w-full items-center justify-between px-5 py-4 text-left transition-colors duration-150 hover:bg-neutral-50"
            >
                <span className="text-sm font-extrabold text-neutral-900">Review your answers</span>
                <ChevronIcon className={`h-4 w-4 text-neutral-400 transition-transform duration-150 ${open ? 'rotate-180' : ''}`} />
            </button>
            {open && (
                <ul className="divide-y divide-black/5 border-t border-black/5">
                    {records.map((record) => {
                        const canPlayCorrect = Object.prototype.hasOwnProperty.call(NOTE_FILES, record.answer);
                        return (
                            <li key={record.position} className="flex items-center gap-2.5 px-5 py-3">
                                <span
                                    className={`flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full ${
                                        record.correct ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-brand'
                                    }`}
                                >
                                    {record.correct ? <CheckIcon className="h-3 w-3" /> : <XIcon className="h-3 w-3" />}
                                </span>
                                <span className="w-5 shrink-0 text-xs font-bold tabular-nums text-neutral-300">{record.position}</span>
                                <span className="min-w-0 flex-1">
                                    <p className={`truncate text-sm font-bold ${record.correct ? 'text-neutral-900' : 'text-brand'}`}>{record.picked || '—'}</p>
                                    {!record.correct && <p className="truncate text-xs text-emerald-700">Correct: {record.answer}</p>}
                                </span>
                                {canPlayCorrect && (() => {
                                    const isPlaying = playing?.position === record.position;
                                    return (
                                        <span className="relative flex h-8 w-8 shrink-0 items-center justify-center">
                                            {isPlaying && <span className="absolute inset-0 animate-ping rounded-full bg-brand/30" />}
                                            <button
                                                type="button"
                                                aria-label={isPlaying ? 'Pause' : `Play the correct note, ${record.answer}`}
                                                onClick={() => toggleNote(record.position, record.answer)}
                                                className={`relative flex h-8 w-8 items-center justify-center rounded-full transition-colors duration-150 ${
                                                    isPlaying ? 'bg-brand text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                                                }`}
                                            >
                                                {isPlaying ? <PauseIcon className="h-3.5 w-3.5" /> : <PlayIcon className="ml-0.5 h-3.5 w-3.5" />}
                                            </button>
                                        </span>
                                    );
                                })()}
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
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

export default function QuizResults({ quiz, result, review }) {
    const { post, processing } = useForm();

    return (
        <AppShell eyebrow={quiz.category} title={quiz.title} intro="Quiz results">
            <Head title={`${quiz.title} — Results`} />

            <div className="mx-auto max-w-xl">
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
                        disabled
                        title="Coming soon"
                        className="mt-3.5 flex w-full cursor-default items-center justify-center gap-2 rounded-xl bg-brand/60 py-3 text-sm font-bold text-white"
                    >
                        <SendIcon className="h-3.5 w-3.5" />
                        Send Challenge
                    </button>
                </div>

                <AnswerReview records={review ?? []} />

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
            </div>
        </AppShell>
    );
}
