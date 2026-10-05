-- cjw_newsletter 4.1.20 -> 4.2.0, PostgreSQL
--
-- The schema of every feature area of 4.2.0 in one change: new columns of cjwnl_edition_send, cjwnl_edition_send_item,
-- cjwnl_list, cjwnl_user and cjwnl_import, and the new tables (see doc/schema-4.2.md of the extension). Run it once:
-- it has no IF NOT EXISTS and fails on a database that has it already.

-- The sequences of the 4.1 tables were named <table>_s; the PostgreSQL driver of the kernel reads the id of a new
-- row from <table>_id_seq, so every insert into them failed to return its id. They are renamed, and the id
-- columns take their default from the new name.

ALTER SEQUENCE cjwnl_blacklist_item_s RENAME TO cjwnl_blacklist_item_id_seq;
ALTER TABLE cjwnl_blacklist_item ALTER COLUMN id SET DEFAULT nextval('cjwnl_blacklist_item_id_seq'::regclass);
ALTER SEQUENCE cjwnl_edition_send_s RENAME TO cjwnl_edition_send_id_seq;
ALTER TABLE cjwnl_edition_send ALTER COLUMN id SET DEFAULT nextval('cjwnl_edition_send_id_seq'::regclass);
ALTER SEQUENCE cjwnl_edition_send_item_s RENAME TO cjwnl_edition_send_item_id_seq;
ALTER TABLE cjwnl_edition_send_item ALTER COLUMN id SET DEFAULT nextval('cjwnl_edition_send_item_id_seq'::regclass);
ALTER SEQUENCE cjwnl_import_s RENAME TO cjwnl_import_id_seq;
ALTER TABLE cjwnl_import ALTER COLUMN id SET DEFAULT nextval('cjwnl_import_id_seq'::regclass);
ALTER SEQUENCE cjwnl_mailbox_s RENAME TO cjwnl_mailbox_id_seq;
ALTER TABLE cjwnl_mailbox ALTER COLUMN id SET DEFAULT nextval('cjwnl_mailbox_id_seq'::regclass);
ALTER SEQUENCE cjwnl_mailbox_item_s RENAME TO cjwnl_mailbox_item_id_seq;
ALTER TABLE cjwnl_mailbox_item ALTER COLUMN id SET DEFAULT nextval('cjwnl_mailbox_item_id_seq'::regclass);
ALTER SEQUENCE cjwnl_subscription_s RENAME TO cjwnl_subscription_id_seq;
ALTER TABLE cjwnl_subscription ALTER COLUMN id SET DEFAULT nextval('cjwnl_subscription_id_seq'::regclass);
ALTER SEQUENCE cjwnl_user_s RENAME TO cjwnl_user_id_seq;
ALTER TABLE cjwnl_user ALTER COLUMN id SET DEFAULT nextval('cjwnl_user_id_seq'::regclass);

ALTER TABLE cjwnl_edition_send ADD COLUMN skin_name character varying(255) DEFAULT ''::character varying NOT NULL;
ALTER TABLE cjwnl_edition_send ADD COLUMN channel character varying(20) DEFAULT 'email'::character varying NOT NULL;
ALTER TABLE cjwnl_edition_send ADD COLUMN schedule_id integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_edition_send ADD COLUMN tracking_mode smallint DEFAULT '0' NOT NULL;
ALTER TABLE cjwnl_edition_send ADD COLUMN ab_test_id integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_edition_send ADD COLUMN test_group_id integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_edition_send ADD COLUMN throttle_transport character varying(50) DEFAULT ''::character varying NOT NULL;

ALTER TABLE cjwnl_edition_send_item ADD COLUMN retry_count smallint DEFAULT '0' NOT NULL;
ALTER TABLE cjwnl_edition_send_item ADD COLUMN next_retry integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_edition_send_item ADD COLUMN batch_id integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_edition_send_item ADD COLUMN "language" character varying(20) DEFAULT ''::character varying NOT NULL;
ALTER TABLE cjwnl_edition_send_item ADD COLUMN ab_variant_id integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_edition_send_item ADD COLUMN first_opened integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_edition_send_item ADD COLUMN open_count integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_edition_send_item ADD COLUMN click_count integer DEFAULT 0 NOT NULL;

