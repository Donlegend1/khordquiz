import guides from '@/data/melodicDictationGuides.json';
import QuizQuickGuide from './QuizQuickGuide';

export default function MelodicDictationGuide({ quizTitle, difficulty, ...props }) {
    const guide = guides[quizTitle];
    if (!guide) return null;
    const bass = guide.focus === 'bass';
    const layered = guide.focus !== 'melody';
    const otherLabel = guide.focus === 'upper-pair' ? 'Lower note' : 'Other chord notes';
    return (
        <QuizQuickGuide
            {...props}
            id="melodic-dictation-guide-heading"
            badge={difficulty}
            heading={guide.heading}
            intro={guide.intro}
            steps={guide.steps}
            about={guide.about}
            example={(
                <>
                    <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-500">See how to build an answer</p>
                    <p className="mt-3 text-sm font-semibold text-neutral-800">{bass ? 'Follow the lowest notes' : layered ? 'Follow the highest notes' : 'Read from left to right'}</p>
                    <div role="img" aria-label={`Example answer in order: ${guide.exampleNotes.join(', ')}${layered ? `. Follow the ${bass ? 'lowest' : 'highest'} notes.` : ''}`} className="mt-4 grid gap-2" style={{ gridTemplateColumns: `repeat(${guide.exampleNotes.length}, minmax(0, 1fr))` }}>
                        {guide.exampleNotes.map((note, index) => (
                            <div key={index} aria-hidden="true" className="flex min-w-0 flex-col gap-2 text-center">
                                <span className="text-[10px] font-semibold text-neutral-500">{index + 1}</span>
                                {bass && <div className="h-14 rounded-lg border border-neutral-200 bg-neutral-100 p-2 text-neutral-300"><span className="block border-t-4 border-current" /><span className="mt-2 block border-t-4 border-current" /></div>}
                                <span className="flex h-12 items-center justify-center rounded-lg border border-emerald-600 bg-emerald-50 text-base font-bold text-emerald-700">{note}</span>
                                {layered && !bass && <div className="h-14 rounded-lg border border-neutral-200 bg-neutral-100 p-2 text-neutral-300"><span className="block border-t-4 border-current" />{guide.focus === 'upper-chord' && <span className="mt-2 block border-t-4 border-current" />}</div>}
                            </div>
                        ))}
                    </div>
                    {layered && <p className="mt-3 text-xs leading-relaxed text-neutral-600">Green: notes to enter. Grey: {otherLabel.toLowerCase()}.</p>}
                    <div className="mt-5 rounded-xl border border-neutral-200 bg-white p-4 text-center">
                        <p className="text-[10px] font-semibold uppercase tracking-widest text-neutral-500">Example answer</p>
                        <p className="mt-2 text-lg font-bold text-neutral-900">{guide.exampleNotes.join(' → ')}</p>
                    </div>
                    <p className="mt-4 text-xs leading-relaxed text-neutral-600">{guide.exampleExplanation}</p>
                </>
            )}
        />
    );
}
