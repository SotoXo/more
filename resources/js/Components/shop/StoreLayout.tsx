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
                <div className="absolute inset-x-0 top-0 h-[720px] bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.08),transparent_38%),radial-gradient(circle_at_18%_18%,rgba(110,168,255,0.12),transparent_28%),linear-gradient(180deg,#0b1016_0%,#05070b_70%)]" />
                <div className="absolute left-[-10%] top-[10%] h-80 w-80 rounded-full bg-white/6 blur-[120px]" />
                <div className="absolute right-[-14%] top-[28%] h-[30rem] w-[30rem] rounded-full bg-sky-300/8 blur-[150px]" />
                <div className="absolute bottom-[-10%] left-[18%] h-72 w-72 rounded-full bg-stone-300/6 blur-[130px]" />
            </div>

            <LandingHeader user={user} />

            <main className="pb-24 pt-28 sm:pt-32">{children}</main>

            <LandingFooter user={user} />
        </div>
    );
}
