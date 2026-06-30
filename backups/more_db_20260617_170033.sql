--
-- PostgreSQL database dump
--

\restrict m83YNrrQgiCIuMILcYltFybNtfgYaVycDaGJN9L67sCeycVvMVKeF4DPlD1nmke

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
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    cod_usuario_aul bigint,
    tabla_aul character varying(100) NOT NULL,
    registro_id_aul bigint,
    accion_aul character varying(30) NOT NULL,
    modulo_aul character varying(80) NOT NULL,
    valores_anteriores_aul json,
    valores_nuevos_aul json,
    campos_modificados_aul json,
    descripcion_aul text,
    nivel_aul character varying(30) DEFAULT 'info'::character varying NOT NULL,
    ip_aul character varying(45),
    user_agent_aul text,
    ruta_aul character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.audit_logs OWNER TO sail;

--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.audit_logs_id_seq OWNER TO sail;

--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


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
    tip_tal_cat character varying(20),
    gru_tal_cat character varying(30)
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
-- Name: database_backups; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.database_backups (
    id bigint NOT NULL,
    nombre_bak character varying(150) NOT NULL,
    archivo_bak character varying(255) NOT NULL,
    ruta_bak character varying(500) NOT NULL,
    disco_bak character varying(50) DEFAULT 'local'::character varying NOT NULL,
    tamano_bak bigint,
    estado_bak character varying(30) DEFAULT 'pendiente'::character varying NOT NULL,
    mensaje_bak text,
    cod_usuario_bak bigint,
    fec_bak timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.database_backups OWNER TO sail;

--
-- Name: database_backups_id_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.database_backups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.database_backups_id_seq OWNER TO sail;

--
-- Name: database_backups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.database_backups_id_seq OWNED BY public.database_backups.id;


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
    updated_at timestamp(0) without time zone,
    pun_dre numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    sub_dre numeric(10,2) DEFAULT '0'::numeric NOT NULL
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
-- Name: inventario_movimientos; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.inventario_movimientos (
    id bigint NOT NULL,
    cod_inventario_inm bigint,
    cod_prenda_variante_inm bigint NOT NULL,
    tipo_movimiento_inm character varying(50) NOT NULL,
    cantidad_inm integer NOT NULL,
    cdi_antes_inm integer DEFAULT 0 NOT NULL,
    cdi_despues_inm integer DEFAULT 0 NOT NULL,
    cre_antes_inm integer DEFAULT 0 NOT NULL,
    cre_despues_inm integer DEFAULT 0 NOT NULL,
    ubicacion_antes_inm character varying(100),
    ubicacion_despues_inm character varying(100),
    referencia_tipo_inm character varying(80),
    referencia_id_inm bigint,
    cod_usuario_inm bigint,
    descripcion_inm text,
    metadata_inm json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.inventario_movimientos OWNER TO sail;

--
-- Name: inventario_movimientos_id_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.inventario_movimientos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.inventario_movimientos_id_seq OWNER TO sail;

--
-- Name: inventario_movimientos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.inventario_movimientos_id_seq OWNED BY public.inventario_movimientos.id;


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
-- Name: notifications; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data json NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.notifications OWNER TO sail;

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
-- Name: prenda_danio_imagenes; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.prenda_danio_imagenes (
    id bigint NOT NULL,
    cod_prenda_danio_reporte_pdi bigint NOT NULL,
    ruta_pdi character varying(500) NOT NULL,
    url_pdi character varying(500),
    descripcion_pdi character varying(255),
    orden_pdi integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.prenda_danio_imagenes OWNER TO sail;

--
-- Name: prenda_danio_imagenes_id_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.prenda_danio_imagenes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.prenda_danio_imagenes_id_seq OWNER TO sail;

--
-- Name: prenda_danio_imagenes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.prenda_danio_imagenes_id_seq OWNED BY public.prenda_danio_imagenes.id;


--
-- Name: prenda_danio_reportes; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.prenda_danio_reportes (
    id bigint NOT NULL,
    codigo_pdr character varying(50) NOT NULL,
    nombre_prenda_pdr character varying(180) NOT NULL,
    descripcion_prenda_pdr text,
    cod_prenda_pdr bigint,
    cod_prenda_variante_pdr bigint,
    cod_inventario_pdr bigint,
    cod_usuario_reporta_pdr bigint,
    cod_usuario_revisa_pdr bigint,
    causa_pdr character varying(150) NOT NULL,
    tipo_danio_pdr character varying(80) NOT NULL,
    severidad_pdr character varying(30) NOT NULL,
    cantidad_pdr integer DEFAULT 1 NOT NULL,
    estado_pdr character varying(40) DEFAULT 'reportado'::character varying NOT NULL,
    decision_pdr character varying(80),
    observacion_pdr text,
    observacion_revision_pdr text,
    fecha_reporte_pdr timestamp(0) without time zone,
    fecha_revision_pdr timestamp(0) without time zone,
    afecta_inventario_pdr boolean DEFAULT false NOT NULL,
    metadata_pdr json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.prenda_danio_reportes OWNER TO sail;

--
-- Name: prenda_danio_reportes_id_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.prenda_danio_reportes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.prenda_danio_reportes_id_seq OWNER TO sail;

--
-- Name: prenda_danio_reportes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.prenda_danio_reportes_id_seq OWNED BY public.prenda_danio_reportes.id;


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
-- Name: reserva_pagos; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.reserva_pagos (
    id_reserva_pago bigint NOT NULL,
    cod_reservas_rpg bigint NOT NULL,
    monto_declarado_rpg numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    monto_confirmado_rpg numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    porcentaje_rpg numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    metodo_pago_rpg character varying(30),
    comprobante_ruta_rpg character varying(255),
    comprobante_url_rpg character varying(500),
    est_rpg character varying(30) DEFAULT 'pendiente_revision'::character varying NOT NULL,
    cod_usuario_verifica_rpg bigint,
    fec_verificacion_rpg timestamp(0) without time zone,
    obs_rpg text,
    fec_rpg timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.reserva_pagos OWNER TO sail;

--
-- Name: reserva_pagos_id_reserva_pago_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.reserva_pagos_id_reserva_pago_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reserva_pagos_id_reserva_pago_seq OWNER TO sail;

--
-- Name: reserva_pagos_id_reserva_pago_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.reserva_pagos_id_reserva_pago_seq OWNED BY public.reserva_pagos.id_reserva_pago;


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
    updated_at timestamp(0) without time zone,
    sub_res numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    des_res numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    tot_res numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    monto_pagado_res numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    saldo_res numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    est_pago_res character varying(30) DEFAULT 'pendiente_revision'::character varying NOT NULL,
    fec_disponible_recojo_res timestamp(0) without time zone,
    fec_limite_recojo_res timestamp(0) without time zone,
    punto_recojo_res character varying(150),
    origen_stock_res character varying(30),
    cod_usuario_verifica_pago_res bigint,
    fec_verificacion_pago_res timestamp(0) without time zone,
    obs_pago_res text,
    est_logistico_res character varying(30) DEFAULT 'pendiente_revision'::character varying NOT NULL,
    fec_recogida_res timestamp(0) without time zone,
    cod_usuario_entrega_res bigint
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
-- Name: system_logs; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.system_logs (
    id bigint NOT NULL,
    cod_usuario_slg bigint,
    rol_slg character varying(50),
    modulo_slg character varying(80) NOT NULL,
    accion_slg character varying(80) NOT NULL,
    entidad_tipo_slg character varying(100),
    entidad_id_slg bigint,
    descripcion_slg text NOT NULL,
    nivel_slg character varying(30) DEFAULT 'info'::character varying NOT NULL,
    ip_slg character varying(45),
    user_agent_slg text,
    metodo_http_slg character varying(10),
    ruta_slg character varying(255),
    metadata_slg json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.system_logs OWNER TO sail;

--
-- Name: system_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.system_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.system_logs_id_seq OWNER TO sail;

--
-- Name: system_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.system_logs_id_seq OWNED BY public.system_logs.id;


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
    gru_tal character varying(30),
    seg_tal character varying(20) DEFAULT 'ADULTO'::character varying NOT NULL
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
-- Name: venta_pagos; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.venta_pagos (
    id_venta_pago bigint NOT NULL,
    cod_ventas_vpg bigint NOT NULL,
    monto_declarado_vpg numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    monto_verificado_vpg numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    metodo_pago_vpg character varying(30) NOT NULL,
    comprobante_ruta_vpg character varying(255),
    comprobante_url_vpg character varying(500),
    est_vpg character varying(30) DEFAULT 'pendiente_revision'::character varying NOT NULL,
    cod_usuario_verifica_vpg bigint,
    fec_verificacion_vpg timestamp(0) without time zone,
    obs_vpg text,
    fec_vpg timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.venta_pagos OWNER TO sail;

--
-- Name: venta_pagos_id_venta_pago_seq; Type: SEQUENCE; Schema: public; Owner: sail
--

CREATE SEQUENCE public.venta_pagos_id_venta_pago_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.venta_pagos_id_venta_pago_seq OWNER TO sail;

--
-- Name: venta_pagos_id_venta_pago_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sail
--

ALTER SEQUENCE public.venta_pagos_id_venta_pago_seq OWNED BY public.venta_pagos.id_venta_pago;


--
-- Name: ventas; Type: TABLE; Schema: public; Owner: sail
--

CREATE TABLE public.ventas (
    id_venta bigint NOT NULL,
    nve_ven character varying(50) NOT NULL,
    cod_usuario_cliente_ven bigint,
    cod_usuario_vendedor_ven bigint,
    tiv_ven character varying(20) DEFAULT 'fisica'::character varying NOT NULL,
    sub_ven numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    des_ven numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    tot_ven numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    mpa_ven character varying(30),
    est_ven character varying(30) DEFAULT 'registrada'::character varying NOT NULL,
    fec_ven timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    est_logistico_ven character varying(30) DEFAULT 'pendiente_revision'::character varying NOT NULL,
    fec_disponible_recojo_ven timestamp(0) without time zone,
    fec_limite_recojo_ven timestamp(0) without time zone,
    punto_recojo_ven character varying(150),
    origen_stock_ven character varying(30),
    fec_entrega_ven timestamp(0) without time zone,
    cod_usuario_entrega_ven bigint
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
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: categorias id_categoria; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.categorias ALTER COLUMN id_categoria SET DEFAULT nextval('public.categorias_id_categoria_seq'::regclass);


--
-- Name: colores id_color; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.colores ALTER COLUMN id_color SET DEFAULT nextval('public.colores_id_color_seq'::regclass);


--
-- Name: database_backups id; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.database_backups ALTER COLUMN id SET DEFAULT nextval('public.database_backups_id_seq'::regclass);


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
-- Name: inventario_movimientos id; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.inventario_movimientos ALTER COLUMN id SET DEFAULT nextval('public.inventario_movimientos_id_seq'::regclass);


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
-- Name: prenda_danio_imagenes id; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_danio_imagenes ALTER COLUMN id SET DEFAULT nextval('public.prenda_danio_imagenes_id_seq'::regclass);


--
-- Name: prenda_danio_reportes id; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_danio_reportes ALTER COLUMN id SET DEFAULT nextval('public.prenda_danio_reportes_id_seq'::regclass);


--
-- Name: prenda_variantes id_prenda_variante; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_variantes ALTER COLUMN id_prenda_variante SET DEFAULT nextval('public.prenda_variantes_id_prenda_variante_seq'::regclass);


--
-- Name: prendas id_prenda; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prendas ALTER COLUMN id_prenda SET DEFAULT nextval('public.prendas_id_prenda_seq'::regclass);


--
-- Name: reserva_pagos id_reserva_pago; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reserva_pagos ALTER COLUMN id_reserva_pago SET DEFAULT nextval('public.reserva_pagos_id_reserva_pago_seq'::regclass);


--
-- Name: reservas id_reserva; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reservas ALTER COLUMN id_reserva SET DEFAULT nextval('public.reservas_id_reserva_seq'::regclass);


--
-- Name: roles id_rol; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.roles ALTER COLUMN id_rol SET DEFAULT nextval('public.roles_id_rol_seq'::regclass);


--
-- Name: system_logs id; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.system_logs ALTER COLUMN id SET DEFAULT nextval('public.system_logs_id_seq'::regclass);


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
-- Name: venta_pagos id_venta_pago; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.venta_pagos ALTER COLUMN id_venta_pago SET DEFAULT nextval('public.venta_pagos_id_venta_pago_seq'::regclass);


--
-- Name: ventas id_venta; Type: DEFAULT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas ALTER COLUMN id_venta SET DEFAULT nextval('public.ventas_id_venta_seq'::regclass);


--
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.audit_logs (id, cod_usuario_aul, tabla_aul, registro_id_aul, accion_aul, modulo_aul, valores_anteriores_aul, valores_nuevos_aul, campos_modificados_aul, descripcion_aul, nivel_aul, ip_aul, user_agent_aul, ruta_aul, created_at, updated_at) FROM stdin;
1	1	prendas	7	created	prendas	[]	{"prb_pre":"150.00","est_pre":true,"cod_categorias_pre":3,"cod_marcas_pre":1}	["prb_pre","est_pre","cod_categorias_pre","cod_marcas_pre"]	Auditoria critica created en prendas para registro #7: prb_pre, est_pre, cod_categorias_pre, cod_marcas_pre.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-04 19:58:35	2026-06-04 19:58:35
2	1	prenda_variantes	7	created	variantes	[]	{"pve_prv":"150.00","est_prv":true,"cod_colores_prv":13,"cod_tallas_prv":5,"sku_prv":"CAM-00007"}	["pve_prv","est_prv","cod_colores_prv","cod_tallas_prv","sku_prv"]	Auditoria critica created en prenda_variantes para registro #7: pve_prv, est_prv, cod_colores_prv, cod_tallas_prv, sku_prv.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-04 19:58:35	2026-06-04 19:58:35
3	1	inventarios	7	created	inventario	[]	{"cdi_inv":1,"cre_inv":0,"ubi_inv":"TIENDA"}	["cdi_inv","cre_inv","ubi_inv"]	Auditoria critica created en inventarios para registro #7: cdi_inv, cre_inv, ubi_inv.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-04 19:58:35	2026-06-04 19:58:35
4	1	prendas	8	created	prendas	[]	{"prb_pre":"150.00","est_pre":true,"cod_categorias_pre":8,"cod_marcas_pre":1}	["prb_pre","est_pre","cod_categorias_pre","cod_marcas_pre"]	Auditoria critica created en prendas para registro #8: prb_pre, est_pre, cod_categorias_pre, cod_marcas_pre.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-04 22:18:18	2026-06-04 22:18:18
5	1	prenda_variantes	8	created	variantes	[]	{"pve_prv":"150.00","est_prv":true,"cod_colores_prv":5,"cod_tallas_prv":6,"sku_prv":"POL-00008"}	["pve_prv","est_prv","cod_colores_prv","cod_tallas_prv","sku_prv"]	Auditoria critica created en prenda_variantes para registro #8: pve_prv, est_prv, cod_colores_prv, cod_tallas_prv, sku_prv.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-04 22:18:18	2026-06-04 22:18:18
6	1	inventarios	8	created	inventario	[]	{"cdi_inv":1,"cre_inv":0,"ubi_inv":"TIENDA"}	["cdi_inv","cre_inv","ubi_inv"]	Auditoria critica created en inventarios para registro #8: cdi_inv, cre_inv, ubi_inv.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-04 22:18:18	2026-06-04 22:18:18
7	1	prendas	9	created	prendas	[]	{"prb_pre":"150.00","est_pre":true,"cod_categorias_pre":8,"cod_marcas_pre":1}	["prb_pre","est_pre","cod_categorias_pre","cod_marcas_pre"]	Auditoria critica created en prendas para registro #9: prb_pre, est_pre, cod_categorias_pre, cod_marcas_pre.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-05 00:40:11	2026-06-05 00:40:11
8	1	prenda_variantes	9	created	variantes	[]	{"pve_prv":"150.00","est_prv":true,"cod_colores_prv":4,"cod_tallas_prv":5,"sku_prv":"POL-00009"}	["pve_prv","est_prv","cod_colores_prv","cod_tallas_prv","sku_prv"]	Auditoria critica created en prenda_variantes para registro #9: pve_prv, est_prv, cod_colores_prv, cod_tallas_prv, sku_prv.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-05 00:40:11	2026-06-05 00:40:11
9	1	inventarios	9	created	inventario	[]	{"cdi_inv":1,"cre_inv":0,"ubi_inv":"TIENDA"}	["cdi_inv","cre_inv","ubi_inv"]	Auditoria critica created en inventarios para registro #9: cdi_inv, cre_inv, ubi_inv.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-05 00:40:11	2026-06-05 00:40:11
10	5	ventas	7	created	ventas	[]	{"tot_ven":"150.00","est_ven":"en_revision","mpa_ven":"qr","est_logistico_ven":"pendiente_revision"}	["tot_ven","est_ven","mpa_ven","est_logistico_ven"]	Auditoria critica created en ventas para registro #7: tot_ven, est_ven, mpa_ven, est_logistico_ven.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	/compras/online	2026-06-05 00:43:33	2026-06-05 00:43:33
11	5	inventarios	8	updated	inventario	{"cdi_inv":1,"cre_inv":0}	{"cdi_inv":0,"cre_inv":1}	["cdi_inv","cre_inv"]	Auditoria critica updated en inventarios para registro #8: cdi_inv, cre_inv.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	/compras/online	2026-06-05 00:43:33	2026-06-05 00:43:33
12	5	detalle_ventas	8	created	ventas	[]	{"can_dve":1,"pun_dve":"150.00","sub_dve":"150.00"}	["can_dve","pun_dve","sub_dve"]	Auditoria critica created en detalle_ventas para registro #8: can_dve, pun_dve, sub_dve.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	/compras/online	2026-06-05 00:43:33	2026-06-05 00:43:33
13	5	venta_pagos	3	created	pagos	[]	{"monto_declarado_vpg":"150.00","monto_verificado_vpg":"0.00","est_vpg":"pendiente_revision","metodo_pago_vpg":"qr"}	["monto_declarado_vpg","monto_verificado_vpg","est_vpg","metodo_pago_vpg"]	Auditoria critica created en venta_pagos para registro #3: monto_declarado_vpg, monto_verificado_vpg, est_vpg, metodo_pago_vpg.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	/compras/online	2026-06-05 00:43:33	2026-06-05 00:43:33
14	1	inventarios	8	updated	inventario	{"cre_inv":1}	{"cre_inv":0}	["cre_inv"]	Auditoria critica updated en inventarios para registro #8: cre_inv.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/comercial/ventas/7	2026-06-05 00:44:14	2026-06-05 00:44:14
15	1	ventas	7	updated	ventas	{"est_ven":"en_revision","est_logistico_ven":"pendiente_revision"}	{"est_ven":"registrada","est_logistico_ven":"lista_recojo"}	["est_ven","est_logistico_ven"]	Auditoria critica updated en ventas para registro #7: est_ven, est_logistico_ven.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/comercial/ventas/7	2026-06-05 00:44:14	2026-06-05 00:44:14
16	1	venta_pagos	3	updated	pagos	{"monto_verificado_vpg":"0.00","est_vpg":"pendiente_revision","obs_vpg":null}	{"monto_verificado_vpg":"150.00","est_vpg":"confirmado","obs_vpg":"ninguna"}	["monto_verificado_vpg","est_vpg","obs_vpg"]	Auditoria critica updated en venta_pagos para registro #3: monto_verificado_vpg, est_vpg, obs_vpg.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/comercial/ventas/7	2026-06-05 00:44:14	2026-06-05 00:44:14
17	1	ventas	7	updated	ventas	{"est_logistico_ven":"lista_recojo","fec_entrega_ven":null}	{"est_logistico_ven":"entregada","fec_entrega_ven":"2026-06-05T15:37:48+00:00"}	["est_logistico_ven","fec_entrega_ven"]	Auditoria critica updated en ventas para registro #7: est_logistico_ven, fec_entrega_ven.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/comercial/ventas/7/entregar	2026-06-05 15:37:48	2026-06-05 15:37:48
18	1	prendas	10	created	prendas	[]	{"prb_pre":"150.00","est_pre":true,"cod_categorias_pre":1,"cod_marcas_pre":10}	["prb_pre","est_pre","cod_categorias_pre","cod_marcas_pre"]	Auditoria critica created en prendas para registro #10: prb_pre, est_pre, cod_categorias_pre, cod_marcas_pre.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-10 01:47:45	2026-06-10 01:47:45
19	1	prenda_variantes	10	created	variantes	[]	{"pve_prv":"150.00","est_prv":true,"cod_colores_prv":9,"cod_tallas_prv":5,"sku_prv":"ABR-00010"}	["pve_prv","est_prv","cod_colores_prv","cod_tallas_prv","sku_prv"]	Auditoria critica created en prenda_variantes para registro #10: pve_prv, est_prv, cod_colores_prv, cod_tallas_prv, sku_prv.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-10 01:47:45	2026-06-10 01:47:45
20	1	inventarios	10	created	inventario	[]	{"cdi_inv":1,"cre_inv":0,"ubi_inv":"TIENDA"}	["cdi_inv","cre_inv","ubi_inv"]	Auditoria critica created en inventarios para registro #10: cdi_inv, cre_inv, ubi_inv.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-10 01:47:45	2026-06-10 01:47:45
21	5	ventas	8	created	ventas	[]	{"tot_ven":"150.00","est_ven":"en_revision","mpa_ven":"qr","est_logistico_ven":"pendiente_revision"}	["tot_ven","est_ven","mpa_ven","est_logistico_ven"]	Auditoria critica created en ventas para registro #8: tot_ven, est_ven, mpa_ven, est_logistico_ven.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	/compras/online	2026-06-10 01:49:27	2026-06-10 01:49:27
22	5	inventarios	10	updated	inventario	{"cdi_inv":1,"cre_inv":0}	{"cdi_inv":0,"cre_inv":1}	["cdi_inv","cre_inv"]	Auditoria critica updated en inventarios para registro #10: cdi_inv, cre_inv.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	/compras/online	2026-06-10 01:49:27	2026-06-10 01:49:27
23	5	detalle_ventas	9	created	ventas	[]	{"can_dve":1,"pun_dve":"150.00","sub_dve":"150.00"}	["can_dve","pun_dve","sub_dve"]	Auditoria critica created en detalle_ventas para registro #9: can_dve, pun_dve, sub_dve.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	/compras/online	2026-06-10 01:49:27	2026-06-10 01:49:27
24	5	venta_pagos	4	created	pagos	[]	{"monto_declarado_vpg":"150.00","monto_verificado_vpg":"0.00","est_vpg":"pendiente_revision","metodo_pago_vpg":"qr"}	["monto_declarado_vpg","monto_verificado_vpg","est_vpg","metodo_pago_vpg"]	Auditoria critica created en venta_pagos para registro #4: monto_declarado_vpg, monto_verificado_vpg, est_vpg, metodo_pago_vpg.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	/compras/online	2026-06-10 01:49:27	2026-06-10 01:49:27
25	1	inventarios	10	updated	inventario	{"cre_inv":1}	{"cre_inv":0}	["cre_inv"]	Auditoria critica updated en inventarios para registro #10: cre_inv.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/comercial/ventas/8	2026-06-10 01:50:04	2026-06-10 01:50:04
26	1	ventas	8	updated	ventas	{"est_ven":"en_revision","est_logistico_ven":"pendiente_revision"}	{"est_ven":"registrada","est_logistico_ven":"lista_recojo"}	["est_ven","est_logistico_ven"]	Auditoria critica updated en ventas para registro #8: est_ven, est_logistico_ven.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/comercial/ventas/8	2026-06-10 01:50:04	2026-06-10 01:50:04
27	1	venta_pagos	4	updated	pagos	{"monto_verificado_vpg":"0.00","est_vpg":"pendiente_revision","obs_vpg":null}	{"monto_verificado_vpg":"150.00","est_vpg":"confirmado","obs_vpg":"ninguna"}	["monto_verificado_vpg","est_vpg","obs_vpg"]	Auditoria critica updated en venta_pagos para registro #4: monto_verificado_vpg, est_vpg, obs_vpg.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/comercial/ventas/8	2026-06-10 01:50:04	2026-06-10 01:50:04
28	1	ventas	8	updated	ventas	{"est_logistico_ven":"lista_recojo","fec_entrega_ven":null}	{"est_logistico_ven":"entregada","fec_entrega_ven":"2026-06-10T01:50:44+00:00"}	["est_logistico_ven","fec_entrega_ven"]	Auditoria critica updated en ventas para registro #8: est_logistico_ven, fec_entrega_ven.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/comercial/ventas/8/entregar	2026-06-10 01:50:44	2026-06-10 01:50:44
29	1	prendas	11	created	prendas	[]	{"prb_pre":"170.00","est_pre":true,"cod_categorias_pre":1,"cod_marcas_pre":10}	["prb_pre","est_pre","cod_categorias_pre","cod_marcas_pre"]	Auditoria critica created en prendas para registro #11: prb_pre, est_pre, cod_categorias_pre, cod_marcas_pre.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-10 02:41:27	2026-06-10 02:41:27
30	1	prenda_variantes	11	created	variantes	[]	{"pve_prv":"170.00","est_prv":true,"cod_colores_prv":9,"cod_tallas_prv":5,"sku_prv":"ABR-00011"}	["pve_prv","est_prv","cod_colores_prv","cod_tallas_prv","sku_prv"]	Auditoria critica created en prenda_variantes para registro #11: pve_prv, est_prv, cod_colores_prv, cod_tallas_prv, sku_prv.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-10 02:41:27	2026-06-10 02:41:27
31	1	inventarios	11	created	inventario	[]	{"cdi_inv":1,"cre_inv":0,"ubi_inv":"TIENDA"}	["cdi_inv","cre_inv","ubi_inv"]	Auditoria critica created en inventarios para registro #11: cdi_inv, cre_inv, ubi_inv.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/catalogo/prendas	2026-06-10 02:41:27	2026-06-10 02:41:27
32	5	ventas	9	created	ventas	[]	{"tot_ven":"170.00","est_ven":"en_revision","mpa_ven":"qr","est_logistico_ven":"pendiente_revision"}	["tot_ven","est_ven","mpa_ven","est_logistico_ven"]	Auditoria critica created en ventas para registro #9: tot_ven, est_ven, mpa_ven, est_logistico_ven.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	/compras/online	2026-06-10 02:42:12	2026-06-10 02:42:12
33	5	inventarios	11	updated	inventario	{"cdi_inv":1,"cre_inv":0}	{"cdi_inv":0,"cre_inv":1}	["cdi_inv","cre_inv"]	Auditoria critica updated en inventarios para registro #11: cdi_inv, cre_inv.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	/compras/online	2026-06-10 02:42:12	2026-06-10 02:42:12
34	5	detalle_ventas	10	created	ventas	[]	{"can_dve":1,"pun_dve":"170.00","sub_dve":"170.00"}	["can_dve","pun_dve","sub_dve"]	Auditoria critica created en detalle_ventas para registro #10: can_dve, pun_dve, sub_dve.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	/compras/online	2026-06-10 02:42:12	2026-06-10 02:42:12
35	5	venta_pagos	5	created	pagos	[]	{"monto_declarado_vpg":"170.00","monto_verificado_vpg":"0.00","est_vpg":"pendiente_revision","metodo_pago_vpg":"qr"}	["monto_declarado_vpg","monto_verificado_vpg","est_vpg","metodo_pago_vpg"]	Auditoria critica created en venta_pagos para registro #5: monto_declarado_vpg, monto_verificado_vpg, est_vpg, metodo_pago_vpg.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	/compras/online	2026-06-10 02:42:12	2026-06-10 02:42:12
36	1	inventarios	11	updated	inventario	{"cre_inv":1}	{"cre_inv":0}	["cre_inv"]	Auditoria critica updated en inventarios para registro #11: cre_inv.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/comercial/ventas/9	2026-06-10 02:42:39	2026-06-10 02:42:39
37	1	ventas	9	updated	ventas	{"est_ven":"en_revision","est_logistico_ven":"pendiente_revision"}	{"est_ven":"registrada","est_logistico_ven":"lista_recojo"}	["est_ven","est_logistico_ven"]	Auditoria critica updated en ventas para registro #9: est_ven, est_logistico_ven.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/comercial/ventas/9	2026-06-10 02:42:39	2026-06-10 02:42:39
38	1	venta_pagos	5	updated	pagos	{"monto_verificado_vpg":"0.00","est_vpg":"pendiente_revision","obs_vpg":null}	{"monto_verificado_vpg":"170.00","est_vpg":"confirmado","obs_vpg":"ninguna"}	["monto_verificado_vpg","est_vpg","obs_vpg"]	Auditoria critica updated en venta_pagos para registro #5: monto_verificado_vpg, est_vpg, obs_vpg.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/comercial/ventas/9	2026-06-10 02:42:39	2026-06-10 02:42:39
39	1	ventas	9	updated	ventas	{"est_logistico_ven":"lista_recojo","fec_entrega_ven":null}	{"est_logistico_ven":"entregada","fec_entrega_ven":"2026-06-10T02:42:49+00:00"}	["est_logistico_ven","fec_entrega_ven"]	Auditoria critica updated en ventas para registro #9: est_logistico_ven, fec_entrega_ven.	critical	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	/superadmin/comercial/ventas/9/entregar	2026-06-10 02:42:49	2026-06-10 02:42:49
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

COPY public.categorias (id_categoria, nom_cat, des_cat, cod_categoria_padre_cat, est_cat, fec_cat, created_at, updated_at, tip_tal_cat, gru_tal_cat) FROM stdin;
11	POLERA	\N	\N	t	2026-04-15 04:08:21	2026-04-15 04:08:21	2026-04-15 04:08:21	ALFA	\N
1	ABRIGOS	Abrigos, capas y piezas de exterior.	\N	t	2026-05-12 19:45:21	2026-04-14 22:54:14	2026-05-12 19:45:21	ALFA	SUPERIOR
2	BLAZERS	Sastreria ligera y blazers de estructura definida.	\N	t	2026-05-12 19:45:21	2026-04-14 22:54:14	2026-05-12 19:45:21	ALFA	SUPERIOR
3	CAMISAS	Camisas y piezas de botonadura.	\N	t	2026-05-12 19:45:21	2026-04-14 22:54:14	2026-05-12 19:45:21	ALFA	SUPERIOR
4	BLUSAS	Blusas fluidas y piezas de caida suave.	\N	t	2026-05-12 19:45:21	2026-04-14 22:54:14	2026-05-12 19:45:21	ALFA	SUPERIOR
5	VESTIDOS	Vestidos de linea diaria, evento y ocasion.	\N	t	2026-05-12 19:45:21	2026-04-14 22:54:14	2026-05-12 19:45:21	ALFA	SUPERIOR
6	FALDAS	Faldas para look diario, oficina o ocasion.	\N	t	2026-05-12 19:45:21	2026-04-14 22:54:14	2026-05-12 19:45:21	NUMERICA	INFERIOR
7	PANTALONES	Pantalones sastreros, rectos y de silueta amplia.	\N	t	2026-05-12 19:45:21	2026-04-14 22:54:14	2026-05-12 19:45:21	NUMERICA	INFERIOR
8	POLOS Y KNITWEAR	Punto ligero, knitwear y polos de base.	\N	t	2026-05-12 19:45:21	2026-04-14 22:54:14	2026-05-12 19:45:21	ALFA	SUPERIOR
9	ACCESORIOS	Complementos de styling y piezas de apoyo.	\N	t	2026-05-12 19:45:21	2026-04-14 22:54:14	2026-05-12 19:45:21	UNICA	GENERAL
10	CONJUNTOS	Sets coordinados y propuestas armadas.	\N	t	2026-05-12 19:45:21	2026-04-14 22:54:14	2026-05-12 19:45:21	ALFA	SUPERIOR
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
13	AZUL PETROLEO	#18378B	t	2026-05-13 02:19:32	2026-05-13 02:19:32	2026-05-13 02:19:32	AZUL	#1D4ED8	700
14	AMARILLO	#F5DC1E	t	2026-05-13 02:36:42	2026-05-13 02:36:42	2026-05-13 02:36:42	AMARILLO	#EAB308	1100
\.


--
-- Data for Name: database_backups; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.database_backups (id, nombre_bak, archivo_bak, ruta_bak, disco_bak, tamano_bak, estado_bak, mensaje_bak, cod_usuario_bak, fec_bak, created_at, updated_at) FROM stdin;
1	Backup manual 04/06/2026 16:03:07	mati_boutique_backup_20260604_160307.sql	backups/database/mati_boutique_backup_20260604_160307.sql	local	119034	exitoso	Backup generado correctamente.	1	2026-06-04 16:03:08	2026-06-04 16:03:07	2026-06-04 16:03:08
2	Backup manual 04/06/2026 18:57:32	mati_boutique_backup_20260604_185732.sql	backups/database/mati_boutique_backup_20260604_185732.sql	local	121102	exitoso	Backup generado correctamente.	1	2026-06-04 18:57:32	2026-06-04 18:57:32	2026-06-04 18:57:32
\.


--
-- Data for Name: detalle_reservas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.detalle_reservas (id_detalle_reserva, cod_reservas_dre, cod_prenda_variantes_dre, can_dre, fec_dre, created_at, updated_at, pun_dre, sub_dre) FROM stdin;
1	1	3	2	2026-05-18 04:39:05	2026-05-18 04:39:05	2026-05-18 04:39:05	50.00	100.00
2	1	2	1	2026-05-18 04:39:05	2026-05-18 04:39:05	2026-05-18 04:39:05	300.00	300.00
3	2	3	1	2026-05-18 15:58:15	2026-05-18 15:58:15	2026-05-18 15:58:15	50.00	50.00
4	3	5	5	2026-05-18 21:06:19	2026-05-18 21:06:19	2026-05-18 21:06:19	500.00	2500.00
5	4	3	1	2026-05-18 21:17:43	2026-05-18 21:17:43	2026-05-18 21:17:43	50.00	50.00
6	5	3	1	2026-05-19 01:12:23	2026-05-19 01:12:23	2026-05-19 01:12:23	50.00	50.00
7	6	3	1	2026-05-24 00:08:16	2026-05-24 00:08:16	2026-05-24 00:08:16	50.00	50.00
8	7	6	3	2026-05-24 20:13:12	2026-05-24 20:13:12	2026-05-24 20:13:12	500.00	1500.00
9	8	1	1	2026-05-25 00:58:09	2026-05-25 00:58:09	2026-05-25 00:58:09	700.00	700.00
\.


--
-- Data for Name: detalle_ventas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.detalle_ventas (id_detalle_venta, cod_ventas_dve, cod_prenda_variantes_dve, can_dve, pun_dve, sub_dve, fec_dve, created_at, updated_at) FROM stdin;
1	1	3	2	50.00	100.00	2026-05-18 15:36:24	2026-05-18 15:36:24	2026-05-18 15:36:24
2	1	2	1	300.00	300.00	2026-05-18 15:36:24	2026-05-18 15:36:24	2026-05-18 15:36:24
3	2	5	5	500.00	2500.00	2026-05-18 21:16:15	2026-05-18 21:16:15	2026-05-18 21:16:15
4	3	3	1	50.00	50.00	2026-05-18 21:18:17	2026-05-18 21:18:17	2026-05-18 21:18:17
5	4	3	1	50.00	50.00	2026-05-19 01:13:10	2026-05-19 01:13:10	2026-05-19 01:13:10
6	5	3	1	50.00	50.00	2026-05-19 01:13:40	2026-05-19 01:13:40	2026-05-19 01:13:40
7	6	6	2	500.00	1000.00	2026-05-24 19:56:52	2026-05-24 19:56:52	2026-05-24 19:56:52
8	7	8	1	150.00	150.00	2026-06-05 00:43:33	2026-06-05 00:43:33	2026-06-05 00:43:33
9	8	10	1	150.00	150.00	2026-06-10 01:49:27	2026-06-10 01:49:27	2026-06-10 01:49:27
10	9	11	1	170.00	170.00	2026-06-10 02:42:12	2026-06-10 02:42:12	2026-06-10 02:42:12
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
7	5	\N	productos/5/02175538047-a4o-rmgbrfuz.jpg	/storage/productos/5/02175538047-a4o-rmgbrfuz.jpg	t	0	catalogo	2026-05-13 02:37:23	2026-05-13 02:37:23	2026-05-13 02:37:23
8	6	\N	productos/6/pantalon-zara-kkmxdr6w.jpg	/storage/productos/6/pantalon-zara-kkmxdr6w.jpg	t	0	catalogo	2026-05-24 19:27:48	2026-05-24 19:27:48	2026-05-24 19:27:48
9	7	\N	productos/7/camiseta-regular-fit-2qzruear.jpg	/storage/productos/7/camiseta-regular-fit-2qzruear.jpg	t	0	catalogo	2026-06-04 19:58:35	2026-06-04 19:58:35	2026-06-04 19:58:35
10	8	\N	productos/8/polo-regular-fit-dakkgsyh.jpg	/storage/productos/8/polo-regular-fit-dakkgsyh.jpg	t	0	catalogo	2026-06-04 22:18:18	2026-06-04 22:18:18	2026-06-04 22:18:18
11	9	\N	productos/9/polo-regular-fit-tido44pz.jpg	/storage/productos/9/polo-regular-fit-tido44pz.jpg	t	0	catalogo	2026-06-05 00:40:11	2026-06-05 00:40:11	2026-06-05 00:40:11
12	10	\N	productos/10/jersey-regular-fit-izqg4a1z.jpg	/storage/productos/10/jersey-regular-fit-izqg4a1z.jpg	t	0	catalogo	2026-06-10 01:47:45	2026-06-10 01:47:45	2026-06-10 01:47:45
13	11	\N	productos/11/jersey-alpaca-frueyyc3.jpg	/storage/productos/11/jersey-alpaca-frueyyc3.jpg	t	0	catalogo	2026-06-10 02:41:27	2026-06-10 02:41:27	2026-06-10 02:41:27
\.


--
-- Data for Name: inventario_movimientos; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.inventario_movimientos (id, cod_inventario_inm, cod_prenda_variante_inm, tipo_movimiento_inm, cantidad_inm, cdi_antes_inm, cdi_despues_inm, cre_antes_inm, cre_despues_inm, ubicacion_antes_inm, ubicacion_despues_inm, referencia_tipo_inm, referencia_id_inm, cod_usuario_inm, descripcion_inm, metadata_inm, created_at, updated_at) FROM stdin;
1	7	7	entrada	1	0	1	0	0	TIENDA	TIENDA	prenda_variante	7	1	Stock inicial registrado para variante.	{"inventario_id":7,"variante_id":7,"ubicacion_antes":null,"ubicacion_despues":"TIENDA","origen":"ficha_prenda"}	2026-06-04 19:58:35	2026-06-04 19:58:35
2	8	8	entrada	1	0	1	0	0	TIENDA	TIENDA	prenda_variante	8	1	Stock inicial registrado para variante.	{"inventario_id":8,"variante_id":8,"ubicacion_antes":null,"ubicacion_despues":"TIENDA","origen":"ficha_prenda"}	2026-06-04 22:18:18	2026-06-04 22:18:18
3	9	9	entrada	1	0	1	0	0	TIENDA	TIENDA	prenda_variante	9	1	Stock inicial registrado para variante.	{"inventario_id":9,"variante_id":9,"ubicacion_antes":null,"ubicacion_despues":"TIENDA","origen":"ficha_prenda"}	2026-06-05 00:40:11	2026-06-05 00:40:11
4	8	8	venta_en_revision	1	1	0	0	1	TIENDA	TIENDA	venta	7	5	Venta online enviada a revision: stock separado.	{"venta_id":7,"numero_venta":"VEN-20260605-0001","estado":"en_revision","variante_id":8,"sku":"POL-00008","ubicacion_antes":"TIENDA","ubicacion_despues":"TIENDA"}	2026-06-05 00:43:33	2026-06-05 00:43:33
5	8	8	venta	1	0	0	1	0	TIENDA	TIENDA	venta	7	1	Venta online confirmada: stock comprometido consumido.	{"venta_id":7,"numero_venta":"VEN-20260605-0001","tipo_venta":"online","estado_venta":"en_revision","variante_id":8,"sku":"POL-00008","ubicacion_antes":"TIENDA","ubicacion_despues":"TIENDA"}	2026-06-05 00:44:14	2026-06-05 00:44:14
6	10	10	entrada	1	0	1	0	0	TIENDA	TIENDA	prenda_variante	10	1	Stock inicial registrado para variante.	{"inventario_id":10,"variante_id":10,"ubicacion_antes":null,"ubicacion_despues":"TIENDA","origen":"ficha_prenda"}	2026-06-10 01:47:45	2026-06-10 01:47:45
7	10	10	venta_en_revision	1	1	0	0	1	TIENDA	TIENDA	venta	8	5	Venta online enviada a revision: stock separado.	{"venta_id":8,"numero_venta":"VEN-20260610-0001","estado":"en_revision","variante_id":10,"sku":"ABR-00010","ubicacion_antes":"TIENDA","ubicacion_despues":"TIENDA"}	2026-06-10 01:49:27	2026-06-10 01:49:27
8	10	10	venta	1	0	0	1	0	TIENDA	TIENDA	venta	8	1	Venta online confirmada: stock comprometido consumido.	{"venta_id":8,"numero_venta":"VEN-20260610-0001","tipo_venta":"online","estado_venta":"en_revision","variante_id":10,"sku":"ABR-00010","ubicacion_antes":"TIENDA","ubicacion_despues":"TIENDA"}	2026-06-10 01:50:04	2026-06-10 01:50:04
9	11	11	entrada	1	0	1	0	0	TIENDA	TIENDA	prenda_variante	11	1	Stock inicial registrado para variante.	{"inventario_id":11,"variante_id":11,"ubicacion_antes":null,"ubicacion_despues":"TIENDA","origen":"ficha_prenda"}	2026-06-10 02:41:27	2026-06-10 02:41:27
10	11	11	venta_en_revision	1	1	0	0	1	TIENDA	TIENDA	venta	9	5	Venta online enviada a revision: stock separado.	{"venta_id":9,"numero_venta":"VEN-20260610-0002","estado":"en_revision","variante_id":11,"sku":"ABR-00011","ubicacion_antes":"TIENDA","ubicacion_despues":"TIENDA"}	2026-06-10 02:42:12	2026-06-10 02:42:12
11	11	11	venta	1	0	0	1	0	TIENDA	TIENDA	venta	9	1	Venta online confirmada: stock comprometido consumido.	{"venta_id":9,"numero_venta":"VEN-20260610-0002","tipo_venta":"online","estado_venta":"en_revision","variante_id":11,"sku":"ABR-00011","ubicacion_antes":"TIENDA","ubicacion_despues":"TIENDA"}	2026-06-10 02:42:39	2026-06-10 02:42:39
\.


--
-- Data for Name: inventarios; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.inventarios (id_inventario, cod_prenda_variantes_inv, cdi_inv, cre_inv, ubi_inv, created_at, updated_at) FROM stdin;
4	4	1	0	DEPOSITO	2026-05-05 20:01:45	2026-05-05 20:01:45
2	2	0	0	TIENDA	2026-04-15 03:42:01	2026-05-18 04:41:16
5	5	0	0	TIENDA	2026-05-13 02:37:23	2026-05-18 21:16:15
3	3	3	2	TIENDA	2026-04-15 04:09:48	2026-05-24 00:08:16
6	6	0	3	DEPOSITO	2026-05-24 19:27:48	2026-05-24 20:13:12
1	1	0	1	TIENDA	2026-04-15 01:03:22	2026-05-25 00:58:09
7	7	1	0	TIENDA	2026-06-04 19:58:35	2026-06-04 19:58:35
9	9	1	0	TIENDA	2026-06-05 00:40:11	2026-06-05 00:40:11
8	8	0	0	TIENDA	2026-06-04 22:18:18	2026-06-05 00:44:14
10	10	0	0	TIENDA	2026-06-10 01:47:45	2026-06-10 01:50:04
11	11	0	0	TIENDA	2026-06-10 02:41:27	2026-06-10 02:42:39
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
25	2026_05_12_194145_add_size_group_to_categorias_table	3
26	2026_05_12_202039_add_segment_to_tallas_table	4
27	2026_05_18_002816_add_business_fields_to_reservas_table	5
28	2026_05_18_002857_add_price_fields_to_detalle_reservas_table	5
29	2026_05_18_002913_create_reserva_pagos_table	5
30	2026_05_19_005453_create_venta_pagos_table	6
31	2026_05_19_005508_make_vendedor_nullable_on_ventas_table	6
32	2026_05_24_210710_create_notifications_table	7
33	2026_05_24_210849_add_pickup_logistics_fields_to_ventas_table	7
34	2026_05_24_210919_add_logistics_status_to_reservas_table	7
35	2026_06_04_000000_create_system_logs_table	8
36	2026_06_04_010000_create_audit_logs_table	9
37	2026_06_04_020000_create_database_backups_table	10
38	2026_06_04_030000_create_inventario_movimientos_table	11
39	2026_06_05_120000_create_prenda_danio_reportes_table	12
40	2026_06_05_120100_create_prenda_danio_imagenes_table	12
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.notifications (id, type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at) FROM stdin;
1daae199-d6fd-4d61-9056-eedc2bde9ccc	App\\Notifications\\CommercialNotification	App\\Models\\User	2	{"title":"Nueva reserva pendiente de revisi\\u00f3n","message":"La reserva RES-20260525-0001 tiene un comprobante pendiente de revisi\\u00f3n.","type":"pago_pendiente","url":"\\/superadmin\\/comercial\\/reservas\\/8","icon":"receipt","severity":"warning","entity_type":"reserva","entity_id":8}	\N	2026-05-25 00:58:09	2026-05-25 00:58:09
058f9ef0-4a56-4353-8d40-ab2a830b7bc5	App\\Notifications\\CommercialNotification	App\\Models\\User	8	{"title":"Nueva reserva pendiente de revisi\\u00f3n","message":"La reserva RES-20260525-0001 tiene un comprobante pendiente de revisi\\u00f3n.","type":"pago_pendiente","url":"\\/superadmin\\/comercial\\/reservas\\/8","icon":"receipt","severity":"warning","entity_type":"reserva","entity_id":8}	\N	2026-05-25 00:58:09	2026-05-25 00:58:09
c8b268af-3e87-4848-a531-8ee9753eeeb2	App\\Notifications\\CommercialNotification	App\\Models\\User	1	{"title":"Nueva reserva pendiente de revisi\\u00f3n","message":"La reserva RES-20260525-0001 tiene un comprobante pendiente de revisi\\u00f3n.","type":"pago_pendiente","url":"\\/superadmin\\/comercial\\/reservas\\/8","icon":"receipt","severity":"warning","entity_type":"reserva","entity_id":8}	2026-05-25 00:58:21	2026-05-25 00:58:09	2026-05-25 00:58:21
884a7d5d-e330-47ea-8103-89d4d3ddeeb5	App\\Notifications\\CommercialNotification	App\\Models\\User	2	{"title":"Nueva compra online pendiente de revisi\\u00f3n","message":"La compra VEN-20260605-0001 tiene un comprobante pendiente de revisi\\u00f3n.","type":"pago_pendiente","url":"\\/superadmin\\/comercial\\/ventas\\/7","icon":"receipt","severity":"warning","entity_type":"venta","entity_id":7}	\N	2026-06-05 00:43:33	2026-06-05 00:43:33
cdd8c23e-6e76-4645-85d4-8fa6aeb3dd38	App\\Notifications\\CommercialNotification	App\\Models\\User	8	{"title":"Nueva compra online pendiente de revisi\\u00f3n","message":"La compra VEN-20260605-0001 tiene un comprobante pendiente de revisi\\u00f3n.","type":"pago_pendiente","url":"\\/superadmin\\/comercial\\/ventas\\/7","icon":"receipt","severity":"warning","entity_type":"venta","entity_id":7}	\N	2026-06-05 00:43:33	2026-06-05 00:43:33
dc5147cd-41e8-4ce3-a054-68677d7ca93d	App\\Notifications\\CommercialNotification	App\\Models\\User	1	{"title":"Nueva compra online pendiente de revisi\\u00f3n","message":"La compra VEN-20260605-0001 tiene un comprobante pendiente de revisi\\u00f3n.","type":"pago_pendiente","url":"\\/superadmin\\/comercial\\/ventas\\/7","icon":"receipt","severity":"warning","entity_type":"venta","entity_id":7}	2026-06-05 15:37:37	2026-06-05 00:43:33	2026-06-05 15:37:37
641fa17e-f0fb-4a61-b84a-dfab361ece04	App\\Notifications\\CommercialNotification	App\\Models\\User	1	{"title":"Nueva compra online pendiente de revisi\\u00f3n","message":"La compra VEN-20260610-0001 tiene un comprobante pendiente de revisi\\u00f3n.","type":"pago_pendiente","url":"\\/superadmin\\/comercial\\/ventas\\/8","icon":"receipt","severity":"warning","entity_type":"venta","entity_id":8}	\N	2026-06-10 01:49:27	2026-06-10 01:49:27
0e3c3e59-7b82-480a-8713-09a4680ad061	App\\Notifications\\CommercialNotification	App\\Models\\User	2	{"title":"Nueva compra online pendiente de revisi\\u00f3n","message":"La compra VEN-20260610-0001 tiene un comprobante pendiente de revisi\\u00f3n.","type":"pago_pendiente","url":"\\/superadmin\\/comercial\\/ventas\\/8","icon":"receipt","severity":"warning","entity_type":"venta","entity_id":8}	\N	2026-06-10 01:49:27	2026-06-10 01:49:27
1644c417-b054-472c-88c4-022193debf34	App\\Notifications\\CommercialNotification	App\\Models\\User	8	{"title":"Nueva compra online pendiente de revisi\\u00f3n","message":"La compra VEN-20260610-0001 tiene un comprobante pendiente de revisi\\u00f3n.","type":"pago_pendiente","url":"\\/superadmin\\/comercial\\/ventas\\/8","icon":"receipt","severity":"warning","entity_type":"venta","entity_id":8}	\N	2026-06-10 01:49:27	2026-06-10 01:49:27
dbd4ffdc-a97b-42ee-a2ed-88a71c53f9a5	App\\Notifications\\CommercialNotification	App\\Models\\User	1	{"title":"Nueva compra online pendiente de revisi\\u00f3n","message":"La compra VEN-20260610-0002 tiene un comprobante pendiente de revisi\\u00f3n.","type":"pago_pendiente","url":"\\/superadmin\\/comercial\\/ventas\\/9","icon":"receipt","severity":"warning","entity_type":"venta","entity_id":9}	\N	2026-06-10 02:42:12	2026-06-10 02:42:12
28f69e94-8b45-4a38-b914-606f4e652198	App\\Notifications\\CommercialNotification	App\\Models\\User	2	{"title":"Nueva compra online pendiente de revisi\\u00f3n","message":"La compra VEN-20260610-0002 tiene un comprobante pendiente de revisi\\u00f3n.","type":"pago_pendiente","url":"\\/superadmin\\/comercial\\/ventas\\/9","icon":"receipt","severity":"warning","entity_type":"venta","entity_id":9}	\N	2026-06-10 02:42:12	2026-06-10 02:42:12
f1a1b110-c3de-45c5-8cfe-1650fadf243d	App\\Notifications\\CommercialNotification	App\\Models\\User	5	{"title":"Compra entregada","message":"Tu compra VEN-20260610-0001 fue entregada correctamente.","type":"venta_entregada","url":"\\/cuenta\\/pedidos","icon":"shopping-bag","severity":"success","entity_type":"venta","entity_id":8}	2026-06-10 23:12:14	2026-06-10 01:50:44	2026-06-10 23:12:14
7f14d26a-999e-41b3-b0b1-b475ec874059	App\\Notifications\\CommercialNotification	App\\Models\\User	5	{"title":"Compra confirmada","message":"Tu compra VEN-20260610-0001 fue confirmada y ya est\\u00e1 lista para recojo.","type":"venta_lista_recojo","url":"\\/cuenta\\/pedidos","icon":"check-circle","severity":"success","entity_type":"venta","entity_id":8}	2026-06-10 23:12:14	2026-06-10 01:50:04	2026-06-10 23:12:14
3625bfc2-2f58-47aa-a50d-1424cd618f41	App\\Notifications\\CommercialNotification	App\\Models\\User	5	{"title":"Compra enviada a revisi\\u00f3n","message":"Tu compra VEN-20260610-0001 fue enviada a revisi\\u00f3n.","type":"venta_creada","url":"\\/cuenta\\/pedidos","icon":"shopping-bag","severity":"info","entity_type":"venta","entity_id":8}	2026-06-10 23:12:14	2026-06-10 01:49:27	2026-06-10 23:12:14
24388057-aef5-4493-a503-df9b70c923f9	App\\Notifications\\CommercialNotification	App\\Models\\User	5	{"title":"Compra entregada","message":"Tu compra VEN-20260605-0001 fue entregada correctamente.","type":"venta_entregada","url":"\\/cuenta\\/pedidos","icon":"shopping-bag","severity":"success","entity_type":"venta","entity_id":7}	2026-06-10 23:12:14	2026-06-05 15:37:48	2026-06-10 23:12:14
6592af35-56eb-4382-80a3-126adcffbaef	App\\Notifications\\CommercialNotification	App\\Models\\User	5	{"title":"Compra enviada a revisi\\u00f3n","message":"Tu compra VEN-20260605-0001 fue enviada a revisi\\u00f3n.","type":"venta_creada","url":"\\/cuenta\\/pedidos","icon":"shopping-bag","severity":"info","entity_type":"venta","entity_id":7}	2026-06-10 23:12:14	2026-06-05 00:43:33	2026-06-10 23:12:14
48e0810f-62e8-40a1-80ca-5d04b1928ca9	App\\Notifications\\CommercialNotification	App\\Models\\User	5	{"title":"Reserva confirmada","message":"Tu reserva RES-20260525-0001 fue confirmada y ya est\\u00e1 lista para recojo.","type":"reserva_lista_recojo","url":"\\/cuenta\\/reservas","icon":"check-circle","severity":"success","entity_type":"reserva","entity_id":8}	2026-06-10 23:12:14	2026-05-25 00:59:09	2026-06-10 23:12:14
a982d34c-e736-4f34-a5ae-a8e175665653	App\\Notifications\\CommercialNotification	App\\Models\\User	5	{"title":"Reserva enviada a revisi\\u00f3n","message":"Tu reserva RES-20260525-0001 fue enviada a revisi\\u00f3n.","type":"reserva_creada","url":"\\/cuenta\\/reservas","icon":"calendar","severity":"info","entity_type":"reserva","entity_id":8}	2026-06-10 23:12:14	2026-05-25 00:58:09	2026-06-10 23:12:14
c9ee6473-be56-4c2b-8feb-c2e793874e4a	App\\Notifications\\CommercialNotification	App\\Models\\User	8	{"title":"Nueva compra online pendiente de revisi\\u00f3n","message":"La compra VEN-20260610-0002 tiene un comprobante pendiente de revisi\\u00f3n.","type":"pago_pendiente","url":"\\/superadmin\\/comercial\\/ventas\\/9","icon":"receipt","severity":"warning","entity_type":"venta","entity_id":9}	\N	2026-06-10 02:42:12	2026-06-10 02:42:12
320c043a-6383-428d-9177-8a01f023afd6	App\\Notifications\\CommercialNotification	App\\Models\\User	5	{"title":"Compra entregada","message":"Tu compra VEN-20260610-0002 fue entregada correctamente.","type":"venta_entregada","url":"\\/cuenta\\/pedidos","icon":"shopping-bag","severity":"success","entity_type":"venta","entity_id":9}	2026-06-10 23:12:14	2026-06-10 02:42:49	2026-06-10 23:12:14
cb4d4b7a-e6d7-4dff-991e-6492fa65e5a0	App\\Notifications\\CommercialNotification	App\\Models\\User	5	{"title":"Compra confirmada","message":"Tu compra VEN-20260610-0002 fue confirmada y ya est\\u00e1 lista para recojo.","type":"venta_lista_recojo","url":"\\/cuenta\\/pedidos","icon":"check-circle","severity":"success","entity_type":"venta","entity_id":9}	2026-06-10 23:12:14	2026-06-10 02:42:39	2026-06-10 23:12:14
8b7bd6a2-a92d-467c-9ba8-3ecb1a9058bd	App\\Notifications\\CommercialNotification	App\\Models\\User	5	{"title":"Compra enviada a revisi\\u00f3n","message":"Tu compra VEN-20260610-0002 fue enviada a revisi\\u00f3n.","type":"venta_creada","url":"\\/cuenta\\/pedidos","icon":"shopping-bag","severity":"info","entity_type":"venta","entity_id":9}	2026-06-10 23:12:14	2026-06-10 02:42:12	2026-06-10 23:12:14
5efa15f0-ea61-4319-ae24-b9e416f48c0b	App\\Notifications\\CommercialNotification	App\\Models\\User	5	{"title":"Compra confirmada","message":"Tu compra VEN-20260605-0001 fue confirmada y ya est\\u00e1 lista para recojo.","type":"venta_lista_recojo","url":"\\/cuenta\\/pedidos","icon":"check-circle","severity":"success","entity_type":"venta","entity_id":7}	2026-06-10 23:12:14	2026-06-05 00:44:14	2026-06-10 23:12:14
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: prenda_danio_imagenes; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.prenda_danio_imagenes (id, cod_prenda_danio_reporte_pdi, ruta_pdi, url_pdi, descripcion_pdi, orden_pdi, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: prenda_danio_reportes; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.prenda_danio_reportes (id, codigo_pdr, nombre_prenda_pdr, descripcion_prenda_pdr, cod_prenda_pdr, cod_prenda_variante_pdr, cod_inventario_pdr, cod_usuario_reporta_pdr, cod_usuario_revisa_pdr, causa_pdr, tipo_danio_pdr, severidad_pdr, cantidad_pdr, estado_pdr, decision_pdr, observacion_pdr, observacion_revision_pdr, fecha_reporte_pdr, fecha_revision_pdr, afecta_inventario_pdr, metadata_pdr, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: prenda_variantes; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.prenda_variantes (id_prenda_variante, cod_prendas_prv, cod_colores_prv, cod_tallas_prv, sku_prv, cob_prv, pve_prv, cos_prv, est_prv, fec_prv, created_at, updated_at) FROM stdin;
1	1	4	5	POL-00001	\N	700.00	\N	t	2026-04-15 01:03:22	2026-04-15 01:03:22	2026-04-15 01:03:22
2	2	4	11	PAN-00002	\N	300.00	\N	t	2026-04-15 03:42:01	2026-04-15 03:42:01	2026-04-15 03:42:01
3	3	4	5	POL-00003	\N	50.00	\N	t	2026-04-15 04:09:48	2026-04-15 04:09:48	2026-04-15 04:09:48
4	4	5	4	POL-00004	\N	50.00	\N	t	2026-05-05 20:01:45	2026-05-05 20:01:45	2026-05-05 20:01:45
5	5	14	5	POL-00005	\N	500.00	\N	t	2026-05-13 02:37:23	2026-05-13 02:37:23	2026-05-13 02:37:23
6	6	5	12	PAN-00006	\N	500.00	\N	t	2026-05-24 19:27:48	2026-05-24 19:27:48	2026-05-24 19:27:48
7	7	13	5	CAM-00007	\N	150.00	\N	t	2026-06-04 19:58:35	2026-06-04 19:58:35	2026-06-04 19:58:35
8	8	5	6	POL-00008	\N	150.00	\N	t	2026-06-04 22:18:18	2026-06-04 22:18:18	2026-06-04 22:18:18
9	9	4	5	POL-00009	\N	150.00	\N	t	2026-06-05 00:40:11	2026-06-05 00:40:11	2026-06-05 00:40:11
10	10	9	5	ABR-00010	\N	150.00	\N	t	2026-06-10 01:47:45	2026-06-10 01:47:45	2026-06-10 01:47:45
11	11	9	5	ABR-00011	\N	170.00	\N	t	2026-06-10 02:41:27	2026-06-10 02:41:27	2026-06-10 02:41:27
\.


--
-- Data for Name: prendas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.prendas (id_prenda, nom_pre, des_pre, cod_categorias_pre, cod_marcas_pre, cod_temporadas_pre, gen_pre, prb_pre, est_pre, cod_usuario_registra_pre, fec_pre, created_at, updated_at) FROM stdin;
1	SOBRECAMISA REGULAR FIT	SOBRECAMISA REGULAR FIT ZARA	8	1	5	hombre	700.00	t	1	2026-04-15 01:03:22	2026-04-15 01:03:22	2026-04-15 01:03:22
2	PANTALÓN SUPER BAGGY CON LINO	Pantalón super baggy con lino bershka	7	2	5	hombre	300.00	t	1	2026-04-15 03:42:01	2026-04-15 03:42:01	2026-04-15 03:42:01
3	CAMISETA RELAXED FIT PRINT	Camiseta relaxed fit print BERSHKA	11	2	5	hombre	50.00	t	1	2026-04-15 04:09:48	2026-04-15 04:09:48	2026-04-15 04:09:48
4	POLERA	polera marca patito	11	11	5	unisex	50.00	t	1	2026-05-05 20:01:45	2026-05-05 20:01:45	2026-05-05 20:01:45
5	CAMISETA SLIM FIT PRINT	Camiseta slim fit print Brazil	11	2	7	hombre	500.00	t	1	2026-05-13 02:37:23	2026-05-13 02:37:23	2026-05-13 02:37:23
6	PANTALÓN CHINO STRAIGHT FIT ALGODÓN - LINO	Pantalón straight fit confeccionado en tejido con algodón y lino. Bolsillos frontales y detalle de bolsillos traseros de vivo con botón. Cierre frontal con cremallera y gancho metálico oculto.	7	1	5	hombre	500.00	t	1	2026-05-24 19:27:48	2026-05-24 19:27:48	2026-05-24 19:27:48
7	CAMISA REGULAR FIT 100 LINO ÍNDIGO	Camisa regular fit confeccionada en tejido de lino tintado en índigo. Cuello solapa y manga larga acabada en puño con botón. Cierre frontal de botonadura.	3	1	5	hombre	150.00	t	1	2026-06-04 19:58:35	2026-06-04 19:58:35	2026-06-04 19:58:35
8	POLO PUNTO REGULAR FIT 100 LINO	Polo regular fit de punto tejido en hilatura de lino (excepto elásticos). Cuello solapa con abertura frontal. Manga corta. Acabados en rib.	8	1	5	hombre	150.00	t	1	2026-06-04 22:18:18	2026-06-04 22:18:18	2026-06-04 22:18:18
9	POLO	Polo temporada carnavalera	8	1	6	hombre	150.00	t	1	2026-06-05 00:40:11	2026-06-05 00:40:11	2026-06-05 00:40:11
10	JERSEY	Jersey	1	10	5	hombre	150.00	t	1	2026-06-10 01:47:45	2026-06-10 01:47:45	2026-06-10 01:47:45
11	JERSEY ALPACA	jersey	1	10	5	hombre	170.00	t	1	2026-06-10 02:41:27	2026-06-10 02:41:27	2026-06-10 02:41:27
\.


--
-- Data for Name: reserva_pagos; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.reserva_pagos (id_reserva_pago, cod_reservas_rpg, monto_declarado_rpg, monto_confirmado_rpg, porcentaje_rpg, metodo_pago_rpg, comprobante_ruta_rpg, comprobante_url_rpg, est_rpg, cod_usuario_verifica_rpg, fec_verificacion_rpg, obs_rpg, fec_rpg, created_at, updated_at) FROM stdin;
1	1	250.00	250.00	62.50	qr	reservas/comprobantes/J6LGqYIB9HcoV1GdOcBF6e6W2kv3MgNME9Zv5w4H.png	http://localhost/storage/reservas/comprobantes/J6LGqYIB9HcoV1GdOcBF6e6W2kv3MgNME9Zv5w4H.png	confirmado	1	2026-05-18 04:40:54	ninguna	2026-05-18 04:39:05	2026-05-18 04:39:05	2026-05-18 04:40:54
2	1	150.00	150.00	37.50	efectivo	\N	\N	confirmado	1	2026-05-18 15:36:24	nada	2026-05-18 15:36:24	2026-05-18 15:36:24	2026-05-18 15:36:24
3	2	26.00	26.00	52.00	qr	reservas/comprobantes/fVLERXD6Wc8eCdBpJmmpiLWunvpWQYcp6TJpypQ2.png	http://localhost/storage/reservas/comprobantes/fVLERXD6Wc8eCdBpJmmpiLWunvpWQYcp6TJpypQ2.png	confirmado	1	2026-05-18 16:00:06	PAGO BIEN	2026-05-18 15:58:15	2026-05-18 15:58:15	2026-05-18 16:00:06
4	3	1250.00	2250.00	90.00	qr	reservas/comprobantes/JOqQZMNuKWRc3wECNQR6zw9EJOIw6NhNOsbpTVxK.png	http://localhost/storage/reservas/comprobantes/JOqQZMNuKWRc3wECNQR6zw9EJOIw6NhNOsbpTVxK.png	confirmado	1	2026-05-18 21:07:05	dfhgfdh	2026-05-18 21:06:19	2026-05-18 21:06:19	2026-05-18 21:07:05
5	3	250.00	250.00	10.00	efectivo	\N	\N	confirmado	1	2026-05-18 21:16:15	ninguna	2026-05-18 21:16:15	2026-05-18 21:16:15	2026-05-18 21:16:15
6	4	50.00	50.00	100.00	qr	reservas/comprobantes/nP7ryh0zTUwIVcEVorVjVXQgYdMRQbNufPWF0Un8.png	http://localhost/storage/reservas/comprobantes/nP7ryh0zTUwIVcEVorVjVXQgYdMRQbNufPWF0Un8.png	confirmado	1	2026-05-18 21:18:11	ninguna	2026-05-18 21:17:43	2026-05-18 21:17:43	2026-05-18 21:18:11
7	5	25.00	25.00	50.00	qr	reservas/comprobantes/Qv3iFLuaWCEJFqC5DxsWsEwbQrx6Nz5Ldgqz4QhG.png	http://localhost/storage/reservas/comprobantes/Qv3iFLuaWCEJFqC5DxsWsEwbQrx6Nz5Ldgqz4QhG.png	confirmado	1	2026-05-19 01:12:54	ninguna	2026-05-19 01:12:23	2026-05-19 01:12:23	2026-05-19 01:12:54
8	5	25.00	25.00	50.00	efectivo	\N	\N	confirmado	1	2026-05-19 01:13:09	\N	2026-05-19 01:13:09	2026-05-19 01:13:09	2026-05-19 01:13:09
9	6	25.00	25.00	50.00	qr	reservas/comprobantes/X9wY8rtj7k7TA9eQTNtJPnUmN6ATg4rXWC3ufdPp.png	http://localhost/storage/reservas/comprobantes/X9wY8rtj7k7TA9eQTNtJPnUmN6ATg4rXWC3ufdPp.png	confirmado	1	2026-05-24 00:09:01	ninguna	2026-05-24 00:08:16	2026-05-24 00:08:16	2026-05-24 00:09:01
10	7	750.00	0.00	50.00	qr	reservas/comprobantes/D2yeynrZlb07RnXlLBa0hXZ9W5FCLco9mKEE2NRT.png	http://localhost/storage/reservas/comprobantes/D2yeynrZlb07RnXlLBa0hXZ9W5FCLco9mKEE2NRT.png	pendiente_revision	\N	\N	\N	2026-05-24 20:13:12	2026-05-24 20:13:12	2026-05-24 20:13:12
11	8	500.00	350.00	50.00	qr	reservas/comprobantes/y3eM5iQVgpT0b4L8t8SwoPG0g2LpvrljnfYTBbrK.png	http://localhost/storage/reservas/comprobantes/y3eM5iQVgpT0b4L8t8SwoPG0g2LpvrljnfYTBbrK.png	confirmado	1	2026-05-25 00:59:09	ninguna	2026-05-25 00:58:09	2026-05-25 00:58:09	2026-05-25 00:59:09
\.


--
-- Data for Name: reservas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.reservas (id_reserva, nre_res, cod_usuario_cliente_res, cod_usuario_responsable_res, est_res, ven_res, obs_res, fec_res, created_at, updated_at, sub_res, des_res, tot_res, monto_pagado_res, saldo_res, est_pago_res, fec_disponible_recojo_res, fec_limite_recojo_res, punto_recojo_res, origen_stock_res, cod_usuario_verifica_pago_res, fec_verificacion_pago_res, obs_pago_res, est_logistico_res, fec_recogida_res, cod_usuario_entrega_res) FROM stdin;
1	RES-20260518-0001	5	5	completada	2026-06-17 23:59:59	nada	2026-05-18 04:39:05	2026-05-18 04:39:05	2026-05-18 15:36:24	400.00	0.00	400.00	400.00	0.00	pagado_total	2026-05-18 00:00:00	2026-06-17 23:59:59	Recojo en tienda Mati Boutique	TIENDA	1	2026-05-18 15:36:24	nada	pendiente_revision	\N	\N
2	RES-20260518-0002	5	5	lista_recojo	2026-06-17 23:59:59	\N	2026-05-18 15:58:15	2026-05-18 15:58:15	2026-05-18 16:00:12	50.00	0.00	50.00	26.00	24.00	anticipo_confirmado	2026-05-18 00:00:00	2026-06-17 23:59:59	Recojo en tienda Mati Boutique	TIENDA	1	2026-05-18 16:00:06	PAGO BIEN	pendiente_revision	\N	\N
3	RES-20260518-0003	5	5	completada	2026-06-17 23:59:59	ninguna	2026-05-18 21:06:19	2026-05-18 21:06:19	2026-05-18 21:16:15	2500.00	0.00	2500.00	2500.00	0.00	pagado_total	2026-05-18 00:00:00	2026-06-17 23:59:59	Recojo en tienda Mati Boutique	TIENDA	1	2026-05-18 21:16:15	ninguna	pendiente_revision	\N	\N
4	RES-20260518-0004	5	5	completada	2026-06-17 23:59:59	\N	2026-05-18 21:17:43	2026-05-18 21:17:43	2026-05-18 21:18:17	50.00	0.00	50.00	50.00	0.00	pagado_total	2026-05-18 00:00:00	2026-06-17 23:59:59	Recojo en tienda Mati Boutique	TIENDA	1	2026-05-18 21:18:11	ninguna	pendiente_revision	\N	\N
5	RES-20260519-0001	5	5	completada	2026-06-18 23:59:59	\N	2026-05-19 01:12:23	2026-05-19 01:12:23	2026-05-19 01:13:10	50.00	0.00	50.00	50.00	0.00	pagado_total	2026-05-19 00:00:00	2026-06-18 23:59:59	Recojo en tienda Mati Boutique	TIENDA	1	2026-05-19 01:13:09	ninguna	pendiente_revision	\N	\N
6	RES-20260524-0001	5	5	lista_recojo	2026-06-23 23:59:59	\N	2026-05-24 00:08:16	2026-05-24 00:08:16	2026-05-24 00:09:22	50.00	0.00	50.00	25.00	25.00	anticipo_confirmado	2026-05-24 00:00:00	2026-06-23 23:59:59	Recojo en tienda Mati Boutique	TIENDA	1	2026-05-24 00:09:01	ninguna	pendiente_revision	\N	\N
7	RES-20260524-0002	3	3	por_procesar	2026-06-26 23:59:59	\N	2026-05-24 20:13:12	2026-05-24 20:13:12	2026-05-24 20:13:12	1500.00	0.00	1500.00	0.00	1500.00	pendiente_revision	2026-05-27 00:00:00	2026-06-26 23:59:59	Recojo en tienda Mati Boutique	DEPOSITO	\N	\N	\N	pendiente_revision	\N	\N
8	RES-20260525-0001	5	5	confirmada	2026-06-24 23:59:59	\N	2026-05-25 00:58:09	2026-05-25 00:58:09	2026-05-25 00:59:09	700.00	0.00	700.00	350.00	350.00	anticipo_confirmado	2026-05-25 00:00:00	2026-06-24 23:59:59	Recojo en tienda Mati Boutique	TIENDA	1	2026-05-25 00:59:09	ninguna	lista_recojo	\N	\N
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
ku9iHA9einuNLF0b4xQy18jsnalP0YMKyohRdRlj	\N	172.19.0.1	curl/8.5.0	eyJfdG9rZW4iOiJTbWRTVnh2U2N2bUJtaXoyQTMwbFZKV3BhZlN6S042bWd5dDFMMFM2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=	1781558032
0DfwGgFmlKJ25bhgGIhsQNP4TWQukJtAjbeQ1Hbz	\N	172.19.0.1	curl/8.5.0	eyJfdG9rZW4iOiJYSFVPdFZvTUxsaDFkcXlmY3lsSGlRejBrVXRzcndUdzMxdjUwdWhGIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvc3VwZXJhZG1pbiIsInJvdXRlIjoic3VwZXJhZG1pbi5kYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJ1cmwiOnsiaW50ZW5kZWQiOiJodHRwOlwvXC9sb2NhbGhvc3RcL3N1cGVyYWRtaW4ifX0=	1781562261
ByG1nMZussUJYqGqMgC1TbyeteFqMWUvx6lvcbyh	\N	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0	eyJfdG9rZW4iOiJsOGphM1VGbkpRSHg3SVdnaVR5NkVGMHZ0NldvMDEzd3V2aVBrWmJEIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19	1781570454
0XnN0AzhJ1h5XxLuxpnXGUrDMpw6o0zYXDXXX0zp	\N	172.19.0.1	curl/8.5.0	eyJfdG9rZW4iOiJ6eUd3MzRkY1JpUldwcjBLVFg0NnprYkJzOHJPQTVjWHp4REF4TjAyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvc3VwZXJhZG1pbiIsInJvdXRlIjoic3VwZXJhZG1pbi5kYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJ1cmwiOnsiaW50ZW5kZWQiOiJodHRwOlwvXC9sb2NhbGhvc3RcL3N1cGVyYWRtaW4ifX0=	1781562285
kFReG4htV9QrLzwRp2FgljHDFjPx80JeCV1YJ9mN	\N	172.19.0.1	curl/8.5.0	eyJfdG9rZW4iOiJSVHBmSkRPR0V4SGsxTkY5dThWd2FjMTcyazJVbDZVTVJEWlBxWlQ0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvc2FuY3R1bVwvY3NyZi1jb29raWUiLCJyb3V0ZSI6InNhbmN0dW0uY3NyZi1jb29raWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==	1781558064
PQmIhGUOYEBxwa5Zg6CfcUPhk51BNJZnUwlwKI65	2	172.19.0.1	curl/8.5.0	eyJfdG9rZW4iOiJFbENIbXRwOXExT2luRU1EeVY5VU1DOFUxczdEV1FtclhaWUxrc0Q1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvdmVuZGVkb3JcL3JlcG9ydGVzXC9leHBvcnRhcj9kYXRlX2Zyb209MjAyNi0wNi0wMSZkYXRlX3RvPTIwMjYtMDYtMTUiLCJyb3V0ZSI6InZlbmRlZG9yLnJlcG9ydGVzLmV4cG9ydCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoyfQ==	1781558079
Nr7R6CgzrGeZyBrUGiy78kgwcVt0AkzVUq80MBwF	2	172.19.0.1	curl/8.5.0	eyJfdG9rZW4iOiI5RXZ4R2hYT0k0SnlhWE1neHFqd2R3MmlFV0tGc0NaS3gyeTlHM2JGIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvdmVuZGVkb3JcL3JlcG9ydGVzXC9leHBvcnRhcj9kYXRlX2Zyb209MjAyNi0wNi0wMSZkYXRlX3RvPTIwMjYtMDYtMTUiLCJyb3V0ZSI6InZlbmRlZG9yLnJlcG9ydGVzLmV4cG9ydCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoyfQ==	1781558093
JrULIPWxAftwSlAfQ3vULGcbdpRq6KuySZIg3Llx	\N	172.19.0.1	curl/8.5.0	eyJfdG9rZW4iOiJKcUtLQ21RSjlDVGp5TTlHR0w5MGlmVFBQNVI1RDMyckJrTUp2NlFMIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvc2FuY3R1bVwvY3NyZi1jb29raWUiLCJyb3V0ZSI6InNhbmN0dW0uY3NyZi1jb29raWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==	1781562268
fJ3aXRzdWiRUOLOqR9xtrBlvSV4eayY7DLvi5lWC	\N	172.19.0.1	curl/8.5.0	eyJfdG9rZW4iOiJ6NngyaXJ1bXRoemZJeEl2YTJwSVgwVzJ1MFM1anJXUkVqeEhwUG9NIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvc3VwZXJhZG1pbiIsInJvdXRlIjoic3VwZXJhZG1pbi5kYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJ1cmwiOnsiaW50ZW5kZWQiOiJodHRwOlwvXC9sb2NhbGhvc3RcL3N1cGVyYWRtaW4ifX0=	1781562299
Z5NuYGz8vRBUCTXvGfYGobXdfhsG48mbXXEcVLox	2	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	eyJfdG9rZW4iOiJZbjBIWGE2RmlmNUgwenY0d0V5ak1PN3o2UWltUXV5dUFwcTRqdWJBIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbG9jYWxob3N0XC92ZW5kZWRvclwvcmVwb3J0ZXM/ZGF0ZV9mcm9tPTIwMjYtMDYtMDEmZGF0ZV90bz0yMDI2LTA2LTE1Iiwicm91dGUiOiJ2ZW5kZWRvci5yZXBvcnRlcy5pbmRleCJ9LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6Mn0=	1781562747
g71hzZb9kW9u4VImrtV1cOPxzat1wPa7oUdlJjM7	5	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	eyJfdG9rZW4iOiJiaUdWVXJpWUFHMHBmeUxkTHRDUHVPUmo5MUt4dXVnQ3dFdVRlUGpYIiwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjUsIl9mbGFzaCI6eyJuZXciOltdLCJvbGQiOltdfSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvcHJvZmlsZSIsInJvdXRlIjoicHJvZmlsZS5lZGl0In19	1781571296
\.


--
-- Data for Name: system_logs; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.system_logs (id, cod_usuario_slg, rol_slg, modulo_slg, accion_slg, entidad_tipo_slg, entidad_id_slg, descripcion_slg, nivel_slg, ip_slg, user_agent_slg, metodo_http_slg, ruta_slg, metadata_slg, created_at, updated_at) FROM stdin;
1	5	cliente	autenticacion	inicio_sesion	user	5	Inicio de sesion exitoso.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/login	{"destino_intencionado":"http:\\/\\/localhost\\/superadmin\\/logs"}	2026-06-04 15:42:10	2026-06-04 15:42:10
2	1	superadmin	autenticacion	inicio_sesion	user	1	Inicio de sesion exitoso.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	POST	/login	{"destino_intencionado":"http:\\/\\/localhost\\/superadmin\\/backups"}	2026-06-04 16:00:11	2026-06-04 16:00:11
3	1	superadmin	backups	backup_generado	database_backup	1	Backup de base de datos generado correctamente.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	POST	/superadmin/backups/generar	{"backup_id":1,"filename":"mati_boutique_backup_20260604_160307.sql","tamano_bytes":119034,"estado":"exitoso"}	2026-06-04 16:03:08	2026-06-04 16:03:08
4	1	superadmin	backups	backup_descargado	database_backup	1	Backup de base de datos descargado.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	GET	/superadmin/backups/1/descargar	{"backup_id":1,"filename":"mati_boutique_backup_20260604_160307.sql","tamano_bytes":119034,"estado":"exitoso"}	2026-06-04 16:06:00	2026-06-04 16:06:00
5	1	superadmin	backups	backup_descargado	database_backup	1	Backup de base de datos descargado.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/backups/1/descargar	{"backup_id":1,"filename":"mati_boutique_backup_20260604_160307.sql","tamano_bytes":119034,"estado":"exitoso"}	2026-06-04 16:06:56	2026-06-04 16:06:56
6	1	superadmin	backups	backup_generado	database_backup	2	Backup de base de datos generado correctamente.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/backups/generar	{"backup_id":2,"filename":"mati_boutique_backup_20260604_185732.sql","tamano_bytes":121102,"estado":"exitoso"}	2026-06-04 18:57:32	2026-06-04 18:57:32
7	1	superadmin	backups	backup_descargado	database_backup	2	Backup de base de datos descargado.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/backups/2/descargar	{"backup_id":2,"filename":"mati_boutique_backup_20260604_185732.sql","tamano_bytes":121102,"estado":"exitoso"}	2026-06-04 18:57:36	2026-06-04 18:57:36
8	1	superadmin	autenticacion	inicio_sesion	user	1	Inicio de sesion exitoso.	info	172.18.0.1	curl/8.5.0	POST	/login	{"destino_intencionado":null}	2026-06-04 19:00:11	2026-06-04 19:00:11
9	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	curl/8.5.0	GET	/superadmin/reportes	{"seccion":"ventas","filtros":{"section":"ventas"}}	2026-06-04 19:00:11	2026-06-04 19:00:11
10	1	superadmin	autenticacion	inicio_sesion	user	1	Inicio de sesion exitoso.	info	172.18.0.1	curl/8.5.0	POST	/login	{"destino_intencionado":null}	2026-06-04 19:00:29	2026-06-04 19:00:29
11	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	curl/8.5.0	GET	/superadmin/reportes	{"seccion":"ventas","filtros":{"section":"ventas"}}	2026-06-04 19:00:29	2026-06-04 19:00:29
12	1	superadmin	exportaciones	csv_exportado	\N	\N	CSV exportado: Reporte de ventas.	info	172.18.0.1	curl/8.5.0	GET	/superadmin/reportes/exportar/ventas.csv	{"tipo":"ventas","filename":"mati-boutique-ventas-20260604-190115.csv","filas":0,"filtros":{"period":"mes_actual","sale_status":"registrada","section":"ventas"}}	2026-06-04 19:01:15	2026-06-04 19:01:15
13	1	superadmin	reportes	vista_imprimible_abierta	\N	\N	Vista imprimible abierta: Reporte de ventas.	info	172.18.0.1	curl/8.5.0	GET	/superadmin/reportes/imprimir/ventas	{"tipo":"ventas","filas":0,"filtros":{"period":"mes_actual","sale_status":"registrada","section":"ventas"}}	2026-06-04 19:01:15	2026-06-04 19:01:15
14	1	superadmin	autenticacion	inicio_sesion	user	1	Inicio de sesion exitoso.	info	172.18.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; es-BO) WindowsPowerShell/5.1.26100.8457	POST	/login	{"destino_intencionado":null}	2026-06-04 19:33:24	2026-06-04 19:33:24
15	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; es-BO) WindowsPowerShell/5.1.26100.8457	GET	/superadmin/inventario/movimientos	{"filtros":{"tipo_movimiento":"entrada"}}	2026-06-04 19:33:24	2026-06-04 19:33:24
16	1	superadmin	autenticacion	inicio_sesion	user	1	Inicio de sesion exitoso.	info	172.18.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; es-BO) WindowsPowerShell/5.1.26100.8457	POST	/login	{"destino_intencionado":null}	2026-06-04 19:33:40	2026-06-04 19:33:40
17	1	superadmin	inventario	exportacion_kardex	\N	\N	CSV de Kardex exportado.	info	172.18.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; es-BO) WindowsPowerShell/5.1.26100.8457	GET	/superadmin/inventario/movimientos/exportar.csv	{"filename":"mati-boutique-kardex-20260604-193340.csv","filas":0,"filtros":{"tipo_movimiento":"entrada"}}	2026-06-04 19:33:40	2026-06-04 19:33:40
18	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-04 19:39:03	2026-06-04 19:39:03
19	1	superadmin	variantes	variante_creada	prenda_variante	7	Variante creada desde ficha de prenda: CAM-00007.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"variante_id":7,"prenda_id":7,"sku":"CAM-00007","codigo_barra":null,"precio_venta":"150.00","activa":true}	2026-06-04 19:58:35	2026-06-04 19:58:35
20	1	superadmin	inventario	movimiento_kardex	inventario_movimiento	1	Movimiento Kardex registrado: entrada para inventario #7.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"movimiento_id":1,"inventario_id":7,"variante_id":7,"tipo_movimiento":"entrada","cantidad":1,"referencia_tipo":"prenda_variante","referencia_id":7}	2026-06-04 19:58:35	2026-06-04 19:58:35
21	1	superadmin	inventario	stock_actualizado_manual	inventario	7	Stock inicial registrado para variante. Variante #7.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"inventario_id":7,"variante_id":7,"stock_disponible":1,"stock_reservado":0,"ubicacion":"TIENDA"}	2026-06-04 19:58:35	2026-06-04 19:58:35
22	1	superadmin	imagenes	imagen_agregada	imagen_prenda	9	Imagen principal agregada a prenda.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"imagen_id":9,"prenda_id":7,"variante_id":null,"principal":true,"orden":0,"fuente":"catalogo"}	2026-06-04 19:58:35	2026-06-04 19:58:35
23	1	superadmin	prendas	prenda_creada	prenda	7	Prenda creado: CAMISA REGULAR FIT 100 LINO ÍNDIGO.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"recurso":"catalogo.prendas","registro_id":7,"registro":"CAMISA REGULAR FIT 100 LINO \\u00cdNDIGO"}	2026-06-04 19:58:35	2026-06-04 19:58:35
24	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-04 21:41:36	2026-06-04 21:41:36
25	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-04 21:42:04	2026-06-04 21:42:04
26	1	superadmin	variantes	variante_creada	prenda_variante	8	Variante creada desde ficha de prenda: POL-00008.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"variante_id":8,"prenda_id":8,"sku":"POL-00008","codigo_barra":null,"precio_venta":"150.00","activa":true}	2026-06-04 22:18:18	2026-06-04 22:18:18
27	1	superadmin	inventario	movimiento_kardex	inventario_movimiento	2	Movimiento Kardex registrado: entrada para inventario #8.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"movimiento_id":2,"inventario_id":8,"variante_id":8,"tipo_movimiento":"entrada","cantidad":1,"referencia_tipo":"prenda_variante","referencia_id":8}	2026-06-04 22:18:18	2026-06-04 22:18:18
28	1	superadmin	inventario	stock_actualizado_manual	inventario	8	Stock inicial registrado para variante. Variante #8.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"inventario_id":8,"variante_id":8,"stock_disponible":1,"stock_reservado":0,"ubicacion":"TIENDA"}	2026-06-04 22:18:18	2026-06-04 22:18:18
29	1	superadmin	imagenes	imagen_agregada	imagen_prenda	10	Imagen principal agregada a prenda.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"imagen_id":10,"prenda_id":8,"variante_id":null,"principal":true,"orden":0,"fuente":"catalogo"}	2026-06-04 22:18:18	2026-06-04 22:18:18
30	1	superadmin	prendas	prenda_creada	prenda	8	Prenda creado: POLO PUNTO REGULAR FIT 100 LINO.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"recurso":"catalogo.prendas","registro_id":8,"registro":"POLO PUNTO REGULAR FIT 100 LINO"}	2026-06-04 22:18:18	2026-06-04 22:18:18
31	1	superadmin	prendas	prenda_actualizada	prenda	8	Prenda actualizado: POLO PUNTO REGULAR FIT 100 LINO.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	PUT	/superadmin/catalogo/prendas/8	{"recurso":"catalogo.prendas","registro_id":8,"registro":"POLO PUNTO REGULAR FIT 100 LINO"}	2026-06-04 22:18:20	2026-06-04 22:18:20
32	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-04 22:18:32	2026-06-04 22:18:32
33	1	superadmin	inventario	exportacion_kardex	\N	\N	CSV de Kardex exportado.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos/exportar.csv	{"filename":"mati-boutique-kardex-20260604-221841.csv","filas":2,"filtros":[]}	2026-06-04 22:18:41	2026-06-04 22:18:41
34	1	superadmin	variantes	variante_creada	prenda_variante	9	Variante creada desde ficha de prenda: POL-00009.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"variante_id":9,"prenda_id":9,"sku":"POL-00009","codigo_barra":null,"precio_venta":"150.00","activa":true}	2026-06-05 00:40:11	2026-06-05 00:40:11
35	1	superadmin	inventario	movimiento_kardex	inventario_movimiento	3	Movimiento Kardex registrado: entrada para inventario #9.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"movimiento_id":3,"inventario_id":9,"variante_id":9,"tipo_movimiento":"entrada","cantidad":1,"referencia_tipo":"prenda_variante","referencia_id":9}	2026-06-05 00:40:11	2026-06-05 00:40:11
36	1	superadmin	inventario	stock_actualizado_manual	inventario	9	Stock inicial registrado para variante. Variante #9.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"inventario_id":9,"variante_id":9,"stock_disponible":1,"stock_reservado":0,"ubicacion":"TIENDA"}	2026-06-05 00:40:11	2026-06-05 00:40:11
37	1	superadmin	imagenes	imagen_agregada	imagen_prenda	11	Imagen principal agregada a prenda.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"imagen_id":11,"prenda_id":9,"variante_id":null,"principal":true,"orden":0,"fuente":"catalogo"}	2026-06-05 00:40:11	2026-06-05 00:40:11
38	1	superadmin	prendas	prenda_creada	prenda	9	Prenda creado: POLO.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"recurso":"catalogo.prendas","registro_id":9,"registro":"POLO"}	2026-06-05 00:40:11	2026-06-05 00:40:11
39	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 00:41:07	2026-06-05 00:41:07
40	\N	\N	autenticacion	inicio_sesion_fallido	\N	\N	Intento fallido de inicio de sesion.	warning	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/login	{"email_intentado":"limber.caso123@gmail.com","motivo":"credenciales_invalidas"}	2026-06-05 00:42:14	2026-06-05 00:42:14
41	5	cliente	autenticacion	inicio_sesion	user	5	Inicio de sesion exitoso.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/login	{"destino_intencionado":null}	2026-06-05 00:42:18	2026-06-05 00:42:18
42	5	cliente	inventario	movimiento_kardex	inventario_movimiento	4	Movimiento Kardex registrado: venta_en_revision para inventario #8.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/compras/online	{"movimiento_id":4,"inventario_id":8,"variante_id":8,"tipo_movimiento":"venta_en_revision","cantidad":1,"referencia_tipo":"venta","referencia_id":7}	2026-06-05 00:43:33	2026-06-05 00:43:33
43	5	cliente	ventas	compra_online_enviada_revision	venta	7	Compra online enviada a revision: VEN-20260605-0001.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/compras/online	{"venta_id":7,"numero_venta":"VEN-20260605-0001","cliente_id":5,"total":150,"estado":"en_revision","items":1}	2026-06-05 00:43:33	2026-06-05 00:43:33
44	5	cliente	pagos	comprobante_subido	venta_pago	3	Comprobante subido para compra online: VEN-20260605-0001.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/compras/online	{"venta_id":7,"numero_venta":"VEN-20260605-0001","monto_declarado":150,"metodo_pago":"qr","estado":"pendiente_revision"}	2026-06-05 00:43:33	2026-06-05 00:43:33
45	1	superadmin	inventario	movimiento_kardex	inventario_movimiento	5	Movimiento Kardex registrado: venta para inventario #8.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	PUT	/superadmin/comercial/ventas/7	{"movimiento_id":5,"inventario_id":8,"variante_id":8,"tipo_movimiento":"venta","cantidad":1,"referencia_tipo":"venta","referencia_id":7}	2026-06-05 00:44:14	2026-06-05 00:44:14
46	1	superadmin	ventas	venta_confirmada	venta	7	Venta online confirmada: VEN-20260605-0001.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	PUT	/superadmin/comercial/ventas/7	{"venta_id":7,"numero_venta":"VEN-20260605-0001","cliente_id":5,"vendedor_id":1,"tipo":"online","estado":"registrada","estado_logistico":"lista_recojo","total":150,"metodo_pago":"qr","monto_verificado":150}	2026-06-05 00:44:14	2026-06-05 00:44:14
47	1	superadmin	pagos	pago_confirmado	venta_pago	3	Pago confirmado para venta: VEN-20260605-0001.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	PUT	/superadmin/comercial/ventas/7	{"venta_id":7,"numero_venta":"VEN-20260605-0001","monto_verificado":150,"metodo_pago":"qr","estado":"confirmado"}	2026-06-05 00:44:14	2026-06-05 00:44:14
48	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"resumen","filtros":{"section":"resumen"}}	2026-06-05 00:45:33	2026-06-05 00:45:33
49	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"ventas","filtros":{"section":"ventas"}}	2026-06-05 00:45:45	2026-06-05 00:45:45
50	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 15:32:01	2026-06-05 15:32:01
51	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 15:34:40	2026-06-05 15:34:40
52	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 15:34:41	2026-06-05 15:34:41
53	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 15:34:41	2026-06-05 15:34:41
54	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 15:34:42	2026-06-05 15:34:42
55	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 15:34:42	2026-06-05 15:34:42
56	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 15:34:42	2026-06-05 15:34:42
57	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 15:34:42	2026-06-05 15:34:42
58	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 15:34:43	2026-06-05 15:34:43
59	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 15:34:43	2026-06-05 15:34:43
60	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 15:34:43	2026-06-05 15:34:43
61	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-05 15:35:18	2026-06-05 15:35:18
62	1	superadmin	logistica	venta_entregada	venta	7	Venta entregada: VEN-20260605-0001.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/comercial/ventas/7/entregar	{"venta_id":7,"numero_venta":"VEN-20260605-0001","cliente_id":5,"estado_logistico":"entregada","punto_recojo":"Recojo en tienda Mati Boutique","usuario_entrega_id":1}	2026-06-05 15:37:48	2026-06-05 15:37:48
63	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"resumen","filtros":{"section":"resumen"}}	2026-06-05 15:38:59	2026-06-05 15:38:59
64	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"reservas","filtros":{"section":"reservas"}}	2026-06-05 15:39:02	2026-06-05 15:39:02
65	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"clientes","filtros":{"section":"clientes"}}	2026-06-05 15:42:25	2026-06-05 15:42:25
66	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"clientes","filtros":{"section":"clientes"}}	2026-06-05 18:03:04	2026-06-05 18:03:04
67	5	cliente	autenticacion	inicio_sesion	user	5	Inicio de sesion exitoso.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/login	{"destino_intencionado":null}	2026-06-08 23:13:06	2026-06-08 23:13:06
68	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-08 23:14:40	2026-06-08 23:14:40
69	5	cliente	autenticacion	inicio_sesion	user	5	Inicio de sesion exitoso.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/login	{"destino_intencionado":null}	2026-06-09 23:40:14	2026-06-09 23:40:14
70	5	cliente	autenticacion	cierre_sesion	user	5	Cierre de sesion.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/logout	[]	2026-06-09 23:40:20	2026-06-09 23:40:20
71	5	cliente	autenticacion	inicio_sesion	user	5	Inicio de sesion exitoso.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/login	{"destino_intencionado":null}	2026-06-09 23:44:56	2026-06-09 23:44:56
72	1	superadmin	variantes	variante_creada	prenda_variante	10	Variante creada desde ficha de prenda: ABR-00010.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"variante_id":10,"prenda_id":10,"sku":"ABR-00010","codigo_barra":null,"precio_venta":"150.00","activa":true}	2026-06-10 01:47:45	2026-06-10 01:47:45
73	1	superadmin	inventario	movimiento_kardex	inventario_movimiento	6	Movimiento Kardex registrado: entrada para inventario #10.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"movimiento_id":6,"inventario_id":10,"variante_id":10,"tipo_movimiento":"entrada","cantidad":1,"referencia_tipo":"prenda_variante","referencia_id":10}	2026-06-10 01:47:45	2026-06-10 01:47:45
74	1	superadmin	inventario	stock_actualizado_manual	inventario	10	Stock inicial registrado para variante. Variante #10.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"inventario_id":10,"variante_id":10,"stock_disponible":1,"stock_reservado":0,"ubicacion":"TIENDA"}	2026-06-10 01:47:45	2026-06-10 01:47:45
75	1	superadmin	imagenes	imagen_agregada	imagen_prenda	12	Imagen principal agregada a prenda.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"imagen_id":12,"prenda_id":10,"variante_id":null,"principal":true,"orden":0,"fuente":"catalogo"}	2026-06-10 01:47:45	2026-06-10 01:47:45
76	1	superadmin	prendas	prenda_creada	prenda	10	Prenda creado: JERSEY.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"recurso":"catalogo.prendas","registro_id":10,"registro":"JERSEY"}	2026-06-10 01:47:45	2026-06-10 01:47:45
77	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-10 01:48:07	2026-06-10 01:48:07
78	5	cliente	inventario	movimiento_kardex	inventario_movimiento	7	Movimiento Kardex registrado: venta_en_revision para inventario #10.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/compras/online	{"movimiento_id":7,"inventario_id":10,"variante_id":10,"tipo_movimiento":"venta_en_revision","cantidad":1,"referencia_tipo":"venta","referencia_id":8}	2026-06-10 01:49:27	2026-06-10 01:49:27
79	5	cliente	ventas	compra_online_enviada_revision	venta	8	Compra online enviada a revision: VEN-20260610-0001.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/compras/online	{"venta_id":8,"numero_venta":"VEN-20260610-0001","cliente_id":5,"total":150,"estado":"en_revision","items":1}	2026-06-10 01:49:27	2026-06-10 01:49:27
80	5	cliente	pagos	comprobante_subido	venta_pago	4	Comprobante subido para compra online: VEN-20260610-0001.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/compras/online	{"venta_id":8,"numero_venta":"VEN-20260610-0001","monto_declarado":150,"metodo_pago":"qr","estado":"pendiente_revision"}	2026-06-10 01:49:27	2026-06-10 01:49:27
81	1	superadmin	inventario	movimiento_kardex	inventario_movimiento	8	Movimiento Kardex registrado: venta para inventario #10.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	PUT	/superadmin/comercial/ventas/8	{"movimiento_id":8,"inventario_id":10,"variante_id":10,"tipo_movimiento":"venta","cantidad":1,"referencia_tipo":"venta","referencia_id":8}	2026-06-10 01:50:04	2026-06-10 01:50:04
82	1	superadmin	ventas	venta_confirmada	venta	8	Venta online confirmada: VEN-20260610-0001.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	PUT	/superadmin/comercial/ventas/8	{"venta_id":8,"numero_venta":"VEN-20260610-0001","cliente_id":5,"vendedor_id":1,"tipo":"online","estado":"registrada","estado_logistico":"lista_recojo","total":150,"metodo_pago":"qr","monto_verificado":150}	2026-06-10 01:50:04	2026-06-10 01:50:04
83	1	superadmin	pagos	pago_confirmado	venta_pago	4	Pago confirmado para venta: VEN-20260610-0001.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	PUT	/superadmin/comercial/ventas/8	{"venta_id":8,"numero_venta":"VEN-20260610-0001","monto_verificado":150,"metodo_pago":"qr","estado":"confirmado"}	2026-06-10 01:50:04	2026-06-10 01:50:04
84	1	superadmin	logistica	venta_entregada	venta	8	Venta entregada: VEN-20260610-0001.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/comercial/ventas/8/entregar	{"venta_id":8,"numero_venta":"VEN-20260610-0001","cliente_id":5,"estado_logistico":"entregada","punto_recojo":"Recojo en tienda Mati Boutique","usuario_entrega_id":1}	2026-06-10 01:50:44	2026-06-10 01:50:44
85	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"resumen","filtros":{"section":"resumen"}}	2026-06-10 01:50:58	2026-06-10 01:50:58
86	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"reservas","filtros":{"section":"reservas"}}	2026-06-10 01:51:05	2026-06-10 01:51:05
87	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"inventario","filtros":{"section":"inventario"}}	2026-06-10 01:51:09	2026-06-10 01:51:09
88	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"clientes","filtros":{"section":"clientes"}}	2026-06-10 01:51:12	2026-06-10 01:51:12
89	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-10 02:38:42	2026-06-10 02:38:42
90	1	superadmin	variantes	variante_creada	prenda_variante	11	Variante creada desde ficha de prenda: ABR-00011.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"variante_id":11,"prenda_id":11,"sku":"ABR-00011","codigo_barra":null,"precio_venta":"170.00","activa":true}	2026-06-10 02:41:27	2026-06-10 02:41:27
91	1	superadmin	inventario	movimiento_kardex	inventario_movimiento	9	Movimiento Kardex registrado: entrada para inventario #11.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"movimiento_id":9,"inventario_id":11,"variante_id":11,"tipo_movimiento":"entrada","cantidad":1,"referencia_tipo":"prenda_variante","referencia_id":11}	2026-06-10 02:41:27	2026-06-10 02:41:27
92	1	superadmin	inventario	stock_actualizado_manual	inventario	11	Stock inicial registrado para variante. Variante #11.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"inventario_id":11,"variante_id":11,"stock_disponible":1,"stock_reservado":0,"ubicacion":"TIENDA"}	2026-06-10 02:41:27	2026-06-10 02:41:27
93	1	superadmin	imagenes	imagen_agregada	imagen_prenda	13	Imagen principal agregada a prenda.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"imagen_id":13,"prenda_id":11,"variante_id":null,"principal":true,"orden":0,"fuente":"catalogo"}	2026-06-10 02:41:27	2026-06-10 02:41:27
94	1	superadmin	prendas	prenda_creada	prenda	11	Prenda creado: JERSEY ALPACA.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/catalogo/prendas	{"recurso":"catalogo.prendas","registro_id":11,"registro":"JERSEY ALPACA"}	2026-06-10 02:41:27	2026-06-10 02:41:27
95	5	cliente	inventario	movimiento_kardex	inventario_movimiento	10	Movimiento Kardex registrado: venta_en_revision para inventario #11.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/compras/online	{"movimiento_id":10,"inventario_id":11,"variante_id":11,"tipo_movimiento":"venta_en_revision","cantidad":1,"referencia_tipo":"venta","referencia_id":9}	2026-06-10 02:42:12	2026-06-10 02:42:12
96	5	cliente	ventas	compra_online_enviada_revision	venta	9	Compra online enviada a revision: VEN-20260610-0002.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/compras/online	{"venta_id":9,"numero_venta":"VEN-20260610-0002","cliente_id":5,"total":170,"estado":"en_revision","items":1}	2026-06-10 02:42:12	2026-06-10 02:42:12
97	5	cliente	pagos	comprobante_subido	venta_pago	5	Comprobante subido para compra online: VEN-20260610-0002.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	POST	/compras/online	{"venta_id":9,"numero_venta":"VEN-20260610-0002","monto_declarado":170,"metodo_pago":"qr","estado":"pendiente_revision"}	2026-06-10 02:42:12	2026-06-10 02:42:12
98	1	superadmin	inventario	movimiento_kardex	inventario_movimiento	11	Movimiento Kardex registrado: venta para inventario #11.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	PUT	/superadmin/comercial/ventas/9	{"movimiento_id":11,"inventario_id":11,"variante_id":11,"tipo_movimiento":"venta","cantidad":1,"referencia_tipo":"venta","referencia_id":9}	2026-06-10 02:42:39	2026-06-10 02:42:39
99	1	superadmin	ventas	venta_confirmada	venta	9	Venta online confirmada: VEN-20260610-0002.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	PUT	/superadmin/comercial/ventas/9	{"venta_id":9,"numero_venta":"VEN-20260610-0002","cliente_id":5,"vendedor_id":1,"tipo":"online","estado":"registrada","estado_logistico":"lista_recojo","total":170,"metodo_pago":"qr","monto_verificado":170}	2026-06-10 02:42:39	2026-06-10 02:42:39
100	1	superadmin	pagos	pago_confirmado	venta_pago	5	Pago confirmado para venta: VEN-20260610-0002.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	PUT	/superadmin/comercial/ventas/9	{"venta_id":9,"numero_venta":"VEN-20260610-0002","monto_verificado":170,"metodo_pago":"qr","estado":"confirmado"}	2026-06-10 02:42:39	2026-06-10 02:42:39
101	1	superadmin	logistica	venta_entregada	venta	9	Venta entregada: VEN-20260610-0002.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	POST	/superadmin/comercial/ventas/9/entregar	{"venta_id":9,"numero_venta":"VEN-20260610-0002","cliente_id":5,"estado_logistico":"entregada","punto_recojo":"Recojo en tienda Mati Boutique","usuario_entrega_id":1}	2026-06-10 02:42:49	2026-06-10 02:42:49
102	1	superadmin	inventario	consulta_kardex	\N	\N	Consulta de Kardex de inventario.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/inventario/movimientos	{"filtros":[]}	2026-06-10 02:42:57	2026-06-10 02:42:57
103	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"ventas","filtros":{"section":"ventas"}}	2026-06-10 02:43:21	2026-06-10 02:43:21
104	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"reservas","filtros":{"section":"reservas"}}	2026-06-10 02:43:27	2026-06-10 02:43:27
105	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"pagos","filtros":{"section":"pagos"}}	2026-06-10 02:43:30	2026-06-10 02:43:30
106	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"inventario","filtros":{"section":"inventario"}}	2026-06-10 02:43:32	2026-06-10 02:43:32
107	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0	GET	/superadmin/reportes	{"seccion":"clientes","filtros":{"section":"clientes"}}	2026-06-10 02:43:37	2026-06-10 02:43:37
108	2	vendedor	autenticacion	inicio_sesion	user	2	Inicio de sesion exitoso.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	POST	/login	{"destino_intencionado":null}	2026-06-14 14:35:42	2026-06-14 14:35:42
109	2	vendedor	autenticacion	inicio_sesion	user	2	Inicio de sesion exitoso.	info	172.18.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	POST	/login	{"destino_intencionado":"http:\\/\\/localhost\\/vendedor\\/pagos"}	2026-06-14 19:39:57	2026-06-14 19:39:57
110	2	vendedor	autenticacion	inicio_sesion	user	2	Inicio de sesion exitoso.	info	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	POST	/login	{"destino_intencionado":"http:\\/\\/localhost\\/vendedor\\/reportes"}	2026-06-15 21:10:47	2026-06-15 21:10:47
111	2	vendedor	autenticacion	inicio_sesion	user	2	Inicio de sesion exitoso.	info	172.19.0.1	curl/8.5.0	POST	/login	{"destino_intencionado":null}	2026-06-15 21:14:39	2026-06-15 21:14:39
112	2	vendedor	autenticacion	inicio_sesion	user	2	Inicio de sesion exitoso.	info	172.19.0.1	curl/8.5.0	POST	/login	{"destino_intencionado":null}	2026-06-15 21:14:53	2026-06-15 21:14:53
113	2	vendedor	autenticacion	cierre_sesion	user	2	Cierre de sesion.	info	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	POST	/logout	[]	2026-06-15 22:26:48	2026-06-15 22:26:48
114	3	cliente	autenticacion	inicio_sesion	user	3	Inicio de sesion exitoso.	info	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	POST	/login	{"destino_intencionado":null}	2026-06-15 22:26:53	2026-06-15 22:26:53
115	3	cliente	autenticacion	cierre_sesion	user	3	Cierre de sesion.	info	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	POST	/logout	[]	2026-06-15 22:27:26	2026-06-15 22:27:26
116	1	superadmin	autenticacion	inicio_sesion	user	1	Inicio de sesion exitoso.	info	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	POST	/login	{"destino_intencionado":null}	2026-06-15 22:27:31	2026-06-15 22:27:31
117	1	superadmin	reportes	reporte_visualizado	\N	\N	Reportes operativos visualizados.	info	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	GET	/superadmin/reportes	{"seccion":"resumen","filtros":[]}	2026-06-15 22:27:33	2026-06-15 22:27:33
118	1	superadmin	autenticacion	cierre_sesion	user	1	Cierre de sesion.	info	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	POST	/logout	[]	2026-06-15 22:27:35	2026-06-15 22:27:35
119	2	vendedor	autenticacion	inicio_sesion	user	2	Inicio de sesion exitoso.	info	172.19.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36	POST	/login	{"destino_intencionado":null}	2026-06-15 22:27:40	2026-06-15 22:27:40
\.


