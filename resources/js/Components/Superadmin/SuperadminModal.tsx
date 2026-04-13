import { AnimatePresence, motion } from 'framer-motion';
import { AlertTriangle } from 'lucide-react';

export default function SuperadminModal({
    open,
    title,
    description,
    confirmLabel,
    onClose,
    onConfirm,
}: {
    open: boolean;
    title: string;
    description: string;
    confirmLabel: string;
    onClose: () => void;
    onConfirm: () => void;
}) {
    return (
        <AnimatePresence>
            {open && (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="fixed inset-0 z-[80] flex items-center justify-center px-4"
                >
                    <div
                        className="absolute inset-0 bg-[#060c13]/84 backdrop-blur-sm"
                        onClick={onClose}
                    />
                    <motion.div
                        initial={{ opacity: 0, y: 16, scale: 0.98 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: 10, scale: 0.98 }}
                        transition={{ duration: 0.22, ease: [0.22, 1, 0.36, 1] }}
                        className="sa-panel relative w-full max-w-lg rounded-[2rem] p-6 sm:p-8"
                    >
                        <div className="flex items-start gap-4">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full border border-[rgba(201,160,106,0.28)] bg-[rgba(201,160,106,0.12)] text-[rgb(244,224,190)]">
                                <AlertTriangle size={18} />
                            </div>
                            <div>
                                <p className="text-[0.7rem] uppercase tracking-[0.24em] text-stone-400">
                                    Confirmación
                                </p>
                                <p className="mt-3 text-3xl leading-none text-white">
                                    {title}
                                </p>
                                <p className="mt-4 text-sm leading-7 text-stone-300">
                                    {description}
                                </p>
                            </div>
                        </div>
                        <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                onClick={onClose}
                                className="sa-button-ghost px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em]"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                onClick={onConfirm}
                                className="sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em]"
                            >
                                {confirmLabel}
                            </button>
                        </div>
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
