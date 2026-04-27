import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminFormSection from '@/Components/Superadmin/SuperadminFormSection';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import { formatAdminDate } from '@/lib/superadmin';
import type { LandingPageProps } from '@/types/landing';
import type { SuperadminFormSection as SuperadminFormSectionData } from '@/types/superadmin';
import { Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

type ProfileProps = {
    profile: {
        nom_usu: string;
        app_usu: string;
        apm_usu?: string | null;
        tel_usu?: string | null;
        email: string;
        rol: string;
        estado: string;
        fec_usu?: string | null;
        nombre_completo: string;
    };
};

type FormValue = string | number | boolean | File | null;

export default function SuperadminProfileEdit({ profile }: ProfileProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;

    const profileForm = useForm<Record<string, FormValue>>({
        nom_usu: profile.nom_usu ?? '',
        app_usu: profile.app_usu ?? '',
        apm_usu: profile.apm_usu ?? '',
        tel_usu: profile.tel_usu ?? '',
        email: profile.email ?? '',
    });

    const passwordForm = useForm<Record<string, string>>({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const profileSections: SuperadminFormSectionData[] = [
        {
            title: 'Datos personales',
            description: 'Datos principales de la cuenta superadmin.',
            fields: [
                {
                    name: 'nom_usu',
                    label: 'Nombre',
                    value: profile.nom_usu ?? '',
                    required: true,
                    placeholder: 'NOMBRE',
                    transform: 'uppercase',
                    hint: 'Se guarda en MAYÚSCULAS. No admite números.',
                },
                {
                    name: 'app_usu',
                    label: 'Apellido paterno',
                    value: profile.app_usu ?? '',
                    required: true,
                    placeholder: 'APELLIDO PATERNO',
                    transform: 'uppercase',
                    hint: 'Se guarda en MAYÚSCULAS. No admite números.',
                },
                {
                    name: 'apm_usu',
                    label: 'Apellido materno',
                    value: profile.apm_usu ?? '',
                    placeholder: 'APELLIDO MATERNO',
                    transform: 'uppercase',
                    hint: 'Se guarda en MAYÚSCULAS. No admite números.',
                },
                {
                    name: 'tel_usu',
                    label: 'Teléfono',
                    value: profile.tel_usu ?? '',
                    type: 'tel',
                    placeholder: '70000000',
                    transform: 'digits',
                    maxLength: 8,
                    hint: 'Debe tener 8 dígitos y comenzar con 6 o 7.',
                },
                {
                    name: 'email',
                    label: 'Correo',
                    value: profile.email ?? '',
                    type: 'email',
                    required: true,
                    placeholder: 'correo@dominio.com',
                    transform: 'lowercase',
                },
            ],
        },
        {
            title: 'Cuenta',
            description: 'Datos de acceso y referencia.',
            fields: [
                {
                    name: 'rol',
                    label: 'Rol',
                    value: profile.rol,
                    disabled: true,
                },
                {
                    name: 'estado',
                    label: 'Estado',
                    value: profile.estado,
                    disabled: true,
                },
                {
                    name: 'fec_usu',
                    label: 'Fecha de alta',
                    value: formatAdminDate(profile.fec_usu),
                    disabled: true,
                },
            ],
        },
    ];

    const passwordSections: SuperadminFormSectionData[] = [
        {
            title: 'Cambio de contraseña',
            description: 'Actualiza la clave de acceso del panel.',
            fields: [
                {
                    name: 'current_password',
                    label: 'Contraseña actual',
                    value: '',
                    type: 'password',
                    required: true,
                },
                {
                    name: 'password',
                    label: 'Nueva contraseña',
                    value: '',
                    type: 'password',
                    required: true,
                    hint: 'Debe incluir mayúscula, minúscula y número.',
                },
                {
                    name: 'password_confirmation',
                    label: 'Confirmar contraseña',
                    value: '',
                    type: 'password',
                    required: true,
                },
            ],
        },
    ];

    const handleProfileSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        profileForm.patch('/superadmin/perfil', {
            preserveScroll: true,
        });
    };

    const handlePasswordSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        passwordForm.put('/superadmin/perfil/password', {
            preserveScroll: true,
            onSuccess: () =>
                passwordForm.reset(
                    'current_password',
                    'password',
                    'password_confirmation',
                ),
        });
    };

    return (
        <SuperadminLayout title="MORE | Mi perfil">
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow="Cuenta"
                    title="Mi perfil"
                    description="Gestiona tus datos de acceso dentro del panel."
                    breadcrumbs={[
                        { label: 'Superadmin', href: '/superadmin' },
                        { label: 'Mi perfil' },
                    ]}
                    actions={[{ label: 'Volver al panel', href: '/superadmin', kind: 'ghost' }]}
                />

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                <section className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
                    <div className="space-y-4">
                        <form onSubmit={handleProfileSubmit} className="space-y-4">
                            {profileSections.map((section) => (
                                <SuperadminFormSection
                                    key={section.title}
                                    section={section}
                                    data={profileForm.data}
                                    errors={profileForm.errors as Record<string, string>}
                                    processing={profileForm.processing}
                                    onChange={(name, value) => profileForm.setData(name, value)}
                                />
                            ))}

                            <div className="sa-panel rounded-[1.6rem] p-5">
                                <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                    <button
                                        type="submit"
                                        disabled={profileForm.processing}
                                        className="sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:cursor-not-allowed disabled:opacity-70"
                                    >
                                        {profileForm.processing ? 'Guardando' : 'Guardar datos'}
                                    </button>
                                </div>
                            </div>
                        </form>

                        <form onSubmit={handlePasswordSubmit} className="space-y-4">
                            {passwordSections.map((section) => (
                                <SuperadminFormSection
                                    key={section.title}
                                    section={section}
                                    data={passwordForm.data}
                                    errors={passwordForm.errors as Record<string, string>}
                                    processing={passwordForm.processing}
                                    onChange={(name, value) => passwordForm.setData(name, String(value ?? ''))}
                                />
                            ))}

                            <div className="sa-panel rounded-[1.6rem] p-5">
                                <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                    <button
                                        type="submit"
                                        disabled={passwordForm.processing}
                                        className="sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:cursor-not-allowed disabled:opacity-70"
                                    >
                                        {passwordForm.processing ? 'Actualizando' : 'Actualizar contraseña'}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <aside className="space-y-4">
                        <div className="sa-panel rounded-[1.6rem] p-6">
                            <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                Cuenta activa
                            </p>
                            <p className="mt-3 text-2xl text-white">
                                {profile.nombre_completo}
                            </p>
                            <div className="mt-5 space-y-3 text-sm text-stone-300">
                                <p>Rol: {profile.rol}</p>
                                <p>Correo: {profile.email}</p>
                                <p>Estado: {profile.estado}</p>
                                <p>Alta: {formatAdminDate(profile.fec_usu)}</p>
                            </div>
                        </div>

                        <div className="sa-panel rounded-[1.6rem] p-6">
                            <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                Sesión
                            </p>
                            <p className="mt-3 text-sm leading-6 text-stone-300">
                                Cierra la sesión actual desde el panel.
                            </p>
                            <div className="mt-5">
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="sa-button-ghost w-full px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]"
                                >
                                    Cerrar sesión
                                </Link>
                            </div>
                        </div>
                    </aside>
                </section>
            </div>
        </SuperadminLayout>
    );
}
