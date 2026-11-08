import guides from '@/data/keyFindingGuides.json';
import QuizQuickGuide, { GuideExampleChoice } from './QuizQuickGuide';

function ExampleKeyKeyboard({ answer, helper, helperLabel }) {
    const whites = ['C', 'D', 'E', 'F', 'G', 'A', 'B'];
    const blacks = [{ after: 0, value: 'C#/Db', names: ['C♯', 'D♭'] }, { after: 1, value: 'D#/Eb', names: ['D♯', 'E♭'] }, { after: 3, value: 'F#/Gb', names: ['F♯', 'G♭'] }, { after: 4, value: 'G#/Ab', names: ['G♯', 'A♭'] }, { after: 5, value: 'A#/Bb', names: ['A♯', 'B♭'] }];
    return (
        <div role="img" aria-label={`Example keyboard with ${answer} selected as the song's key${helper ? ` and ${helperLabel ?? helper} marked as the reference note` : ''}`} className="relative flex h-32 overflow-hidden rounded-xl border border-black/10 bg-white">
            {whites.map((note, index) => <span aria-hidden="true" key={note} className={`flex min-w-0 flex-1 items-end justify-center pb-3 text-xs font-bold ${index < 6 ? 'border-r border-neutral-200' : ''} ${note === answer ? 'bg-emerald-600 text-white' : note === helper ? 'bg-amber-400 text-neutral-900' : 'text-neutral-400'}`}>{note}</span>)}
            {blacks.map(({ after, names, value }) => <span aria-hidden="true" key={after} style={{ left: `${((after + 1) * 100) / 7 - (100 / 7) * 0.31}%`, width: `${(100 / 7) * 0.62}%` }} className={`absolute top-0 flex h-[62%] flex-col items-center justify-end rounded-b-md pb-2 text-[8px] font-semibold leading-tight ${value === answer ? 'bg-emerald-600 text-white' : value === helper ? 'bg-amber-400 text-neutral-900' : 'bg-neutral-900 text-neutral-300'}`}>{names.map((name) => <span key={name}>{name}</span>)}</span>)}
        </div>
    );
}

export default function KeyFindingGuide({ quizTitle, difficulty, ...props }) {
    const guide = guides[quizTitle];
    const helper = guide.keyboardExample.helper;
    const helperLabel = guide.keyboardExample.helperLabel ?? helper;
    return (
        <QuizQuickGuide
            {...props}
            id={quizTitle.endsWith('#2') ? 'find-the-key-two-guide-heading' : 'find-the-key-one-guide-heading'}
            badge={difficulty}
            heading={guide.heading}
            intro={guide.intro}
            steps={guide.steps}
            about={guide.about}
            example={(
                <>
                    <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-400">See how an answer is chosen</p>
                    <div className="my-4 rounded-xl border border-black/5 bg-white px-4 py-5 text-center shadow-sm"><p className="text-[10px] font-medium uppercase tracking-widest text-neutral-400">Example answer</p><p className="mt-2 text-2xl font-bold text-neutral-900">{guide.example}</p></div>
                    {helper && <p className="mb-3 text-xs font-semibold text-amber-700">Reference note: {helperLabel}</p>}
                    <ExampleKeyKeyboard answer={guide.keyboardExample.answer} helper={helper} helperLabel={helperLabel} />
                    <div className="mt-3">
                        {guide.keyboardExample.relationship && <GuideExampleChoice label="Reference note heard as" value={`${guide.keyboardExample.relationship} (flat sixth)`} />}
                        <GuideExampleChoice label={helper ? 'Tonal centre (answer)' : "Song's key"} value={guide.example} />
                    </div>
                    <p className="mt-4 text-xs leading-relaxed text-neutral-500">{guide.exampleExplanation}</p>
                </>
            )}
        />
    );
}
