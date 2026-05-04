import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div
            className="flex min-h-screen flex-col items-center px-4 py-8 sm:justify-center"
            style={{
                backgroundColor: '#030407',
                color: '#e7e5e4',
                backgroundImage:
                    'radial-gradient(circle at 50% 0%, rgba(181,122,109,0.16), transparent 62%)',
            }}
        >
            <div className="text-center">
                <Link
                    href="/"
                    style={{
                        color: '#f8fafc',
                        fontSize: '1.8rem',
                        fontWeight: 700,
                        letterSpacing: '0.28em',
                        textTransform: 'uppercase',
                        textDecoration: 'none',
                    }}
                >
                    MORE
                </Link>
                <p className="mt-2 text-[0.65rem] uppercase tracking-[0.28em] text-stone-500">
                    Mati Boutique
                </p>
            </div>

            <div
                className="mt-6 w-full overflow-hidden rounded-[1.6rem] px-6 py-8 shadow-[0_24px_70px_rgba(0,0,0,0.34)] sm:max-w-md sm:px-10"
                style={{
                    backgroundColor: 'rgba(8, 10, 14, 0.78)',
                    backdropFilter: 'blur(16px)',
                    WebkitBackdropFilter: 'blur(16px)',
                    border: '1px solid rgba(255,255,255,0.08)',
                }}
            >
                {children}
            </div>
        </div>
    );
}
