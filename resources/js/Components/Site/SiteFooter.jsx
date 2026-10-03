import { Link } from '@inertiajs/react';

const legalLinks = [
    { label: 'Privacy Policy', href: '/privacy-policy' },
    { label: 'Terms of Use', href: '/terms' },
];

export default function SiteFooter() {
    return (
        <footer className="border-t border-white/5 bg-[#1b1c1e]">
            <div className="mx-auto flex max-w-7xl flex-col items-center gap-3 px-6 py-5 text-sm text-gray-300 sm:flex-row sm:justify-between lg:px-[100px]">
                <p>© {new Date().getFullYear()} KhordQuiz · All Rights Reserved.</p>
                <nav aria-label="Legal" className="flex items-center gap-5">
                    {legalLinks.map((link) => (
                        <Link key={link.href} href={link.href} className="underline-offset-4 transition hover:text-white hover:underline">
                            {link.label}
                        </Link>
                    ))}
                </nav>
            </div>
        </footer>
    );
}
