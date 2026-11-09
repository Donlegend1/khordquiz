import { darkGrid } from '@/Components/Site/backgrounds';
import Eyebrow from '@/Components/Site/Eyebrow';
import { useTrans } from '@/lib/i18n';

export default function FinalCta() {
    const t = useTrans();

    return (
        <section style={darkGrid}>
            <div className="mx-auto max-w-7xl px-6 py-20 text-center lg:px-[100px] lg:py-24">
                <Eyebrow variant="dark">{t('Start Today')}</Eyebrow>

                <h2 className="mt-5 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    {t('Ready to train')}{' '}
                    <span className="font-display font-semibold tracking-normal text-brand">{t('your ear?')}</span>
                </h2>

                <p className="mx-auto mt-4 max-w-[480px] text-base leading-snug text-gray-300">
                    {t("Your ear can learn to do this. A few focused minutes a day, and you'll start catching notes, chords and progressions before the sound even fades.")}
                </p>

                <a
                    href="#pricing"
                    className="mt-10 inline-block rounded-md bg-brand px-5 py-3 text-base font-medium text-white transition hover:bg-brand-dark"
                >
                    {t('Start Free')}
                </a>
            </div>
        </section>
    );
}
