export type LandingUser = {
    id?: number;
    nom_usu?: string | null;
    nombre?: string | null;
    email?: string | null;
} | null;

export type LandingPageProps = {
    auth?: {
        user?: LandingUser;
    };
};
