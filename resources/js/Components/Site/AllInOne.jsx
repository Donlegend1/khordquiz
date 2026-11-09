import Eyebrow from '@/Components/Site/Eyebrow';
import PhoneFrame from '@/Components/Site/PhoneFrame';
import { useTrans } from '@/lib/i18n';

export default function AllInOne() {
    const t = useTrans();

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
                <Eyebrow>{t('Endless Benefits')}</Eyebrow>

                <h2 className="mt-5 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">
                    {t('All-in-one Ear Training')}{' '}
                    <span className="font-display font-semibold tracking-normal text-brand">{t('Quiz App.')}</span>
                </h2>

                <p className="mx-auto mt-4 max-w-[440px] text-base leading-relaxed text-neutral-700">
                    {t('Practise at your level, see how you’re improving and know what to try next — all in one app.')}
                </p>

                {/* Phones are cropped at the section's bottom edge. */}
                <div className="mt-10 flex h-[300px] items-start justify-center sm:h-[320px]">
                    <PhoneFrame
                        src="/images/app-progress-overview-current.png"
                        alt={t('Progress screen showing today’s goal, a quiz to continue and areas to practise')}
                        className="z-0 -mr-3 mt-[108px] hidden w-[232px] shrink-0 sm:block"
                    />
                    <PhoneFrame
                        src="/images/app-chord-naming-current.png"
                        alt={t('Chord Naming quiz with the audio player, chord quality, degrees and exclusion choices')}
                        className="z-10 w-[280px] shrink-0 sm:w-[296px]"
                    />
                    <PhoneFrame
                        src="/images/app-for-you-current.png"
                        alt={t('For You screen with the next Beginner quiz and lessons coming up')}
                        className="z-0 -ml-3 mt-[108px] hidden w-[232px] shrink-0 sm:block"
                    />
                </div>
            </div>
        </section>
    );
}
