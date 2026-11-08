export const TWO_FIVE_ONE_CONFIG = {
    length: 3,
    fixedDegrees: ['2', '5', '1'],
    qualityOptions: ['Minor 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Minor 6', 'Major 7th', 'Major 6'],
    qualityOptionsByDegree: {
        2: ['Minor 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Minor 6'],
        5: ['Dominant 7th', 'Minor 7th', 'Minor 7(♭5)'],
        1: ['Dominant 7th', 'Minor 7th', 'Major 7th', 'Major 6', 'Minor 6'],
    },
    extensionOptions: ['♭9', '9', '♯9', '11', '♯11', '♭13', '13'],
    extensionOptionsByQuality: {
        'Minor 7th': ['9', '11', '13'],
        'Minor 7(♭5)': ['♭9', '9', '11'],
        'Dominant 7th': ['♭9', '9', '♯9', '♯11', '♭13', '13'],
        'Minor 6': ['9', '11'],
        'Major 7th': ['9', '♯11', '13'],
        'Major 6': ['9', '♯11'],
    },
    requireExtensions: true,
    extensionOrderIndependent: true,
};

function normalizePart(part) {
    const match = part?.trim().match(/^([251])\s+(.+?)\s*\(([^()]*)\)$/u);
    if (!match) return null;
    const extensions = match[3].split(/\s*,\s*/).sort();
    if (extensions.includes('')) return null;
    return JSON.stringify([match[1], match[2].trim(), extensions]);
}

export function progressionPartMatches(submitted, expected) {
    const left = normalizePart(submitted);
    return left !== null && left === normalizePart(expected);
}

export function twoFiveOneAnswersMatch(submitted, expected) {
    const left = submitted.split(' - ');
    const right = expected.split(' - ');
    return left.length === 3 && right.length === 3 && left.every((part, index) => progressionPartMatches(part, right[index]));
}
