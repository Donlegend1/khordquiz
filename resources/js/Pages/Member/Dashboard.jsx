import AppShell from '@/Layouts/AppShell';
import { Head } from '@inertiajs/react';

function Stat({ label, value, detail }) {
    return (
        <div className="rounded-2xl border border-black/5 bg-white px-5 py-4">
            <p className="text-sm text-neutral-500">{label}</p>
            <p className="mt-2 text-3xl font-bold tracking-tight">{value}</p>
            {detail && <p className="mt-1 text-sm text-neutral-500">{detail}</p>}
        </div>
    );
}

const awardTone = {
    streak: 'bg-orange-50 text-orange-700',
    score: 'bg-brand/10 text-brand',
    milestone: 'bg-amber-50 text-amber-800',
};

export default function Dashboard({ streak, stats, resume, recentQuizzes, awards }) {
    return (
        <AppShell
            eyebrow="Member"
            title="Your training"
            intro={`Current streak: ${streak.current} ${streak.current === 1 ? 'day' : 'days'}. Last practice ${streak.last_practiced_on.toLowerCase()}.`}
        >
            <Head title="Your training" />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Stat label="Current streak" value={streak.current} detail={`Best ${streak.longest} days`} />
                <Stat label="Finished" value={stats.completed} detail="Quizzes completed" />
                <Stat label="In progress" value={stats.inProgress} detail="Waiting for you" />
                <Stat label="Accuracy" value={stats.accuracy === null ? '—' : `${stats.accuracy}%`} detail="On finished quizzes" />
            </div>

            <section className="mt-8">
                <h2 className="text-lg font-semibold">Where you stopped</h2>
                <p className="mt-1 text-sm text-neutral-500">The quiz you left unfinished, at the question you were on.</p>

                {resume ? (
                    <article className="mt-4 rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.14em] text-brand">{resume.quiz.category}</p>
                                <h3 className="mt-2 text-2xl font-bold tracking-tight">{resume.quiz.title}</h3>
                                <p className="mt-1 text-sm text-neutral-500">
                                    {resume.quiz.difficulty} · Last played {resume.last_played_at}
                                </p>
                            </div>
                            <div className="rounded-xl bg-[#F6F1EC] px-4 py-3 text-right">
                                <p className="text-xs uppercase tracking-wide text-neutral-500">Stopped at</p>
                                <p className="text-2xl font-bold text-brand">
                                    {resume.stopped_at_question}
                                    <span className="text-base font-medium text-neutral-500"> / {resume.quiz.question_count}</span>
                                </p>
                            </div>
                        </div>
                        {resume.question && (
                            <div className="mt-5 rounded-xl bg-[#F6F1EC] px-4 py-4">
                                <p className="text-sm font-medium text-neutral-900">{resume.question.prompt}</p>
                                {resume.question.audio_url && (
                                    <audio controls src={resume.question.audio_url} className="mt-3 w-full" />
                                )}
                                {resume.question.video_url && (
                                    <video
                                        controls
                                        src={resume.question.video_url}
                                        className="mt-3 max-h-64 w-full rounded-lg bg-black"
                                    />
                                )}
                                <ul className="mt-3 grid gap-2 sm:grid-cols-2">
                                    {resume.question.choices.map((choice, index) => (
                                        <li key={index} className="rounded-lg bg-white px-3 py-2 text-sm text-neutral-700">
                                            {choice}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                        <div className="mt-5">
                            <div className="mb-1.5 flex justify-between text-xs text-neutral-500">
                                <span>Question {resume.stopped_at_question}</span>
                                <span>{resume.progress}% through</span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-neutral-100">
                                <div className="h-full rounded-full bg-brand" style={{ width: `${resume.progress}%` }} />
                            </div>
                        </div>
                    </article>
                ) : (
                    <div className="mt-4 rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-8 text-sm text-neutral-600">
                        You don’t have a quiz in progress.
                        {recentQuizzes[0] && (
                            <span> Last one you touched was {recentQuizzes[0].quiz.title}.</span>
                        )}
                    </div>
                )}
            </section>

            <div className="mt-8 grid gap-8 lg:grid-cols-5">
                <section className="lg:col-span-3">
                    <h2 className="text-lg font-semibold">Recent quizzes</h2>
                    <p className="mt-1 text-sm text-neutral-500">Finished scores and quizzes still open.</p>

                    <div className="mt-4 overflow-hidden rounded-2xl border border-black/5 bg-white">
                        {recentQuizzes.length === 0 ? (
                            <p className="px-5 py-8 text-sm text-neutral-500">No quizzes yet. Your history will show up here.</p>
                        ) : (
                            <ul className="divide-y divide-black/5">
                                {recentQuizzes.map((attempt) => (
                                    <li key={attempt.id} className="px-5 py-4">
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <p className="font-medium">{attempt.quiz.title}</p>
                                                {attempt.status !== 'completed' && attempt.question && (
                                                    <p className="mt-1 text-sm text-neutral-700">{attempt.question.prompt}</p>
                                                )}
                                                <p className="mt-0.5 text-sm text-neutral-500">
                                                    {attempt.quiz.category} · {attempt.last_played_at}
                                                </p>
                                            </div>
                                            {attempt.status === 'completed' ? (
                                                <p className="text-right text-sm">
                                                    <span className="font-semibold text-neutral-900">{attempt.score}%</span>
                                                    <span className="mt-0.5 block text-neutral-500">
                                                        {attempt.correct_answers}/{attempt.quiz.question_count}
                                                    </span>
                                                </p>
                                            ) : (
                                                <p className="text-right text-sm">
                                                    <span className="font-semibold text-brand">Question {attempt.stopped_at_question}</span>
                                                    <span className="mt-0.5 block text-neutral-500">of {attempt.quiz.question_count}</span>
                                                </p>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>

                <section className="lg:col-span-2">
                    <h2 className="text-lg font-semibold">Awards</h2>
                    <p className="mt-1 text-sm text-neutral-500">Streaks, scores, and milestones.</p>

                    <div className="mt-4 space-y-3">
                        {awards.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-8 text-sm text-neutral-500">
                                Awards show up when you finish a quiz or keep a streak.
                            </div>
                        ) : (
                            awards.map((award) => (
                                <article key={award.id} className="rounded-2xl border border-black/5 bg-white px-5 py-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <h3 className="font-semibold">{award.title}</h3>
                                        <span
                                            className={`rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide ${
                                                awardTone[award.kind] ?? 'bg-neutral-100 text-neutral-600'
                                            }`}
                                        >
                                            {award.kind}
                                        </span>
                                    </div>
                                    <p className="mt-1 text-sm text-neutral-600">{award.description}</p>
                                    <p className="mt-2 text-xs text-neutral-400">{award.earned_at}</p>
                                </article>
                            ))
                        )}
                    </div>
                </section>
            </div>
        </AppShell>
    );
}
