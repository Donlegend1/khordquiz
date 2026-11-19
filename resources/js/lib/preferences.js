import { usePage } from '@inertiajs/react';
import { useEffect, useLayoutEffect } from 'react';

// Member settings that change how the web app behaves, shared on every page by
// HandleInertiaRequests (`preferences`). Guests and auth pages get the defaults.
const DEFAULTS = { appearance: 'System', notation: 'Solfège', sound_effects: true, haptics: true, timezone: null };

export function usePreferences() {
    return { ...DEFAULTS, ...(usePage().props.preferences ?? {}) };
}

// ---- Appearance -----------------------------------------------------------

const darkQuery = typeof window !== 'undefined' && window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
let stopFollowingSystem = null;

function setDark(on) {
    document.documentElement.classList.toggle('dark', on);
    document.documentElement.style.colorScheme = on ? 'dark' : 'light';
}

// "System" follows the device setting live; Light/Dark pin it.
export function applyAppearance(appearance) {
    stopFollowingSystem?.();
    stopFollowingSystem = null;

    if (appearance === 'System' && darkQuery) {
        setDark(darkQuery.matches);
        const onChange = (event) => setDark(event.matches);
        darkQuery.addEventListener('change', onChange);
        stopFollowingSystem = () => darkQuery.removeEventListener('change', onChange);
        return;
    }

    setDark(appearance === 'Dark');
}

export function clearAppearance() {
    stopFollowingSystem?.();
    stopFollowingSystem = null;
    setDark(false);
}

// Used by every signed-in screen (AppShell, and QuizPlay which has its own
// full-screen layout). Layout effects so switching pages never paints a frame
// in the wrong theme; screens outside the app (welcome, auth) stay light.
export function useMemberAppearance() {
    const { appearance, timezone } = usePreferences();

    useLayoutEffect(() => {
        applyAppearance(appearance);
    }, [appearance]);
    useLayoutEffect(() => clearAppearance, []);
    useEffect(() => {
        reportTimezone(timezone);
    }, [timezone]);
}

// ---- Notation -------------------------------------------------------------

// Movable-do with sharps for the chromatic keys (the quizzes' black keys are Di/Ri/Fi/Si/Li).
const SOLFEGE_NUMBERS = { Do: '1', Re: '2', Mi: '3', Fa: '4', So: '5', La: '6', Ti: '7', Di: '♯1', Ri: '♯2', Fi: '♯4', Si: '♯5', Li: '♯6' };
const SOLFEGE_WORD = /\b(Do|Re|Mi|Fa|So|La|Ti|Di|Ri|Fi|Si|Li)\b/g;

// Display only: answers are stored and graded as solfège, so this just swaps
// the syllables for scale-degree numbers on screen ("So - Do'" → "5 - 1'").
// Labels with no solfège syllable in them come back unchanged.
export function formatNotation(label, notation) {
    if (notation !== 'Numbers' || typeof label !== 'string') {
        return label;
    }

    return label.replace(SOLFEGE_WORD, (syllable) => SOLFEGE_NUMBERS[syllable]);
}

export function useNotation() {
    const { notation } = usePreferences();

    return (label) => formatNotation(label, notation);
}

// ---- Sound effects & haptics ---------------------------------------------

let audioContext = null;

function tone(context, frequency, start, duration, volume) {
    const oscillator = context.createOscillator();
    const gain = context.createGain();
    oscillator.type = 'sine';
    oscillator.frequency.value = frequency;
    gain.gain.setValueAtTime(0.0001, context.currentTime + start);
    gain.gain.exponentialRampToValueAtTime(volume, context.currentTime + start + 0.015);
    gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + start + duration);
    oscillator.connect(gain).connect(context.destination);
    oscillator.start(context.currentTime + start);
    oscillator.stop(context.currentTime + start + duration + 0.02);
}

// A short rising chime for a right answer, a low double buzz for a wrong one —
// generated, so there are no audio files to load. Kept quiet so it never
// masks the quiz audio.
export function playAnswerFeedback(correct, { sound_effects: soundEffects, haptics }) {
    if (soundEffects) {
        try {
            const Context = window.AudioContext || window.webkitAudioContext;
            audioContext ??= Context ? new Context() : null;
            if (audioContext) {
                audioContext.resume?.();
                if (correct) {
                    tone(audioContext, 880, 0, 0.12, 0.08);
                    tone(audioContext, 1318.5, 0.09, 0.2, 0.08);
                } else {
                    tone(audioContext, 196, 0, 0.14, 0.09);
                    tone(audioContext, 174.6, 0.15, 0.2, 0.09);
                }
            }
        } catch {
            // Audio is a nicety; never let it break answering.
        }
    }

    if (haptics && typeof navigator !== 'undefined' && navigator.vibrate) {
        navigator.vibrate(correct ? 30 : [40, 60, 40]);
    }
}

// ---- Timezone -------------------------------------------------------------

function xsrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

// Reminders go out at the member's local time, so tell the server which
// timezone this browser is in whenever it differs from the saved one.
export function reportTimezone(savedTimezone) {
    let timezone;
    try {
        timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    } catch {
        return;
    }

    if (!timezone || timezone === savedTimezone) {
        return;
    }

    fetch(route('settings.preferences'), {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
        body: JSON.stringify({ timezone }),
    }).catch(() => {});
}
