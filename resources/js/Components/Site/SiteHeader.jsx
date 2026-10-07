import BrandLogo from '@/Components/Site/BrandLogo';
import { useEffect, useState } from 'react';

const navLinks = [
    { label: 'Features', href: '/#features' },
    { label: 'How it works', href: '/#how-it-works' },
    { label: 'Categories', href: '/#categories' },
    { label: 'FAQs', href: '/#faqs' },
    { label: 'Contact', href: '/#contact' },
];

export default function SiteHeader() {
    const [menuOpen, setMenuOpen] = useState(false);
    const close = () => setMenuOpen(false);

    // Lock page scroll while the full-screen menu is open.
    useEffect(() => {
        if (!menuOpen) return;
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = previousOverflow;
        };
    }, [menuOpen]);

    // Close on Escape.
    useEffect(() => {
        if (!menuOpen) return;
        const onKeyDown = (event) => event.key === 'Escape' && close();
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [menuOpen]);

    return (
        <header className="sticky top-0 z-50 border-b border-gray-100 bg-white">
            <div className="mx-auto flex h-[88px] max-w-7xl items-center justify-between px-6 lg:px-[100px]">
                <a href="/" aria-label="KhordQuiz home">
                    <BrandLogo />
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

                <div className="hidden items-center gap-3 md:flex">
                    <a
                        href="/login"
                        className="rounded-lg border border-gray-300 px-4 py-2.5 text-base font-medium text-gray-900 transition hover:border-gray-900 hover:bg-gray-50"
                    >
                        Sign in
                    </a>
                    <a
                        href="/#download"
                        className="rounded-lg border border-brand bg-brand px-4 py-2.5 text-base font-medium text-white transition hover:bg-brand-dark"
                    >
                        Start Training
                    </a>
                </div>

                {/* Hamburger, animates into an X. */}
                <button
                    type="button"
                    onClick={() => setMenuOpen((open) => !open)}
                    className="relative z-50 -mr-2.5 inline-flex h-11 w-11 items-center justify-center rounded-md text-gray-900 md:hidden"
                    aria-label={menuOpen ? 'Close menu' : 'Open menu'}
                    aria-expanded={menuOpen}
                    aria-controls="mobile-menu"
                >
                    <span className="flex h-4 w-6 flex-col justify-between">
                        <span
                            className={`h-0.5 w-6 rounded-full bg-gray-900 transition-transform duration-300 ease-out ${menuOpen ? 'translate-y-[7px] rotate-45' : ''}`}
                        />
                        <span
                            className={`h-0.5 w-6 rounded-full bg-gray-900 transition-opacity duration-150 ${menuOpen ? 'opacity-0' : 'opacity-100'}`}
                        />
                        <span
                            className={`h-0.5 w-6 rounded-full bg-gray-900 transition-transform duration-300 ease-out ${menuOpen ? '-translate-y-[7px] -rotate-45' : ''}`}
                        />
                    </span>
                </button>
            </div>

            {/* Full-screen mobile menu */}
            <div
                id="mobile-menu"
                aria-hidden={!menuOpen}
                className={`fixed inset-x-0 top-[88px] bottom-0 z-40 flex flex-col bg-white transition-all duration-300 ease-out md:hidden ${
                    menuOpen ? 'visible translate-y-0 opacity-100' : 'invisible -translate-y-2 opacity-0'
                }`}
                style={{
                    backgroundImage: [
                        'linear-gradient(to right, rgba(17, 24, 39, 0.045) 1px, transparent 1px)',
                        'linear-gradient(to bottom, rgba(17, 24, 39, 0.045) 1px, transparent 1px)',
                    ].join(', '),
                    backgroundSize: '32px 32px',
                }}
            >
                <nav className="flex flex-1 flex-col justify-center gap-0.5 overflow-y-auto px-6">
                    {navLinks.map((link, index) => (
                        <a
                            key={link.href}
                            href={link.href}
                            onClick={close}
                            tabIndex={menuOpen ? 0 : -1}
                            style={{ transitionDelay: menuOpen ? `${90 + index * 50}ms` : '0ms' }}
                            className={`group flex items-center justify-between border-b border-gray-100 py-4 text-2xl font-semibold tracking-tight text-gray-900 transition-all duration-300 ease-out hover:text-brand ${
                                menuOpen ? 'translate-x-0 opacity-100' : 'translate-x-4 opacity-0'
                            }`}
                        >
                            {link.label}
                            <svg
                                className="h-5 w-5 shrink-0 text-gray-300 transition group-hover:translate-x-1 group-hover:text-brand"
                                viewBox="0 0 20 20"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                aria-hidden="true"
                            >
                                <path d="M7.5 5l5 5-5 5" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                        </a>
                    ))}
                </nav>

                <div
                    style={{ transitionDelay: menuOpen ? `${90 + navLinks.length * 50}ms` : '0ms' }}
                    className={`flex shrink-0 flex-col gap-3 border-t border-gray-100 bg-white px-6 py-6 transition-all duration-300 ease-out ${
                        menuOpen ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-0'
                    }`}
                >
                    <a
                        href="/login"
                        onClick={close}
                        tabIndex={menuOpen ? 0 : -1}
                        className="rounded-xl border border-gray-300 px-4 py-3.5 text-center text-base font-medium text-gray-900 transition hover:border-gray-900 hover:bg-gray-50"
                    >
                        Sign in
                    </a>
                    <a
                        href="/#download"
                        onClick={close}
                        tabIndex={menuOpen ? 0 : -1}
                        className="rounded-xl bg-brand px-4 py-3.5 text-center text-base font-medium text-white transition hover:bg-brand-dark"
                    >
                        Start Training
                    </a>
                </div>
            </div>
        </header>
    );
}
