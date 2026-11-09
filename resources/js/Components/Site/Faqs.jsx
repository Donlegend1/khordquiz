import Eyebrow from '@/Components/Site/Eyebrow';
import { SUPPORT_EMAIL } from '@/data/contact';
import { tk, useTrans } from '@/lib/i18n';
import { Fragment, useState } from 'react';


// Each answer is a list of paragraphs. Copy supplied by KhordQuiz — keep wording as is.
// Strings are tk() keys, translated at render; :email is filled with SUPPORT_EMAIL.
const groups = [
    {
        name: tk('About KhordQuiz'),
        items: [
            {
                question: tk('What is KhordQuiz?'),
                answer: [
                    tk('KhordQuiz is an ear-training app designed to help musicians and instrumentalists improve their ability to hear and recognize notes, chords, melodic lines, and other musical elements.'),
                ],
            },
            {
                question: tk('Who is KhordQuiz for?'),
                answer: [
                    tk('KhordQuiz is for musicians and instrumentalists of different experience levels who want to develop their musical ear.'),
                ],
            },
            {
                question: tk('What exactly can I train with KhordQuiz?'),
                answer: [
                    tk('You can train your ability to hear and recognize musical elements such as notes, chords, and melodic lines through interactive ear-training exercises.'),
                ],
            },
            {
                question: tk('Do I need to play an instrument?'),
                answer: [
                    tk("No. You don't need to play an instrument to use KhordQuiz. However, ear training can be especially useful alongside instrumental practice."),
                ],
            },
            {
                question: tk('Is KhordQuiz suitable for beginners?'),
                answer: [tk('Yes. KhordQuiz is designed for musicians at different stages, including beginners.')],
            },
            {
                question: tk("I'm a beginner. Is this too advanced for me?"),
                answer: [
                    tk('No. The Beginner path starts with Find the Note and builds from there, through intervals, simple triads and short melodies. Before each quiz you can play its reference audio, and you get instant feedback after every answer.'),
                ],
            },
            {
                question: tk('Is KhordQuiz only for pianists?'),
                answer: [tk('No. KhordQuiz is designed for musicians and instrumentalists generally.')],
            },
            {
                question: tk('How does KhordQuiz work?'),
                answer: [
                    tk('KhordQuiz gives you listening exercises and asks you to identify what you hear. You listen, answer, and continue practicing to develop your ability to recognize musical elements by ear.'),
                ],
            },
            {
                question: tk('What devices does it work on?'),
                answer: [
                    tk('KhordQuiz is an app for iPhone, iPad and Android. Download it from the App Store or Google Play. Light and dark mode included.'),
                ],
            },
            {
                question: tk('Do I need headphones?'),
                answer: [
                    tk('They are not required, but they help. Every question is audio, and headphones let you pick out the details — which is why "use headphones for best results" is one of the tips shown before each quiz.'),
                ],
            },
        ],
    },
    {
        name: tk('Plans & Payments'),
        items: [
            {
                question: tk('How much does KhordQuiz cost?'),
                answer: [
                    tk('KhordQuiz offers monthly, yearly, and lifetime access. Current prices, currencies, and applicable purchase terms are displayed in the app before you complete a purchase.'),
                ],
            },
            {
                question: tk('What is the difference between monthly, yearly, and lifetime?'),
                answer: [
                    tk('The monthly and yearly plans provide access through recurring subscriptions where recurring billing applies. The lifetime plan is a one-time purchase that provides access for the lifetime of the KhordQuiz service.'),
                ],
            },
            {
                question: tk('Is there a free trial?'),
                answer: [
                    tk('Yes. New members get a 7-day free trial with full access when they choose the monthly or yearly plan. Unless cancelled, the chosen plan starts when the trial ends.'),
                    tk('The lifetime plan is a one-time purchase and does not include a trial.'),
                ],
            },
            {
                question: tk('Can I get a refund?'),
                answer: [
                    tk('Payments are generally non-refundable except where required by applicable law or applicable platform policies. KhordQuiz may provide refunds for circumstances such as duplicate charges or technical errors attributable to KhordQuiz that result in an incorrect charge.'),
                    tk("For purchases made through a third-party provider or platform, including Apple, the applicable provider's or platform's refund procedures may also apply."),
                ],
            },
            {
                question: tk('What payment methods are supported?'),
                answer: [
                    tk('KhordQuiz may process payments through Stripe, PayPal, or In-App Purchase, depending on the platform and purchase method available to you.'),
                    tk('Available payment methods may vary depending on your country, currency, platform, and other factors.'),
                ],
            },
            {
                question: tk('Is my payment information safe?'),
                answer: [
                    tk('Payments may be processed by third-party providers or platforms such as Stripe, PayPal, and Apple.'),
                    tk('KhordQuiz does not intend to store your complete payment-card details on its own systems. The applicable payment provider or platform may process payment and transaction information according to its own terms and privacy practices.'),
                ],
            },
        ],
    },
    {
        name: tk('Account & Privacy'),
        items: [
            {
                question: tk('What information does KhordQuiz collect?'),
                answer: [
                    tk('KhordQuiz may collect information such as your name, email address, country or region, IP address, device and technical information, account identifiers, and information about how you use the App.'),
                    tk("If you use Google Sign-In, we may receive information made available through Google's authentication process."),
                    tk("If you use Sign in with Apple, we may receive information made available through Apple's authentication process, which may include an Apple-provided user identifier, your name, and a verified email address. If you choose to hide your email address, Apple may provide KhordQuiz with a private relay email address instead."),
                    tk('For more information, see our Privacy Policy.'),
                ],
            },
            {
                question: tk('Can I delete my account?'),
                answer: [
                    tk('Yes. You can request deletion of your KhordQuiz account by contacting :email.'),
                    tk('Some information may be retained where reasonably necessary or required by applicable law, including for legal compliance, accounting, security, fraud prevention, dispute resolution, or other purposes described in our Privacy Policy.'),
                    tk('Deleting your account does not necessarily cancel a subscription purchased through a third-party payment provider or platform. You should separately cancel any active recurring subscription using the applicable cancellation method.'),
                ],
            },
            {
                question: tk('Can children use KhordQuiz?'),
                answer: [
                    tk('KhordQuiz may be used by users of different ages, including children.'),
                    tk('Where applicable law requires parental or guardian consent before a child may create an account, make a purchase, or have personal information collected or processed, the required authorization must be obtained.'),
                    tk('We handle information relating to children in accordance with applicable privacy and child-protection laws.'),
                ],
            },
            {
                question: tk('Is KhordQuiz available internationally?'),
                answer: [
                    tk('KhordQuiz is intended to be available to users in multiple countries and regions.'),
                    tk('However, availability, payment methods, features, and other aspects of the service may vary depending on your location, applicable laws and regulations, platform requirements, and technical limitations.'),
                ],
            },
        ],
    },
    {
        name: tk('Support'),
        items: [
            {
                question: tk('What happens if I encounter a bug?'),
                answer: [
                    tk('Contact us at :email and tell us what went wrong.'),
                    tk('If possible, include your device, operating system, KhordQuiz app version, and a screenshot or screen recording. This information can help us investigate the issue more efficiently.'),
                ],
            },
            {
                question: tk('How do I contact support?'),
                answer: [
                    tk('Email us at :email.'),
                    tk('Include your KhordQuiz account email and relevant details about your question or issue so we can assist you.'),
                ],
            },
        ],
    },
];

