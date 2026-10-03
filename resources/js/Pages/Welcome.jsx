import Contact from '@/Components/Site/Contact';
import AllInOne from '@/Components/Site/AllInOne';
import Categories from '@/Components/Site/Categories';
import Faqs from '@/Components/Site/Faqs';
import FinalCta from '@/Components/Site/FinalCta';
import Features from '@/Components/Site/Features';
import Hero from '@/Components/Site/Hero';
import HowItWorks from '@/Components/Site/HowItWorks';
import StoreBadges from '@/Components/Site/StoreBadges';
import SiteLayout from '@/Layouts/SiteLayout';
import { Head } from '@inertiajs/react';

export default function Welcome() {
    return (
        <SiteLayout>
            <Head title="Welcome" />
            <Hero />
            <Features />
            <HowItWorks />
            <Categories />
            <AllInOne />
            <StoreBadges />
            <Faqs />
            <Contact />
            <FinalCta />
        </SiteLayout>
    );
}
