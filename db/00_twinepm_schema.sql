--
-- PostgreSQL database dump
--

-- Dumped from database version 9.5.8
-- Dumped by pg_dump version 9.5.8

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'utf8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

--
-- Name: update_expires_column(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION update_expires_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
begin
NEW.expires = now();
return NEW;
end;
$$;


ALTER FUNCTION public.update_expires_column() OWNER TO postgres;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: accounts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE accounts (
    id bigint NOT NULL,
    name character varying(500),
    name_visible boolean DEFAULT true NOT NULL,
    description text DEFAULT ''::text NOT NULL,
    time_created_visible boolean DEFAULT true NOT NULL,
    email text DEFAULT ''::text NOT NULL,
    email_visible boolean DEFAULT false NOT NULL,
    date_style character varying(100) DEFAULT 'mmdd'::character varying NOT NULL,
    time_style character varying(100) DEFAULT '12h'::character varying NOT NULL,
    homepage text DEFAULT ''::text NOT NULL,
    time_created timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL
);


ALTER TABLE accounts OWNER TO postgres;

--
-- Name: authorizations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE authorizations (
    user_id bigint NOT NULL,
    oauth_token text NOT NULL,
    time_created timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    client text NOT NULL,
    global_authorization_id integer NOT NULL,
    scopes jsonb NOT NULL,
    ip inet
);


ALTER TABLE authorizations OWNER TO postgres;

--
-- Name: authorizations_global_authorization_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE authorizations_global_authorization_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE authorizations_global_authorization_id_seq OWNER TO postgres;

--
-- Name: authorizations_global_authorization_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE authorizations_global_authorization_id_seq OWNED BY authorizations.global_authorization_id;


--
-- Name: credentials; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE credentials (
    id bigint NOT NULL,
    name character varying(500),
    hash character varying(255) NOT NULL,
    validated boolean DEFAULT false NOT NULL,
    active boolean DEFAULT true NOT NULL
);


ALTER TABLE credentials OWNER TO postgres;

--
-- Name: credentials_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE credentials_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE credentials_id_seq OWNER TO postgres;

--
-- Name: credentials_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE credentials_id_seq OWNED BY credentials.id;


--
-- Name: deleted_versions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE deleted_versions (
    package_id bigint NOT NULL,
    version character varying(100) NOT NULL
);


ALTER TABLE deleted_versions OWNER TO postgres;

--
-- Name: email_validation; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE email_validation (
    id bigint NOT NULL,
    token text NOT NULL,
    time_reserved timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL
);


ALTER TABLE email_validation OWNER TO postgres;

--
-- Name: logins; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE logins (
    id bigint NOT NULL,
    token text NOT NULL
);


ALTER TABLE logins OWNER TO postgres;

--
-- Name: packages; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE packages (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    author_id bigint NOT NULL,
    owner_id bigint NOT NULL,
    description text NOT NULL,
    homepage text DEFAULT ''::text NOT NULL,
    type character varying(100) NOT NULL,
    current_version text,
    time_created timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    keywords jsonb DEFAULT '[]'::jsonb NOT NULL,
    tag text DEFAULT ''::text NOT NULL
);


ALTER TABLE packages OWNER TO postgres;

--
-- Name: versions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE versions (
    package_id bigint NOT NULL,
    global_version_id bigint NOT NULL,
    js text NOT NULL,
    css text NOT NULL,
    description text,
    homepage text,
    version character varying(100) NOT NULL,
    author_id bigint NOT NULL,
    time_created timestamp without time zone DEFAULT timezone('utc'::text, now()),
    name character varying(255) NOT NULL
);


ALTER TABLE versions OWNER TO postgres;

--
-- Name: packages_global_version_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE packages_global_version_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE packages_global_version_id_seq OWNER TO postgres;

--
-- Name: packages_global_version_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE packages_global_version_id_seq OWNED BY versions.global_version_id;


--
-- Name: packages_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE packages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE packages_id_seq OWNER TO postgres;

--
-- Name: packages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE packages_id_seq OWNED BY packages.id;


--
-- Name: global_authorization_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY authorizations ALTER COLUMN global_authorization_id SET DEFAULT nextval('authorizations_global_authorization_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY credentials ALTER COLUMN id SET DEFAULT nextval('credentials_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY packages ALTER COLUMN id SET DEFAULT nextval('packages_id_seq'::regclass);


--
-- Name: global_version_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY versions ALTER COLUMN global_version_id SET DEFAULT nextval('packages_global_version_id_seq'::regclass);


--
-- Name: accounts_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_name_key UNIQUE (name);


--
-- Name: accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


--
-- Name: authorizations_oauth_token_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY authorizations
    ADD CONSTRAINT authorizations_oauth_token_key UNIQUE (oauth_token);


--
-- Name: authorizations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY authorizations
    ADD CONSTRAINT authorizations_pkey PRIMARY KEY (global_authorization_id);


--
-- Name: credentials_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY credentials
    ADD CONSTRAINT credentials_name_key UNIQUE (name);


--
-- Name: credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY credentials
    ADD CONSTRAINT credentials_pkey PRIMARY KEY (id);


--
-- Name: deleted_versions_primary_id_version_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY deleted_versions
    ADD CONSTRAINT deleted_versions_primary_id_version_key PRIMARY KEY (package_id, version);


--
-- Name: email_validation_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY email_validation
    ADD CONSTRAINT email_validation_pkey PRIMARY KEY (id);


--
-- Name: email_validation_token_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY email_validation
    ADD CONSTRAINT email_validation_token_key UNIQUE (token);


--
-- Name: logins_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY logins
    ADD CONSTRAINT logins_pkey PRIMARY KEY (id);


--
-- Name: logins_token_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY logins
    ADD CONSTRAINT logins_token_key UNIQUE (token);


--
-- Name: packages_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY packages
    ADD CONSTRAINT packages_name_key UNIQUE (name);


--
-- Name: packages_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY packages
    ADD CONSTRAINT packages_pkey PRIMARY KEY (id);


--
-- Name: versions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY versions
    ADD CONSTRAINT versions_pkey PRIMARY KEY (global_version_id);


--
-- Name: versions_unique_id_version_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY versions
    ADD CONSTRAINT versions_unique_id_version_key UNIQUE (package_id, version);


--
-- Name: accounts_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_id_fkey FOREIGN KEY (id) REFERENCES credentials(id);


--
-- Name: accounts_name_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_name_fkey FOREIGN KEY (name) REFERENCES credentials(name) ON UPDATE CASCADE;


--
-- Name: authorizations_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY authorizations
    ADD CONSTRAINT authorizations_user_id_fkey FOREIGN KEY (user_id) REFERENCES credentials(id);


--
-- Name: email_validation_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY email_validation
    ADD CONSTRAINT email_validation_id_fkey FOREIGN KEY (id) REFERENCES credentials(id);


--
-- Name: logins_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY logins
    ADD CONSTRAINT logins_id_fkey FOREIGN KEY (id) REFERENCES credentials(id);


--
-- Name: packages_current_version_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY packages
    ADD CONSTRAINT packages_current_version_fkey FOREIGN KEY (id, current_version) REFERENCES versions(package_id, version);


--
-- Name: packages_owner_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY packages
    ADD CONSTRAINT packages_owner_id_fkey FOREIGN KEY (owner_id) REFERENCES accounts(id);


--
-- Name: versions_name_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY versions
    ADD CONSTRAINT versions_name_fkey FOREIGN KEY (name) REFERENCES packages(name) ON UPDATE CASCADE;


--
-- Name: versions_package_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY versions
    ADD CONSTRAINT versions_package_id_fkey FOREIGN KEY (package_id) REFERENCES packages(id);


--
-- PostgreSQL database dump complete
--