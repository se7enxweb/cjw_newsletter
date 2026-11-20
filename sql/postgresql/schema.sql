CREATE SEQUENCE cjwnl_blacklist_item_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 9223372036854775807
    MINVALUE 1
    CACHE 1;

CREATE SEQUENCE cjwnl_edition_send_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 9223372036854775807
    MINVALUE 1
    CACHE 1;

CREATE SEQUENCE cjwnl_edition_send_item_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 9223372036854775807
    MINVALUE 1
    CACHE 1;

CREATE SEQUENCE cjwnl_import_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 9223372036854775807
    MINVALUE 1
    CACHE 1;

CREATE SEQUENCE cjwnl_mailbox_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 9223372036854775807
    MINVALUE 1
    CACHE 1;

CREATE SEQUENCE cjwnl_mailbox_item_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 9223372036854775807
    MINVALUE 1
    CACHE 1;

CREATE SEQUENCE cjwnl_subscription_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 9223372036854775807
    MINVALUE 1
    CACHE 1;

CREATE SEQUENCE cjwnl_user_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 9223372036854775807
    MINVALUE 1
    CACHE 1;

CREATE TABLE cjwnl_blacklist_item (
  id integer NOT NULL DEFAULT nextval('cjwnl_blacklist_item_id_seq'::regclass),
  email_hash character varying(255) DEFAULT NULL::character varying,
  email character varying(255) DEFAULT NULL::character varying,
  newsletter_user_id integer NOT NULL DEFAULT 0,
  created integer,
  creator_contentobject_id integer,
  note text,
  CONSTRAINT cjwnl_blacklist_item_pkey PRIMARY KEY (id)
);

CREATE INDEX cjwnl_blacklist_item_user_id ON cjwnl_blacklist_item USING btree (newsletter_user_id);

CREATE TABLE cjwnl_edition (
  contentobject_attribute_id integer NOT NULL DEFAULT 0,
  contentobject_attribute_version integer NOT NULL DEFAULT 0,
  contentobject_id integer NOT NULL DEFAULT 0,
  contentclass_id integer NOT NULL DEFAULT 0,
  CONSTRAINT cjwnl_edition_pkey PRIMARY KEY (contentobject_attribute_id, contentobject_attribute_version)
);

CREATE INDEX cjwnl_edition_contentobject_attr_id ON cjwnl_edition USING btree (contentobject_attribute_id);
CREATE INDEX cjwnl_edition_contentobject_attr_version ON cjwnl_edition USING btree (contentobject_attribute_version);
CREATE INDEX cjwnl_edition_contentobject_id ON cjwnl_edition USING btree (contentobject_id);

CREATE TABLE cjwnl_edition_send (
  id integer NOT NULL DEFAULT nextval('cjwnl_edition_send_id_seq'::regclass),
  list_contentobject_id integer NOT NULL DEFAULT 0,
  list_contentobject_version integer NOT NULL DEFAULT 0,
  list_is_virtual smallint NOT NULL DEFAULT 0::smallint,
  edition_contentobject_id integer NOT NULL DEFAULT 0,
  edition_contentobject_version integer NOT NULL DEFAULT 0,
  created integer NOT NULL DEFAULT 0,
  status smallint NOT NULL DEFAULT 0::smallint,
  siteaccess character varying(50) NOT NULL,
  output_format_array_string character varying(50) NOT NULL,
  creator_id integer NOT NULL DEFAULT 0,
  mailqueue_created integer NOT NULL DEFAULT 0,
  mailqueue_process_scheduled integer DEFAULT NULL,
  mailqueue_process_started integer NOT NULL DEFAULT 0,
  mailqueue_process_finished integer NOT NULL DEFAULT 0,
  mailqueue_process_aborted integer NOT NULL DEFAULT 0,
  output_xml text NOT NULL,
  hash character varying(255) NOT NULL,
  email_sender character varying(255) NOT NULL,
  email_reply_to character varying(255) NOT NULL DEFAULT ''::character varying,
  email_return_path character varying(255) NOT NULL DEFAULT ''::character varying,
  email_sender_name character varying(255) NOT NULL,
  personalize_content smallint NOT NULL DEFAULT 0::smallint,
  skin_name character varying(255) DEFAULT ''::character varying NOT NULL,
  channel character varying(20) DEFAULT 'email'::character varying NOT NULL,
  schedule_id integer DEFAULT 0 NOT NULL,
  tracking_mode smallint DEFAULT '0' NOT NULL,
  ab_test_id integer DEFAULT 0 NOT NULL,
  test_group_id integer DEFAULT 0 NOT NULL,
  throttle_transport character varying(50) DEFAULT ''::character varying NOT NULL,
  segment_id integer DEFAULT 0 NOT NULL,
  series_step_id integer DEFAULT 0 NOT NULL,
  CONSTRAINT cjwnl_edition_send_pkey PRIMARY KEY (id)
);

