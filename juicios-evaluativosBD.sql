--
-- PostgreSQL database dump
--

\restrict HnZZuhgjmucLmlTVGgo1qgCS5OBkC5Kgh6ktqGX5uhy45HbQPrCERKbfpBSxC42

-- Dumped from database version 18.3
-- Dumped by pg_dump version 18.3

-- Started on 2026-08-18 13:55:00

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
-- TOC entry 242 (class 1259 OID 63464)
-- Name: aprendiz; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.aprendiz (
    "Id_Aprendiz" bigint NOT NULL,
    "Tipo_Documento" character varying(255) NOT NULL,
    "Documento" character varying(255) NOT NULL,
    "Nombre" character varying(255) NOT NULL,
    "Apellido" character varying(255) NOT NULL,
    "Estado" character varying(255) NOT NULL,
    "Id_Ficha" integer NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.aprendiz OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 63463)
-- Name: aprendiz_Id_Aprendiz_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public."aprendiz_Id_Aprendiz_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public."aprendiz_Id_Aprendiz_seq" OWNER TO postgres;

--
-- TOC entry 5180 (class 0 OID 0)
-- Dependencies: 241
-- Name: aprendiz_Id_Aprendiz_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public."aprendiz_Id_Aprendiz_seq" OWNED BY public.aprendiz."Id_Aprendiz";


