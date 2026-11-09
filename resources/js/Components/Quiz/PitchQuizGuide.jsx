import { tk, useTrans } from '@/lib/i18n';
import QuizQuickGuide, { GuideExampleChoice } from './QuizQuickGuide';

function ExampleKeyboard({ withOctave }) {
    const t = useTrans();
    const whiteKeys = ['C', 'D', 'E', 'F', 'G', 'A', 'B'];
    const blackKeys = [{ after: 0, sharp: 'C♯', flat: 'D♭' }, { after: 1, sharp: 'D♯', flat: 'E♭' }, { after: 3, sharp: 'F♯', flat: 'G♭' }, { after: 4, sharp: 'G♯', flat: 'A♭' }, { after: 5, sharp: 'A♯', flat: 'B♭' }];
    return (
        <div role="img" aria-label={withOctave ? t('Example keyboard with G4 selected') : t('Example keyboard with F sharp or G flat selected')} className="relative mt-4 flex h-28 overflow-hidden rounded-xl border border-black/10 bg-white">
            {whiteKeys.map((note, index) => (
                <span aria-hidden="true" key={note} className={`flex min-w-0 flex-1 items-end justify-center pb-3 text-[10px] font-bold ${index < 6 ? 'border-r border-neutral-200' : ''} ${withOctave && note === 'G' ? 'bg-emerald-600 text-white' : 'text-neutral-400'}`}>{note}{withOctave ? '4' : ''}</span>
            ))}
            {blackKeys.map(({ after, sharp, flat }) => (
                <span aria-hidden="true" key={sharp} style={{ left: `${((after + 1) * 100) / 7 - (100 / 7) * 0.31}%`, width: `${(100 / 7) * 0.62}%` }} className={`absolute top-0 flex h-[62%] flex-col items-center justify-end rounded-b-md pb-2 text-[8px] font-semibold leading-tight ${!withOctave && sharp === 'F♯' ? 'bg-emerald-600 text-white' : 'bg-neutral-900 text-neutral-300'}`}>
                    <span>{sharp}</span><span>{flat}</span>
                </span>
            ))}
        </div>
    );
}

export default function PitchQuizGuide({ quizTitle, difficulty, ...props }) {
    const t = useTrans();
    const withOctave = quizTitle === 'Find the Pitch';
    const steps = withOctave ? [
        { title: tk('Listen to the pitch'), description: tk('Play the question audio. Listen for both the note name and how high or low it sounds.') },
        { title: tk('Find its note and octave'), description: tk('Move between octaves on the keyboard, then tap the key that matches. On a phone, swipe to browse octaves.') },
        { title: tk('Check your choice and submit'), description: tk('The number is part of your answer: G3 and G4 are different pitches. Replay the audio if you need to, then submit.') },
    ] : [
        { title: tk('Listen to the note'), description: tk('Press Play to hear the note. You can listen again whenever you need to.') },
        { title: tk('Find the matching sound'), description: tk('Tap the keys on the keyboard to hear their sounds. Choose the key that sounds like the note you heard.') },
        { title: tk('Submit your answer'), description: tk('When you have chosen a key, press Submit Answer. You can change your choice before you submit.') },
    ];
    return (
        <QuizQuickGuide
            {...props}
            id={withOctave ? 'find-the-pitch-guide-heading' : 'find-the-note-guide-heading'}
            badge={difficulty}
            heading={withOctave ? tk('Hear the pitch. Find its place.') : tk('Listen to a note. Find the matching key.')}
            intro={withOctave ? tk('Match the sound to its exact note and octave on the keyboard.') : tk('Listen to the note, then use the keyboard to find a matching sound.')}
            steps={steps}
            about={withOctave ? tk('Each answer includes a note name and an octave number, such as G4. The same note name in a different octave is a different answer. The keyboard shows the pitches available for your chosen instrument.') : tk('Practise recognising notes by their sound. There are twelve keys to choose from. The black keys show two names, such as F♯ and G♭. Both names belong to the same key, so you only need to select it once.')}
            example={(
                <>
                    <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-400">{t('See how an answer is chosen')}</p>
                    <div className="mt-4 rounded-xl border border-black/5 bg-white px-4 py-5 text-center shadow-sm">
                        <p className="text-[10px] font-medium uppercase tracking-widest text-neutral-400">{t('Example answer')}</p>
                        <p className="mt-2 text-2xl font-bold tracking-tight text-neutral-900">{withOctave ? <>G<span className="text-brand">4</span></> : <>F♯ <span className="font-normal text-neutral-300">/</span> <span className="text-brand">G♭</span></>}</p>
                    </div>
                    <ExampleKeyboard withOctave={withOctave} />
                    <div className="mt-3">
                        <GuideExampleChoice label={t('Note name')} value={withOctave ? 'G' : 'F♯ / G♭'} />
                        {withOctave && <GuideExampleChoice label={t('Octave')} value="4" />}
                    </div>
                    <p className="mt-4 text-xs leading-relaxed text-neutral-500">{withOctave ? t('If you hear G4, select G in octave 4. Choosing G in a different octave will not match.') : t('The green key shows F♯ / G♭ selected. This is just an example. In your quiz, choose the key that matches the note you hear.')}</p>
                </>
            )}
        />
    );
}
