-- cjw_newsletter 4.2.20 -> 4.3.0, PostgreSQL
--
-- Segments: the new table cjwnl_segment, cjwnl_edition_send.segment_id and cjwnl_edition_send_item.person_tracking.
-- Run it once: it has no IF NOT EXISTS and fails on a database that has it already.

ALTER TABLE cjwnl_edition_send ADD COLUMN segment_id integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_edition_send_item ADD COLUMN person_tracking smallint DEFAULT '0' NOT NULL;

CREATE SEQUENCE cjwnl_segment_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE cjwnl_segment (
  id integer DEFAULT nextval('cjwnl_segment_id_seq'::text) NOT NULL,
  list_contentobject_id integer DEFAULT 0 NOT NULL,
  name character varying(255) DEFAULT ''::character varying NOT NULL,
  match_mode character varying(3) DEFAULT 'all'::character varying NOT NULL,
  conditions text DEFAULT NULL,
  creator_contentobject_id integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  modified integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_segment_list ON cjwnl_segment USING btree ( list_contentobject_id );
ALTER TABLE ONLY cjwnl_segment ADD CONSTRAINT cjwnl_segment_pkey PRIMARY KEY ( id );

-- Welcome series: the tables cjwnl_series, cjwnl_series_step, cjwnl_series_member and cjwnl_edition_send.series_step_id.
ALTER TABLE cjwnl_edition_send ADD COLUMN series_step_id integer DEFAULT 0 NOT NULL;

CREATE SEQUENCE cjwnl_series_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE cjwnl_series (
  id integer DEFAULT nextval('cjwnl_series_id_seq'::text) NOT NULL,
  list_contentobject_id integer DEFAULT 0 NOT NULL,
  segment_id integer DEFAULT 0 NOT NULL,
  status integer DEFAULT 0 NOT NULL,
  creator_contentobject_id integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  modified integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_series_list ON cjwnl_series USING btree ( list_contentobject_id );
ALTER TABLE ONLY cjwnl_series ADD CONSTRAINT cjwnl_series_pkey PRIMARY KEY ( id );

CREATE SEQUENCE cjwnl_series_step_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE cjwnl_series_step (
  id integer DEFAULT nextval('cjwnl_series_step_id_seq'::text) NOT NULL,
  series_id integer DEFAULT 0 NOT NULL,
  position integer DEFAULT 0 NOT NULL,
  edition_contentobject_id integer DEFAULT 0 NOT NULL,
  delay_days integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  modified integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_series_step_pos ON cjwnl_series_step USING btree ( series_id, position );
ALTER TABLE ONLY cjwnl_series_step ADD CONSTRAINT cjwnl_series_step_pkey PRIMARY KEY ( id );

CREATE SEQUENCE cjwnl_series_member_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE cjwnl_series_member (
  id integer DEFAULT nextval('cjwnl_series_member_id_seq'::text) NOT NULL,
  series_id integer DEFAULT 0 NOT NULL,
  subscription_id integer DEFAULT 0 NOT NULL,
  newsletter_user_id integer DEFAULT 0 NOT NULL,
  entered integer DEFAULT 0 NOT NULL,
  last_position integer DEFAULT 0 NOT NULL,
  last_sent integer DEFAULT 0 NOT NULL,
  pending_send_id integer DEFAULT 0 NOT NULL,
  pending_position integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_series_member_sub ON cjwnl_series_member USING btree ( series_id, subscription_id );
CREATE INDEX cjwnl_series_member_pending ON cjwnl_series_member USING btree ( pending_send_id );
ALTER TABLE ONLY cjwnl_series_member ADD CONSTRAINT cjwnl_series_member_pkey PRIMARY KEY ( id );
