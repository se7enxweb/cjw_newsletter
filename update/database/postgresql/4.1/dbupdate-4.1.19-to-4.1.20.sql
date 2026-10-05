-- cjw_newsletter 4.1.19 -> 4.1.20, PostgreSQL
--
-- sql/postgresql/schema.sql lacked columns that the MySQL schema and share/db_schema.dba have had for several
-- releases; a PostgreSQL installation made from that file fails on the first send, virtual list or custom user
-- field. This adds every missing column where it is absent (ADD COLUMN IF NOT EXISTS, PostgreSQL 9.6 and later),
-- so it is safe on an installation that already added some of them by hand. MySQL and SQLite need nothing.

ALTER TABLE cjwnl_edition_send ADD COLUMN IF NOT EXISTS list_contentobject_version integer NOT NULL DEFAULT 0;
ALTER TABLE cjwnl_edition_send ADD COLUMN IF NOT EXISTS list_is_virtual smallint NOT NULL DEFAULT 0::smallint;
ALTER TABLE cjwnl_edition_send ADD COLUMN IF NOT EXISTS mailqueue_process_scheduled integer DEFAULT NULL;
ALTER TABLE cjwnl_edition_send ADD COLUMN IF NOT EXISTS email_reply_to character varying(255) NOT NULL DEFAULT ''::character varying;
ALTER TABLE cjwnl_edition_send ADD COLUMN IF NOT EXISTS email_return_path character varying(255) NOT NULL DEFAULT ''::character varying;

ALTER TABLE cjwnl_list ADD COLUMN IF NOT EXISTS email_reply_to character varying(255) NOT NULL DEFAULT ''::character varying;
ALTER TABLE cjwnl_list ADD COLUMN IF NOT EXISTS email_return_path character varying(255) NOT NULL DEFAULT ''::character varying;
ALTER TABLE cjwnl_list ADD COLUMN IF NOT EXISTS is_virtual smallint NOT NULL DEFAULT 0::smallint;
ALTER TABLE cjwnl_list ADD COLUMN IF NOT EXISTS virtual_filter text NOT NULL DEFAULT ''::text;

ALTER TABLE cjwnl_user ADD COLUMN IF NOT EXISTS custom_data_text_1 character varying(255) NOT NULL DEFAULT ''::character varying;
ALTER TABLE cjwnl_user ADD COLUMN IF NOT EXISTS custom_data_text_2 character varying(255) NOT NULL DEFAULT ''::character varying;
ALTER TABLE cjwnl_user ADD COLUMN IF NOT EXISTS custom_data_text_3 character varying(255) NOT NULL DEFAULT ''::character varying;
ALTER TABLE cjwnl_user ADD COLUMN IF NOT EXISTS custom_data_text_4 character varying(255) NOT NULL DEFAULT ''::character varying;
