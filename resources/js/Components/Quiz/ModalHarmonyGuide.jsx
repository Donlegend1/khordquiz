import QuizQuickGuide from './QuizQuickGuide';

export default function ModalHarmonyGuide({ guide, difficulty, ...props }) {
    return <QuizQuickGuide {...props} id={guide.id ?? 'modal-harmony-guide-heading'} badge={difficulty} heading={guide.heading} intro={guide.intro} steps={guide.steps} about={guide.about} example={(
        <>
            <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-500">See how an answer is chosen</p>
            <div role="img" aria-label={guide.diagramLabel ?? `${guide.upperTriad} above ${guide.bass} bass, all notes sounding together`} className="mt-4 rounded-xl border border-neutral-200 bg-white p-4">
                <p className="text-xs font-semibold text-neutral-500">{guide.upperLabel ?? `Upper triad: ${guide.upperTriad}`} </p>
                <div aria-hidden="true" className="mt-3 grid grid-cols-3 gap-2">{guide.upperNotes.map((note) => <span key={note} className="rounded-lg bg-neutral-100 p-3 text-center text-lg font-bold text-neutral-900">{note}</span>)}</div>
                <div className="mt-4 flex items-center justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2.5"><span className="text-xs font-semibold text-amber-800">Bass note</span><span className="text-lg font-bold text-amber-800">{guide.bass}</span></div>
                <p className="mt-3 text-center text-xs text-neutral-500">{guide.playedTogetherLabel ?? 'All four notes played together'}</p>
            </div>
            <p className="mt-4 text-xs font-bold text-neutral-800">Choose the full label</p>
            <div role="img" aria-label={`Example answer: ${guide.example}`} className="mt-2 grid gap-2">
                {guide.choices.map((choice, index) => <span key={choice} className={`flex items-center gap-2 rounded-xl border px-3 py-3 text-xs font-semibold ${choice === guide.example ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-neutral-200 bg-white text-neutral-600'}`}><span aria-hidden="true" className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] ${choice === guide.example ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-500'}`}>{String.fromCharCode(65 + index)}</span><span>{choice}</span><span className="sr-only">{choice === guide.example ? ', selected' : ''}</span></span>)}
            </div>
            <p className="mt-4 text-xs leading-relaxed text-neutral-600">{guide.exampleExplanation}</p>
        </>
    )} />;
}
