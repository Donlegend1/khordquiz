import guides from '@/data/chordNamingGuides.json';
import { buildNamedChord } from '@/lib/chordNaming';
import { tk, useTrans } from '@/lib/i18n';
import QuizQuickGuide, { GuideExampleChoice } from './QuizQuickGuide';

export default function ChordNamingGuide({ quizTitle, difficulty, ...props }) {
    const t = useTrans();
    const guide = guides[quizTitle];
    if (!guide) return null;
    const { quality, degrees, exclude, notes } = guide.example;
    return (
        <QuizQuickGuide
            {...props}
            id="chord-naming-guide-heading"
            badge={difficulty}
            heading={guide.heading}
            intro={guide.intro}
            steps={guide.steps}
            aboutLabel={tk('About this level')}
            about={guide.about}
            example={(
                <>
                    <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-500">{t('See how an answer is built')}</p>
                    <div className="mt-4 rounded-xl border border-neutral-200 bg-white p-4">
                        <p className="text-center text-xs font-semibold text-neutral-500">{t('Notes played together')}</p>
                        <div className="mt-3 flex flex-wrap gap-2">{notes.map((note, index) => <span key={index} className="flex min-h-11 min-w-10 flex-1 items-center justify-center rounded-lg bg-neutral-100 p-2 text-base font-bold text-neutral-900">{note}</span>)}</div>
                    </div>
                    <div className="mt-4 rounded-xl border border-neutral-200 bg-white px-4 py-5 text-center">
                        <p className="text-[10px] font-semibold uppercase tracking-widest text-neutral-500">{t('Example answer')}</p>
                        <p className="mt-2 text-xl font-bold text-neutral-900">{buildNamedChord(quality, degrees, exclude)}</p>
                    </div>
                    <div className="mt-3">
                        <GuideExampleChoice label={t('Chord Quality')} value={t(quality)} />
                        <GuideExampleChoice label={t('Chord Degree')} value={degrees.join(', ')} />
                        <GuideExampleChoice label={t('Exclude (if any)')} value={t(exclude)} />
                    </div>
                    <p className="mt-4 text-xs leading-relaxed text-neutral-600">{t(guide.exampleExplanation)} {t('This is a visual example.')}</p>
                </>
            )}
        />
    );
}
