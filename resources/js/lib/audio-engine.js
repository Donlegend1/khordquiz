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

    // Small lead-in so every source's start() call lands in the future relative
    // to ctx.currentTime by the time the loop below finishes scheduling them —
    // starting at currentTime itself risks a source silently missing its start.
    const startAt = ctx.currentTime + 0.05;
    const sources = buffers.map((buffer, index) => {
        const source = ctx.createBufferSource();
        source.buffer = buffer;
        source.connect(ctx.destination);
        source.start(startAt + notes[index].offset);
        return source;
    });

    const longestEnd = Math.max(...notes.map((note, index) => note.offset + buffers[index].duration));
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
