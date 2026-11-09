import { createContext, useContext, useState } from 'react';
import { tk, useTrans } from '@/lib/i18n';
import VideoEmbed from './VideoEmbed';

export const QuizVideoContext = createContext(null);

export function GuideStep({ number, title, children }) {
    return (
        <li className="flex gap-3.5">
            <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-neutral-200 bg-white text-xs font-bold text-neutral-800">{number}</span>
            <div className="pt-0.5">
                <h4 className="text-sm font-bold text-neutral-900">{title}</h4>
                <p className="mt-1 text-sm leading-relaxed text-neutral-500">{children}</p>
            </div>
        </li>
    );
}

export function GuideExampleChoice({ label, value }) {
    return (
        <div className="flex items-center justify-between gap-4 border-b border-neutral-200/70 py-3 last:border-0">
            <span className="text-xs font-medium text-neutral-500">{label}</span>
            <span className="inline-flex min-w-14 items-center justify-center rounded-full bg-neutral-900 px-3.5 py-1.5 text-xs font-semibold text-white">{value}</span>
        </div>
    );
}

export default function QuizQuickGuide({ id, badge, heading, intro, steps, hint, example, about, aboutContent, aboutLabel = tk('About this quiz') }) {
    const t = useTrans();
    const videoEmbed = useContext(QuizVideoContext);
    const [aboutOpen, setAboutOpen] = useState(false);

    return (
        <section aria-labelledby={id} className="mt-5 overflow-hidden rounded-2xl border border-neutral-200">
            <div className="border-b border-neutral-200 bg-white px-5 py-5 sm:px-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.18em] text-brand">
                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.4" className="h-4 w-4">
                            <path d="M3 3.5h4.5A3.5 3.5 0 0110 4.6a3.5 3.5 0 012.5-1.1H17v12h-4.5a3.5 3.5 0 00-2.5 1.1 3.5 3.5 0 00-2.5-1.1H3zM10 4.6v12" />
                        </svg>
                        {t('Your quick guide')}
                    </p>
                    <span className="rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-[10px] font-semibold text-neutral-500">{t(badge)}</span>
                </div>
                <h3 id={id} className="mt-3 text-xl font-bold tracking-tight text-neutral-900 sm:text-2xl">{t(heading)}</h3>
                <p className="mt-1.5 text-sm leading-relaxed text-neutral-500">{t(intro)}</p>
            </div>

            <div className="grid bg-white sm:grid-cols-2">
                <div className="px-5 py-6 sm:px-6">
                    <ol className="space-y-5">
                        {steps.map((step, index) => <GuideStep key={step.title} number={index + 1} title={t(step.title)}>{t(step.description)}</GuideStep>)}
                    </ol>
                    {hint && <p className="mt-5 border-t border-neutral-100 pt-4 text-xs leading-relaxed text-neutral-400">{t(hint)}</p>}
                </div>

                <div className="border-t border-neutral-200 bg-[#F8F6F3] px-5 py-6 sm:border-l sm:border-t-0 sm:px-6">
                    {example}
                </div>
            </div>

            <div className="border-t border-neutral-200 bg-white px-5 py-4 sm:px-6">
                <details className="group" onToggle={(event) => setAboutOpen(event.currentTarget.open)}>
                    <summary className="flex cursor-pointer list-none items-center justify-between gap-3 text-xs font-bold text-neutral-900 focus-visible:rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-red-600 [&::-webkit-details-marker]:hidden">
                        {t(aboutLabel)}
                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" className="h-4 w-4 text-neutral-400 transition-transform group-open:rotate-180"><path d="m5 8 5 5 5-5" /></svg>
                    </summary>
                    <p className="mt-3 text-sm leading-relaxed text-neutral-600">{t(about)}</p>
                    {aboutContent}
                    {videoEmbed ? (
                        <div className="mt-5 border-t border-neutral-100 pt-4">
                            <h4 className="mb-3 text-sm font-semibold text-neutral-900">{t('Video guide')}</h4>
                            {aboutOpen && <VideoEmbed html={videoEmbed} className="aspect-video overflow-hidden rounded-xl bg-neutral-100 [&_iframe]:h-full [&_iframe]:w-full" />}
                        </div>
                    ) : (
                        <div className="mt-5 flex items-center gap-3 rounded-xl border border-dashed border-neutral-200 bg-neutral-50 px-4 py-4">
                            <span aria-hidden="true" className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-neutral-400">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="h-5 w-5"><rect x="3" y="5" width="18" height="14" rx="3" /><path d="m10 9 5 3-5 3z" /></svg>
                            </span>
                            <div>
                                <p className="text-sm font-semibold text-neutral-700">{t('Video guide coming soon')}</p>
                                <p className="mt-1 text-xs leading-relaxed text-neutral-500">{t('You can use the quick guide above to get started.')}</p>
                            </div>
                        </div>
                    )}
                </details>
            </div>


        </section>
    );
}