--
-- Data for Name: tallas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.tallas (id_talla, nom_tal, ord_tal, est_tal, fec_tal, created_at, updated_at, sis_tal, gru_tal, seg_tal) FROM stdin;
6	XL	60	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	ALFA	SUPERIOR	ADULTO
7	XXL	70	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	ALFA	SUPERIOR	ADULTO
19	XXXL	80	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	ALFA	SUPERIOR	ADULTO
20	XXXXL	90	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	ALFA	SUPERIOR	ADULTO
8	32	1320	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	NUMERICA	INFERIOR	ADULTO
28	36 EU	4036	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	CALZADO_EU	CALZADO	ADULTO
29	37 EU	4037	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	CALZADO_EU	CALZADO	ADULTO
30	38 EU	4038	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	CALZADO_EU	CALZADO	ADULTO
31	39 EU	4039	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	CALZADO_EU	CALZADO	ADULTO
32	40 EU	4040	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	CALZADO_EU	CALZADO	ADULTO
33	2A	5020	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	EDAD	GENERAL	INFANTIL
34	3A	5030	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	EDAD	GENERAL	INFANTIL
35	4A	5040	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	EDAD	GENERAL	INFANTIL
36	5A	5050	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	EDAD	GENERAL	INFANTIL
37	6A	5060	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	EDAD	GENERAL	INFANTIL
38	8A	5080	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	EDAD	GENERAL	INFANTIL
1	UNITALLA	10	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	UNICA	GENERAL	ADULTO
17	XXXS	5	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	ALFA	SUPERIOR	ADULTO
18	XXS	10	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	ALFA	SUPERIOR	ADULTO
2	XS	20	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	ALFA	SUPERIOR	ADULTO
3	S	30	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	ALFA	SUPERIOR	ADULTO
4	M	40	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	ALFA	SUPERIOR	ADULTO
5	L	50	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	ALFA	SUPERIOR	ADULTO
9	34	1340	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	NUMERICA	INFERIOR	ADULTO
10	36	1360	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	NUMERICA	INFERIOR	ADULTO
11	38	1380	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	NUMERICA	INFERIOR	ADULTO
12	40	1400	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	NUMERICA	INFERIOR	ADULTO
13	42	1420	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	NUMERICA	INFERIOR	ADULTO
14	44	1440	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	NUMERICA	INFERIOR	ADULTO
15	46	1460	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	NUMERICA	INFERIOR	ADULTO
16	48	1480	t	2026-05-12 21:07:58	2026-04-14 22:54:14	2026-05-12 21:07:58	NUMERICA	INFERIOR	ADULTO
21	6 US	3060	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	CALZADO_US	CALZADO	ADULTO
22	6.5 US	3065	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	CALZADO_US	CALZADO	ADULTO
23	7 US	3070	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	CALZADO_US	CALZADO	ADULTO
24	7.5 US	3075	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	CALZADO_US	CALZADO	ADULTO
25	8 US	3080	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	CALZADO_US	CALZADO	ADULTO
26	8.5 US	3085	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	CALZADO_US	CALZADO	ADULTO
27	9 US	3090	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	CALZADO_US	CALZADO	ADULTO
39	10A	5100	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	EDAD	GENERAL	INFANTIL
40	12A	5120	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	EDAD	GENERAL	INFANTIL
41	92 CM	6092	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	ALTURA_CM	GENERAL	INFANTIL
42	98 CM	6098	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	ALTURA_CM	GENERAL	INFANTIL
43	104 CM	6104	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	ALTURA_CM	GENERAL	INFANTIL
44	110 CM	6110	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	ALTURA_CM	GENERAL	INFANTIL
45	116 CM	6116	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	ALTURA_CM	GENERAL	INFANTIL
46	122 CM	6122	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	ALTURA_CM	GENERAL	INFANTIL
47	128 CM	6128	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	ALTURA_CM	GENERAL	INFANTIL
48	0-3M	7003	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	MESES	GENERAL	BEBE
49	3-6M	7006	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	MESES	GENERAL	BEBE
50	6-9M	7009	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	MESES	GENERAL	BEBE
51	9-12M	7012	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	MESES	GENERAL	BEBE
52	12-18M	7018	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	MESES	GENERAL	BEBE
53	18-24M	7024	t	2026-05-12 21:07:58	2026-05-12 20:43:02	2026-05-12 21:07:58	MESES	GENERAL	BEBE
54	XXXXXXXXL	9999	t	2026-05-12 21:08:03	2026-05-12 21:08:03	2026-05-12 21:08:03	ALFA	GENERAL	INFANTIL
\.


