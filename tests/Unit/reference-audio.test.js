import assert from 'node:assert/strict';
import { test } from 'node:test';
import { shuffled, reshuffleAvoiding } from '../../resources/js/lib/referenceAudio.js';

test('a pass includes every distinct clip once without mutating the source pool', () => {
    const ids = Array.from({ length: 2400 }, (_, i) => `clip-${i}`);
    const original = [...ids];
    const pass = shuffled(ids);
    assert.notEqual(pass, ids);
    assert.equal(new Set(pass).size, ids.length);
    assert.deepEqual([...pass].sort(), [...ids].sort());
    assert.deepEqual(ids, original);
});

test('a new pass never starts with the clip that just finished', () => {
    const ids = ['a', 'b', 'c', 'd'];
    const saved = Math.random;
    try {
        // Leave the source order unchanged, forcing the boundary-repeat case.
        Math.random = () => 0.999;
        const pass = reshuffleAvoiding(ids, 'a');
        assert.notEqual(pass[0], 'a');
        assert.deepEqual([...pass].sort(), ids);
        assert.deepEqual(ids, ['a', 'b', 'c', 'd']);
    } finally {
        Math.random = saved;
    }
});

test('empty and single-clip pools remain usable without an impossible shuffle loop', () => {
    assert.deepEqual(shuffled([]), []);
    assert.deepEqual(reshuffleAvoiding([], 'a'), []);
    assert.deepEqual(reshuffleAvoiding(['a'], 'a'), ['a']);
});
