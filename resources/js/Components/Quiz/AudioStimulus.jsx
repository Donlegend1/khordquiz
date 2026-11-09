import { createNotePlayer } from '@/lib/audio-engine';
import { useEffect, useRef, useState } from 'react';
import { useTrans } from '@/lib/i18n';

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

function audioTime(seconds) {
    const value = Math.max(0, Math.floor(Number.isFinite(seconds) ? seconds : 0));
    return `${Math.floor(value / 60)}:${String(value % 60).padStart(2, '0')}`;
}

export default function AudioStimulus({ questionKey, src, notes, dark = false }) {
    const t = useTrans();
    const audioRef = useRef(null);
    const playerRef = useRef(null);
    const [playing, setPlaying] = useState(false);
    const [loading, setLoading] = useState(false);
    const [duration, setDuration] = useState(0);
    const [position, setPosition] = useState(0);
    const [error, setError] = useState(false);
    const [retry, setRetry] = useState(0);
    const live = Boolean(notes?.length);
    const available = live || Boolean(src);
    const recipeKey = JSON.stringify(notes);

    useEffect(() => {
        let disposed = false;
        let player;
        const audio = audioRef.current;
        setPlaying(false);
        setPosition(0);
        setDuration(0);
        setError(false);
        setLoading(available);
        if (live) {
            createNotePlayer(notes, { onEnded: () => {
                if (!disposed) {
                    setPlaying(false);
                    setPosition(player.duration);
                }
            } }).then(async (ready) => {
                player = ready;
                if (disposed) { player.destroy(); return; }
                playerRef.current = player;
                setDuration(player.duration);
                setLoading(false);
                // Autoplay can be blocked; manual Play remains available.
                try { await player.play(); } catch { /* Wait for a Play click. */ }
                if (!disposed) setPlaying(!player.paused);
            }).catch(() => {
                if (!disposed) { setLoading(false); setError(true); }
            });
        } else if (audio && src) {
            audio.currentTime = 0;
            audio.play().catch(() => {});
        }
        return () => {
            disposed = true;
            player?.destroy();
            playerRef.current = null;
            audio?.pause();
        };
        // Compare the recipe contents so answering does not replay the same question.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [questionKey, src, recipeKey, retry]);

    useEffect(() => {
        if (!live || !playing) return;
        let frame;
        const update = () => {
            const player = playerRef.current;
            if (player) setPosition(player.currentTime);
            frame = requestAnimationFrame(update);
        };
        frame = requestAnimationFrame(update);
        return () => cancelAnimationFrame(frame);
    }, [live, playing]);

    async function toggle() {
        if (live) {
            const player = playerRef.current;
            if (!player) { setRetry((value) => value + 1); return; }
            if (!player.paused) {
                player.pause();
                setPosition(player.currentTime);
                setPlaying(false);
            } else {
                try {
                    await player.play();
                    if (playerRef.current === player) { setError(false); setPlaying(!player.paused); }
                } catch { if (playerRef.current === player) setError(true); }
            }
        } else if (audioRef.current) {
            if (audioRef.current.paused) {
                try { await audioRef.current.play(); setError(false); }
                catch { setError(true); }
            } else audioRef.current.pause();
        }
    }

    async function replay() {
        setPosition(0);
        setError(false);
        if (live) {
            const player = playerRef.current;
            if (!player) { setRetry((value) => value + 1); return; }
            try {
                await player.replay();
                if (playerRef.current === player) setPlaying(!player.paused);
            } catch { if (playerRef.current === player) setError(true); }
        } else if (audioRef.current) {
            const audio = audioRef.current;
            if (audio.error) audio.load();
            audio.currentTime = 0;
            try { await audio.play(); } catch { setError(true); }
        }
    }

    async function seek(value) {
        setPosition(value);
        if (live && playerRef.current) {
            const player = playerRef.current;
            try {
                await player.seek(value);
                if (playerRef.current === player) setPlaying(!player.paused);
            } catch { if (playerRef.current === player) { setPlaying(false); setError(true); } }
        } else if (audioRef.current) audioRef.current.currentTime = value;
    }

    return (
        <div className="mx-auto w-full max-w-lg text-center">
            {!live && src && (
                <audio
                    key={src}
                    ref={audioRef}
                    src={src}
                    preload="metadata"
                    onLoadedMetadata={(event) => {
                        setDuration(Number.isFinite(event.currentTarget.duration) ? event.currentTarget.duration : 0);
                        setLoading(false);
                    }}
                    onTimeUpdate={(event) => setPosition(event.currentTarget.currentTime)}
                    onPlay={() => setPlaying(true)}
                    onPause={() => setPlaying(false)}
                    onEnded={() => setPlaying(false)}
                    onError={() => { setLoading(false); setPlaying(false); setError(true); }}
                    className="hidden"
                />
            )}
            <div>
                <div className={`flex items-start gap-1 rounded-xl border p-2 sm:gap-2 sm:px-3 ${dark ? 'border-white/10 bg-white/5' : 'border-neutral-200 bg-neutral-50 shadow-sm'}`}>
                    <button
                        type="button"
                        onClick={replay}
                        disabled={!available || loading}
                        title={t('Replay from the beginning')}
                        aria-label={t('Replay audio from the beginning')}
                        className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-lg transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-40 ${dark ? 'text-white/70 hover:bg-white/10 hover:text-white' : 'text-neutral-600 hover:bg-neutral-200/70 hover:text-neutral-900'}`}
                    >
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5"><path d="M4 10a8 8 0 1 1 1.8 8M4 4v6h6" /></svg>
                    </button>
                    <button
                        type="button"
                        onClick={toggle}
                        disabled={!available || loading}
                        title={loading ? t('Loading audio') : playing ? t('Pause') : t('Play')}
                        aria-label={loading ? t('Loading audio') : playing ? t('Pause audio') : t('Play audio')}
                        className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-lg transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-40 ${dark ? 'text-white hover:bg-white/10' : 'text-brand hover:bg-red-50 hover:text-brand-dark'}`}
                    >
                        {loading ? <span aria-hidden="true" className="h-5 w-5 animate-spin rounded-full border-2 border-brand/25 border-t-brand" /> : playing ? <PauseIcon aria-hidden="true" className="h-6 w-6" /> : <PlayTriangleIcon aria-hidden="true" className="h-7 w-7" />}
                    </button>
                    <div className="min-w-0 flex-1 px-1">
                        <input
                            type="range"
                            aria-label={t('Audio position')}
                            aria-valuetext={t(':position of :duration', { position: audioTime(position), duration: audioTime(duration) })}
                            min="0"
                            max={duration || 1}
                            step="0.01"
                            value={Math.min(position, duration || 0)}
                            onChange={(event) => seek(Number(event.target.value))}
                            disabled={!duration || loading}
                            style={{ '--audio-track': dark ? 'rgba(255,255,255,0.25)' : '#d4d4d4', '--audio-progress': `${duration ? Math.min(100, Math.max(0, position / duration * 100)) : 0}%` }}
                            className="quiz-audio-timeline focus-visible:rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        />
                        <div aria-hidden="true" className={`flex justify-between pb-0.5 text-[11px] font-medium tabular-nums leading-none ${dark ? 'text-white/50' : 'text-neutral-500'}`}><span>{audioTime(position)}</span><span>{audioTime(duration)}</span></div>
                    </div>
                </div>
                {error && <p role="alert" className="mt-3 text-xs text-red-700">{t('The audio could not play. Press Replay to try again.')}</p>}
                {!available && <p className="mt-3 text-xs text-neutral-500">{t('Audio is not available for this question.')}</p>}
            </div>
        </div>
    );
}

