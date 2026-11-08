import QuizQuickGuide from './QuizQuickGuide';

export default function ProgressionGuide({ guide, difficulty, ...props }) {
    const first = guide.exampleSlots[0];
    const fields = guide.kind === 'cadence' ? [['Cadence', guide.exampleAnswer]] : [
        ...(guide.kind !== 'qualities' ? [['Chord number', first.degree]] : []),
        ['Chord type', first.quality],
        ...(guide.kind === 'extensions' ? [['Added extensions', first.extensions.join(', ')]] : []),
    ];
    return <QuizQuickGuide {...props} id="progression-guide-heading" badge={difficulty} heading={guide.heading} intro={guide.intro} steps={guide.steps} about={guide.about} example={(
        <>
            <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-500">See how an answer is built</p>
            {guide.anchor && <p className="mt-3 rounded-lg border border-neutral-200 bg-white p-3 text-xs leading-relaxed text-neutral-600">First: home major chord → home note. Then: the four chords to answer.</p>}
            <ol className="mt-4 flex flex-wrap gap-2">
                {guide.exampleSlots.map((slot, index) => <li key={index} className="min-w-24 flex-1 basis-24 rounded-xl border border-emerald-600 bg-emerald-50 px-2 py-3 text-center"><p className="text-[10px] font-semibold text-emerald-700">Chord {index + 1}</p>{slot.degree && <p className="mt-1 text-lg font-bold text-emerald-800">{slot.degree}</p>}<p className="mt-1 text-xs font-bold text-emerald-800">{slot.quality}</p>{slot.extensions.length > 0 && <p className="mt-1 text-xs font-semibold text-emerald-800">{slot.extensions.join(', ')}</p>}</li>)}
            </ol>
            <div className="mt-4 rounded-xl border border-neutral-200 bg-white p-4">
                <p className="text-xs font-bold text-neutral-900">{guide.kind === 'cadence' ? 'Example answer' : 'Example: Chord 1'}</p>
                {fields.map(([label, value]) => <div key={label} className="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs"><span className="text-neutral-500">{label}</span><span className="rounded-full bg-neutral-900 px-3 py-1.5 font-semibold text-white">{value}</span></div>)}
            </div>
            <p className="mt-4 text-xs leading-relaxed text-neutral-600">{guide.exampleExplanation} This is a visual example.</p>
        </>
    )} />;
}
