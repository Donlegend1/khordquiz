import guides from '@/data/chordToneGuides.json';
import QuizQuickGuide from './QuizQuickGuide';

export default function ChordToneGuide({ quizTitle, difficulty, ...props }) {
    const guide = guides[quizTitle];
    if (!guide) return null;
    return <QuizQuickGuide {...props} id="chord-tone-guide-heading" badge={difficulty} heading={guide.heading} intro={guide.intro} steps={guide.steps} about={guide.about} example={(
        <>
            <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-500">See how an answer is chosen</p>
            <div className="mt-4 rounded-xl border border-neutral-200 bg-white p-4">
                <p className="text-xs font-semibold text-neutral-500">First: the whole chord</p>
                <p className="mt-1 text-sm font-bold text-neutral-900">{guide.chordName}</p>
                <div className="mt-3 grid grid-cols-4 gap-2">
                    {guide.chordNotes.map(({ note, degree }) => <div key={degree} className="rounded-lg bg-neutral-100 px-1 py-3 text-center"><p className="text-base font-bold text-neutral-900">{note}</p><p className="mt-1 text-xs text-neutral-500">{degree}</p></div>)}
                </div>
                <p className="mt-4 text-xs font-semibold text-neutral-500">Then: one note on its own</p>
                <p className="mt-2 rounded-lg border border-emerald-600 bg-emerald-50 px-3 py-3 text-center text-xl font-bold text-emerald-700">{guide.singleNote}</p>
            </div>
            <p className="mt-4 text-xs font-bold text-neutral-800">Choose the note’s role</p>
            <div role="img" aria-label={`Example answer: ${guide.example}`} className="mt-2 grid grid-cols-4 gap-2">
                {guide.choices.map((choice) => <span key={choice} className={`rounded-lg border p-3 text-center text-sm font-bold ${choice === guide.example ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-neutral-200 bg-white text-neutral-600'}`}>{choice}<span className="sr-only">{choice === guide.example ? ', selected' : ''}</span></span>)}
            </div>
            <p className="mt-4 text-xs leading-relaxed text-neutral-600">{guide.exampleExplanation} This is a visual example, rather than a reference recording.</p>
        </>
    )} />;
}
