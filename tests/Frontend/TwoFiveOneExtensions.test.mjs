import assert from 'node:assert/strict';
import test from 'node:test';
import { TWO_FIVE_ONE_CONFIG, progressionPartMatches, twoFiveOneAnswersMatch } from '../../resources/js/lib/twoFiveOneExtensions.js';
import { composeProgression, progressionFor } from '../../resources/js/lib/quizAnswerPresets.js';

test('quality options follow the 4/3/5 rule and require extensions', () => {
  assert.deepEqual(Object.values(TWO_FIVE_ONE_CONFIG.qualityOptionsByDegree).map(x => x.length).sort(), [3, 4, 5]);
  assert.deepEqual(progressionFor('Chord Progressions', '2-5-1 chord degree'), TWO_FIVE_ONE_CONFIG);
  assert.equal(composeProgression(TWO_FIVE_ONE_CONFIG, ['Minor 7th', 'Dominant 7th', 'Major 7th'].map(quality => ({ quality, extensions: [] }))), '');
  assert(!TWO_FIVE_ONE_CONFIG.extensionOptionsByQuality['Dominant 7th'].includes('11'));
  assert(!TWO_FIVE_ONE_CONFIG.extensionOptionsByQuality['Minor 6'].includes('13'));
});

test('flat-five qualities and reordered extensions retain correct per-chord grading', () => {
  const a = '2 Minor 7(♭5)(9, 11) - 5 Dominant 7th(♭9, ♯11, 13) - 1 Major 7th(9, 13)';
  const b = '2 Minor 7(♭5) (11, 9) - 5 Dominant 7th(13, ♯11, ♭9) - 1 Major 7th(13, 9)';
  assert(twoFiveOneAnswersMatch(a, b));
  assert(progressionPartMatches(a.split(' - ')[0], b.split(' - ')[0]));
  assert(!twoFiveOneAnswersMatch(a, b.replace('(11, 9)', '(♭9, 11)')));
  assert(!twoFiveOneAnswersMatch(a, b.split(' - ').reverse().join(' - ')));
});
