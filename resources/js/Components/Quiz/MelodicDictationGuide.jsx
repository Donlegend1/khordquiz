import guides from '@/data/melodicDictationGuides.json';
import { useTrans } from '@/lib/i18n';
import QuizQuickGuide from './QuizQuickGuide';

export default function MelodicDictationGuide({ quizTitle, difficulty, ...props }) {
    const t = useTrans();
    const guide = guides[quizTitle];
    if (!guide) return null;
    const bass = guide.focus === 'bass';
    const layered = guide.focus !== 'melody';
    const legend = guide.focus === 'upper-pair' ? t('Green: notes to enter. Grey: lower note.') : t('Green: notes to enter. Grey: other chord notes.');
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
                    <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-500">{t('See how to build an answer')}</p>
                    <p className="mt-3 text-sm font-semibold text-neutral-800">{bass ? t('Follow the lowest notes') : layered ? t('Follow the highest notes') : t('Read from left to right')}</p>
                    <div role="img" aria-label={layered ? (bass ? t('Example answer in order: :notes. Follow the lowest notes.', { notes: guide.exampleNotes.join(', ') }) : t('Example answer in order: :notes. Follow the highest notes.', { notes: guide.exampleNotes.join(', ') })) : t('Example answer in order: :notes', { notes: guide.exampleNotes.join(', ') })} className="mt-4 grid gap-2" style={{ gridTemplateColumns: `repeat(${guide.exampleNotes.length}, minmax(0, 1fr))` }}>
                        {guide.exampleNotes.map((note, index) => (
                            <div key={index} aria-hidden="true" className="flex min-w-0 flex-col gap-2 text-center">
                                <span className="text-[10px] font-semibold text-neutral-500">{index + 1}</span>
                                {bass && <div className="h-14 rounded-lg border border-neutral-200 bg-neutral-100 p-2 text-neutral-300"><span className="block border-t-4 border-current" /><span className="mt-2 block border-t-4 border-current" /></div>}
                                <span className="flex h-12 items-center justify-center rounded-lg border border-emerald-600 bg-emerald-50 text-base font-bold text-emerald-700">{note}</span>
                                {layered && !bass && <div className="h-14 rounded-lg border border-neutral-200 bg-neutral-100 p-2 text-neutral-300"><span className="block border-t-4 border-current" />{guide.focus === 'upper-chord' && <span className="mt-2 block border-t-4 border-current" />}</div>}
                            </div>
                        ))}
                    </div>
                    {layered && <p className="mt-3 text-xs leading-relaxed text-neutral-600">{legend}</p>}
                    <div className="mt-5 rounded-xl border border-neutral-200 bg-white p-4 text-center">
                        <p className="text-[10px] font-semibold uppercase tracking-widest text-neutral-500">{t('Example answer')}</p>
                        <p className="mt-2 text-lg font-bold text-neutral-900">{guide.exampleNotes.join(' → ')}</p>
                    </div>
                    <p className="mt-4 text-xs leading-relaxed text-neutral-600">{t(guide.exampleExplanation)}</p>
                </>
            )}
        />
    );
}
