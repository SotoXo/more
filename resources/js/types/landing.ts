import type { PageProps as InertiaPageProps } from '@inertiajs/core';

export type LandingUser = {
    id?: number;
    nom_usu?: string | null;
    app_usu?: string | null;
    apm_usu?: string | null;
    nombre?: string | null;
    email?: string | null;
    tel_usu?: string | null;
} | null;

export type LandingRole = {
    id?: number;
    nombre?: string | null;
} | null;

export type LandingPageProps = InertiaPageProps & {
    auth: {
        user: LandingUser;
        rol: LandingRole;
        is_superadmin: boolean;
    };
    flash: {
        success?: string | null;
        error?: string | null;
    };
};
