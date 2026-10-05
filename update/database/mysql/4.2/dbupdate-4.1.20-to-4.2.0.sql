-- cjw_newsletter 4.1.20 -> 4.2.0, MySQL
--
-- The schema of every feature area of 4.2.0 in one change: new columns of cjwnl_edition_send, cjwnl_edition_send_item,
-- cjwnl_list, cjwnl_user and cjwnl_import, and the new tables (see doc/schema-4.2.md of the extension). Run it once:
-- it has no IF NOT EXISTS and fails on a database that has it already.

ALTER TABLE cjwnl_edition_send ADD COLUMN skin_name varchar(255) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_edition_send ADD COLUMN channel varchar(20) NOT NULL DEFAULT 'email';
ALTER TABLE cjwnl_edition_send ADD COLUMN schedule_id int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send ADD COLUMN tracking_mode tinyint(4) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send ADD COLUMN ab_test_id int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send ADD COLUMN test_group_id int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send ADD COLUMN throttle_transport varchar(50) NOT NULL DEFAULT '';

ALTER TABLE cjwnl_edition_send_item ADD COLUMN retry_count tinyint(4) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN next_retry int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN batch_id int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN language varchar(20) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN ab_variant_id int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN first_opened int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN open_count int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN click_count int(11) NOT NULL DEFAULT '0';

ALTER TABLE cjwnl_list ADD COLUMN skin_name_array_string varchar(255) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_list ADD COLUMN main_language varchar(20) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_list ADD COLUMN language_array_string varchar(255) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_list ADD COLUMN interest_source varchar(20) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_list ADD COLUMN approval_required tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_list ADD COLUMN article_pool_id int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_list ADD COLUMN tracking_mode tinyint(4) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_list ADD COLUMN sms_enabled tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_list ADD COLUMN sms_sender varchar(50) NOT NULL DEFAULT '';

ALTER TABLE cjwnl_user ADD COLUMN language varchar(20) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_user ADD COLUMN soft_bounce_count tinyint(4) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_user ADD COLUMN last_bounce int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_user ADD COLUMN phone_number varchar(50) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_user ADD COLUMN phone_status tinyint(4) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_user ADD COLUMN phone_confirmed int(11) NOT NULL DEFAULT '0';