--
-- Data for Name: temporadas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.temporadas (id_temporada, nom_tem, des_tem, est_tem, fec_tem, created_at, updated_at, cod_tem, ord_tem, fec_ini_tem, fec_fin_tem) FROM stdin;
6	CARNAVALERA	Carnaval	t	2026-05-05 20:12:53	2026-05-05 20:12:54	2026-05-05 20:12:54	C	60	\N	\N
1	PRIMAVERA-VERANO	Piezas ligeras y clima cálido.	t	2026-05-12 21:54:07	2026-04-14 22:54:14	2026-05-12 21:54:07	PV	10	2026-09-01	2027-02-28
2	OTOÑO-INVIERNO	Abrigo, punto y capas.	t	2026-05-12 21:54:07	2026-04-14 22:54:14	2026-05-12 21:54:07	OI	20	2026-03-01	2026-08-31
3	RESORT	Viaje, transición y silueta relajada.	t	2026-05-12 21:54:07	2026-04-14 22:54:14	2026-05-12 21:54:07	RES	30	2026-11-01	2026-12-31
4	CAPSULE	Edición corta y curada.	t	2026-05-12 21:54:07	2026-04-14 22:54:14	2026-05-12 21:54:07	CAP	40	2026-05-01	2026-05-31
5	PERMANENTE	Base continua de fondo de armario.	t	2026-05-12 21:54:07	2026-04-14 22:54:14	2026-05-12 21:54:07	PER	50	2026-01-01	2026-12-31
7	MUNDIAL	\N	t	2026-05-13 02:35:42	2026-05-13 02:35:42	2026-05-13 02:35:42	TMP	70	2026-05-12	2026-08-01
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.users (id, email, email_verified_at, password, remember_token, created_at, updated_at, nom_usu, app_usu, apm_usu, tel_usu, cod_roles_usu, est_usu, fec_usu) FROM stdin;
4	marcelo@gmail.com	\N	$2y$12$OZio1BqxocuDOoAHSiVQ1uUIzur9yuWoBnLGj2Hz/R6Z.IBnG7pd6	\N	2026-04-26 22:07:26	2026-04-26 22:07:26	MARCELO	ESCOBAR	CHIPANA	65489498	3	t	2026-04-26 22:07:26
7	vicente@gmail.com	\N	$2y$12$4iFQNEpMbJd3SXgw/w0geOsAZx.C6d.vQgdwzdm9OjvkZ.CyD43Ke	\N	2026-05-06 21:54:40	2026-05-06 21:54:40	VICENTE	CLAROS	MAMANI	64695618	3	t	2026-05-06 21:54:40
8	pepe@gmail.com	\N	$2y$12$ju8X54ghLeWXfCgXkrlSsuzOiaVKJ7.eCwAUvJ83DPLkzBqnkAsHS	\N	2026-05-12 15:45:51	2026-05-12 15:45:51	PEPE	MAMANI	COLQUE	65156191	2	t	2026-05-12 15:45:51
6	carlavaleria@gmail.com	\N	$2y$12$GYf21MIXNwOxlMMhn.W4U./vxs8x4QegzWTw9oMuxB4wg7CdoXcUq	\N	2026-05-05 20:08:43	2026-05-12 15:46:33	CARLA VALERIA	ENCINAS	CANO	75416865	3	t	2026-05-05 20:08:43
3	cliente@more.com	2026-04-14 22:54:13	$2y$12$VpZr1aU5I28Ri4gWQOoH6etwlbUMa.pBrEsPNJrv7lP8gA/EvTDIy	\N	2026-04-14 22:54:13	2026-05-24 20:00:22	CLIENTE	DEMO	MORE	70000003	3	t	2026-04-14 22:54:13
5	limber.caso123@gmail.com	\N	$2y$12$3WSLcVo8uodJnUBF2EejheTt/PkAQprkWOoVNwzGUG8QeWnJZOVjS	V41P8N6rIAk0f1QYmdmekZRBKKYu0VoOGqIVkcTd42138nGcBleLV1QKvuiI	2026-04-27 00:37:03	2026-04-27 00:37:03	LIMBER	SOTO	CAZU	71930583	3	t	2026-04-27 00:37:03
2	vendedor@more.com	2026-04-14 22:54:13	$2y$12$GRtlFpxNzNOXF/ylPuU0T.Uz9y48GH1BtAvXyp3vtC0s9UNTLtHiu	3JHIBaVe9qG0BaR8On0daxfnyhRia64snElwdWxOKVQPe3HwaoRV9LHeZBTr	2026-04-14 22:54:13	2026-04-14 22:54:13	MATI	BOUTIQUE	VENDEDOR	70000002	2	t	2026-04-14 22:54:13
1	superadmin@more.com	2026-04-14 22:54:13	$2y$12$2dHUNwGIuff1JUZklQUdB.OvH988ikv4uGZuaSjbzBYsxbcbZ6u96	2iGKuWIY8MldnjSsimNqmJWkhbzrWoiE77ODrv41ZhelepEmwzIyBfZK67tY	2026-04-14 22:54:13	2026-04-14 22:54:13	LIMBER	SOTO	CAZU	70000001	1	t	2026-04-14 22:54:13
\.


