import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div
            className="flex min-h-screen flex-col items-center px-4 py-8 sm:justify-center"
            style={{
                backgroundColor: '#08070a',
                color: '#f8fafc',
                backgroundImage:
                    'radial-gradient(circle at 50% 0%, rgba(217,174,155,0.18), transparent 58%), linear-gradient(180deg, #1a1013 0%, #08070a 46%, #050608 100%)',
            }}
        >
            <div className="mb-2 text-center">
                <Link
                    href="/"
                    className="inline-flex flex-col items-center gap-2"
                    style={{
                        color: '#f8fafc',
                        fontSize: '2rem',
                        fontWeight: 800,
                        letterSpacing: '0.1em',
                        textDecoration: 'none',
                        textShadow: '0 10px 34px rgba(232,201,184,0.24)',
                    }}
                >
                    <span>Mati Boutique</span>
                    <span className="h-px w-16 bg-gradient-to-r from-transparent via-[#e8c9b8]/80 to-transparent" />
                </Link>
            </div>

            <div
                className="mt-7 w-full overflow-hidden rounded-2xl px-6 py-8 shadow-[0_24px_70px_rgba(0,0,0,0.34)] sm:max-w-md sm:px-10"
                style={{
                    backgroundColor: 'rgba(18, 20, 26, 0.92)',
                    backdropFilter: 'blur(16px)',
                    WebkitBackdropFilter: 'blur(16px)',
                    border: '1px solid rgba(255,255,255,0.18)',
                }}
            >
                {children}
            </div>
        </div>
    );
}
