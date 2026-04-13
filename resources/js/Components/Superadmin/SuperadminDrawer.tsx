import type { ReactNode } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { X } from 'lucide-react';

export default function SuperadminDrawer({
    open,
    title,
    description,
    children,
    onClose,
}: {
    open: boolean;
    title: string;
    description: string;
    children: ReactNode;
    onClose: () => void;
}) {
    return (
        <AnimatePresence>
            {open && (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="fixed inset-0 z-[85]"
                >
                    <div
                        className="absolute inset-0 bg-[#060c13]/72 backdrop-blur-sm"
                        onClick={onClose}
                    />
                    <motion.aside
                        initial={{ x: '100%' }}
                        animate={{ x: 0 }}
                        exit={{ x: '100%' }}
                        transition={{ duration: 0.26, ease: [0.22, 1, 0.36, 1] }}
                        className="sa-panel absolute inset-y-0 right-0 w-full max-w-xl overflow-y-auto rounded-l-[2rem] p-6 sm:p-8"
                    >
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-[0.7rem] uppercase tracking-[0.24em] text-stone-400">
                                    Panel lateral
                                </p>
                                <p className="mt-3 text-3xl leading-none text-white">{title}</p>
                                <p className="mt-4 text-sm leading-7 text-stone-300">
                                    {description}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={onClose}
                                className="sa-button-ghost h-10 w-10 p-0"
                            >
                                <X size={16} />
                            </button>
                        </div>
                        <div className="mt-8">{children}</div>
                    </motion.aside>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