--
-- Data for Name: venta_pagos; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.venta_pagos (id_venta_pago, cod_ventas_vpg, monto_declarado_vpg, monto_verificado_vpg, metodo_pago_vpg, comprobante_ruta_vpg, comprobante_url_vpg, est_vpg, cod_usuario_verifica_vpg, fec_verificacion_vpg, obs_vpg, fec_vpg, created_at, updated_at) FROM stdin;
1	5	50.00	50.00	qr	ventas/comprobantes/4SRKXIpdRdylmbYL2KUIuPWrVQ6stLao9D0zLtVu.png	http://localhost/storage/ventas/comprobantes/4SRKXIpdRdylmbYL2KUIuPWrVQ6stLao9D0zLtVu.png	confirmado	1	2026-05-22 03:18:20	ninguna	2026-05-19 01:13:40	2026-05-19 01:13:40	2026-05-22 03:18:20
2	6	1000.00	1000.00	qr	ventas/comprobantes/xtGmwwuRWo97n68zYPsrTFHBWfLvfceu4JcP0E85.png	http://localhost/storage/ventas/comprobantes/xtGmwwuRWo97n68zYPsrTFHBWfLvfceu4JcP0E85.png	confirmado	1	2026-05-24 19:58:40	\N	2026-05-24 19:56:52	2026-05-24 19:56:52	2026-05-24 19:58:40
3	7	150.00	150.00	qr	ventas/comprobantes/495bWpVbanRbhYmhSYksKWTL4tBR4uOtCUp7iqlr.jpg	http://localhost/storage/ventas/comprobantes/495bWpVbanRbhYmhSYksKWTL4tBR4uOtCUp7iqlr.jpg	confirmado	1	2026-06-05 00:44:14	ninguna	2026-06-05 00:43:33	2026-06-05 00:43:33	2026-06-05 00:44:14
4	8	150.00	150.00	qr	ventas/comprobantes/9AE4D2m4GdncI8HCzdwLHs6mkFXStDDzd2CRAU9L.png	http://localhost/storage/ventas/comprobantes/9AE4D2m4GdncI8HCzdwLHs6mkFXStDDzd2CRAU9L.png	confirmado	1	2026-06-10 01:50:04	ninguna	2026-06-10 01:49:27	2026-06-10 01:49:27	2026-06-10 01:50:04
5	9	170.00	170.00	qr	ventas/comprobantes/0pST1ul1fozKdfdk0l6xqqwEw1irSZaFoyRZ8hki.jpg	http://localhost/storage/ventas/comprobantes/0pST1ul1fozKdfdk0l6xqqwEw1irSZaFoyRZ8hki.jpg	confirmado	1	2026-06-10 02:42:39	ninguna	2026-06-10 02:42:12	2026-06-10 02:42:12	2026-06-10 02:42:39
\.


