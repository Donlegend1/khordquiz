import { buildNamedChord, decodeNamingAnswer } from './chordNaming.js';

// Reconstruct the original answer widgets without enabling their editing state.
export function reviewSelection(answer, config) {
    const text = answer ?? '';
    const [quality = null, inversion = null] = text.split(' · ');
    const parts = text.split(' - ');
    const qualities = [...new Set([
        ...(config?.qualityOptions ?? []),
        ...Object.values(config?.qualityOptionsByDegree ?? {}).flat(),
        ...Object.values(config?.fixedQualities ?? {}),
    ])].sort((a, b) => b.length - a.length);
    return {
        sequence: text ? text.split(' ') : [],
        quality,
        inversion,
        selected: text ? text.split(', ') : [],
        naming: decodeNamingAnswer(text),
        slots: Array.from({ length: config?.length ?? 0 }, (_, index) => {
            let part = parts[index] ?? '';
            const degree = config.qualityOnly ? null : (config.fixedDegrees?.[index] ?? part.split(' ')[0]);
            if (!config.qualityOnly) part = part.slice(part.indexOf(' ') + 1);
            const quality = qualities.find((option) => part === option || part.startsWith(`${option}(`)) ?? part;
            const suffix = part.slice(quality.length);
            return { degree, quality, extensions: suffix.startsWith('(') ? suffix.slice(1, -1).split(', ').filter(Boolean) : [] };
        }),
    };
}

export function reviewAnswerLabel(answer, type) {
    if (!answer) return 'No answer selected';
    if (type !== 'naming') return answer;
    const decoded = decodeNamingAnswer(answer);
    return decoded ? buildNamedChord(decoded.quality, decoded.degrees, decoded.exclude) : answer;
}
