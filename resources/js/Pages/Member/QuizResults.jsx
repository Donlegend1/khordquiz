import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm } from '@inertiajs/react';

function TrophyIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M8 4h8v5a4 4 0 01-8 0V4z" />
            <path d="M8 5H5a3 3 0 003 3M16 5h3a3 3 0 01-3 3" />
            <path d="M12 13v3M9 20h6M10 20v-2.5h4V20" />
        </svg>
    );
}

function message(percent) {
    if (percent === 100) return 'Perfect score!';
    if (percent >= 80) return 'Great work!';
    if (percent >= 50) return 'Nice effort!';
    return 'Keep practising!';
}

export default function QuizResults({ quiz, result }) {
    const { post, processing } = useForm();

    return (
        <AppShell eyebrow={quiz.category} title={quiz.title} intro="Quiz results">
            <Head title={`${quiz.title} — Results`} />

            <div className="mx-auto max-w-md">
                <div className="flex flex-col items-center rounded-2xl border border-black/5 bg-white px-6 py-10 text-center">
                    <span className="flex h-16 w-16 items-center justify-center rounded-full bg-rose-50 text-brand">
                        <TrophyIcon className="h-7 w-7" />
                    </span>
                    <p className="mt-4 text-lg font-extrabold text-neutral-900">{message(result.percent)}</p>
                    <p className="mt-1 text-5xl font-extrabold tracking-tight text-neutral-900">{result.percent}%</p>
                    <p className="mt-2 text-sm text-neutral-500">
                        {result.correct} of {result.total} correct
                    </p>
                </div>

                <div className="mt-6 space-y-2.5">
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => post(route('quiz.start', quiz.slug))}
                        className="w-full rounded-xl bg-brand py-3.5 text-sm font-bold text-white transition-opacity duration-150 disabled:opacity-60"
                    >
                        Retry Quiz
                    </button>
                    <Link
                        href={route('quiz.category', quiz.categorySlug)}
                        className="block w-full rounded-xl py-3.5 text-center text-sm font-semibold text-neutral-600 hover:bg-neutral-50"
                    >
                        Back to {quiz.category}
                    </Link>
                </div>
            </div>
        </AppShell>
    );
}
