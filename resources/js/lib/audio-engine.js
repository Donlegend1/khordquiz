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
                .then((response) => {
                    if (!response.ok) throw new Error('Unable to load audio.');
                    return response.arrayBuffer();
                })
                .then((data) => getContext().decodeAudioData(data))
                .catch((error) => {
                    bufferCache.delete(url); // let a later call retry instead of caching a failure
                    throw error;
                })
        );
    }
    return bufferCache.get(url);
}

// A seekable live mix. Positions are seconds into the complete question, not
// seconds into a particular sample. Seeking into a chord resumes every voice
// from that point; voices from earlier chords stop at the next chord's onset.
export async function createNotePlayer(notes, { onEnded } = {}) {
    if (!notes.length) throw new Error('No audio notes available.');
    const ctx = getContext();
    const buffers = await Promise.all(notes.map((note) => loadBuffer(note.url)));
    const offsets = [...new Set(notes.map((note) => note.offset))].sort((a, b) => a - b);
    const segments = notes.map((note, index) => {
        const next = offsets.find((offset) => offset > note.offset) ?? Infinity;
        return {
            buffer: buffers[index],
            start: note.offset,
            end: Math.min(note.offset + buffers[index].duration, next),
            scale: 1 / Math.sqrt(notes.filter((other) => other.offset === note.offset).length),
            fades: Number.isFinite(next) && next <= note.offset + buffers[index].duration,
        };
    });
    const duration = Math.max(...segments.map((segment) => segment.end));
    let position = 0;
    let startedAt = 0;
    let playing = false;
    let destroyed = false;
    let revision = 0;
    let timer;
    let sources = [];

    function currentTime() {
        return Math.min(duration, position + (playing ? Math.max(0, ctx.currentTime - startedAt) : 0));
    }

    function clearSources() {
        clearTimeout(timer);
        sources.forEach(({ source, gain }) => {
            source.onended = null;
            try { source.stop(); } catch { /* Already stopped. */ }
            source.disconnect();
            gain.disconnect();
        });
        sources = [];
    }

    function pause() {
        position = currentTime();
        playing = false;
        revision++;
        clearSources();
    }

    async function play() {
        if (destroyed) return;
        pause();
        if (position >= duration) position = 0;
        const run = ++revision;
        if (ctx.state === 'suspended') await ctx.resume();
        if (destroyed || revision !== run) return;
        startedAt = ctx.currentTime + 0.05;
        playing = true;
        sources = segments.filter((segment) => segment.end > position).map((segment) => {
            const source = ctx.createBufferSource();
            const gain = ctx.createGain();
            source.buffer = segment.buffer;
            source.connect(gain);
            gain.connect(ctx.destination);
            const begins = Math.max(position, segment.start);
            const when = startedAt + begins - position;
            const end = startedAt + segment.end - position;
            let level = segment.scale;
            if (segment.fades) {
                const fadeAt = segment.end - 0.015;
                if (begins > fadeAt) level *= (segment.end - begins) / 0.015;
                gain.gain.setValueAtTime(level, when);
                gain.gain.setValueAtTime(level, Math.max(when, startedAt + fadeAt - position));
                gain.gain.linearRampToValueAtTime(0, end);
            } else {
                gain.gain.setValueAtTime(level, when);
            }
            source.start(when, begins - segment.start);
            source.stop(end);
            return { source, gain };
        });
        timer = setTimeout(() => {
            if (revision !== run || destroyed) return;
            position = duration;
            playing = false;
            clearSources();
            onEnded?.();
        }, (duration - position + 0.05) * 1000);
    }

    async function seek(seconds) {
        const resume = playing;
        pause();
        position = Math.max(0, Math.min(duration, Number.isFinite(seconds) ? seconds : 0));
        if (resume && position < duration) await play();
        else if (resume && position === duration) onEnded?.();
    }

    return {
        duration,
        get currentTime() { return currentTime(); },
        get paused() { return !playing; },
        play,
        pause,
        seek,
        async replay() {
            pause();
            position = 0;
            await play();
        },
        stop() {
            pause();
            position = 0;
        },
        destroy() {
            pause();
            destroyed = true;
        },
    };
}

// Existing preview/review callers still get their one-shot playback API.
export async function playNotes(notes, options = {}) {
    const player = await createNotePlayer(notes, options);
    try {
        await player.play();
        return player;
    } catch (error) {
        player.destroy();
        throw error;
    }
}
