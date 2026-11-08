export const NAMING_QUALITIES = ['Major', 'Minor', 'Augmented', 'Diminished', 'Sus2', 'Sus4'];
export const NAMING_DEGREES = ['♭5', '♯5', '6', '♭7', '7', 'maj7', '♭9', '9', '♯9', '♭11', '11', '♯11', '♭13', '13', '♯13'];
export const NAMING_EXCLUDES = ['No 3', 'No 5', 'None'];

export function namingDegreeOptions(quality) {
    return NAMING_DEGREES.filter((degree) => !(quality?.startsWith('Sus') && degree === '6') && (degree !== 'maj7' || quality === 'Diminished'));
}

export function namingExcludeOptions(degrees) {
    return degrees.some((degree) => degree === '7' || degree === '♭7' || degree === 'maj7') ? NAMING_EXCLUDES : ['None'];
}

export function toggleNamingDegree(degrees, option) {
    if (degrees.includes(option)) return degrees.filter((degree) => degree !== option);
    const sevenths = ['7', '♭7', 'maj7'];
    const opposites = { '♭5': '♯5', '♯5': '♭5' };
    return [...degrees.filter((degree) => sevenths.includes(option) ? !sevenths.includes(degree) : degree !== opposites[option]), option];
}

export function validNamingSelection(quality, degrees, exclude) {
    return NAMING_QUALITIES.includes(quality) && degrees.length > 0
        && degrees.every((degree) => namingDegreeOptions(quality).includes(degree))
        && degrees.filter((degree) => ['7', '♭7', 'maj7'].includes(degree)).length <= 1
        && !(degrees.includes('♭5') && degrees.includes('♯5'))
        && namingExcludeOptions(degrees).includes(exclude);
}

export function encodeNamingAnswer(quality, degrees, exclude) {
    return `v2|${quality}|${NAMING_DEGREES.filter((degree) => degrees.includes(degree)).join(',')}|${exclude ?? 'None'}`;
}

export function decodeNamingAnswer(value) {
    const parts = (value ?? '').split('|');
    const modern = parts[0] === 'v2';
    if (modern) parts.shift();
    let [quality = '', text = '', exclude = 'None'] = parts;
    let degrees = text ? text.split(',') : [];
    if (!modern) {
        if (quality === 'Minor') degrees = degrees.map((degree) => degree === '7' ? '♭7' : degree);
        if (quality === 'Dominant') {
            quality = 'Major';
            degrees = [...degrees.filter((degree) => degree !== '7'), '♭7'];
        }
        const suspension = degrees.find((degree) => degree === 'Sus 2' || degree === 'Sus 4');
        if (suspension) {
            if (quality === 'Augmented') degrees.push('♯5');
            if (quality === 'Diminished') degrees.push('♭5');
            quality = suspension.replace(' ', '');
            degrees = degrees.filter((degree) => degree !== 'Sus 2' && degree !== 'Sus 4');
        }
    }
    return { quality, degrees: NAMING_DEGREES.filter((degree) => degrees.includes(degree)), exclude };
}

export function namingDisplayQuality(quality, degrees) {
    return quality === 'Major' && degrees.includes('♭7') ? 'Dominant' : (quality ?? '');
}

