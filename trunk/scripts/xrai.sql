--
-- PostgreSQL database dump
--

-- pg_dump --no-owner --schema_only --schema=inex_2006

-- SET search_path = inex_2006, pg_catalog;

SET default_with_oids = false;

--
-- Name: assessments; Type: TABLE; Schema: inex_2006; Owner: inex; Tablespace: 
--

CREATE TABLE assessments (
    exhaustivity integer,
    idfile integer NOT NULL,
    startpath integer NOT NULL,
    endpath integer NOT NULL,
    idpool integer NOT NULL
) without OID;



--
-- Name: TABLE assessments; Type: COMMENT; Schema: inex_2006; Owner: inex
--

COMMENT ON TABLE assessments IS 'Assessments';


--
-- Name: COLUMN assessments.exhaustivity; Type: COMMENT; Schema: inex_2006; Owner: inex
--

COMMENT ON COLUMN assessments.exhaustivity IS 'For a passage: positive is exh, 0 is unknown, negative for old (exh=-1-value), null for not validated';


--
-- Name: COLUMN assessments.endpath; Type: COMMENT; Schema: inex_2006; Owner: inex
--

COMMENT ON COLUMN assessments.endpath IS 'Might point to an empty path';


--
-- Name: files; Type: TABLE; Schema: inex_2006; Owner: inex; Tablespace: 
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
) without OID;

COMMENT ON TABLE files IS 'Files index by id';
CREATE INDEX "parents" ON files USING btree (parent);

--
-- Name: paths; Type: TABLE; Schema: inex_2006; Owner: inex; Tablespace: 
--

CREATE TABLE paths (
    id serial NOT NULL,
    path character varying(255) NOT NULL
);
ALTER TABLE ONLY paths ALTER COLUMN id SET STATISTICS 1;
COMMENT ON TABLE paths IS 'Paths indexed by an ID';


--
-- Name: assessmentsview; Type: VIEW; Schema: inex_2006; Owner: inex
--

CREATE VIEW assessmentsview AS
    SELECT assessments.exhaustivity, assessments.idpool, files.collection, files.filename, pathsstart."path" AS startpart, pathsend."path" AS endpath FROM (((assessments JOIN paths pathsstart ON ((assessments.startpath = pathsstart.id))) JOIN paths pathsend ON ((assessments.endpath = pathsend.id))) JOIN files ON ((assessments.idfile = files.id)));

--
-- Name: filestatus; Type: TABLE; Schema: inex_2006; Owner: inex; Tablespace: 
--

CREATE TABLE filestatus (
    idpool integer NOT NULL,
    idfile integer NOT NULL,
    version integer DEFAULT 0 NOT NULL,
    inpool boolean DEFAULT false NOT NULL,
    status smallint DEFAULT 0 NOT NULL,
    hasrelevant boolean DEFAULT false NOT NULL,
    bep int NOT NULL,
    CONSTRAINT "validVersion" CHECK (("version" > 0))
) WITHOUT OID;

ALTER TABLE filestatus ADD CONSTRAINT bep_path FOREIGN KEY (bep) REFERENCES paths(id) ON UPDATE CASCADE;


COMMENT ON TABLE filestatus IS 'Keeps information about the assessment process';
COMMENT ON COLUMN filestatus.version IS 'Current version of the file.';
COMMENT ON COLUMN filestatus.inpool IS 'Was the file in the original pool?';
COMMENT ON COLUMN filestatus.status IS '0 = highlighting, 1 = assessing, 2 = done';
COMMENT ON COLUMN filestatus.hasrelevant IS 'Flag to know if there is some highlighted passages in the file';


--
-- Name: history; Type: TABLE
--

CREATE TABLE history (
    idpool integer NOT NULL,
    idfile integer NOT NULL,
    "time" numeric(14,0) NOT NULL,
    CONSTRAINT pk_history PRIMARY KEY (idpool, idfile, time)
);
ALTER TABLE ONLY history
    ADD CONSTRAINT "validPool" FOREIGN KEY (idpool) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE ONLY history
    ADD CONSTRAINT "validFile" FOREIGN KEY (idfile) REFERENCES files(id) ON UPDATE CASCADE ON DELETE RESTRICT;

--
-- Name: history; Type: TABLE; Schema: inex_2006; Owner: inex; Tablespace: 
--

CREATE TABLE log (
    idpool integer NOT NULL,
    idfile integer NOT NULL,
    startpath integer,
    endpath integer,
    "time" numeric(14,0) NOT NULL,
    "action" character(10) NOT NULL
);

