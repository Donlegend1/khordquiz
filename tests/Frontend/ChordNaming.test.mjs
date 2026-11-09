import assert from 'node:assert/strict';
import test from 'node:test';
import {
  NAMING_QUALITIES, NAMING_DEGREES, buildNamedChord,
  decodeNamingAnswer, encodeNamingAnswer, namingDegreeOptions, namingDisplayQuality,
  namingExcludeOptions, toggleNamingDegree, validNamingSelection, namingAnswersMatch,
} from '../../resources/js/lib/chordNaming.js';

test('six qualities use explicit seventh degrees', () => {
  assert.deepEqual(NAMING_QUALITIES, ['Major', 'Minor', 'Augmented', 'Diminished', 'Sus2', 'Sus4']);
  assert(NAMING_DEGREES.includes('♭7'));
  assert(NAMING_DEGREES.includes('7'));
  assert(!NAMING_DEGREES.includes('𝄫7'));
  assert(!NAMING_DEGREES.includes('Sus 4'));
});

test('quality-first names distinguish the seventh types', () => {
  assert.equal(buildNamedChord('Major', ['♭7'], 'None'), 'Dominant 7');
  assert.equal(namingDisplayQuality('Major', ['♭7']), 'Dominant');
  assert.equal(encodeNamingAnswer('Major', ['♭7'], 'None'), 'v2|Major|♭7|None');
  assert.equal(buildNamedChord('Major', ['7'], 'None'), 'Major 7');
  assert.equal(buildNamedChord('Major', ['♭7', '9'], 'None'), 'Dominant 9');
  assert.equal(buildNamedChord('Major', ['♭7', '♭9', '♯11'], 'None'), 'Dominant 7(♭9, ♯11)');
  assert.equal(buildNamedChord('Major', ['♭7', '♯11', '13'], 'No 5'), 'Dominant 7(♯11, 13) (No 5)');
  assert.equal(buildNamedChord('Major', ['7', '13'], 'None'), 'Major 7(13)');
  assert.equal(buildNamedChord('Minor', ['♭7'], 'None'), 'Minor 7');
  assert.equal(buildNamedChord('Minor', ['7'], 'None'), 'Minor maj7');
  assert.equal(buildNamedChord('Diminished', ['7'], 'None'), 'Diminished 7');
  assert.equal(buildNamedChord('Sus4', ['9'], 'None'), 'Sus4 add 9');
});

test('choices enforce the agreed degree, omission, and suspension rules', () => {
  assert.equal(validNamingSelection('Major', [], 'None'), false);
  assert.equal(validNamingSelection('Major', ['6'], 'No 5'), false);
  assert.equal(validNamingSelection('Major', ['♭7'], 'No 5'), true);
  assert.equal(validNamingSelection('Minor', ['7', '♭7'], 'None'), false);
  assert.equal(validNamingSelection('Sus4', ['6'], 'None'), false);
  assert(!namingDegreeOptions('Sus2').includes('6'));
  assert.deepEqual(namingExcludeOptions(['9']), ['None']);
  assert.deepEqual(toggleNamingDegree(['7', '9'], '♭7'), ['9', '♭7']);
  assert.deepEqual(toggleNamingDegree(['♭7', '♭9'], '♯9'), ['♭7', '♭9', '♯9']);
  assert.equal(validNamingSelection('Major', ['♭7', '♭9', '♯9', '♭13'], 'None'), true);
  assert.equal(buildNamedChord('Major', ['♭7', '♭9', '♯9', '♭13'], 'None'), 'Dominant 7(♭9, ♯9, ♭13)');
});

test('old answers map to the new controls without reinterpreting new minor-major sevenths', () => {
  assert.deepEqual(decodeNamingAnswer('Dominant|9|None'), { quality: 'Major', degrees: ['♭7', '9'], exclude: 'None' });
  assert.deepEqual(decodeNamingAnswer('Minor|7|No 3'), { quality: 'Minor', degrees: ['♭7'], exclude: 'No 3' });
  assert.equal(decodeNamingAnswer(encodeNamingAnswer('Minor', ['7'], 'None')).degrees[0], '7');
  assert.equal(encodeNamingAnswer('Major', ['9', '♭7'], 'None'), encodeNamingAnswer('Major', ['♭7', '9'], 'None'));
});

test('secondary chord controls distinguish diminished sevenths and accept equivalent qualities', () => {
  assert(namingDegreeOptions('Diminished').includes('maj7'));
  assert(!namingDegreeOptions('Minor').includes('maj7'));
  assert.equal(validNamingSelection('Diminished', ['7', 'maj7'], 'None'), false);
  assert.deepEqual(toggleNamingDegree(['7', '9'], 'maj7'), ['9', 'maj7']);
  assert.deepEqual(toggleNamingDegree(['maj7', '9'], '♭7'), ['9', '♭7']);
  assert.equal(buildNamedChord('Diminished', ['7', '9', '11'], 'None'), 'Diminished 7(9, 11)');
  assert.equal(buildNamedChord('Diminished', ['maj7', '9', '11'], 'None'), 'Diminished maj7(9, 11)');
  assert.equal(buildNamedChord('Augmented', ['♭7', '9'], 'None'), 'Augmented 9');
  assert.equal(buildNamedChord('Major', ['♯5', '7', '9', '♯11'], 'None'), 'Major 9(♯5, ♯11)');
  assert(namingAnswersMatch('v2|Minor|9,7,♭5|None', 'v2|Diminished|maj7,9|None'));
  assert(namingAnswersMatch('v2|Augmented|9,7|None', 'v2|Major|♯5,7,9|None'));
  assert(namingAnswersMatch('v2|Major|♯5,♭7,9|None', 'v2|Augmented|♭7,9|None'));
  assert(namingAnswersMatch('v2|Diminished|♭7,11|None', 'v2|Minor|♭5,♭7,11|None'));
  assert(!namingAnswersMatch('v2|Diminished|7,9|None', 'v2|Diminished|maj7,9|None'));
});

test('omissions grade by the remaining tones while keeping the seventh and extensions distinct', () => {
  assert(!namingAnswersMatch('v2|Diminished|6|None', 'v2|Diminished|7|None'));
  assert(namingAnswersMatch('v2|Major|♭7,9,11|No 3', 'v2|Minor|♭7,9,11|No 3'));
  assert(namingAnswersMatch('v2|Augmented|7,9|No 5', 'v2|Major|7,9|No 5'));
  assert(namingAnswersMatch('v2|Minor|♭5,♭7,9|No 5', 'v2|Minor|♭7,9|No 5'));
  assert(!namingAnswersMatch('v2|Major|7,9,11|No 3', 'v2|Minor|♭7,9,11|No 3'));
  assert(!namingAnswersMatch('v2|Major|♭7,9|No 3', 'v2|Minor|♭7,9,11|No 3'));
  assert(!namingAnswersMatch('v2|Major|♭7,9,11|None', 'v2|Minor|♭7,9,11|No 3'));
});
