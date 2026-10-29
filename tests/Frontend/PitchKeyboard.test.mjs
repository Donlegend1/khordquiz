import assert from 'node:assert/strict';
import test from 'node:test';
import { clampPitchPage, defaultPitchPage, playablePitchOctaves } from '../../resources/js/lib/pitchKeyboard.js';

test('only recorded octave pages are navigable, including partial octaves', () => {
    assert.deepEqual(playablePitchOctaves({ G2: '/g2', C3: '/c3', G5: '/g5', C7: null }), [2, 3, 5]);
});

test('the default is octave three when available', () => {
    assert.equal(defaultPitchPage([2, 3, 4, 5, 6]), 1);
    assert.equal(defaultPitchPage([3]), 0);
});

test('instruments without octave three start at the nearest playable octave', () => {
    assert.equal(defaultPitchPage([4, 5, 6]), 0);
    assert.equal(defaultPitchPage([1, 2]), 1);
});

test('navigation stops at the first and last available page', () => {
    assert.equal(clampPitchPage(-1, 5), 0);
    assert.equal(clampPitchPage(5, 5), 4);
    assert.equal(clampPitchPage(2, 5), 2);
    assert.equal(clampPitchPage(1, 1), 0);
});

test('an empty instrument has no octave pages', () => {
    assert.deepEqual(playablePitchOctaves({}), []);
    assert.equal(defaultPitchPage([]), 0);
    assert.equal(clampPitchPage(1, 0), 0);
});