export function buildNamedChord(quality, degrees, exclude) {
    const displayQuality = namingDisplayQuality(quality, degrees);
    const seventh = degrees.includes('7') || degrees.includes('♭7') || degrees.includes('maj7');
    const has = (degree) => degrees.includes(degree);
    const naturals = ['9', '11', '13'].filter(has);
    const explicitFlatSeventh = has('♭7') && !['Minor', 'Dominant', 'Augmented'].includes(displayQuality);
    const extension = has('9') ? (has('11') ? (has('13') ? '13' : '11') : '9') : '7';
    const number = seventh ? (explicitFlatSeventh || quality === 'Diminished' ? '7' : extension) : has('6') ? (has('9') ? '6/9' : '6') : '';
    const seventhLabel = has('maj7') ? 'maj' : has('♭7') ? (['Minor', 'Dominant', 'Augmented'].includes(displayQuality) ? '' : '♭') : has('7') && ['Minor', 'Augmented', 'Sus2', 'Sus4'].includes(quality) ? 'maj' : '';
    // Keep an explicit flat seventh beside extensions so ♭9 never substitutes for ♭7 + 9.
    const core = number ? (seventhLabel === '♭' && number !== '7' ? `♭7 add ${number}` : `${seventhLabel}${number}`) : '';
    const altered = ['♭5', '♯5', '♭9', '♯9', '♭11', '♯11', '♭13', '♯13'].filter(has);
    const remainingNaturals = seventh ? naturals.filter((degree) => Number(degree) > Number(number)) : [];
    const bracketed = seventh || has('6') ? NAMING_DEGREES.filter((degree) => altered.includes(degree) || remainingNaturals.includes(degree)) : altered.filter((degree) => degree === '♭5' || degree === '♯5');
    const adds = seventh ? (has('6') ? ['6'] : []) : [...naturals.filter((degree) => !(has('6') && degree === '9')), ...(!has('6') ? altered.filter((degree) => degree !== '♭5' && degree !== '♯5') : [])];
    return displayQuality + (core ? ` ${core}` : '') + (bracketed.length ? `(${bracketed.join(', ')})` : '')
        + (adds.length ? ` add ${adds.join(', ')}` : '') + (exclude && exclude !== 'None' ? ` (${exclude})` : '');
}

function canonicalNamingAnswer(answer) {
    let { quality, degrees, exclude } = answer;
    if (quality === 'Augmented' && degrees.includes('7')) {
        quality = 'Major';
        degrees = [...degrees, '♯5'];
    } else if (quality === 'Major' && degrees.includes('♯5') && degrees.includes('♭7')) {
        quality = 'Augmented';
        degrees = degrees.filter((degree) => degree !== '♯5');
    } else if (quality === 'Minor' && degrees.includes('♭5') && degrees.includes('7')) {
        quality = 'Diminished';
        degrees = [...degrees.filter((degree) => !['♭5', '7'].includes(degree)), 'maj7'];
    } else if (quality === 'Diminished' && degrees.includes('♭7')) {
        quality = 'Minor';
        degrees = [...degrees, '♭5'];
    }
    return encodeNamingAnswer(quality, degrees, exclude);
}

export function namingIntervals(answer) {
    const { quality, degrees, exclude } = answer;
    if (!validNamingSelection(quality, degrees, exclude)) return null;
    const triads = { Major: [0, 4, 7], Minor: [0, 3, 7], Augmented: [0, 4, 8], Diminished: [0, 3, 6], Sus2: [0, 2, 7], Sus4: [0, 5, 7] };
    const tones = [...triads[quality]];
    if (degrees.includes('♭5')) tones[2] = 6;
    else if (degrees.includes('♯5')) tones[2] = 8;
    const remaining = tones.filter((_, index) => !(exclude === 'No 3' && index === 1) && !(exclude === 'No 5' && index === 2));
    const mapping = { '6': 9, '♭7': 10, '7': quality === 'Diminished' ? 9 : 11, maj7: 11, '♭9': 13, '9': 14, '♯9': 15, '♭11': 16, '11': 17, '♯11': 18, '♭13': 20, '13': 21, '♯13': 22 };
    for (const degree of degrees) {
        if (mapping[degree] !== undefined) remaining.push(mapping[degree]);
    }
    return [...new Set(remaining)].sort((a, b) => a - b);
}

export function namingAnswersMatch(submitted, correct) {
    const selected = decodeNamingAnswer(submitted);
    const expected = decodeNamingAnswer(correct);
    return validNamingSelection(selected.quality, selected.degrees, selected.exclude)
        && validNamingSelection(expected.quality, expected.degrees, expected.exclude)
        && selected.exclude === expected.exclude
        && (expected.exclude === 'None'
            ? canonicalNamingAnswer(selected) === canonicalNamingAnswer(expected)
            : JSON.stringify(namingIntervals(selected)) === JSON.stringify(namingIntervals(expected)));
}