ALTER TABLE cjwnl_list ADD COLUMN skin_name_array_string character varying(255) DEFAULT ''::character varying NOT NULL;
ALTER TABLE cjwnl_list ADD COLUMN main_language character varying(20) DEFAULT ''::character varying NOT NULL;
ALTER TABLE cjwnl_list ADD COLUMN language_array_string character varying(255) DEFAULT ''::character varying NOT NULL;
ALTER TABLE cjwnl_list ADD COLUMN interest_source character varying(20) DEFAULT ''::character varying NOT NULL;
ALTER TABLE cjwnl_list ADD COLUMN approval_required smallint DEFAULT '0' NOT NULL;
ALTER TABLE cjwnl_list ADD COLUMN article_pool_id integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_list ADD COLUMN tracking_mode smallint DEFAULT '0' NOT NULL;
ALTER TABLE cjwnl_list ADD COLUMN sms_enabled smallint DEFAULT '0' NOT NULL;
ALTER TABLE cjwnl_list ADD COLUMN sms_sender character varying(50) DEFAULT ''::character varying NOT NULL;

ALTER TABLE cjwnl_user ADD COLUMN "language" character varying(20) DEFAULT ''::character varying NOT NULL;
ALTER TABLE cjwnl_user ADD COLUMN soft_bounce_count smallint DEFAULT '0' NOT NULL;
ALTER TABLE cjwnl_user ADD COLUMN last_bounce integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_user ADD COLUMN phone_number character varying(50) DEFAULT ''::character varying NOT NULL;
ALTER TABLE cjwnl_user ADD COLUMN phone_status smallint DEFAULT '0' NOT NULL;
ALTER TABLE cjwnl_user ADD COLUMN phone_confirmed integer DEFAULT 0 NOT NULL;

ALTER TABLE cjwnl_import ADD COLUMN mapping_id integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_import ADD COLUMN is_dry_run smallint DEFAULT '0' NOT NULL;
ALTER TABLE cjwnl_import ADD COLUMN consent_source character varying(100) DEFAULT ''::character varying NOT NULL;
ALTER TABLE cjwnl_import ADD COLUMN status smallint DEFAULT '0' NOT NULL;
ALTER TABLE cjwnl_import ADD COLUMN skipped_count integer DEFAULT 0 NOT NULL;
ALTER TABLE cjwnl_import ADD COLUMN error_count integer DEFAULT 0 NOT NULL;

