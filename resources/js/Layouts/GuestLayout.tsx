import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0" style={{ backgroundColor: '#03040b', color: '#e2e8f0', backgroundImage: 'radial-gradient(circle at 50% 0%, rgba(30, 20, 80, 0.15), transparent 70%)' }}>
            <div>
                <Link href="/" style={{ color: '#f8fafc', fontSize: '2rem', fontWeight: 700, letterSpacing: '0.25em', textTransform: 'uppercase', textDecoration: 'none' }}>
                    MORE
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden px-10 py-8 shadow-[0_20px_40px_rgba(45,30,100,0.15)] sm:max-w-md sm:rounded-xl"
                 style={{ backgroundColor: 'rgba(12, 13, 20, 0.6)', backdropFilter: 'blur(12px)', WebkitBackdropFilter: 'blur(12px)', border: '1px solid rgba(255,255,255,0.05)' }}>
                {children}
            </div>
        </div>
    );
}
