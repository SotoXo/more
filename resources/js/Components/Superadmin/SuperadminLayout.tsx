import { Head, usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import SuperadminSidebar from './SuperadminSidebar';
import SuperadminTopbar from './SuperadminTopbar';
import type { AdminTheme } from './ThemeToggle';

const themeStorageKey = 'mati-admin-theme';

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
    const [theme, setTheme] = useState<AdminTheme>('dark');

    useEffect(() => {
        const storedTheme = window.localStorage.getItem(themeStorageKey);
        if (storedTheme === 'light' || storedTheme === 'dark') {
            setTheme(storedTheme);
        }
    }, []);

    const toggleTheme = () => {
        setTheme((current) => {
            const next = current === 'dark' ? 'light' : 'dark';
            window.localStorage.setItem(themeStorageKey, next);
            return next;
        });
    };

    return (
        <>
            <Head title={title} />
            <div className="sa-shell sa-grid-pattern relative overflow-hidden" data-admin-theme={theme}>
                <div
                    className={
                        collapsed
                            ? 'grid min-h-screen xl:grid-cols-[92px_minmax(0,1fr)]'
                            : 'grid min-h-screen xl:grid-cols-[280px_minmax(0,1fr)]'
                    }
                >
                    <aside className="sa-sidebar-shell relative z-40 hidden xl:block">
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
                                <div className="sa-mobile-overlay absolute inset-0" onClick={() => setMobileOpen(false)} />
                                <motion.aside
                                    initial={{ x: '-100%' }}
                                    animate={{ x: 0 }}
                                    exit={{ x: '-100%' }}
                                    transition={{ duration: 0.24, ease: [0.22, 1, 0.36, 1] }}
                                    className="sa-sidebar-shell absolute inset-y-0 left-0 w-full max-w-[280px]"
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
                        <SuperadminTopbar
                            theme={theme}
                            onToggleTheme={toggleTheme}
                            onOpenSidebar={() => setMobileOpen(true)}
                        />
                        <main className="px-4 py-5 sm:px-6 lg:px-8">
                            <div className="mx-auto max-w-[1500px]">{children}</div>
                        </main>
                    </div>
                </div>
            </div>
        </>
    );
}