ALTER TABLE cjwnl_import ADD COLUMN mapping_id int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_import ADD COLUMN is_dry_run tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_import ADD COLUMN consent_source varchar(100) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_import ADD COLUMN status tinyint(4) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_import ADD COLUMN skipped_count int(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_import ADD COLUMN error_count int(11) NOT NULL DEFAULT '0';

CREATE TABLE cjwnl_throttle_state (
  id int(11) NOT NULL AUTO_INCREMENT,
  transport varchar(50) NOT NULL DEFAULT '',
  window_type varchar(10) NOT NULL DEFAULT 'minute',
  window_start int(11) NOT NULL DEFAULT '0',
  sent_count int(11) NOT NULL DEFAULT '0',
  paused_until int(11) NOT NULL DEFAULT '0',
  modified int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_throttle_state_window ( transport, window_type )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_send_batch (
  id int(11) NOT NULL AUTO_INCREMENT,
  edition_send_id int(11) NOT NULL DEFAULT '0',
  channel varchar(20) NOT NULL DEFAULT 'email',
  batch_number int(11) NOT NULL DEFAULT '0',
  item_count int(11) NOT NULL DEFAULT '0',
  sent_count int(11) NOT NULL DEFAULT '0',
  failed_count int(11) NOT NULL DEFAULT '0',
  last_item_id int(11) NOT NULL DEFAULT '0',
  status tinyint(4) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  started int(11) NOT NULL DEFAULT '0',
  finished int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_send_batch_send ( edition_send_id, status )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_mailin_address (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_contentobject_id int(11) NOT NULL DEFAULT '0',
  email varchar(255) NOT NULL DEFAULT '',
  plus_tag varchar(100) NOT NULL DEFAULT '',
  action varchar(20) NOT NULL DEFAULT 'both',
  mailbox_id int(11) NOT NULL DEFAULT '0',
  is_active tinyint(1) NOT NULL DEFAULT '1',
  created int(11) NOT NULL DEFAULT '0',
  modified int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_mailin_address_email ( email, plus_tag ),
  KEY cjwnl_mailin_address_list ( list_contentobject_id )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_mailin_message (
  id int(11) NOT NULL AUTO_INCREMENT,
  mailin_address_id int(11) NOT NULL DEFAULT '0',
  message_identifier varchar(255) NOT NULL DEFAULT '',
  email_from varchar(255) NOT NULL DEFAULT '',
  action varchar(20) NOT NULL DEFAULT '',
  status tinyint(4) NOT NULL DEFAULT '0',
  pending_token varchar(64) NOT NULL DEFAULT '',
  newsletter_user_id int(11) NOT NULL DEFAULT '0',
  note text DEFAULT NULL,
  created int(11) NOT NULL DEFAULT '0',
  processed int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_mailin_message_ident ( message_identifier ),
  KEY cjwnl_mailin_message_status ( status, created )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_test_group (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_contentobject_id int(11) NOT NULL DEFAULT '0',
  name varchar(255) NOT NULL DEFAULT '',
  email_list text DEFAULT NULL,
  creator_contentobject_id int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  modified int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_test_group_list ( list_contentobject_id )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_schedule (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_contentobject_id int(11) NOT NULL DEFAULT '0',
  mode varchar(20) NOT NULL DEFAULT 'latest',
  template_edition_contentobject_id int(11) NOT NULL DEFAULT '0',
  recurrence_type varchar(1) NOT NULL DEFAULT 'w',
  recurrence_value varchar(255) NOT NULL DEFAULT '',
  send_time int(11) NOT NULL DEFAULT '0',
  timezone varchar(64) NOT NULL DEFAULT '',
  auto_fill tinyint(1) NOT NULL DEFAULT '0',
  article_pool_id int(11) NOT NULL DEFAULT '0',
  skip_if_empty tinyint(1) NOT NULL DEFAULT '1',
  condition_handler varchar(255) NOT NULL DEFAULT '',
  next_run int(11) NOT NULL DEFAULT '0',
  last_run int(11) NOT NULL DEFAULT '0',
  last_edition_send_id int(11) NOT NULL DEFAULT '0',
  last_result varchar(50) NOT NULL DEFAULT '',
  status tinyint(4) NOT NULL DEFAULT '0',
  creator_contentobject_id int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  modified int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_schedule_due ( status, next_run ),
  KEY cjwnl_schedule_list ( list_contentobject_id )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_schedule_log (
  id int(11) NOT NULL AUTO_INCREMENT,
  schedule_id int(11) NOT NULL DEFAULT '0',
  run_at int(11) NOT NULL DEFAULT '0',
  result varchar(20) NOT NULL DEFAULT '',
  edition_contentobject_id int(11) NOT NULL DEFAULT '0',
  edition_send_id int(11) NOT NULL DEFAULT '0',
  article_count int(11) NOT NULL DEFAULT '0',
  message text DEFAULT NULL,
  PRIMARY KEY ( id ),
  KEY cjwnl_schedule_log_schedule ( schedule_id, run_at )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_article_pool (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_contentobject_id int(11) NOT NULL DEFAULT '0',
  name varchar(255) NOT NULL DEFAULT '',
  is_default tinyint(1) NOT NULL DEFAULT '0',
  parent_node_id_array_string varchar(255) NOT NULL DEFAULT '',
  class_identifier_array_string varchar(255) NOT NULL DEFAULT '',
  section_id_array_string varchar(255) NOT NULL DEFAULT '',
  tag_id_array_string varchar(255) NOT NULL DEFAULT '',
  state_id_array_string varchar(255) NOT NULL DEFAULT '',
  max_age_days int(11) NOT NULL DEFAULT '0',
  max_items int(11) NOT NULL DEFAULT '10',
  sort_by varchar(50) NOT NULL DEFAULT 'published',
  filter_data text DEFAULT NULL,
  creator_contentobject_id int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  modified int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_article_pool_list ( list_contentobject_id )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_edition_article (
  id int(11) NOT NULL AUTO_INCREMENT,
  edition_contentobject_id int(11) NOT NULL DEFAULT '0',
  contentobject_id int(11) NOT NULL DEFAULT '0',
  article_pool_id int(11) NOT NULL DEFAULT '0',
  position int(11) NOT NULL DEFAULT '0',
  added_by tinyint(4) NOT NULL DEFAULT '0',
  creator_contentobject_id int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_edition_article_edition ( edition_contentobject_id, contentobject_id ),
  KEY cjwnl_edition_article_object ( contentobject_id )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_approval (
  id int(11) NOT NULL AUTO_INCREMENT,
  edition_contentobject_id int(11) NOT NULL DEFAULT '0',
  edition_contentobject_version int(11) NOT NULL DEFAULT '0',
  list_contentobject_id int(11) NOT NULL DEFAULT '0',
  collaboration_item_id int(11) NOT NULL DEFAULT '0',
  status tinyint(4) NOT NULL DEFAULT '0',
  requested_by int(11) NOT NULL DEFAULT '0',
  requested int(11) NOT NULL DEFAULT '0',
  decided_by int(11) NOT NULL DEFAULT '0',
  decided int(11) NOT NULL DEFAULT '0',
  comment text DEFAULT NULL,
  PRIMARY KEY ( id ),
  KEY cjwnl_approval_edition ( edition_contentobject_id, edition_contentobject_version ),
  KEY cjwnl_approval_collaboration ( collaboration_item_id )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_interest (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_contentobject_id int(11) NOT NULL DEFAULT '0',
  identifier varchar(100) NOT NULL DEFAULT '',
  name varchar(255) NOT NULL DEFAULT '',
  source varchar(20) NOT NULL DEFAULT 'topic',
  eztags_id int(11) NOT NULL DEFAULT '0',
  priority int(11) NOT NULL DEFAULT '0',
  is_active tinyint(1) NOT NULL DEFAULT '1',
  created int(11) NOT NULL DEFAULT '0',
  modified int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_interest_identifier ( list_contentobject_id, identifier )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_user_interest (
  id int(11) NOT NULL AUTO_INCREMENT,
  newsletter_user_id int(11) NOT NULL DEFAULT '0',
  interest_id int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_user_interest_pair ( newsletter_user_id, interest_id ),
  KEY cjwnl_user_interest_interest ( interest_id )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_edition_send_output (
  id int(11) NOT NULL AUTO_INCREMENT,
  edition_send_id int(11) NOT NULL DEFAULT '0',
  language varchar(20) NOT NULL DEFAULT '',
  output_xml longtext DEFAULT NULL,
  created int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_edition_send_output_lang ( edition_send_id, language )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_link (
  id int(11) NOT NULL AUTO_INCREMENT,
  edition_send_id int(11) NOT NULL DEFAULT '0',
  url_hash varchar(64) NOT NULL DEFAULT '',
  url text DEFAULT NULL,
  contentobject_id int(11) NOT NULL DEFAULT '0',
  position int(11) NOT NULL DEFAULT '0',
  click_count int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_link_send_url ( edition_send_id, url_hash ),
  KEY cjwnl_link_object ( contentobject_id )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_link_click (
  id int(11) NOT NULL AUTO_INCREMENT,
  link_id int(11) NOT NULL DEFAULT '0',
  edition_send_item_id int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_link_click_link ( link_id ),
  KEY cjwnl_link_click_item ( edition_send_item_id ),
  KEY cjwnl_link_click_created ( created )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_open (
  id int(11) NOT NULL AUTO_INCREMENT,
  edition_send_id int(11) NOT NULL DEFAULT '0',
  edition_send_item_id int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_open_send ( edition_send_id ),
  KEY cjwnl_open_item ( edition_send_item_id ),
  KEY cjwnl_open_created ( created )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_stat_total (
  id int(11) NOT NULL AUTO_INCREMENT,
  edition_send_id int(11) NOT NULL DEFAULT '0',
  link_id int(11) NOT NULL DEFAULT '0',
  stat_type varchar(20) NOT NULL DEFAULT '',
  stat_day int(11) NOT NULL DEFAULT '0',
  total int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_stat_total_key ( edition_send_id, link_id, stat_type, stat_day ),
  KEY cjwnl_stat_total_day ( stat_type, stat_day )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_ab_test (
  id int(11) NOT NULL AUTO_INCREMENT,
  edition_send_id int(11) NOT NULL DEFAULT '0',
  status tinyint(4) NOT NULL DEFAULT '0',
  sample_percent int(11) NOT NULL DEFAULT '10',
  variant_count int(11) NOT NULL DEFAULT '2',
  criterion varchar(20) NOT NULL DEFAULT 'open',
  wait_seconds int(11) NOT NULL DEFAULT '14400',
  winner_variant_id int(11) NOT NULL DEFAULT '0',
  samples_sent int(11) NOT NULL DEFAULT '0',
  decided int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  modified int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_ab_test_send ( edition_send_id ),
  KEY cjwnl_ab_test_status ( status )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_ab_variant (
  id int(11) NOT NULL AUTO_INCREMENT,
  ab_test_id int(11) NOT NULL DEFAULT '0',
  variant_key varchar(10) NOT NULL DEFAULT '',
  subject varchar(255) NOT NULL DEFAULT '',
  item_count int(11) NOT NULL DEFAULT '0',
  open_count int(11) NOT NULL DEFAULT '0',
  click_count int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  UNIQUE KEY cjwnl_ab_variant_test ( ab_test_id, variant_key )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_sms_code (
  id int(11) NOT NULL AUTO_INCREMENT,
  newsletter_user_id int(11) NOT NULL DEFAULT '0',
  phone_number varchar(50) NOT NULL DEFAULT '',
  code_hash varchar(64) NOT NULL DEFAULT '',
  purpose varchar(20) NOT NULL DEFAULT 'confirm',
  attempts tinyint(4) NOT NULL DEFAULT '0',
  expires int(11) NOT NULL DEFAULT '0',
  used int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_sms_code_user ( newsletter_user_id, purpose )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_sms_message (
  id int(11) NOT NULL AUTO_INCREMENT,
  edition_send_id int(11) NOT NULL DEFAULT '0',
  newsletter_user_id int(11) NOT NULL DEFAULT '0',
  phone_number varchar(50) NOT NULL DEFAULT '',
  body text DEFAULT NULL,
  status tinyint(4) NOT NULL DEFAULT '0',
  transport varchar(50) NOT NULL DEFAULT '',
  provider_message_id varchar(255) NOT NULL DEFAULT '',
  error text DEFAULT NULL,
  batch_id int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  processed int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_sms_message_send ( edition_send_id, status ),
  KEY cjwnl_sms_message_user ( newsletter_user_id )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_sms_inbound (
  id int(11) NOT NULL AUTO_INCREMENT,
  phone_number varchar(50) NOT NULL DEFAULT '',
  keyword varchar(50) NOT NULL DEFAULT '',
  body text DEFAULT NULL,
  newsletter_user_id int(11) NOT NULL DEFAULT '0',
  action varchar(20) NOT NULL DEFAULT '',
  provider_message_id varchar(255) NOT NULL DEFAULT '',
  created int(11) NOT NULL DEFAULT '0',
  processed int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_sms_inbound_phone ( phone_number )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_import_mapping (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL DEFAULT '',
  list_contentobject_id int(11) NOT NULL DEFAULT '0',
  mapping text DEFAULT NULL,
  delimiter varchar(5) NOT NULL DEFAULT ';',
  has_header tinyint(1) NOT NULL DEFAULT '1',
  encoding varchar(20) NOT NULL DEFAULT 'UTF-8',
  consent_source varchar(100) NOT NULL DEFAULT '',
  creator_contentobject_id int(11) NOT NULL DEFAULT '0',
  created int(11) NOT NULL DEFAULT '0',
  modified int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_import_mapping_list ( list_contentobject_id )
) ENGINE=InnoDB;

CREATE TABLE cjwnl_migration_log (
  id int(11) NOT NULL AUTO_INCREMENT,
  run_id varchar(40) NOT NULL DEFAULT '',
  source_table varchar(100) NOT NULL DEFAULT '',
  source_id varchar(100) NOT NULL DEFAULT '',
  target_table varchar(100) NOT NULL DEFAULT '',
  target_id int(11) NOT NULL DEFAULT '0',
  action varchar(20) NOT NULL DEFAULT '',
  is_dry_run tinyint(1) NOT NULL DEFAULT '0',
  message text DEFAULT NULL,
  created int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  KEY cjwnl_migration_log_run ( run_id ),
  KEY cjwnl_migration_log_source ( source_table, source_id )
) ENGINE=InnoDB;