CREATE INDEX cjwnl_edition_send_edition_coid ON cjwnl_edition_send USING btree (edition_contentobject_id);
CREATE INDEX cjwnl_edition_send_edition_co_version ON cjwnl_edition_send USING btree (edition_contentobject_version);
CREATE INDEX cjwnl_edition_send_list_coid ON cjwnl_edition_send USING btree (list_contentobject_id);

CREATE TABLE cjwnl_edition_send_item (
  id integer NOT NULL DEFAULT nextval('cjwnl_edition_send_item_id_seq'::regclass),
  edition_send_id integer NOT NULL DEFAULT 0,
  newsletter_user_id integer NOT NULL DEFAULT 0,
  output_format_id smallint NOT NULL DEFAULT 0::smallint,
  subscription_id integer NOT NULL DEFAULT 0,
  created integer NOT NULL DEFAULT 0,
  processed integer NOT NULL DEFAULT 0,
  status smallint NOT NULL DEFAULT 0::smallint,
  hash character varying(255) NOT NULL,
  bounced integer NOT NULL DEFAULT 0,
  retry_count smallint DEFAULT '0' NOT NULL,
  next_retry integer DEFAULT 0 NOT NULL,
  batch_id integer DEFAULT 0 NOT NULL,
  "language" character varying(20) DEFAULT ''::character varying NOT NULL,
  ab_variant_id integer DEFAULT 0 NOT NULL,
  first_opened integer DEFAULT 0 NOT NULL,
  open_count integer DEFAULT 0 NOT NULL,
  click_count integer DEFAULT 0 NOT NULL,
  person_tracking smallint DEFAULT '0' NOT NULL,
  CONSTRAINT cjwnl_edition_send_item_pkey PRIMARY KEY (id)
);

CREATE INDEX cjwnl_edition_send_item_esid ON cjwnl_edition_send_item USING btree (edition_send_id);
CREATE INDEX cjwnl_edition_send_item_nuid ON cjwnl_edition_send_item USING btree (newsletter_user_id);
CREATE INDEX cjwnl_edition_send_item_sid ON cjwnl_edition_send_item USING btree (subscription_id);

CREATE TABLE cjwnl_import (
  id integer NOT NULL DEFAULT nextval('cjwnl_import_id_seq'::regclass),
  "type" character varying(255) NOT NULL,
  list_contentobject_id integer,
  created integer,
  creator_contentobject_id character varying(45) DEFAULT NULL::character varying,
  note text,
  data_text text NOT NULL,
  remote_id character varying(255) NOT NULL,
  data_xml text NOT NULL,
  imported integer NOT NULL DEFAULT 0,
  imported_user_count integer NOT NULL DEFAULT 0,
  imported_subscription_count integer NOT NULL DEFAULT 0,
  mapping_id integer DEFAULT 0 NOT NULL,
  is_dry_run smallint DEFAULT '0' NOT NULL,
  consent_source character varying(100) DEFAULT ''::character varying NOT NULL,
  status smallint DEFAULT '0' NOT NULL,
  skipped_count integer DEFAULT 0 NOT NULL,
  error_count integer DEFAULT 0 NOT NULL,
  CONSTRAINT cjwnl_import_pkey PRIMARY KEY (id)
);

