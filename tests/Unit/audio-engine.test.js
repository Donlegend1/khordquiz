import assert from 'node:assert/strict';
import { test } from 'node:test';
import { createNotePlayer, playNotes } from '../../resources/js/lib/audio-engine.js';

const sources = [];
const context = {
    currentTime: 0,
    state: 'running',
    destination: {},
    async resume() { this.state = 'running'; },
    async decodeAudioData(data) { return { duration: data.duration }; },
    createBufferSource() {
        const source = {
            connect() {}, disconnect() {},
            start(...args) { this.started = args; },
            stop(...args) { this.stops.push(args); },
            stops: [],
        };
        sources.push(source);
        return source;
    },
    createGain() {
        return { connect() {}, disconnect() {}, gain: { setValueAtTime() {}, linearRampToValueAtTime() {} } };
    },
};
globalThis.window = { AudioContext: function () { return context; } };
globalThis.fetch = async () => ({ ok: true, async arrayBuffer() { return { duration: 3 }; } });

function reset(t) {
    sources.length = 0;
    context.currentTime = 0;
    context.state = 'running';
    t.mock.timers.enable({ apis: ['setTimeout'] });
}

function assertStart(source, when, offset) {
    assert.ok(Math.abs(source.started[0] - when) < 0.000001);
    assert.equal(source.started[1], offset);
}

const chords = [
    { url: '/first-root.wav', offset: 0 }, { url: '/first-third.wav', offset: 0 },
    { url: '/second-root.wav', offset: 2 }, { url: '/second-third.wav', offset: 2 },
];

test('seeking into a live chord resumes its voices and skips the earlier chord', async (t) => {
    reset(t);
    const player = await createNotePlayer(chords);
    t.after(() => player.destroy());
    assert.equal(player.duration, 5);
    await player.play();
    assert.equal(sources.length, 4);
    assertStart(sources[0], 0.05, 0);
    assert.deepEqual(sources[0].stops[0], [2.05]);
    const old = sources.slice();
    await player.seek(3);
    assert.ok(old.every((source) => source.stops.length === 2));
    assert.equal(sources.length, 6);
    assertStart(sources[4], 0.05, 1);
    assertStart(sources[5], 0.05, 1);
    assert.equal(player.currentTime, 3);
    assert.equal(player.paused, false);
});

test('pause preserves position, paused seeking stays silent, and replay starts at zero', async (t) => {
    reset(t);
    const player = await createNotePlayer(chords);
    t.after(() => player.destroy());
    await player.play();
    context.currentTime = 1.55;
    player.pause();
    assert.equal(player.currentTime, 1.5);
    assert.equal(player.paused, true);
    await player.seek(3);
    assert.equal(sources.length, 4);
    await player.play();
    assertStart(sources[4], 1.6, 1);
    await player.replay();
    assert.equal(player.currentTime, 0);
    assertStart(sources[6], 1.6, 0);
    player.stop();
    assert.equal(player.currentTime, 0);
    assert.equal(player.paused, true);
});

test('repeated seeking and replay leave only one completion callback', async (t) => {
    reset(t);
    let ended = 0;
    const player = await createNotePlayer(chords, { onEnded: () => ended++ });
    t.after(() => player.destroy());
    await player.play();
    await player.seek(3);
    await player.replay();
    context.currentTime = 6;
    t.mock.timers.tick(5100);
    assert.equal(ended, 1);
    assert.equal(player.paused, true);
    assert.equal(player.currentTime, 5);
    t.mock.timers.tick(10000);
    assert.equal(ended, 1);
});

test('destroy cancels a pending audio-context resume and its scheduled playback', async (t) => {
    reset(t);
    const originalResume = context.resume;
    let resume;
    context.state = 'suspended';
    context.resume = () => new Promise((resolve) => { resume = resolve; });
    t.after(() => { context.resume = originalResume; });
    const player = await createNotePlayer(chords);
    const pending = player.play();
    player.destroy();
    resume();
    await pending;
    assert.equal(sources.length, 0);
    assert.equal(player.paused, true);
});

test('existing one-shot previews still play and can stop without firing onEnded', async (t) => {
    reset(t);
    let ended = 0;
    const handle = await playNotes(chords, { onEnded: () => ended++ });
    handle.stop();
    t.mock.timers.tick(10000);
    assert.equal(ended, 0);
    assert.ok(sources.every((source) => source.stops.length === 2));
});

test('a failed sample download can be retried instead of caching an error', async (t) => {
    reset(t);
    const originalFetch = globalThis.fetch;
    globalThis.fetch = async () => ({ ok: false });
    t.after(() => { globalThis.fetch = originalFetch; });
    const notes = [{ url: '/retry-test.wav', offset: 0 }];
    await assert.rejects(createNotePlayer(notes), /Unable to load audio/);
    globalThis.fetch = originalFetch;
    const player = await createNotePlayer(notes);
    assert.equal(player.duration, 3);
    player.destroy();
});
