--
-- PostgreSQL database dump
--

\restrict tUGWFCvSaFusMovVUZRo7cPM8tNtONfC8HSbL0SI9b4bVB6z4GqIdWhYDBDDTRh

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
-- Name: atributos_prenda; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.atributos_prenda (
    id_atributo_prenda bigint NOT NULL,
    id_prenda bigint NOT NULL,
    nombre_atributo character varying(100) NOT NULL,
    valor_atributo character varying(255) NOT NULL,
    fuente character varying(50),
    nivel_confianza numeric(5,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.atributos_prenda OWNER TO sail;

--
-- Name: atributos_prenda_id_atributo_prenda_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.atributos_prenda_id_atributo_prenda_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.atributos_prenda_id_atributo_prenda_seq OWNER TO sail;

--
-- Name: atributos_prenda_id_atributo_prenda_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.atributos_prenda_id_atributo_prenda_seq OWNED BY public.atributos_prenda.id_atributo_prenda;


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
    nombre_categoria character varying(100) NOT NULL,
    descripcion character varying(255),
    id_categoria_padre bigint,
    estado boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
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
    nombre_color character varying(50) NOT NULL,
    codigo_hex character varying(7),
    estado boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
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
-- Name: detalle_ventas; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.detalle_ventas (
    id_detalle_venta bigint NOT NULL,
    id_venta bigint NOT NULL,
    id_prenda_variante bigint NOT NULL,
    cantidad integer NOT NULL,
    precio_unitario numeric(10,2) NOT NULL,
    subtotal numeric(10,2) NOT NULL,
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
-- Name: detecciones_ia; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.detecciones_ia (
    id_deteccion_ia bigint NOT NULL,
    id_imagen_prenda bigint NOT NULL,
    tipo_prenda_detectado character varying(100),
    color_detectado character varying(100),
    estilo_detectado character varying(100),
    categoria_detectada character varying(100),
    nivel_confianza numeric(5,2),
    respuesta_cruda jsonb,
    validado boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.detecciones_ia OWNER TO sail;

--
-- Name: detecciones_ia_id_deteccion_ia_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.detecciones_ia_id_deteccion_ia_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.detecciones_ia_id_deteccion_ia_seq OWNER TO sail;

--
-- Name: detecciones_ia_id_deteccion_ia_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.detecciones_ia_id_deteccion_ia_seq OWNED BY public.detecciones_ia.id_deteccion_ia;


--
-- Name: estilos; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.estilos (
    id_estilo bigint NOT NULL,
    nombre_estilo character varying(100) NOT NULL,
    descripcion character varying(255),
    estado boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.estilos OWNER TO sail;

--
-- Name: estilos_id_estilo_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.estilos_id_estilo_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.estilos_id_estilo_seq OWNER TO sail;

--
-- Name: estilos_id_estilo_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.estilos_id_estilo_seq OWNED BY public.estilos.id_estilo;


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
    id_usuario bigint NOT NULL,
    id_prenda bigint NOT NULL,
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
    id_prenda bigint NOT NULL,
    id_prenda_variante bigint,
    ruta_imagen character varying(255) NOT NULL,
    url_imagen character varying(500),
    es_principal boolean DEFAULT false NOT NULL,
    orden_visualizacion integer DEFAULT 0 NOT NULL,
    fuente character varying(50),
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
-- Name: interacciones_usuarios; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.interacciones_usuarios (
    id_interaccion_usuario bigint NOT NULL,
    id_usuario bigint NOT NULL,
    id_prenda bigint NOT NULL,
    tipo_interaccion character varying(30) NOT NULL,
    detalle_interaccion jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.interacciones_usuarios OWNER TO sail;

--
-- Name: interacciones_usuarios_id_interaccion_usuario_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.interacciones_usuarios_id_interaccion_usuario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.interacciones_usuarios_id_interaccion_usuario_seq OWNER TO sail;

--
-- Name: interacciones_usuarios_id_interaccion_usuario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.interacciones_usuarios_id_interaccion_usuario_seq OWNED BY public.interacciones_usuarios.id_interaccion_usuario;


--
-- Name: inventarios; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.inventarios (
    id_inventario bigint NOT NULL,
    id_prenda_variante bigint NOT NULL,
    cantidad_disponible integer DEFAULT 0 NOT NULL,
    cantidad_reservada integer DEFAULT 0 NOT NULL,
    stock_minimo integer DEFAULT 0 NOT NULL,
    ubicacion_fisica character varying(100),
    actualizado_en timestamp(0) without time zone
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
    nombre_marca character varying(100) NOT NULL,
    descripcion character varying(255),
    estado boolean DEFAULT true NOT NULL,
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
-- Name: movimientos_inventario; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.movimientos_inventario (
    id_movimiento_inventario bigint NOT NULL,
    id_inventario bigint NOT NULL,
    tipo_movimiento character varying(20) NOT NULL,
    cantidad integer NOT NULL,
    motivo character varying(100),
    referencia character varying(100),
    observacion text,
    realizado_por bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.movimientos_inventario OWNER TO sail;

--
-- Name: movimientos_inventario_id_movimiento_inventario_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.movimientos_inventario_id_movimiento_inventario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.movimientos_inventario_id_movimiento_inventario_seq OWNER TO sail;

--
-- Name: movimientos_inventario_id_movimiento_inventario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.movimientos_inventario_id_movimiento_inventario_seq OWNED BY public.movimientos_inventario.id_movimiento_inventario;


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
    id_prenda bigint NOT NULL,
    id_color bigint NOT NULL,
    id_talla bigint NOT NULL,
    sku character varying(50) NOT NULL,
    codigo_barras character varying(100),
    precio_venta numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    costo numeric(10,2),
    estado boolean DEFAULT true NOT NULL,
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
    nombre_prenda character varying(150) NOT NULL,
    descripcion text,
    id_categoria bigint NOT NULL,
    id_marca bigint,
    id_estilo bigint,
    id_temporada bigint,
    genero character varying(20),
    precio_base numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    estado boolean DEFAULT true NOT NULL,
    registrado_por bigint NOT NULL,
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
-- Name: roles; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.roles (
    id_rol bigint NOT NULL,
    nombre_rol character varying(50) NOT NULL,
    descripcion character varying(255),
    estado boolean DEFAULT true NOT NULL,
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
    nombre_talla character varying(20) NOT NULL,
    orden_visualizacion integer DEFAULT 0 NOT NULL,
    estado boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
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
    nombre_temporada character varying(50) NOT NULL,
    descripcion character varying(255),
    estado boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
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
    nombre character varying(100) NOT NULL,
    apellido_paterno character varying(100) NOT NULL,
    apellido_materno character varying(100),
    telefono character varying(20),
    id_rol bigint,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    estado boolean DEFAULT true NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
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
    numero_venta character varying(50) NOT NULL,
    id_usuario_cliente bigint,
    id_usuario_vendedor bigint NOT NULL,
    tipo_venta character varying(20) DEFAULT 'fisica'::character varying NOT NULL,
    subtotal numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    descuento numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    total numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    metodo_pago character varying(30),
    estado character varying(30) DEFAULT 'registrada'::character varying NOT NULL,
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
-- Name: atributos_prenda id_atributo_prenda; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.atributos_prenda ALTER COLUMN id_atributo_prenda SET DEFAULT nextval('public.atributos_prenda_id_atributo_prenda_seq'::regclass);


--
-- Name: categorias id_categoria; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.categorias ALTER COLUMN id_categoria SET DEFAULT nextval('public.categorias_id_categoria_seq'::regclass);


--
-- Name: colores id_color; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.colores ALTER COLUMN id_color SET DEFAULT nextval('public.colores_id_color_seq'::regclass);


--
-- Name: detalle_ventas id_detalle_venta; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detalle_ventas ALTER COLUMN id_detalle_venta SET DEFAULT nextval('public.detalle_ventas_id_detalle_venta_seq'::regclass);


--
-- Name: detecciones_ia id_deteccion_ia; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detecciones_ia ALTER COLUMN id_deteccion_ia SET DEFAULT nextval('public.detecciones_ia_id_deteccion_ia_seq'::regclass);


--
-- Name: estilos id_estilo; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.estilos ALTER COLUMN id_estilo SET DEFAULT nextval('public.estilos_id_estilo_seq'::regclass);


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
-- Name: interacciones_usuarios id_interaccion_usuario; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.interacciones_usuarios ALTER COLUMN id_interaccion_usuario SET DEFAULT nextval('public.interacciones_usuarios_id_interaccion_usuario_seq'::regclass);


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
-- Name: movimientos_inventario id_movimiento_inventario; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.movimientos_inventario ALTER COLUMN id_movimiento_inventario SET DEFAULT nextval('public.movimientos_inventario_id_movimiento_inventario_seq'::regclass);


--
-- Name: prenda_variantes id_prenda_variante; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes ALTER COLUMN id_prenda_variante SET DEFAULT nextval('public.prenda_variantes_id_prenda_variante_seq'::regclass);


--
-- Name: prendas id_prenda; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas ALTER COLUMN id_prenda SET DEFAULT nextval('public.prendas_id_prenda_seq'::regclass);


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
-- Data for Name: atributos_prenda; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.atributos_prenda (id_atributo_prenda, id_prenda, nombre_atributo, valor_atributo, fuente, nivel_confianza, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: categorias; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.categorias (id_categoria, nombre_categoria, descripcion, id_categoria_padre, estado, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: colores; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.colores (id_color, nombre_color, codigo_hex, estado, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: detalle_ventas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.detalle_ventas (id_detalle_venta, id_venta, id_prenda_variante, cantidad, precio_unitario, subtotal, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: detecciones_ia; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.detecciones_ia (id_deteccion_ia, id_imagen_prenda, tipo_prenda_detectado, color_detectado, estilo_detectado, categoria_detectada, nivel_confianza, respuesta_cruda, validado, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: estilos; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.estilos (id_estilo, nombre_estilo, descripcion, estado, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: favoritos; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.favoritos (id_favorito, id_usuario, id_prenda, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: imagenes_prenda; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.imagenes_prenda (id_imagen_prenda, id_prenda, id_prenda_variante, ruta_imagen, url_imagen, es_principal, orden_visualizacion, fuente, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: interacciones_usuarios; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.interacciones_usuarios (id_interaccion_usuario, id_usuario, id_prenda, tipo_interaccion, detalle_interaccion, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: inventarios; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.inventarios (id_inventario, id_prenda_variante, cantidad_disponible, cantidad_reservada, stock_minimo, ubicacion_fisica, actualizado_en) FROM stdin;
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

COPY public.marcas (id_marca, nombre_marca, descripcion, estado, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_03_29_170306_create_roles_table	1
5	2026_03_29_170401_create_categorias_table	1
6	2026_03_29_170600_create_marcas_table	1
7	2026_03_29_170611_create_estilos_table	1
8	2026_03_29_170625_create_colores_table	1
9	2026_03_29_170710_create_tallas_table	1
10	2026_03_29_170744_create_temporadas_table	1
11	2026_03_29_170801_create_prendas_table	1
12	2026_03_29_170956_create_prenda_variantes_table	1
13	2026_03_29_171014_create_imagenes_prenda_table	1
14	2026_03_29_171037_create_inventarios_table	1
15	2026_03_29_171129_create_movimientos_inventario_table	1
16	2026_03_29_171148_create_ventas_table	1
17	2026_03_29_171203_create_detalle_ventas_table	1
18	2026_03_29_171243_create_favoritos_table	1
19	2026_03_29_171256_create_interacciones_usuarios_table	1
20	2026_03_29_171311_create_detecciones_ia_table	1
21	2026_03_29_171343_create_atributos_prenda_table	1
\.


--
-- Data for Name: movimientos_inventario; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.movimientos_inventario (id_movimiento_inventario, id_inventario, tipo_movimiento, cantidad, motivo, referencia, observacion, realizado_por, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: prenda_variantes; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.prenda_variantes (id_prenda_variante, id_prenda, id_color, id_talla, sku, codigo_barras, precio_venta, costo, estado, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: prendas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.prendas (id_prenda, nombre_prenda, descripcion, id_categoria, id_marca, id_estilo, id_temporada, genero, precio_base, estado, registrado_por, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.roles (id_rol, nombre_rol, descripcion, estado, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
\.


--
-- Data for Name: tallas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.tallas (id_talla, nombre_talla, orden_visualizacion, estado, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: temporadas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.temporadas (id_temporada, nombre_temporada, descripcion, estado, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.users (id, nombre, apellido_paterno, apellido_materno, telefono, id_rol, email, email_verified_at, password, estado, remember_token, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: ventas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.ventas (id_venta, numero_venta, id_usuario_cliente, id_usuario_vendedor, tipo_venta, subtotal, descuento, total, metodo_pago, estado, created_at, updated_at) FROM stdin;
\.


--
-- Name: atributos_prenda_id_atributo_prenda_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.atributos_prenda_id_atributo_prenda_seq', 1, false);


--
-- Name: categorias_id_categoria_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.categorias_id_categoria_seq', 1, false);


--
-- Name: colores_id_color_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.colores_id_color_seq', 1, false);


--
-- Name: detalle_ventas_id_detalle_venta_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.detalle_ventas_id_detalle_venta_seq', 1, false);


--
-- Name: detecciones_ia_id_deteccion_ia_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.detecciones_ia_id_deteccion_ia_seq', 1, false);


--
-- Name: estilos_id_estilo_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.estilos_id_estilo_seq', 1, false);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: favoritos_id_favorito_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.favoritos_id_favorito_seq', 1, false);


--
-- Name: imagenes_prenda_id_imagen_prenda_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.imagenes_prenda_id_imagen_prenda_seq', 1, false);


--
-- Name: interacciones_usuarios_id_interaccion_usuario_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.interacciones_usuarios_id_interaccion_usuario_seq', 1, false);


--
-- Name: inventarios_id_inventario_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.inventarios_id_inventario_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: marcas_id_marca_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.marcas_id_marca_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.migrations_id_seq', 21, true);


--
-- Name: movimientos_inventario_id_movimiento_inventario_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.movimientos_inventario_id_movimiento_inventario_seq', 1, false);


--
-- Name: prenda_variantes_id_prenda_variante_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.prenda_variantes_id_prenda_variante_seq', 1, false);


--
-- Name: prendas_id_prenda_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.prendas_id_prenda_seq', 1, false);


--
-- Name: roles_id_rol_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.roles_id_rol_seq', 1, false);


--
-- Name: tallas_id_talla_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.tallas_id_talla_seq', 1, false);


--
-- Name: temporadas_id_temporada_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.temporadas_id_temporada_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.users_id_seq', 1, false);


--
-- Name: ventas_id_venta_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.ventas_id_venta_seq', 1, false);


--
-- Name: atributos_prenda atributos_prenda_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.atributos_prenda
    ADD CONSTRAINT atributos_prenda_pkey PRIMARY KEY (id_atributo_prenda);


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
-- Name: categorias categorias_nombre_categoria_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_nombre_categoria_unique UNIQUE (nombre_categoria);


--
-- Name: categorias categorias_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_pkey PRIMARY KEY (id_categoria);


--
-- Name: colores colores_nombre_color_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.colores
    ADD CONSTRAINT colores_nombre_color_unique UNIQUE (nombre_color);


--
-- Name: colores colores_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.colores
    ADD CONSTRAINT colores_pkey PRIMARY KEY (id_color);


--
-- Name: detalle_ventas detalle_ventas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detalle_ventas
    ADD CONSTRAINT detalle_ventas_pkey PRIMARY KEY (id_detalle_venta);


--
-- Name: detecciones_ia detecciones_ia_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detecciones_ia
    ADD CONSTRAINT detecciones_ia_pkey PRIMARY KEY (id_deteccion_ia);


--
-- Name: estilos estilos_nombre_estilo_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.estilos
    ADD CONSTRAINT estilos_nombre_estilo_unique UNIQUE (nombre_estilo);


--
-- Name: estilos estilos_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.estilos
    ADD CONSTRAINT estilos_pkey PRIMARY KEY (id_estilo);


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
-- Name: favoritos favoritos_id_usuario_id_prenda_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.favoritos
    ADD CONSTRAINT favoritos_id_usuario_id_prenda_unique UNIQUE (id_usuario, id_prenda);


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
-- Name: interacciones_usuarios interacciones_usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.interacciones_usuarios
    ADD CONSTRAINT interacciones_usuarios_pkey PRIMARY KEY (id_interaccion_usuario);


--
-- Name: inventarios inventarios_id_prenda_variante_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.inventarios
    ADD CONSTRAINT inventarios_id_prenda_variante_unique UNIQUE (id_prenda_variante);


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
-- Name: marcas marcas_nombre_marca_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.marcas
    ADD CONSTRAINT marcas_nombre_marca_unique UNIQUE (nombre_marca);


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
-- Name: movimientos_inventario movimientos_inventario_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.movimientos_inventario
    ADD CONSTRAINT movimientos_inventario_pkey PRIMARY KEY (id_movimiento_inventario);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: prenda_variantes prenda_variantes_codigo_barras_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT prenda_variantes_codigo_barras_unique UNIQUE (codigo_barras);


--
-- Name: prenda_variantes prenda_variantes_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT prenda_variantes_pkey PRIMARY KEY (id_prenda_variante);


--
-- Name: prenda_variantes prenda_variantes_sku_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT prenda_variantes_sku_unique UNIQUE (sku);


--
-- Name: prendas prendas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas
    ADD CONSTRAINT prendas_pkey PRIMARY KEY (id_prenda);


--
-- Name: roles roles_nombre_rol_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_nombre_rol_unique UNIQUE (nombre_rol);


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
-- Name: tallas tallas_nombre_talla_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.tallas
    ADD CONSTRAINT tallas_nombre_talla_unique UNIQUE (nombre_talla);


--
-- Name: tallas tallas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.tallas
    ADD CONSTRAINT tallas_pkey PRIMARY KEY (id_talla);


--
-- Name: temporadas temporadas_nombre_temporada_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.temporadas
    ADD CONSTRAINT temporadas_nombre_temporada_unique UNIQUE (nombre_temporada);


--
-- Name: temporadas temporadas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.temporadas
    ADD CONSTRAINT temporadas_pkey PRIMARY KEY (id_temporada);


--
-- Name: prenda_variantes uq_prenda_color_talla; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT uq_prenda_color_talla UNIQUE (id_prenda, id_color, id_talla);


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
-- Name: ventas ventas_numero_venta_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_numero_venta_unique UNIQUE (numero_venta);


--
-- Name: ventas ventas_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_pkey PRIMARY KEY (id_venta);


--
-- Name: atributos_prenda_id_prenda_nombre_atributo_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX atributos_prenda_id_prenda_nombre_atributo_index ON public.atributos_prenda USING btree (id_prenda, nombre_atributo);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: interacciones_usuarios_tipo_interaccion_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX interacciones_usuarios_tipo_interaccion_index ON public.interacciones_usuarios USING btree (tipo_interaccion);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: movimientos_inventario_tipo_movimiento_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX movimientos_inventario_tipo_movimiento_index ON public.movimientos_inventario USING btree (tipo_movimiento);


--
-- Name: prendas_nombre_prenda_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX prendas_nombre_prenda_index ON public.prendas USING btree (nombre_prenda);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: users_apellido_paterno_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX users_apellido_paterno_index ON public.users USING btree (apellido_paterno);


--
-- Name: users_id_rol_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX users_id_rol_index ON public.users USING btree (id_rol);


--
-- Name: users_telefono_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX users_telefono_index ON public.users USING btree (telefono);


--
-- Name: ventas_id_usuario_cliente_id_usuario_vendedor_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX ventas_id_usuario_cliente_id_usuario_vendedor_index ON public.ventas USING btree (id_usuario_cliente, id_usuario_vendedor);


--
-- Name: atributos_prenda atributos_prenda_id_prenda_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.atributos_prenda
    ADD CONSTRAINT atributos_prenda_id_prenda_foreign FOREIGN KEY (id_prenda) REFERENCES public.prendas(id_prenda) ON DELETE CASCADE;


--
-- Name: categorias categorias_id_categoria_padre_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_id_categoria_padre_foreign FOREIGN KEY (id_categoria_padre) REFERENCES public.categorias(id_categoria) ON DELETE SET NULL;


--
-- Name: detalle_ventas detalle_ventas_id_prenda_variante_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detalle_ventas
    ADD CONSTRAINT detalle_ventas_id_prenda_variante_foreign FOREIGN KEY (id_prenda_variante) REFERENCES public.prenda_variantes(id_prenda_variante) ON DELETE RESTRICT;


--
-- Name: detalle_ventas detalle_ventas_id_venta_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detalle_ventas
    ADD CONSTRAINT detalle_ventas_id_venta_foreign FOREIGN KEY (id_venta) REFERENCES public.ventas(id_venta) ON DELETE CASCADE;


--
-- Name: detecciones_ia detecciones_ia_id_imagen_prenda_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.detecciones_ia
    ADD CONSTRAINT detecciones_ia_id_imagen_prenda_foreign FOREIGN KEY (id_imagen_prenda) REFERENCES public.imagenes_prenda(id_imagen_prenda) ON DELETE CASCADE;


--
-- Name: favoritos favoritos_id_prenda_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.favoritos
    ADD CONSTRAINT favoritos_id_prenda_foreign FOREIGN KEY (id_prenda) REFERENCES public.prendas(id_prenda) ON DELETE CASCADE;


--
-- Name: favoritos favoritos_id_usuario_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.favoritos
    ADD CONSTRAINT favoritos_id_usuario_foreign FOREIGN KEY (id_usuario) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: imagenes_prenda imagenes_prenda_id_prenda_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.imagenes_prenda
    ADD CONSTRAINT imagenes_prenda_id_prenda_foreign FOREIGN KEY (id_prenda) REFERENCES public.prendas(id_prenda) ON DELETE CASCADE;


--
-- Name: imagenes_prenda imagenes_prenda_id_prenda_variante_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.imagenes_prenda
    ADD CONSTRAINT imagenes_prenda_id_prenda_variante_foreign FOREIGN KEY (id_prenda_variante) REFERENCES public.prenda_variantes(id_prenda_variante) ON DELETE SET NULL;


--
-- Name: interacciones_usuarios interacciones_usuarios_id_prenda_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.interacciones_usuarios
    ADD CONSTRAINT interacciones_usuarios_id_prenda_foreign FOREIGN KEY (id_prenda) REFERENCES public.prendas(id_prenda) ON DELETE CASCADE;


--
-- Name: interacciones_usuarios interacciones_usuarios_id_usuario_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.interacciones_usuarios
    ADD CONSTRAINT interacciones_usuarios_id_usuario_foreign FOREIGN KEY (id_usuario) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: inventarios inventarios_id_prenda_variante_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.inventarios
    ADD CONSTRAINT inventarios_id_prenda_variante_foreign FOREIGN KEY (id_prenda_variante) REFERENCES public.prenda_variantes(id_prenda_variante) ON DELETE CASCADE;


--
-- Name: movimientos_inventario movimientos_inventario_id_inventario_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.movimientos_inventario
    ADD CONSTRAINT movimientos_inventario_id_inventario_foreign FOREIGN KEY (id_inventario) REFERENCES public.inventarios(id_inventario) ON DELETE CASCADE;


--
-- Name: movimientos_inventario movimientos_inventario_realizado_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.movimientos_inventario
    ADD CONSTRAINT movimientos_inventario_realizado_por_foreign FOREIGN KEY (realizado_por) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: prenda_variantes prenda_variantes_id_color_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT prenda_variantes_id_color_foreign FOREIGN KEY (id_color) REFERENCES public.colores(id_color) ON DELETE RESTRICT;


--
-- Name: prenda_variantes prenda_variantes_id_prenda_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT prenda_variantes_id_prenda_foreign FOREIGN KEY (id_prenda) REFERENCES public.prendas(id_prenda) ON DELETE CASCADE;


--
-- Name: prenda_variantes prenda_variantes_id_talla_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes
    ADD CONSTRAINT prenda_variantes_id_talla_foreign FOREIGN KEY (id_talla) REFERENCES public.tallas(id_talla) ON DELETE RESTRICT;


--
-- Name: prendas prendas_id_categoria_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas
    ADD CONSTRAINT prendas_id_categoria_foreign FOREIGN KEY (id_categoria) REFERENCES public.categorias(id_categoria) ON DELETE RESTRICT;


--
-- Name: prendas prendas_id_estilo_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas
    ADD CONSTRAINT prendas_id_estilo_foreign FOREIGN KEY (id_estilo) REFERENCES public.estilos(id_estilo) ON DELETE SET NULL;


--
-- Name: prendas prendas_id_marca_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas
    ADD CONSTRAINT prendas_id_marca_foreign FOREIGN KEY (id_marca) REFERENCES public.marcas(id_marca) ON DELETE SET NULL;


--
-- Name: prendas prendas_id_temporada_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas
    ADD CONSTRAINT prendas_id_temporada_foreign FOREIGN KEY (id_temporada) REFERENCES public.temporadas(id_temporada) ON DELETE SET NULL;


--
-- Name: prendas prendas_registrado_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas
    ADD CONSTRAINT prendas_registrado_por_foreign FOREIGN KEY (registrado_por) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: ventas ventas_id_usuario_cliente_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_id_usuario_cliente_foreign FOREIGN KEY (id_usuario_cliente) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ventas ventas_id_usuario_vendedor_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_id_usuario_vendedor_foreign FOREIGN KEY (id_usuario_vendedor) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict tUGWFCvSaFusMovVUZRo7cPM8tNtONfC8HSbL0SI9b4bVB6z4GqIdWhYDBDDTRh