CREATE TABLE cjwnl_list (
  contentobject_attribute_id integer NOT NULL DEFAULT 0,
  contentobject_attribute_version integer NOT NULL DEFAULT 0,
  contentobject_id integer NOT NULL DEFAULT 0,
  contentclass_id integer NOT NULL DEFAULT 0,
  main_siteaccess character varying(255) NOT NULL,
  siteaccess_array_string character varying(255) NOT NULL,
  output_format_array_string character varying(255) NOT NULL,
  email_sender_name character varying(255) NOT NULL,
  email_sender character varying(255) NOT NULL,
  email_reply_to character varying(255) NOT NULL DEFAULT ''::character varying,
  email_return_path character varying(255) NOT NULL DEFAULT ''::character varying,
  email_receiver_test character varying(255) NOT NULL,
  auto_approve_registered_user smallint NOT NULL DEFAULT 0::smallint,
  skin_name character varying(255) NOT NULL DEFAULT 'default'::character varying,
  personalize_content smallint NOT NULL DEFAULT 0::smallint,
  user_data_fields text NOT NULL,
  is_virtual smallint NOT NULL DEFAULT 0::smallint,
  virtual_filter text NOT NULL DEFAULT ''::text,
  skin_name_array_string character varying(255) DEFAULT ''::character varying NOT NULL,
  main_language character varying(20) DEFAULT ''::character varying NOT NULL,
  language_array_string character varying(255) DEFAULT ''::character varying NOT NULL,
  interest_source character varying(20) DEFAULT ''::character varying NOT NULL,
  approval_required smallint DEFAULT '0' NOT NULL,
  article_pool_id integer DEFAULT 0 NOT NULL,
  tracking_mode smallint DEFAULT '0' NOT NULL,
  sms_enabled smallint DEFAULT '0' NOT NULL,
  sms_sender character varying(50) DEFAULT ''::character varying NOT NULL,
  CONSTRAINT cjwnl_list_pkey PRIMARY KEY (contentobject_attribute_id, contentobject_attribute_version)
);

CREATE INDEX cjwnl_list_contentobject_attr_id ON cjwnl_list USING btree (contentobject_attribute_id);
CREATE INDEX cjwnl_list_contentobject_attr_version ON cjwnl_list USING btree (contentobject_attribute_version);
CREATE INDEX cjwnl_list_contentobject_id ON cjwnl_list USING btree (contentobject_id);

CREATE TABLE cjwnl_mailbox (
  id integer NOT NULL DEFAULT nextval('cjwnl_mailbox_id_seq'::regclass),
  email character varying(255) DEFAULT NULL::character varying,
  server character varying(255) DEFAULT NULL::character varying,
  port integer,
  user_name character varying(255) DEFAULT NULL::character varying,
  "password" character varying(255) DEFAULT NULL::character varying,
  "type" character varying(10) DEFAULT 'imap'::character varying,
  delete_mails_from_server smallint NOT NULL DEFAULT 0::smallint,
  is_ssl smallint NOT NULL DEFAULT 0::smallint,
  is_activated smallint DEFAULT 1::smallint,
  last_server_connect integer,
  CONSTRAINT cjwnl_mailbox_pkey PRIMARY KEY (id)
);

CREATE TABLE cjwnl_mailbox_item (
  id integer NOT NULL DEFAULT nextval('cjwnl_mailbox_item_id_seq'::regclass),
  mailbox_id integer,
  message_id integer,
  message_identifier character varying(50) DEFAULT NULL::character varying,
  message_size integer NOT NULL DEFAULT 0,
  created integer,
  processed integer,
  bounce_code character varying(255) DEFAULT NULL::character varying,
  email_from character varying(255) DEFAULT NULL::character varying,
  email_to character varying(255) DEFAULT NULL::character varying,
  email_subject character varying(255) DEFAULT NULL::character varying,
  email_send_date integer,
  edition_send_id integer,
  edition_send_item_id integer NOT NULL DEFAULT 0,
  newsletter_user_id integer,
  CONSTRAINT cjwnl_mailbox_item_pkey PRIMARY KEY (id)
);

CREATE INDEX cjwnl_mailbox_item_edition_sid ON cjwnl_mailbox_item USING btree (edition_send_id);
CREATE INDEX cjwnl_mailbox_item_mailbox_id ON cjwnl_mailbox_item USING btree (mailbox_id);
CREATE INDEX cjwnl_mailbox_item_newsletter_uid ON cjwnl_mailbox_item USING btree (newsletter_user_id);

