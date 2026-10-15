import AppShell from '@/Layouts/AppShell';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function PlayIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="9" />
            <path d="M10 9l5 3-5 3z" fill="currentColor" stroke="none" />
        </svg>
    );
}

function CheckIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 13l4 4L19 7" />
        </svg>
    );
}

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

function RepeatIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 7h11a4 4 0 014 4v1M20 17H9a4 4 0 01-4-4v-1" />
            <path d="M8 4L4 7l4 3M16 14l4 3-4 3" />
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

function RestartIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M3 12a9 9 0 1 1 2.6 6.3" />
            <path d="M3 7v5h5" />
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

function ChevronLeftIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M15 5l-7 7 7 7" />
        </svg>
    );
}

function ChevronRightIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 5l7 7-7 7" />
        </svg>
    );
}

function CheckCircleIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="9" />
            <path d="M8 12.5l2.5 2.5L16 9.5" />
        </svg>
    );
}

function BookmarkIcon({ filled, ...props }) {
    return (
        <svg viewBox="0 0 24 24" fill={filled ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 3.5h12v17l-6-4-6 4v-17z" />
        </svg>
    );
}

function HeadsetIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 13v-1a8 8 0 0116 0v1" />
            <rect x="2.5" y="13" width="4" height="6" rx="1.5" />
            <rect x="17.5" y="13" width="4" height="6" rx="1.5" />
            <path d="M19.5 19v.5a3 3 0 01-3 3H13" />
        </svg>
    );
}

// Xylophone, Strings and Organ have no clean single emoji of their own, so
// they're drawn here instead, same as the mobile app does.
function XylophoneIcon({ size = 22 }) {
    const bars = ['#E53935', '#FB8C00', '#FDD835', '#43A047', '#1E88E5', '#8E24AA'];
    return (
        <svg width={size} height={size} viewBox="0 0 26 26">
            <rect fill="#8D6E63" height={2} rx={1} width={24} x={1} y={8} />
            <rect fill="#8D6E63" height={2} rx={1} width={24} x={1} y={17} />
            {bars.map((fill, index) => {
                const height = 20 - index * 2;
                return <rect fill={fill} height={height} key={fill} rx={1.2} width={3} x={2 + index * 4} y={13 - height / 2} />;
            })}
        </svg>
    );
}

function OrganIcon({ size = 22 }) {
    const pipes = [10, 14, 18, 20, 18, 14, 10];
    return (
        <svg width={size} height={size} viewBox="0 0 26 26">
            {pipes.map((height, index) => (
                <rect
                    fill={index % 2 ? '#B0B7C3' : '#D5DAE1'}
                    key={index}
                    height={height}
                    rx={1.4}
                    stroke="#7C8594"
                    strokeWidth={0.6}
                    width={2.8}
                    x={2.6 + index * 3}
                    y={21 - height}
                />
            ))}
            <rect fill="#8D6E63" height={3.5} rx={1} width={24} x={1} y={21} />
        </svg>
    );
}

function StringsIcon({ size = 22 }) {
    return (
        <span className="flex items-center justify-center" style={{ height: size }}>
            <span style={{ fontSize: size * 0.7, marginRight: -size * 0.35, marginTop: size * 0.15 }}>🎻</span>
            <span style={{ fontSize: size * 0.7 }}>🎻</span>
        </span>
    );
}

const INSTRUMENT_EMOJI = {
    All: '🔀',
    Choir: '👥',
    Ensemble: '🎻',
    'Full Song': '💿',
    Guitar: '🎸',
    Instruments: '🎹',
    Monophonic: '🪈',
    Piano: '🎹',
    Saxophone: '🎷',
    Synth: '🎛️',
    'Vocal Stems': '🎙️',
    Violin: '🎻',
    Voice: '🎤',
};

function SoundIcon({ sound, size = 22 }) {
    if (sound === 'Xylophone') return <XylophoneIcon size={size} />;
    if (sound === 'Strings') return <StringsIcon size={size} />;
    if (sound === 'Organ') return <OrganIcon size={size} />;
    return <span style={{ fontSize: size * 0.85 }}>{INSTRUMENT_EMOJI[sound] ?? '🎵'}</span>;
}

