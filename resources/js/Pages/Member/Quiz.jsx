import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';

function NoteIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 18V9l9-2v9M9 18a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 019 18zM18 16a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 0118 16z" />
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

export default function Quiz({ categories }) {
    return (
        <AppShell eyebrow="Member" title="Start Quiz" intro="Choose a category to begin practising.">
            <Head title="Start Quiz" />

            {categories.length === 0 ? (
                <div className="flex flex-col items-center rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-16 text-center">
                    <p className="text-sm text-neutral-500">No quizzes are published yet. Check back soon.</p>
                </div>
            ) : (
                <div className="mx-auto max-w-2xl space-y-3">
                    {categories.map((category) => (
                        <Link
                            key={category.slug}
                            href={route('quiz.category', category.slug)}
                            className="flex items-center gap-4 rounded-2xl border border-black/5 bg-white p-4 transition-colors duration-150 hover:border-brand/15 hover:bg-brand/[0.03]"
                        >
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-600">
                                <NoteIcon className="h-5 w-5" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="text-[15px] font-bold text-neutral-900">{category.name}</p>
                                {category.done > 0 ? (
                                    <div className="mt-1.5 flex items-center gap-2">
                                        <div className="h-1.5 flex-1 max-w-40 overflow-hidden rounded-full bg-neutral-100">
                                            <div className="h-full rounded-full bg-brand" style={{ width: `${category.percent}%` }} />
                                        </div>
                                        <span className="text-xs text-neutral-500">{category.percent}%</span>
                                    </div>
                                ) : (
                                    <p className="text-sm text-neutral-400">{category.quizCount} {category.quizCount === 1 ? 'quiz' : 'quizzes'} · not attempted yet</p>
                                )}
                            </div>
                            <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                        </Link>
                    ))}
                </div>
            )}
        </AppShell>
    );
}
