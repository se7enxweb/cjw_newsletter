CREATE TABLE cjwnl_blacklist_item (
  id int(11) NOT NULL AUTO_INCREMENT,
  email_hash varchar(255) DEFAULT NULL,
  email varchar(255) DEFAULT NULL,
  newsletter_user_id int(11) NOT NULL,
  created int(11) DEFAULT NULL,
  creator_contentobject_id int(11) DEFAULT NULL,
  note text,
  PRIMARY KEY (id),
  KEY cjwnewsletter_user_id (newsletter_user_id)
) COMMENT='table with blacklisted user emails' ENGINE=InnoDB;


CREATE TABLE cjwnl_edition (
  contentobject_attribute_id int(11) NOT NULL,
  contentobject_attribute_version int(11) NOT NULL,
  contentobject_id int(11) NOT NULL,
  contentclass_id int(11) NOT NULL,
  PRIMARY KEY (contentobject_attribute_id,contentobject_attribute_version),
  KEY contentobject_id (contentobject_id),
  KEY contentobject_attribute_id (contentobject_attribute_id),
  KEY contentobject_attribute_version (contentobject_attribute_version)
) ENGINE=InnoDB;


CREATE TABLE cjwnl_edition_send (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_contentobject_id int(11) NOT NULL,
  list_contentobject_version int(11) NOT NULL DEFAULT '0',
  list_is_virtual tinyint(1) NOT NULL DEFAULT '0',
  edition_contentobject_id int(11) NOT NULL,
  edition_contentobject_version int(11) NOT NULL,
  created int(11) NOT NULL COMMENT 'when the edition is marked for send out',
  status tinyint(4) NOT NULL DEFAULT '0' COMMENT '0- WAIT_FOR_PROCESS, 1 - MAILQUEUE_CREATED, 2 - MAILQUEUE_PROCESS_STARTED, 3 - MAILQUEUE_PROCESS_FINISHED, 4 -STATUS_WAIT_FOR_SCHEDULE, 9 - ABORT',
  siteaccess varchar(50) NOT NULL,
  output_format_array_string varchar(50) NOT NULL,
  creator_id int(11) NOT NULL,
  mailqueue_created int(11) NOT NULL,
  mailqueue_process_scheduled int(11) NULL DEFAULT NULL,
  mailqueue_process_started int(11) NOT NULL,
  mailqueue_process_finished int(11) NOT NULL,
  mailqueue_process_aborted int(11) NOT NULL,
  output_xml longtext NOT NULL COMMENT 'xml with newsletter version of html, text',
  hash varchar(255) NOT NULL,
  email_sender varchar(255) NOT NULL,
  email_reply_to varchar(255) NOT NULL,
  email_return_path varchar(255) NOT NULL,
  email_sender_name varchar(255) NOT NULL,
  personalize_content tinyint(1) NOT NULL DEFAULT '0',
  skin_name varchar(255) NOT NULL DEFAULT '',
  channel varchar(20) NOT NULL DEFAULT 'email',
  schedule_id int(11) NOT NULL DEFAULT '0',
  tracking_mode tinyint(4) NOT NULL DEFAULT '0',
  ab_test_id int(11) NOT NULL DEFAULT '0',
  test_group_id int(11) NOT NULL DEFAULT '0',
  throttle_transport varchar(50) NOT NULL DEFAULT '',
  segment_id int(11) NOT NULL DEFAULT '0',
  series_step_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  KEY edition_contentobject_id (edition_contentobject_id),
  KEY edition_contentobject_version (edition_contentobject_version),
  KEY list_contentobject_id (list_contentobject_id)
) ENGINE=InnoDB;


