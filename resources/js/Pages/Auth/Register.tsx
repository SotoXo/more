import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, Eye, EyeOff } from 'lucide-react';
import { FormEventHandler, useEffect, useRef, useState } from 'react';

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const phonePattern = /^[67]\d{7}$/;
const authInputClass =
    'bg-[#151820] text-[#f8fafc] placeholder:text-[#a8b3c7] ' +
    'border-[rgba(255,255,255,0.22)] focus:border-[#e8c9b8] focus:ring-[#e8c9b8]';
const authLabelClass = 'text-[#d5dde9]';
const helpTextClass = 'mt-1.5 text-xs leading-5 text-[#aeb9ca]';
const passwordToggleClass =
    'absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-md ' +
    'text-[#d5dde9] transition hover:bg-white/5 hover:text-white ' +
    'focus:outline-none focus:ring-2 focus:ring-[#e8c9b8] focus:ring-offset-2 focus:ring-offset-[#12141a]';

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

type RegisterField =
    | 'nom_usu'
    | 'app_usu'
    | 'apm_usu'
    | 'tel_usu'
    | 'email'
    | 'password'
    | 'password_confirmation';

type RegisterErrors = Partial<Record<RegisterField, string>>;
type FocusableRef = { focus: () => void };

const describedBy = (...ids: Array<string | false | null | undefined>) =>
    ids.filter(Boolean).join(' ') || undefined;