function StatusDot({ quiz, inverted }) {
    // A member's explicit "Mark as Completed" (or a genuinely finished attempt) wins over
    // a merely in-progress one — otherwise restarting an already-completed quiz would make
    // it look unfinished again in the sidebar, which reads as the completion being lost.
    if (quiz.status === 'completed' || quiz.manuallyCompleted) {
        return (
            <span className="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                <CheckIcon className="h-2.5 w-2.5" />
            </span>
        );
    }

    if (quiz.status === 'in_progress') {
        return (
            <span
                className={`flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 ${inverted ? 'border-white' : 'border-brand'}`}
            >
                <span className={`h-1.5 w-1.5 rounded-full ${inverted ? 'bg-white' : 'bg-brand'}`} />
            </span>
        );
    }

    return <span className={`h-4 w-4 shrink-0 rounded-full border-2 ${inverted ? 'border-white/40' : 'border-neutral-300'}`} />;
}

function SidebarRow({ quiz, categorySlug, selected }) {
    return (
        <Link
            href={route('quiz.category', { category: categorySlug, quiz: quiz.slug })}
            preserveScroll
            preserveState
            replace
            className={`flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left transition-all duration-150 ${
                selected ? 'bg-brand shadow-sm' : 'bg-neutral-100 hover:bg-neutral-200'
            }`}
        >
            <span
                className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${
                    selected ? 'bg-white/15 text-white' : 'bg-white text-neutral-600'
                }`}
            >
                <PlayIcon className="h-3.5 w-3.5" />
            </span>
            <p className={`min-w-0 flex-1 truncate text-sm font-semibold ${selected ? 'text-white' : 'text-neutral-900'}`}>
                {quiz.title}
            </p>
            <StatusDot quiz={quiz} inverted={selected} />
        </Link>
    );
}

// dangerouslySetInnerHTML never executes <script> tags (browsers ignore
// script elements created via innerHTML), so embed codes that load a player
// via <script src="..."> — Wistia, Vimeo's JS embed, etc. — need their
// script tags recreated as real DOM nodes to actually run.
function VideoEmbed({ html, className }) {
    const containerRef = useRef(null);

    useEffect(() => {
        const container = containerRef.current;
        if (!container) return;

        container.innerHTML = html;

        container.querySelectorAll('script').forEach((oldScript) => {
            const newScript = document.createElement('script');
            for (const { name, value } of oldScript.attributes) {
                newScript.setAttribute(name, value);
            }
            newScript.textContent = oldScript.textContent;
            oldScript.replaceWith(newScript);
        });
    }, [html]);

    return <div ref={containerRef} className={className} />;
}

function SoundPicker({ sounds, sound, onSelect }) {
    if (sounds.length === 0) return null;

    return (
        <div className="mt-4">
            <p className="text-xs font-semibold text-neutral-700">Choose a sound</p>
            <div className="mt-2.5 flex flex-wrap gap-2.5 sm:flex-nowrap">
                {sounds.map((option) => {
                    const active = sound === option;
                    return (
                        <button
                            key={option}
                            type="button"
                            onClick={() => onSelect(option)}
                            className={`flex w-[31%] flex-col items-center justify-center gap-1 rounded-xl border px-3 py-3 text-center transition-colors duration-150 sm:w-auto sm:flex-1 sm:py-4 ${
                                active ? 'border-brand/60 bg-brand/5' : 'border-black/10 bg-white hover:bg-neutral-50'
                            }`}
                        >
                            <SoundIcon sound={option} />
                            <span className={`truncate text-xs ${active ? 'font-bold text-brand' : 'font-medium text-neutral-600'}`}>
                                {option}
                            </span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

// Opened from the button next to Start Quiz rather than shown inline, since a
// quiz can have several labeled tracks (one per sound it covers) — stepping
// through them belongs in a focused player, not a card competing for space
// with everything else on the page.
function ReferenceAudioModal({ tracks, onClose }) {
    const [index, setIndex] = useState(0);
    const [playing, setPlaying] = useState(false);
    const [currentTime, setCurrentTime] = useState(0);
    const [duration, setDuration] = useState(0);
    const audioRef = useRef(null);
    const track = tracks[index] ?? null;

    useEffect(() => {
        const onKeyDown = (event) => event.key === 'Escape' && onClose();
        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, [onClose]);

    useEffect(() => {
        setCurrentTime(0);
        setDuration(0);
        setPlaying(false);
    }, [index]);

    const togglePlay = () => {
        if (!audioRef.current) return;
        if (playing) {
            audioRef.current.pause();
        } else {
            audioRef.current.play();
        }
    };

    const restart = () => {
        if (!audioRef.current) return;
        audioRef.current.currentTime = 0;
        audioRef.current.play();
    };

    const canGoPrevious = index > 0;
    const canGoNext = index < tracks.length - 1;

    const previous = () => {
        setIndex((i) => Math.max(0, i - 1));
    };

    const next = () => {
        setIndex((i) => Math.min(tracks.length - 1, i + 1));
    };

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            onClick={(e) => e.target === e.currentTarget && onClose()}
        >
            <div className="w-full max-w-lg rounded-2xl bg-neutral-900 p-8 text-white">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2 text-white/50">
                        <HeadsetIcon className="h-[18px] w-[18px]" />
                        <p className="text-xs font-semibold">Reference Audio</p>
                    </div>
                    <button type="button" onClick={onClose} aria-label="Close" className="text-white/50 hover:text-white">
                        <CloseIcon className="h-5 w-5" />
                    </button>
                </div>

                {track ? (
                    <>
                        <audio
                            key={track.id}
                            ref={audioRef}
                            src={track.url}
                            autoPlay
                            onPlay={() => setPlaying(true)}
                            onPause={() => setPlaying(false)}
                            onEnded={() => setPlaying(false)}
                            onLoadedMetadata={(e) => setDuration(e.currentTarget.duration)}
                            onTimeUpdate={(e) => setCurrentTime(e.currentTarget.currentTime)}
                        />

                        <div className="mt-5 flex items-center gap-3 rounded-xl bg-white/5 px-3 py-2.5">
                            <button type="button" onClick={restart} aria-label="Restart" className="shrink-0 text-white/70 hover:text-white">
                                <RestartIcon className="h-5 w-5" />
                            </button>
                            <button type="button" onClick={togglePlay} aria-label={playing ? 'Pause' : 'Play'} className="shrink-0 text-white">
                                {playing ? <PauseIcon className="h-5 w-5" /> : <PlayTriangleIcon className="h-5 w-5" />}
                            </button>
                            <input
                                type="range"
                                min={0}
                                max={duration || 0}
                                step={0.01}
                                value={currentTime}
                                onChange={(e) => {
                                    const value = Number(e.target.value);
                                    if (audioRef.current) audioRef.current.currentTime = value;
                                    setCurrentTime(value);
                                }}
                                className="h-1.5 w-full accent-brand"
                            />
                        </div>

                        <p className="mt-6 text-center text-sm text-white/50">Listen and familiarize yourself with the sound(s).</p>
                        <p className="font-display mt-3 text-center text-4xl font-semibold">{track.label}</p>
                        {track.name && <p className="mt-1.5 text-center text-sm text-white/50">{track.name}</p>}

                        {tracks.length > 1 && (
                            <div className="mt-6 flex gap-2.5">
                                {canGoPrevious && (
                                    <button
                                        type="button"
                                        onClick={previous}
                                        className="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-brand py-3 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90"
                                    >
                                        <ChevronLeftIcon className="h-4 w-4" />
                                        Previous
                                    </button>
                                )}
                                {canGoNext && (
                                    <button
                                        type="button"
                                        onClick={next}
                                        className="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-green-600 py-3 text-sm font-bold text-white transition-opacity duration-150 hover:bg-green-700"
                                    >
                                        Next
                                        <ChevronRightIcon className="h-4 w-4" />
                                    </button>
                                )}
                            </div>
                        )}
                    </>
                ) : (
                    <p className="mt-10 text-center text-sm text-white/50">No reference audio has been added for this quiz yet.</p>
                )}
            </div>
        </div>
    );
}

function DetailPane({ quiz }) {
    const { post, processing } = useForm();
    const [sound, setSound] = useState(quiz?.sounds?.[0] ?? null);
    const [blockedNotice, setBlockedNotice] = useState(false);
    const [audioModalOpen, setAudioModalOpen] = useState(false);

    useEffect(() => {
        setSound(quiz?.sounds?.[0] ?? null);
        setBlockedNotice(false);
        setAudioModalOpen(false);
    }, [quiz?.slug]);

    if (!quiz) {
        return (
            <div className="flex h-full min-h-[320px] items-center justify-center rounded-2xl border border-black/5 bg-white p-8 text-center text-sm text-neutral-500">
                No quizzes in this category are ready yet. Check back soon.
            </div>
        );
    }

    const resuming = quiz.status === 'in_progress';
    const completed = quiz.status === 'completed';
    // The top badge/sidebar checkmark reflect a manual mark too, but the
    // Start/Continue/Retry button and score text below stay tied to a real
    // attempt only — manually marking a quiz complete doesn't mean it's been
    // played, so "Retry Quiz" would be the wrong thing to say.
    //
    // A completed/manually-marked quiz keeps showing as completed even with a
    // leftover in-progress attempt (e.g. after a cancelled retry) — otherwise
    // restarting it would make an already-earned completion look undone.
    const showsCompleted = completed || quiz.manuallyCompleted;

    function toggleCompleted() {
        router.post(route('quiz.mark-completed', quiz.slug), {}, { preserveScroll: true, preserveState: true });
    }

    function toggleBookmark() {
        router.post(route('quiz.bookmark', quiz.slug), {}, { preserveScroll: true, preserveState: true });
    }

    // Same DEFAULT_TIPS the mobile app falls back to for every quiz it has no
    // specific override for (app/quiz-preview.tsx) — not invented copy.
    const tips = [
        'Use headphones for best results.',
        'Listen carefully before answering.',
        'Take your time and answer the questions.',
    ];

    function start() {
        if (!quiz.available) {
            setBlockedNotice(true);
            return;
        }
        post(route('quiz.start', quiz.slug));
    }

    return (
        <div className="rounded-2xl border border-black/5 bg-white p-6 sm:p-7">
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <h2 className="text-2xl font-bold text-neutral-900">{quiz.title}</h2>
                </div>
                {showsCompleted && (
                    <span className="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">
                        <CheckIcon className="h-3.5 w-3.5" />
                        Completed
                    </span>
                )}
            </div>

            {quiz.video_embed ? (
                <VideoEmbed
                    html={quiz.video_embed}
                    className="mt-5 aspect-video overflow-hidden rounded-2xl bg-neutral-900 [&_iframe]:h-full [&_iframe]:w-full"
                />
            ) : (
                <div className="mt-5 flex aspect-video items-center justify-center rounded-2xl bg-neutral-100 text-sm text-neutral-400">
                    No video for this quiz yet
                </div>
            )}

            <div className="mt-6 grid grid-cols-3 gap-3">
                <div className="flex items-center gap-3 rounded-xl border border-black/5 bg-neutral-50 px-3.5 py-3">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-neutral-400 ring-1 ring-black/5">
                        <GaugeIcon className="h-4.5 w-4.5" />
                    </span>
                    <div className="min-w-0">
                        <p className="text-xs text-neutral-400">Difficulty</p>
                        <p className="text-sm font-bold text-neutral-900">{quiz.difficulty}</p>
                    </div>
                </div>
                <div className="flex items-center gap-3 rounded-xl border border-black/5 bg-neutral-50 px-3.5 py-3">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-neutral-400 ring-1 ring-black/5">
                        <HelpIcon className="h-4.5 w-4.5" />
                    </span>
                    <div className="min-w-0">
                        <p className="text-xs text-neutral-400">Questions</p>
                        <p className="text-sm font-bold text-neutral-900">{quiz.question_count}</p>
                    </div>
                </div>
                <div className="flex items-center gap-3 rounded-xl border border-black/5 bg-neutral-50 px-3.5 py-3">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-neutral-400 ring-1 ring-black/5">
                        <RepeatIcon className="h-4.5 w-4.5" />
                    </span>
                    <div className="min-w-0">
                        <p className="text-xs text-neutral-400">Attempts</p>
                        <p className="text-sm font-bold text-neutral-900">Unlimited</p>
                    </div>
                </div>
            </div>

            <div className="mt-4 rounded-2xl border border-black/5 bg-neutral-50 p-4">
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

            <SoundPicker sounds={quiz.sounds} sound={sound} onSelect={setSound} />

            {completed && quiz.score !== null && (
                <p className="mt-4 text-sm font-semibold text-emerald-700">Last attempt: {quiz.score}% correct</p>
            )}

            <div className="mt-6 flex gap-2.5">
                <button
                    type="button"
                    disabled={processing}
                    onClick={start}
                    className="flex-1 rounded-xl bg-neutral-900 py-3.5 text-sm font-bold text-white transition-opacity duration-150 hover:opacity-90 disabled:opacity-60"
                >
                    {resuming ? 'Continue Quiz' : completed ? 'Retry Quiz' : 'Start Quiz'}
                </button>
                {quiz.referenceAudioTracks.length > 0 && (
                    <button
                        type="button"
                        onClick={() => setAudioModalOpen(true)}
                        className="flex shrink-0 items-center justify-center gap-2 rounded-xl bg-green-600 px-4 py-3.5 text-sm font-extrabold text-white shadow-sm transition-all duration-150 hover:bg-green-700 hover:shadow active:scale-[0.98]"
                    >
                        <HeadsetIcon className="h-5 w-5" />
                        Listen Mode
                    </button>
                )}
            </div>
            {blockedNotice && (
                <p className="mt-2.5 text-center text-sm text-neutral-500">Questions for this quiz haven&apos;t been added yet.</p>
            )}

            {audioModalOpen && <ReferenceAudioModal tracks={quiz.referenceAudioTracks} onClose={() => setAudioModalOpen(false)} />}

            {quiz.available ? (
                <div className="mt-3 flex gap-2.5">
                    <button
                        type="button"
                        onClick={toggleCompleted}
                        className={`flex min-h-[48px] flex-1 items-center justify-center gap-2 rounded-xl border-[1.5px] text-sm font-bold transition-colors duration-150 ${
                            quiz.manuallyCompleted ? 'border-emerald-600 text-emerald-600' : 'border-brand text-brand'
                        }`}
                    >
                        <CheckCircleIcon className="h-5 w-5" />
                        {quiz.manuallyCompleted ? 'Completed' : 'Mark as Completed'}
                    </button>
                    <button
                        type="button"
                        onClick={toggleBookmark}
                        aria-label={quiz.bookmarked ? 'Remove bookmark' : 'Bookmark this quiz'}
                        className={`flex min-h-[48px] w-[52px] shrink-0 items-center justify-center rounded-xl border-[1.5px] transition-colors duration-150 ${
                            quiz.bookmarked ? 'border-brand text-brand' : 'border-neutral-300 text-neutral-400'
                        }`}
                    >
                        <BookmarkIcon filled={quiz.bookmarked} className="h-5 w-5" />
                    </button>
                </div>
            ) : (
                // Not-yet-built quizzes have no real Quiz row to attach a
                // mark to, so these stay inert rather than posting to
                // nothing (same "coming soon" treatment as other unbuilt-quiz UI).
                <div className="mt-3 flex gap-2.5">
                    <button
                        type="button"
                        disabled
                        title="Coming soon"
                        className="flex min-h-[48px] flex-1 cursor-default items-center justify-center gap-2 rounded-xl border-[1.5px] border-brand/40 text-sm font-bold text-brand/40"
                    >
                        <CheckCircleIcon className="h-5 w-5" />
                        Mark as Completed
                    </button>
                    <button
                        type="button"
                        disabled
                        title="Coming soon"
                        aria-label="Bookmark this quiz"
                        className="flex min-h-[48px] w-[52px] shrink-0 cursor-default items-center justify-center rounded-xl border-[1.5px] border-neutral-200 text-neutral-300"
                    >
                        <BookmarkIcon className="h-5 w-5" />
                    </button>
                </div>
            )}
        </div>
    );
}

export default function QuizCategory({ category, categorySlug, selectedSlug, quizzes, progress }) {
    const selected = quizzes.find((quiz) => quiz.slug === selectedSlug) ?? quizzes[0] ?? null;

    return (
        <AppShell eyebrow="Start Quiz" title={category} intro="Pick a quiz to begin.">
            <Head title={selected ? `${selected.title} · ${category}` : category} />

            <div className="grid gap-6 lg:grid-cols-[360px_1fr]">
                <div className="rounded-2xl border border-black/5 bg-white p-4 shadow-sm">
                    <div className="mb-4 flex items-center justify-between text-sm">
                        <span className="font-semibold text-neutral-700">
                            {progress.done}/{progress.total} Completed
                        </span>
                        <span className="font-semibold text-neutral-500">{progress.percent}%</span>
                    </div>
                    <div className="mb-5 h-1.5 w-full overflow-hidden rounded-full bg-neutral-200">
                        <div className="h-full rounded-full bg-neutral-900" style={{ width: `${progress.percent}%` }} />
                    </div>

                    <div className="space-y-1">
                        {quizzes.map((quiz) => (
                            <SidebarRow
                                key={quiz.slug}
                                quiz={quiz}
                                categorySlug={categorySlug}
                                selected={quiz.slug === selected?.slug}
                            />
                        ))}
                    </div>
                </div>

                <DetailPane quiz={selected} />
            </div>
        </AppShell>
    );
}
