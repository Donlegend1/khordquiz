import Eyebrow from '@/Components/Site/Eyebrow';
import PhoneFrame from '@/Components/Site/PhoneFrame';
import { tk, useTrans } from '@/lib/i18n';

// Mirrors LANGUAGES in the app's settings screen (app/settings.tsx). Add to this list as languages are added.
// Native names — not translated.
const languages = ['English', 'Français', 'Español', 'Português', 'Deutsch'];

const features = [
    {
        tag: tk('Audio Quizzes'),
        title: tk('Listen, then answer.'),
        description:
            tk('Every question starts with a sound. Press play, then name the note, interval, chord or progression you hear.'),
        points: [tk('25 questions in every quiz'), tk('Instant feedback after each answer'), tk('Multi-select questions for chord extensions')],
        image: '/images/app-quiz.png',
        alt: tk('Quiz screen asking to recognise a chord progression and pick each chord quality'),
    },
    {
        tag: tk('Instrument Sounds'),
        title: tk('Train on the sound you actually play.'),
        description:
            tk('Choose the instrument each quiz is played in, so your ear learns the sounds you hear in real music.'),
        points: [
            tk('Piano, violin, saxophone, guitar, xylophone and voice'),
            tk('Strings, choir and organ on chord quizzes'),
            tk('Pick a new sound before every quiz'),
        ],
        image: '/images/app-sounds.png',
        alt: tk('Quiz preview screen with a choice of piano, violin, saxophone, xylophone, guitar and voice'),
        callout: <SoundCallout />,
    },
    {
        tag: tk('Mistake Review'),
        title: tk('Know exactly what trips you up.'),
        description:
            tk('KhordQuiz spots the pairs you keep mixing up — like hearing a Major chord but picking Augmented — so you know exactly what to listen for next time.'),
        points: [tk('Your most-confused pairs, ranked by how often you mix them up'), tk('Every missed question saved to a replay list'), tk('One tap from Progress — no digging through quizzes')],
        image: '/images/app-confused.png',
        alt: tk('Most confused screen showing answer pairs mixed up most often, with a Review mistakes button'),
        callout: <MistakeCallout />,
    },
    {
        tag: tk('Progress'),
        title: tk('See exactly where your ear is weakest.'),
        description:
            tk('Your accuracy, streak and daily goal at a glance, with a weekly chart of your practice to show how far you have come.'),
        points: [
            tk('Overall accuracy and quizzes completed'),
            tk('A daily goal you can change any time'),
            tk('A Needs Work list of your lowest-scoring quizzes'),
        ],
        image: '/images/app-progress.png',
        alt: tk('Your progress screen showing overall accuracy, streak and today’s goal'),
    },
    {
        tag: tk('Leaderboard'),
        title: tk('Climb the global leaderboard.'),
        description:
            tk('Compete with other students. Your rank stays on screen, along with how many points it takes to pass the next player.'),
        points: [tk('This week and all-time rankings'), tk('A podium for the top three players'), tk('Points needed to pass the next player')],
        image: '/images/app-leaderboard.png',
        alt: tk('Leaderboard screen showing your rank and the top players this week'),
    },
    {
        tag: tk('Languages'),
        title: tk('Learn in your language.'),
        description: tk('Use KhordQuiz in the language you are most comfortable with, so nothing gets in the way of training your ear.'),
        points: [tk('Available in :languages and more'), tk('Switch any time from Settings'), tk('Light and dark mode, too')],
        image: '/images/app-settings.png',
        alt: tk('Settings screen with appearance, language and practice options'),
        callout: <LanguageCallout />,
    },
    {
        tag: tk('Challenges'),
        title: tk('Challenge your friends.'),
        description:
            tk('Finish any quiz and send it as a challenge. Your friend plays the same quiz, and you both see who came out on top.'),
        points: [tk('Send a challenge after any quiz'), tk('See whose turn it is at a glance'), tk('Track your wins, losses and win rate')],
        image: '/images/app-challenges.png',
        alt: tk('Challenges screen showing wins, losses and challenges from friends'),
    },
];

export default function Features() {
    const t = useTrans();

    return (
        <section id="features" className="scroll-mt-[88px] bg-white">
            <div className="mx-auto max-w-7xl px-6 py-16 lg:px-[100px] lg:py-20">
                <div className="mx-auto max-w-2xl text-center">
                    <Eyebrow>{t('Everything you need')}</Eyebrow>

                    <h2 className="mt-5 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl lg:text-[44px]">
                        {t('Built for real')}{' '}
                        <span className="font-display font-semibold tracking-normal text-brand">{t('Progress.')}</span>
                    </h2>

                    <p className="mx-auto mt-4 max-w-[480px] text-base leading-relaxed text-neutral-700">
                        {t('KhordQuiz turns ear training into a daily habit. It tracks what you can recognise, shows where you are weakest, and pushes you forward.')}
                    </p>
                </div>

                <div className="mt-12 flex flex-col gap-14 lg:mt-16 lg:gap-20">
                    {features.map((feature, index) => (
                        <FeatureRow key={feature.title} {...feature} reversed={index % 2 === 1} />
                    ))}
                </div>
            </div>
        </section>
    );
}

