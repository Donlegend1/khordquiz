import Eyebrow from '@/Components/Site/Eyebrow';
import { pathQuizCount, paths, quizCount, totals } from '@/data/quizCatalog';
import { useState } from 'react';

// Static class strings so Tailwind keeps them.
const accents = {
    Beginner: { dot: 'bg-emerald-500', tint: 'bg-emerald-50 text-emerald-700' },
    Intermediate: { dot: 'bg-amber-500', tint: 'bg-amber-50 text-amber-700' },
    Advanced: { dot: 'bg-brand', tint: 'bg-brand/10 text-brand' },
};

const stats = [
    { value: totals.quizzes, label: 'Quizzes' },
    { value: totals.categories, label: 'Categories' },
    { value: '5,000+', label: 'Audio samples' },
    { value: paths.length, label: 'Learning paths' },
];

export default function Categories() {
    const [active, setActive] = useState(0);
    const path = paths[active];
    const accent = accents[path.level];

    return (
        <section
            id="categories"
            className="scroll-mt-[88px] bg-gradient-to-b from-[#F3F6FB] via-[#FBF5F1] to-[#FDEFE7]"
        >
            <div className="mx-auto max-w-7xl px-6 py-16 lg:px-[100px] lg:py-20">
                <div className="grid gap-10 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <Eyebrow>
                            {totals.categories} Categories · {totals.quizzes} Quizzes
                        </Eyebrow>

                        <h2 className="mt-5 text-3xl font-bold leading-tight tracking-tight text-neutral-900 sm:text-4xl">
                            From a single note to
                            <br />
                            <span className="font-display font-semibold tracking-normal text-brand">full progressions.</span>
                        </h2>

                        <p className="mt-4 max-w-[520px] text-base leading-relaxed text-neutral-700">
                            Every quiz sits on a learning path, in the order KhordQuiz teaches it. Pick your level and
                            you always know what to practise next.
                        </p>
                    </div>

                    <dl className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:w-[460px]">
                        {stats.map((stat) => (
                            <div key={stat.label} className="rounded-xl border border-white bg-white/70 px-4 py-3 shadow-sm">
                                <dt className="sr-only">{stat.label}</dt>
                                <dd className="text-2xl font-bold tracking-tight text-neutral-900">{stat.value}</dd>
                                <dd className="text-xs text-neutral-600" aria-hidden="true">
                                    {stat.label}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>

                {/* Level tabs */}
                <div
                    role="tablist"
                    aria-label="Learning paths"
                    className="mt-12 grid grid-cols-3 gap-1 rounded-xl bg-white p-1 shadow-sm ring-1 ring-gray-200 sm:inline-grid"
                >
                    {paths.map((item, index) => {
                        const selected = index === active;
                        return (
                            <button
                                key={item.level}
                                type="button"
                                role="tab"
                                aria-selected={selected}
                                aria-controls="path-panel"
                                onClick={() => setActive(index)}
                                className={`rounded-lg px-3 py-2.5 text-left transition sm:min-w-[170px] sm:px-5 ${
                                    selected ? 'bg-neutral-900 text-white' : 'text-neutral-700 hover:bg-gray-50'
                                }`}
                            >
                                <span className="flex items-center gap-2 text-sm font-semibold sm:text-base">
                                    <span className={`h-2 w-2 rounded-full ${accents[item.level].dot}`} />
                                    {item.level}
                                </span>
                                <span className={`mt-0.5 block text-xs ${selected ? 'text-white/70' : 'text-neutral-500'}`}>
                                    {pathQuizCount(item)} quizzes
                                </span>
                            </button>
                        );
                    })}
                </div>

                <div id="path-panel" role="tabpanel" aria-label={`${path.level} path`} className="mt-6">
                    <p className="text-base text-neutral-700">
                        <span className="font-semibold text-neutral-900">{path.level} path · </span>
                        {path.intro}
                    </p>

                    <ol className="mt-6 grid gap-4 md:grid-cols-2">
                        {path.categories.map((category, index) => (
                            <CategoryCard key={category.name} category={category} step={index + 1} accent={accent} />
                        ))}
                    </ol>
                </div>

                {/* Closing nudge */}
                <div className="mt-10 flex flex-col items-start gap-5 rounded-2xl bg-neutral-900 px-6 py-6 text-white sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <div>
                        <p className="text-lg font-semibold">Not sure where to start?</p>
                        <p className="mt-1 text-sm text-white/70">
                            Choose your level when you sign up and KhordQuiz hands you a starting point from your path.
                        </p>
                    </div>
                    <a
                        href="#download"
                        className="shrink-0 rounded-md bg-brand px-5 py-3 text-base font-medium text-white transition hover:bg-brand-dark"
                    >
                        Find your starting point
                    </a>
                </div>
            </div>
        </section>
    );
}

function CategoryCard({ category, step, accent }) {
    const count = quizCount(category);

    return (
        <li className="rounded-2xl border border-gray-100 bg-white p-5 shadow-[0_6px_24px_-8px_rgba(17,24,39,0.12)]">
            <div className="flex items-start gap-4">
                <span
                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold ${accent.tint}`}
                >
                    {String(step).padStart(2, '0')}
                </span>

                <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-3">
                        <h3 className="text-lg font-semibold leading-snug text-neutral-900">{category.name}</h3>
                        <span className="shrink-0 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-neutral-700">
                            {count} {count === 1 ? 'quiz' : 'quizzes'}
                        </span>
                    </div>
                    <p className="mt-1 text-sm text-neutral-600">{category.description}</p>
                </div>
            </div>

            <ul className="mt-4 flex flex-wrap gap-1.5" aria-label={`${category.name} quizzes`}>
                {category.quizzes
                    ? category.quizzes.map((quiz) => <QuizChip key={quiz}>{quiz}</QuizChip>)
                    : Array.from({ length: category.levels }, (_, index) => (
                          <QuizChip key={index}>Level {index + 1}</QuizChip>
                      ))}
                {category.general && <QuizChip>General</QuizChip>}
            </ul>
        </li>
    );
}

function QuizChip({ children }) {
    return (
        <li className="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-[13px] leading-tight text-neutral-700">
            {children}
        </li>
    );
}
