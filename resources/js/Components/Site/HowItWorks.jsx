import { darkGrid } from '@/Components/Site/backgrounds';
import Eyebrow from '@/Components/Site/Eyebrow';
import { tk, useTrans } from '@/lib/i18n';

const steps = [
    {
        title: tk('Sign up & Choose your Plan'),
        description: tk('Create your account and your 7‑day free trial starts right away — full access to every quiz from day one.'),
    },
    {
        title: tk('Pick & Play a Quiz'),
        description:
            tk('Choose any quiz and the instrument it’s played in, warm up with the reference audio, then answer 25 questions with instant feedback on every one.'),
    },
    {
        title: tk('Track your Growth'),
        description:
            tk('Watch your score climb, revisit every mistake until it sticks, challenge a friend to beat it, and keep your streak alive.'),
    },
];

export default function HowItWorks() {
    const t = useTrans();

    return (
        <section
            id="how-it-works"
            className="scroll-mt-[88px]"
            style={darkGrid}
        >
            <div className="mx-auto max-w-7xl px-6 py-16 lg:px-[100px] lg:py-20">
                <Eyebrow variant="dark">{t('Simple to Start')}</Eyebrow>

                <h2 className="mt-5 text-3xl font-bold leading-tight tracking-tight text-white sm:text-4xl">
                    {t('Up and training')}{' '}
                    <span className="font-display font-semibold tracking-normal text-brand">{t('in three steps.')}</span>
                </h2>

                <p className="mt-4 text-base text-gray-300">{t('From download to your first quiz in a few minutes')}</p>

                <ol className="mt-10 grid gap-5 md:grid-cols-3">
                    {steps.map((step, index) => (
                        <li
                            key={step.title}
                            className="rounded-xl border border-white/10 bg-[#23262A] p-6 transition hover:border-white/20"
                        >
                            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-xl font-semibold text-white">
                                {index + 1}
                            </span>
                            <h3 className="mt-6 text-lg font-semibold text-white lg:text-xl">{t(step.title)}</h3>
                            <p className="mt-2 text-sm leading-snug text-gray-300">{t(step.description)}</p>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}