--
-- Data for Name: ventas; Type: TABLE DATA; Schema: public; Owner: sail
--

COPY public.ventas (id_venta, nve_ven, cod_usuario_cliente_ven, cod_usuario_vendedor_ven, tiv_ven, sub_ven, des_ven, tot_ven, mpa_ven, est_ven, fec_ven, created_at, updated_at, est_logistico_ven, fec_disponible_recojo_ven, fec_limite_recojo_ven, punto_recojo_ven, origen_stock_ven, fec_entrega_ven, cod_usuario_entrega_ven) FROM stdin;
1	VEN-RES-RES-20260518-0001	5	1	reserva	400.00	0.00	400.00	efectivo	registrada	2026-05-18 15:36:24	2026-05-18 15:36:24	2026-05-18 15:36:24	pendiente_revision	\N	\N	\N	\N	\N	\N
2	VEN-RES-RES-20260518-0003	5	1	reserva	2500.00	0.00	2500.00	efectivo	registrada	2026-05-18 21:16:15	2026-05-18 21:16:15	2026-05-18 21:16:15	pendiente_revision	\N	\N	\N	\N	\N	\N
3	VEN-RES-RES-20260518-0004	5	1	reserva	50.00	0.00	50.00	qr	registrada	2026-05-18 21:18:17	2026-05-18 21:18:17	2026-05-18 21:18:17	pendiente_revision	\N	\N	\N	\N	\N	\N
4	VEN-RES-RES-20260519-0001	5	1	reserva	50.00	0.00	50.00	efectivo	registrada	2026-05-19 01:13:10	2026-05-19 01:13:10	2026-05-19 01:13:10	pendiente_revision	\N	\N	\N	\N	\N	\N
5	VEN-20260519-0001	5	1	online	50.00	0.00	50.00	qr	registrada	2026-05-19 01:13:40	2026-05-19 01:13:40	2026-05-22 03:18:20	pendiente_revision	\N	\N	\N	\N	\N	\N
6	VEN-20260524-0001	5	1	online	1000.00	0.00	1000.00	qr	registrada	2026-05-24 19:56:52	2026-05-24 19:56:52	2026-05-24 19:58:40	pendiente_revision	\N	\N	\N	\N	\N	\N
7	VEN-20260605-0001	5	1	online	150.00	0.00	150.00	qr	registrada	2026-06-05 00:43:33	2026-06-05 00:43:33	2026-06-05 15:37:48	entregada	2026-06-05 00:00:00	2026-07-05 23:59:59	Recojo en tienda Mati Boutique	TIENDA	2026-06-05 15:37:48	1
8	VEN-20260610-0001	5	1	online	150.00	0.00	150.00	qr	registrada	2026-06-10 01:49:27	2026-06-10 01:49:27	2026-06-10 01:50:44	entregada	2026-06-10 00:00:00	2026-07-10 23:59:59	Recojo en tienda Mati Boutique	TIENDA	2026-06-10 01:50:44	1
9	VEN-20260610-0002	5	1	online	170.00	0.00	170.00	qr	registrada	2026-06-10 02:42:12	2026-06-10 02:42:12	2026-06-10 02:42:49	entregada	2026-06-10 00:00:00	2026-07-10 23:59:59	Recojo en tienda Mati Boutique	TIENDA	2026-06-10 02:42:49	1
\.


