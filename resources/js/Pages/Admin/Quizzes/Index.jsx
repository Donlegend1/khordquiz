import QuizTable from '@/Components/Admin/QuizTable';
import AppShell from '@/Layouts/AppShell';
import { Head, Link } from '@inertiajs/react';

export default function Index({ quizzes }) {
    return (
        <AppShell
            eyebrow="Admin"
            title="Quiz list"
            intro="Edit a quiz, take it down, or add a new one."
            action={
                <Link
                    href={route('admin.quizzes.create')}
                    className="inline-flex items-center rounded-md bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-dark"
                >
                    Add quiz
                </Link>
            }
        >
            <Head title="Quiz list" />

            <QuizTable quizzes={quizzes.data} />

            {quizzes.last_page > 1 && (
                <nav className="mt-4 flex flex-wrap gap-2" aria-label="Pagination">
                    {quizzes.links.map((link, index) =>
                        link.url ? (
                            <Link
                                key={index}
                                href={link.url}
                                className={`rounded-md px-3 py-1.5 text-sm ${
                                    link.active ? 'bg-brand text-white' : 'bg-white text-neutral-700 hover:bg-neutral-100'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ) : (
                            <span
                                key={index}
                                className="rounded-md px-3 py-1.5 text-sm text-neutral-400"
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ),
                    )}
                </nav>
            )}
        </AppShell>
    );
}
