-- cjw_newsletter 4.2.20 -> 4.3.0, SQLite
--
-- Segments: the new table cjwnl_segment, cjwnl_edition_send.segment_id and cjwnl_edition_send_item.person_tracking.
-- Run it once: it has no IF NOT EXISTS and fails on a database that has it already.

ALTER TABLE cjwnl_edition_send ADD COLUMN segment_id INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN person_tracking tinyint(1) NOT NULL DEFAULT '0';

CREATE TABLE cjwnl_segment (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  list_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  name varchar(255) NOT NULL DEFAULT '',
  match_mode varchar(3) NOT NULL DEFAULT 'all',
  conditions text DEFAULT NULL,
  creator_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_segment_list ON cjwnl_segment  ( list_contentobject_id );

-- Welcome series: the tables cjwnl_series, cjwnl_series_step, cjwnl_series_member and cjwnl_edition_send.series_step_id.
ALTER TABLE cjwnl_edition_send ADD COLUMN series_step_id INTEGER(11) NOT NULL DEFAULT '0';

CREATE TABLE cjwnl_series (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  list_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  segment_id INTEGER(11) NOT NULL DEFAULT '0',
  status INTEGER(11) NOT NULL DEFAULT '0',
  creator_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  UNIQUE INDEX cjwnl_series_list ON cjwnl_series  ( list_contentobject_id );

CREATE TABLE cjwnl_series_step (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  series_id INTEGER(11) NOT NULL DEFAULT '0',
  position INTEGER(11) NOT NULL DEFAULT '0',
  edition_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  delay_days INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  UNIQUE INDEX cjwnl_series_step_pos ON cjwnl_series_step  ( series_id, position );

CREATE TABLE cjwnl_series_member (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  series_id INTEGER(11) NOT NULL DEFAULT '0',
  subscription_id INTEGER(11) NOT NULL DEFAULT '0',
  newsletter_user_id INTEGER(11) NOT NULL DEFAULT '0',
  entered INTEGER(11) NOT NULL DEFAULT '0',
  last_position INTEGER(11) NOT NULL DEFAULT '0',
  last_sent INTEGER(11) NOT NULL DEFAULT '0',
  pending_send_id INTEGER(11) NOT NULL DEFAULT '0',
  pending_position INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  UNIQUE INDEX cjwnl_series_member_sub ON cjwnl_series_member  ( series_id, subscription_id );
CREATE  INDEX cjwnl_series_member_pending ON cjwnl_series_member  ( pending_send_id );
