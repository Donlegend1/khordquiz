import { useState } from 'react';

const navLinks = [
    { label: 'Features', href: '/#features' },
    { label: 'How it works', href: '/#how-it-works' },
    { label: 'Categories', href: '/#categories' },
    { label: 'FAQs', href: '/#faqs' },
    { label: 'Contact', href: '/#contact' },
];

export default function SiteHeader() {
    const [menuOpen, setMenuOpen] = useState(false);

    return (
        <header className="sticky top-0 z-50 border-b border-gray-100 bg-white">
            <div className="mx-auto flex h-[88px] max-w-7xl items-center justify-between px-6 lg:px-[100px]">
                <a href="/" className="flex items-baseline text-[28px] leading-none tracking-tight">
                    <span className="font-sans font-bold text-black">Khord</span>
                    <span className="font-serif font-semibold text-brand">Quiz</span>
                </a>

                <nav className="hidden items-center gap-10 md:flex">
                    {navLinks.map((link) => (
                        <a
                            key={link.href}
                            href={link.href}
                            className="text-base text-gray-900 transition hover:text-brand"
                        >
                            {link.label}
                        </a>
                    ))}
                </nav>

                <a
                    href="/#download"
                    className="hidden rounded-lg bg-brand px-4 py-2.5 text-base font-medium text-white transition hover:bg-brand-dark md:inline-block"
                >
                    Download App Now
                </a>

                <button
                    type="button"
                    onClick={() => setMenuOpen((open) => !open)}
                    className="inline-flex items-center justify-center rounded-md p-2 text-gray-900 md:hidden"
                    aria-label="Toggle menu"
                    aria-expanded={menuOpen}
                >
                    <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {menuOpen ? (
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        ) : (
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                        )}
                    </svg>
                </button>
            </div>

            {menuOpen && (
                <div className="border-t border-gray-100 px-6 pb-6 md:hidden">
                    <nav className="flex flex-col py-2">
                        {navLinks.map((link) => (
                            <a
                                key={link.href}
                                href={link.href}
                                onClick={() => setMenuOpen(false)}
                                className="py-3 text-base text-gray-900 hover:text-brand"
                            >
                                {link.label}
                            </a>
                        ))}
                    </nav>
                    <a
                        href="/#download"
                        onClick={() => setMenuOpen(false)}
                        className="block rounded-lg bg-brand px-4 py-3 text-center text-base font-medium text-white hover:bg-brand-dark"
                    >
                        Download App Now
                    </a>
                </div>
            )}
        </header>
    );
}
