import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, Eye, EyeOff } from 'lucide-react';
import { FormEventHandler, useRef, useState } from 'react';

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const passwordRequirements = [
    {
        id: 'length',
        label: 'Mínimo 8 caracteres',
        test: (value: string) => value.length >= 8,
    },
    {
        id: 'uppercase',
        label: 'Al menos una mayúscula',
        test: (value: string) => /[A-Z]/.test(value),
    },
    {
        id: 'lowercase',
        label: 'Al menos una minúscula',
        test: (value: string) => /[a-z]/.test(value),
    },
    {
        id: 'number',
        label: 'Al menos un número',
        test: (value: string) => /[0-9]/.test(value),
    },
];

const passwordMeetsRequirements = (value: string) =>
    passwordRequirements.every((requirement) => requirement.test(value));

export default function Register() {
    const { data, setData, post, processing, errors, reset, setError, clearErrors } = useForm({
        nom_usu: '',
        app_usu: '',
        apm_usu: '',
        tel_usu: '',
        email: '',
        password: '',
        password_confirmation: '',
    });
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);
    const [passwordFocused, setPasswordFocused] = useState(false);
    const emailRef = useRef<{ focus: () => void }>(null);
    const passwordRef = useRef<{ focus: () => void }>(null);
    const passwordConfirmationRef = useRef<{ focus: () => void }>(null);

    const passwordsMatch =
        data.password.length > 0 &&
        data.password_confirmation.length > 0 &&
        data.password === data.password_confirmation;
    const passwordIsValid = passwordMeetsRequirements(data.password);
    const showPasswordRequirements = passwordFocused && !passwordIsValid;
    const passwordDescriptionIds = [
        errors.password ? 'password-error' : null,
        showPasswordRequirements ? 'password-requirements' : null,
        passwordIsValid ? 'password-valid' : null,
    ]
        .filter(Boolean)
        .join(' ') || undefined;

    const handleNameChange = (val: string, field: 'nom_usu' | 'app_usu' | 'apm_usu', maxLen: number) => {
        let cleanVal = val.toUpperCase().replace(/[0-9]/g, '').slice(0, maxLen);
        setData(field, cleanVal);

        if (cleanVal.length > 0 && cleanVal[0] !== cleanVal[0].toUpperCase()) {
            setError(field, 'Debe estar en MAYÚSCULAS y no puede contener números.');
        } else {
            clearErrors(field);
        }
    };

    const handlePhoneChange = (val: string) => {
        let cleanVal = val.replace(/[^0-9]/g, '').slice(0, 8);
        setData('tel_usu', cleanVal);

        if (cleanVal.length > 0 && !/^[67]/.test(cleanVal)) {
            setError('tel_usu', 'El teléfono debe empezar con 6 o 7.');
        } else if (cleanVal.length > 0 && cleanVal.length < 8) {
            setError('tel_usu', 'El teléfono debe tener 8 dígitos.');
        } else {
            clearErrors('tel_usu');
        }
    };

    const handleEmailChange = (val: string) => {
        const cleanVal = val.trim().toLowerCase();
        setData('email', cleanVal);

        if (cleanVal.length > 0 && !emailPattern.test(cleanVal)) {
            setError('email', 'Ingresa un correo electrónico válido.');
        } else {
            clearErrors('email');
        }
    };

    const handlePasswordChange = (val: string) => {
        setData('password', val);

        if (val.length > 0 && !passwordMeetsRequirements(val)) {
            setError('password', 'La contraseña no cumple los requisitos mínimos.');
        } else {
            clearErrors('password');
        }

        if (
            data.password_confirmation.length > 0 &&
            val !== data.password_confirmation
        ) {
            setError('password_confirmation', 'Las contraseñas no coinciden.');
        } else {
            clearErrors('password_confirmation');
        }
    };

    const handlePasswordConfirmationChange = (val: string) => {
        setData('password_confirmation', val);

        if (val.length > 0 && data.password !== val) {
            setError('password_confirmation', 'Las contraseñas no coinciden.');
        } else {
            clearErrors('password_confirmation');
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (!data.email) {
            setError('email', 'Ingresa tu correo electrónico.');
            emailRef.current?.focus();
            return;
        }

        if (!emailPattern.test(data.email)) {
            setError('email', 'Ingresa un correo electrónico válido.');
            emailRef.current?.focus();
            return;
        }

        if (!data.password) {
            setError('password', 'Ingresa tu contraseña.');
            passwordRef.current?.focus();
            return;
        }

        if (!passwordIsValid) {
            setError('password', 'La contraseña no cumple los requisitos mínimos.');
            passwordRef.current?.focus();
            return;
        }

        if (!data.password_confirmation) {
            setError('password_confirmation', 'Confirma tu contraseña.');
            passwordConfirmationRef.current?.focus();
            return;
        }

        if (data.password !== data.password_confirmation) {
            setError('password_confirmation', 'Las contraseñas no coinciden.');
            passwordConfirmationRef.current?.focus();
            return;
        }

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Registrarse" />

            <div className="mb-8 text-center">
                <h2 className="mb-2 text-2xl font-bold text-[#f8fafc]" style={{ fontFamily: '"Playfair Display", serif', letterSpacing: '0.05em' }}>Crea tu cuenta MORE</h2>
                <p className="text-sm text-[#94a3b8]">Guarda favoritos, revisa reservas y compra con más facilidad.</p>
            </div>

            <form onSubmit={submit} className="flex flex-col gap-5" noValidate>
                <div>
                    <InputLabel htmlFor="nom_usu" value="Nombre" />
                    <TextInput
                        id="nom_usu"
                        name="nom_usu"
                        value={data.nom_usu}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused={true}
                        maxLength={50}
                        onChange={(e) => handleNameChange(e.target.value, 'nom_usu', 50)}
                        hasError={Boolean(errors.nom_usu)}
                        aria-invalid={Boolean(errors.nom_usu)}
                        aria-describedby={errors.nom_usu ? 'nom_usu-error' : undefined}
                        required
                    />
                    <InputError id="nom_usu-error" message={errors.nom_usu} className="mt-2 text-[#ef4444]" />
                </div>

                <div>
                    <InputLabel htmlFor="app_usu" value="Apellido paterno" />
                    <TextInput
                        id="app_usu"
                        name="app_usu"
                        value={data.app_usu}
                        className="mt-1 block w-full"
                        maxLength={20}
                        onChange={(e) => handleNameChange(e.target.value, 'app_usu', 20)}
                        hasError={Boolean(errors.app_usu)}
                        aria-invalid={Boolean(errors.app_usu)}
                        aria-describedby={errors.app_usu ? 'app_usu-error' : undefined}
                        required
                    />
                    <InputError id="app_usu-error" message={errors.app_usu} className="mt-2 text-[#ef4444]" />
                </div>

                <div>
                    <InputLabel htmlFor="apm_usu" value="Apellido materno" />
                    <TextInput
                        id="apm_usu"
                        name="apm_usu"
                        value={data.apm_usu}
                        className="mt-1 block w-full"
                        maxLength={20}
                        onChange={(e) => handleNameChange(e.target.value, 'apm_usu', 20)}
                        hasError={Boolean(errors.apm_usu)}
                        aria-invalid={Boolean(errors.apm_usu)}
                        aria-describedby={errors.apm_usu ? 'apm_usu-error' : undefined}
                        required
                    />
                    <InputError id="apm_usu-error" message={errors.apm_usu} className="mt-2 text-[#ef4444]" />
                </div>

                <div>
                    <InputLabel htmlFor="tel_usu" value="Teléfono celular" />
                    <TextInput
                        id="tel_usu"
                        name="tel_usu"
                        type="tel"
                        value={data.tel_usu}
                        className="mt-1 block w-full"
                        maxLength={8}
                        onChange={(e) => handlePhoneChange(e.target.value)}
                        hasError={Boolean(errors.tel_usu)}
                        aria-invalid={Boolean(errors.tel_usu)}
                        aria-describedby={errors.tel_usu ? 'tel_usu-error' : undefined}
                        required
                    />
                    <InputError id="tel_usu-error" message={errors.tel_usu} className="mt-2 text-[#ef4444]" />
                </div>

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
                        onChange={(e) => handleEmailChange(e.target.value)}
                        hasError={Boolean(errors.email)}
                        aria-invalid={Boolean(errors.email)}
                        aria-describedby={errors.email ? 'email-error' : undefined}
                        required
                    />
                    <InputError id="email-error" message={errors.email} className="mt-2 text-[#ef4444]" />
                </div>

                <div>
                    <div className="flex items-center gap-2">
                        <InputLabel htmlFor="password" value="Contraseña" />
                        {passwordIsValid && (
                            <span
                                id="password-valid"
                                className="inline-flex items-center text-emerald-400"
                            >
                                <CheckCircle2 aria-hidden="true" size={17} />
                                <span className="sr-only">
                                    La contraseña cumple los requisitos.
                                </span>
                            </span>
                        )}
                    </div>
                    <div className="relative mt-1">
                        <TextInput
                            id="password"
                            ref={passwordRef}
                            type={showPassword ? 'text' : 'password'}
                            name="password"
                            value={data.password}
                            className="block w-full pr-12"
                            autoComplete="new-password"
                            onChange={(e) => handlePasswordChange(e.target.value)}
                            onFocus={() => setPasswordFocused(true)}
                            onBlur={() => setPasswordFocused(false)}
                            hasError={Boolean(errors.password)}
                            aria-invalid={Boolean(errors.password)}
                            aria-describedby={passwordDescriptionIds}
                            required
                        />
                        <button
                            type="button"
                            className="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-[#94a3b8] transition hover:text-[#f8fafc] focus:outline-none focus:ring-2 focus:ring-[#818cf8] focus:ring-offset-2 focus:ring-offset-[#03040b]"
                            aria-label={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                            aria-pressed={showPassword}
                            onMouseDown={(e) => e.preventDefault()}
                            onClick={() => setShowPassword((show) => !show)}
                        >
                            {showPassword ? (
                                <EyeOff aria-hidden="true" size={18} />
                            ) : (
                                <Eye aria-hidden="true" size={18} />
                            )}
                        </button>
                    </div>
                    <InputError id="password-error" message={errors.password} className="mt-2 text-[#ef4444]" />
                    {showPasswordRequirements && (
                        <ul
                            id="password-requirements"
                            className="mt-3 grid gap-1.5 text-sm"
                        >
                            {passwordRequirements.map((requirement) => {
                                const met = requirement.test(data.password);
                                const touched = data.password.length > 0;

                                return (
                                    <li
                                        key={requirement.id}
                                        className={
                                            'flex items-center gap-2 font-medium ' +
                                            (met
                                                ? 'text-emerald-400'
                                                : touched
                                                  ? 'text-[#ef4444]'
                                                  : 'text-[#94a3b8]')
                                        }
                                    >
                                        {met ? (
                                            <CheckCircle2 aria-hidden="true" size={15} />
                                        ) : (
                                            <span
                                                aria-hidden="true"
                                                className={
                                                    'h-2 w-2 rounded-full ' +
                                                    (touched
                                                        ? 'bg-[#ef4444]'
                                                        : 'bg-[#64748b]')
                                                }
                                            />
                                        )}
                                        {requirement.label}
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </div>

                <div>
                    <InputLabel htmlFor="password_confirmation" value="Confirmar contraseña" />
                    <div className="relative mt-1">
                        <TextInput
                            id="password_confirmation"
                            ref={passwordConfirmationRef}
                            type={showPasswordConfirmation ? 'text' : 'password'}
                            name="password_confirmation"
                            value={data.password_confirmation}
                            className="block w-full pr-12"
                            autoComplete="new-password"
                            onChange={(e) =>
                                handlePasswordConfirmationChange(e.target.value)
                            }
                            hasError={Boolean(errors.password_confirmation)}
                            aria-invalid={Boolean(errors.password_confirmation)}
                            aria-describedby={
                                errors.password_confirmation
                                    ? 'password_confirmation-error'
                                    : passwordsMatch
                                      ? 'password_confirmation-success'
                                      : undefined
                            }
                            required
                        />
                        <button
                            type="button"
                            className="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-[#94a3b8] transition hover:text-[#f8fafc] focus:outline-none focus:ring-2 focus:ring-[#818cf8] focus:ring-offset-2 focus:ring-offset-[#03040b]"
                            aria-label={
                                showPasswordConfirmation
                                    ? 'Ocultar confirmación de contraseña'
                                    : 'Mostrar confirmación de contraseña'
                            }
                            aria-pressed={showPasswordConfirmation}
                            onMouseDown={(e) => e.preventDefault()}
                            onClick={() =>
                                setShowPasswordConfirmation((show) => !show)
                            }
                        >
                            {showPasswordConfirmation ? (
                                <EyeOff aria-hidden="true" size={18} />
                            ) : (
                                <Eye aria-hidden="true" size={18} />
                            )}
                        </button>
                    </div>
                    <InputError
                        id="password_confirmation-error"
                        message={errors.password_confirmation}
                        className="mt-2 text-[#ef4444]"
                    />
                    {passwordsMatch && !errors.password_confirmation && (
                        <p
                            id="password_confirmation-success"
                            className="mt-2 flex items-center gap-2 text-sm font-medium text-emerald-400"
                        >
                            <CheckCircle2 aria-hidden="true" size={16} />
                            Las contraseñas coinciden.
                        </p>
                    )}
                </div>

                <div className="mt-6 flex flex-col items-center justify-between gap-4 sm:flex-row sm:mt-8">
                    <Link
                        href={route('login')}
                        className="text-sm text-[#94a3b8] underline transition duration-300 hover:text-[#f8fafc]"
                    >
                        ¿Ya tienes cuenta? Inicia sesión
                    </Link>

                    <PrimaryButton className="w-full sm:w-auto" disabled={processing}>
                        Crear cuenta
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
