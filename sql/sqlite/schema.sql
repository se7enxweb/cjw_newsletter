-- cjw_newsletter, SQLite: generated from share/db_schema.dba by the kernel's SQLite schema handler.
-- Index names are "<table>__<name>" (SQLite index names are database-wide).

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

CREATE TABLE cjwnl_blacklist_item (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email_hash varchar(255) DEFAULT NULL,
  email varchar(255) DEFAULT NULL,
  newsletter_user_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) DEFAULT NULL,
  creator_contentobject_id INTEGER(11) DEFAULT NULL,
  note text
);
CREATE  INDEX cjwnl_blacklist_item__cjwnewsletter_user_id ON cjwnl_blacklist_item  ( newsletter_user_id );

CREATE TABLE cjwnl_edition (
  contentobject_attribute_id INTEGER(11) NOT NULL DEFAULT '0',
  contentobject_attribute_version INTEGER(11) NOT NULL DEFAULT '0',
  contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  contentclass_id INTEGER(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ( contentobject_attribute_id, contentobject_attribute_version )
);
CREATE  INDEX cjwnl_edition__contentobject_attribute_id ON cjwnl_edition  ( contentobject_attribute_id );
CREATE  INDEX cjwnl_edition__contentobject_attribute_version ON cjwnl_edition  ( contentobject_attribute_version );
CREATE  INDEX cjwnl_edition__contentobject_id ON cjwnl_edition  ( contentobject_id );

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

CREATE TABLE cjwnl_edition_send (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  list_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  list_contentobject_version INTEGER(11) NOT NULL DEFAULT '0',
  list_is_virtual tinyint(1) NOT NULL DEFAULT '0',
  edition_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  edition_contentobject_version INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  status tinyint(4) NOT NULL DEFAULT '0',
  siteaccess varchar(50) NOT NULL DEFAULT '',
  output_format_array_string varchar(50) NOT NULL DEFAULT '',
  creator_id INTEGER(11) NOT NULL DEFAULT '0',
  mailqueue_created INTEGER(11) NOT NULL DEFAULT '0',
  mailqueue_process_scheduled INTEGER(11) DEFAULT NULL,
  mailqueue_process_started INTEGER(11) NOT NULL DEFAULT '0',
  mailqueue_process_finished INTEGER(11) NOT NULL DEFAULT '0',
  mailqueue_process_aborted INTEGER(11) NOT NULL DEFAULT '0',
  output_xml longtext NOT NULL,
  hash varchar(255) NOT NULL DEFAULT '',
  email_sender varchar(255) NOT NULL DEFAULT '',
  email_reply_to varchar(255) NOT NULL DEFAULT '',
  email_return_path varchar(255) NOT NULL DEFAULT '',
  email_sender_name varchar(255) NOT NULL DEFAULT '',
  personalize_content tinyint(1) NOT NULL DEFAULT '0',
  skin_name varchar(255) NOT NULL DEFAULT '',
  channel varchar(20) NOT NULL DEFAULT 'email',
  schedule_id INTEGER(11) NOT NULL DEFAULT '0',
  tracking_mode tinyint(4) NOT NULL DEFAULT '0',
  ab_test_id INTEGER(11) NOT NULL DEFAULT '0',
  test_group_id INTEGER(11) NOT NULL DEFAULT '0',
  throttle_transport varchar(50) NOT NULL DEFAULT ''
);
CREATE  INDEX cjwnl_edition_send__edition_contentobject_id ON cjwnl_edition_send  ( edition_contentobject_id );
CREATE  INDEX cjwnl_edition_send__edition_contentobject_version ON cjwnl_edition_send  ( edition_contentobject_version );
CREATE  INDEX cjwnl_edition_send__list_contentobject_id ON cjwnl_edition_send  ( list_contentobject_id );

CREATE TABLE cjwnl_edition_send_item (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  edition_send_id INTEGER(11) NOT NULL DEFAULT '0',
  newsletter_user_id INTEGER(11) NOT NULL DEFAULT '0',
  output_format_id tinyint(4) NOT NULL DEFAULT '0',
  subscription_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  processed INTEGER(11) NOT NULL DEFAULT '0',
  status tinyint(4) NOT NULL DEFAULT '0',
  hash varchar(255) NOT NULL DEFAULT '',
  bounced INTEGER(11) NOT NULL DEFAULT '0',
  retry_count tinyint(4) NOT NULL DEFAULT '0',
  next_retry INTEGER(11) NOT NULL DEFAULT '0',
  batch_id INTEGER(11) NOT NULL DEFAULT '0',
  language varchar(20) NOT NULL DEFAULT '',
  ab_variant_id INTEGER(11) NOT NULL DEFAULT '0',
  first_opened INTEGER(11) NOT NULL DEFAULT '0',
  open_count INTEGER(11) NOT NULL DEFAULT '0',
  click_count INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_edition_send_item__edition_send_id ON cjwnl_edition_send_item  ( edition_send_id );
CREATE  INDEX cjwnl_edition_send_item__newsletter_user_id ON cjwnl_edition_send_item  ( newsletter_user_id );
CREATE  INDEX cjwnl_edition_send_item__subscription_id ON cjwnl_edition_send_item  ( subscription_id );

CREATE TABLE cjwnl_edition_send_output (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  edition_send_id INTEGER(11) NOT NULL DEFAULT '0',
  language varchar(20) NOT NULL DEFAULT '',
  output_xml longtext DEFAULT NULL,
  created INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  UNIQUE INDEX cjwnl_edition_send_output_lang ON cjwnl_edition_send_output  ( edition_send_id, language );

CREATE TABLE cjwnl_import (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  type varchar(255) NOT NULL DEFAULT '',
  list_contentobject_id INTEGER(11) DEFAULT NULL,
  created INTEGER(11) DEFAULT NULL,
  creator_contentobject_id varchar(45) DEFAULT NULL,
  note text,
  data_text longtext NOT NULL,
  remote_id varchar(255) NOT NULL DEFAULT '',
  data_xml longtext NOT NULL,
  imported INTEGER(11) NOT NULL DEFAULT '0',
  imported_user_count INTEGER(11) NOT NULL DEFAULT '0',
  imported_subscription_count INTEGER(11) NOT NULL DEFAULT '0',
  mapping_id INTEGER(11) NOT NULL DEFAULT '0',
  is_dry_run tinyint(1) NOT NULL DEFAULT '0',
  consent_source varchar(100) NOT NULL DEFAULT '',
  status tinyint(4) NOT NULL DEFAULT '0',
  skipped_count INTEGER(11) NOT NULL DEFAULT '0',
  error_count INTEGER(11) NOT NULL DEFAULT '0'
);

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

CREATE TABLE cjwnl_list (
  contentobject_attribute_id INTEGER(11) NOT NULL DEFAULT '0',
  contentobject_attribute_version INTEGER(11) NOT NULL DEFAULT '0',
  contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  contentclass_id INTEGER(11) NOT NULL DEFAULT '0',
  main_siteaccess varchar(255) NOT NULL DEFAULT '',
  siteaccess_array_string varchar(255) NOT NULL DEFAULT '',
  output_format_array_string varchar(255) NOT NULL DEFAULT '',
  email_sender_name varchar(255) NOT NULL DEFAULT '',
  email_sender varchar(255) NOT NULL DEFAULT '',
  email_reply_to varchar(255) NOT NULL DEFAULT '',
  email_return_path varchar(255) NOT NULL DEFAULT '',
  email_receiver_test varchar(255) NOT NULL DEFAULT '',
  auto_approve_registered_user tinyint(1) NOT NULL DEFAULT '0',
  skin_name varchar(255) NOT NULL DEFAULT 'default',
  personalize_content tinyint(1) NOT NULL DEFAULT '0',
  user_data_fields text NOT NULL,
  is_virtual tinyint(1) NOT NULL DEFAULT '0',
  virtual_filter text NOT NULL,
  skin_name_array_string varchar(255) NOT NULL DEFAULT '',
  main_language varchar(20) NOT NULL DEFAULT '',
  language_array_string varchar(255) NOT NULL DEFAULT '',
  interest_source varchar(20) NOT NULL DEFAULT '',
  approval_required tinyint(1) NOT NULL DEFAULT '0',
  article_pool_id INTEGER(11) NOT NULL DEFAULT '0',
  tracking_mode tinyint(4) NOT NULL DEFAULT '0',
  sms_enabled tinyint(1) NOT NULL DEFAULT '0',
  sms_sender varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY ( contentobject_attribute_id, contentobject_attribute_version )
);
CREATE  INDEX cjwnl_list__contentobject_attribute_id ON cjwnl_list  ( contentobject_attribute_id );
CREATE  INDEX cjwnl_list__contentobject_attribute_version ON cjwnl_list  ( contentobject_attribute_version );
CREATE  INDEX cjwnl_list__contentobject_id ON cjwnl_list  ( contentobject_id );

CREATE TABLE cjwnl_mailbox (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email varchar(255) DEFAULT NULL,
  server varchar(255) DEFAULT NULL,
  port INTEGER(11) DEFAULT NULL,
  user_name varchar(255) DEFAULT NULL,
  password varchar(255) DEFAULT NULL,
  type varchar(10) DEFAULT 'imap',
  delete_mails_from_server tinyint(1) NOT NULL DEFAULT '0',
  is_ssl tinyint(1) NOT NULL DEFAULT '0',
  is_activated tinyint(1) DEFAULT '1',
  last_server_connect INTEGER(11) DEFAULT NULL
);

CREATE TABLE cjwnl_mailbox_item (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  mailbox_id INTEGER(11) DEFAULT NULL,
  message_id INTEGER(11) DEFAULT NULL,
  message_identifier varchar(50) DEFAULT NULL,
  message_size INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) DEFAULT NULL,
  processed INTEGER(11) DEFAULT NULL,
  bounce_code varchar(255) DEFAULT NULL,
  email_from varchar(255) DEFAULT NULL,
  email_to varchar(255) DEFAULT NULL,
  email_subject varchar(255) DEFAULT NULL,
  email_send_date INTEGER(11) DEFAULT NULL,
  edition_send_id INTEGER(11) DEFAULT NULL,
  edition_send_item_id INTEGER(11) NOT NULL DEFAULT '0',
  newsletter_user_id INTEGER(11) DEFAULT NULL
);
CREATE  INDEX cjwnl_mailbox_item__edition_send_id ON cjwnl_mailbox_item  ( edition_send_id );
CREATE  INDEX cjwnl_mailbox_item__mailbox_id ON cjwnl_mailbox_item  ( mailbox_id );
CREATE  INDEX cjwnl_mailbox_item__newsletter_user_id ON cjwnl_mailbox_item  ( newsletter_user_id );

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

CREATE TABLE cjwnl_open (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  edition_send_id INTEGER(11) NOT NULL DEFAULT '0',
  edition_send_item_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_open_created ON cjwnl_open  ( created );
CREATE  INDEX cjwnl_open_item ON cjwnl_open  ( edition_send_item_id );
CREATE  INDEX cjwnl_open_send ON cjwnl_open  ( edition_send_id );

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

CREATE TABLE cjwnl_subscription (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  list_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  newsletter_user_id INTEGER(11) DEFAULT NULL,
  hash varchar(255) NOT NULL DEFAULT '',
  status tinyint(4) NOT NULL DEFAULT '0',
  output_format_array_string varchar(255) NOT NULL DEFAULT '',
  creator_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  modifier_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0',
  confirmed INTEGER(11) NOT NULL DEFAULT '0',
  approved INTEGER(11) NOT NULL DEFAULT '0',
  removed INTEGER(11) NOT NULL DEFAULT '0',
  remote_id varchar(255) NOT NULL DEFAULT '',
  import_id INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_subscription__import_id ON cjwnl_subscription  ( import_id );
CREATE  INDEX cjwnl_subscription__list_contentobject_id ON cjwnl_subscription  ( list_contentobject_id );
CREATE  INDEX cjwnl_subscription__newsletter_user_id ON cjwnl_subscription  ( newsletter_user_id );

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

CREATE TABLE cjwnl_user (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email varchar(255) DEFAULT NULL,
  salutation tinyint(4) DEFAULT NULL,
  first_name varchar(255) DEFAULT NULL,
  last_name varchar(255) DEFAULT NULL,
  organisation varchar(255) DEFAULT NULL,
  birthday varchar(10) DEFAULT NULL,
  data_xml text,
  hash varchar(255) DEFAULT NULL,
  ez_user_id INTEGER(11) DEFAULT NULL,
  status tinyint(4) NOT NULL DEFAULT '0',
  creator_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0',
  modified INTEGER(11) NOT NULL DEFAULT '0',
  modifier_contentobject_id INTEGER(11) NOT NULL DEFAULT '0',
  confirmed INTEGER(11) NOT NULL DEFAULT '0',
  removed INTEGER(11) NOT NULL DEFAULT '0',
  bounced INTEGER(11) NOT NULL DEFAULT '0',
  blacklisted INTEGER(11) NOT NULL DEFAULT '0',
  note text,
  external_user_id INTEGER(11) DEFAULT NULL,
  remote_id varchar(255) DEFAULT NULL,
  import_id INTEGER(11) DEFAULT NULL,
  bounce_count tinyint(4) DEFAULT '0',
  data_text text,
  custom_data_text_1 varchar(255) NOT NULL DEFAULT '',
  custom_data_text_2 varchar(255) NOT NULL DEFAULT '',
  custom_data_text_3 varchar(255) NOT NULL DEFAULT '',
  custom_data_text_4 varchar(255) NOT NULL DEFAULT '',
  language varchar(20) NOT NULL DEFAULT '',
  soft_bounce_count tinyint(4) NOT NULL DEFAULT '0',
  last_bounce INTEGER(11) NOT NULL DEFAULT '0',
  phone_number varchar(50) NOT NULL DEFAULT '',
  phone_status tinyint(4) NOT NULL DEFAULT '0',
  phone_confirmed INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_user__ez_user_id ON cjwnl_user  ( ez_user_id );
CREATE  INDEX cjwnl_user__import_id ON cjwnl_user  ( import_id );

CREATE TABLE cjwnl_user_interest (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  newsletter_user_id INTEGER(11) NOT NULL DEFAULT '0',
  interest_id INTEGER(11) NOT NULL DEFAULT '0',
  created INTEGER(11) NOT NULL DEFAULT '0'
);
CREATE  INDEX cjwnl_user_interest_interest ON cjwnl_user_interest  ( interest_id );
CREATE  UNIQUE INDEX cjwnl_user_interest_pair ON cjwnl_user_interest  ( newsletter_user_id, interest_id );
