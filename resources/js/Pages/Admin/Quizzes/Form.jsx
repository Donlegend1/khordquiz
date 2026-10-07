import InputError from '@/Components/InputError';
import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm } from '@inertiajs/react';

const fieldClass = (error) =>
    `block w-full rounded-lg border bg-white px-3.5 py-2.5 text-[15px] text-neutral-900 focus:outline-none focus:ring-2 ${
        error
            ? 'border-brand focus:border-brand focus:ring-brand/20'
            : 'border-gray-300 focus:border-neutral-900 focus:ring-neutral-900/10'
    }`;

const blankQuestion = () => ({
    id: null,
    prompt: '',
    choices: ['', '', '', ''],
    correct: 0,
    audio: null,
    video: null,
    audio_url: null,
    video_url: null,
    keep_audio: false,
    keep_video: false,
});

function MediaField({ kind, label, question, index, error, onFile, onRemove }) {
    const file = question[kind];
    const url = question[`${kind}_url`];
    const keep = question[`keep_${kind}`];
    const showingCurrent = keep && url && !file;

    return (
        <div>
            <label className="mb-1.5 block text-sm font-medium" htmlFor={`${kind}-${index}`}>
                {label} <span className="font-normal text-neutral-500">(optional)</span>
            </label>
            {showingCurrent &&
                (kind === 'audio' ? (
                    <audio controls src={url} className="mb-2 w-full" />
                ) : (
                    <video controls src={url} className="mb-2 max-h-48 w-full rounded-lg bg-black" />
                ))}
            {file && <p className="mb-2 text-sm text-neutral-600">{file.name}</p>}
            <input
                id={`${kind}-${index}`}
                type="file"
                accept={kind === 'audio' ? 'audio/*' : 'video/*'}
                onChange={(event) => onFile(event.target.files?.[0] ?? null)}
                className="block w-full text-sm text-neutral-600 file:mr-3 file:rounded-md file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-neutral-800"
            />
            {(file || showingCurrent) && (
                <button type="button" onClick={onRemove} className="mt-2 text-sm font-medium text-brand hover:text-brand-dark">
                    Remove {label.toLowerCase()}
                </button>
            )}
            <InputError message={error} className="mt-1.5" />
        </div>
    );
}

