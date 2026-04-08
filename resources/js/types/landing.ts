export type LandingUser = {
    id?: number;
    nombre?: string | null;
    email?: string | null;
} | null;

export type LandingPageProps = {
    auth?: {
        user?: LandingUser;
    };
};
