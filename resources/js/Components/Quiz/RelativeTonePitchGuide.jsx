import { SOLFEGE_NOTE_CHOICES } from '@/lib/quizAnswerPresets';
import QuizQuickGuide, { GuideExampleChoice } from './QuizQuickGuide';

export default function RelativeTonePitchGuide({ quizTitle, difficulty, ...props }) {
    const widerRange = quizTitle === 'Relative Tone Pitch #2';
    return (
        <QuizQuickGuide
            {...props}
            id={widerRange ? 'relative-tone-pitch-two-guide-heading' : 'relative-tone-pitch-one-guide-heading'}
            badge={difficulty}
            heading={widerRange ? 'Name the note, high or low.' : 'Listen to a note. Choose its name.'}
            intro={widerRange ? 'Listen to a note, then choose its name. The notes can sound higher or lower than in quiz #1.' : 'Listen to a note, then choose Do, Re, Mi, Fa, So, La or Ti.'}
            steps={[
                { title: 'Listen to the note', description: 'Select Play home note (Do) if you need a reference. Then press Play to hear the question note. You can listen again whenever you need to.' },
                { title: 'Choose its name', description: widerRange ? 'A note can sound higher or lower and still have the same name. For example, a high Mi and a low Mi are both Mi. Choose the name that matches what you hear.' : 'Select Do, Re, Mi, Fa, So, La or Ti to match the note you heard. You can change your choice before you submit.' },
                { title: 'Submit your answer', description: 'When you have chosen a name, press Submit Answer. Then move on to the next question.' },
            ]}
            about={widerRange ? 'This quiz uses the same seven note names as quiz #1, with a wider range of high and low sounds. A high Do and a low Do are both called Do. Listen for which name fits the note, even when it sounds higher or lower than before.' : 'Do, Re, Mi, Fa, So, La and Ti are the names used on the answer buttons. The notes in this quiz stay in one small range. In Listen Mode, hear each note with its name. Select Next to hear another example and get familiar with the sounds before you start.'}
            example={(
                <>
                    <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-400">See how an answer is chosen</p>
                    <div className="mt-4 rounded-xl border border-black/5 bg-white px-4 py-5 text-center shadow-sm">
                        <p className="text-[10px] font-medium uppercase tracking-widest text-neutral-400">Example answer</p>
                        <p className="mt-2 text-2xl font-bold text-neutral-900">Mi</p>
                    </div>
                    <div role="img" aria-label="Example answer buttons with Mi selected" className="mt-4 grid grid-cols-2 gap-2">
                        {SOLFEGE_NOTE_CHOICES.map((name, index) => (
                            <span aria-hidden="true" key={name} className={`flex items-center gap-2.5 rounded-xl border px-3 py-3 text-sm font-semibold ${name === 'Mi' ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-neutral-200 bg-white text-neutral-600'}`}>
                                <span className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-[10px] font-bold ${name === 'Mi' ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-500'}`}>{String.fromCharCode(65 + index)}</span>
                                {name}
                                {name === 'Mi' && <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="2" className="ml-auto h-4 w-4"><path d="m4 10 4 4 8-8" /></svg>}
                            </span>
                        ))}
                    </div>
                    {widerRange && <div className="mt-3"><GuideExampleChoice label="A lower Mi" value="Mi" /><GuideExampleChoice label="A higher Mi" value="Mi" /></div>}
                    <p className="mt-4 text-xs leading-relaxed text-neutral-500">This example shows Mi selected. In your quiz, choose the name that matches the note you hear.</p>
                </>
            )}
        />
    );
}
