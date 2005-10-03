--
-- PostgreSQL database dump
--

SET client_encoding = 'SQL_ASCII';

--
-- TOC entry 17 (OID 4150753)
-- Name: paths; Type: TABLE; Schema: inex_2005; Owner: inex
--

CREATE TABLE paths (
    id serial NOT NULL,
    "path" character varying(255) NOT NULL
);


--
-- TOC entry 19 (OID 4150758)
-- Name: files; Type: TABLE; Schema: inex_2005; Owner: inex
--

CREATE TABLE files (
    id serial NOT NULL,
    collection character varying(16) NOT NULL,
    filename character varying(16) NOT NULL,
    title text,
    "type" character varying(5),
    parent integer,
    pre integer,
    post integer
) WITHOUT OIDS;


--
-- TOC entry 21 (OID 4150764)
-- Name: assessments; Type: TABLE; Schema: inex_2005; Owner: inex
--

CREATE TABLE assessments (
    exhaustivity integer,
    idfile integer NOT NULL,
    startpath integer NOT NULL,
    endpath integer NOT NULL,
    idpool integer NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 25 (OID 4150766)
-- Name: keywords; Type: TABLE; Schema: inex_2005; Owner: inex
--

CREATE TABLE keywords (
    idpool integer NOT NULL,
    colour character(6) NOT NULL,
    keywords text NOT NULL,
    "mode" character varying(10) NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 26 (OID 4150773)
-- Name: topics; Type: TABLE; Schema: inex_2005; Owner: inex
--

CREATE TABLE topics (
    id serial NOT NULL,
    definition text NOT NULL,
    "type" character varying(10)
) WITHOUT OIDS;


--
-- TOC entry 28 (OID 4150779)
-- Name: pools; Type: TABLE; Schema: inex_2005; Owner: inex
--

CREATE TABLE pools (
    id integer DEFAULT nextval('public.pools_id_seq'::text) NOT NULL,
    idtopic integer NOT NULL,
    name text NOT NULL,
    state character varying(10) NOT NULL,
    enabled boolean NOT NULL,
    login character varying(16) NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 8 (OID 4150785)
-- Name: pools_id_seq; Type: SEQUENCE; Schema: inex_2005; Owner: inex
--

CREATE SEQUENCE pools_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- TOC entry 30 (OID 4150787)
-- Name: filestatus; Type: TABLE; Schema: inex_2005; Owner: inex
--

CREATE TABLE filestatus (
    idpool integer NOT NULL,
    idfile integer NOT NULL,
    "version" integer DEFAULT 0 NOT NULL,
    inpool boolean DEFAULT false NOT NULL,
    status smallint DEFAULT 0 NOT NULL,
    CONSTRAINT "validVersion" CHECK (("version" > 0))
) WITHOUT OIDS;


--
-- TOC entry 35 (OID 4150793)
-- Name: history; Type: TABLE; Schema: inex_2005; Owner: inex
--

CREATE TABLE history (
    idpool integer NOT NULL,
    idfile integer NOT NULL,
    startpath integer NOT NULL,
    endpath integer NOT NULL,
    "time" numeric(14,0) NOT NULL,
    "action" character(3) NOT NULL
);


--
-- TOC entry 37 (OID 4150797)
-- Name: assessmentsview; Type: VIEW; Schema: inex_2005; Owner: inex
--

CREATE VIEW assessmentsview AS
    SELECT assessments.exhaustivity, assessments.idpool, files.collection, files.filename, pathsstart."path" AS startpart, pathsend."path" AS endpath FROM (((assessments JOIN paths pathsstart ON ((assessments.startpath = pathsstart.id))) JOIN paths pathsend ON ((assessments.endpath = pathsend.id))) JOIN files ON ((assessments.idfile = files.id)));


--
-- TOC entry 38 (OID 4150798)
-- Name: topicelements; Type: TABLE; Schema: inex_2005; Owner: inex
--

CREATE TABLE topicelements (
    idfile integer NOT NULL,
    idtopic integer NOT NULL,
    idpath integer NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 40 (OID 4150805)
-- Name: topicelementsview; Type: VIEW; Schema: inex_2005; Owner: inex
--

CREATE VIEW topicelementsview AS
    SELECT topicelements.idfile, topicelements.idpath, topicelements.idtopic, files.filename, paths."path" FROM ((topicelements JOIN files ON ((topicelements.idfile = files.id))) JOIN paths ON ((topicelements.idpath = paths.id)));




--
-- TOC entry 56 (OID 4150806)
-- Name: unique_file_id; Type: CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY files
    ADD CONSTRAINT unique_file_id PRIMARY KEY (id);


--
-- TOC entry 55 (OID 4150808)
-- Name: unique_file; Type: CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY files
    ADD CONSTRAINT unique_file UNIQUE (collection, filename);


--
-- TOC entry 53 (OID 4150810)
-- Name: unique_path_id; Type: CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY paths
    ADD CONSTRAINT unique_path_id PRIMARY KEY (id);


--
-- TOC entry 52 (OID 4150812)
-- Name: unique_path; Type: CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY paths
    ADD CONSTRAINT unique_path UNIQUE ("path");


--
-- TOC entry 60 (OID 4150814)
-- Name: pkPool; Type: CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY pools
    ADD CONSTRAINT "pkPool" PRIMARY KEY (id);


--
-- TOC entry 59 (OID 4150816)
-- Name: pkTopic; Type: CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY topics
    ADD CONSTRAINT "pkTopic" PRIMARY KEY (id);


--
-- TOC entry 58 (OID 4150818)
-- Name: pkKeywords; Type: CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY keywords
    ADD CONSTRAINT "pkKeywords" PRIMARY KEY (idpool, colour, "mode");


--
-- TOC entry 61 (OID 4150820)
-- Name: pkAssessmentVersion; Type: CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY filestatus
    ADD CONSTRAINT "pkAssessmentVersion" PRIMARY KEY (idpool, idfile);


--
-- TOC entry 57 (OID 4150822)
-- Name: pk_assessments; Type: CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT pk_assessments PRIMARY KEY (idpool, idfile, startpath, endpath);


--
-- TOC entry 62 (OID 4150824)
-- Name: pk_topicelements; Type: CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY topicelements
    ADD CONSTRAINT pk_topicelements PRIMARY KEY (idtopic, idfile, idpath);



--
-- TOC entry 68 (OID 4150826)
-- Name: validStartPath; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT "validStartPath" FOREIGN KEY (startpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 69 (OID 4150830)
-- Name: validEndPath; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT "validEndPath" FOREIGN KEY (endpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 72 (OID 4150834)
-- Name: validTopic; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY pools
    ADD CONSTRAINT "validTopic" FOREIGN KEY (idtopic) REFERENCES topics(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 71 (OID 4150838)
-- Name: validPool; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY keywords
    ADD CONSTRAINT "validPool" FOREIGN KEY (idpool) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 73 (OID 4150842)
-- Name: validPool; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY filestatus
    ADD CONSTRAINT "validPool" FOREIGN KEY (idpool) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 74 (OID 4150846)
-- Name: validFile; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY filestatus
    ADD CONSTRAINT "validFile" FOREIGN KEY (idfile) REFERENCES files(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 67 (OID 4150850)
-- Name: validParent; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY files
    ADD CONSTRAINT "validParent" FOREIGN KEY (parent) REFERENCES files(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 75 (OID 4150854)
-- Name: validPool; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY history
    ADD CONSTRAINT "validPool" FOREIGN KEY (idpool) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 76 (OID 4150858)
-- Name: validFile; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY history
    ADD CONSTRAINT "validFile" FOREIGN KEY (idfile) REFERENCES files(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 77 (OID 4150862)
-- Name: validStartPath; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY history
    ADD CONSTRAINT "validStartPath" FOREIGN KEY (startpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 78 (OID 4150866)
-- Name: validEndPath; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY history
    ADD CONSTRAINT "validEndPath" FOREIGN KEY (endpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 79 (OID 4150870)
-- Name: validFile; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY topicelements
    ADD CONSTRAINT "validFile" FOREIGN KEY (idfile) REFERENCES files(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 80 (OID 4150874)
-- Name: validTopic; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY topicelements
    ADD CONSTRAINT "validTopic" FOREIGN KEY (idtopic) REFERENCES topics(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 81 (OID 4150878)
-- Name: validPath; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY topicelements
    ADD CONSTRAINT "validPath" FOREIGN KEY (idpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 70 (OID 4150882)
-- Name: validPoolFile; Type: FK CONSTRAINT; Schema: inex_2005; Owner: inex
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT "validPoolFile" FOREIGN KEY (idpool, idfile) REFERENCES filestatus(idpool, idfile) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 18 (OID 4150753)
-- Name: TABLE paths; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON TABLE paths IS 'Paths indexed by an ID';


--
-- TOC entry 20 (OID 4150758)
-- Name: TABLE files; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON TABLE files IS 'Files index by id';


--
-- TOC entry 22 (OID 4150764)
-- Name: TABLE assessments; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON TABLE assessments IS 'Assessments';


--
-- TOC entry 23 (OID 4150764)
-- Name: COLUMN assessments.exhaustivity; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON COLUMN assessments.exhaustivity IS 'For a passage: positive is exh, 0 is unknown, negative for old (exh=-1-value), null for not validated';


--
-- TOC entry 24 (OID 4150764)
-- Name: COLUMN assessments.endpath; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON COLUMN assessments.endpath IS 'Might point to an empty path';


--
-- TOC entry 27 (OID 4150773)
-- Name: COLUMN topics."type"; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON COLUMN topics."type" IS 'Type of the query: CO, CO+S, CAS';


--
-- TOC entry 29 (OID 4150779)
-- Name: COLUMN pools.state; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON COLUMN pools.state IS 'Distinguish official topics, etc.';


--
-- TOC entry 31 (OID 4150787)
-- Name: TABLE filestatus; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON TABLE filestatus IS 'Keeps information about the assessment process';


--
-- TOC entry 32 (OID 4150787)
-- Name: COLUMN filestatus."version"; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON COLUMN filestatus."version" IS 'Current version of the file.';


--
-- TOC entry 33 (OID 4150787)
-- Name: COLUMN filestatus.inpool; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON COLUMN filestatus.inpool IS 'Was the file in the original pool?';


--
-- TOC entry 34 (OID 4150787)
-- Name: COLUMN filestatus.status; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON COLUMN filestatus.status IS '0 = highlighting, 1 = assessing, 2 = done';


--
-- TOC entry 36 (OID 4150793)
-- Name: TABLE history; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON TABLE history IS 'Log the assessor actions';


--
-- TOC entry 39 (OID 4150798)
-- Name: TABLE topicelements; Type: COMMENT; Schema: inex_2005; Owner: inex
--

COMMENT ON TABLE topicelements IS 'Keeps the elements which should be highlighted for a topic';


