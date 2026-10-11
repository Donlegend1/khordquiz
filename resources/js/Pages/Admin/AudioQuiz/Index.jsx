import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';

function NoteIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 18V9l9-2v9M9 18a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 019 18zM18 16a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 0118 16z" />
        </svg>
    );
}

export default function Index({ categories }) {
    return (
        <AppShell eyebrow="Admin" title="Audio Quiz" intro="Choose a category to manage its lessons and questions.">
            <Head title="Audio Quiz" />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {categories.map((category) => {
                    const percent = category.total > 0 ? Math.round((category.configured / category.total) * 100) : 0;

                    return (
                        <Link
                            key={category.slug}
                            href={route('admin.audio-quiz.show', category.slug)}
                            className="rounded-2xl border border-black/5 bg-white p-5 transition-colors duration-150 hover:border-brand/15 hover:bg-brand/[0.03]"
                        >
                            <div className="flex items-center gap-3">
                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-600">
                                    <NoteIcon className="h-4.5 w-4.5" />
                                </span>
                                <p className="text-[15px] font-bold text-neutral-900">{category.name}</p>
                            </div>
                            <p className="mt-3 text-sm text-neutral-500">
                                {category.total} {category.total === 1 ? 'lesson' : 'lessons'} &middot; {category.configured}/{category.total} configured
                            </p>
                            <div className="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-neutral-100">
                                <div className="h-full rounded-full bg-brand" style={{ width: `${percent}%` }} />
                            </div>
                        </Link>
                    );
                })}
            </div>
        </AppShell>
    );
}
