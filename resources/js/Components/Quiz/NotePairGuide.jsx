import { SOLFEGE_NOTE_CHOICES, SOLFEGE_PAIR_CHOICES } from '@/lib/quizAnswerPresets';
import QuizQuickGuide from './QuizQuickGuide';

const COUNTS = { Second: 2, Third: 3, Fourth: 4, Fifth: 5, Sixth: 6, Seventh: 7 };
const COUNT_WORDS = { Second: 'two', Third: 'three', Fourth: 'four', Fifth: 'five', Sixth: 'six', Seventh: 'seven' };

export default function NotePairGuide({ quizTitle, difficulty, ...props }) {
    const [timing, interval] = quizTitle.split(' ');
    const together = timing === 'Harmonic';
    const choices = SOLFEGE_PAIR_CHOICES[interval];
    const example = choices[0];
    const [low, high] = example.split(' - ');
    const countedNames = SOLFEGE_NOTE_CHOICES.slice(0, COUNTS[interval]).join(', ');
    const hasHigherMarks = choices.some((choice) => choice.includes("'"));

    return (
        <QuizQuickGuide
            {...props}
            id={`${timing.toLowerCase()}-${interval.toLowerCase()}-guide-heading`}
            badge={difficulty}
            heading={together ? 'Two notes together. Name them both.' : 'Hear two notes. Name them in order.'}
            intro={together ? 'You will hear two notes at the same time. Choose the pair of names that matches.' : 'You will hear two notes, one after the other. Choose the pair of names that matches.'}
            steps={[
                { title: 'Listen to both notes', description: together ? 'Press Play to hear the two notes together. Listen for a lower sound and a higher sound. You can listen again whenever you need to.' : 'Press Play to hear the first note, then the next note. You can listen again whenever you need to.' },
                { title: 'Choose the matching pair', description: together ? `Choose the names of both notes. The answers put the lower note first and the higher note second. For example, ${example} names the two notes together.` : `Choose both names in the order you heard them. For example, ${example} means ${low} plays first, then ${high}.` },
                { title: 'Submit your answer', description: 'When you have chosen a pair, press Submit Answer. You can change your choice before you submit.' },
            ]}
            about={`A ${interval.toLowerCase()} counts ${COUNT_WORDS[interval]} note names, including the first and last: ${countedNames}. That makes ${example} one example. Other questions start on a different note. ${together ? 'The two notes always play together in this quiz.' : 'The two notes always play one after the other in this quiz.'}${hasHigherMarks ? " A mark after a name, such as Do', means the higher version of that note." : ''}`}
            example={(
                <>
                    <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-neutral-400">How the notes are played</p>
                    <div role="img" aria-label={together ? `${low} and ${high} sound together` : `${low} plays first, then ${high}`} className="mt-4 rounded-xl border border-neutral-200 bg-white p-4">
                        <p className="mb-3 text-center text-xs font-semibold text-neutral-500">{together ? 'At the same time' : 'One after the other'}</p>
                        <div aria-hidden="true" className="flex items-center gap-2">
                            <div className="min-w-0 flex-1 rounded-lg bg-neutral-100 px-2 py-3 text-center"><p className="text-[10px] font-medium text-neutral-500">{together ? 'Lower note' : 'First note'}</p><p className="mt-1 text-xl font-bold text-neutral-900">{low}</p></div>
                            <span className="text-xl text-neutral-400">{together ? '+' : '→'}</span>
                            <div className="min-w-0 flex-1 rounded-lg bg-neutral-100 px-2 py-3 text-center"><p className="text-[10px] font-medium text-neutral-500">{together ? 'Higher note' : 'Next note'}</p><p className="mt-1 text-xl font-bold text-neutral-900">{high}</p></div>
                        </div>
                    </div>
                    <p className="mt-4 text-center text-[10px] font-medium uppercase tracking-widest text-neutral-400">Example answer</p>
                    <p className="mt-1 text-center text-xl font-bold text-neutral-900">{example}</p>
                    <div role="img" aria-label={`Example answer buttons with ${example} selected`} className="mt-4 grid grid-cols-2 gap-2">
                        {choices.map((choice, index) => (
                            <span aria-hidden="true" key={choice} className={`flex min-w-0 items-center gap-2 rounded-xl border px-2.5 py-3 text-xs font-semibold ${choice === example ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-neutral-200 bg-white text-neutral-600'}`}>
                                <span className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-[10px] font-bold ${choice === example ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-500'}`}>{String.fromCharCode(65 + index)}</span>
                                <span>{choice}</span>
                            </span>
                        ))}
                    </div>
                    <p className="mt-4 text-xs leading-relaxed text-neutral-500">This example shows {example} selected. In your quiz, choose the pair that matches the two notes you hear.</p>
                </>
            )}
        />
    );
}
