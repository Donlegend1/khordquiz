import Eyebrow from '@/Components/Site/Eyebrow';
import PhoneFrame from '@/Components/Site/PhoneFrame';

export default function Hero() {
    return (
        <section
            className="relative overflow-hidden"
            style={{
                backgroundColor: '#FBF6F3',
                backgroundImage: [
                    'linear-gradient(to right, rgba(17, 24, 39, 0.05) 1px, transparent 1px)',
                    'linear-gradient(to bottom, rgba(17, 24, 39, 0.05) 1px, transparent 1px)',
                    'linear-gradient(to bottom, #F3F6FB 0%, #FBF4F0 60%, #FDEFE7 100%)',
                ].join(', '),
                backgroundSize: '48px 48px, 48px 48px, 100% 100%',
            }}
        >
            <div className="mx-auto grid max-w-7xl items-center gap-12 px-6 pt-16 lg:grid-cols-[1fr_auto] lg:gap-16 lg:px-[100px] lg:pt-14">
                <div className="pb-4 text-center lg:pb-14 lg:text-left">
                    <Eyebrow>Ear Training for Every Musician</Eyebrow>

                    <h1 className="mt-5 text-4xl font-bold leading-[1.15] tracking-tight text-neutral-900 sm:text-5xl lg:text-[52px]">
                        Train Your Ear
                        <br />
                        Through{' '}
                        <span className="font-display font-medium tracking-normal text-brand">
                            Smart Quizzes
                        </span>
                    </h1>

                    <p className="mx-auto mt-6 max-w-[600px] text-base leading-relaxed text-neutral-700 lg:text-[17px] lg:mx-0">
                        Press play, then name what you hear. 94 audio quizzes on intervals, chords and
                        progressions — picked for your level, with streaks, challenges and a leaderboard to keep you
                        coming back.
                    </p>

                    <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row lg:justify-start">
                        <a
                            href="#download"
                            className="rounded-md bg-brand px-5 py-3 text-center text-base font-medium text-white transition hover:bg-brand-dark"
                        >
                            Download App Now
                        </a>
                        <a
                            href="#features"
                            className="rounded-md border border-brand bg-transparent px-10 py-3 text-center text-base font-medium text-brand transition hover:bg-brand/5"
                        >
                            See Features
                        </a>
                    </div>
                </div>

                <PhoneMockup />
            </div>
        </section>
    );
}

function PhoneMockup() {
    return (
        // Cropped at the section's bottom edge, as in the design.
        <div className="mx-auto h-[460px] w-[300px] sm:h-[500px] sm:w-[336px] lg:mr-6">
            <PhoneFrame
                src="/images/app-home.png"
                alt="KhordQuiz home screen showing streak, accuracy, today's goal and the next quiz"
                loading="eager"
            />
        </div>
    );
}
