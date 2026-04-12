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
        <div className="relative min-h-screen overflow-x-hidden bg-[#05070b] text-white">
            <Head title={title} />

            <div className="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
                <div className="absolute inset-x-0 top-0 h-[760px] bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.08),transparent_36%),radial-gradient(circle_at_18%_16%,rgba(181,122,109,0.16),transparent_28%),linear-gradient(180deg,#0b1016_0%,#05070b_72%)]" />
                <div className="absolute left-[-10%] top-[10%] h-80 w-80 rounded-full bg-white/6 blur-[120px]" />
                <div className="absolute right-[-14%] top-[26%] h-[30rem] w-[30rem] rounded-full bg-[rgba(181,122,109,0.12)] blur-[150px]" />
                <div className="absolute bottom-[-10%] left-[18%] h-72 w-72 rounded-full bg-[rgba(103,63,69,0.14)] blur-[130px]" />
                <div className="absolute inset-x-0 top-28 h-px bg-[linear-gradient(90deg,transparent,rgba(223,184,166,0.18),transparent)]" />
            </div>

            <LandingHeader user={user} />

            <main className="pb-24 pt-28 sm:pt-32">{children}</main>

            <LandingFooter user={user} />
        </div>
    );
}
