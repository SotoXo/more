import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Iniciar Sesión" />

            <div className="mb-8 text-center">
                <h2 className="text-2xl font-bold text-[#f8fafc] mb-2" style={{ fontFamily: '"Playfair Display", serif', letterSpacing: '0.05em' }}>Bienvenido de Vuelta</h2>
                <p className="text-sm text-[#94a3b8]">Por favor, ingresa tus credenciales.</p>
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-emerald-400">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <InputLabel htmlFor="email" value="Correo Electrónico" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2 text-[#ef4444]" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Contraseña" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2 text-[#ef4444]" />
                </div>

                <div className="block">
                    <label className="flex items-center cursor-pointer">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData(
                                    'remember',
                                    (e.target.checked || false) as false,
                                )
                            }
                            className="bg-[#0d0d12] border-[rgba(255,255,255,0.2)] text-[#818cf8] focus:ring-[#818cf8]"
                        />
                        <span className="ms-3 text-sm text-[#94a3b8]">
                            Mantenme conectado
                        </span>
                    </label>
                </div>

                <div className="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div className="flex flex-col gap-2">
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="text-sm text-[#94a3b8] underline transition duration-300 hover:text-[#f8fafc]"
                            >
                                ¿Olvidaste tu contraseña?
                            </Link>
                        )}
                        <Link
                            href={route('register')}
                            className="text-sm text-[#94a3b8] underline transition duration-300 hover:text-[#f8fafc]"
                        >
                            ¿No tienes una cuenta? Regístrate
                        </Link>
                    </div>

                    <PrimaryButton className="w-full sm:w-auto" disabled={processing}>
                        Iniciar Sesión
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
