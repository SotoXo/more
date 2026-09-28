import LandingFooter from '@/Components/landing/LandingFooter';
import LandingHeader from '@/Components/landing/LandingHeader';
import type { LandingPageProps } from '@/types/landing';
import { Head, usePage } from '@inertiajs/react';
import type { PropsWithChildren, ReactNode } from 'react';

type StoreLayoutProps = PropsWithChildren<{
    title: string;
    ambience?: ReactNode;
}>;

export default function StoreLayout({ title, ambience, children }: StoreLayoutProps) {
    const page = usePage();
    const user = (page.props as unknown as LandingPageProps).auth?.user ?? null;
    const layoutClassName = ambience
        ? 'more-store-bg relative isolate min-h-screen overflow-x-clip text-white'
        : 'more-store-bg relative min-h-screen overflow-x-hidden text-white';

    return (
        <div className={layoutClassName}>
            <Head title={title} />

            <div className="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
                <div className="absolute inset-x-0 top-0 h-[680px] bg-[radial-gradient(circle_at_18%_12%,rgba(181,122,109,0.18),transparent_30%),linear-gradient(180deg,#0b0f14_0%,#05070a_76%,transparent_100%)]" />
                <div className="absolute inset-x-0 top-28 h-px bg-[linear-gradient(90deg,transparent,rgba(223,184,166,0.2),transparent)]" />
            </div>

            {ambience}

            <LandingHeader user={user} />

            <main className="pb-24 pt-28 sm:pt-32">{children}</main>

            {ambience ? (
                <div className="relative z-30"><LandingFooter user={user} /></div>
            ) : (
                <LandingFooter user={user} />
            )}
        </div>
    );
}
