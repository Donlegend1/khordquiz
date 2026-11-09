import { tk, useTrans } from '@/lib/i18n';
import QuizQuickGuide from './QuizQuickGuide';

export default function ModalHarmonyGuide({ guide, difficulty, ...props }) {
    const t = useTrans();
    return <QuizQuickGuide {...props} id={guide.id ?? 'modal-harmony-guide-heading'} badge={difficulty} heading={guide.heading} intro={guide.intro} steps={guide.steps} about={guide.about} example={(
        <>
            <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-500">{t('See how an answer is chosen')}</p>
            <div role="img" aria-label={guide.diagramLabel ? t(guide.diagramLabel) : t(':triad above :bass bass, all notes sounding together', { triad: t(guide.upperTriad), bass: guide.bass })} className="mt-4 rounded-xl border border-neutral-200 bg-white p-4">
                <p className="text-xs font-semibold text-neutral-500">{guide.upperLabel ? t(guide.upperLabel) : t('Upper triad: :triad', { triad: t(guide.upperTriad) })} </p>
                <div aria-hidden="true" className="mt-3 grid grid-cols-3 gap-2">{guide.upperNotes.map((note) => <span key={note} className="rounded-lg bg-neutral-100 p-3 text-center text-lg font-bold text-neutral-900">{note}</span>)}</div>
                <div className="mt-4 flex items-center justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2.5"><span className="text-xs font-semibold text-amber-800">{t('Bass note')}</span><span className="text-lg font-bold text-amber-800">{guide.bass}</span></div>
                <p className="mt-3 text-center text-xs text-neutral-500">{t(guide.playedTogetherLabel ?? tk('All four notes played together'))}</p>
            </div>
            <p className="mt-4 text-xs font-bold text-neutral-800">{t('Choose the full label')}</p>
            <div role="img" aria-label={t('Example answer: :answer', { answer: t(guide.example) })} className="mt-2 grid gap-2">
                {guide.choices.map((choice, index) => <span key={choice} className={`flex items-center gap-2 rounded-xl border px-3 py-3 text-xs font-semibold ${choice === guide.example ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-neutral-200 bg-white text-neutral-600'}`}><span aria-hidden="true" className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] ${choice === guide.example ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-500'}`}>{String.fromCharCode(65 + index)}</span><span>{t(choice)}</span><span className="sr-only">{choice === guide.example ? t(', selected') : ''}</span></span>)}
            </div>
            <p className="mt-4 text-xs leading-relaxed text-neutral-600">{t(guide.exampleExplanation)}</p>
        </>
    )} />;
}
