--
-- PostgreSQL database dump
--

SET client_encoding = 'LATIN1';

--
-- TOC entry 8 (OID 17145)
-- Name: paths; Type: TABLE; Schema: public; Owner: bpiwowar
--

CREATE TABLE paths (
    id serial NOT NULL,
    "path" character varying(255) NOT NULL
);


--
-- TOC entry 10 (OID 17150)
-- Name: files; Type: TABLE; Schema: public; Owner: bpiwowar
--

CREATE TABLE files (
    id serial NOT NULL,
    collection character varying(16) NOT NULL,
    filename character varying(16) NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 12 (OID 17161)
-- Name: assessments; Type: TABLE; Schema: public; Owner: bpiwowar
--

CREATE TABLE assessments (
    exhaustivity integer NOT NULL,
    file integer NOT NULL,
    "startPath" integer NOT NULL,
    "endPath" integer NOT NULL,
    "idPool" integer NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 14 (OID 17181)
-- Name: keywords; Type: TABLE; Schema: public; Owner: bpiwowar
--

CREATE TABLE keywords (
    "idPool" integer NOT NULL,
    colour character(6) NOT NULL,
    keywords text NOT NULL,
    "mode" character varying(10) NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 15 (OID 17188)
-- Name: topics; Type: TABLE; Schema: public; Owner: bpiwowar
--

CREATE TABLE topics (
    id serial NOT NULL,
    definition text NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 16 (OID 17194)
-- Name: pools; Type: TABLE; Schema: public; Owner: bpiwowar
--

CREATE TABLE pools (
    idpool integer DEFAULT nextval('public.pools_id_seq'::text) NOT NULL,
    idtopic integer NOT NULL,
    login character varying(10) NOT NULL,
    name text NOT NULL,
    state character varying(10) NOT NULL,
    enabled boolean NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 6 (OID 17202)
-- Name: pools_id_seq; Type: SEQUENCE; Schema: public; Owner: bpiwowar
--

CREATE SEQUENCE pools_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- TOC entry 18 (OID 17224)
-- Name: assessmentsView; Type: VIEW; Schema: public; Owner: bpiwowar
--

CREATE VIEW "assessmentsView" AS
    SELECT assessments.exhaustivity, assessments."idPool", files.collection, files.filename, pathsstart."path" AS startxpath, pathsend."path" AS endxpath FROM (((assessments JOIN paths pathsstart ON ((assessments."startPath" = pathsstart.id))) JOIN paths pathsend ON ((assessments."endPath" = pathsend.id))) JOIN files ON ((assessments.file = files.id)));


--
-- TOC entry 5 (OID 17225)
-- Name: xpathstring; Type: DOMAIN; Schema: public; Owner: bpiwowar
--

CREATE DOMAIN xpathstring AS character varying(255) NOT NULL;


--
-- Data for TOC entry 30 (OID 17145)
-- Name: paths; Type: TABLE DATA; Schema: public; Owner: bpiwowar
--



--
-- Data for TOC entry 31 (OID 17150)
-- Name: files; Type: TABLE DATA; Schema: public; Owner: bpiwowar
--



--
-- Data for TOC entry 32 (OID 17161)
-- Name: assessments; Type: TABLE DATA; Schema: public; Owner: bpiwowar
--



--
-- Data for TOC entry 33 (OID 17181)
-- Name: keywords; Type: TABLE DATA; Schema: public; Owner: bpiwowar
--



--
-- Data for TOC entry 34 (OID 17188)
-- Name: topics; Type: TABLE DATA; Schema: public; Owner: bpiwowar
--

INSERT INTO topics VALUES (1, '<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE inex_topic SYSTEM "topic.dtd">
<inex_topic topic_id="177" query_type="CO" ct_no="76">
<title>Difference of two unordered labeled trees or change detection
in unordered labeled trees.</title>
<description>Find articles/elements that describe an algorithm for
calculating the difference between two unordered label trees, or
change detection for unordered labeled trees.</description>
<narrative>To be relevant, an article/element must describe an
algorithm for detecting changes in unordered trees, or an algorithm
for calculating the difference between two trees. Elements within
articles that analyse the complexity of the algorithm or compare other
algorithms for tree difference are also relevant. The motivation is to
implement an algorithm relevant for XML tree difference in an academic
context where bibliographic references are required. The ultimate
application is e-subscription and notification for
changes. </narrative>
<keywords>labeled tree, unordered tree, difference, diff, algorithm,
change detection, edit distance. </keywords>
</inex_topic>
');


--
-- Data for TOC entry 35 (OID 17194)
-- Name: pools; Type: TABLE DATA; Schema: public; Owner: bpiwowar
--

INSERT INTO pools VALUES (1, 1, 'demo', 'Demo pool', 'demo', true);


--
-- TOC entry 25 (OID 17153)
-- Name: unique_file_id; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY files
    ADD CONSTRAINT unique_file_id PRIMARY KEY (id);


--
-- TOC entry 24 (OID 17155)
-- Name: unique_file; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY files
    ADD CONSTRAINT unique_file UNIQUE (collection, filename);


--
-- TOC entry 23 (OID 17157)
-- Name: unique_path_id; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY paths
    ADD CONSTRAINT unique_path_id PRIMARY KEY (id);


--
-- TOC entry 22 (OID 17159)
-- Name: unique_path; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY paths
    ADD CONSTRAINT unique_path UNIQUE ("path");


--
-- TOC entry 26 (OID 17163)
-- Name: pk_assessment; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT pk_assessment PRIMARY KEY ("idPool", file, "startPath", "endPath");


--
-- TOC entry 29 (OID 17199)
-- Name: pkPool; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY pools
    ADD CONSTRAINT "pkPool" PRIMARY KEY (idpool);


--
-- TOC entry 28 (OID 17204)
-- Name: pkTopic; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY topics
    ADD CONSTRAINT "pkTopic" PRIMARY KEY (id);


--
-- TOC entry 27 (OID 17216)
-- Name: pkKeywords; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY keywords
    ADD CONSTRAINT "pkKeywords" PRIMARY KEY ("idPool", colour, "mode");


--
-- TOC entry 36 (OID 17169)
-- Name: validStartPath; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT "validStartPath" FOREIGN KEY ("startPath") REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 37 (OID 17173)
-- Name: validEndPath; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT "validEndPath" FOREIGN KEY ("endPath") REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 38 (OID 17177)
-- Name: validFile; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT "validFile" FOREIGN KEY (file) REFERENCES files(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 40 (OID 17206)
-- Name: validTopic; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY pools
    ADD CONSTRAINT "validTopic" FOREIGN KEY (idtopic) REFERENCES topics(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 39 (OID 17218)
-- Name: validPool; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY keywords
    ADD CONSTRAINT "validPool" FOREIGN KEY ("idPool") REFERENCES pools(idpool) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 19 (OID 17143)
-- Name: paths_id_seq; Type: SEQUENCE SET; Schema: public; Owner: bpiwowar
--

SELECT pg_catalog.setval('paths_id_seq', 1, false);


--
-- TOC entry 20 (OID 17148)
-- Name: files_id_seq; Type: SEQUENCE SET; Schema: public; Owner: bpiwowar
--

SELECT pg_catalog.setval('files_id_seq', 1, false);


--
-- TOC entry 21 (OID 17186)
-- Name: topics_id_seq; Type: SEQUENCE SET; Schema: public; Owner: bpiwowar
--

SELECT pg_catalog.setval('topics_id_seq', 1, true);


--
-- TOC entry 7 (OID 17202)
-- Name: pools_id_seq; Type: SEQUENCE SET; Schema: public; Owner: bpiwowar
--

SELECT pg_catalog.setval('pools_id_seq', 1, true);


SET SESSION AUTHORIZATION 'postgres';

--
-- TOC entry 3 (OID 2200)
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'Standard public schema';


SET SESSION AUTHORIZATION 'bpiwowar';

--
-- TOC entry 9 (OID 17145)
-- Name: TABLE paths; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON TABLE paths IS 'Paths indexed by an ID';


--
-- TOC entry 11 (OID 17150)
-- Name: TABLE files; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON TABLE files IS 'Files index by id';


--
-- TOC entry 13 (OID 17161)
-- Name: TABLE assessments; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON TABLE assessments IS 'Assessments';


--
-- TOC entry 17 (OID 17194)
-- Name: COLUMN pools.state; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON COLUMN pools.state IS 'Distinguish official topics, etc.';