--
-- Name: audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.audit_logs_id_seq', 39, true);


--
-- Name: categorias_id_categoria_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.categorias_id_categoria_seq', 11, true);


--
-- Name: colores_id_color_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.colores_id_color_seq', 16, true);


--
-- Name: database_backups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.database_backups_id_seq', 2, true);


--
-- Name: detalle_reservas_id_detalle_reserva_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.detalle_reservas_id_detalle_reserva_seq', 9, true);


--
-- Name: detalle_ventas_id_detalle_venta_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.detalle_ventas_id_detalle_venta_seq', 10, true);


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

SELECT pg_catalog.setval('public.imagenes_prenda_id_imagen_prenda_seq', 13, true);


--
-- Name: inventario_movimientos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.inventario_movimientos_id_seq', 11, true);


--
-- Name: inventarios_id_inventario_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.inventarios_id_inventario_seq', 11, true);


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

SELECT pg_catalog.setval('public.migrations_id_seq', 40, true);


--
-- Name: prenda_danio_imagenes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.prenda_danio_imagenes_id_seq', 1, false);


--
-- Name: prenda_danio_reportes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.prenda_danio_reportes_id_seq', 1, false);


--
-- Name: prenda_variantes_id_prenda_variante_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.prenda_variantes_id_prenda_variante_seq', 11, true);


