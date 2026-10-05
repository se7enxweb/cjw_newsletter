-- cjw_newsletter 4.1.20 -> 4.2.0, SQLite
--
-- The schema of every feature area of 4.2.0 in one change: new columns of cjwnl_edition_send, cjwnl_edition_send_item,
-- cjwnl_list, cjwnl_user and cjwnl_import, and the new tables (see doc/schema-4.2.md of the extension). Run it once:
-- it has no IF NOT EXISTS and fails on a database that has it already.

ALTER TABLE cjwnl_edition_send ADD COLUMN skin_name varchar(255) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_edition_send ADD COLUMN channel varchar(20) NOT NULL DEFAULT 'email';
ALTER TABLE cjwnl_edition_send ADD COLUMN schedule_id INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send ADD COLUMN tracking_mode tinyint(4) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send ADD COLUMN ab_test_id INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send ADD COLUMN test_group_id INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send ADD COLUMN throttle_transport varchar(50) NOT NULL DEFAULT '';

ALTER TABLE cjwnl_edition_send_item ADD COLUMN retry_count tinyint(4) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN next_retry INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN batch_id INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN language varchar(20) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN ab_variant_id INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN first_opened INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN open_count INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_edition_send_item ADD COLUMN click_count INTEGER(11) NOT NULL DEFAULT '0';

ALTER TABLE cjwnl_list ADD COLUMN skin_name_array_string varchar(255) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_list ADD COLUMN main_language varchar(20) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_list ADD COLUMN language_array_string varchar(255) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_list ADD COLUMN interest_source varchar(20) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_list ADD COLUMN approval_required tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_list ADD COLUMN article_pool_id INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_list ADD COLUMN tracking_mode tinyint(4) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_list ADD COLUMN sms_enabled tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_list ADD COLUMN sms_sender varchar(50) NOT NULL DEFAULT '';

ALTER TABLE cjwnl_user ADD COLUMN language varchar(20) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_user ADD COLUMN soft_bounce_count tinyint(4) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_user ADD COLUMN last_bounce INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_user ADD COLUMN phone_number varchar(50) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_user ADD COLUMN phone_status tinyint(4) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_user ADD COLUMN phone_confirmed INTEGER(11) NOT NULL DEFAULT '0';

ALTER TABLE cjwnl_import ADD COLUMN mapping_id INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_import ADD COLUMN is_dry_run tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_import ADD COLUMN consent_source varchar(100) NOT NULL DEFAULT '';
ALTER TABLE cjwnl_import ADD COLUMN status tinyint(4) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_import ADD COLUMN skipped_count INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE cjwnl_import ADD COLUMN error_count INTEGER(11) NOT NULL DEFAULT '0';

