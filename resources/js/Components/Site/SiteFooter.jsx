import { tk, useTrans } from '@/lib/i18n';
import { Link } from '@inertiajs/react';

const legalLinks = [
    { label: tk('Privacy Policy'), href: '/privacy-policy' },
    { label: tk('Terms of Use'), href: '/terms' },
];

export default function SiteFooter() {
    const t = useTrans();

    return (
        <footer className="border-t border-white/5 bg-[#1b1c1e]">
            <div className="mx-auto flex max-w-7xl flex-col items-center gap-3 px-6 py-5 text-sm text-gray-300 sm:flex-row sm:justify-between lg:px-[100px]">
                <p>{t('© :year KhordQuiz · All Rights Reserved.', { year: new Date().getFullYear() })}</p>
                <nav aria-label={t('Legal')} className="flex items-center gap-5">
                    {legalLinks.map((link) => (
                        <Link key={link.href} href={link.href} className="underline-offset-4 transition hover:text-white hover:underline">
                            {t(link.label)}
                        </Link>
                    ))}
                </nav>
            </div>
        </footer>
    );
}