--
-- Name: prendas_id_prenda_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.prendas_id_prenda_seq', 11, true);


--
-- Name: reserva_pagos_id_reserva_pago_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.reserva_pagos_id_reserva_pago_seq', 11, true);


--
-- Name: reservas_id_reserva_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.reservas_id_reserva_seq', 8, true);


--
-- Name: roles_id_rol_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.roles_id_rol_seq', 3, true);


--
-- Name: system_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.system_logs_id_seq', 119, true);


--
-- Name: tallas_id_talla_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.tallas_id_talla_seq', 54, true);


--
-- Name: temporadas_id_temporada_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.temporadas_id_temporada_seq', 7, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.users_id_seq', 8, true);


--
-- Name: venta_pagos_id_venta_pago_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.venta_pagos_id_venta_pago_seq', 5, true);


--
-- Name: ventas_id_venta_seq; Type: SEQUENCE SET; Schema: public; Owner: sail
--

SELECT pg_catalog.setval('public.ventas_id_venta_seq', 9, true);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


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
-- Name: database_backups database_backups_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.database_backups
    ADD CONSTRAINT database_backups_pkey PRIMARY KEY (id);


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
-- Name: inventario_movimientos inventario_movimientos_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.inventario_movimientos
    ADD CONSTRAINT inventario_movimientos_pkey PRIMARY KEY (id);


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
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: prenda_danio_imagenes prenda_danio_imagenes_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_danio_imagenes
    ADD CONSTRAINT prenda_danio_imagenes_pkey PRIMARY KEY (id);