--
-- TOC entry 225 (class 1259 OID 63319)
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 63330)
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 63391)
-- Name: competencia; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.competencia (
    "Id_Competencia" bigint NOT NULL,
    "Codigo" character varying(255) NOT NULL,
    "Nombre" text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.competencia OWNER TO postgres;

--
-- TOC entry 232 (class 1259 OID 63390)
-- Name: competencia_Id_Competencia_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public."competencia_Id_Competencia_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public."competencia_Id_Competencia_seq" OWNER TO postgres;

--
-- TOC entry 5181 (class 0 OID 0)
-- Dependencies: 232
-- Name: competencia_Id_Competencia_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public."competencia_Id_Competencia_seq" OWNED BY public.competencia."Id_Competencia";


--
-- TOC entry 231 (class 1259 OID 63372)
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- TOC entry 230 (class 1259 OID 63371)
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- TOC entry 5182 (class 0 OID 0)
-- Dependencies: 230
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- TOC entry 240 (class 1259 OID 63450)
-- Name: ficha; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ficha (
    "Id_Ficha" bigint NOT NULL,
    "Jornada" character varying(255) NOT NULL,
    "Id_Programa" bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.ficha OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 63435)
-- Name: funcionario; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.funcionario (
    "Id_Funcionario" bigint NOT NULL,
    "Tipo_Documento" character varying(255) NOT NULL,
    "Documento" numeric(20,0) NOT NULL,
    "Nombre" character varying(255) NOT NULL,
    "Apellido" character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.funcionario OWNER TO postgres;

--
-- TOC entry 238 (class 1259 OID 63434)
-- Name: funcionario_Id_Funcionario_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public."funcionario_Id_Funcionario_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public."funcionario_Id_Funcionario_seq" OWNER TO postgres;

--
-- TOC entry 5183 (class 0 OID 0)
-- Dependencies: 238
-- Name: funcionario_Id_Funcionario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public."funcionario_Id_Funcionario_seq" OWNED BY public.funcionario."Id_Funcionario";


--
-- TOC entry 246 (class 1259 OID 74222)
-- Name: importaciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.importaciones (
    id bigint NOT NULL,
    nombre_archivo character varying(255) NOT NULL,
    id_ficha character varying(255),
    aprendices_procesados integer DEFAULT 0 NOT NULL,
    duracion_segundos integer DEFAULT 0 NOT NULL,
    estado character varying(255) DEFAULT 'exitoso'::character varying NOT NULL,
    detalle text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.importaciones OWNER TO postgres;

--
-- TOC entry 245 (class 1259 OID 74221)
-- Name: importaciones_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.importaciones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.importaciones_id_seq OWNER TO postgres;

--
-- TOC entry 5184 (class 0 OID 0)
-- Dependencies: 245
-- Name: importaciones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.importaciones_id_seq OWNED BY public.importaciones.id;


--
-- TOC entry 229 (class 1259 OID 63357)
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 63342)
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.jobs OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 63341)
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- TOC entry 5185 (class 0 OID 0)
-- Dependencies: 227
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- TOC entry 244 (class 1259 OID 63487)
-- Name: juicios_evaluativos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.juicios_evaluativos (
    "Id_Juicio" bigint NOT NULL,
    "Id_Resultado" bigint NOT NULL,
    "Id_Aprendiz" bigint NOT NULL,
    "Estado" integer NOT NULL,
    "Id_Funcionario" bigint NOT NULL,
    "Fecha" date,
    "Hora" timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.juicios_evaluativos OWNER TO postgres;

--
-- TOC entry 243 (class 1259 OID 63486)
-- Name: juicios_evaluativos_Id_Juicio_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public."juicios_evaluativos_Id_Juicio_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public."juicios_evaluativos_Id_Juicio_seq" OWNER TO postgres;

--
-- TOC entry 5186 (class 0 OID 0)
-- Dependencies: 243
-- Name: juicios_evaluativos_Id_Juicio_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public."juicios_evaluativos_Id_Juicio_seq" OWNED BY public.juicios_evaluativos."Id_Juicio";


--
-- TOC entry 220 (class 1259 OID 63274)
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 63273)
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- TOC entry 5187 (class 0 OID 0)
-- Dependencies: 219
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- TOC entry 223 (class 1259 OID 63298)
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 63420)
-- Name: programa; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.programa (
    "Id_Programa" bigint NOT NULL,
    "Nombre" character varying(255) NOT NULL,
    "Modalidad" character varying(255),
    "Codigo" character varying(255) NOT NULL,
    "Version" character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.programa OWNER TO postgres;

--
-- TOC entry 236 (class 1259 OID 63419)
-- Name: programa_Id_Programa_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public."programa_Id_Programa_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public."programa_Id_Programa_seq" OWNER TO postgres;

--
-- TOC entry 5188 (class 0 OID 0)
-- Dependencies: 236
-- Name: programa_Id_Programa_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public."programa_Id_Programa_seq" OWNED BY public.programa."Id_Programa";


--
-- TOC entry 248 (class 1259 OID 81466)
-- Name: remisiones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.remisiones (
    id bigint NOT NULL,
    "Id_Aprendiz" bigint NOT NULL,
    "Id_Ficha" bigint,
    score_riesgo integer DEFAULT 0 NOT NULL,
    nivel_semaforo character varying(20) DEFAULT 'MODERADO'::character varying NOT NULL,
    total_pendientes integer DEFAULT 0 NOT NULL,
    estado_remision character varying(30) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    radicado character varying(50),
    motivo text,
    observaciones text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.remisiones OWNER TO postgres;

--
-- TOC entry 247 (class 1259 OID 81465)
-- Name: remisiones_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.remisiones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.remisiones_id_seq OWNER TO postgres;

--
-- TOC entry 5189 (class 0 OID 0)
-- Dependencies: 247
-- Name: remisiones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.remisiones_id_seq OWNED BY public.remisiones.id;


--
-- TOC entry 235 (class 1259 OID 63402)
-- Name: resultados; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.resultados (
    "Id_Resultado" bigint NOT NULL,
    "Codigo" character varying(255) NOT NULL,
    "Nombre" text,
    "Id_Competencia" bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.resultados OWNER TO postgres;

--
-- TOC entry 234 (class 1259 OID 63401)
-- Name: resultados_Id_Resultado_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public."resultados_Id_Resultado_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public."resultados_Id_Resultado_seq" OWNER TO postgres;

--
-- TOC entry 5190 (class 0 OID 0)
-- Dependencies: 234
-- Name: resultados_Id_Resultado_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public."resultados_Id_Resultado_seq" OWNED BY public.resultados."Id_Resultado";


--
-- TOC entry 224 (class 1259 OID 63307)
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 63284)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 63283)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- TOC entry 5191 (class 0 OID 0)
-- Dependencies: 221
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 4944 (class 2604 OID 63467)
-- Name: aprendiz Id_Aprendiz; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aprendiz ALTER COLUMN "Id_Aprendiz" SET DEFAULT nextval('public."aprendiz_Id_Aprendiz_seq"'::regclass);


--
-- TOC entry 4940 (class 2604 OID 63394)
-- Name: competencia Id_Competencia; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.competencia ALTER COLUMN "Id_Competencia" SET DEFAULT nextval('public."competencia_Id_Competencia_seq"'::regclass);


--
-- TOC entry 4938 (class 2604 OID 63375)
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- TOC entry 4943 (class 2604 OID 63438)
-- Name: funcionario Id_Funcionario; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.funcionario ALTER COLUMN "Id_Funcionario" SET DEFAULT nextval('public."funcionario_Id_Funcionario_seq"'::regclass);


--
-- TOC entry 4946 (class 2604 OID 74225)
-- Name: importaciones id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.importaciones ALTER COLUMN id SET DEFAULT nextval('public.importaciones_id_seq'::regclass);


--
-- TOC entry 4937 (class 2604 OID 63345)
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- TOC entry 4945 (class 2604 OID 63490)
-- Name: juicios_evaluativos Id_Juicio; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.juicios_evaluativos ALTER COLUMN "Id_Juicio" SET DEFAULT nextval('public."juicios_evaluativos_Id_Juicio_seq"'::regclass);


--
-- TOC entry 4935 (class 2604 OID 63277)
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- TOC entry 4942 (class 2604 OID 63423)
-- Name: programa Id_Programa; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.programa ALTER COLUMN "Id_Programa" SET DEFAULT nextval('public."programa_Id_Programa_seq"'::regclass);


--
-- TOC entry 4950 (class 2604 OID 81469)
-- Name: remisiones id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.remisiones ALTER COLUMN id SET DEFAULT nextval('public.remisiones_id_seq'::regclass);


--
-- TOC entry 4941 (class 2604 OID 63405)
-- Name: resultados Id_Resultado; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resultados ALTER COLUMN "Id_Resultado" SET DEFAULT nextval('public."resultados_Id_Resultado_seq"'::regclass);


--
-- TOC entry 4936 (class 2604 OID 63287)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 5000 (class 2606 OID 63485)
-- Name: aprendiz aprendiz_documento_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aprendiz
    ADD CONSTRAINT aprendiz_documento_unique UNIQUE ("Documento");


--
-- TOC entry 5002 (class 2606 OID 63478)
-- Name: aprendiz aprendiz_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aprendiz
    ADD CONSTRAINT aprendiz_pkey PRIMARY KEY ("Id_Aprendiz");


--
-- TOC entry 4972 (class 2606 OID 63339)
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- TOC entry 4969 (class 2606 OID 63328)
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- TOC entry 4983 (class 2606 OID 63400)
-- Name: competencia competencia_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.competencia
    ADD CONSTRAINT competencia_pkey PRIMARY KEY ("Id_Competencia");


--
-- TOC entry 4979 (class 2606 OID 63387)
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- TOC entry 4981 (class 2606 OID 63389)
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- TOC entry 4998 (class 2606 OID 63462)
-- Name: ficha ficha_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ficha
    ADD CONSTRAINT ficha_pkey PRIMARY KEY ("Id_Ficha");


--
-- TOC entry 4994 (class 2606 OID 63449)
-- Name: funcionario funcionario_documento_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.funcionario
    ADD CONSTRAINT funcionario_documento_unique UNIQUE ("Documento");


--
-- TOC entry 4996 (class 2606 OID 63447)
-- Name: funcionario funcionario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.funcionario
    ADD CONSTRAINT funcionario_pkey PRIMARY KEY ("Id_Funcionario");


--
-- TOC entry 5016 (class 2606 OID 74237)
-- Name: importaciones importaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.importaciones
    ADD CONSTRAINT importaciones_pkey PRIMARY KEY (id);


--
-- TOC entry 4977 (class 2606 OID 63370)
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- TOC entry 4974 (class 2606 OID 63355)
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- TOC entry 5014 (class 2606 OID 63497)
-- Name: juicios_evaluativos juicios_evaluativos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.juicios_evaluativos
    ADD CONSTRAINT juicios_evaluativos_pkey PRIMARY KEY ("Id_Juicio");


--
-- TOC entry 4956 (class 2606 OID 63282)
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- TOC entry 4962 (class 2606 OID 63306)
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- TOC entry 4990 (class 2606 OID 63433)
-- Name: programa programa_nombre_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.programa
    ADD CONSTRAINT programa_nombre_unique UNIQUE ("Nombre");


--
-- TOC entry 4992 (class 2606 OID 63431)
-- Name: programa programa_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.programa
    ADD CONSTRAINT programa_pkey PRIMARY KEY ("Id_Programa");


--
-- TOC entry 5018 (class 2606 OID 81483)
-- Name: remisiones remisiones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.remisiones
    ADD CONSTRAINT remisiones_pkey PRIMARY KEY (id);


--
-- TOC entry 4986 (class 2606 OID 63418)
-- Name: resultados resultados_codigo_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resultados
    ADD CONSTRAINT resultados_codigo_unique UNIQUE ("Codigo");


--
-- TOC entry 4988 (class 2606 OID 63411)
-- Name: resultados resultados_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resultados
    ADD CONSTRAINT resultados_pkey PRIMARY KEY ("Id_Resultado");


--
-- TOC entry 4965 (class 2606 OID 63316)
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- TOC entry 4958 (class 2606 OID 63297)
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- TOC entry 4960 (class 2606 OID 63295)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 4967 (class 1259 OID 63329)
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- TOC entry 4970 (class 1259 OID 63340)
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- TOC entry 5003 (class 1259 OID 74241)
-- Name: idx_aprendiz_apellido; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_aprendiz_apellido ON public.aprendiz USING btree ("Apellido");


--
-- TOC entry 5004 (class 1259 OID 74239)
-- Name: idx_aprendiz_estado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_aprendiz_estado ON public.aprendiz USING btree ("Estado");


--
-- TOC entry 5005 (class 1259 OID 74238)
-- Name: idx_aprendiz_ficha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_aprendiz_ficha ON public.aprendiz USING btree ("Id_Ficha");


--
-- TOC entry 5006 (class 1259 OID 74242)
-- Name: idx_aprendiz_ficha_estado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_aprendiz_ficha_estado ON public.aprendiz USING btree ("Id_Ficha", "Estado");


--
-- TOC entry 5007 (class 1259 OID 74240)
-- Name: idx_aprendiz_nombre; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_aprendiz_nombre ON public.aprendiz USING btree ("Nombre");


--
-- TOC entry 5008 (class 1259 OID 74243)
-- Name: idx_juicios_aprendiz; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_juicios_aprendiz ON public.juicios_evaluativos USING btree ("Id_Aprendiz");


--
-- TOC entry 5009 (class 1259 OID 74246)
-- Name: idx_juicios_aprendiz_estado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_juicios_aprendiz_estado ON public.juicios_evaluativos USING btree ("Id_Aprendiz", "Estado");


--
-- TOC entry 5010 (class 1259 OID 74244)
-- Name: idx_juicios_estado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_juicios_estado ON public.juicios_evaluativos USING btree ("Estado");


--
-- TOC entry 5011 (class 1259 OID 74245)
-- Name: idx_juicios_resultado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_juicios_resultado ON public.juicios_evaluativos USING btree ("Id_Resultado");


--
-- TOC entry 5012 (class 1259 OID 74247)
-- Name: idx_juicios_resultado_aprendiz; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_juicios_resultado_aprendiz ON public.juicios_evaluativos USING btree ("Id_Resultado", "Id_Aprendiz");


--
-- TOC entry 4984 (class 1259 OID 74248)
-- Name: idx_resultados_competencia; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_resultados_competencia ON public.resultados USING btree ("Id_Competencia");


--
-- TOC entry 4975 (class 1259 OID 63356)
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- TOC entry 5019 (class 1259 OID 81494)
-- Name: remisiones_radicado_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX remisiones_radicado_index ON public.remisiones USING btree (radicado);


--
-- TOC entry 4963 (class 1259 OID 63318)
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- TOC entry 4966 (class 1259 OID 63317)
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- TOC entry 5022 (class 2606 OID 63479)
-- Name: aprendiz aprendiz_id_ficha_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aprendiz
    ADD CONSTRAINT aprendiz_id_ficha_foreign FOREIGN KEY ("Id_Ficha") REFERENCES public.ficha("Id_Ficha");


--
-- TOC entry 5021 (class 2606 OID 63456)
-- Name: ficha ficha_id_programa_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ficha
    ADD CONSTRAINT ficha_id_programa_foreign FOREIGN KEY ("Id_Programa") REFERENCES public.programa("Id_Programa");


--
-- TOC entry 5023 (class 2606 OID 63503)
-- Name: juicios_evaluativos juicios_evaluativos_id_aprendiz_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.juicios_evaluativos
    ADD CONSTRAINT juicios_evaluativos_id_aprendiz_foreign FOREIGN KEY ("Id_Aprendiz") REFERENCES public.aprendiz("Id_Aprendiz");


--
-- TOC entry 5024 (class 2606 OID 63508)
-- Name: juicios_evaluativos juicios_evaluativos_id_funcionario_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.juicios_evaluativos
    ADD CONSTRAINT juicios_evaluativos_id_funcionario_foreign FOREIGN KEY ("Id_Funcionario") REFERENCES public.funcionario("Id_Funcionario");


--
-- TOC entry 5025 (class 2606 OID 63498)
-- Name: juicios_evaluativos juicios_evaluativos_id_resultado_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.juicios_evaluativos
    ADD CONSTRAINT juicios_evaluativos_id_resultado_foreign FOREIGN KEY ("Id_Resultado") REFERENCES public.resultados("Id_Resultado");


--
-- TOC entry 5026 (class 2606 OID 81484)
-- Name: remisiones remisiones_id_aprendiz_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.remisiones
    ADD CONSTRAINT remisiones_id_aprendiz_foreign FOREIGN KEY ("Id_Aprendiz") REFERENCES public.aprendiz("Id_Aprendiz") ON DELETE CASCADE;


--
-- TOC entry 5027 (class 2606 OID 81489)
-- Name: remisiones remisiones_id_ficha_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.remisiones
    ADD CONSTRAINT remisiones_id_ficha_foreign FOREIGN KEY ("Id_Ficha") REFERENCES public.ficha("Id_Ficha") ON DELETE SET NULL;


--
-- TOC entry 5020 (class 2606 OID 63412)
-- Name: resultados resultados_id_competencia_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resultados
    ADD CONSTRAINT resultados_id_competencia_foreign FOREIGN KEY ("Id_Competencia") REFERENCES public.competencia("Id_Competencia") ON DELETE CASCADE;


-- Completed on 2026-08-18 13:55:00

--
-- PostgreSQL database dump complete
--

\unrestrict HnZZuhgjmucLmlTVGgo1qgCS5OBkC5Kgh6ktqGX5uhy45HbQPrCERKbfpBSxC42

