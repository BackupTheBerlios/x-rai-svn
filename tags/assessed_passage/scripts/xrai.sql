--
-- PostgreSQL database dump
--

SET client_encoding = 'LATIN1';

ALTER TABLE ONLY public.assessments DROP CONSTRAINT "validPoolFile";
ALTER TABLE ONLY public.topicelements DROP CONSTRAINT "validPath";
ALTER TABLE ONLY public.topicelements DROP CONSTRAINT "validTopic";
ALTER TABLE ONLY public.topicelements DROP CONSTRAINT "validFile";
ALTER TABLE ONLY public.history DROP CONSTRAINT "validEndPath";
ALTER TABLE ONLY public.history DROP CONSTRAINT "validStartPath";
ALTER TABLE ONLY public.history DROP CONSTRAINT "validFile";
ALTER TABLE ONLY public.history DROP CONSTRAINT "validPool";
ALTER TABLE ONLY public.files DROP CONSTRAINT "validParent";
ALTER TABLE ONLY public.filestatus DROP CONSTRAINT "validFile";
ALTER TABLE ONLY public.filestatus DROP CONSTRAINT "validPool";
ALTER TABLE ONLY public.keywords DROP CONSTRAINT "validPool";
ALTER TABLE ONLY public.pools DROP CONSTRAINT "validTopic";
ALTER TABLE ONLY public.assessments DROP CONSTRAINT "validEndPath";
ALTER TABLE ONLY public.assessments DROP CONSTRAINT "validStartPath";
ALTER TABLE ONLY public.topicelements DROP CONSTRAINT pk_topicelements;
ALTER TABLE ONLY public.assessments DROP CONSTRAINT pk_assessments;
ALTER TABLE ONLY public.filestatus DROP CONSTRAINT "pkAssessmentVersion";
ALTER TABLE ONLY public.keywords DROP CONSTRAINT "pkKeywords";
ALTER TABLE ONLY public.topics DROP CONSTRAINT "pkTopic";
ALTER TABLE ONLY public.pools DROP CONSTRAINT "pkPool";
ALTER TABLE ONLY public.paths DROP CONSTRAINT unique_path;
ALTER TABLE ONLY public.paths DROP CONSTRAINT unique_path_id;
ALTER TABLE ONLY public.files DROP CONSTRAINT unique_file;
ALTER TABLE ONLY public.files DROP CONSTRAINT unique_file_id;
DROP VIEW public.topicelementsview;
DROP VIEW public.statusview;
DROP TABLE public.topicelements;
DROP VIEW public.assessmentsview;
DROP TABLE public.history;
DROP TABLE public.filestatus;
DROP DOMAIN public.xpathstring;
DROP SEQUENCE public.pools_id_seq;
DROP TABLE public.pools;
DROP TABLE public.topics;
DROP TABLE public.keywords;
DROP TABLE public.assessments;
DROP TABLE public.files;
DROP TABLE public.paths;
SET SESSION AUTHORIZATION 'postgres';

--
-- TOC entry 4 (OID 2200)
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
GRANT ALL ON SCHEMA public TO PUBLIC;


SET SESSION AUTHORIZATION 'bpiwowar';

--
-- TOC entry 7 (OID 17145)
-- Name: paths; Type: TABLE; Schema: public; Owner: bpiwowar
--

CREATE TABLE paths (
    id serial NOT NULL,
    "path" character varying(255) NOT NULL
);


--
-- TOC entry 9 (OID 17150)
-- Name: files; Type: TABLE; Schema: public; Owner: bpiwowar
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
-- TOC entry 11 (OID 17156)
-- Name: assessments; Type: TABLE; Schema: public; Owner: bpiwowar
--

