import { Link, router } from '@inertiajs/react';

const difficultyClass = {
    Beginner: 'bg-emerald-50 text-emerald-700',
    Intermediate: 'bg-amber-50 text-amber-800',
    Advanced: 'bg-brand/10 text-brand',
};

export default function QuizTable({ quizzes }) {
    const remove = (quiz) => {
        if (confirm(`Delete “${quiz.title}”? Members lose their progress on it.`)) {
            router.delete(route('admin.quizzes.destroy', quiz.id));
        }
    };

    if (quizzes.length === 0) {
        return (
            <div className="rounded-2xl border border-dashed border-neutral-300 bg-white px-6 py-14 text-center">
                <p className="text-neutral-700">No quizzes yet.</p>
                <Link
                    href={route('admin.quizzes.create')}
                    className="mt-4 inline-flex items-center rounded-md bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-dark"
                >
                    Add quiz
                </Link>
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-2xl border border-black/5 bg-white">
            <div className="overflow-x-auto">
                <table className="min-w-full text-left text-sm">
                    <thead className="border-b border-black/5 text-xs uppercase tracking-wide text-neutral-500">
                        <tr>
                            <th className="px-5 py-3 font-medium">Quiz</th>
                            <th className="px-5 py-3 font-medium">Category</th>
                            <th className="px-5 py-3 font-medium">Level</th>
                            <th className="px-5 py-3 font-medium">Questions</th>
                            <th className="px-5 py-3 font-medium">Attempts</th>
                            <th className="px-5 py-3 font-medium">Status</th>
                            <th className="px-5 py-3 font-medium">
                                <span className="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-black/5">
                        {quizzes.map((quiz) => (
                            <tr key={quiz.id}>
                                <td className="px-5 py-4">
                                    <p className="font-medium text-neutral-900">{quiz.title}</p>
                                    {quiz.description && (
                                        <p className="mt-0.5 max-w-xs truncate text-neutral-500">{quiz.description}</p>
                                    )}
                                </td>
                                <td className="whitespace-nowrap px-5 py-4 text-neutral-600">{quiz.category}</td>
                                <td className="px-5 py-4">
                                    <span
                                        className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                                            difficultyClass[quiz.difficulty] ?? 'bg-neutral-100 text-neutral-600'
                                        }`}
                                    >
                                        {quiz.difficulty}
                                    </span>
                                </td>
                                <td className="px-5 py-4 text-neutral-600">{quiz.question_count}</td>
                                <td className="px-5 py-4 text-neutral-600">{quiz.attempts_count}</td>
                                <td className="px-5 py-4">
                                    <span className={quiz.is_published ? 'text-emerald-700' : 'text-neutral-500'}>
                                        {quiz.is_published ? 'Published' : 'Draft'}
                                    </span>
                                </td>
                                <td className="whitespace-nowrap px-5 py-4 text-right">
                                    <Link
                                        href={route('admin.quizzes.edit', quiz.id)}
                                        className="font-medium text-neutral-700 hover:text-neutral-900"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => remove(quiz)}
                                        className="ms-4 font-medium text-brand hover:text-brand-dark"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
