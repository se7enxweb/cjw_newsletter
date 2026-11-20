-- cjw_newsletter 4.2.20 -> 4.3.0, MySQL
--
-- Segments: the new table cjwnl_segment, cjwnl_edition_send.segment_id and cjwnl_edition_send_item.person_tracking.
-- Run it once: it has no IF NOT EXISTS and fails on a database that has it already.

ALTER TABLE cjwnl_edition_send ADD COLUMN segment_id int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN person_tracking tinyint(1) NOT NULL DEFAULT '0';

CREATE TABLE cjwnl_segment (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_contentobject_id int(11) NOT NULL DEFAULT '0',
  name varchar(255) NOT NULL DEFAULT '',
  match_mode varchar(3) NOT NULL DEFAULT 'all',
  conditions text DEFAULT NULL,
  creator_contentobject_id int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  modified int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_segment_list ( list_contentobject_id )
) ENGINE=InnoDB;

-- Welcome series: the tables cjwnl_series, cjwnl_series_step, cjwnl_series_member and cjwnl_edition_send.series_step_id.
ALTER TABLE cjwnl_edition_send ADD COLUMN series_step_id int(11) NOT NULL DEFAULT '0';

CREATE TABLE cjwnl_series (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_contentobject_id int(11) NOT NULL DEFAULT '0',
  segment_id int(11) NOT NULL DEFAULT '0',
  status int(11) NOT NULL DEFAULT '0',
  creator_contentobject_id int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  modified int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_series_list ( list_contentobject_id )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_series_step (
  id int(11) NOT NULL AUTO_INCREMENT,
  series_id int(11) NOT NULL DEFAULT '0',
  position int(11) NOT NULL DEFAULT '0',
  edition_contentobject_id int(11) NOT NULL DEFAULT '0',
  delay_days int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  modified int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_series_step_pos ( series_id, position )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_series_member (
  id int(11) NOT NULL AUTO_INCREMENT,
  series_id int(11) NOT NULL DEFAULT '0',
  subscription_id int(11) NOT NULL DEFAULT '0',
  newsletter_user_id int(11) NOT NULL DEFAULT '0',
  entered int(11) NOT NULL DEFAULT '0',
  last_position int(11) NOT NULL DEFAULT '0',
  last_sent int(11) NOT NULL DEFAULT '0',
  pending_send_id int(11) NOT NULL DEFAULT '0',
  pending_position int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_series_member_sub ( series_id, subscription_id ),
  KEY cjwnl_series_member_pending ( pending_send_id )
) ENGINE=InnoDB;