export default function Register() {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
        setError,
        clearErrors,
    } = useForm({
        nom_usu: '',
        app_usu: '',
        apm_usu: '',
        tel_usu: '',
        email: '',
        password: '',
        password_confirmation: '',
    });
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] =
        useState(false);
    const [passwordFocused, setPasswordFocused] = useState(false);
    const nameRef = useRef<FocusableRef>(null);
    const paternalLastNameRef = useRef<FocusableRef>(null);
    const maternalLastNameRef = useRef<FocusableRef>(null);
    const phoneRef = useRef<FocusableRef>(null);
    const emailRef = useRef<FocusableRef>(null);
    const passwordRef = useRef<FocusableRef>(null);
    const passwordConfirmationRef = useRef<FocusableRef>(null);

    const fieldRefs: Record<
        RegisterField,
        React.RefObject<FocusableRef>
    > = {
        nom_usu: nameRef,
        app_usu: paternalLastNameRef,
        apm_usu: maternalLastNameRef,
        tel_usu: phoneRef,
        email: emailRef,
        password: passwordRef,
        password_confirmation: passwordConfirmationRef,
    };

    const passwordsMatch =
        data.password.length > 0 &&
        data.password_confirmation.length > 0 &&
        data.password === data.password_confirmation;
    const passwordIsValid = passwordMeetsRequirements(data.password);
    const showPasswordRequirements =
        (passwordFocused || Boolean(errors.password) || data.password.length > 0) &&
        !passwordIsValid;
    const passwordDescriptionIds = describedBy(
        errors.password && 'password-error',
        'password-help',
        showPasswordRequirements && 'password-requirements',
        passwordIsValid && 'password-valid',
    );

    const focusFirstInvalid = (nextErrors: RegisterErrors) => {
        const firstInvalidField = (
            [
                'nom_usu',
                'app_usu',
                'apm_usu',
                'tel_usu',
                'email',
                'password',
                'password_confirmation',
            ] as RegisterField[]
        ).find((field) => Boolean(nextErrors[field]));

        if (firstInvalidField) {
            fieldRefs[firstInvalidField].current?.focus();
        }
    };

    const applyClientErrors = (nextErrors: RegisterErrors) => {
        clearErrors();

        Object.entries(nextErrors).forEach(([field, message]) => {
            if (message) {
                setError(field as RegisterField, message);
            }
        });
    };

    const validate = (): boolean => {
        const nextErrors: RegisterErrors = {};

        if (!data.nom_usu.trim()) {
            nextErrors.nom_usu = 'Ingresa tu nombre.';
        }

        if (!data.app_usu.trim()) {
            nextErrors.app_usu = 'Ingresa tu apellido paterno.';
        }

        if (!data.apm_usu.trim()) {
            nextErrors.apm_usu = 'Ingresa tu apellido materno.';
        }

        if (!data.tel_usu) {
            nextErrors.tel_usu = 'Ingresa tu teléfono celular.';
        } else if (!phonePattern.test(data.tel_usu)) {
            nextErrors.tel_usu =
                'El teléfono debe tener 8 dígitos y empezar con 6 o 7.';
        }

        if (!data.email) {
            nextErrors.email = 'Ingresa tu correo electrónico.';
        } else if (!emailPattern.test(data.email)) {
            nextErrors.email = 'Ingresa un correo electrónico válido.';
        }

        if (!data.password) {
            nextErrors.password = 'Ingresa tu contraseña.';
        } else if (!passwordIsValid) {
            nextErrors.password =
                'La contraseña no cumple los requisitos mínimos.';
        }

        if (!data.password_confirmation) {
            nextErrors.password_confirmation = 'Confirma tu contraseña.';
        } else if (data.password !== data.password_confirmation) {
            nextErrors.password_confirmation = 'Las contraseñas no coinciden.';
        }

        applyClientErrors(nextErrors);
        focusFirstInvalid(nextErrors);

        return Object.keys(nextErrors).length === 0;
    };

    const handleNameChange = (
        val: string,
        field: 'nom_usu' | 'app_usu' | 'apm_usu',
        maxLen: number,
    ) => {
        const cleanVal = val.normalize('NFC').toUpperCase().replace(/[^\p{Lu}\s]/gu, '').replace(/\s+/g, ' ').trimStart().slice(0, maxLen);

        setData(field, cleanVal);

        if (cleanVal.trim().length > 0) {
            clearErrors(field);
        }
    };

    const handlePhoneChange = (val: string) => {
        const cleanVal = val.replace(/[^0-9]/g, '').slice(0, 8);

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

        if (!validate()) {
            return;
        }

        post(route('register'), {
            onError: (serverErrors) =>
                focusFirstInvalid(serverErrors as RegisterErrors),
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    useEffect(() => {
        focusFirstInvalid(errors as RegisterErrors);
    }, [
        errors.nom_usu,
        errors.app_usu,
        errors.apm_usu,
        errors.tel_usu,
        errors.email,
        errors.password,
        errors.password_confirmation,
    ]);

    return (
        <GuestLayout>
            <Head title="Registrarse" />

            <div className="mb-7 text-center">
                <h2 className="text-2xl font-bold text-[#f8fafc]">
                    Crea tu cuenta
                </h2>
            </div>

            <form onSubmit={submit} className="flex flex-col gap-5" noValidate>
                <div>
                    <InputLabel
                        htmlFor="nom_usu"
                        value="Nombre"
                        className={authLabelClass}
                    />
                    <TextInput
                        id="nom_usu"
                        ref={nameRef}
                        name="nom_usu"
                        onBlur={() => setData('nom_usu', data.nom_usu.trim())}
                        value={data.nom_usu}
                        className={`mt-1 block w-full ${authInputClass}`}
                        autoComplete="given-name"
                        placeholder="Ej. MARÍA"
                        isFocused={true}
                        maxLength={50}
                        onChange={(e) =>
                            handleNameChange(e.target.value, 'nom_usu', 50)
                        }
                        hasError={Boolean(errors.nom_usu)}
                        aria-invalid={Boolean(errors.nom_usu)}
                        aria-describedby={errors.nom_usu ? 'nom_usu-error' : undefined}
                        required
                    />
                    <InputError
                        id="nom_usu-error"
                        message={errors.nom_usu}
                        className="mt-2 text-[#ff6b6b]"
                    />
                </div>

                <div>
                    <InputLabel
                        htmlFor="app_usu"
                        value="Apellido paterno"
                        className={authLabelClass}
                    />
                    <TextInput
                        id="app_usu"
                        ref={paternalLastNameRef}
                        name="app_usu"
                        onBlur={() => setData('app_usu', data.app_usu.trim())}
                        value={data.app_usu}
                        className={`mt-1 block w-full ${authInputClass}`}
                        autoComplete="family-name"
                        placeholder="Ej. PÉREZ"
                        maxLength={20}
                        onChange={(e) =>
                            handleNameChange(e.target.value, 'app_usu', 20)
                        }
                        hasError={Boolean(errors.app_usu)}
                        aria-invalid={Boolean(errors.app_usu)}
                        aria-describedby={errors.app_usu ? 'app_usu-error' : undefined}
                        required
                    />
                    <InputError
                        id="app_usu-error"
                        message={errors.app_usu}
                        className="mt-2 text-[#ff6b6b]"
                    />
                </div>

                <div>
                    <InputLabel
                        htmlFor="apm_usu"
                        value="Apellido materno"
                        className={authLabelClass}
                    />
                    <TextInput
                        id="apm_usu"
                        ref={maternalLastNameRef}
                        name="apm_usu"
                        onBlur={() => setData('apm_usu', data.apm_usu.trim())}
                        value={data.apm_usu}
                        className={`mt-1 block w-full ${authInputClass}`}
                        autoComplete="additional-name"
                        placeholder="Ej. GARCÍA"
                        maxLength={20}
                        onChange={(e) =>
                            handleNameChange(e.target.value, 'apm_usu', 20)
                        }
                        hasError={Boolean(errors.apm_usu)}
                        aria-invalid={Boolean(errors.apm_usu)}
                        aria-describedby={errors.apm_usu ? 'apm_usu-error' : undefined}
                        required
                    />
                    <InputError
                        id="apm_usu-error"
                        message={errors.apm_usu}
                        className="mt-2 text-[#ff6b6b]"
                    />
                </div>

                <div>
                    <InputLabel
                        htmlFor="tel_usu"
                        value="Teléfono celular"
                        className={authLabelClass}
                    />
                    <TextInput
                        id="tel_usu"
                        ref={phoneRef}
                        name="tel_usu"
                        type="tel"
                        value={data.tel_usu}
                        className={`mt-1 block w-full ${authInputClass}`}
                        autoComplete="tel"
                        inputMode="numeric"
                        placeholder="Ej. 71234567"
                        maxLength={8}
                        onChange={(e) => handlePhoneChange(e.target.value)}
                        hasError={Boolean(errors.tel_usu)}
                        aria-invalid={Boolean(errors.tel_usu)}
                        aria-describedby={describedBy(
                            'tel_usu-help',
                            errors.tel_usu && 'tel_usu-error',
                        )}
                        required
                    />
                    <p id="tel_usu-help" className={helpTextClass}>
                        Debe tener 8 dígitos y empezar con 6 o 7.
                    </p>
                    <InputError
                        id="tel_usu-error"
                        message={errors.tel_usu}
                        className="mt-2 text-[#ff6b6b]"
                    />
                </div>

                <div>
                    <InputLabel
                        htmlFor="email"
                        value="Correo electrónico"
                        className={authLabelClass}
                    />
                    <TextInput
                        id="email"
                        ref={emailRef}
                        type="email"
                        name="email"
                        value={data.email}
                        className={`mt-1 block w-full ${authInputClass}`}
                        autoComplete="username"
                        placeholder="correo@dominio.com"
                        onChange={(e) => handleEmailChange(e.target.value)}
                        hasError={Boolean(errors.email)}
                        aria-invalid={Boolean(errors.email)}
                        aria-describedby={errors.email ? 'email-error' : undefined}
                        required
                    />
                    <InputError
                        id="email-error"
                        message={errors.email}
                        className="mt-2 text-[#ff6b6b]"
                    />
                </div>

                <div>
                    <div className="flex items-center gap-2">
                        <InputLabel
                            htmlFor="password"
                            value="Contraseña"
                            className={authLabelClass}
                        />
                        {passwordIsValid && (
                            <span
                                id="password-valid"
                                className="inline-flex items-center text-emerald-300"
                            >
                                <CheckCircle2
                                    aria-hidden="true"
                                    size={17}
                                    strokeWidth={2.25}
                                />
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
                            className={`block w-full pr-12 ${authInputClass}`}
                            autoComplete="new-password"
                            placeholder="Mínimo 8 caracteres"
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
                            className={passwordToggleClass}
                            aria-label={
                                showPassword
                                    ? 'Ocultar contraseña'
                                    : 'Mostrar contraseña'
                            }
                            aria-pressed={showPassword}
                            onMouseDown={(e) => e.preventDefault()}
                            onClick={() => setShowPassword((show) => !show)}
                        >
                            {showPassword ? (
                                <EyeOff
                                    aria-hidden="true"
                                    size={20}
                                    strokeWidth={2.25}
                                />
                            ) : (
                                <Eye
                                    aria-hidden="true"
                                    size={20}
                                    strokeWidth={2.25}
                                />
                            )}
                        </button>
                    </div>
                    <p id="password-help" className={helpTextClass}>
                        Usa mayúsculas, minúsculas y al menos un número.
                    </p>
                    <InputError
                        id="password-error"
                        message={errors.password}
                        className="mt-2 text-[#ff6b6b]"
                    />
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
                                                ? 'text-emerald-300'
                                                : touched
                                                  ? 'text-[#ff6b6b]'
                                                  : 'text-[#c5cfdd]')
                                        }
                                    >
                                        {met ? (
                                            <CheckCircle2
                                                aria-hidden="true"
                                                size={15}
                                                strokeWidth={2.25}
                                            />
                                        ) : (
                                            <span
                                                aria-hidden="true"
                                                className={
                                                    'h-2 w-2 rounded-full ' +
                                                    (touched
                                                        ? 'bg-[#ff6b6b]'
                                                        : 'bg-[#9aa8bb]')
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
                    <InputLabel
                        htmlFor="password_confirmation"
                        value="Confirmar contraseña"
                        className={authLabelClass}
                    />
                    <div className="relative mt-1">
                        <TextInput
                            id="password_confirmation"
                            ref={passwordConfirmationRef}
                            type={showPasswordConfirmation ? 'text' : 'password'}
                            name="password_confirmation"
                            value={data.password_confirmation}
                            className={`block w-full pr-12 ${authInputClass}`}
                            autoComplete="new-password"
                            placeholder="Repite tu contraseña"
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
                            className={passwordToggleClass}
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
                                <EyeOff
                                    aria-hidden="true"
                                    size={20}
                                    strokeWidth={2.25}
                                />
                            ) : (
                                <Eye
                                    aria-hidden="true"
                                    size={20}
                                    strokeWidth={2.25}
                                />
                            )}
                        </button>
                    </div>
                    <InputError
                        id="password_confirmation-error"
                        message={errors.password_confirmation}
                        className="mt-2 text-[#ff6b6b]"
                    />
                    {passwordsMatch && !errors.password_confirmation && (
                        <p
                            id="password_confirmation-success"
                            className="mt-2 flex items-center gap-2 text-sm font-medium text-emerald-300"
                        >
                            <CheckCircle2
                                aria-hidden="true"
                                size={16}
                                strokeWidth={2.25}
                            />
                            Las contraseñas coinciden.
                        </p>
                    )}
                </div>

                <div className="mt-6 flex flex-col items-center justify-between gap-4 sm:mt-8 sm:flex-row">
                    <Link
                        href={route('login')}
                        className="text-sm text-[#c5cfdd] underline transition duration-300 hover:text-[#f8fafc]"
                    >
                        ¿Ya tienes cuenta? Inicia sesión
                    </Link>

                    <PrimaryButton className="w-full sm:w-auto" disabled={processing}>
                        {processing ? 'Creando cuenta...' : 'Crear cuenta'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
