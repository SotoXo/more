import { AnimatePresence, motion } from 'framer-motion';
import { CheckCheck } from 'lucide-react';

export default function SuperadminToast({
    open,
    title,
    description,
}: {
    open: boolean;
    title: string;
    description: string;
}) {
    return (
        <AnimatePresence>
            {open && (
                <motion.div
                    initial={{ opacity: 0, y: 20, scale: 0.98 }}
                    animate={{ opacity: 1, y: 0, scale: 1 }}
                    exit={{ opacity: 0, y: 10, scale: 0.98 }}
                    transition={{ duration: 0.22, ease: [0.22, 1, 0.36, 1] }}
                    className="fixed bottom-6 right-6 z-[90] w-[min(92vw,360px)]"
                >
                    <div className="sa-panel rounded-[1.6rem] p-4 shadow-[0_24px_60px_rgba(0,0,0,0.42)]">
                        <div className="flex gap-4">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-[rgba(113,168,148,0.28)] bg-[rgba(113,168,148,0.12)] text-[rgb(205,241,226)]">
                                <CheckCheck size={16} />
                            </div>
                            <div>
                                <p className="text-sm text-white">{title}</p>
                                <p className="mt-1 text-sm leading-6 text-stone-300">
                                    {description}
                                </p>
                            </div>
                        </div>
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
