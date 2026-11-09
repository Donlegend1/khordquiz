import assert from 'node:assert/strict';
import { test } from 'node:test';
import { reviewAnswerLabel, reviewSelection } from '../../resources/js/lib/reviewAnswers.js';
import { encodeNamingAnswer } from '../../resources/js/lib/chordNaming.js';

test('review reconstructs the correct note sequence, extensions and compound selections', () => {
    assert.deepEqual(reviewSelection('Do Mi So', null).sequence, ['Do', 'Mi', 'So']);
    assert.deepEqual(reviewSelection('7, 9, ♯11', null).selected, ['7', '9', '♯11']);
    const compound = reviewSelection('Minor · First inversion', null);
    assert.equal(compound.quality, 'Minor');
    assert.equal(compound.inversion, 'First inversion');
});

test('seventh-chord quality parentheses are preserved while extensions are decoded separately', () => {
    const config = { length: 3, fixedDegrees: ['2', '5', '1'], qualityOptions: ['Minor 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Major 7th'] };
    const state = reviewSelection('2 Minor 7(♭5)(9, 11) - 5 Dominant 7th(♭9, 13) - 1 Major 7th(9)', config);
    assert.deepEqual(state.slots, [
        { degree: '2', quality: 'Minor 7(♭5)', extensions: ['9', '11'] },
        { degree: '5', quality: 'Dominant 7th', extensions: ['♭9', '13'] },
        { degree: '1', quality: 'Major 7th', extensions: ['9'] },
    ]);
});

test('review handles diatonic fixed qualities and quality-only chordal progressions', () => {
    assert.deepEqual(reviewSelection('1 Major - 6 Minor', { length: 2, fixedQualities: { 1: 'Major', 6: 'Minor' } }).slots, [
        { degree: '1', quality: 'Major', extensions: [] }, { degree: '6', quality: 'Minor', extensions: [] },
    ]);
    assert.deepEqual(reviewSelection('Minor 7(♭5) - Major 7(♯5)', { length: 2, qualityOnly: true, qualityOptions: ['Minor 7(♭5)', 'Major 7(♯5)'] }).slots, [
        { degree: null, quality: 'Minor 7(♭5)', extensions: [] }, { degree: null, quality: 'Major 7(♯5)', extensions: [] },
    ]);
});

test('encoded chord naming answers become preselected fields and readable names', () => {
    const answer = encodeNamingAnswer('Major', ['♭7', '9'], 'No 5');
    assert.deepEqual(reviewSelection(answer).naming, { quality: 'Major', degrees: ['♭7', '9'], exclude: 'No 5' });
    assert.match(reviewAnswerLabel(answer, 'naming'), /Dominant/);
    assert.doesNotMatch(reviewAnswerLabel(answer, 'naming'), /v2\|/);
    assert.equal(reviewAnswerLabel('C#/Db', 'keyboard'), 'C#/Db');
    assert.equal(reviewAnswerLabel(null, 'flat'), 'No answer selected');
});
