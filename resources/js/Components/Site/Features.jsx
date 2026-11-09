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
        tag: tk('Quick Guides'),
        title: tk('Know what to do before you press play.'),
        description:
            tk('Each quiz has a short guide with clear steps and an illustrated example. See what to listen for and how to choose your answer before you begin.'),
        points: [
            tk('Simple instructions for each quiz'),
            tk('Illustrations that show how an answer is chosen'),
            tk('Read at your own pace, then start when you are ready'),
        ],
        visual: <QuickGuidePreview />,
    },
    {
        tag: tk('Instrument Sounds'),
        title: tk('Choose the sound you want to practise with.'),
        description:
            tk('Explore familiar instruments and discover new ones. Choose a sound before you start a quiz, or save your favourite in Settings.'),
        points: [
            tk('28 instrument sounds across seven families'),
            tk('Grand piano, Rhodes, Harpejji, violin, choir and more'),
            tk('Choose from the sounds available for each quiz'),
        ],
        image: '/images/app-sounds-current.png',
        alt: tk('Updated sound picker with seven instrument families, colored instrument icons and Harpejji selected'),
        callout: <SoundCallout />,
    },
    {
        tag: tk('Review Your Answers'),
        title: tk('Hear it again. See what you missed.'),
        description:
            tk('After a quiz, go through your answers one at a time. Replay the sound, compare a missed answer with the correct one, and move back and forth whenever you need to.'),
        points: [
            tk('See your answer alongside the correct answer'),
            tk('Replay the question while you review'),
            tk('Use Previous, Next or jump to a question'),
        ],
        image: '/images/app-major-extension-review.png',
        alt: tk('Major Chord Extensions answer review with replay controls and the correct extension choices highlighted'),
        callout: <ReviewCallout />,
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
        title: tk('See your progress and what needs practice.'),
        description:
            tk('Keep track of your practice, work towards a daily goal, and see which quizzes need more attention. Your strongest results show how far you have come.'),
        points: [
            tk('Set a daily goal and see how many questions remain'),
            tk('Continue a quiz from where you left off'),
            tk('Compare Needs work with your Strongest results'),
        ],
        image: '/images/app-progress-current.png',
        alt: tk('Updated Progress page showing accuracy, practice totals, Continue and Today’s goal'),
        callout: <ProgressCallout />,
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

function FeatureRow({ tag, title, description, points, image, alt, callout, visual, reversed }) {
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
            {visual ? <div className={reversed ? 'lg:order-1' : ''}>{visual}</div> : <div
                className={`relative overflow-hidden rounded-2xl bg-gradient-to-b from-[#F2F5FB] to-[#FCEFE6] ${tag === 'Review Your Answers' ? 'h-[480px] sm:h-[520px]' : 'h-[340px] sm:h-[380px]'} ${reversed ? 'lg:order-1' : ''}`}
            >
                <PhoneFrame
                    src={image}
                    alt={t(alt)}
                    className={`mx-auto mt-10 w-[220px] sm:w-[240px] ${callout ? 'opacity-60' : ''}`}
                />
                {callout}
            </div>}
        </article>
    );
}

function QuickGuidePreview() {
    const t = useTrans();
    const steps = [
        [t('Listen'), t('Play the sound. Listen again whenever you need to.')],
        [t('Choose'), t('Select the answer that matches what you hear.')],
        [t('Submit'), t('Check your choice, then submit your answer.')],
    ];

    return (
        <figure aria-label={t('Example Quick Guide with three simple steps and a highlighted piano key')} className="relative isolate flex min-h-[420px] items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-slate-100 via-stone-50 to-rose-50 px-5 py-8 sm:px-8 sm:py-10">
            <div aria-hidden="true" className="absolute -right-10 top-8 h-44 w-44 rounded-full bg-rose-100/60 blur-2xl" />
            <PhoneFrame className="relative w-full max-w-[340px]">
            <div className="bg-white p-5 sm:p-6">
                <div aria-hidden="true" className="mx-auto mb-5 h-1 w-8 rounded-full bg-neutral-200" />
                <div className="flex items-center gap-2.5">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand/5 text-brand">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5"><path d="M12 5c-3-2-6-2-9-1v15c3-1 6-1 9 1 3-2 6-2 9-1V4c-3-1-6-1-9 1Zm0 0v15" /></svg>
                    </span>
                    <p className="text-[11px] font-bold uppercase tracking-[0.14em] text-brand">{t('Your quick guide')}</p>
                </div>
                <h4 className="mt-4 text-xl font-bold tracking-tight text-neutral-900">{t('Three simple steps.')}</h4>
                <ol className="mt-5 space-y-4">
                    {steps.map(([label, description], index) => (
                        <li key={label} className="flex items-start gap-3">
                            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-neutral-200 text-[11px] font-semibold text-neutral-600">{index + 1}</span>
                            <div>
                                <p className="text-sm font-semibold text-neutral-900">{label}</p>
                                <p className="mt-0.5 text-xs leading-relaxed text-neutral-500">{description}</p>
                            </div>
                        </li>
                    ))}
                </ol>
                <div className="mt-5 rounded-xl border border-neutral-100 bg-neutral-50 p-3">
                    <p className="mb-2 text-[10px] font-medium text-neutral-500">{t('An example shows you how')}</p>
                    <div aria-hidden="true" className="relative flex h-16 overflow-hidden rounded-lg border border-neutral-200 bg-white">
                        {['C', 'D', 'E', 'F', 'G', 'A', 'B'].map((note) => <span key={note} className="flex flex-1 items-end justify-center border-r border-neutral-200 pb-1.5 text-[9px] font-semibold text-neutral-400 last:border-0">{note}</span>)}
                        {[1, 2, 4, 5, 6].map((after) => <span key={after} style={{ left: `${after * 100 / 7 - 4}%`, width: '8%' }} className={`absolute top-0 flex h-10 items-end justify-center rounded-b pb-1 text-[8px] font-bold ${after === 4 ? 'bg-emerald-600 text-white' : 'bg-neutral-900'}`}>{after === 4 ? 'F♯' : ''}</span>)}
                    </div>
                </div>
            </div>
            </PhoneFrame>
        </figure>
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

// The current instrument list, captured from the updated sound picker.
function SoundCallout() {
    const t = useTrans();

    return (
        <div className={`${calloutClass} top-[120px] p-2.5 sm:top-[130px]`} aria-hidden="true">
            <p className="mb-2 px-2 text-sm font-semibold text-neutral-800">{t('Choose your instrument')}</p>
            <img src="/images/app-sound-options-current.png" alt="" width="700" height="384" className="h-auto w-[260px] rounded-xl sm:w-[300px]" loading="lazy" />
        </div>
    );
}

function ReviewCallout() {
    const t = useTrans();

    return (
        <div className={`${calloutClass} top-9 w-[280px] p-2.5 sm:w-[300px]`} aria-hidden="true">
            <p className="mb-2 px-2 text-sm font-semibold text-neutral-800">{t('Compare your answers')}</p>
            <img src="/images/app-major-extension-comparison.png" alt="" className="h-auto w-full rounded-xl" loading="lazy" />
        </div>
    );
}

function ProgressCallout() {
    const t = useTrans();

    return (
        <div className={`${calloutClass} top-[150px] w-[280px] p-2.5 sm:w-[340px]`} aria-hidden="true">
            <p className="mb-2 px-2 text-sm font-semibold text-neutral-800">{t('Your practice at a glance')}</p>
            <img src="/images/app-progress-summary-current.png" alt="" className="h-auto w-full rounded-xl" loading="lazy" />
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
