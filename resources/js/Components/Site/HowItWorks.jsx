import { darkGrid } from '@/Components/Site/backgrounds';
import Eyebrow from '@/Components/Site/Eyebrow';

const steps = [
    {
        title: 'Sign up & Choose your Plan',
        description:
            'Create your account, pick an avatar and your level, and get a starting point picked for you. Then choose a plan and start your 7‑day free trial.',
    },
    {
        title: 'Pick & Play a Quiz',
        description:
            'Choose a quiz and the sound it plays in, listen to the reference audio, then answer 25 questions with instant feedback.',
    },
    {
        title: 'Track your Growth',
        description:
            'See your score, review your mistakes, send the quiz to a friend as a challenge, and keep your streak alive.',
    },
];

export default function HowItWorks() {
    return (
        <section
            id="how-it-works"
            className="scroll-mt-[88px]"
            style={darkGrid}
        >
            <div className="mx-auto max-w-7xl px-6 py-16 lg:px-[100px] lg:py-20">
                <Eyebrow variant="dark">Simple to Start</Eyebrow>

                <h2 className="mt-5 text-3xl font-bold leading-tight tracking-tight text-white sm:text-4xl">
                    Up and training{' '}
                    <span className="font-display font-semibold tracking-normal text-brand">
                        in
                        <br className="hidden sm:inline" /> three steps.
                    </span>
                </h2>

                <p className="mt-4 text-base text-gray-300">From download to your first quiz in a few minutes</p>

                <ol className="mt-10 grid gap-5 md:grid-cols-3">
                    {steps.map((step, index) => (
                        <li
                            key={step.title}
                            className="rounded-xl border border-white/10 bg-[#23262A] p-6 transition hover:border-white/20"
                        >
                            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-xl font-semibold text-white">
                                {index + 1}
                            </span>
                            <h3 className="mt-6 text-lg font-semibold text-white lg:text-xl">{step.title}</h3>
                            <p className="mt-2 text-sm leading-snug text-gray-300">{step.description}</p>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}