CREATE TABLE cjwnl_edition_send_item (
  id int(11) NOT NULL AUTO_INCREMENT,
  edition_send_id int(11) NOT NULL,
  newsletter_user_id int(11) NOT NULL,
  output_format_id tinyint(4) NOT NULL DEFAULT '0',
  subscription_id int(11) NOT NULL,
  created int(11) NOT NULL COMMENT 'timestamp',
  processed int(11) NOT NULL COMMENT 'timestamp',
  status tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 - NEW, 1 - SEND, 9 - ABBORT ',
  hash varchar(255) NOT NULL,
  bounced int(11) NOT NULL,
  retry_count tinyint(4) NOT NULL DEFAULT '0',
  next_retry int(11) NOT NULL DEFAULT '0',
  batch_id int(11) NOT NULL DEFAULT '0',
  language varchar(20) NOT NULL DEFAULT '',
  ab_variant_id int(11) NOT NULL DEFAULT '0',
  first_opened int(11) NOT NULL DEFAULT '0',
  open_count int(11) NOT NULL DEFAULT '0',
  click_count int(11) NOT NULL DEFAULT '0',
  person_tracking tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  KEY edition_send_id (edition_send_id),
  KEY newsletter_user_id (newsletter_user_id),
  KEY subscription_id (subscription_id)
) ENGINE=InnoDB;


