import LandingFooter from '@/Components/landing/LandingFooter';
import LandingHeader from '@/Components/landing/LandingHeader';
import type { LandingPageProps } from '@/types/landing';
import { Head, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

type StoreLayoutProps = PropsWithChildren<{
    title: string;
}>;

export default function StoreLayout({ title, children }: StoreLayoutProps) {
    const page = usePage();
    const user = (page.props as unknown as LandingPageProps).auth?.user ?? null;

    return (
        <div className="more-store-bg relative min-h-screen overflow-x-hidden text-white">
            <Head title={title} />

            <div className="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
                <div className="absolute inset-x-0 top-0 h-[680px] bg-[radial-gradient(circle_at_18%_12%,rgba(181,122,109,0.18),transparent_30%),linear-gradient(180deg,#0b0f14_0%,#05070a_76%,transparent_100%)]" />
                <div className="absolute inset-x-0 top-28 h-px bg-[linear-gradient(90deg,transparent,rgba(223,184,166,0.2),transparent)]" />
            </div>

            <LandingHeader user={user} />

            <main className="pb-24 pt-28 sm:pt-32">{children}</main>

            <LandingFooter user={user} />
        </div>
    );
}
