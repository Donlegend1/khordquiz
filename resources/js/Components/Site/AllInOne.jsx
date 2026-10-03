import Eyebrow from '@/Components/Site/Eyebrow';
import PhoneFrame from '@/Components/Site/PhoneFrame';

export default function AllInOne() {
    return (
        <section
            className="relative overflow-hidden"
            style={{
                backgroundColor: '#FFFFFF',
                backgroundImage: [
                    'linear-gradient(to right, rgba(17, 24, 39, 0.045) 1px, transparent 1px)',
                    'linear-gradient(to bottom, rgba(17, 24, 39, 0.045) 1px, transparent 1px)',
                ].join(', '),
                backgroundSize: '48px 48px',
            }}
        >
            <div className="mx-auto max-w-7xl px-6 pt-16 text-center lg:px-[100px] lg:pt-20">
                <Eyebrow>Endless Benefits</Eyebrow>

                <h2 className="mt-5 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">
                    All-in-one Ear Training{' '}
                    <span className="font-display font-semibold tracking-normal text-brand">Quiz App.</span>
                </h2>

                <p className="mx-auto mt-4 max-w-[440px] text-base leading-relaxed text-neutral-700">
                    Quizzes picked for your level, a second go at every question you missed, and a leaderboard to see
                    how you stack up — all in one app.
                </p>

                <a
                    href="#download"
                    className="mt-8 inline-block rounded-md bg-brand px-5 py-3 text-base font-medium text-white transition hover:bg-brand-dark"
                >
                    Download App Now
                </a>

                {/* Phones are cropped at the section's bottom edge. */}
                <div className="mt-10 flex h-[300px] items-start justify-center sm:h-[320px]">
                    <PhoneFrame
                        src="/images/app-leaderboard-all-time.png"
                        alt="All-time leaderboard showing your rank and the top players"
                        className="z-0 -mr-3 mt-[108px] hidden w-[232px] shrink-0 sm:block"
                    />
                    <PhoneFrame
                        src="/images/app-review.png"
                        alt="Reviewing a mistake: name the interval you hear"
                        className="z-10 w-[280px] shrink-0 sm:w-[296px]"
                    />
                    <PhoneFrame
                        src="/images/app-for-you-advanced.png"
                        alt="For You screen with the next quiz on your path and quizzes to practise again"
                        className="z-0 -ml-3 mt-[108px] hidden w-[232px] shrink-0 sm:block"
                    />
                </div>
            </div>
        </section>
    );
}