CREATE SEQUENCE IF NOT EXISTS cjwnl_throttle_state_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_throttle_state (
  id integer DEFAULT nextval('cjwnl_throttle_state_id_seq'::text) NOT NULL,
  transport character varying(50) DEFAULT ''::character varying NOT NULL,
  window_type character varying(10) DEFAULT 'minute'::character varying NOT NULL,
  window_start integer DEFAULT 0 NOT NULL,
  sent_count integer DEFAULT 0 NOT NULL,
  paused_until integer DEFAULT 0 NOT NULL,
  modified integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_throttle_state_window ON cjwnl_throttle_state USING btree ( transport, window_type );
ALTER TABLE ONLY cjwnl_throttle_state ADD CONSTRAINT cjwnl_throttle_state_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_send_batch_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_send_batch (
  id integer DEFAULT nextval('cjwnl_send_batch_id_seq'::text) NOT NULL,
  edition_send_id integer DEFAULT 0 NOT NULL,
  channel character varying(20) DEFAULT 'email'::character varying NOT NULL,
  batch_number integer DEFAULT 0 NOT NULL,
  item_count integer DEFAULT 0 NOT NULL,
  sent_count integer DEFAULT 0 NOT NULL,
  failed_count integer DEFAULT 0 NOT NULL,
  last_item_id integer DEFAULT 0 NOT NULL,
  status smallint DEFAULT '0' NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  started integer DEFAULT 0 NOT NULL,
  finished integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_send_batch_send ON cjwnl_send_batch USING btree ( edition_send_id, status );
ALTER TABLE ONLY cjwnl_send_batch ADD CONSTRAINT cjwnl_send_batch_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_mailin_address_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_mailin_address (
  id integer DEFAULT nextval('cjwnl_mailin_address_id_seq'::text) NOT NULL,
  list_contentobject_id integer DEFAULT 0 NOT NULL,
  email character varying(255) DEFAULT ''::character varying NOT NULL,
  plus_tag character varying(100) DEFAULT ''::character varying NOT NULL,
  "action" character varying(20) DEFAULT 'both'::character varying NOT NULL,
  mailbox_id integer DEFAULT 0 NOT NULL,
  is_active smallint DEFAULT '1' NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  modified integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_mailin_address_email ON cjwnl_mailin_address USING btree ( email, plus_tag );
CREATE INDEX cjwnl_mailin_address_list ON cjwnl_mailin_address USING btree ( list_contentobject_id );
ALTER TABLE ONLY cjwnl_mailin_address ADD CONSTRAINT cjwnl_mailin_address_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_mailin_message_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_mailin_message (
  id integer DEFAULT nextval('cjwnl_mailin_message_id_seq'::text) NOT NULL,
  mailin_address_id integer DEFAULT 0 NOT NULL,
  message_identifier character varying(255) DEFAULT ''::character varying NOT NULL,
  email_from character varying(255) DEFAULT ''::character varying NOT NULL,
  "action" character varying(20) DEFAULT ''::character varying NOT NULL,
  status smallint DEFAULT '0' NOT NULL,
  pending_token character varying(64) DEFAULT ''::character varying NOT NULL,
  newsletter_user_id integer DEFAULT 0 NOT NULL,
  note text DEFAULT NULL,
  created integer DEFAULT 0 NOT NULL,
  processed integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_mailin_message_ident ON cjwnl_mailin_message USING btree ( message_identifier );
CREATE INDEX cjwnl_mailin_message_status ON cjwnl_mailin_message USING btree ( status, created );
ALTER TABLE ONLY cjwnl_mailin_message ADD CONSTRAINT cjwnl_mailin_message_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_test_group_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_test_group (
  id integer DEFAULT nextval('cjwnl_test_group_id_seq'::text) NOT NULL,
  list_contentobject_id integer DEFAULT 0 NOT NULL,
  name character varying(255) DEFAULT ''::character varying NOT NULL,
  email_list text DEFAULT NULL,
  creator_contentobject_id integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  modified integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_test_group_list ON cjwnl_test_group USING btree ( list_contentobject_id );
ALTER TABLE ONLY cjwnl_test_group ADD CONSTRAINT cjwnl_test_group_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_schedule_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_schedule (
  id integer DEFAULT nextval('cjwnl_schedule_id_seq'::text) NOT NULL,
  list_contentobject_id integer DEFAULT 0 NOT NULL,
  "mode" character varying(20) DEFAULT 'latest'::character varying NOT NULL,
  template_edition_contentobject_id integer DEFAULT 0 NOT NULL,
  recurrence_type character varying(1) DEFAULT 'w'::character varying NOT NULL,
  recurrence_value character varying(255) DEFAULT ''::character varying NOT NULL,
  send_time integer DEFAULT 0 NOT NULL,
  timezone character varying(64) DEFAULT ''::character varying NOT NULL,
  auto_fill smallint DEFAULT '0' NOT NULL,
  article_pool_id integer DEFAULT 0 NOT NULL,
  skip_if_empty smallint DEFAULT '1' NOT NULL,
  condition_handler character varying(255) DEFAULT ''::character varying NOT NULL,
  next_run integer DEFAULT 0 NOT NULL,
  last_run integer DEFAULT 0 NOT NULL,
  last_edition_send_id integer DEFAULT 0 NOT NULL,
  last_result character varying(50) DEFAULT ''::character varying NOT NULL,
  status smallint DEFAULT '0' NOT NULL,
  creator_contentobject_id integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  modified integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_schedule_due ON cjwnl_schedule USING btree ( status, next_run );
CREATE INDEX cjwnl_schedule_list ON cjwnl_schedule USING btree ( list_contentobject_id );
ALTER TABLE ONLY cjwnl_schedule ADD CONSTRAINT cjwnl_schedule_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_schedule_log_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_schedule_log (
  id integer DEFAULT nextval('cjwnl_schedule_log_id_seq'::text) NOT NULL,
  schedule_id integer DEFAULT 0 NOT NULL,
  run_at integer DEFAULT 0 NOT NULL,
  result character varying(20) DEFAULT ''::character varying NOT NULL,
  edition_contentobject_id integer DEFAULT 0 NOT NULL,
  edition_send_id integer DEFAULT 0 NOT NULL,
  article_count integer DEFAULT 0 NOT NULL,
  message text DEFAULT NULL
);
CREATE INDEX cjwnl_schedule_log_schedule ON cjwnl_schedule_log USING btree ( schedule_id, run_at );
ALTER TABLE ONLY cjwnl_schedule_log ADD CONSTRAINT cjwnl_schedule_log_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_article_pool_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_article_pool (
  id integer DEFAULT nextval('cjwnl_article_pool_id_seq'::text) NOT NULL,
  list_contentobject_id integer DEFAULT 0 NOT NULL,
  name character varying(255) DEFAULT ''::character varying NOT NULL,
  is_default smallint DEFAULT '0' NOT NULL,
  parent_node_id_array_string character varying(255) DEFAULT ''::character varying NOT NULL,
  class_identifier_array_string character varying(255) DEFAULT ''::character varying NOT NULL,
  section_id_array_string character varying(255) DEFAULT ''::character varying NOT NULL,
  tag_id_array_string character varying(255) DEFAULT ''::character varying NOT NULL,
  state_id_array_string character varying(255) DEFAULT ''::character varying NOT NULL,
  max_age_days integer DEFAULT 0 NOT NULL,
  max_items integer DEFAULT 10 NOT NULL,
  sort_by character varying(50) DEFAULT 'published'::character varying NOT NULL,
  filter_data text DEFAULT NULL,
  creator_contentobject_id integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  modified integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_article_pool_list ON cjwnl_article_pool USING btree ( list_contentobject_id );
ALTER TABLE ONLY cjwnl_article_pool ADD CONSTRAINT cjwnl_article_pool_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_edition_article_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_edition_article (
  id integer DEFAULT nextval('cjwnl_edition_article_id_seq'::text) NOT NULL,
  edition_contentobject_id integer DEFAULT 0 NOT NULL,
  contentobject_id integer DEFAULT 0 NOT NULL,
  article_pool_id integer DEFAULT 0 NOT NULL,
  "position" integer DEFAULT 0 NOT NULL,
  added_by smallint DEFAULT '0' NOT NULL,
  creator_contentobject_id integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_edition_article_edition ON cjwnl_edition_article USING btree ( edition_contentobject_id, contentobject_id );
CREATE INDEX cjwnl_edition_article_object ON cjwnl_edition_article USING btree ( contentobject_id );
ALTER TABLE ONLY cjwnl_edition_article ADD CONSTRAINT cjwnl_edition_article_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_approval_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_approval (
  id integer DEFAULT nextval('cjwnl_approval_id_seq'::text) NOT NULL,
  edition_contentobject_id integer DEFAULT 0 NOT NULL,
  edition_contentobject_version integer DEFAULT 0 NOT NULL,
  list_contentobject_id integer DEFAULT 0 NOT NULL,
  collaboration_item_id integer DEFAULT 0 NOT NULL,
  status smallint DEFAULT '0' NOT NULL,
  requested_by integer DEFAULT 0 NOT NULL,
  requested integer DEFAULT 0 NOT NULL,
  decided_by integer DEFAULT 0 NOT NULL,
  decided integer DEFAULT 0 NOT NULL,
  "comment" text DEFAULT NULL
);
CREATE INDEX cjwnl_approval_edition ON cjwnl_approval USING btree ( edition_contentobject_id, edition_contentobject_version );
CREATE INDEX cjwnl_approval_collaboration ON cjwnl_approval USING btree ( collaboration_item_id );
ALTER TABLE ONLY cjwnl_approval ADD CONSTRAINT cjwnl_approval_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_interest_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_interest (
  id integer DEFAULT nextval('cjwnl_interest_id_seq'::text) NOT NULL,
  list_contentobject_id integer DEFAULT 0 NOT NULL,
  identifier character varying(100) DEFAULT ''::character varying NOT NULL,
  name character varying(255) DEFAULT ''::character varying NOT NULL,
  source character varying(20) DEFAULT 'topic'::character varying NOT NULL,
  eztags_id integer DEFAULT 0 NOT NULL,
  priority integer DEFAULT 0 NOT NULL,
  is_active smallint DEFAULT '1' NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  modified integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_interest_identifier ON cjwnl_interest USING btree ( list_contentobject_id, identifier );
ALTER TABLE ONLY cjwnl_interest ADD CONSTRAINT cjwnl_interest_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_user_interest_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_user_interest (
  id integer DEFAULT nextval('cjwnl_user_interest_id_seq'::text) NOT NULL,
  newsletter_user_id integer DEFAULT 0 NOT NULL,
  interest_id integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_user_interest_pair ON cjwnl_user_interest USING btree ( newsletter_user_id, interest_id );
CREATE INDEX cjwnl_user_interest_interest ON cjwnl_user_interest USING btree ( interest_id );
ALTER TABLE ONLY cjwnl_user_interest ADD CONSTRAINT cjwnl_user_interest_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_edition_send_output_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_edition_send_output (
  id integer DEFAULT nextval('cjwnl_edition_send_output_id_seq'::text) NOT NULL,
  edition_send_id integer DEFAULT 0 NOT NULL,
  "language" character varying(20) DEFAULT ''::character varying NOT NULL,
  output_xml text DEFAULT NULL,
  created integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_edition_send_output_lang ON cjwnl_edition_send_output USING btree ( edition_send_id, "language" );
ALTER TABLE ONLY cjwnl_edition_send_output ADD CONSTRAINT cjwnl_edition_send_output_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_link_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_link (
  id integer DEFAULT nextval('cjwnl_link_id_seq'::text) NOT NULL,
  edition_send_id integer DEFAULT 0 NOT NULL,
  url_hash character varying(64) DEFAULT ''::character varying NOT NULL,
  url text DEFAULT NULL,
  contentobject_id integer DEFAULT 0 NOT NULL,
  "position" integer DEFAULT 0 NOT NULL,
  click_count integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_link_send_url ON cjwnl_link USING btree ( edition_send_id, url_hash );
CREATE INDEX cjwnl_link_object ON cjwnl_link USING btree ( contentobject_id );
ALTER TABLE ONLY cjwnl_link ADD CONSTRAINT cjwnl_link_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_link_click_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_link_click (
  id integer DEFAULT nextval('cjwnl_link_click_id_seq'::text) NOT NULL,
  link_id integer DEFAULT 0 NOT NULL,
  edition_send_item_id integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_link_click_link ON cjwnl_link_click USING btree ( link_id );
CREATE INDEX cjwnl_link_click_item ON cjwnl_link_click USING btree ( edition_send_item_id );
CREATE INDEX cjwnl_link_click_created ON cjwnl_link_click USING btree ( created );
ALTER TABLE ONLY cjwnl_link_click ADD CONSTRAINT cjwnl_link_click_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_open_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_open (
  id integer DEFAULT nextval('cjwnl_open_id_seq'::text) NOT NULL,
  edition_send_id integer DEFAULT 0 NOT NULL,
  edition_send_item_id integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_open_send ON cjwnl_open USING btree ( edition_send_id );
CREATE INDEX cjwnl_open_item ON cjwnl_open USING btree ( edition_send_item_id );
CREATE INDEX cjwnl_open_created ON cjwnl_open USING btree ( created );
ALTER TABLE ONLY cjwnl_open ADD CONSTRAINT cjwnl_open_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_stat_total_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_stat_total (
  id integer DEFAULT nextval('cjwnl_stat_total_id_seq'::text) NOT NULL,
  edition_send_id integer DEFAULT 0 NOT NULL,
  link_id integer DEFAULT 0 NOT NULL,
  stat_type character varying(20) DEFAULT ''::character varying NOT NULL,
  stat_day integer DEFAULT 0 NOT NULL,
  total integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_stat_total_key ON cjwnl_stat_total USING btree ( edition_send_id, link_id, stat_type, stat_day );
CREATE INDEX cjwnl_stat_total_day ON cjwnl_stat_total USING btree ( stat_type, stat_day );
ALTER TABLE ONLY cjwnl_stat_total ADD CONSTRAINT cjwnl_stat_total_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_ab_test_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_ab_test (
  id integer DEFAULT nextval('cjwnl_ab_test_id_seq'::text) NOT NULL,
  edition_send_id integer DEFAULT 0 NOT NULL,
  status smallint DEFAULT '0' NOT NULL,
  sample_percent integer DEFAULT 10 NOT NULL,
  variant_count integer DEFAULT 2 NOT NULL,
  criterion character varying(20) DEFAULT 'open'::character varying NOT NULL,
  wait_seconds integer DEFAULT 14400 NOT NULL,
  winner_variant_id integer DEFAULT 0 NOT NULL,
  samples_sent integer DEFAULT 0 NOT NULL,
  decided integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  modified integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_ab_test_send ON cjwnl_ab_test USING btree ( edition_send_id );
CREATE INDEX cjwnl_ab_test_status ON cjwnl_ab_test USING btree ( status );
ALTER TABLE ONLY cjwnl_ab_test ADD CONSTRAINT cjwnl_ab_test_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_ab_variant_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_ab_variant (
  id integer DEFAULT nextval('cjwnl_ab_variant_id_seq'::text) NOT NULL,
  ab_test_id integer DEFAULT 0 NOT NULL,
  variant_key character varying(10) DEFAULT ''::character varying NOT NULL,
  subject character varying(255) DEFAULT ''::character varying NOT NULL,
  item_count integer DEFAULT 0 NOT NULL,
  open_count integer DEFAULT 0 NOT NULL,
  click_count integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX cjwnl_ab_variant_test ON cjwnl_ab_variant USING btree ( ab_test_id, variant_key );
ALTER TABLE ONLY cjwnl_ab_variant ADD CONSTRAINT cjwnl_ab_variant_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_sms_code_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_sms_code (
  id integer DEFAULT nextval('cjwnl_sms_code_id_seq'::text) NOT NULL,
  newsletter_user_id integer DEFAULT 0 NOT NULL,
  phone_number character varying(50) DEFAULT ''::character varying NOT NULL,
  code_hash character varying(64) DEFAULT ''::character varying NOT NULL,
  purpose character varying(20) DEFAULT 'confirm'::character varying NOT NULL,
  attempts smallint DEFAULT '0' NOT NULL,
  expires integer DEFAULT 0 NOT NULL,
  used integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_sms_code_user ON cjwnl_sms_code USING btree ( newsletter_user_id, purpose );
ALTER TABLE ONLY cjwnl_sms_code ADD CONSTRAINT cjwnl_sms_code_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_sms_message_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_sms_message (
  id integer DEFAULT nextval('cjwnl_sms_message_id_seq'::text) NOT NULL,
  edition_send_id integer DEFAULT 0 NOT NULL,
  newsletter_user_id integer DEFAULT 0 NOT NULL,
  phone_number character varying(50) DEFAULT ''::character varying NOT NULL,
  body text DEFAULT NULL,
  status smallint DEFAULT '0' NOT NULL,
  transport character varying(50) DEFAULT ''::character varying NOT NULL,
  provider_message_id character varying(255) DEFAULT ''::character varying NOT NULL,
  error text DEFAULT NULL,
  batch_id integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  processed integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_sms_message_send ON cjwnl_sms_message USING btree ( edition_send_id, status );
CREATE INDEX cjwnl_sms_message_user ON cjwnl_sms_message USING btree ( newsletter_user_id );
ALTER TABLE ONLY cjwnl_sms_message ADD CONSTRAINT cjwnl_sms_message_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_sms_inbound_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_sms_inbound (
  id integer DEFAULT nextval('cjwnl_sms_inbound_id_seq'::text) NOT NULL,
  phone_number character varying(50) DEFAULT ''::character varying NOT NULL,
  keyword character varying(50) DEFAULT ''::character varying NOT NULL,
  body text DEFAULT NULL,
  newsletter_user_id integer DEFAULT 0 NOT NULL,
  "action" character varying(20) DEFAULT ''::character varying NOT NULL,
  provider_message_id character varying(255) DEFAULT ''::character varying NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  processed integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_sms_inbound_phone ON cjwnl_sms_inbound USING btree ( phone_number );
ALTER TABLE ONLY cjwnl_sms_inbound ADD CONSTRAINT cjwnl_sms_inbound_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_import_mapping_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_import_mapping (
  id integer DEFAULT nextval('cjwnl_import_mapping_id_seq'::text) NOT NULL,
  name character varying(255) DEFAULT ''::character varying NOT NULL,
  list_contentobject_id integer DEFAULT 0 NOT NULL,
  mapping text DEFAULT NULL,
  "delimiter" character varying(5) DEFAULT ';'::character varying NOT NULL,
  has_header smallint DEFAULT '1' NOT NULL,
  "encoding" character varying(20) DEFAULT 'UTF-8'::character varying NOT NULL,
  consent_source character varying(100) DEFAULT ''::character varying NOT NULL,
  creator_contentobject_id integer DEFAULT 0 NOT NULL,
  created integer DEFAULT 0 NOT NULL,
  modified integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_import_mapping_list ON cjwnl_import_mapping USING btree ( list_contentobject_id );
ALTER TABLE ONLY cjwnl_import_mapping ADD CONSTRAINT cjwnl_import_mapping_pkey PRIMARY KEY ( id );

CREATE SEQUENCE IF NOT EXISTS cjwnl_migration_log_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_migration_log (
  id integer DEFAULT nextval('cjwnl_migration_log_id_seq'::text) NOT NULL,
  run_id character varying(40) DEFAULT ''::character varying NOT NULL,
  source_table character varying(100) DEFAULT ''::character varying NOT NULL,
  source_id character varying(100) DEFAULT ''::character varying NOT NULL,
  target_table character varying(100) DEFAULT ''::character varying NOT NULL,
  target_id integer DEFAULT 0 NOT NULL,
  "action" character varying(20) DEFAULT ''::character varying NOT NULL,
  is_dry_run smallint DEFAULT '0' NOT NULL,
  message text DEFAULT NULL,
  created integer DEFAULT 0 NOT NULL
);
CREATE INDEX cjwnl_migration_log_run ON cjwnl_migration_log USING btree ( run_id );
CREATE INDEX cjwnl_migration_log_source ON cjwnl_migration_log USING btree ( source_table, source_id );
ALTER TABLE ONLY cjwnl_migration_log ADD CONSTRAINT cjwnl_migration_log_pkey PRIMARY KEY ( id );
