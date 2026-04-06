import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Register() {
    const { data, setData, post, processing, errors, reset, setError, clearErrors } = useForm({
        nombre: '',
        apellido_paterno: '',
        apellido_materno: '',
        telefono: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const handleNameChange = (val: string, field: 'nombre' | 'apellido_paterno' | 'apellido_materno', maxLen: number) => {
        // Prevent numbers
        let cleanVal = val.replace(/[0-9]/g, '').slice(0, maxLen);
        setData(field, cleanVal);

        if (cleanVal.length > 0 && cleanVal[0] !== cleanVal[0].toUpperCase()) {
            setError(field, 'Debe iniciar con mayúscula');
        } else {
            clearErrors(field);
        }
    };

    const handlePhoneChange = (val: string) => {
        // Prevent letters, only numbers, max 8
        let cleanVal = val.replace(/[^0-9]/g, '').slice(0, 8);
        setData('telefono', cleanVal);

        if (cleanVal.length > 0 && !/^[67]/.test(cleanVal)) {
            setError('telefono', 'Debe empezar con 6 o 7');
        } else if (cleanVal.length > 0 && cleanVal.length < 8) {
            setError('telefono', 'Debe tener 8 dígitos');
        } else {
            clearErrors('telefono');
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Registrarse" />

            <div className="mb-8 text-center">
                <h2 className="text-2xl font-bold text-[#f8fafc] mb-2" style={{ fontFamily: '"Playfair Display", serif', letterSpacing: '0.05em' }}>Crea tu Cuenta</h2>
                <p className="text-sm text-[#94a3b8]">Únete para disfrutar de la experiencia completa.</p>
            </div>

            <form onSubmit={submit} className="flex flex-col gap-5">
                <div>
                    <InputLabel htmlFor="nombre" value="Nombre" />
                    <TextInput
                        id="nombre"
                        name="nombre"
                        value={data.nombre}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused={true}
                        maxLength={50}
                        onChange={(e) => handleNameChange(e.target.value, 'nombre', 50)}
                        required
                    />
                    <InputError message={errors.nombre} className="mt-2 text-[#ef4444]" />
                </div>

                <div>
                    <InputLabel htmlFor="apellido_paterno" value="Apellido Paterno" />
                    <TextInput
                        id="apellido_paterno"
                        name="apellido_paterno"
                        value={data.apellido_paterno}
                        className="mt-1 block w-full"
                        maxLength={20}
                        onChange={(e) => handleNameChange(e.target.value, 'apellido_paterno', 20)}
                        required
                    />
                    <InputError message={errors.apellido_paterno} className="mt-2 text-[#ef4444]" />
                </div>

                <div>
                    <InputLabel htmlFor="apellido_materno" value="Apellido Materno" />
                    <TextInput
                        id="apellido_materno"
                        name="apellido_materno"
                        value={data.apellido_materno}
                        className="mt-1 block w-full"
                        maxLength={20}
                        onChange={(e) => handleNameChange(e.target.value, 'apellido_materno', 20)}
                        required
                    />
                    <InputError message={errors.apellido_materno} className="mt-2 text-[#ef4444]" />
                </div>

                <div>
                    <InputLabel htmlFor="telefono" value="Teléfono" />
                    <TextInput
                        id="telefono"
                        name="telefono"
                        type="tel"
                        value={data.telefono}
                        className="mt-1 block w-full"
                        maxLength={8}
                        onChange={(e) => handlePhoneChange(e.target.value)}
                        required
                    />
                    <InputError message={errors.telefono} className="mt-2 text-[#ef4444]" />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Correo Electrónico" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                        required
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
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />
                    <InputError message={errors.password} className="mt-2 text-[#ef4444]" />
                </div>

                <div>
                    <InputLabel htmlFor="password_confirmation" value="Confirmar Contraseña" />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        required
                    />
                    <InputError message={errors.password_confirmation} className="mt-2 text-[#ef4444]" />
                </div>

                <div className="mt-6 flex flex-col items-center justify-between gap-4 sm:flex-row sm:mt-8">
                    <Link
                        href={route('login')}
                        className="text-sm text-[#94a3b8] underline transition duration-300 hover:text-[#f8fafc]"
                    >
                        ¿Ya tienes cuenta? Inicia sesión
                    </Link>

                    <PrimaryButton className="w-full sm:w-auto" disabled={processing}>
                        Registrarse
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
