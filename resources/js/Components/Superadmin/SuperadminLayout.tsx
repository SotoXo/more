import { Head, usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import type { ReactNode } from 'react';
import { useState } from 'react';
import SuperadminSidebar from './SuperadminSidebar';
import SuperadminTopbar from './SuperadminTopbar';

export default function SuperadminLayout({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    const page = usePage();
    const pathname = page.url.split('?')[0];
    const [mobileOpen, setMobileOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(false);

    return (
        <>
            <Head title={title} />
            <div className="sa-shell sa-grid-pattern relative overflow-hidden text-white">
                <div
                    className={
                        collapsed
                            ? 'grid min-h-screen xl:grid-cols-[92px_minmax(0,1fr)]'
                            : 'grid min-h-screen xl:grid-cols-[280px_minmax(0,1fr)]'
                    }
                >
                    <aside className="relative z-40 hidden border-r border-white/8 bg-[#0d141d] xl:block">
                        <SuperadminSidebar
                            currentPath={pathname}
                            collapsed={collapsed}
                            onToggleCollapse={() => setCollapsed((value) => !value)}
                        />
                    </aside>

                    <AnimatePresence>
                        {mobileOpen && (
                            <motion.div
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                exit={{ opacity: 0 }}
                                className="fixed inset-0 z-50 xl:hidden"
                            >
                                <div
                                    className="absolute inset-0 bg-[#05090f]/84 backdrop-blur-sm"
                                    onClick={() => setMobileOpen(false)}
                                />
                                <motion.aside
                                    initial={{ x: '-100%' }}
                                    animate={{ x: 0 }}
                                    exit={{ x: '-100%' }}
                                    transition={{ duration: 0.24, ease: [0.22, 1, 0.36, 1] }}
                                    className="absolute inset-y-0 left-0 w-full max-w-[280px] border-r border-white/8 bg-[#0d141d]"
                                >
                                    <SuperadminSidebar
                                        currentPath={pathname}
                                        collapsed={false}
                                        onNavigate={() => setMobileOpen(false)}
                                    />
                                </motion.aside>
                            </motion.div>
                        )}
                    </AnimatePresence>

                    <div className="relative z-10 min-w-0">
                        <SuperadminTopbar onOpenSidebar={() => setMobileOpen(true)} />
                        <main className="px-4 py-5 sm:px-6 lg:px-8">
                            <div className="mx-auto max-w-[1500px]">{children}</div>
                        </main>
                    </div>
                </div>
            </div>
        </>
    );
}