export default function Faqs() {
    const t = useTrans();
    const [active, setActive] = useState(0);
    const group = groups[active];

    return (
        <section id="faqs" className="scroll-mt-[88px] bg-white">
            <div className="mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[320px_1fr] lg:gap-16 lg:px-[100px] lg:py-20">
                <div className="min-w-0 lg:sticky lg:top-[120px] lg:self-start">
                    <Eyebrow>{t('FAQs')}</Eyebrow>

                    <h2 className="mt-5 text-3xl font-bold leading-tight tracking-tight text-neutral-900 sm:text-4xl">
                        {t('Frequently Asked')}
                        <br />
                        <span className="font-display font-semibold tracking-normal text-brand">{t('Questions.')}</span>
                    </h2>

                    <div
                        role="tablist"
                        aria-label={t('FAQ topics')}
                        className="mt-8 flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible"
                    >
                        {groups.map((item, index) => {
                            const selected = index === active;
                            return (
                                <button
                                    key={item.name}
                                    type="button"
                                    role="tab"
                                    aria-selected={selected}
                                    aria-controls="faq-panel"
                                    onClick={() => setActive(index)}
                                    className={`flex shrink-0 items-center justify-between gap-3 rounded-lg px-4 py-2.5 text-left text-sm font-medium transition lg:text-[15px] ${
                                        selected
                                            ? 'bg-neutral-900 text-white'
                                            : 'bg-gray-50 text-neutral-700 ring-1 ring-inset ring-gray-200 hover:bg-gray-100'
                                    }`}
                                >
                                    {t(item.name)}
                                    <span className={`text-xs ${selected ? 'text-white/60' : 'text-neutral-400'}`}>
                                        {item.items.length}
                                    </span>
                                </button>
                            );
                        })}
                    </div>

                    <p className="mt-8 hidden text-sm text-neutral-600 lg:block">
                        {t('Still have a question?')}
                        <br />
                        <a href={`mailto:${SUPPORT_EMAIL}`} className="font-medium text-brand hover:underline">
                            {SUPPORT_EMAIL}
                        </a>
                    </p>
                </div>

                <div id="faq-panel" role="tabpanel" aria-label={t(group.name)} className="flex min-w-0 flex-col gap-3">
                    {group.items.map((item, index) => (
                        <FaqItem key={item.question} {...item} defaultOpen={index === 0} />
                    ))}
                </div>
            </div>
        </section>
    );
}