function FeatureRow({ tag, title, description, points, image, alt, callout, reversed }) {
    const t = useTrans();

    return (
        <article className="grid items-center gap-8 lg:grid-cols-2 lg:gap-16">
            <div className={reversed ? 'lg:order-2' : ''}>
                <span className="inline-block rounded bg-gray-100 px-2 py-1 text-xs font-medium uppercase tracking-wide text-neutral-700">
                    {t(tag)}
                </span>
                <h3 className="mt-3 text-2xl font-bold tracking-tight text-neutral-900 sm:text-[28px] sm:leading-tight">
                    {t(title)}
                </h3>
                <p className="mt-3 max-w-[480px] text-base leading-relaxed text-neutral-700">{t(description)}</p>

                <ul className="mt-5 space-y-2.5">
                    {points.map((point) => (
                        <li key={point} className="flex items-start gap-3 text-[15px] text-neutral-800">
                            <CheckIcon />
                            {t(point, { languages: languages.join(', ') })}
                        </li>
                    ))}
                </ul>
            </div>

            {/* Phone is cropped at the panel's bottom edge. */}
            <div
                className={`relative h-[340px] overflow-hidden rounded-2xl bg-gradient-to-b from-[#F2F5FB] to-[#FCEFE6] sm:h-[380px] ${reversed ? 'lg:order-1' : ''}`}
            >
                <PhoneFrame
                    src={image}
                    alt={t(alt)}
                    className={`mx-auto mt-10 w-[220px] sm:w-[240px] ${callout ? 'opacity-60' : ''}`}
                />
                {callout}
            </div>
        </article>
    );
}

function CheckIcon() {
    return (
        <svg className="mt-0.5 h-5 w-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M4 10.5l4 4 8-9" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

const calloutClass =
    'absolute left-1/2 -translate-x-1/2 rounded-xl bg-white ring-2 ring-brand/70 shadow-[0_18px_40px_-10px_rgba(193,39,31,0.35)]';

// The instrument grid, cut straight from the screenshot and enlarged.
function SoundCallout() {
    const crop = { x: 24, y: 524, w: 544, h: 278 }; // in the 589×1280 screenshot
    const width = 300;
    const scale = width / crop.w;

    return (
        <div className={`${calloutClass} top-[120px] p-2.5 sm:top-[130px]`} aria-hidden="true">
            <div
                style={{
                    width,
                    height: crop.h * scale,
                    backgroundImage: 'url(/images/app-sounds.png)',
                    backgroundSize: `${589 * scale}px auto`,
                    backgroundPosition: `-${crop.x * scale}px -${crop.y * scale}px`,
                }}
            />
        </div>
    );
}

// The "Most confused" card, cut straight from the screenshot and enlarged.
function MistakeCallout() {
    const crop = { x: 20, y: 80, w: 549, h: 486 }; // in the 589×1280 screenshot
    const width = 300;
    const scale = width / crop.w;

    return (
        <div className={`${calloutClass} top-[70px] p-2 sm:top-[80px]`} aria-hidden="true">
            <div
                style={{
                    width,
                    height: crop.h * scale,
                    backgroundImage: 'url(/images/app-confused.png)',
                    backgroundSize: `${589 * scale}px auto`,
                    backgroundPosition: `-${crop.x * scale}px -${crop.y * scale}px`,
                }}
            />
        </div>
    );
}

function LanguageCallout() {
    const t = useTrans();

    return (
        <div className={`${calloutClass} top-[72px] w-[260px] py-2`} aria-hidden="true">
            <p className="px-5 pb-1.5 pt-1 text-[11px] font-semibold uppercase tracking-wide text-neutral-500">
                {t('Choose language')}
            </p>
            <ul>
                {languages.map((language, index) => (
                    <li
                        key={language}
                        className="flex items-center justify-between border-t border-gray-100 px-5 py-2 text-[15px] leading-5 text-neutral-900 first:border-t-0"
                    >
                        <span className={index === 0 ? 'font-semibold text-brand' : ''}>{language}</span>
                        {index === 0 && (
                            <svg className="h-4 w-4 text-brand" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fillRule="evenodd"
                                    d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z"
                                    clipRule="evenodd"
                                />
                            </svg>
                        )}
                    </li>
                ))}
                <li className="border-t border-gray-100 px-5 py-2 text-[13px] leading-5 text-neutral-500">{t('and more…')}</li>
            </ul>
        </div>
    );
}
