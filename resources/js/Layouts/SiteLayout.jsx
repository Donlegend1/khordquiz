import SiteFooter from '@/Components/Site/SiteFooter';
import SiteHeader from '@/Components/Site/SiteHeader';

export default function SiteLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col bg-white font-sans text-gray-900 antialiased">
            <SiteHeader />
            <main className="flex-1">{children}</main>
            <SiteFooter />
        </div>
    );
}
