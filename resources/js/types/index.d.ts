export interface User {
    id: number;
    nom_usu: string;
    app_usu: string | null;
    apm_usu: string | null;
    tel_usu: string | null;
    email: string;
    email_verified_at?: string;
    cod_roles_usu?: number;
    // legacy / computed
    name?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};

// ─── Cart item (session-based) ──────────────────────────────
export interface CartItem {
    variante_id: number;
    prenda_id: number;
    cantidad: number;
    nombre: string;
    marca: string;
    imagen: string;
    talla: string;
    color: string;
    precio: number;
}

// ─── Order (venta) ──────────────────────────────────────────
export interface PedidoItem {
    id: number;
    cantidad: number;
    precio: number;
    subtotal: number;
    nombre: string;
    marca: string;
    imagen: string;
    talla: string;
    color: string;
    slug: string;
}

export interface Pedido {
    id: number;
    numero: string;
    estado: string;
    subtotal: number;
    descuento: number;
    total: number;
    pago: string | null;
    fecha: string | null;
    items: PedidoItem[];
}

// ─── Reservation (reserva) ──────────────────────────────────
export interface ReservaItem {
    id: number;
    cantidad: number;
    nombre: string;
    marca: string;
    imagen: string;
    talla: string;
    color: string;
    slug: string;
}

export interface Reserva {
    id: number;
    numero: string;
    estado: string;
    vence: string | null;
    fecha: string | null;
    obs: string | null;
    items: ReservaItem[];
}