export default function Form({ quiz = null, categories, difficulties }) {
    const editing = Boolean(quiz);
    const { data, setData, post, put, processing, errors } = useForm({
        title: quiz?.title ?? '',
        category: quiz?.category ?? categories[0],
        difficulty: quiz?.difficulty ?? difficulties[0],
        description: quiz?.description ?? '',
        is_published: quiz?.is_published ?? true,
        questions: quiz?.questions?.length ? quiz.questions : [blankQuestion()],
    });

    const updateQuestion = (index, patch) => {
        setData(
            'questions',
            data.questions.map((question, questionIndex) =>
                questionIndex === index ? { ...question, ...patch } : question,
            ),
        );
    };

    const updateChoice = (questionIndex, choiceIndex, value) => {
        const question = data.questions[questionIndex];

        updateQuestion(questionIndex, {
            choices: question.choices.map((choice, index) => (index === choiceIndex ? value : choice)),
        });
    };

    const submit = (event) => {
        event.preventDefault();

        const options = { forceFormData: true };

        if (editing) {
            put(route('admin.quizzes.update', quiz.id), options);
            return;
        }

        post(route('admin.quizzes.store'), options);
    };

    return (
        <AppShell
            eyebrow="Admin"
            title={editing ? 'Edit quiz' : 'Add quiz'}
            intro={
                editing
                    ? 'Update this quiz and the questions that belong only to it.'
                    : 'Add a quiz with its own questions and answers.'
            }
        >
            <Head title={editing ? 'Edit quiz' : 'Add quiz'} />

            <form onSubmit={submit} className="max-w-3xl space-y-6">
                <div className="rounded-2xl border border-black/5 bg-white px-5 py-6 sm:px-8">
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

                <label className="mt-5 flex items-center gap-2 text-sm text-neutral-700">
                    <input
                        type="checkbox"
                        checked={data.is_published}
                        onChange={(event) => setData('is_published', event.target.checked)}
                        className="rounded border-gray-300 text-brand focus:ring-brand/30"
                    />
                    Published for members
                </label>

                </div>

                <section className="rounded-2xl border border-black/5 bg-white px-5 py-6 sm:px-8">
                    <div className="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 className="text-lg font-semibold">Questions and answers</h2>
                            <p className="mt-1 text-sm text-neutral-500">
                                These belong to this quiz only. Mark the correct answer on each one.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setData('questions', [...data.questions, blankQuestion()])}
                            className="rounded-md border border-neutral-300 px-3 py-2 text-sm font-medium text-neutral-800 hover:bg-neutral-50"
                        >
                            Add question
                        </button>
                    </div>
                    <InputError message={errors.questions} className="mt-3" />

                    <div className="mt-5 space-y-5">
                        {data.questions.map((question, index) => (
                            <article key={index} className="rounded-xl border border-black/5 bg-[#F6F1EC] p-4">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="text-sm font-semibold">Question {index + 1}</p>
                                    {data.questions.length > 1 && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setData(
                                                    'questions',
                                                    data.questions.filter((_, questionIndex) => questionIndex !== index),
                                                )
                                            }
                                            className="text-sm font-medium text-brand hover:text-brand-dark"
                                        >
                                            Remove
                                        </button>
                                    )}
                                </div>

                                <label className="mt-3 block text-sm font-medium" htmlFor={`prompt-${index}`}>
                                    Question
                                </label>
                                <textarea
                                    id={`prompt-${index}`}
                                    value={question.prompt}
                                    rows={2}
                                    onChange={(event) => updateQuestion(index, { prompt: event.target.value })}
                                    className={`mt-1.5 ${fieldClass(errors[`questions.${index}.prompt`])}`}
                                    placeholder="Which note is a perfect 5th above C?"
                                />
                                <InputError message={errors[`questions.${index}.prompt`]} className="mt-1.5" />

                                <p className="mb-2 mt-4 text-sm font-medium">Answers</p>
                                <div className="space-y-2">
                                    {question.choices.map((choice, choiceIndex) => (
                                        <div key={choiceIndex}>
                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="radio"
                                                    name={`correct-${index}`}
                                                    checked={question.correct === choiceIndex}
                                                    onChange={() => updateQuestion(index, { correct: choiceIndex })}
                                                    className="text-brand focus:ring-brand/30"
                                                    aria-label={`Mark answer ${choiceIndex + 1} correct`}
                                                />
                                                <input
                                                    value={choice}
                                                    onChange={(event) => updateChoice(index, choiceIndex, event.target.value)}
                                                    className={fieldClass(errors[`questions.${index}.choices.${choiceIndex}`])}
                                                    placeholder={`Answer ${choiceIndex + 1}`}
                                                    aria-label={`Answer ${choiceIndex + 1}`}
                                                />
                                                {question.choices.length > 2 && (
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            const choices = question.choices.filter(
                                                                (_, current) => current !== choiceIndex,
                                                            );
                                                            const correct =
                                                                question.correct === choiceIndex
                                                                    ? 0
                                                                    : question.correct > choiceIndex
                                                                      ? question.correct - 1
                                                                      : question.correct;

                                                            updateQuestion(index, { choices, correct });
                                                        }}
                                                        className="shrink-0 text-sm text-neutral-500 hover:text-neutral-900"
                                                    >
                                                        Remove
                                                    </button>
                                                )}
                                            </div>
                                            <InputError
                                                message={errors[`questions.${index}.choices.${choiceIndex}`]}
                                                className="ms-6 mt-1"
                                            />
                                        </div>
                                    ))}
                                </div>
                                <InputError message={errors[`questions.${index}.choices`]} className="mt-1.5" />
                                <InputError message={errors[`questions.${index}.correct`]} className="mt-1.5" />

                                {question.choices.length < 6 && (
                                    <button
                                        type="button"
                                        onClick={() => updateQuestion(index, { choices: [...question.choices, ''] })}
                                        className="mt-3 text-sm font-medium text-neutral-700 hover:text-neutral-900"
                                    >
                                        Add answer
                                    </button>
                                )}

                                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                    <MediaField
                                        kind="audio"
                                        label="Audio"
                                        question={question}
                                        index={index}
                                        error={errors[`questions.${index}.audio`]}
                                        onFile={(audio) => updateQuestion(index, { audio, keep_audio: true })}
                                        onRemove={() => updateQuestion(index, { audio: null, audio_url: null, keep_audio: false })}
                                    />
                                    <MediaField
                                        kind="video"
                                        label="Video"
                                        question={question}
                                        index={index}
                                        error={errors[`questions.${index}.video`]}
                                        onFile={(video) => updateQuestion(index, { video, keep_video: true })}
                                        onRemove={() => updateQuestion(index, { video: null, video_url: null, keep_video: false })}
                                    />
                                </div>
                            </article>
                        ))}
                    </div>
                </section>

                <div className="flex items-center gap-4">
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