CREATE TABLE cjwnl_throttle_state (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  transport varchar(50) NOT NULL DEFAULT '',
  window_type varchar(10) NOT NULL DEFAULT 'minute',
  window_start INTEGER(11) NOT NULL DEFAULT '0',
  sent_count INTEGER(11) NOT NULL DEFAULT '0',
  paused_until INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  UNIQUE INDEX cjwnl_throttle_state_window ON cjwnl_throttle_state  ( transport, window_type );

CREATE TABLE cjwnl_send_batch (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  edition_send_id INTEGER(11) NOT NULL DEFAULT '0',
  channel varchar(20) NOT NULL DEFAULT 'email',
  batch_number INTEGER(11) NOT NULL DEFAULT '0',
  item_count INTEGER(11) NOT NULL DEFAULT '0',
  sent_count INTEGER(11) NOT NULL DEFAULT '0',
  failed_count INTEGER(11) NOT NULL DEFAULT '0',
  last_item_id INTEGER(11) NOT NULL DEFAULT '0',
  status tinyint(4) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  started INTEGER(11) NOT NULL DEFAULT '0',
  finished INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_send_batch_send ON cjwnl_send_batch  ( edition_send_id, status );

CREATE TABLE cjwnl_mailin_address (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  list_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  email varchar(255) NOT NULL DEFAULT '',
  plus_tag varchar(100) NOT NULL DEFAULT '',
  action varchar(20) NOT NULL DEFAULT 'both',
  mailbox_id INTEGER(11) NOT NULL DEFAULT '0',
  is_active tinyint(1) NOT NULL DEFAULT '1',
  created INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  UNIQUE INDEX cjwnl_mailin_address_email ON cjwnl_mailin_address  ( email, plus_tag );
CREATE  INDEX cjwnl_mailin_address_list ON cjwnl_mailin_address  ( list_contentobject_id );

CREATE TABLE cjwnl_mailin_message (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  mailin_address_id INTEGER(11) NOT NULL DEFAULT '0',
  message_identifier varchar(255) NOT NULL DEFAULT '',
  email_from varchar(255) NOT NULL DEFAULT '',
  action varchar(20) NOT NULL DEFAULT '',
  status tinyint(4) NOT NULL DEFAULT '0',
  pending_token varchar(64) NOT NULL DEFAULT '',
  newsletter_user_id INTEGER(11) NOT NULL DEFAULT '0',
  note text DEFAULT NULL,
  created INTEGER(11) NOT NULL DEFAULT '0',
  processed INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_mailin_message_ident ON cjwnl_mailin_message  ( message_identifier );
CREATE  INDEX cjwnl_mailin_message_status ON cjwnl_mailin_message  ( status, created );

CREATE TABLE cjwnl_test_group (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  list_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  name varchar(255) NOT NULL DEFAULT '',
  email_list text DEFAULT NULL,
  creator_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_test_group_list ON cjwnl_test_group  ( list_contentobject_id );

CREATE TABLE cjwnl_schedule (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  list_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  mode varchar(20) NOT NULL DEFAULT 'latest',
  template_edition_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  recurrence_type varchar(1) NOT NULL DEFAULT 'w',
  recurrence_value varchar(255) NOT NULL DEFAULT '',
  send_time INTEGER(11) NOT NULL DEFAULT '0',
  timezone varchar(64) NOT NULL DEFAULT '',
  auto_fill tinyint(1) NOT NULL DEFAULT '0',
  article_pool_id INTEGER(11) NOT NULL DEFAULT '0',
  skip_if_empty tinyint(1) NOT NULL DEFAULT '1',
  condition_handler varchar(255) NOT NULL DEFAULT '',
  next_run INTEGER(11) NOT NULL DEFAULT '0',
  last_run INTEGER(11) NOT NULL DEFAULT '0',
  last_edition_send_id INTEGER(11) NOT NULL DEFAULT '0',
  last_result varchar(50) NOT NULL DEFAULT '',
  status tinyint(4) NOT NULL DEFAULT '0',
  creator_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_schedule_due ON cjwnl_schedule  ( status, next_run );
CREATE  INDEX cjwnl_schedule_list ON cjwnl_schedule  ( list_contentobject_id );

CREATE TABLE cjwnl_schedule_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  schedule_id INTEGER(11) NOT NULL DEFAULT '0',
  run_at INTEGER(11) NOT NULL DEFAULT '0',
  result varchar(20) NOT NULL DEFAULT '',
  edition_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  edition_send_id INTEGER(11) NOT NULL DEFAULT '0',
  article_count INTEGER(11) NOT NULL DEFAULT '0',
  message text DEFAULT NULL
);
CREATE  INDEX cjwnl_schedule_log_schedule ON cjwnl_schedule_log  ( schedule_id, run_at );

CREATE TABLE cjwnl_article_pool (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  list_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  name varchar(255) NOT NULL DEFAULT '',
  is_default tinyint(1) NOT NULL DEFAULT '0',
  parent_node_id_array_string varchar(255) NOT NULL DEFAULT '',
  class_identifier_array_string varchar(255) NOT NULL DEFAULT '',
  section_id_array_string varchar(255) NOT NULL DEFAULT '',
  tag_id_array_string varchar(255) NOT NULL DEFAULT '',
  state_id_array_string varchar(255) NOT NULL DEFAULT '',
  max_age_days INTEGER(11) NOT NULL DEFAULT '0',
  max_items INTEGER(11) NOT NULL DEFAULT '10',
  sort_by varchar(50) NOT NULL DEFAULT 'published',
  filter_data text DEFAULT NULL,
  creator_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_article_pool_list ON cjwnl_article_pool  ( list_contentobject_id );

CREATE TABLE cjwnl_edition_article (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  edition_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  article_pool_id INTEGER(11) NOT NULL DEFAULT '0',
  position INTEGER(11) NOT NULL DEFAULT '0',
  added_by tinyint(4) NOT NULL DEFAULT '0',
  creator_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  UNIQUE INDEX cjwnl_edition_article_edition ON cjwnl_edition_article  ( edition_contentobject_id, contentobject_id );
CREATE  INDEX cjwnl_edition_article_object ON cjwnl_edition_article  ( contentobject_id );

CREATE TABLE cjwnl_approval (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  edition_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  edition_contentobject_version INTEGER(11) NOT NULL DEFAULT '0',
  list_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  collaboration_item_id INTEGER(11) NOT NULL DEFAULT '0',
  status tinyint(4) NOT NULL DEFAULT '0',
  requested_by INTEGER(11) NOT NULL DEFAULT '0',
  requested INTEGER(11) NOT NULL DEFAULT '0',
  decided_by INTEGER(11) NOT NULL DEFAULT '0',
  decided INTEGER(11) NOT NULL DEFAULT '0',
  comment text DEFAULT NULL
);
CREATE  INDEX cjwnl_approval_collaboration ON cjwnl_approval  ( collaboration_item_id );
CREATE  INDEX cjwnl_approval_edition ON cjwnl_approval  ( edition_contentobject_id, edition_contentobject_version );

CREATE TABLE cjwnl_interest (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  list_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  identifier varchar(100) NOT NULL DEFAULT '',
  name varchar(255) NOT NULL DEFAULT '',
  source varchar(20) NOT NULL DEFAULT 'topic',
  eztags_id INTEGER(11) NOT NULL DEFAULT '0',
  priority INTEGER(11) NOT NULL DEFAULT '0',
  is_active tinyint(1) NOT NULL DEFAULT '1',
  created INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  UNIQUE INDEX cjwnl_interest_identifier ON cjwnl_interest  ( list_contentobject_id, identifier );

CREATE TABLE cjwnl_user_interest (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  newsletter_user_id INTEGER(11) NOT NULL DEFAULT '0',
  interest_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_user_interest_interest ON cjwnl_user_interest  ( interest_id );
CREATE  UNIQUE INDEX cjwnl_user_interest_pair ON cjwnl_user_interest  ( newsletter_user_id, interest_id );

CREATE TABLE cjwnl_edition_send_output (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  edition_send_id INTEGER(11) NOT NULL DEFAULT '0',
  language varchar(20) NOT NULL DEFAULT '',
  output_xml longtext DEFAULT NULL,
  created INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  UNIQUE INDEX cjwnl_edition_send_output_lang ON cjwnl_edition_send_output  ( edition_send_id, language );

CREATE TABLE cjwnl_link (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  edition_send_id INTEGER(11) NOT NULL DEFAULT '0',
  url_hash varchar(64) NOT NULL DEFAULT '',
  url text DEFAULT NULL,
  contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  position INTEGER(11) NOT NULL DEFAULT '0',
  click_count INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_link_object ON cjwnl_link  ( contentobject_id );
CREATE  UNIQUE INDEX cjwnl_link_send_url ON cjwnl_link  ( edition_send_id, url_hash );

CREATE TABLE cjwnl_link_click (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  link_id INTEGER(11) NOT NULL DEFAULT '0',
  edition_send_item_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_link_click_created ON cjwnl_link_click  ( created );
CREATE  INDEX cjwnl_link_click_item ON cjwnl_link_click  ( edition_send_item_id );
CREATE  INDEX cjwnl_link_click_link ON cjwnl_link_click  ( link_id );

CREATE TABLE cjwnl_open (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  edition_send_id INTEGER(11) NOT NULL DEFAULT '0',
  edition_send_item_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_open_created ON cjwnl_open  ( created );
CREATE  INDEX cjwnl_open_item ON cjwnl_open  ( edition_send_item_id );
CREATE  INDEX cjwnl_open_send ON cjwnl_open  ( edition_send_id );

CREATE TABLE cjwnl_stat_total (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  edition_send_id INTEGER(11) NOT NULL DEFAULT '0',
  link_id INTEGER(11) NOT NULL DEFAULT '0',
  stat_type varchar(20) NOT NULL DEFAULT '',
  stat_day INTEGER(11) NOT NULL DEFAULT '0',
  total INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_stat_total_day ON cjwnl_stat_total  ( stat_type, stat_day );
CREATE  UNIQUE INDEX cjwnl_stat_total_key ON cjwnl_stat_total  ( edition_send_id, link_id, stat_type, stat_day );

CREATE TABLE cjwnl_ab_test (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  edition_send_id INTEGER(11) NOT NULL DEFAULT '0',
  status tinyint(4) NOT NULL DEFAULT '0',
  sample_percent INTEGER(11) NOT NULL DEFAULT '10',
  variant_count INTEGER(11) NOT NULL DEFAULT '2',
  criterion varchar(20) NOT NULL DEFAULT 'open',
  wait_seconds INTEGER(11) NOT NULL DEFAULT '14400',
  winner_variant_id INTEGER(11) NOT NULL DEFAULT '0',
  samples_sent INTEGER(11) NOT NULL DEFAULT '0',
  decided INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  UNIQUE INDEX cjwnl_ab_test_send ON cjwnl_ab_test  ( edition_send_id );
CREATE  INDEX cjwnl_ab_test_status ON cjwnl_ab_test  ( status );

CREATE TABLE cjwnl_ab_variant (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ab_test_id INTEGER(11) NOT NULL DEFAULT '0',
  variant_key varchar(10) NOT NULL DEFAULT '',
  subject varchar(255) NOT NULL DEFAULT '',
  item_count INTEGER(11) NOT NULL DEFAULT '0',
  open_count INTEGER(11) NOT NULL DEFAULT '0',
  click_count INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  UNIQUE INDEX cjwnl_ab_variant_test ON cjwnl_ab_variant  ( ab_test_id, variant_key );

CREATE TABLE cjwnl_sms_code (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  newsletter_user_id INTEGER(11) NOT NULL DEFAULT '0',
  phone_number varchar(50) NOT NULL DEFAULT '',
  code_hash varchar(64) NOT NULL DEFAULT '',
  purpose varchar(20) NOT NULL DEFAULT 'confirm',
  attempts tinyint(4) NOT NULL DEFAULT '0',
  expires INTEGER(11) NOT NULL DEFAULT '0',
  used INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_sms_code_user ON cjwnl_sms_code  ( newsletter_user_id, purpose );

CREATE TABLE cjwnl_sms_message (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  edition_send_id INTEGER(11) NOT NULL DEFAULT '0',
  newsletter_user_id INTEGER(11) NOT NULL DEFAULT '0',
  phone_number varchar(50) NOT NULL DEFAULT '',
  body text DEFAULT NULL,
  status tinyint(4) NOT NULL DEFAULT '0',
  transport varchar(50) NOT NULL DEFAULT '',
  provider_message_id varchar(255) NOT NULL DEFAULT '',
  error text DEFAULT NULL,
  batch_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  processed INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_sms_message_send ON cjwnl_sms_message  ( edition_send_id, status );
CREATE  INDEX cjwnl_sms_message_user ON cjwnl_sms_message  ( newsletter_user_id );

CREATE TABLE cjwnl_sms_inbound (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  phone_number varchar(50) NOT NULL DEFAULT '',
  keyword varchar(50) NOT NULL DEFAULT '',
  body text DEFAULT NULL,
  newsletter_user_id INTEGER(11) NOT NULL DEFAULT '0',
  action varchar(20) NOT NULL DEFAULT '',
  provider_message_id varchar(255) NOT NULL DEFAULT '',
  created INTEGER(11) NOT NULL DEFAULT '0',
  processed INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_sms_inbound_phone ON cjwnl_sms_inbound  ( phone_number );

CREATE TABLE cjwnl_import_mapping (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name varchar(255) NOT NULL DEFAULT '',
  list_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  mapping text DEFAULT NULL,
  delimiter varchar(5) NOT NULL DEFAULT ';',
  has_header tinyint(1) NOT NULL DEFAULT '1',
  encoding varchar(20) NOT NULL DEFAULT 'UTF-8',
  consent_source varchar(100) NOT NULL DEFAULT '',
  creator_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_import_mapping_list ON cjwnl_import_mapping  ( list_contentobject_id );

CREATE TABLE cjwnl_migration_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  run_id varchar(40) NOT NULL DEFAULT '',
  source_table varchar(100) NOT NULL DEFAULT '',
  source_id varchar(100) NOT NULL DEFAULT '',
  target_table varchar(100) NOT NULL DEFAULT '',
  target_id INTEGER(11) NOT NULL DEFAULT '0',
  action varchar(20) NOT NULL DEFAULT '',
  is_dry_run tinyint(1) NOT NULL DEFAULT '0',
  message text DEFAULT NULL,
  created INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_migration_log_run ON cjwnl_migration_log  ( run_id );
CREATE  INDEX cjwnl_migration_log_source ON cjwnl_migration_log  ( source_table, source_id );