--
-- Name: prenda_danio_reportes prenda_danio_reportes_codigo_pdr_unique; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_danio_reportes
    ADD CONSTRAINT prenda_danio_reportes_codigo_pdr_unique UNIQUE (codigo_pdr);


--
-- Name: prenda_danio_reportes prenda_danio_reportes_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_danio_reportes
    ADD CONSTRAINT prenda_danio_reportes_pkey PRIMARY KEY (id);


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
-- Name: reserva_pagos reserva_pagos_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reserva_pagos
    ADD CONSTRAINT reserva_pagos_pkey PRIMARY KEY (id_reserva_pago);


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
-- Name: system_logs system_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.system_logs
    ADD CONSTRAINT system_logs_pkey PRIMARY KEY (id);


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
-- Name: venta_pagos venta_pagos_pkey; Type: CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.venta_pagos
    ADD CONSTRAINT venta_pagos_pkey PRIMARY KEY (id_venta_pago);


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
-- Name: audit_logs_accion_aul_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX audit_logs_accion_aul_index ON public.audit_logs USING btree (accion_aul);


--
-- Name: audit_logs_cod_usuario_aul_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX audit_logs_cod_usuario_aul_index ON public.audit_logs USING btree (cod_usuario_aul);


--
-- Name: audit_logs_created_at_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX audit_logs_created_at_index ON public.audit_logs USING btree (created_at);


--
-- Name: audit_logs_modulo_aul_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX audit_logs_modulo_aul_index ON public.audit_logs USING btree (modulo_aul);


--
-- Name: audit_logs_nivel_aul_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX audit_logs_nivel_aul_index ON public.audit_logs USING btree (nivel_aul);


--
-- Name: audit_logs_tabla_aul_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX audit_logs_tabla_aul_index ON public.audit_logs USING btree (tabla_aul);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: categorias_gru_tal_cat_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX categorias_gru_tal_cat_index ON public.categorias USING btree (gru_tal_cat);


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
-- Name: database_backups_cod_usuario_bak_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX database_backups_cod_usuario_bak_index ON public.database_backups USING btree (cod_usuario_bak);


--
-- Name: database_backups_estado_bak_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX database_backups_estado_bak_index ON public.database_backups USING btree (estado_bak);


--
-- Name: database_backups_fec_bak_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX database_backups_fec_bak_index ON public.database_backups USING btree (fec_bak);


--
-- Name: detalle_reservas_cod_prenda_variantes_dre_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX detalle_reservas_cod_prenda_variantes_dre_index ON public.detalle_reservas USING btree (cod_prenda_variantes_dre);


--
-- Name: idx_audit_logs_modulo_nivel_fecha; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX idx_audit_logs_modulo_nivel_fecha ON public.audit_logs USING btree (modulo_aul, nivel_aul, created_at);


--
-- Name: idx_audit_logs_tabla_registro; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX idx_audit_logs_tabla_registro ON public.audit_logs USING btree (tabla_aul, registro_id_aul);


--
-- Name: idx_inv_mov_ref; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX idx_inv_mov_ref ON public.inventario_movimientos USING btree (referencia_tipo_inm, referencia_id_inm);


--
-- Name: idx_pdi_reporte; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX idx_pdi_reporte ON public.prenda_danio_imagenes USING btree (cod_prenda_danio_reporte_pdi);


--
-- Name: idx_pdr_inventario_estado; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX idx_pdr_inventario_estado ON public.prenda_danio_reportes USING btree (cod_inventario_pdr, estado_pdr);


--
-- Name: idx_reserva_pagos_reserva_estado; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX idx_reserva_pagos_reserva_estado ON public.reserva_pagos USING btree (cod_reservas_rpg, est_rpg);


--
-- Name: idx_reservas_fechas_recojo; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX idx_reservas_fechas_recojo ON public.reservas USING btree (fec_disponible_recojo_res, fec_limite_recojo_res);


--
-- Name: idx_system_logs_entidad; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX idx_system_logs_entidad ON public.system_logs USING btree (entidad_tipo_slg, entidad_id_slg);


--
-- Name: idx_system_logs_modulo_nivel_fecha; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX idx_system_logs_modulo_nivel_fecha ON public.system_logs USING btree (modulo_slg, nivel_slg, created_at);


--
-- Name: idx_venta_pagos_venta_estado; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX idx_venta_pagos_venta_estado ON public.venta_pagos USING btree (cod_ventas_vpg, est_vpg);


--
-- Name: idx_ventas_fechas_recojo; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX idx_ventas_fechas_recojo ON public.ventas USING btree (fec_disponible_recojo_ven, fec_limite_recojo_ven);


--
-- Name: inventario_movimientos_cod_inventario_inm_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX inventario_movimientos_cod_inventario_inm_index ON public.inventario_movimientos USING btree (cod_inventario_inm);


--
-- Name: inventario_movimientos_cod_prenda_variante_inm_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX inventario_movimientos_cod_prenda_variante_inm_index ON public.inventario_movimientos USING btree (cod_prenda_variante_inm);


--
-- Name: inventario_movimientos_cod_usuario_inm_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX inventario_movimientos_cod_usuario_inm_index ON public.inventario_movimientos USING btree (cod_usuario_inm);


--
-- Name: inventario_movimientos_created_at_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX inventario_movimientos_created_at_index ON public.inventario_movimientos USING btree (created_at);


--
-- Name: inventario_movimientos_tipo_movimiento_inm_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX inventario_movimientos_tipo_movimiento_inm_index ON public.inventario_movimientos USING btree (tipo_movimiento_inm);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: notifications_read_at_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX notifications_read_at_index ON public.notifications USING btree (read_at);


--
-- Name: prenda_danio_reportes_causa_pdr_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX prenda_danio_reportes_causa_pdr_index ON public.prenda_danio_reportes USING btree (causa_pdr);


--
-- Name: prenda_danio_reportes_estado_pdr_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX prenda_danio_reportes_estado_pdr_index ON public.prenda_danio_reportes USING btree (estado_pdr);


--
-- Name: prenda_danio_reportes_fecha_reporte_pdr_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX prenda_danio_reportes_fecha_reporte_pdr_index ON public.prenda_danio_reportes USING btree (fecha_reporte_pdr);


--
-- Name: prenda_danio_reportes_severidad_pdr_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX prenda_danio_reportes_severidad_pdr_index ON public.prenda_danio_reportes USING btree (severidad_pdr);


--
-- Name: prenda_danio_reportes_tipo_danio_pdr_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX prenda_danio_reportes_tipo_danio_pdr_index ON public.prenda_danio_reportes USING btree (tipo_danio_pdr);


--
-- Name: prendas_nom_pre_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX prendas_nom_pre_index ON public.prendas USING btree (nom_pre);


--
-- Name: reserva_pagos_cod_reservas_rpg_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX reserva_pagos_cod_reservas_rpg_index ON public.reserva_pagos USING btree (cod_reservas_rpg);


--
-- Name: reserva_pagos_est_rpg_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX reserva_pagos_est_rpg_index ON public.reserva_pagos USING btree (est_rpg);


--
-- Name: reservas_est_logistico_res_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX reservas_est_logistico_res_index ON public.reservas USING btree (est_logistico_res);


--
-- Name: reservas_est_pago_res_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX reservas_est_pago_res_index ON public.reservas USING btree (est_pago_res);


--
-- Name: reservas_est_res_est_pago_res_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX reservas_est_res_est_pago_res_index ON public.reservas USING btree (est_res, est_pago_res);


--
-- Name: reservas_fec_recogida_res_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX reservas_fec_recogida_res_index ON public.reservas USING btree (fec_recogida_res);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: system_logs_accion_slg_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX system_logs_accion_slg_index ON public.system_logs USING btree (accion_slg);


--
-- Name: system_logs_cod_usuario_slg_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX system_logs_cod_usuario_slg_index ON public.system_logs USING btree (cod_usuario_slg);


--
-- Name: system_logs_created_at_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX system_logs_created_at_index ON public.system_logs USING btree (created_at);


--
-- Name: system_logs_modulo_slg_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX system_logs_modulo_slg_index ON public.system_logs USING btree (modulo_slg);


--
-- Name: system_logs_nivel_slg_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX system_logs_nivel_slg_index ON public.system_logs USING btree (nivel_slg);


--
-- Name: tallas_gru_tal_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX tallas_gru_tal_index ON public.tallas USING btree (gru_tal);


--
-- Name: tallas_seg_tal_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX tallas_seg_tal_index ON public.tallas USING btree (seg_tal);


--
-- Name: tallas_seg_tal_sis_tal_gru_tal_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX tallas_seg_tal_sis_tal_gru_tal_index ON public.tallas USING btree (seg_tal, sis_tal, gru_tal);


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
-- Name: venta_pagos_cod_ventas_vpg_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX venta_pagos_cod_ventas_vpg_index ON public.venta_pagos USING btree (cod_ventas_vpg);


--
-- Name: venta_pagos_est_vpg_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX venta_pagos_est_vpg_index ON public.venta_pagos USING btree (est_vpg);


--
-- Name: venta_pagos_metodo_pago_vpg_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX venta_pagos_metodo_pago_vpg_index ON public.venta_pagos USING btree (metodo_pago_vpg);


--
-- Name: ventas_cod_usuario_cliente_ven_cod_usuario_vendedor_ven_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX ventas_cod_usuario_cliente_ven_cod_usuario_vendedor_ven_index ON public.ventas USING btree (cod_usuario_cliente_ven, cod_usuario_vendedor_ven);


--
-- Name: ventas_est_logistico_ven_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX ventas_est_logistico_ven_index ON public.ventas USING btree (est_logistico_ven);


--
-- Name: ventas_origen_stock_ven_index; Type: INDEX; Schema: public; Owner: sail
--

CREATE INDEX ventas_origen_stock_ven_index ON public.ventas USING btree (origen_stock_ven);


--
-- Name: audit_logs audit_logs_cod_usuario_aul_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_cod_usuario_aul_foreign FOREIGN KEY (cod_usuario_aul) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: categorias categorias_cod_categoria_padre_cat_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_cod_categoria_padre_cat_foreign FOREIGN KEY (cod_categoria_padre_cat) REFERENCES public.categorias(id_categoria) ON DELETE SET NULL;


--
-- Name: database_backups database_backups_cod_usuario_bak_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.database_backups
    ADD CONSTRAINT database_backups_cod_usuario_bak_foreign FOREIGN KEY (cod_usuario_bak) REFERENCES public.users(id) ON DELETE SET NULL;


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
-- Name: inventario_movimientos inventario_movimientos_cod_inventario_inm_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.inventario_movimientos
    ADD CONSTRAINT inventario_movimientos_cod_inventario_inm_foreign FOREIGN KEY (cod_inventario_inm) REFERENCES public.inventarios(id_inventario) ON DELETE SET NULL;


--
-- Name: inventario_movimientos inventario_movimientos_cod_prenda_variante_inm_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.inventario_movimientos
    ADD CONSTRAINT inventario_movimientos_cod_prenda_variante_inm_foreign FOREIGN KEY (cod_prenda_variante_inm) REFERENCES public.prenda_variantes(id_prenda_variante) ON DELETE RESTRICT;


--
-- Name: inventario_movimientos inventario_movimientos_cod_usuario_inm_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.inventario_movimientos
    ADD CONSTRAINT inventario_movimientos_cod_usuario_inm_foreign FOREIGN KEY (cod_usuario_inm) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: inventarios inventarios_cod_prenda_variantes_inv_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.inventarios
    ADD CONSTRAINT inventarios_cod_prenda_variantes_inv_foreign FOREIGN KEY (cod_prenda_variantes_inv) REFERENCES public.prenda_variantes(id_prenda_variante) ON DELETE CASCADE;


--
-- Name: prenda_danio_imagenes prenda_danio_imagenes_cod_prenda_danio_reporte_pdi_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_danio_imagenes
    ADD CONSTRAINT prenda_danio_imagenes_cod_prenda_danio_reporte_pdi_foreign FOREIGN KEY (cod_prenda_danio_reporte_pdi) REFERENCES public.prenda_danio_reportes(id) ON DELETE CASCADE;


--
-- Name: prenda_danio_reportes prenda_danio_reportes_cod_inventario_pdr_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_danio_reportes
    ADD CONSTRAINT prenda_danio_reportes_cod_inventario_pdr_foreign FOREIGN KEY (cod_inventario_pdr) REFERENCES public.inventarios(id_inventario) ON DELETE SET NULL;


--
-- Name: prenda_danio_reportes prenda_danio_reportes_cod_prenda_pdr_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_danio_reportes
    ADD CONSTRAINT prenda_danio_reportes_cod_prenda_pdr_foreign FOREIGN KEY (cod_prenda_pdr) REFERENCES public.prendas(id_prenda) ON DELETE SET NULL;


--
-- Name: prenda_danio_reportes prenda_danio_reportes_cod_prenda_variante_pdr_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_danio_reportes
    ADD CONSTRAINT prenda_danio_reportes_cod_prenda_variante_pdr_foreign FOREIGN KEY (cod_prenda_variante_pdr) REFERENCES public.prenda_variantes(id_prenda_variante) ON DELETE SET NULL;


--
-- Name: prenda_danio_reportes prenda_danio_reportes_cod_usuario_reporta_pdr_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_danio_reportes
    ADD CONSTRAINT prenda_danio_reportes_cod_usuario_reporta_pdr_foreign FOREIGN KEY (cod_usuario_reporta_pdr) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: prenda_danio_reportes prenda_danio_reportes_cod_usuario_revisa_pdr_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.prenda_danio_reportes
    ADD CONSTRAINT prenda_danio_reportes_cod_usuario_revisa_pdr_foreign FOREIGN KEY (cod_usuario_revisa_pdr) REFERENCES public.users(id) ON DELETE SET NULL;


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
-- Name: reserva_pagos reserva_pagos_cod_reservas_rpg_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reserva_pagos
    ADD CONSTRAINT reserva_pagos_cod_reservas_rpg_foreign FOREIGN KEY (cod_reservas_rpg) REFERENCES public.reservas(id_reserva) ON DELETE CASCADE;


--
-- Name: reserva_pagos reserva_pagos_cod_usuario_verifica_rpg_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reserva_pagos
    ADD CONSTRAINT reserva_pagos_cod_usuario_verifica_rpg_foreign FOREIGN KEY (cod_usuario_verifica_rpg) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: reservas reservas_cod_usuario_cliente_res_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reservas
    ADD CONSTRAINT reservas_cod_usuario_cliente_res_foreign FOREIGN KEY (cod_usuario_cliente_res) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: reservas reservas_cod_usuario_entrega_res_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reservas
    ADD CONSTRAINT reservas_cod_usuario_entrega_res_foreign FOREIGN KEY (cod_usuario_entrega_res) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: reservas reservas_cod_usuario_responsable_res_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reservas
    ADD CONSTRAINT reservas_cod_usuario_responsable_res_foreign FOREIGN KEY (cod_usuario_responsable_res) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: reservas reservas_cod_usuario_verifica_pago_res_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.reservas
    ADD CONSTRAINT reservas_cod_usuario_verifica_pago_res_foreign FOREIGN KEY (cod_usuario_verifica_pago_res) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: system_logs system_logs_cod_usuario_slg_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.system_logs
    ADD CONSTRAINT system_logs_cod_usuario_slg_foreign FOREIGN KEY (cod_usuario_slg) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: users users_cod_roles_usu_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_cod_roles_usu_foreign FOREIGN KEY (cod_roles_usu) REFERENCES public.roles(id_rol) ON DELETE SET NULL;


--
-- Name: venta_pagos venta_pagos_cod_usuario_verifica_vpg_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.venta_pagos
    ADD CONSTRAINT venta_pagos_cod_usuario_verifica_vpg_foreign FOREIGN KEY (cod_usuario_verifica_vpg) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: venta_pagos venta_pagos_cod_ventas_vpg_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.venta_pagos
    ADD CONSTRAINT venta_pagos_cod_ventas_vpg_foreign FOREIGN KEY (cod_ventas_vpg) REFERENCES public.ventas(id_venta) ON DELETE CASCADE;


--
-- Name: ventas ventas_cod_usuario_cliente_ven_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_cod_usuario_cliente_ven_foreign FOREIGN KEY (cod_usuario_cliente_ven) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ventas ventas_cod_usuario_entrega_ven_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_cod_usuario_entrega_ven_foreign FOREIGN KEY (cod_usuario_entrega_ven) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ventas ventas_cod_usuario_vendedor_ven_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sail
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_cod_usuario_vendedor_ven_foreign FOREIGN KEY (cod_usuario_vendedor_ven) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict m83YNrrQgiCIuMILcYltFybNtfgYaVycDaGJN9L67sCeycVvMVKeF4DPlD1nmke

