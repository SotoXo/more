import { ButtonHTMLAttributes } from 'react';

export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center rounded-md border border-transparent bg-white px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#03040b] transition-all duration-300 ease-in-out hover:bg-[#e2e8f0] hover:-translate-y-0.5 hover:shadow-[0_10px_25px_rgba(255,255,255,0.15)] focus:outline-none focus:ring-2 focus:ring-[#818cf8] focus:ring-offset-2 focus:ring-offset-[#03040b] ${
                    disabled && 'opacity-50 cursor-not-allowed transform-none'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
