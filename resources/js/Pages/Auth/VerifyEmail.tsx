import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Verificar correo electrónico" />

            <div className="mb-4 text-sm text-[#c5cfdd]">
                Gracias por registrarte. Antes de continuar, verifica tu correo
                electrónico desde el enlace que te enviamos. Si no lo recibiste,
                podemos enviarte otro.
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm font-medium text-emerald-300">
                    Te enviamos un nuevo enlace de verificación.
                </div>
            )}

            <form onSubmit={submit}>
                <div className="mt-4 flex items-center justify-between">
                    <PrimaryButton disabled={processing}>
                        Reenviar verificación
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="rounded-md text-sm text-[#c5cfdd] underline hover:text-[#f8fafc] focus:outline-none focus:ring-2 focus:ring-[#e8c9b8] focus:ring-offset-2 focus:ring-offset-[#12141a]"
                    >
                        Cerrar sesión
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
