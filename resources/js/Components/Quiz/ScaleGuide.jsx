import { useTrans } from '@/lib/i18n';
import QuizQuickGuide from './QuizQuickGuide';

export default function ScaleGuide({ guide, difficulty, ...props }) {
    const t = useTrans();
    return <QuizQuickGuide {...props} id="scale-guide-heading" badge={difficulty} heading={guide.heading} intro={guide.intro} steps={guide.steps} about={guide.about} example={(
        <>
            <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-500">{t('See how an answer is chosen')}</p>
            <div className="mt-4 rounded-xl border border-neutral-200 bg-white p-4">
                <p className="text-sm font-bold text-neutral-900">{t(':scale starting on :note', { scale: t(guide.example), note: guide.home })}</p>
                <p className="mt-2 text-xs text-neutral-500">{t('First, the notes go up')}</p>
                <ol aria-label={t('Notes played one after another')} className="mt-3 grid grid-cols-4 gap-2">
                    {guide.exampleNotes.map((note, index) => <li key={index} className="rounded-lg bg-neutral-100 p-2 text-center"><span className="block text-[10px] text-neutral-500">{index + 1}</span><span className="mt-1 block text-base font-bold text-neutral-900">{note}</span></li>)}
                </ol>
                <p className="mt-3 text-xs leading-relaxed text-neutral-600">{t('Then the same notes come back down to :note.', { note: guide.home })}</p>
            </div>
            {guide.comparison && <p className="mt-4 text-xs leading-relaxed text-neutral-600">{t(guide.comparison)}</p>}
            <p className="mt-4 text-xs font-bold text-neutral-800">{t('Choose the scale name')}</p>
            <div role="img" aria-label={t('Example answer: :answer', { answer: t(guide.example) })} className="mt-2 grid gap-2">
                {guide.choices.map((choice, index) => <span key={choice} className={`flex items-center gap-2 rounded-xl border px-3 py-3 text-xs font-semibold ${choice === guide.example ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-neutral-200 bg-white text-neutral-600'}`}><span aria-hidden="true" className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] ${choice === guide.example ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-500'}`}>{String.fromCharCode(65 + index)}</span><span>{t(choice)}</span><span className="sr-only">{choice === guide.example ? t(', selected') : ''}</span></span>)}
            </div>
            <p className="mt-4 text-xs leading-relaxed text-neutral-600">{t(guide.exampleExplanation)}</p>
        </>
    )} />;
}
