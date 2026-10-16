import AppShell from '@/Layouts/AppShell';
import { Head, router } from '@inertiajs/react';

function BookmarkIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 3.5h12v17l-6-4-6 4v-17z" />
        </svg>
    );
}

export default function Saved({ saved }) {
    function removeBookmark(slug) {
        router.post(route('quiz.bookmark', slug), {}, { preserveScroll: true });
    }

    return (
        <AppShell eyebrow="Member" title="Saved" intro="Quizzes and questions you have bookmarked for future reference.">
            <Head title="Saved" />

            {saved.length === 0 ? (
                <div className="flex flex-col items-center rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-12 text-center">
                    <span className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                        <BookmarkIcon className="h-5 w-5" />
                    </span>
                    <p className="mt-3 text-sm font-medium text-neutral-700">Nothing saved yet</p>
                    <p className="mt-1 max-w-xs text-sm text-neutral-500">
                        Bookmark a quiz while you practise and it&apos;ll show up here.
                    </p>
                </div>
            ) : (
                <ul className="space-y-3">
                    {saved.map((item) => (
                        <li
                            key={item.slug}
                            className="flex items-center gap-4 rounded-2xl border border-black/5 bg-white px-5 py-4"
                        >
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand">
                                <BookmarkIcon className="h-5 w-5" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="font-medium text-neutral-900">{item.title}</p>
                                <p className="mt-0.5 text-sm text-neutral-500">
                                    {item.category} · {item.difficulty}
                                </p>
                            </div>
                            <span className="shrink-0 text-sm text-neutral-400">{item.savedAt}</span>
                            <button
                                type="button"
                                onClick={() => removeBookmark(item.slug)}
                                aria-label="Remove bookmark"
                                className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-neutral-400 transition-colors duration-150 hover:bg-neutral-100 hover:text-neutral-700"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-4 w-4">
                                    <path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" />
                                </svg>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </AppShell>
    );
}
