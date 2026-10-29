// Kept in sync with NoteSample::SCIENTIFIC_PITCH_OCTAVES.
const OCTAVES = [1, 2, 3, 4, 5, 6, 7];

export function playablePitchOctaves(keyboardAudio) {
    const octaves = new Set(Object.entries(keyboardAudio)
        .filter(([, url]) => Boolean(url))
        .map(([pitch]) => Number(pitch.match(/(\d+)$/)?.[1])));
    return OCTAVES.filter((octave) => octaves.has(octave));
}

export function defaultPitchPage(octaves) {
    if (!octaves.length) return 0;
    return octaves.reduce((nearest, octave, index) =>
        Math.abs(octave - 3) < Math.abs(octaves[nearest] - 3) ? index : nearest, 0);
}

export function clampPitchPage(index, pageCount) {
    return Math.max(0, Math.min(index, Math.max(0, pageCount - 1)));
}
