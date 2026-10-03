import { LEGAL_CONTACT_EMAIL } from '@/data/legal/helpers';
import { PRIVACY_POLICY } from '@/data/legal/privacyPolicy';
import { TERMS_OF_SERVICE } from '@/data/legal/termsOfService';
import SiteLayout from '@/Layouts/SiteLayout';
import { Head, Link } from '@inertiajs/react';
import { Fragment } from 'react';

const documents = {
    privacy: { document: PRIVACY_POLICY, href: '/privacy-policy' },
    terms: { document: TERMS_OF_SERVICE, href: '/terms' },
};

const sectionId = (index) => `section-${index + 1}`;

export default function Legal({ page }) {
    const { document } = documents[page];

    return (
        <SiteLayout>
            <Head title={document.title} />

            <div className="bg-gradient-to-b from-[#F3F6FB] to-white">
                <div className="mx-auto max-w-7xl px-6 pb-10 pt-14 lg:px-[100px] lg:pt-20">
                    <nav aria-label="Legal documents" className="flex gap-2">
                        {Object.entries(documents).map(([key, item]) => (
                            <Link
                                key={key}
                                href={item.href}
                                className={`rounded-full px-3.5 py-1.5 text-sm font-medium transition ${
                                    key === page
                                        ? 'bg-neutral-900 text-white'
                                        : 'bg-white text-neutral-700 ring-1 ring-inset ring-gray-200 hover:bg-gray-50'
                                }`}
                            >
                                {item.document.title}
                            </Link>
                        ))}
                    </nav>

                    <h1 className="mt-6 text-4xl font-bold tracking-tight text-neutral-900 sm:text-5xl">{document.title}</h1>
                    <p className="mt-3 text-sm text-neutral-500">Effective {document.effectiveDate}</p>
                </div>
            </div>

            <div className="mx-auto grid max-w-7xl gap-12 px-6 pb-20 lg:grid-cols-[260px_1fr] lg:px-[100px]">
                <aside className="hidden lg:block">
                    <nav aria-label="Contents" className="sticky top-[112px] max-h-[calc(100vh-140px)] overflow-y-auto pr-2">
                        <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">Contents</p>
                        <ol className="mt-3 space-y-1.5 text-sm">
                            {document.sections.map((section, index) => (
                                <li key={section.title}>
                                    <a href={`#${sectionId(index)}`} className="text-neutral-600 hover:text-brand">
                                        {section.title}
                                    </a>
                                </li>
                            ))}
                        </ol>
                    </nav>
                </aside>

                <article className="min-w-0 max-w-3xl text-[15px] leading-relaxed text-neutral-700">
                    <div className="space-y-4">
                        {document.intro.map((block, index) => (
                            <Block key={index} block={block} />
                        ))}
                    </div>

                    {document.sections.map((section, index) => (
                        <section key={section.title} id={sectionId(index)} className="scroll-mt-[112px] pt-10">
                            <h2 className="text-xl font-semibold text-neutral-900">{section.title}</h2>
                            <div className="mt-4 space-y-4">
                                {section.blocks.map((block, blockIndex) => (
                                    <Block key={blockIndex} block={block} />
                                ))}
                            </div>
                        </section>
                    ))}

                    <a
                        href={`mailto:${LEGAL_CONTACT_EMAIL}`}
                        className="mt-6 inline-flex items-center gap-3 rounded-xl border border-gray-200 px-5 py-4 font-semibold text-neutral-900 transition hover:border-gray-300 hover:text-brand"
                    >
                        <svg className="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="M3.5 6.5l8.5 6 8.5-6" />
                        </svg>
                        {LEGAL_CONTACT_EMAIL}
                    </a>
                </article>
            </div>
        </SiteLayout>
    );
}

function Block({ block }) {
    if (block.type === 'subheading') {
        return <h3 className="pt-2 text-base font-semibold text-neutral-900">{block.text}</h3>;
    }
    if (block.type === 'list') {
        return (
            <ul className="list-disc space-y-1.5 pl-5 marker:text-neutral-400">
                {block.items.map((item) => (
                    <li key={item}>
                        <WithEmailLinks text={item} />
                    </li>
                ))}
            </ul>
        );
    }
    return (
        <p>
            <WithEmailLinks text={block.text} />
        </p>
    );
}

function WithEmailLinks({ text }) {
    const parts = text.split(LEGAL_CONTACT_EMAIL);
    return parts.map((part, index) => (
        <Fragment key={index}>
            {part}
            {index < parts.length - 1 && (
                <a href={`mailto:${LEGAL_CONTACT_EMAIL}`} className="font-medium text-brand hover:underline">
                    {LEGAL_CONTACT_EMAIL}
                </a>
            )}
        </Fragment>
    ));
}
