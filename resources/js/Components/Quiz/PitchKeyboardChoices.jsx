import { useEffect, useId, useMemo, useRef, useState } from 'react';
import { NOTE_BLACK_KEYS, NOTE_WHITE_KEYS, blackKeyLeft, blackKeyWidth } from '@/lib/quizAnswerPresets';
import { clampPitchPage, defaultPitchPage, playablePitchOctaves } from '@/lib/pitchKeyboard';
import { useTrans } from '@/lib/i18n';

function OctaveArrow({ direction, disabled, onClick, controls }) {
    const t = useTrans();
    return (
        <button
            type="button"
            aria-label={direction === 'left' ? t('Previous octave') : t('Next octave')}
            aria-controls={controls}
            disabled={disabled}
            onClick={onClick}
            className="hidden h-9 w-7 shrink-0 items-center justify-center rounded-full border border-neutral-200 bg-white text-neutral-600 transition-colors hover:border-neutral-400 hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900 disabled:pointer-events-none disabled:opacity-25 md:flex md:h-10 md:w-10"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4" aria-hidden="true">
                <path d={direction === 'left' ? 'm14 6-6 6 6 6' : 'm10 6 6 6-6 6'} />
            </svg>
        </button>
    );
}

export default function PitchKeyboardChoices({ answered, correctAnswer, value, onChange, keyboardAudio = {}, initialPitch = null }) {
    const t = useTrans();
    const keyboardId = useId();
    const scrollRef = useRef(null);
    const audioRef = useRef(null);
    const gestureRef = useRef(null);
    const pageRef = useRef(0);
    const availableKeys = JSON.stringify(Object.keys(keyboardAudio).filter((pitch) => keyboardAudio[pitch]).sort());
    const octaves = useMemo(() => playablePitchOctaves(Object.fromEntries(JSON.parse(availableKeys).map((pitch) => [pitch, true]))), [availableKeys]);
    const targetPage = initialPitch ? octaves.indexOf(Number(initialPitch.match(/(\d+)$/)?.[1])) : -1;
    const initialPage = targetPage >= 0 ? targetPage : defaultPitchPage(octaves);
    const [activePage, setActivePage] = useState(initialPage);
    const currentPage = clampPitchPage(activePage, octaves.length);
    const currentOctave = octaves[currentPage];

    useEffect(() => {
        pageRef.current = initialPage;
        setActivePage(initialPage);
        const viewport = scrollRef.current;
        if (viewport) viewport.scrollTo({ left: initialPage * viewport.clientWidth, behavior: 'instant' });
        audioRef.current?.pause();
    }, [availableKeys, initialPage]);

    useEffect(() => {
        const viewport = scrollRef.current;
        if (!viewport) return;
        const observer = new ResizeObserver(() => {
            viewport.scrollTo({ left: pageRef.current * viewport.clientWidth, behavior: 'instant' });
        });
        observer.observe(viewport);
        return () => observer.disconnect();
    }, [availableKeys]);

    useEffect(() => () => audioRef.current?.pause(), []);

    function goToPage(index) {
        const page = clampPitchPage(index, octaves.length);
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        pageRef.current = page;
        setActivePage(page);
        scrollRef.current?.scrollTo({ left: page * scrollRef.current.clientWidth, behavior: reducedMotion ? 'instant' : 'smooth' });
    }

    function syncPage(event) {
        const viewport = event.currentTarget;
        if (!viewport.clientWidth) return;
        const page = clampPitchPage(Math.round(viewport.scrollLeft / viewport.clientWidth), octaves.length);
        pageRef.current = page;
        setActivePage(page);
    }

    function press(pitch) {
        if (answered || !keyboardAudio[pitch] || gestureRef.current?.moved) return;
        onChange(pitch);
        audioRef.current?.pause();
        const audio = new Audio(keyboardAudio[pitch]);
        audioRef.current = audio;
        audio.play().catch(() => {});
    }

    function keyClass(pitch, black) {
        if (answered && pitch === correctAnswer) return 'bg-emerald-600 text-white';
        if (answered && pitch === value) return 'bg-rose-600 text-white';
        if (!answered && pitch === value) return 'bg-emerald-600 text-white';
        return black ? 'bg-neutral-900 text-neutral-300 hover:bg-neutral-800' : 'bg-white text-neutral-400 hover:bg-neutral-50';
    }

    if (!octaves.length) {
        return <p className="text-sm text-neutral-500">{t('No pitches are available for this instrument.')}</p>;
    }

    return (
        <div className="min-w-0">
            <div className="mb-3 text-center">
                <p aria-live="polite" aria-atomic="true" className="text-sm font-semibold text-neutral-600">{t('Octave :number', { number: currentOctave })} <span className="font-normal text-neutral-400">· C{currentOctave}–B{currentOctave}</span></p>
                <p className="mt-1 text-xs text-neutral-400 md:hidden">{t('Swipe to change octave')}</p>
                <p className="mt-1 hidden text-xs text-neutral-400 md:block">{t('Use the arrows to change octave')}</p>
            </div>
            <div className="flex min-w-0 items-center gap-2 sm:gap-3">
                <OctaveArrow direction="left" disabled={currentPage === 0} onClick={() => goToPage(pageRef.current - 1)} controls={keyboardId} />
                <div
                    id={keyboardId}
                    ref={scrollRef}
                    role="region"
                    aria-label={t('Pitch keyboard')}
                    tabIndex={0}
                    onScroll={syncPage}
                    onKeyDown={(event) => {
                        gestureRef.current = null;
                        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                        event.preventDefault();
                        goToPage(event.key === 'Home' ? 0 : event.key === 'End' ? octaves.length - 1 : pageRef.current + (event.key === 'ArrowLeft' ? -1 : 1));
                    }}
                    onPointerDown={(event) => { gestureRef.current = { x: event.clientX, y: event.clientY, moved: false }; }}
                    onPointerMove={(event) => {
                        const gesture = gestureRef.current;
                        if (gesture && (Math.abs(event.clientX - gesture.x) > 8 || Math.abs(event.clientY - gesture.y) > 8)) gesture.moved = true;
                    }}
                    onClickCapture={(event) => {
                        if (gestureRef.current?.moved) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        gestureRef.current = null;
                    }}
                    className="pitch-keyboard-scroll min-w-0 flex-1 snap-x snap-mandatory overflow-x-auto overscroll-x-contain rounded-2xl border border-black/10 bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900"
                >
                    <div className="flex">
                        {octaves.map((octave, pageIndex) => (
                            <div key={octave} aria-hidden={pageIndex !== currentPage} className="relative flex h-36 min-w-0 basis-full shrink-0 snap-start snap-always overflow-hidden sm:h-44">
                                {NOTE_WHITE_KEYS.map((note, index) => {
                                    const pitch = `${note}${octave}`;
                                    const available = Boolean(keyboardAudio[pitch]);
                                    return (
                                        <button
                                            key={pitch}
                                            type="button"
                                            aria-label={pitch}
                                            aria-pressed={value === pitch}
                                            tabIndex={pageIndex === currentPage ? 0 : -1}
                                            disabled={answered || !available}
                                            onClick={() => press(pitch)}
                                            className={`flex min-w-0 flex-1 items-end justify-center pb-2.5 text-xs font-bold transition-colors duration-100 disabled:pointer-events-none sm:text-sm ${index < NOTE_WHITE_KEYS.length - 1 ? 'border-r border-neutral-200' : ''} ${available ? '' : 'opacity-30'} ${keyClass(pitch, false)}`}
                                        >
                                            {pitch}
                                        </button>
                                    );
                                })}
                                {NOTE_BLACK_KEYS.map(({ afterIndex, note }) => {
                                    const pitch = `${note}${octave}`;
                                    const available = Boolean(keyboardAudio[pitch]);
                                    return (
                                        <button
                                            key={pitch}
                                            type="button"
                                            aria-label={t(':sharp or :flat', { sharp: `${note.split('/')[0]}${octave}`, flat: `${note.split('/')[1]}${octave}` })}
                                            aria-pressed={value === pitch}
                                            tabIndex={pageIndex === currentPage ? 0 : -1}
                                            disabled={answered || !available}
                                            onClick={() => press(pitch)}
                                            style={{ left: blackKeyLeft(afterIndex, 7), width: blackKeyWidth(7) }}
                                            className={`absolute top-0 flex h-[62%] flex-col items-center justify-end gap-0.5 rounded-b-md pb-2 text-[9px] font-bold leading-tight transition-colors duration-100 disabled:pointer-events-none sm:text-[10px] ${available ? '' : 'opacity-30'} ${keyClass(pitch, true)}`}
                                        >
                                            {note.split('/').map((label) => <span key={label}>{label}{octave}</span>)}
                                        </button>
                                    );
                                })}
                            </div>
                        ))}
                    </div>
                </div>
                <OctaveArrow direction="right" disabled={currentPage === octaves.length - 1} onClick={() => goToPage(pageRef.current + 1)} controls={keyboardId} />
            </div>
        </div>
    );
}
