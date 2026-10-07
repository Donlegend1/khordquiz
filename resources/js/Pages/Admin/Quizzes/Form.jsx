import InputError from '@/Components/InputError';
import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm } from '@inertiajs/react';

const fieldClass = (error) =>
    `block w-full rounded-lg border bg-white px-3.5 py-2.5 text-[15px] text-neutral-900 focus:outline-none focus:ring-2 ${
        error
            ? 'border-brand focus:border-brand focus:ring-brand/20'
            : 'border-gray-300 focus:border-neutral-900 focus:ring-neutral-900/10'
    }`;

export default function Form({ quiz = null, categories, difficulties }) {
    const editing = Boolean(quiz);
    const { data, setData, post, put, processing, errors } = useForm({
        title: quiz?.title ?? '',
        category: quiz?.category ?? categories[0],
        difficulty: quiz?.difficulty ?? difficulties[0],
        description: quiz?.description ?? '',
        question_count: quiz?.question_count ?? 25,
        is_published: quiz?.is_published ?? true,
    });

    const submit = (event) => {
        event.preventDefault();

        if (editing) {
            put(route('admin.quizzes.update', quiz.id));
            return;
        }

        post(route('admin.quizzes.store'));
    };

    return (
        <AppShell
            eyebrow="Admin"
            title={editing ? 'Edit quiz' : 'Add quiz'}
            intro={editing ? 'Update the details members see in the catalog.' : 'Add a quiz to the ear-training catalog.'}
        >
            <Head title={editing ? 'Edit quiz' : 'Add quiz'} />

            <form
                onSubmit={submit}
                className="max-w-2xl rounded-2xl border border-black/5 bg-white px-5 py-6 sm:px-8"
            >
                <div>
                    <label htmlFor="title" className="mb-1.5 block text-sm font-medium">
                        Title
                    </label>
                    <input
                        id="title"
                        value={data.title}
                        onChange={(event) => setData('title', event.target.value)}
                        className={fieldClass(errors.title)}
                        placeholder="Harmonic 5th"
                        required
                    />
                    <InputError message={errors.title} className="mt-1.5" />
                </div>

                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label htmlFor="category" className="mb-1.5 block text-sm font-medium">
                            Category
                        </label>
                        <select
                            id="category"
                            value={data.category}
                            onChange={(event) => setData('category', event.target.value)}
                            className={fieldClass(errors.category)}
                        >
                            {categories.map((category) => (
                                <option key={category} value={category}>
                                    {category}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.category} className="mt-1.5" />
                    </div>

                    <div>
                        <label htmlFor="difficulty" className="mb-1.5 block text-sm font-medium">
                            Level
                        </label>
                        <select
                            id="difficulty"
                            value={data.difficulty}
                            onChange={(event) => setData('difficulty', event.target.value)}
                            className={fieldClass(errors.difficulty)}
                        >
                            {difficulties.map((difficulty) => (
                                <option key={difficulty} value={difficulty}>
                                    {difficulty}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.difficulty} className="mt-1.5" />
                    </div>
                </div>

                <div className="mt-4">
                    <label htmlFor="description" className="mb-1.5 block text-sm font-medium">
                        Description
                    </label>
                    <textarea
                        id="description"
                        value={data.description ?? ''}
                        onChange={(event) => setData('description', event.target.value)}
                        rows={4}
                        className={fieldClass(errors.description)}
                        placeholder="What should the ear be listening for?"
                    />
                    <InputError message={errors.description} className="mt-1.5" />
                </div>

                <div className="mt-4">
                    <label htmlFor="question_count" className="mb-1.5 block text-sm font-medium">
                        Questions
                    </label>
                    <input
                        id="question_count"
                        type="number"
                        min="1"
                        max="100"
                        value={data.question_count}
                        onChange={(event) => setData('question_count', event.target.value)}
                        className={`${fieldClass(errors.question_count)} max-w-[140px]`}
                        required
                    />
                    <InputError message={errors.question_count} className="mt-1.5" />
                </div>

                <label className="mt-5 flex items-center gap-2 text-sm text-neutral-700">
                    <input
                        type="checkbox"
                        checked={data.is_published}
                        onChange={(event) => setData('is_published', event.target.checked)}
                        className="rounded border-gray-300 text-brand focus:ring-brand/30"
                    />
                    Published for members
                </label>

                <div className="mt-6 flex items-center gap-4">
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center rounded-md bg-brand px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-dark disabled:opacity-60"
                    >
                        {processing ? 'Saving…' : editing ? 'Save changes' : 'Add quiz'}
                    </button>
                    <Link href={route('admin.quizzes.index')} className="text-sm font-medium text-neutral-600 hover:text-neutral-900">
                        Cancel
                    </Link>
                </div>
            </form>
        </AppShell>
    );
}
