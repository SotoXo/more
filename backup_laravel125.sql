--
-- PostgreSQL database dump
--

\restrict tHsGSemD1ScblSbBNGgyuXzEgNpOJ3wTsPMl6NNDl2AHSg9xZ1Ev8Gl4LjFA7Pf

-- Dumped from database version 18.3
-- Dumped by pg_dump version 18.3

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache OWNER TO sail;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO sail;

--
-- Name: categorias; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.categorias (
    id_categoria bigint NOT NULL,
    nom_cat character varying(100) NOT NULL,
    des_cat character varying(255),
    cod_categoria_padre_cat bigint,
    est_cat boolean DEFAULT true NOT NULL,
    fec_cat timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    tip_tal_cat character varying(20)
);


ALTER TABLE public.categorias OWNER TO sail;

--
-- Name: categorias_id_categoria_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.categorias_id_categoria_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.categorias_id_categoria_seq OWNER TO sail;

--
-- Name: categorias_id_categoria_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.categorias_id_categoria_seq OWNED BY public.categorias.id_categoria;


--
-- Name: colores; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.colores (
    id_color bigint NOT NULL,
    nom_col character varying(50) NOT NULL,
    hex_col character varying(7),
    est_col boolean DEFAULT true NOT NULL,
    fec_col timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    fam_col character varying(30),
    hex_dom_col character(7),
    ord_col integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.colores OWNER TO sail;

--
-- Name: colores_id_color_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.colores_id_color_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.colores_id_color_seq OWNER TO sail;

--
-- Name: colores_id_color_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.colores_id_color_seq OWNED BY public.colores.id_color;


--
-- Name: detalle_reservas; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.detalle_reservas (
    id_detalle_reserva bigint NOT NULL,
    cod_reservas_dre bigint NOT NULL,
    cod_prenda_variantes_dre bigint NOT NULL,
    can_dre integer NOT NULL,
    fec_dre timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.detalle_reservas OWNER TO sail;

--
-- Name: detalle_reservas_id_detalle_reserva_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.detalle_reservas_id_detalle_reserva_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.detalle_reservas_id_detalle_reserva_seq OWNER TO sail;

--
-- Name: detalle_reservas_id_detalle_reserva_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.detalle_reservas_id_detalle_reserva_seq OWNED BY public.detalle_reservas.id_detalle_reserva;


--
-- Name: detalle_ventas; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.detalle_ventas (
    id_detalle_venta bigint NOT NULL,
    cod_ventas_dve bigint NOT NULL,
    cod_prenda_variantes_dve bigint NOT NULL,
    can_dve integer NOT NULL,
    pun_dve numeric(10,2) NOT NULL,
    sub_dve numeric(10,2) NOT NULL,
    fec_dve timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.detalle_ventas OWNER TO sail;

--
-- Name: detalle_ventas_id_detalle_venta_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.detalle_ventas_id_detalle_venta_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.detalle_ventas_id_detalle_venta_seq OWNER TO sail;

--
-- Name: detalle_ventas_id_detalle_venta_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.detalle_ventas_id_detalle_venta_seq OWNED BY public.detalle_ventas.id_detalle_venta;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO sail;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO sail;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: favoritos; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.favoritos (
    id_favorito bigint NOT NULL,
    cod_usuarios_fav bigint NOT NULL,
    cod_prendas_fav bigint NOT NULL,
    fec_fav timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.favoritos OWNER TO sail;

--
-- Name: favoritos_id_favorito_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.favoritos_id_favorito_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.favoritos_id_favorito_seq OWNER TO sail;

--
-- Name: favoritos_id_favorito_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.favoritos_id_favorito_seq OWNED BY public.favoritos.id_favorito;


--
-- Name: imagenes_prenda; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.imagenes_prenda (
    id_imagen_prenda bigint NOT NULL,
    cod_prendas_igp bigint NOT NULL,
    cod_prenda_variantes_igp bigint,
    rut_igp character varying(255) NOT NULL,
    url_igp character varying(500),
    pri_igp boolean DEFAULT false NOT NULL,
    ord_igp integer DEFAULT 0 NOT NULL,
    fue_igp character varying(50),
    fec_igp timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.imagenes_prenda OWNER TO sail;

--
-- Name: imagenes_prenda_id_imagen_prenda_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.imagenes_prenda_id_imagen_prenda_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.imagenes_prenda_id_imagen_prenda_seq OWNER TO sail;

--
-- Name: imagenes_prenda_id_imagen_prenda_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.imagenes_prenda_id_imagen_prenda_seq OWNED BY public.imagenes_prenda.id_imagen_prenda;


--
-- Name: inventarios; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.inventarios (
    id_inventario bigint NOT NULL,
    cod_prenda_variantes_inv bigint NOT NULL,
    cdi_inv integer DEFAULT 0 NOT NULL,
    cre_inv integer DEFAULT 0 NOT NULL,
    ubi_inv character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.inventarios OWNER TO sail;

--
-- Name: inventarios_id_inventario_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.inventarios_id_inventario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.inventarios_id_inventario_seq OWNER TO sail;

--
-- Name: inventarios_id_inventario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.inventarios_id_inventario_seq OWNED BY public.inventarios.id_inventario;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO sail;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO sail;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO sail;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: marcas; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.marcas (
    id_marca bigint NOT NULL,
    nom_mar character varying(100) NOT NULL,
    des_mar character varying(255),
    est_mar boolean DEFAULT true NOT NULL,
    fec_mar timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.marcas OWNER TO sail;

--
-- Name: marcas_id_marca_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.marcas_id_marca_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.marcas_id_marca_seq OWNER TO sail;

--
-- Name: marcas_id_marca_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.marcas_id_marca_seq OWNED BY public.marcas.id_marca;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO sail;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO sail;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO sail;

--
-- Name: prenda_variantes; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.prenda_variantes (
    id_prenda_variante bigint NOT NULL,
    cod_prendas_prv bigint NOT NULL,
    cod_colores_prv bigint NOT NULL,
    cod_tallas_prv bigint NOT NULL,
    sku_prv character varying(50) NOT NULL,
    cob_prv character varying(100),
    pve_prv numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    cos_prv numeric(10,2),
    est_prv boolean DEFAULT true NOT NULL,
    fec_prv timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.prenda_variantes OWNER TO sail;

--
-- Name: prenda_variantes_id_prenda_variante_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.prenda_variantes_id_prenda_variante_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.prenda_variantes_id_prenda_variante_seq OWNER TO sail;

--
-- Name: prenda_variantes_id_prenda_variante_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.prenda_variantes_id_prenda_variante_seq OWNED BY public.prenda_variantes.id_prenda_variante;


--
-- Name: prendas; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.prendas (
    id_prenda bigint NOT NULL,
    nom_pre character varying(150) NOT NULL,
    des_pre text,
    cod_categorias_pre bigint NOT NULL,
    cod_marcas_pre bigint,
    cod_temporadas_pre bigint,
    gen_pre character varying(20),
    prb_pre numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    est_pre boolean DEFAULT true NOT NULL,
    cod_usuario_registra_pre bigint NOT NULL,
    fec_pre timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.prendas OWNER TO sail;

--
-- Name: prendas_id_prenda_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.prendas_id_prenda_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.prendas_id_prenda_seq OWNER TO sail;

--
-- Name: prendas_id_prenda_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.prendas_id_prenda_seq OWNED BY public.prendas.id_prenda;


--
-- Name: reservas; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.reservas (
    id_reserva bigint NOT NULL,
    nre_res character varying(50) NOT NULL,
    cod_usuario_cliente_res bigint NOT NULL,
    cod_usuario_responsable_res bigint NOT NULL,
    est_res character varying(30) DEFAULT 'activa'::character varying NOT NULL,
    ven_res timestamp(0) without time zone,
    obs_res text,
    fec_res timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.reservas OWNER TO sail;

--
-- Name: reservas_id_reserva_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.reservas_id_reserva_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reservas_id_reserva_seq OWNER TO sail;

--
-- Name: reservas_id_reserva_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.reservas_id_reserva_seq OWNED BY public.reservas.id_reserva;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.roles (
    id_rol bigint NOT NULL,
    nom_rol character varying(50) NOT NULL,
    des_rol character varying(255),
    est_rol boolean DEFAULT true NOT NULL,
    fec_rol timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.roles OWNER TO sail;

--
-- Name: roles_id_rol_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.roles_id_rol_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_rol_seq OWNER TO sail;

--
-- Name: roles_id_rol_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.roles_id_rol_seq OWNED BY public.roles.id_rol;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO sail;

--
-- Name: tallas; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.tallas (
    id_talla bigint NOT NULL,
    nom_tal character varying(20) NOT NULL,
    ord_tal integer DEFAULT 0 NOT NULL,
    est_tal boolean DEFAULT true NOT NULL,
    fec_tal timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    sis_tal character varying(20),
    gru_tal character varying(30)
);


ALTER TABLE public.tallas OWNER TO sail;

--
-- Name: tallas_id_talla_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.tallas_id_talla_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tallas_id_talla_seq OWNER TO sail;

--
-- Name: tallas_id_talla_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.tallas_id_talla_seq OWNED BY public.tallas.id_talla;


--
-- Name: temporadas; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.temporadas (
    id_temporada bigint NOT NULL,
    nom_tem character varying(50) NOT NULL,
    des_tem character varying(255),
    est_tem boolean DEFAULT true NOT NULL,
    fec_tem timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    cod_tem character varying(20),
    ord_tem integer DEFAULT 0 NOT NULL,
    fec_ini_tem date,
    fec_fin_tem date
);


ALTER TABLE public.temporadas OWNER TO sail;

--
-- Name: temporadas_id_temporada_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.temporadas_id_temporada_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.temporadas_id_temporada_seq OWNER TO sail;

--
-- Name: temporadas_id_temporada_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.temporadas_id_temporada_seq OWNED BY public.temporadas.id_temporada;


--
-- Name: users; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    nom_usu character varying(100) NOT NULL,
    app_usu character varying(100) NOT NULL,
    apm_usu character varying(100),
    tel_usu character varying(20),
    cod_roles_usu bigint,
    est_usu boolean DEFAULT true NOT NULL,
    fec_usu timestamp(0) without time zone
);


ALTER TABLE public.users OWNER TO sail;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO sail;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: ventas; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.ventas (
    id_venta bigint NOT NULL,
    nve_ven character varying(50) NOT NULL,
    cod_usuario_cliente_ven bigint,
    cod_usuario_vendedor_ven bigint NOT NULL,
    tiv_ven character varying(20) DEFAULT 'fisica'::character varying NOT NULL,
    sub_ven numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    des_ven numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    tot_ven numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    mpa_ven character varying(30),
    est_ven character varying(30) DEFAULT 'registrada'::character varying NOT NULL,
    fec_ven timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.ventas OWNER TO sail;

--
-- Name: ventas_id_venta_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.ventas_id_venta_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ventas_id_venta_seq OWNER TO sail;

--
-- Name: ventas_id_venta_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.ventas_id_venta_seq OWNED BY public.ventas.id_venta;


--
-- Name: categorias id_categoria; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.categorias ALTER COLUMN id_categoria SET DEFAULT nextval('public.categorias_id_categoria_seq'::regclass);


--
-- Name: colores id_color; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.colores ALTER COLUMN id_color SET DEFAULT nextval('public.colores_id_color_seq'::regclass);


--
-- Name: detalle_reservas id_detalle_reserva; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detalle_reservas ALTER COLUMN id_detalle_reserva SET DEFAULT nextval('public.detalle_reservas_id_detalle_reserva_seq'::regclass);


--
-- Name: detalle_ventas id_detalle_venta; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detalle_ventas ALTER COLUMN id_detalle_venta SET DEFAULT nextval('public.detalle_ventas_id_detalle_venta_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: favoritos id_favorito; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.favoritos ALTER COLUMN id_favorito SET DEFAULT nextval('public.favoritos_id_favorito_seq'::regclass);


--
-- Name: imagenes_prenda id_imagen_prenda; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.imagenes_prenda ALTER COLUMN id_imagen_prenda SET DEFAULT nextval('public.imagenes_prenda_id_imagen_prenda_seq'::regclass);


--
-- Name: inventarios id_inventario; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.inventarios ALTER COLUMN id_inventario SET DEFAULT nextval('public.inventarios_id_inventario_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: marcas id_marca; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.marcas ALTER COLUMN id_marca SET DEFAULT nextval('public.marcas_id_marca_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: prenda_variantes id_prenda_variante; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes ALTER COLUMN id_prenda_variante SET DEFAULT nextval('public.prenda_variantes_id_prenda_variante_seq'::regclass);


--
-- Name: prendas id_prenda; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas ALTER COLUMN id_prenda SET DEFAULT nextval('public.prendas_id_prenda_seq'::regclass);


--
-- Name: reservas id_reserva; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reservas ALTER COLUMN id_reserva SET DEFAULT nextval('public.reservas_id_reserva_seq'::regclass);


--
-- Name: roles id_rol; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.roles ALTER COLUMN id_rol SET DEFAULT nextval('public.roles_id_rol_seq'::regclass);


--
-- Name: tallas id_talla; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.tallas ALTER COLUMN id_talla SET DEFAULT nextval('public.tallas_id_talla_seq'::regclass);


--
-- Name: temporadas id_temporada; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.temporadas ALTER COLUMN id_temporada SET DEFAULT nextval('public.temporadas_id_temporada_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: ventas id_venta; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas ALTER COLUMN id_venta SET DEFAULT nextval('public.ventas_id_venta_seq'::regclass);


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.cache (key, value, expiration) FROM stdin;
more-cache-noexiste@example.com|172.21.0.1:timer	i:1777856982;	1777856982
more-cache-noexiste@example.com|172.21.0.1	i:1;	1777856982
more-cache-cliente@more.com|172.19.0.1:timer	i:1778010389;	1778010389
more-cache-cliente@more.com|172.19.0.1	i:1;	1778010389
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: categorias; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.categorias (id_categoria, nom_cat, des_cat, cod_categoria_padre_cat, est_cat, fec_cat, created_at, updated_at, tip_tal_cat) FROM stdin;
1	ABRIGOS	Abrigos, capas y piezas de exterior.	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA
2	BLAZERS	Sastreria ligera y blazers de estructura definida.	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA
3	CAMISAS	Camisas y piezas de botonadura.	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA
4	BLUSAS	Blusas fluidas y piezas de caida suave.	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA
5	VESTIDOS	Vestidos de linea diaria, evento y ocasion.	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA
6	FALDAS	Faldas para look diario, oficina o ocasion.	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA
7	PANTALONES	Pantalones sastreros, rectos y de silueta amplia.	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	NUMERICA
8	POLOS Y KNITWEAR	Punto ligero, knitwear y polos de base.	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA
9	ACCESORIOS	Complementos de styling y piezas de apoyo.	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	UNITALLA
10	CONJUNTOS	Sets coordinados y propuestas armadas.	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA
11	POLERA	\N	\N	t	2026-04-15 04:08:21	2026-04-15 04:08:21	2026-04-15 04:08:21	ALFA
\.


--
-- Data for Name: colores; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.colores (id_color, nom_col, hex_col, est_col, fec_col, created_at, updated_at, fam_col, hex_dom_col, ord_col) FROM stdin;
1	NEGRO	#111111	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	NEGRO	#111111	10
2	BLANCO	#F7F6F2	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	BLANCO	#F7F6F2	20
3	CREMA	#F2E8D8	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	BLANCO	#F7F6F2	30
4	BEIGE	#D8C3A5	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	BEIGE	#D8C3A5	40
5	CAMEL	#B88956	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	CAMEL	#B88956	50
6	GRIS	#8A8F98	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	GRIS	#8A8F98	60
7	AZUL MARINO	#1F2A44	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	AZUL	#1F2A44	70
8	CELESTE	#A9C7E8	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	AZUL	#6F9FD8	80
9	VERDE OLIVA	#66704A	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	VERDE	#66704A	90
10	MARRÓN	#6F4E37	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	MARRON	#6F4E37	100
11	BORGONA	#6F1D2C	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ROJO	#8A2332	110
12	ROSA PALO	#D8A7B1	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ROSA	#D28FA0	120
\.


--
-- Data for Name: detalle_reservas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.detalle_reservas (id_detalle_reserva, cod_reservas_dre, cod_prenda_variantes_dre, can_dre, fec_dre, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: detalle_ventas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.detalle_ventas (id_detalle_venta, cod_ventas_dve, cod_prenda_variantes_dve, can_dve, pun_dve, sub_dve, fec_dve, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: favoritos; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.favoritos (id_favorito, cod_usuarios_fav, cod_prendas_fav, fec_fav, created_at, updated_at) FROM stdin;
1	5	3	2026-04-27 06:28:31	2026-04-27 06:28:31	2026-04-27 06:28:31
3	5	1	2026-04-27 06:28:33	2026-04-27 06:28:33	2026-04-27 06:28:33
4	7	3	2026-05-06 21:55:13	2026-05-06 21:55:13	2026-05-06 21:55:13
5	7	2	2026-05-06 21:55:14	2026-05-06 21:55:14	2026-05-06 21:55:14
\.


--
-- Data for Name: imagenes_prenda; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.imagenes_prenda (id_imagen_prenda, cod_prendas_igp, cod_prenda_variantes_igp, rut_igp, url_igp, pri_igp, ord_igp, fue_igp, fec_igp, created_at, updated_at) FROM stdin;
1	1	\N	productos/1/sobrecamisa-redxfvha.jpg	/storage/productos/1/sobrecamisa-redxfvha.jpg	t	0	catalogo	2026-04-15 01:03:22	2026-04-15 01:03:22	2026-04-15 01:03:22
2	2	\N	productos/2/01011053711-p-6sfp7fjy.jpg	/storage/productos/2/01011053711-p-6sfp7fjy.jpg	t	0	catalogo	2026-04-15 03:42:01	2026-04-15 03:42:01	2026-04-15 03:42:01
3	3	\N	productos/3/02303734805-a4o-timcmgas.jpg	/storage/productos/3/02303734805-a4o-timcmgas.jpg	t	0	catalogo	2026-04-15 04:09:48	2026-04-15 04:09:48	2026-04-15 04:09:48
5	4	\N	productos/4/01011053711-p-qp4xhdmx.jpg	/storage/productos/4/01011053711-p-qp4xhdmx.jpg	t	1	galeria	2026-05-11 02:45:45	2026-05-11 02:45:45	2026-05-11 02:45:51
\.


--
-- Data for Name: inventarios; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.inventarios (id_inventario, cod_prenda_variantes_inv, cdi_inv, cre_inv, ubi_inv, created_at, updated_at) FROM stdin;
1	1	1	0	TIENDA	2026-04-15 01:03:22	2026-04-15 01:03:22
2	2	1	0	TIENDA	2026-04-15 03:42:01	2026-04-15 03:42:01
3	3	10	0	TIENDA	2026-04-15 04:09:48	2026-04-15 04:09:48
4	4	1	0	DEPOSITO	2026-05-05 20:01:45	2026-05-05 20:01:45
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: marcas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.marcas (id_marca, nom_mar, des_mar, est_mar, fec_mar, created_at, updated_at) FROM stdin;
1	ZARA	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14
2	BERSHKA	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14
3	STRADIVARIUS	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14
4	RALPH LAUREN	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14
5	HUGO BOSS	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14
6	MASSIMO DUTTI	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14
7	MANGO	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14
8	PULL&BEAR	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14
9	RESERVED	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14
10	COS	\N	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14
11	PATITO	\N	t	2026-05-05 19:59:54	2026-05-05 19:59:54	2026-05-05 19:59:54
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_04_08_015700_create_roles_table	1
5	2026_04_08_015820_update_users_table_for_mati_boutique	1
6	2026_04_08_020206_create_categorias_table	1
7	2026_04_08_020405_create_marcas_table	1
8	2026_04_08_020443_create_colores_table	1
9	2026_04_08_020514_create_tallas_table	1
10	2026_04_08_020601_create_temporadas_table	1
11	2026_04_08_020642_create_prendas_table	1
12	2026_04_08_020745_create_prenda_variantes_table	1
13	2026_04_08_021433_create_imagenes_prenda_table	1
14	2026_04_08_021516_create_inventarios_table	1
15	2026_04_08_021957_create_ventas_table	1
16	2026_04_08_034318_create_detalle_ventas_table	1
17	2026_04_08_034503_create_reservas_table	1
18	2026_04_08_034539_create_detalle_reservas_table	1
19	2026_04_08_034622_create_favoritos_table	1
20	2026_04_14_222823_add_business_fields_to_colores_table	1
21	2026_04_14_222902_add_business_fields_to_tallas_table	1
22	2026_04_14_222922_add_business_fields_to_temporadas_table	1
23	2026_04_14_222951_add_business_fields_to_categorias_table	1
24	2026_05_11_014318_add_period_fields_to_temporadas_table	2
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: prenda_variantes; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.prenda_variantes (id_prenda_variante, cod_prendas_prv, cod_colores_prv, cod_tallas_prv, sku_prv, cob_prv, pve_prv, cos_prv, est_prv, fec_prv, created_at, updated_at) FROM stdin;
1	1	4	5	POL-00001	\N	700.00	\N	t	2026-04-15 01:03:22	2026-04-15 01:03:22	2026-04-15 01:03:22
2	2	4	11	PAN-00002	\N	300.00	\N	t	2026-04-15 03:42:01	2026-04-15 03:42:01	2026-04-15 03:42:01
3	3	4	5	POL-00003	\N	50.00	\N	t	2026-04-15 04:09:48	2026-04-15 04:09:48	2026-04-15 04:09:48
4	4	5	4	POL-00004	\N	50.00	\N	t	2026-05-05 20:01:45	2026-05-05 20:01:45	2026-05-05 20:01:45
\.


--
-- Data for Name: prendas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.prendas (id_prenda, nom_pre, des_pre, cod_categorias_pre, cod_marcas_pre, cod_temporadas_pre, gen_pre, prb_pre, est_pre, cod_usuario_registra_pre, fec_pre, created_at, updated_at) FROM stdin;
1	SOBRECAMISA REGULAR FIT	SOBRECAMISA REGULAR FIT ZARA	8	1	5	hombre	700.00	t	1	2026-04-15 01:03:22	2026-04-15 01:03:22	2026-04-15 01:03:22
2	PANTALÓN SUPER BAGGY CON LINO	Pantalón super baggy con lino bershka	7	2	5	hombre	300.00	t	1	2026-04-15 03:42:01	2026-04-15 03:42:01	2026-04-15 03:42:01
3	CAMISETA RELAXED FIT PRINT	Camiseta relaxed fit print BERSHKA	11	2	5	hombre	50.00	t	1	2026-04-15 04:09:48	2026-04-15 04:09:48	2026-04-15 04:09:48
4	POLERA	polera marca patito	11	11	5	unisex	50.00	t	1	2026-05-05 20:01:45	2026-05-05 20:01:45	2026-05-05 20:01:45
\.


--
-- Data for Name: reservas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.reservas (id_reserva, nre_res, cod_usuario_cliente_res, cod_usuario_responsable_res, est_res, ven_res, obs_res, fec_res, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.roles (id_rol, nom_rol, des_rol, est_rol, fec_rol, created_at, updated_at) FROM stdin;
1	superadmin	Rol con acceso total al sistema	t	2026-04-14 22:54:13	2026-04-14 22:54:13	2026-04-14 22:54:13
2	vendedor	Rol encargado de ventas y reservas	t	2026-04-14 22:54:13	2026-04-14 22:54:13	2026-04-14 22:54:13
3	cliente	Rol asignado a clientes del sistema	t	2026-04-14 22:54:13	2026-04-14 22:54:13	2026-04-14 22:54:13
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
QSdZ499YVrnpGATTUQiMptydzb3pyY0dLe7mUkV5	\N	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0	eyJfdG9rZW4iOiJxZURMQlR1dmtFSHcyU1BTNFpBQnptOVV1bkpWeU5XSVZQMEFVTTBYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvY2F0YWxvZ28iLCJyb3V0ZSI6InNob3AuY2F0YWxvZyJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19	1778471158
Z5urDK7ewNVTVy5d1qnwfKi8wuE71qcIFBSM8x0c	1	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	eyJfdG9rZW4iOiJuY1dDcTNJZjdKU1ppOVQ0V1VxbkhjUkU3cTlYZ3J1RlV3QzliUkhSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==	1778600833
CSbXU5wYeeiWm1a8LmNABr0oqAZcuE8FViDzZeUy	\N	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Codex/26.506.31421 Chrome/146.0.7680.179 Electron/41.2.0 Safari/537.36	eyJfdG9rZW4iOiJzdHYwWmdUSUNWT09pS3hzQUtSa056WFhPcXViYkZIcGJPZ2JEQ1JZIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19	1778471158
RSmzAG1v2bls1vQNoBTGrS1LVZrg8RzGrFNKbUcf	1	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	eyJfdG9rZW4iOiI2UFZ5eXpSV2twT3I3eW1nd3Q5MVhjR1EwcW1kdW9HYkFBbnhBNnd0IiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvc3VwZXJhZG1pblwvY2F0YWxvZ29cL3ByZW5kYXMiLCJyb3V0ZSI6InN1cGVyYWRtaW4ucmVzb3VyY2VzLmluZGV4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjF9	1778471901
TgwY7cgbOOlrYKlMQIHLOBP5wPVB96XdNfEibn9o	1	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	eyJfdG9rZW4iOiJZRUJxRXp1ODN0WFFMeWNwS1IyMFpCVldDck5wUVRIbEVJaDYzQkZHIiwidXJsIjpbXSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=	1778611632
\.


--
-- Data for Name: tallas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.tallas (id_talla, nom_tal, ord_tal, est_tal, fec_tal, created_at, updated_at, sis_tal, gru_tal) FROM stdin;
1	UNITALLA	10	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	UNICA	GENERAL
2	XS	20	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA	SUPERIOR
3	S	30	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA	SUPERIOR
4	M	40	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA	SUPERIOR
5	L	50	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA	SUPERIOR
6	XL	60	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA	SUPERIOR
7	XXL	70	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	ALFA	SUPERIOR
8	32	132	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	NUMERICA	INFERIOR
9	34	134	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	NUMERICA	INFERIOR
10	36	136	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	NUMERICA	INFERIOR
11	38	138	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	NUMERICA	INFERIOR
12	40	140	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	NUMERICA	INFERIOR
13	42	142	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	NUMERICA	INFERIOR
14	44	144	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	NUMERICA	INFERIOR
15	46	146	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	NUMERICA	INFERIOR
16	48	148	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	NUMERICA	INFERIOR
\.


--
-- Data for Name: temporadas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.temporadas (id_temporada, nom_tem, des_tem, est_tem, fec_tem, created_at, updated_at, cod_tem, ord_tem, fec_ini_tem, fec_fin_tem) FROM stdin;
1	PRIMAVERA-VERANO	Piezas ligeras y clima calido.	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	PV	10	\N	\N
2	OTOÑO-INVIERNO	Abrigo, punto y capas.	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	OI	20	\N	\N
3	RESORT	Viaje, transicion y silueta relajada.	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	RES	30	\N	\N
4	CAPSULE	Edicion corta y curada.	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	CAP	40	\N	\N
5	PERMANENTE	Base continua de fondo de armario.	t	2026-04-14 22:54:14	2026-04-14 22:54:14	2026-04-14 22:54:14	PER	50	\N	\N
6	CARNAVALERA	Carnaval	t	2026-05-05 20:12:53	2026-05-05 20:12:54	2026-05-05 20:12:54	C	60	\N	\N
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.users (id, email, email_verified_at, password, remember_token, created_at, updated_at, nom_usu, app_usu, apm_usu, tel_usu, cod_roles_usu, est_usu, fec_usu) FROM stdin;
1	superadmin@more.com	2026-04-14 22:54:13	$2y$12$2dHUNwGIuff1JUZklQUdB.OvH988ikv4uGZuaSjbzBYsxbcbZ6u96	\N	2026-04-14 22:54:13	2026-04-14 22:54:13	LIMBER	SOTO	CAZU	70000001	1	t	2026-04-14 22:54:13
2	vendedor@more.com	2026-04-14 22:54:13	$2y$12$GRtlFpxNzNOXF/ylPuU0T.Uz9y48GH1BtAvXyp3vtC0s9UNTLtHiu	\N	2026-04-14 22:54:13	2026-04-14 22:54:13	MATI	BOUTIQUE	VENDEDOR	70000002	2	t	2026-04-14 22:54:13
3	cliente@more.com	2026-04-14 22:54:13	$2y$12$VpZr1aU5I28Ri4gWQOoH6etwlbUMa.pBrEsPNJrv7lP8gA/EvTDIy	\N	2026-04-14 22:54:13	2026-04-15 02:11:22	CLIENTE	DEMO	MORE	70000003	3	f	2026-04-14 22:54:13
4	marcelo@gmail.com	\N	$2y$12$OZio1BqxocuDOoAHSiVQ1uUIzur9yuWoBnLGj2Hz/R6Z.IBnG7pd6	\N	2026-04-26 22:07:26	2026-04-26 22:07:26	MARCELO	ESCOBAR	CHIPANA	65489498	3	t	2026-04-26 22:07:26
5	limber.caso123@gmail.com	\N	$2y$12$3WSLcVo8uodJnUBF2EejheTt/PkAQprkWOoVNwzGUG8QeWnJZOVjS	\N	2026-04-27 00:37:03	2026-04-27 00:37:03	LIMBER	SOTO	CAZU	71930583	3	t	2026-04-27 00:37:03
7	vicente@gmail.com	\N	$2y$12$4iFQNEpMbJd3SXgw/w0geOsAZx.C6d.vQgdwzdm9OjvkZ.CyD43Ke	\N	2026-05-06 21:54:40	2026-05-06 21:54:40	VICENTE	CLAROS	MAMANI	64695618	3	t	2026-05-06 21:54:40
8	pepe@gmail.com	\N	$2y$12$ju8X54ghLeWXfCgXkrlSsuzOiaVKJ7.eCwAUvJ83DPLkzBqnkAsHS	\N	2026-05-12 15:45:51	2026-05-12 15:45:51	PEPE	MAMANI	COLQUE	65156191	2	t	2026-05-12 15:45:51
6	carlavaleria@gmail.com	\N	$2y$12$GYf21MIXNwOxlMMhn.W4U./vxs8x4QegzWTw9oMuxB4wg7CdoXcUq	\N	2026-05-05 20:08:43	2026-05-12 15:46:33	CARLA VALERIA	ENCINAS	CANO	75416865	3	t	2026-05-05 20:08:43
\.


--
-- Data for Name: ventas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.ventas (id_venta, nve_ven, cod_usuario_cliente_ven, cod_usuario_vendedor_ven, tiv_ven, sub_ven, des_ven, tot_ven, mpa_ven, est_ven, fec_ven, created_at, updated_at) FROM stdin;
\.


--
-- Name: categorias_id_categoria_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.categorias_id_categoria_seq', 11, true);


--
-- Name: colores_id_color_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.colores_id_color_seq', 12, true);


--
-- Name: detalle_reservas_id_detalle_reserva_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.detalle_reservas_id_detalle_reserva_seq', 1, false);


--
-- Name: detalle_ventas_id_detalle_venta_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.detalle_ventas_id_detalle_venta_seq', 1, false);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: favoritos_id_favorito_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.favoritos_id_favorito_seq', 5, true);


--
-- Name: imagenes_prenda_id_imagen_prenda_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.imagenes_prenda_id_imagen_prenda_seq', 6, true);


--
-- Name: inventarios_id_inventario_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.inventarios_id_inventario_seq', 4, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: marcas_id_marca_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.marcas_id_marca_seq', 11, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.migrations_id_seq', 24, true);


--
-- Name: prenda_variantes_id_prenda_variante_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.prenda_variantes_id_prenda_variante_seq', 4, true);


--
-- Name: prendas_id_prenda_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.prendas_id_prenda_seq', 4, true);


--
-- Name: reservas_id_reserva_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.reservas_id_reserva_seq', 1, false);


--
-- Name: roles_id_rol_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.roles_id_rol_seq', 3, true);


--
-- Name: tallas_id_talla_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.tallas_id_talla_seq', 16, true);


--
-- Name: temporadas_id_temporada_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.temporadas_id_temporada_seq', 6, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.users_id_seq', 8, true);


--
-- Name: ventas_id_venta_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.ventas_id_venta_seq', 1, false);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categorias categorias_nom_cat_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_nom_cat_unique UNIQUE (nom_cat);


--
-- Name: categorias categorias_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_pkey PRIMARY KEY (id_categoria);


--
-- Name: colores colores_nom_col_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.colores
    ADD CONSTRAINT colores_nom_col_unique UNIQUE (nom_col);


--
-- Name: colores colores_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.colores
    ADD CONSTRAINT colores_pkey PRIMARY KEY (id_color);


--
-- Name: detalle_reservas detalle_reservas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detalle_reservas
    ADD CONSTRAINT detalle_reservas_pkey PRIMARY KEY (id_detalle_reserva);


--
-- Name: detalle_ventas detalle_ventas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detalle_ventas
    ADD CONSTRAINT detalle_ventas_pkey PRIMARY KEY (id_detalle_venta);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: favoritos favoritos_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.favoritos
    ADD CONSTRAINT favoritos_pkey PRIMARY KEY (id_favorito);


--
-- Name: imagenes_prenda imagenes_prenda_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.imagenes_prenda
    ADD CONSTRAINT imagenes_prenda_pkey PRIMARY KEY (id_imagen_prenda);


--
-- Name: inventarios inventarios_cod_prenda_variantes_inv_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.inventarios
    ADD CONSTRAINT inventarios_cod_prenda_variantes_inv_unique UNIQUE (cod_prenda_variantes_inv);


--
-- Name: inventarios inventarios_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.inventarios
    ADD CONSTRAINT inventarios_pkey PRIMARY KEY (id_inventario);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: marcas marcas_nom_mar_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.marcas
    ADD CONSTRAINT marcas_nom_mar_unique UNIQUE (nom_mar);


--
-- Name: marcas marcas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.marcas
    ADD CONSTRAINT marcas_pkey PRIMARY KEY (id_marca);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: prenda_variantes prenda_variantes_cob_prv_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT prenda_variantes_cob_prv_unique UNIQUE (cob_prv);


--
-- Name: prenda_variantes prenda_variantes_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT prenda_variantes_pkey PRIMARY KEY (id_prenda_variante);


--
-- Name: prenda_variantes prenda_variantes_sku_prv_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT prenda_variantes_sku_prv_unique UNIQUE (sku_prv);


--
-- Name: prendas prendas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas
    ADD CONSTRAINT prendas_pkey PRIMARY KEY (id_prenda);


--
-- Name: reservas reservas_nre_res_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reservas
    ADD CONSTRAINT reservas_nre_res_unique UNIQUE (nre_res);


--
-- Name: reservas reservas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reservas
    ADD CONSTRAINT reservas_pkey PRIMARY KEY (id_reserva);


--
-- Name: roles roles_nom_rol_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_nom_rol_unique UNIQUE (nom_rol);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id_rol);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: tallas tallas_nom_tal_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.tallas
    ADD CONSTRAINT tallas_nom_tal_unique UNIQUE (nom_tal);


--
-- Name: tallas tallas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.tallas
    ADD CONSTRAINT tallas_pkey PRIMARY KEY (id_talla);


--
-- Name: temporadas temporadas_cod_tem_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.temporadas
    ADD CONSTRAINT temporadas_cod_tem_unique UNIQUE (cod_tem);


--
-- Name: temporadas temporadas_nom_tem_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.temporadas
    ADD CONSTRAINT temporadas_nom_tem_unique UNIQUE (nom_tem);


--
-- Name: temporadas temporadas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.temporadas
    ADD CONSTRAINT temporadas_pkey PRIMARY KEY (id_temporada);


--
-- Name: prenda_variantes uq_prenda_color_talla; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT uq_prenda_color_talla UNIQUE (cod_prendas_prv, cod_colores_prv, cod_tallas_prv);


--
-- Name: favoritos uq_usuario_prenda_favorito; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.favoritos
    ADD CONSTRAINT uq_usuario_prenda_favorito UNIQUE (cod_usuarios_fav, cod_prendas_fav);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: ventas ventas_nve_ven_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_nve_ven_unique UNIQUE (nve_ven);


--
-- Name: ventas ventas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_pkey PRIMARY KEY (id_venta);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: categorias_tip_tal_cat_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX categorias_tip_tal_cat_index ON public.categorias USING btree (tip_tal_cat);


--
-- Name: colores_fam_col_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX colores_fam_col_index ON public.colores USING btree (fam_col);


--
-- Name: colores_ord_col_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX colores_ord_col_index ON public.colores USING btree (ord_col);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: prendas_nom_pre_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX prendas_nom_pre_index ON public.prendas USING btree (nom_pre);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: tallas_gru_tal_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX tallas_gru_tal_index ON public.tallas USING btree (gru_tal);


--
-- Name: tallas_sis_tal_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX tallas_sis_tal_index ON public.tallas USING btree (sis_tal);


--
-- Name: temporadas_fec_ini_tem_fec_fin_tem_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX temporadas_fec_ini_tem_fec_fin_tem_index ON public.temporadas USING btree (fec_ini_tem, fec_fin_tem);


--
-- Name: temporadas_ord_tem_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX temporadas_ord_tem_index ON public.temporadas USING btree (ord_tem);


--
-- Name: users_app_usu_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX users_app_usu_index ON public.users USING btree (app_usu);


--
-- Name: users_cod_roles_usu_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX users_cod_roles_usu_index ON public.users USING btree (cod_roles_usu);


--
-- Name: users_tel_usu_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX users_tel_usu_index ON public.users USING btree (tel_usu);


--
-- Name: ventas_cod_usuario_cliente_ven_cod_usuario_vendedor_ven_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX ventas_cod_usuario_cliente_ven_cod_usuario_vendedor_ven_index ON public.ventas USING btree (cod_usuario_cliente_ven, cod_usuario_vendedor_ven);


--
-- Name: categorias categorias_cod_categoria_padre_cat_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_cod_categoria_padre_cat_foreign FOREIGN KEY (cod_categoria_padre_cat) REFERENCES public.categorias(id_categoria) ON DELETE SET NULL;


--
-- Name: detalle_reservas detalle_reservas_cod_prenda_variantes_dre_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detalle_reservas
    ADD CONSTRAINT detalle_reservas_cod_prenda_variantes_dre_foreign FOREIGN KEY (cod_prenda_variantes_dre) REFERENCES public.prenda_variantes(id_prenda_variante) ON DELETE RESTRICT;


--
-- Name: detalle_reservas detalle_reservas_cod_reservas_dre_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detalle_reservas
    ADD CONSTRAINT detalle_reservas_cod_reservas_dre_foreign FOREIGN KEY (cod_reservas_dre) REFERENCES public.reservas(id_reserva) ON DELETE CASCADE;


--
-- Name: detalle_ventas detalle_ventas_cod_prenda_variantes_dve_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detalle_ventas
    ADD CONSTRAINT detalle_ventas_cod_prenda_variantes_dve_foreign FOREIGN KEY (cod_prenda_variantes_dve) REFERENCES public.prenda_variantes(id_prenda_variante) ON DELETE RESTRICT;


--
-- Name: detalle_ventas detalle_ventas_cod_ventas_dve_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detalle_ventas
    ADD CONSTRAINT detalle_ventas_cod_ventas_dve_foreign FOREIGN KEY (cod_ventas_dve) REFERENCES public.ventas(id_venta) ON DELETE CASCADE;


--
-- Name: favoritos favoritos_cod_prendas_fav_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.favoritos
    ADD CONSTRAINT favoritos_cod_prendas_fav_foreign FOREIGN KEY (cod_prendas_fav) REFERENCES public.prendas(id_prenda) ON DELETE CASCADE;


--
-- Name: favoritos favoritos_cod_usuarios_fav_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.favoritos
    ADD CONSTRAINT favoritos_cod_usuarios_fav_foreign FOREIGN KEY (cod_usuarios_fav) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: imagenes_prenda imagenes_prenda_cod_prenda_variantes_igp_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.imagenes_prenda
    ADD CONSTRAINT imagenes_prenda_cod_prenda_variantes_igp_foreign FOREIGN KEY (cod_prenda_variantes_igp) REFERENCES public.prenda_variantes(id_prenda_variante) ON DELETE SET NULL;


--
-- Name: imagenes_prenda imagenes_prenda_cod_prendas_igp_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.imagenes_prenda
    ADD CONSTRAINT imagenes_prenda_cod_prendas_igp_foreign FOREIGN KEY (cod_prendas_igp) REFERENCES public.prendas(id_prenda) ON DELETE CASCADE;


--
-- Name: inventarios inventarios_cod_prenda_variantes_inv_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.inventarios
    ADD CONSTRAINT inventarios_cod_prenda_variantes_inv_foreign FOREIGN KEY (cod_prenda_variantes_inv) REFERENCES public.prenda_variantes(id_prenda_variante) ON DELETE CASCADE;


--
-- Name: prenda_variantes prenda_variantes_cod_colores_prv_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT prenda_variantes_cod_colores_prv_foreign FOREIGN KEY (cod_colores_prv) REFERENCES public.colores(id_color) ON DELETE RESTRICT;


--
-- Name: prenda_variantes prenda_variantes_cod_prendas_prv_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT prenda_variantes_cod_prendas_prv_foreign FOREIGN KEY (cod_prendas_prv) REFERENCES public.prendas(id_prenda) ON DELETE CASCADE;


--
-- Name: prenda_variantes prenda_variantes_cod_tallas_prv_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT prenda_variantes_cod_tallas_prv_foreign FOREIGN KEY (cod_tallas_prv) REFERENCES public.tallas(id_talla) ON DELETE RESTRICT;


--
-- Name: prendas prendas_cod_categorias_pre_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas
    ADD CONSTRAINT prendas_cod_categorias_pre_foreign FOREIGN KEY (cod_categorias_pre) REFERENCES public.categorias(id_categoria) ON DELETE RESTRICT;


--
-- Name: prendas prendas_cod_marcas_pre_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas
    ADD CONSTRAINT prendas_cod_marcas_pre_foreign FOREIGN KEY (cod_marcas_pre) REFERENCES public.marcas(id_marca) ON DELETE SET NULL;


--
-- Name: prendas prendas_cod_temporadas_pre_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas
    ADD CONSTRAINT prendas_cod_temporadas_pre_foreign FOREIGN KEY (cod_temporadas_pre) REFERENCES public.temporadas(id_temporada) ON DELETE SET NULL;


--
-- Name: prendas prendas_cod_usuario_registra_pre_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas
    ADD CONSTRAINT prendas_cod_usuario_registra_pre_foreign FOREIGN KEY (cod_usuario_registra_pre) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: reservas reservas_cod_usuario_cliente_res_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reservas
    ADD CONSTRAINT reservas_cod_usuario_cliente_res_foreign FOREIGN KEY (cod_usuario_cliente_res) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: reservas reservas_cod_usuario_responsable_res_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reservas
    ADD CONSTRAINT reservas_cod_usuario_responsable_res_foreign FOREIGN KEY (cod_usuario_responsable_res) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: users users_cod_roles_usu_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_cod_roles_usu_foreign FOREIGN KEY (cod_roles_usu) REFERENCES public.roles(id_rol) ON DELETE SET NULL;


--
-- Name: ventas ventas_cod_usuario_cliente_ven_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_cod_usuario_cliente_ven_foreign FOREIGN KEY (cod_usuario_cliente_ven) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ventas ventas_cod_usuario_vendedor_ven_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_cod_usuario_vendedor_ven_foreign FOREIGN KEY (cod_usuario_vendedor_ven) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict tHsGSemD1ScblSbBNGgyuXzEgNpOJ3wTsPMl6NNDl2AHSg9xZ1Ev8Gl4LjFA7Pf