CREATE TABLE cjwnl_import (
  id int(11) NOT NULL AUTO_INCREMENT,
  type varchar(255) NOT NULL COMMENT 'import type',
  list_contentobject_id int(11) DEFAULT NULL,
  created int(11) DEFAULT NULL,
  creator_contentobject_id varchar(45) DEFAULT NULL,
  note text,
  data_text longtext NOT NULL,
  remote_id varchar(255) NOT NULL,
  data_xml longtext NOT NULL,
  imported int(11) NOT NULL,
  imported_user_count int(11) NOT NULL,
  imported_subscription_count int(11) NOT NULL,
  mapping_id int(11) NOT NULL DEFAULT '0',
  is_dry_run tinyint(1) NOT NULL DEFAULT '0',
  consent_source varchar(100) NOT NULL DEFAULT '',
  status tinyint(4) NOT NULL DEFAULT '0',
  skipped_count int(11) NOT NULL DEFAULT '0',
  error_count int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=InnoDB;


CREATE TABLE cjwnl_list (
  contentobject_attribute_id int(11) NOT NULL,
  contentobject_attribute_version int(11) NOT NULL,
  contentobject_id int(11) NOT NULL,
  contentclass_id int(11) NOT NULL,
  main_siteaccess varchar(255) NOT NULL,
  siteaccess_array_string varchar(255) NOT NULL,
  output_format_array_string varchar(255) NOT NULL,
  email_sender_name varchar(255) NOT NULL,
  email_sender varchar(255) NOT NULL,
  email_reply_to varchar(255) NOT NULL,
  email_return_path varchar(255) NOT NULL,
  email_receiver_test varchar(255) NOT NULL,
  auto_approve_registered_user tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 - no, 1 - yes',
  skin_name varchar(255) NOT NULL DEFAULT 'default',
  personalize_content tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 - false, 1- true',
  user_data_fields text NOT NULL COMMENT 'definition user data fields for data input ( show / required ) as xml',
  is_virtual tinyint(1) NOT NULL DEFAULT '0',
  virtual_filter text NOT NULL,
  skin_name_array_string varchar(255) NOT NULL DEFAULT '',
  main_language varchar(20) NOT NULL DEFAULT '',
  language_array_string varchar(255) NOT NULL DEFAULT '',
  interest_source varchar(20) NOT NULL DEFAULT '',
  approval_required tinyint(1) NOT NULL DEFAULT '0',
  article_pool_id int(11) NOT NULL DEFAULT '0',
  tracking_mode tinyint(4) NOT NULL DEFAULT '0',
  sms_enabled tinyint(1) NOT NULL DEFAULT '0',
  sms_sender varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (contentobject_attribute_id,contentobject_attribute_version),
  KEY contentobject_id (contentobject_id),
  KEY contentobject_attribute_id (contentobject_attribute_id),
  KEY contentobject_attribute_version (contentobject_attribute_version)
) ENGINE=InnoDB;


CREATE TABLE cjwnl_mailbox (
  id int(11) NOT NULL AUTO_INCREMENT,
  email varchar(255) DEFAULT NULL,
  server varchar(255) DEFAULT NULL,
  port int(11) DEFAULT NULL,
  user_name varchar(255) DEFAULT NULL,
  password varchar(255) DEFAULT NULL,
  type varchar(10) DEFAULT 'imap' COMMENT 'pop3, imap',
  delete_mails_from_server tinyint(1) NOT NULL DEFAULT '0',
  is_ssl tinyint(1) NOT NULL DEFAULT '0',
  is_activated tinyint(1) DEFAULT '1',
  last_server_connect int(11) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB;


CREATE TABLE cjwnl_mailbox_item (
  id int(11) NOT NULL AUTO_INCREMENT,
  mailbox_id int(11) DEFAULT NULL,
  message_id int(11) DEFAULT NULL,
  message_identifier varchar(50) DEFAULT NULL,
  message_size int(11) NOT NULL DEFAULT '0',
  created int(11) DEFAULT NULL,
  processed int(11) DEFAULT NULL,
  bounce_code varchar(255) DEFAULT NULL,
  email_from varchar(255) DEFAULT NULL,
  email_to varchar(255) DEFAULT NULL,
  email_subject varchar(255) DEFAULT NULL,
  email_send_date int(11) DEFAULT NULL,
  edition_send_id int(11) DEFAULT NULL,
  edition_send_item_id int(11) NOT NULL,
  newsletter_user_id int(11) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY edition_send_id (edition_send_id),
  KEY mailbox_id (mailbox_id),
  KEY newsletter_user_id (newsletter_user_id)
) ENGINE=InnoDB;


CREATE TABLE cjwnl_subscription (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_contentobject_id int(11) NOT NULL,
  newsletter_user_id int(11) DEFAULT NULL,
  hash varchar(255) NOT NULL,
  status tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 - PENDING, 1 - CONFIRMED,  2 - APPROVED, 3 - REMOVED_SELF, 4 - REMOVED_ADMIN',
  output_format_array_string varchar(255) NOT NULL COMMENT ';0;1;',
  creator_contentobject_id int(11) NOT NULL,
  created int(11) NOT NULL,
  modifier_contentobject_id int(11) NOT NULL,
  modified int(11) NOT NULL,
  confirmed int(11) NOT NULL,
  approved int(11) NOT NULL,
  removed int(11) NOT NULL,
  remote_id varchar(255) NOT NULL,
  import_id int(11) NOT NULL,
  PRIMARY KEY (id),
  KEY list_contentobject_id (list_contentobject_id),
  KEY newsletter_user_id (newsletter_user_id),
  KEY import_id (import_id)
) ENGINE=InnoDB;


CREATE TABLE cjwnl_user (
  id int(11) NOT NULL AUTO_INCREMENT,
  email varchar(255) DEFAULT NULL,
  salutation tinyint(4) DEFAULT NULL COMMENT '0-no, 1-Mr, 2-Ms',
  first_name varchar(255) DEFAULT NULL,
  last_name varchar(255) DEFAULT NULL,
  organisation varchar(255) DEFAULT NULL,
  birthday varchar(10) DEFAULT NULL COMMENT 'yyyy-mm-dd',
  data_xml text COMMENT 'extra data kodiert in xml',
  hash varchar(255) DEFAULT NULL,
  ez_user_id int(11) DEFAULT NULL,
  status tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 - PENDING, 1 - CONFIRMED',
  creator_contentobject_id int(11) NOT NULL,
  created int(11) NOT NULL,
  modified int(11) NOT NULL,
  modifier_contentobject_id int(11) NOT NULL,
  confirmed int(11) NOT NULL COMMENT 'timestamp email confirmation',
  removed int(11) NOT NULL,
  bounced int(11) NOT NULL,
  blacklisted int(11) NOT NULL,
  note text,
  external_user_id int(11) DEFAULT NULL,
  remote_id varchar(255) DEFAULT NULL,
  import_id int(11) DEFAULT NULL,
  bounce_count tinyint(4) DEFAULT '0',
  data_text text COMMENT 'field is free for any kind of text data',
  custom_data_text_1 varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  custom_data_text_2 varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  custom_data_text_3 varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  custom_data_text_4 varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  language varchar(20) NOT NULL DEFAULT '',
  soft_bounce_count tinyint(4) NOT NULL DEFAULT '0',
  last_bounce int(11) NOT NULL DEFAULT '0',
  phone_number varchar(50) NOT NULL DEFAULT '',
  phone_status tinyint(4) NOT NULL DEFAULT '0',
  phone_confirmed int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  KEY ez_user_id (ez_user_id),
  KEY import_id (import_id)
) ENGINE=InnoDB;


-- cjw_newsletter 4.2.0

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