CREATE TABLE cjwnl_subscription (
  id integer NOT NULL DEFAULT nextval('cjwnl_subscription_id_seq'::regclass),
  list_contentobject_id integer NOT NULL DEFAULT 0,
  newsletter_user_id integer NOT NULL DEFAULT 0,
  hash character varying(255) NOT NULL,
  status smallint NOT NULL DEFAULT 0,
  output_format_array_string character varying(255) NOT NULL,
  creator_contentobject_id integer NOT NULL DEFAULT 0,
  created integer NOT NULL DEFAULT 0,
  modifier_contentobject_id integer NOT NULL DEFAULT 0,
  modified integer NOT NULL DEFAULT 0,
  confirmed integer NOT NULL DEFAULT 0,
  approved integer NOT NULL DEFAULT 0,
  removed integer NOT NULL DEFAULT 0,
  remote_id character varying(255) NOT NULL,
  import_id integer NOT NULL DEFAULT 0,
  CONSTRAINT cjwnl_subscription_pkey PRIMARY KEY (id)
);

CREATE INDEX cjwnl_subscription_import_id ON cjwnl_subscription USING btree (import_id);
CREATE INDEX cjwnl_subscription_list_contentobject_id ON cjwnl_subscription USING btree (list_contentobject_id);
CREATE INDEX cjwnl_subscription_newsletter_user_id ON cjwnl_subscription USING btree (newsletter_user_id);

CREATE TABLE cjwnl_user (
  id integer NOT NULL DEFAULT nextval('cjwnl_user_id_seq'::regclass),
  email character varying(255) DEFAULT NULL::character varying,
  salutation smallint,
  first_name character varying(255) DEFAULT NULL::character varying,
  last_name character varying(255) DEFAULT NULL::character varying,
  organisation character varying(255) DEFAULT NULL::character varying,
  birthday character varying(10) DEFAULT NULL::character varying,
  data_xml text,
  hash character varying(255) DEFAULT NULL::character varying,
  ez_user_id integer,
  status smallint NOT NULL DEFAULT 0::smallint,
  creator_contentobject_id integer NOT NULL DEFAULT 0,
  created integer NOT NULL DEFAULT 0,
  modified integer NOT NULL DEFAULT 0,
  modifier_contentobject_id integer NOT NULL DEFAULT 0,
  confirmed integer NOT NULL DEFAULT 0,
  removed integer NOT NULL DEFAULT 0,
  bounced integer NOT NULL DEFAULT 0,
  blacklisted integer NOT NULL DEFAULT 0,
  external_user_id integer NOT NULL DEFAULT 0,
  note text,
  remote_id character varying(255) DEFAULT NULL::character varying,
  import_id integer,
  bounce_count smallint DEFAULT 0::smallint,
  data_text text,
  custom_data_text_1 character varying(255) NOT NULL DEFAULT ''::character varying,
  custom_data_text_2 character varying(255) NOT NULL DEFAULT ''::character varying,
  custom_data_text_3 character varying(255) NOT NULL DEFAULT ''::character varying,
  custom_data_text_4 character varying(255) NOT NULL DEFAULT ''::character varying,
  "language" character varying(20) DEFAULT ''::character varying NOT NULL,
  soft_bounce_count smallint DEFAULT '0' NOT NULL,
  last_bounce integer DEFAULT 0 NOT NULL,
  phone_number character varying(50) DEFAULT ''::character varying NOT NULL,
  phone_status smallint DEFAULT '0' NOT NULL,
  phone_confirmed integer DEFAULT 0 NOT NULL,
  CONSTRAINT cjwnl_user_pkey PRIMARY KEY (id)
);

CREATE INDEX cjwnl_user_ez_import_id ON cjwnl_user USING btree (import_id);
CREATE INDEX cjwnl_user_ez_user_id ON cjwnl_user USING btree (ez_user_id);


-- cjw_newsletter 4.2.0

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

CREATE SEQUENCE IF NOT EXISTS cjwnl_segment_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 9223372036854775807
  MINVALUE 1
  CACHE 1;
CREATE TABLE IF NOT EXISTS cjwnl_segment (
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
