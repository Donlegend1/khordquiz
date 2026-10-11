import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';

const DIFFICULTY_TONE = {
    Beginner: 'bg-emerald-50 text-emerald-700',
    Intermediate: 'bg-amber-50 text-amber-700',
    Advanced: 'bg-rose-50 text-brand',
};

function QuizIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="8.5" />
            <path d="M10 9.3l5.5 2.7-5.5 2.7z" fill="currentColor" stroke="none" />
        </svg>
    );
}

function ChevronIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 5l7 7-7 7" />
        </svg>
    );
}

function CheckIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 13l4 4L19 7" />
        </svg>
    );
}

function Row({ quiz }) {
    const content = (
        <>
            <span
                className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
                    quiz.available ? 'bg-neutral-100 text-neutral-600' : 'bg-neutral-50 text-neutral-300'
                }`}
            >
                <QuizIcon className="h-5 w-5" />
            </span>
            <div className="min-w-0 flex-1">
                <p className={`text-[15px] font-bold ${quiz.available ? 'text-neutral-900' : 'text-neutral-400'}`}>{quiz.title}</p>
                {quiz.available ? (
                    <>
                        <p className="mt-0.5 line-clamp-1 text-sm text-neutral-500">{quiz.description}</p>
                        <div className="mt-2 flex items-center gap-2">
                            <span className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${DIFFICULTY_TONE[quiz.difficulty] ?? 'bg-neutral-100 text-neutral-600'}`}>
                                {quiz.difficulty}
                            </span>
                            <span className="text-xs text-neutral-400">{quiz.question_count} questions</span>
                            {quiz.status === 'completed' && (
                                <span className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                    <CheckIcon className="h-3 w-3" />
                                    {quiz.score}%
                                </span>
                            )}
                            {quiz.status === 'in_progress' && <span className="text-xs font-semibold text-brand">In progress</span>}
                        </div>
                    </>
                ) : (
                    <p className="mt-0.5 text-sm text-neutral-400">Not built yet</p>
                )}
            </div>
            {quiz.available ? (
                <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
            ) : (
                <span className="shrink-0 rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-neutral-400">
                    Soon
                </span>
            )}
        </>
    );

    if (!quiz.available) {
        return (
            <div aria-disabled="true" className="flex cursor-default items-center gap-4 rounded-2xl border border-transparent bg-white/60 p-4">
                {content}
            </div>
        );
    }

    return (
        <Link
            href={route('quiz.preview', quiz.slug)}
            className="flex items-center gap-4 rounded-2xl border border-black/5 bg-white p-4 transition-colors duration-150 hover:border-brand/15 hover:bg-brand/[0.03]"
        >
            {content}
        </Link>
    );
}

export default function QuizCategory({ category, quizzes }) {
    return (
        <AppShell eyebrow="Start Quiz" title={category} intro="Pick a quiz to begin.">
            <Head title={category} />

            <div className="mx-auto max-w-2xl space-y-3">
                {quizzes.map((quiz) => (
                    <Row key={quiz.slug} quiz={quiz} />
                ))}
            </div>
        </AppShell>
    );
}