function FaqItem({ question, answer, defaultOpen }) {
    const t = useTrans();
    const [open, setOpen] = useState(defaultOpen);

    return (
        <div className="rounded-lg border border-gray-200 bg-white transition hover:border-gray-300">
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                aria-expanded={open}
                className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
            >
                <span className="text-[15px] font-medium text-neutral-900">{t(question)}</span>
                <svg
                    className={`h-5 w-5 shrink-0 text-neutral-700 transition-transform ${open ? 'rotate-180' : ''}`}
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    aria-hidden="true"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 7.5l5 5 5-5" />
                </svg>
            </button>

            {open && (
                <div className="-mt-1 space-y-2.5 px-5 pb-5 text-sm leading-relaxed text-neutral-700">
                    {answer.map((paragraph) => (
                        <p key={paragraph}>
                            <WithEmailLinks text={t(paragraph, { email: SUPPORT_EMAIL })} />
                        </p>
                    ))}
                </div>
            )}
        </div>
    );
}

// Turns the support address and "Privacy Policy" (as translated) inside an answer into links.
function WithEmailLinks({ text }) {
    const t = useTrans();
    const inlineLinks = {
        [SUPPORT_EMAIL]: `mailto:${SUPPORT_EMAIL}`,
        [t('Privacy Policy')]: '/privacy-policy',
    };
    const escape = (value) => value.replace(/[.*+?^$()|[\]\\{}]/g, '\\$&');
    const pattern = new RegExp('(' + Object.keys(inlineLinks).map(escape).join('|') + ')');
    return text.split(pattern).map((part, index) =>
        inlineLinks[part] ? (
            <a key={index} href={inlineLinks[part]} className="font-medium text-brand hover:underline">
                {part}
            </a>
        ) : (
            <Fragment key={index}>{part}</Fragment>
        ),
    );
}
