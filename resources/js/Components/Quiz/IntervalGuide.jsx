import guides from '@/data/intervalGuides.json';
import QuizQuickGuide from './QuizQuickGuide';

export default function IntervalGuide({ quizTitle, difficulty, ...props }) {
    const guide = guides[quizTitle];
    if (!guide) return null;
    const { low, high, together } = guide.pair;
    return (
        <QuizQuickGuide
            {...props}
            id="interval-guide-heading"
            badge={difficulty}
            heading={guide.heading}
            intro={guide.intro}
            steps={guide.steps}
            about={guide.about}
            example={(
                <>
                    <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-500">How the notes are played</p>
                    <div role="img" aria-label={together ? `${low} and ${high} sound together` : `${low} plays first, then ${high}`} className="mt-4 rounded-xl border border-neutral-200 bg-white p-4">
                        <p className="mb-3 text-center text-xs font-semibold text-neutral-500">{together ? 'At the same time' : 'One after the other'}</p>
                        <div aria-hidden="true" className="flex items-center gap-2">
                            <div className="min-w-0 flex-1 rounded-lg bg-neutral-100 px-2 py-3 text-center"><p className="text-[10px] font-medium text-neutral-500">{together ? 'Lower note' : 'First note'}</p><p className="mt-1 text-xl font-bold text-neutral-900">{low}</p></div>
                            <span className="text-xl text-neutral-400">{together ? '+' : '→'}</span>
                            <div className="min-w-0 flex-1 rounded-lg bg-neutral-100 px-2 py-3 text-center"><p className="text-[10px] font-medium text-neutral-500">{together ? 'Higher note' : 'Next note'}</p><p className="mt-1 text-xl font-bold text-neutral-900">{high}</p></div>
                        </div>
                    </div>
                    <p className="mt-4 text-center text-[10px] font-medium uppercase tracking-widest text-neutral-500">Example answer</p>
                    <p className="mt-1 text-center text-xl font-bold text-neutral-900">{guide.example}</p>
                    <div role="img" aria-label={`Example answer buttons with ${guide.example} selected`} className="mt-4 grid grid-cols-2 gap-2">
                        {guide.choices.map((choice, index) => (
                            <span aria-hidden="true" key={choice} className={`flex min-w-0 items-center gap-2 rounded-xl border px-2.5 py-3 text-xs font-semibold ${choice === guide.example ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-neutral-200 bg-white text-neutral-600'}`}>
                                <span className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-[10px] font-bold ${choice === guide.example ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-500'}`}>{String.fromCharCode(65 + index)}</span>
                                <span>{choice}</span>
                            </span>
                        ))}
                    </div>
                    <p className="mt-4 text-xs leading-relaxed text-neutral-600">{guide.exampleExplanation}</p>
                </>
            )}
        />
    );
}
