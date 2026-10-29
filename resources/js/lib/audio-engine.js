// Live note-mixing playback engine — combines individual note sample files at
// playback time (simultaneously for chords/harmonic intervals, offset in time
// for melodies/melodic intervals) instead of fetching one pre-rendered
// combination file. See app/Support/NoteSample.php for the backend half of
// this: it's what turns a question's audio_path into the {url, offset}[]
// this module expects.
//
// Mirrored on mobile by src/lib/audio-engine.ts in the KhordQuiz app repo —
// keep the two in sync by hand (there's no shared JS/TS package between the
// two repos).

let sharedContext = null;
const bufferCache = new Map(); // url -> Promise<AudioBuffer>

function getContext() {
    if (!sharedContext) {
        const Ctor = window.AudioContext || window.webkitAudioContext;
        sharedContext = new Ctor();
    }
    return sharedContext;
}

function loadBuffer(url) {
    if (!bufferCache.has(url)) {
        bufferCache.set(
            url,
            fetch(url)
                .then((response) => response.arrayBuffer())
                .then((data) => getContext().decodeAudioData(data))
                .catch((error) => {
                    bufferCache.delete(url); // let a later call retry instead of caching a failure
                    throw error;
                })
        );
    }
    return bufferCache.get(url);
}

// notes: [{ url, offset }], offset in seconds from playback start.
// Returns { stop } — call stop() to cut playback short (e.g. a new question
// arriving before this one finished). onEnded fires once, after the last
// scheduled note's own buffer has finished playing.
export async function playNotes(notes, { onEnded } = {}) {
    const ctx = getContext();
    if (ctx.state === 'suspended') {
        await ctx.resume();
    }

    const buffers = await Promise.all(notes.map((note) => loadBuffer(note.url)));

    // A note's own raw sample often rings far longer than the gap to the next
    // note in a melodic sequence (a sustained instrument like Piano/EP/Organ
    // can still be clearly audible a full second later) — left unchecked, the
    // next note starts on top of it, audibly blending the two together
    // instead of a clean "first note stops, second starts" transition (the
    // same issue found and fixed in the pre-rendered interval files; this is
    // its live-mixing-engine equivalent, since every note here plays its full
    // natural buffer length with nothing ever cutting it short). Only applies
    // between notes at different offsets — simultaneous notes (a chord/
    // harmonic interval, same offset) are meant to ring together and are left
    // untouched.
    // Matches the pre-rendered Guitar interval audio exactly: the first note
    // stops the instant the second one starts, in the same split second, not
    // a moment before. FADE_SECONDS was 0.15 — long enough to be audible on
    // its own as the first note visibly dying away, which itself reads as a
    // gap even though it was timed to reach silence exactly on the next
    // note's onset. Cut way down to just long enough to avoid a hard click,
    // short enough to not be heard as a fade at all.
    const FADE_SECONDS = 0.015;
    const GAP_SECONDS = 0;

    // Small lead-in so every source's start() call lands in the future relative
    // to ctx.currentTime by the time the loop below finishes scheduling them —
    // starting at currentTime itself risks a source silently missing its start.
    const startAt = ctx.currentTime + 0.05;
    const sources = buffers.map((buffer, index) => {
        const offset = notes[index].offset;
        const nextOffset = Math.min(
            ...notes.filter((note) => note.offset > offset).map((note) => note.offset),
            Infinity
        );

        const source = ctx.createBufferSource();
        source.buffer = buffer;

        const gain = ctx.createGain();
        source.connect(gain);
        gain.connect(ctx.destination);

        const startTime = startAt + offset;
        source.start(startTime);

        if (Number.isFinite(nextOffset)) {
            const cutoffTime = startAt + Math.max(offset, nextOffset - GAP_SECONDS);
            const fadeStart = Math.max(startTime, cutoffTime - FADE_SECONDS);
            gain.gain.setValueAtTime(1, fadeStart);
            gain.gain.linearRampToValueAtTime(0, cutoffTime);
            source.stop(cutoffTime);
        }

        return source;
    });

    const effectiveEnd = (note, index) => {
        const nextOffset = Math.min(
            ...notes.filter((other) => other.offset > note.offset).map((other) => other.offset),
            Infinity
        );
        return Number.isFinite(nextOffset) ? Math.max(note.offset, nextOffset - GAP_SECONDS) : note.offset + buffers[index].duration;
    };
    const longestEnd = Math.max(...notes.map((note, index) => effectiveEnd(note, index)));
    const timeoutId = setTimeout(() => onEnded?.(), longestEnd * 1000 + 50);

    return {
        stop: () => {
            clearTimeout(timeoutId);
            sources.forEach((source) => {
                try {
                    source.stop();
                } catch {
                    // Already finished/stopped — nothing to do.
                }
            });
        },
    };
}
