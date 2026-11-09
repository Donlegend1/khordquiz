import guides from '@/data/basicTriadGuides.json';
import { useTrans } from '@/lib/i18n';
import QuizQuickGuide from './QuizQuickGuide';

function ExampleChoices({ label, choices, selected }) {
    const t = useTrans();
    return (
        <div className="mt-4">
            <p className="text-xs font-bold text-neutral-800">{label}</p>
            <div className="mt-2 flex flex-wrap gap-2">
                {choices.map((choice) => <span key={choice} className={`rounded-xl border px-3 py-2.5 text-xs font-semibold ${choice === selected ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-neutral-200 bg-white text-neutral-600'}`}>{choice === selected && <span aria-hidden="true">✓ </span>}{t(choice)}<span className="sr-only">{choice === selected ? t(', selected') : ''}</span></span>)}
            </div>
        </div>
    );
}

export default function BasicTriadGuide({ quizTitle, difficulty, guide: providedGuide, ...props }) {
    const t = useTrans();
    const guide = providedGuide ?? guides[quizTitle];
    if (!guide) return null;
    return (
        <QuizQuickGuide {...props} id="chord-quality-guide-heading" badge={difficulty} heading={guide.heading} intro={guide.intro} steps={guide.steps} about={guide.about} example={(
            <>
                <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-500">{t('See how an answer is chosen')}</p>
                <div className="mt-4 rounded-xl border border-neutral-200 bg-white p-4">
                    <p className="text-center text-xs font-semibold text-neutral-500">{t('Chord notes played together')}</p>
                    <div className="mt-3 flex flex-wrap items-center justify-center gap-2">
                        {guide.exampleNotes.map((note, index) => <span key={index} className="flex min-h-12 min-w-10 flex-1 basis-11 items-center justify-center rounded-lg bg-neutral-100 p-2 text-base font-bold text-neutral-900">{note}</span>)}
                    </div>
                    <p className="mt-3 text-xs leading-relaxed text-neutral-600">{t(guide.layoutCaption)}</p>
                </div>
                <ExampleChoices label={t('Chord quality')} choices={guide.qualities} selected={guide.exampleQuality} />
                {guide.inversions.length > 0 && <ExampleChoices label={t('Inversion')} choices={guide.inversions} selected={guide.exampleInversion} />}
                <p className="mt-4 text-xs leading-relaxed text-neutral-600">{t(guide.exampleExplanation)}</p>
            </>
        )} />
    );
}
