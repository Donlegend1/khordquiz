import icons from '@/data/instrumentIcons.json';

export const FAMILY_INSTRUMENTS = {
    Piano: 'Piano', Guitar: 'Guitar', Strings: 'Violin', Brass: 'Trumpet',
    Vocals: 'Voice', Percussion: 'Marimba', Woodwinds: 'Flute',
};

export default function InstrumentIcon({ sound, size = 24 }) {
    if (!icons[sound]) return null;

    return <span aria-hidden="true" className="inline-flex shrink-0 items-center justify-center" style={{ width: size, height: size }} dangerouslySetInnerHTML={{ __html: icons[sound] }} />;
}