COMMENT ON TABLE log IS 'Log the assessor actions';
CREATE INDEX "action" ON log USING btree ("action");
CREATE INDEX "time" ON log USING btree ("time");
ALTER TABLE ONLY log
    ADD CONSTRAINT "validPool" FOREIGN KEY (idpool) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE ONLY log
    ADD CONSTRAINT "validFile" FOREIGN KEY (idfile) REFERENCES files(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE ONLY log
    ADD CONSTRAINT "validEndPath" FOREIGN KEY (endpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE ONLY log
    ADD CONSTRAINT "validStartPath" FOREIGN KEY (startpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;



--
-- Name: keywords; Type: TABLE; Schema: inex_2006; Owner: inex; Tablespace: 
--

CREATE TABLE keywords (
    idpool integer NOT NULL,
    colour character(6) NOT NULL,
    keywords text NOT NULL,
    "mode" character varying(10) NOT NULL
);



--
-- Name: pools; Type: TABLE; Schema: inex_2006; Owner: inex; Tablespace: 
--

CREATE TABLE pools (
    id integer DEFAULT nextval('public.pools_id_seq'::text) NOT NULL,
    idtopic integer NOT NULL,
    name text NOT NULL,
    state character varying(10) NOT NULL,
    enabled boolean NOT NULL,
    login character varying(16) NOT NULL,
    main boolean,
    CONSTRAINT true_main CHECK (main)
);



--
-- Name: COLUMN pools.state; Type: COMMENT; Schema: inex_2006; Owner: inex
--

COMMENT ON COLUMN pools.state IS 'Distinguish official topics, etc.';


--
-- Name: pools_id_seq; Type: SEQUENCE; Schema: inex_2006; Owner: inex
--

CREATE SEQUENCE pools_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;



--
-- Name: progress; Type: VIEW; Schema: inex_2006; Owner: inex
--

CREATE VIEW progress AS
    SELECT pools.id AS pool, pools.login, pools.idtopic, (SELECT count(*) AS count FROM filestatus WHERE ((filestatus.idpool = pools.id) AND (filestatus.status = 2))) AS done, (SELECT count(*) AS count FROM filestatus WHERE ((filestatus.idpool = pools.id) AND (filestatus.status < 2))) AS todo FROM pools WHERE ((pools.state)::text = 'official'::text);



--
-- Name: progressbytopic; Type: VIEW; Schema: inex_2006; Owner: inex
--

CREATE VIEW progressbytopic AS
    SELECT progress.idtopic, sum(CASE WHEN (progress.todo = 0) THEN 1 ELSE 0 END) AS done, sum(CASE WHEN (progress.todo > 0) THEN 1 ELSE 0 END) AS todo FROM progress GROUP BY progress.idtopic ORDER BY sum(CASE WHEN (progress.todo = 0) THEN 1 ELSE 0 END), progress.idtopic;



--
-- Name: topicelements; Type: TABLE; Schema: inex_2006; Owner: inex; Tablespace: 
--

CREATE TABLE topicelements (
    idfile integer NOT NULL,
    idtopic integer NOT NULL,
    idpath integer NOT NULL
);



--
-- Name: TABLE topicelements; Type: COMMENT; Schema: inex_2006; Owner: inex
--

COMMENT ON TABLE topicelements IS 'Keeps the elements which should be highlighted for a topic';


--
-- Name: topicelementsview; Type: VIEW; Schema: inex_2006; Owner: inex
--

CREATE VIEW topicelementsview AS
    SELECT topicelements.idfile, topicelements.idpath, topicelements.idtopic, files.filename, paths."path" FROM ((topicelements JOIN files ON ((topicelements.idfile = files.id))) JOIN paths ON ((topicelements.idpath = paths.id)));



--
-- Name: topics; Type: TABLE; Schema: inex_2006; Owner: inex; Tablespace: 
--

CREATE TABLE topics (
    id serial NOT NULL,
    definition text NOT NULL,
    "type" character varying(10)
);



--
-- Name: COLUMN topics."type"; Type: COMMENT; Schema: inex_2006; Owner: inex
--

COMMENT ON COLUMN topics."type" IS 'Type of the query: CO, CO+S, CAS';


--
-- Name: pkAssessmentVersion; Type: CONSTRAINT; Schema: inex_2006; Owner: inex; Tablespace: 
--

ALTER TABLE ONLY filestatus
    ADD CONSTRAINT "pkAssessmentVersion" PRIMARY KEY (idpool, idfile);



--
-- Name: pkKeywords; Type: CONSTRAINT; Schema: inex_2006; Owner: inex; Tablespace: 
--

ALTER TABLE ONLY keywords
    ADD CONSTRAINT "pkKeywords" PRIMARY KEY (idpool, colour, "mode");



--
-- Name: pkPool; Type: CONSTRAINT; Schema: inex_2006; Owner: inex; Tablespace: 
--

ALTER TABLE ONLY pools
    ADD CONSTRAINT "pkPool" PRIMARY KEY (id);



--
-- Name: pkTopic; Type: CONSTRAINT; Schema: inex_2006; Owner: inex; Tablespace: 
--

ALTER TABLE ONLY topics
    ADD CONSTRAINT "pkTopic" PRIMARY KEY (id);



--
-- Name: pk_assessments; Type: CONSTRAINT; Schema: inex_2006; Owner: inex; Tablespace: 
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT pk_assessments PRIMARY KEY (idpool, idfile, startpath, endpath);



--
-- Name: pk_topicelements; Type: CONSTRAINT; Schema: inex_2006; Owner: inex; Tablespace: 
--

ALTER TABLE ONLY topicelements
    ADD CONSTRAINT pk_topicelements PRIMARY KEY (idtopic, idfile, idpath);



--
-- Name: unique_file; Type: CONSTRAINT; Schema: inex_2006; Owner: inex; Tablespace: 
--

ALTER TABLE ONLY files
    ADD CONSTRAINT unique_file UNIQUE (collection, filename);



--
-- Name: unique_file_id; Type: CONSTRAINT; Schema: inex_2006; Owner: inex; Tablespace: 
--

ALTER TABLE ONLY files
    ADD CONSTRAINT unique_file_id PRIMARY KEY (id);



--
-- Name: unique_path; Type: CONSTRAINT; Schema: inex_2006; Owner: inex; Tablespace: 
--

ALTER TABLE ONLY paths
    ADD CONSTRAINT unique_path UNIQUE (path);



--
-- Name: unique_path_id; Type: CONSTRAINT; Schema: inex_2006; Owner: inex; Tablespace: 
--

ALTER TABLE ONLY paths
    ADD CONSTRAINT unique_path_id PRIMARY KEY (id);



--
-- Name: unique_true_main; Type: CONSTRAINT; Schema: inex_2006; Owner: inex; Tablespace: 
--

ALTER TABLE ONLY pools
    ADD CONSTRAINT unique_true_main UNIQUE (idtopic, main);



--
-- Name: file_pre_post; Type: INDEX; Schema: inex_2006; Owner: inex; Tablespace: 
--

CREATE UNIQUE INDEX file_pre_post ON files USING btree (pre, post);



--
-- Name: lower_filename; Type: INDEX; Schema: inex_2006; Owner: inex; Tablespace: 
--

CREATE INDEX lower_filename ON files USING btree (lower((collection)::text), lower((filename)::text));



--
-- Name: validEndPath; Type: FK CONSTRAINT; Schema: inex_2006; Owner: inex
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT "validEndPath" FOREIGN KEY (endpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;



--
-- Name: validFile; Type: FK CONSTRAINT; Schema: inex_2006; Owner: inex
--

ALTER TABLE ONLY filestatus
    ADD CONSTRAINT "validFile" FOREIGN KEY (idfile) REFERENCES files(id) ON UPDATE CASCADE ON DELETE CASCADE;




--
-- Name: validFile; Type: FK CONSTRAINT; Schema: inex_2006; Owner: inex
--

ALTER TABLE ONLY topicelements
    ADD CONSTRAINT "validFile" FOREIGN KEY (idfile) REFERENCES files(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: validParent; Type: FK CONSTRAINT; Schema: inex_2006; Owner: inex
--

ALTER TABLE ONLY files
    ADD CONSTRAINT "validParent" FOREIGN KEY (parent) REFERENCES files(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: validPath; Type: FK CONSTRAINT; Schema: inex_2006; Owner: inex
--

ALTER TABLE ONLY topicelements
    ADD CONSTRAINT "validPath" FOREIGN KEY (idpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: validPool; Type: FK CONSTRAINT; Schema: inex_2006; Owner: inex
--

ALTER TABLE ONLY keywords
    ADD CONSTRAINT "validPool" FOREIGN KEY (idpool) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: validPool; Type: FK CONSTRAINT; Schema: inex_2006; Owner: inex
--

ALTER TABLE ONLY filestatus
    ADD CONSTRAINT "validPool" FOREIGN KEY (idpool) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE CASCADE;

--
-- Name: validPoolFile; Type: FK CONSTRAINT; Schema: inex_2006; Owner: inex
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT "validPoolFile" FOREIGN KEY (idpool, idfile) REFERENCES filestatus(idpool, idfile) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: validStartPath; Type: FK CONSTRAINT; Schema: inex_2006; Owner: inex
--

ALTER TABLE ONLY assessments
    ADD CONSTRAINT "validStartPath" FOREIGN KEY (startpath) REFERENCES paths(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: validTopic; Type: FK CONSTRAINT; Schema: inex_2006; Owner: inex
--

ALTER TABLE ONLY pools
    ADD CONSTRAINT "validTopic" FOREIGN KEY (idtopic) REFERENCES topics(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: validTopic; Type: FK CONSTRAINT; Schema: inex_2006; Owner: inex
--

ALTER TABLE ONLY topicelements
    ADD CONSTRAINT "validTopic" FOREIGN KEY (idtopic) REFERENCES topics(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

