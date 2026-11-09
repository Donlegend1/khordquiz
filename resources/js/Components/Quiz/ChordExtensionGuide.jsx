import { useTrans } from '@/lib/i18n';
import QuizQuickGuide from './QuizQuickGuide';

export default function ChordExtensionGuide({ guide, difficulty, ...props }) {
    const t = useTrans();
    return <QuizQuickGuide {...props} id="chord-extension-guide-heading" badge={difficulty} heading={guide.heading} intro={guide.intro} steps={guide.steps} about={guide.about} aboutContent={(
        <div className="mt-4"><p className="text-xs font-bold text-neutral-900">{t('Chords in this lesson')}</p><ul className="mt-2 list-disc space-y-1 pl-4 text-sm text-neutral-600">{guide.availableChords.map((chord) => <li key={chord}>{t(chord)}</li>)}</ul></div>
    )} example={(
        <>
            <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-500">{t('See how to select an answer')}</p>
            <div className="mt-4 rounded-xl border border-neutral-200 bg-white p-4">
                <p className="text-sm font-bold text-neutral-900">{t(guide.exampleChord)}</p>
                <p className="mt-1 text-xs text-neutral-500">{t('Notes played together')}</p>
                <div className="mt-3 flex flex-wrap gap-2">{guide.exampleNotes.map((note, index) => <span key={index} className="flex min-h-11 min-w-10 flex-1 items-center justify-center rounded-lg bg-neutral-100 p-2 text-base font-bold text-neutral-900">{note}</span>)}</div>
            </div>
            <p className="mt-4 text-xs font-bold text-neutral-800">{t('Select all that apply')}</p>
            <div role="img" aria-label={t('Selected: :choices', { choices: guide.selected.join(', ') })} className="mt-2 grid grid-cols-3 gap-2">
                {guide.choices.map((choice) => {
                    const selected = guide.selected.includes(choice);
                    return <span key={choice} className={`flex items-center gap-2 rounded-xl border px-2 py-3 text-xs font-bold ${selected ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-neutral-200 bg-white text-neutral-600'}`}><span aria-hidden="true" className={`flex h-4 w-4 shrink-0 items-center justify-center rounded border ${selected ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-neutral-300'}`}>{selected ? '✓' : ''}</span>{choice}</span>;
                })}
            </div>
            <p className="mt-4 text-xs leading-relaxed text-neutral-600">{t(guide.exampleExplanation)} {t('This is a visual example.')}</p>
        </>
    )} />;
}
