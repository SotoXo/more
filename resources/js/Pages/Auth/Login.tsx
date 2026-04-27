import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Eye, EyeOff } from 'lucide-react';
import {
    FormEventHandler,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';

type LoginErrors = {
    email?: string;
    password?: string;
};

type FlashMessages = {
    success?: string | null;
    error?: string | null;
};

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

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
    const page = usePage();
    const flash = (page.props as unknown as { flash?: FlashMessages }).flash;
    const [clientErrors, setClientErrors] = useState<LoginErrors>({});
    const [showPassword, setShowPassword] = useState(false);
    const emailRef = useRef<{ focus: () => void }>(null);
    const passwordRef = useRef<{ focus: () => void }>(null);

    const serverErrors = errors as Record<string, string | undefined>;
    const emailError = clientErrors.email ?? serverErrors.email;
    const passwordError = clientErrors.password ?? serverErrors.password;
    const globalError = serverErrors.login ?? flash?.error ?? null;
    const successMessage = status ?? flash?.success ?? null;

    const normalizedEmail = useMemo(
        () => data.email.trim().toLowerCase(),
        [data.email],
    );

    const validate = (): boolean => {
        const nextErrors: LoginErrors = {};

        if (!normalizedEmail) {
            nextErrors.email = 'Ingresa tu correo electrónico.';
        } else if (!emailPattern.test(normalizedEmail)) {
            nextErrors.email = 'Ingresa un correo electrónico válido.';
        }

        if (!data.password) {
            nextErrors.password = 'Ingresa tu contraseña.';
        }

        setClientErrors(nextErrors);

        if (nextErrors.email) {
            emailRef.current?.focus();
            return false;
        }

        if (nextErrors.password) {
            passwordRef.current?.focus();
            return false;
        }

        return true;
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (!validate()) {
            return;
        }

        setData('email', normalizedEmail);
        setClientErrors({});

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    useEffect(() => {
        if (serverErrors.email) {
            emailRef.current?.focus();
            return;
        }

        if (serverErrors.password) {
            passwordRef.current?.focus();
        }
    }, [serverErrors.email, serverErrors.password]);

    return (
        <GuestLayout>
            <Head title="Iniciar sesión" />

            <div className="mb-8 text-center">
                <h2 className="mb-2 text-2xl font-bold text-[#f8fafc]">
                    Bienvenido de vuelta
                </h2>
                <p className="text-sm text-[#94a3b8]">
                    Ingresa con tu correo y contraseña para continuar.
                </p>
            </div>

            {successMessage && (
                <div className="mb-4 rounded-md border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm font-medium text-emerald-300">
                    {successMessage}
                </div>
            )}

            {globalError && (
                <div
                    id="login-error"
                    role="alert"
                    className="mb-4 rounded-md border border-[#ef4444]/40 bg-[#ef4444]/10 px-4 py-3 text-sm font-medium text-[#fecaca]"
                >
                    {globalError}
                </div>
            )}

            <form onSubmit={submit} className="space-y-5" noValidate>
                <div>
                    <InputLabel htmlFor="email" value="Correo electrónico" />

                    <TextInput
                        id="email"
                        ref={emailRef}
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        placeholder="correo@dominio.com"
                        isFocused={true}
                        required
                        hasError={Boolean(emailError)}
                        aria-invalid={Boolean(emailError)}
                        aria-describedby={emailError ? 'email-error' : undefined}
                        onChange={(e) => {
                            setData('email', e.target.value.toLowerCase());
                            setClientErrors((current) => ({
                                ...current,
                                email: undefined,
                            }));
                        }}
                        onBlur={() => setData('email', normalizedEmail)}
                    />

                    <InputError
                        id="email-error"
                        message={emailError}
                        className="mt-2 text-[#ef4444]"
                    />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Contraseña" />

                    <div className="relative mt-1">
                        <TextInput
                            id="password"
                            ref={passwordRef}
                            type={showPassword ? 'text' : 'password'}
                            name="password"
                            value={data.password}
                            className="block w-full pr-12"
                            autoComplete="current-password"
                            placeholder="Ingresa tu contraseña"
                            required
                            hasError={Boolean(passwordError)}
                            aria-invalid={Boolean(passwordError)}
                            aria-describedby={
                                passwordError ? 'password-error' : undefined
                            }
                            onChange={(e) => {
                                setData('password', e.target.value);
                                setClientErrors((current) => ({
                                    ...current,
                                    password: undefined,
                                }));
                            }}
                        />

                        <button
                            type="button"
                            className="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-[#94a3b8] transition hover:text-[#f8fafc] focus:outline-none focus:ring-2 focus:ring-[#818cf8] focus:ring-offset-2 focus:ring-offset-[#03040b]"
                            aria-label={
                                showPassword
                                    ? 'Ocultar contraseña'
                                    : 'Mostrar contraseña'
                            }
                            aria-pressed={showPassword}
                            onClick={() => setShowPassword((show) => !show)}
                        >
                            {showPassword ? (
                                <EyeOff aria-hidden="true" size={18} />
                            ) : (
                                <Eye aria-hidden="true" size={18} />
                            )}
                        </button>
                    </div>

                    <InputError
                        id="password-error"
                        message={passwordError}
                        className="mt-2 text-[#ef4444]"
                    />
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
                        {processing ? 'Ingresando...' : 'Iniciar sesión'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
