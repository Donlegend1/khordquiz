import { darkGrid } from '@/Components/Site/backgrounds';
import Eyebrow from '@/Components/Site/Eyebrow';

export default function FinalCta() {
    return (
        <section style={darkGrid}>
            <div className="mx-auto max-w-7xl px-6 py-20 text-center lg:px-[100px] lg:py-24">
                <Eyebrow variant="dark">Start Today</Eyebrow>

                <h2 className="mt-5 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    Ready to train{' '}
                    <span className="font-display font-semibold tracking-normal text-brand">your ear?</span>
                </h2>

                <p className="mx-auto mt-4 max-w-[480px] text-base leading-snug text-gray-300">
                    Join the musicians training their ear a little every day. 94 quizzes across 16 categories, three
                    levels, and a leaderboard to keep you motivated.
                </p>

                <a
                    href="#download"
                    className="mt-10 inline-block rounded-md bg-brand px-5 py-3 text-base font-medium text-white transition hover:bg-brand-dark"
                >
                    Download App Now
                </a>
            </div>
        </section>
    );
}