CREATE TABLE assessments (
    exhaustivity integer,
    idfile integer NOT NULL,
    startpath integer NOT NULL,
    endpath integer NOT NULL,
    idpool integer NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 15 (OID 17158)
-- Name: keywords; Type: TABLE; Schema: public; Owner: bpiwowar
--

CREATE TABLE keywords (
    idpool integer NOT NULL,
    colour character(6) NOT NULL,
    keywords text NOT NULL,
    "mode" character varying(10) NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 16 (OID 17165)
-- Name: topics; Type: TABLE; Schema: public; Owner: bpiwowar
--

CREATE TABLE topics (
    id serial NOT NULL,
    definition text NOT NULL,
    "type" character varying(10)
) WITHOUT OIDS;


--
-- TOC entry 18 (OID 17171)
-- Name: pools; Type: TABLE; Schema: public; Owner: bpiwowar
--

CREATE TABLE pools (
    id integer DEFAULT nextval('public.pools_id_seq'::text) NOT NULL,
    idtopic integer NOT NULL,
    login character varying(10) NOT NULL,
    name text NOT NULL,
    state character varying(10) NOT NULL,
    enabled boolean NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 6 (OID 17177)
-- Name: pools_id_seq; Type: SEQUENCE; Schema: public; Owner: bpiwowar
--

CREATE SEQUENCE pools_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- TOC entry 20 (OID 17180)
-- Name: filestatus; Type: TABLE; Schema: public; Owner: bpiwowar
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
-- TOC entry 25 (OID 17188)
-- Name: history; Type: TABLE; Schema: public; Owner: bpiwowar
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
-- TOC entry 27 (OID 17192)
-- Name: assessmentsview; Type: VIEW; Schema: public; Owner: bpiwowar
--

CREATE VIEW assessmentsview AS
    SELECT assessments.exhaustivity, assessments.idpool, files.collection, files.filename, pathsstart."path" AS startpart, pathsend."path" AS endpath FROM (((assessments JOIN paths pathsstart ON ((assessments.startpath = pathsstart.id))) JOIN paths pathsend ON ((assessments.endpath = pathsend.id))) JOIN files ON ((assessments.idfile = files.id)));


--
-- TOC entry 28 (OID 17193)
-- Name: topicelements; Type: TABLE; Schema: public; Owner: bpiwowar
--

CREATE TABLE topicelements (
    idfile integer NOT NULL,
    idtopic integer NOT NULL,
    idpath integer NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 30 (OID 17197)
-- Name: statusview; Type: VIEW; Schema: public; Owner: bpiwowar
--

CREATE VIEW statusview AS
    SELECT root.id AS rootid, anc.filename, ta.status, ta.inpool, count(*) AS count FROM files root, files anc, files f, filestatus ta WHERE ((((anc.parent = root.id) AND (anc.pre <= f.pre)) AND (anc.post >= f.pre)) AND (ta.idfile = f.id)) GROUP BY root.id, anc.filename, ta.status, ta.inpool;


--
-- TOC entry 31 (OID 36059)
-- Name: topicelementsview; Type: VIEW; Schema: public; Owner: bpiwowar
--

CREATE VIEW topicelementsview AS
    SELECT topicelements.idfile, topicelements.idpath, topicelements.idtopic, files.filename, paths."path" FROM ((topicelements JOIN files ON ((topicelements.idfile = files.id))) JOIN paths ON ((topicelements.idpath = paths.id)));


--
-- TOC entry 35 (OID 35967)
-- Name: unique_file_id; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY files
    ADD CONSTRAINT unique_file_id PRIMARY KEY (id);


--
-- TOC entry 34 (OID 35969)
-- Name: unique_file; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY files
    ADD CONSTRAINT unique_file UNIQUE (collection, filename);


--
-- TOC entry 33 (OID 35971)
-- Name: unique_path_id; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY paths
    ADD CONSTRAINT unique_path_id PRIMARY KEY (id);


--
-- TOC entry 32 (OID 35973)
-- Name: unique_path; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY paths
    ADD CONSTRAINT unique_path UNIQUE ("path");


--
-- TOC entry 39 (OID 35975)
-- Name: pkPool; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY pools
    ADD CONSTRAINT "pkPool" PRIMARY KEY (id);


--
-- TOC entry 38 (OID 35977)
-- Name: pkTopic; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY topics
    ADD CONSTRAINT "pkTopic" PRIMARY KEY (id);


--
-- TOC entry 37 (OID 35979)
-- Name: pkKeywords; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY keywords
    ADD CONSTRAINT "pkKeywords" PRIMARY KEY (idpool, colour, "mode");


--
-- TOC entry 40 (OID 35981)
-- Name: pkAssessmentVersion; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY filestatus
    ADD CONSTRAINT "pkAssessmentVersion" PRIMARY KEY (idpool, idfile);


--
-- TOC entry 36 (OID 35985)
-- Name: pk_assessments; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT pk_assessments PRIMARY KEY (idpool, idfile, startpath, endpath);


--
-- TOC entry 41 (OID 35987)
-- Name: pk_topicelements; Type: CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY topicelements
    ADD CONSTRAINT pk_topicelements PRIMARY KEY (idtopic, idfile, idpath);


--
-- TOC entry 43 (OID 35989)
-- Name: validStartPath; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT "validStartPath" FOREIGN KEY (startpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 44 (OID 35993)
-- Name: validEndPath; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT "validEndPath" FOREIGN KEY (endpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 47 (OID 35997)
-- Name: validTopic; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY pools
    ADD CONSTRAINT "validTopic" FOREIGN KEY (idtopic) REFERENCES topics(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 46 (OID 36001)
-- Name: validPool; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY keywords
    ADD CONSTRAINT "validPool" FOREIGN KEY (idpool) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 48 (OID 36005)
-- Name: validPool; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY filestatus
    ADD CONSTRAINT "validPool" FOREIGN KEY (idpool) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 49 (OID 36009)
-- Name: validFile; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY filestatus
    ADD CONSTRAINT "validFile" FOREIGN KEY (idfile) REFERENCES files(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 42 (OID 36021)
-- Name: validParent; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY files
    ADD CONSTRAINT "validParent" FOREIGN KEY (parent) REFERENCES files(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 50 (OID 36025)
-- Name: validPool; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY history
    ADD CONSTRAINT "validPool" FOREIGN KEY (idpool) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 51 (OID 36029)
-- Name: validFile; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY history
    ADD CONSTRAINT "validFile" FOREIGN KEY (idfile) REFERENCES files(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 52 (OID 36033)
-- Name: validStartPath; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY history
    ADD CONSTRAINT "validStartPath" FOREIGN KEY (startpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 53 (OID 36037)
-- Name: validEndPath; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY history
    ADD CONSTRAINT "validEndPath" FOREIGN KEY (endpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 54 (OID 36041)
-- Name: validFile; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY topicelements
    ADD CONSTRAINT "validFile" FOREIGN KEY (idfile) REFERENCES files(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 55 (OID 36045)
-- Name: validTopic; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY topicelements
    ADD CONSTRAINT "validTopic" FOREIGN KEY (idtopic) REFERENCES topics(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 56 (OID 36049)
-- Name: validPath; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY topicelements
    ADD CONSTRAINT "validPath" FOREIGN KEY (idpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 45 (OID 36053)
-- Name: validPoolFile; Type: FK CONSTRAINT; Schema: public; Owner: bpiwowar
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT "validPoolFile" FOREIGN KEY (idpool, idfile) REFERENCES filestatus(idpool, idfile) ON UPDATE CASCADE ON DELETE CASCADE;


SET SESSION AUTHORIZATION 'postgres';

--
-- TOC entry 3 (OID 2200)
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'Standard public schema';


SET SESSION AUTHORIZATION 'bpiwowar';

--
-- TOC entry 8 (OID 17145)
-- Name: TABLE paths; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON TABLE paths IS 'Paths indexed by an ID';


--
-- TOC entry 10 (OID 17150)
-- Name: TABLE files; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON TABLE files IS 'Files index by id';


--
-- TOC entry 12 (OID 17156)
-- Name: TABLE assessments; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON TABLE assessments IS 'Assessments';


--
-- TOC entry 13 (OID 17156)
-- Name: COLUMN assessments.exhaustivity; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON COLUMN assessments.exhaustivity IS 'For a passage: positive is exh, 0 is unknown, negative for old (exh=-1-value), null for not validated';


--
-- TOC entry 14 (OID 17156)
-- Name: COLUMN assessments.endpath; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON COLUMN assessments.endpath IS 'Might point to an empty path';


--
-- TOC entry 17 (OID 17165)
-- Name: COLUMN topics."type"; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON COLUMN topics."type" IS 'Type of the query: CO, CO+S, CAS';


--
-- TOC entry 19 (OID 17171)
-- Name: COLUMN pools.state; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON COLUMN pools.state IS 'Distinguish official topics, etc.';


--
-- TOC entry 21 (OID 17180)
-- Name: TABLE filestatus; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON TABLE filestatus IS 'Keeps information about the assessment process';


--
-- TOC entry 22 (OID 17180)
-- Name: COLUMN filestatus."version"; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON COLUMN filestatus."version" IS 'Current version of the file.';


--
-- TOC entry 23 (OID 17180)
-- Name: COLUMN filestatus.inpool; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON COLUMN filestatus.inpool IS 'Was the file in the original pool?';


--
-- TOC entry 24 (OID 17180)
-- Name: COLUMN filestatus.status; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON COLUMN filestatus.status IS '0 = highlighting, 1 = assessing, 2 = done';


--
-- TOC entry 26 (OID 17188)
-- Name: TABLE history; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON TABLE history IS 'Log the assessor actions';


--
-- TOC entry 29 (OID 17193)
-- Name: TABLE topicelements; Type: COMMENT; Schema: public; Owner: bpiwowar
--

COMMENT ON TABLE topicelements IS 'Keeps the elements which should be highlighted for a topic';


