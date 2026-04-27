import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

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
                <h2 className="text-2xl font-bold text-[#f8fafc] mb-2" style={{ fontFamily: '"Playfair Display", serif', letterSpacing: '0.05em' }}>Crea tu cuenta</h2>
                <p className="text-sm text-[#94a3b8]">Únete para disfrutar de la experiencia completa.</p>
            </div>

            <form onSubmit={submit} className="flex flex-col gap-5">
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
                        required
                    />
                    <InputError message={errors.nom_usu} className="mt-2 text-[#ef4444]" />
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
                        required
                    />
                    <InputError message={errors.app_usu} className="mt-2 text-[#ef4444]" />
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
                        required
                    />
                    <InputError message={errors.apm_usu} className="mt-2 text-[#ef4444]" />
                </div>

                <div>
                    <InputLabel htmlFor="tel_usu" value="Teléfono" />
                    <TextInput
                        id="tel_usu"
                        name="tel_usu"
                        type="tel"
                        value={data.tel_usu}
                        className="mt-1 block w-full"
                        maxLength={8}
                        onChange={(e) => handlePhoneChange(e.target.value)}
                        required
                    />
                    <InputError message={errors.tel_usu} className="mt-2 text-[#ef4444]" />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Correo electrónico" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value.trim().toLowerCase())}
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
                    <InputLabel htmlFor="password_confirmation" value="Confirmar contraseña" />
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
