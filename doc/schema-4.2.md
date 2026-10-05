# cjw_newsletter 4.2.0: the schema change

Every table and column 4.2.0 adds, in one change for all feature areas. The source of truth is `share/db_schema.dba`;
`sql/{mysql,postgresql,sqlite}/schema.sql` are made from it, and an installation of 4.1.20 is brought to 4.2.0 with
`update/database/<engine>/4.2/dbupdate-4.1.20-to-4.2.0.sql` (run once; a second run fails). The PostgreSQL upgrade also
renames the sequences of the 4.1 tables from `<table>_s` to `<table>_id_seq`, the name the database driver reads.

Each table belongs to one feature area; its class is in `classes/<area folder>/`, data only (definition and fetch
helpers). New columns of 4.1 tables are in the definitions of their 4.1 classes. Timestamps are Unix times; lists of
ids or names in one column use cjw's `;a;b;` form. Every new column has a default, so the upgrade works on tables with rows.

| Area | Folder | Tables and columns |
|---|---|---|
| N1 Deliverability | `classes/deliverability/` | `cjwnl_throttle_state`, `cjwnl_send_batch`, `cjwnl_mailin_address`, `cjwnl_mailin_message`, `cjwnl_test_group`, `cjwnl_edition_send.test_group_id`, `cjwnl_edition_send.throttle_transport`, `cjwnl_edition_send_item.retry_count`, `cjwnl_edition_send_item.next_retry`, `cjwnl_edition_send_item.batch_id`, `cjwnl_user.soft_bounce_count`, `cjwnl_user.last_bounce` |
| N2 Editorial | `classes/editorial/` | `cjwnl_schedule`, `cjwnl_schedule_log`, `cjwnl_article_pool`, `cjwnl_edition_article`, `cjwnl_approval`, `cjwnl_edition_send.schedule_id`, `cjwnl_list.approval_required`, `cjwnl_list.article_pool_id` |
| N3 Rendering | `classes/rendering/` | `cjwnl_interest`, `cjwnl_user_interest`, `cjwnl_edition_send_output`, `cjwnl_edition_send.skin_name`, `cjwnl_edition_send_item.language`, `cjwnl_list.skin_name_array_string`, `cjwnl_list.main_language`, `cjwnl_list.language_array_string`, `cjwnl_list.interest_source`, `cjwnl_user.language` |
| N4 Statistics | `classes/statistics/` | `cjwnl_link`, `cjwnl_link_click`, `cjwnl_open`, `cjwnl_stat_total`, `cjwnl_ab_test`, `cjwnl_ab_variant`, `cjwnl_edition_send.tracking_mode`, `cjwnl_edition_send.ab_test_id`, `cjwnl_edition_send_item.ab_variant_id`, `cjwnl_edition_send_item.first_opened`, `cjwnl_edition_send_item.open_count`, `cjwnl_edition_send_item.click_count`, `cjwnl_list.tracking_mode` |
| N5 SMS | `classes/sms/` | `cjwnl_sms_code`, `cjwnl_sms_message`, `cjwnl_sms_inbound`, `cjwnl_edition_send.channel`, `cjwnl_list.sms_enabled`, `cjwnl_list.sms_sender`, `cjwnl_user.phone_number`, `cjwnl_user.phone_status`, `cjwnl_user.phone_confirmed` |
| N6 Import/export and migration | `classes/importexport/` | `cjwnl_import_mapping`, `cjwnl_migration_log`, `cjwnl_import.mapping_id`, `cjwnl_import.is_dry_run`, `cjwnl_import.consent_source`, `cjwnl_import.status`, `cjwnl_import.skipped_count`, `cjwnl_import.error_count` |

## New columns of 4.1 tables

### cjwnl_edition_send

| Column | Type | Area | Meaning |
|---|---|---|---|
| `skin_name` | varchar(255), default '' | N3 | Skin chosen for this send; empty = the skin of the list. |
| `channel` | varchar(20), default `email` | N5 | `email` or `sms`. |
| `schedule_id` | int, default `0` | N2 | `cjwnl_schedule.id` that made the send; 0 = sent by hand. |
| `tracking_mode` | tinyint, default `0` | N4 | Copied from the list when the send is made: 0 no tracking, 1 anonymous totals only, 2 per person for recipients who agreed (category `newsletter_statistics`). |
| `ab_test_id` | int, default `0` | N4 | `cjwnl_ab_test.id`; 0 = no A/B test. |
| `test_group_id` | int, default `0` | N1 | `cjwnl_test_group.id` of a test send; 0 = a real send. |
| `throttle_transport` | varchar(50), default '' | N1 | Transport whose rate limit applies (`cjwnl_throttle_state.transport`); empty = the default transport. |

### cjwnl_edition_send_item

| Column | Type | Area | Meaning |
|---|---|---|---|
| `retry_count` | tinyint, default `0` | N1 | Soft-bounce retries made so far (at most `[DeliverabilitySettings] SoftBounceMaxRetries`). |
| `next_retry` | int, default `0` | N1 | Not to be sent again before this time; 0 = no retry pending. |
| `batch_id` | int, default `0` | N1 | `cjwnl_send_batch.id` that sent the item. |
| `language` | varchar(20), default '' | N3 | Locale the item was rendered in; empty = the main language of the list. |
| `ab_variant_id` | int, default `0` | N4 | `cjwnl_ab_variant.id` whose subject the item got; 0 = none. |
| `first_opened` | int, default `0` | N4 | First open (per person, only with consent; cleared after the retention period). |
| `open_count` | int, default `0` | N4 | Opens (per person, only with consent). |
| `click_count` | int, default `0` | N4 | Clicks (per person, only with consent). |

### cjwnl_list

| Column | Type | Area | Meaning |
|---|---|---|---|
| `skin_name_array_string` | varchar(255), default '' | N3 | Skins allowed for sends of the list, as `;default;company;`; empty = every skin. |
| `main_language` | varchar(20), default '' | N3 | Locale a subscriber gets when the edition has no translation in their language; empty = the siteaccess locale. |
| `language_array_string` | varchar(255), default '' | N3 | Locales offered to subscribers, as `;eng-GB;ger-DE;`. |
| `interest_source` | varchar(20), default '' | N3 | Where interests come from: empty (none), `topics` (`cjwnl_interest` of the list) or `eztags`. |
| `approval_required` | tinyint (0/1), default `0` | N2 | 1 = a send waits for an approval (`cjwnl_approval`). |
| `article_pool_id` | int, default `0` | N2 | `cjwnl_article_pool.id` of the list; 0 = the global default pool. |
| `tracking_mode` | tinyint, default `0` | N4 | Default tracking of the sends: 0 off (the default), 1 anonymous totals, 2 per person with consent. |
| `sms_enabled` | tinyint (0/1), default `0` | N5 | 1 = SMS editions may be sent to the list. |
| `sms_sender` | varchar(50), default '' | N5 | Sender id or number of the SMS; empty = that of the transport. |

### cjwnl_user

| Column | Type | Area | Meaning |
|---|---|---|---|
| `language` | varchar(20), default '' | N3 | Preferred locale; empty = the main language of the list. |
| `soft_bounce_count` | tinyint, default `0` | N1 | Soft bounces since the last delivered mail. |
| `last_bounce` | int, default `0` | N1 | Time of the last bounce. |
| `phone_number` | varchar(50), default '' | N5 | Mobile number in E.164 form; empty = none. |
| `phone_status` | tinyint, default `0` | N5 | 0 none, 1 code sent and pending, 2 confirmed, 3 stopped (STOP keyword). |
| `phone_confirmed` | int, default `0` | N5 | When the number was confirmed with the code. |

### cjwnl_import

| Column | Type | Area | Meaning |
|---|---|---|---|
| `mapping_id` | int, default `0` | N6 | `cjwnl_import_mapping.id` used; 0 = the fixed columns of 4.1. |
| `is_dry_run` | tinyint (0/1), default `0` | N6 | 1 = counted and checked only, nothing written. |
| `consent_source` | varchar(100), default '' | N6 | Source written to the consent log for the imported subscriptions. |
| `status` | tinyint, default `0` | N6 | 0 new, 1 previewed, 2 running, 3 done, 9 failed. |
| `skipped_count` | int, default `0` | N6 | Rows skipped (existing, suppressed, invalid). |
| `error_count` | int, default `0` | N6 | Rows that failed. |

## New tables

### cjwnl_throttle_state

Area N1 Deliverability, class `CjwNewsletterThrottleState` (`classes/deliverability/cjwnewsletterthrottlestate.php`). The rate of one transport in the current window. The queue run reads and raises `sent_count` and stops (to be resumed by the next run) when `[ThrottleSettings] MaxPerMinute[]` / `MaxPerHour[]` is reached. One row per transport and window type.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `transport` | varchar(50), default '' | Transport name (`smtp`, `sendmail`, `file`, `sms`, or the name of an SMS transport). |
| `window_type` | varchar(10), default `minute` | `minute` or `hour`. |
| `window_start` | int, default `0` | Start of the current window. |
| `sent_count` | int, default `0` | Mails sent in the current window. |
| `paused_until` | int, default `0` | No sending before this time (a pause set by an admin or after an error); 0 = not paused. |
| `modified` | int, default `0` | When the row was last changed (Unix time). |

Indexes: `cjwnl_throttle_state_window` (unique, transport, window_type).

### cjwnl_send_batch

Area N1 Deliverability, class `CjwNewsletterSendBatch` (`classes/deliverability/cjwnewslettersendbatch.php`). A batch of a send. `status`: 0 new, 1 running, 2 done, 3 paused (rate limit), 9 failed. A stopped run resumes after `last_item_id`.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `edition_send_id` | int, default `0` | `cjwnl_edition_send.id`. |
| `channel` | varchar(20), default `email` | `email` or `sms`. |
| `batch_number` | int, default `0` | Number of the batch within the send, from 1. |
| `item_count` | int, default `0` | Items the batch took. |
| `sent_count` | int, default `0` | Items sent. |
| `failed_count` | int, default `0` | Items that failed. |
| `last_item_id` | int, default `0` | Last item handled (resume point). |
| `status` | tinyint, default `0` | State, see the table description. |
| `created` | int, default `0` | When the row was made (Unix time). |
| `started` | int, default `0` | When the batch started. |
| `finished` | int, default `0` | When the batch finished. |

Indexes: `cjwnl_send_batch_send` (edition_send_id, status).

### cjwnl_mailin_address

Area N1 Deliverability, class `CjwNewsletterMailinAddress` (`classes/deliverability/cjwnewslettermailinaddress.php`). An address that takes subscribe/unsubscribe mails for a list. The kernel IMAP/POP3 reader reads the mailbox together with the bounces and routes by recipient address; a subscribe only starts the double opt-in.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `list_contentobject_id` | int, default `0` | Content object id of the newsletter list (0 = every list, or the global default). |
| `email` | varchar(255), default '' | The address (lower case). |
| `plus_tag` | varchar(100), default '' | Tag of a plus-address (`news+<tag>@...`); empty = the address itself. |
| `action` | varchar(20), default `both` | `subscribe`, `unsubscribe` or `both` (the keyword in the subject decides). |
| `mailbox_id` | int, default `0` | `cjwnl_mailbox.id` that receives it; 0 = the kernel bounce mailbox. |
| `is_active` | tinyint (0/1), default `1` | 1 = mails are processed. |
| `created` | int, default `0` | When the row was made (Unix time). |
| `modified` | int, default `0` | When the row was last changed (Unix time). |

Indexes: `cjwnl_mailin_address_email` (unique, email, plus_tag); `cjwnl_mailin_address_list` (list_contentobject_id).

### cjwnl_mailin_message

Area N1 Deliverability, class `CjwNewsletterMailinMessage` (`classes/deliverability/cjwnewslettermailinmessage.php`). `status`: 0 new, 1 pending confirmation, 2 done, 9 rejected. The sender address is kept only until the message is processed and is erased with the user.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `mailin_address_id` | int, default `0` | `cjwnl_mailin_address.id`. |
| `message_identifier` | varchar(255), default '' | Message-Id of the mail (a mail is never processed twice). |
| `email_from` | varchar(255), default '' | Sender address. |
| `action` | varchar(20), default '' | `subscribe` or `unsubscribe` as understood. |
| `status` | tinyint, default `0` | State, see the table description. |
| `pending_token` | varchar(64), default '' | Id of the kernel pending confirmation (`expmail_pending`) it started, if any. |
| `newsletter_user_id` | int, default `0` | `cjwnl_user.id` found or created; 0 = none. |
| `note` | text, null | Why it was rejected, or what was done. |
| `created` | int, default `0` | When the row was made (Unix time). |
| `processed` | int, default `0` | When it was processed. |

Indexes: `cjwnl_mailin_message_ident` (message_identifier); `cjwnl_mailin_message_status` (status, created).

### cjwnl_test_group

Area N1 Deliverability, class `CjwNewsletterTestGroup` (`classes/deliverability/cjwnewslettertestgroup.php`). Test addresses for test sends (at most `[TestSendSettings] MaxTestGroupSize`). Test sends are never gated and never counted in the statistics.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `list_contentobject_id` | int, default `0` | Content object id of the newsletter list (0 = every list, or the global default). |
| `name` | varchar(255), default '' | Name shown in the send form. |
| `email_list` | text, null | Addresses, one per line. |
| `creator_contentobject_id` | int, default `0` | Content object id of the user who made it. |
| `created` | int, default `0` | When the row was made (Unix time). |
| `modified` | int, default `0` | When the row was last changed (Unix time). |

Indexes: `cjwnl_test_group_list` (list_contentobject_id).

### cjwnl_schedule

Area N2 Editorial, class `CjwNewsletterSchedule` (`classes/editorial/cjwnewsletterschedule.php`). A recurring send. `status`: 0 active, 1 paused, 9 removed. The queue-create part and `ext:cjw_newsletter:schedule run` take the schedules whose `next_run` has come.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `list_contentobject_id` | int, default `0` | Content object id of the newsletter list (0 = every list, or the global default). |
| `mode` | varchar(20), default `latest` | `copy` (copy the template edition and send the copy) or `latest` (send the latest unsent edition of the list). |
| `template_edition_contentobject_id` | int, default `0` | Edition copied in mode `copy`. |
| `recurrence_type` | varchar(1), default `w` | `d` chosen weekdays, `w` weekly, `m` monthly. |
| `recurrence_value` | varchar(255), default '' | Weekdays `1,3,5` (ISO, 1 = Monday) for `d`; weekday for `w`; day of the month for `m` (a day beyond the month means its last day). |
| `send_time` | int, default `0` | Time of day, seconds after midnight in `timezone`. |
| `timezone` | varchar(64), default '' | Time zone name; empty = `[ScheduleSettings] DefaultTimezone` or the site's. |
| `auto_fill` | tinyint (0/1), default `0` | 1 = the copy is filled from the pool with content published since the last send. |
| `article_pool_id` | int, default `0` | Pool of the auto-fill; 0 = the pool of the list. |
| `skip_if_empty` | tinyint (0/1), default `1` | 1 = no send (logged as skipped) when the auto-fill finds nothing. |
| `condition_handler` | varchar(255), default '' | Class asked before each run (`[ScheduleSettings] ConditionHandlers[]`); empty = none. |
| `next_run` | int, default `0` | Next run. |
| `last_run` | int, default `0` | Last run. |
| `last_edition_send_id` | int, default `0` | Send made by the last run. |
| `last_result` | varchar(50), default '' | Result of the last run (as `cjwnl_schedule_log.result`). |
| `status` | tinyint, default `0` | State, see the table description. |
| `creator_contentobject_id` | int, default `0` | Content object id of the user who made it. |
| `created` | int, default `0` | When the row was made (Unix time). |
| `modified` | int, default `0` | When the row was last changed (Unix time). |

Indexes: `cjwnl_schedule_due` (status, next_run); `cjwnl_schedule_list` (list_contentobject_id).

### cjwnl_schedule_log

Area N2 Editorial, class `CjwNewsletterScheduleLog` (`classes/editorial/cjwnewsletterschedulelog.php`). One run of a schedule.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `schedule_id` | int, default `0` | `cjwnl_schedule.id`. |
| `run_at` | int, default `0` | When it ran. |
| `result` | varchar(20), default '' | `sent`, `skipped_empty`, `skipped_condition`, `skipped_dry_run` or `failed`. |
| `edition_contentobject_id` | int, default `0` | Edition sent or made. |
| `edition_send_id` | int, default `0` | `cjwnl_edition_send.id`. |
| `article_count` | int, default `0` | Articles the auto-fill added. |
| `message` | text, null | Details (the error of a failed run). |

Indexes: `cjwnl_schedule_log_schedule` (schedule_id, run_at).

### cjwnl_article_pool

Area N2 Editorial, class `CjwNewsletterArticlePool` (`classes/editorial/cjwnewsletterarticlepool.php`). Where the articles for editions come from. All filters are applied with `fetch( content, list )` filters, never in hand-made SQL. A list without a pool uses the row with `list_contentobject_id` 0 and `is_default` 1, else `[ArticlePoolSettings]`.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `list_contentobject_id` | int, default `0` | Content object id of the newsletter list (0 = every list, or the global default). |
| `name` | varchar(255), default '' | Name shown to editors. |
| `is_default` | tinyint (0/1), default `0` | 1 = the global default pool. |
| `parent_node_id_array_string` | varchar(255), default '' | Nodes searched, as `;2;45;`. |
| `class_identifier_array_string` | varchar(255), default '' | Classes, as `;article;cjw_newsletter_article;`. |
| `section_id_array_string` | varchar(255), default '' | Sections, as `;1;3;`; empty = any. |
| `tag_id_array_string` | varchar(255), default '' | eztags tag ids, as `;12;14;`; empty = any. |
| `state_id_array_string` | varchar(255), default '' | Object state ids, as `;1;`; empty = any. |
| `max_age_days` | int, default `0` | Only content published in the last n days; 0 = any age. |
| `max_items` | int, default `10` | Most articles the auto-fill takes. |
| `sort_by` | varchar(50), default `published` | `published`, `modified`, `priority` or `name`. |
| `filter_data` | text, null | Further filters (JSON), read and cast in PHP. |
| `creator_contentobject_id` | int, default `0` | Content object id of the user who made it. |
| `created` | int, default `0` | When the row was made (Unix time). |
| `modified` | int, default `0` | When the row was last changed (Unix time). |

Indexes: `cjwnl_article_pool_list` (list_contentobject_id).

### cjwnl_edition_article

Area N2 Editorial, class `CjwNewsletterEditionArticle` (`classes/editorial/cjwnewslettereditionarticle.php`). An article taken into an edition from a pool (an editor's pick or the auto-fill). Also the source of "which editions carried this article" in the article statistics.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `edition_contentobject_id` | int, default `0` | Content object id of the edition. |
| `contentobject_id` | int, default `0` | Content object id of the article. |
| `article_pool_id` | int, default `0` | Pool it came from; 0 = picked without a pool. |
| `position` | int, default `0` | Order in the edition. |
| `added_by` | tinyint, default `0` | 0 an editor, 1 the auto-fill, 2 the interests block. |
| `creator_contentobject_id` | int, default `0` | Content object id of the user who made it. |
| `created` | int, default `0` | When the row was made (Unix time). |

Indexes: `cjwnl_edition_article_edition` (unique, edition_contentobject_id, contentobject_id); `cjwnl_edition_article_object` (contentobject_id).

### cjwnl_approval

Area N2 Editorial, class `CjwNewsletterApproval` (`classes/editorial/cjwnewsletterapproval.php`). `status`: 0 pending, 1 approved, 2 rejected, 3 withdrawn (a new version replaces it). A list with `approval_required` cannot be sent until the current version is approved.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `edition_contentobject_id` | int, default `0` | Content object id of the edition. |
| `edition_contentobject_version` | int, default `0` | Version approved. |
| `list_contentobject_id` | int, default `0` | Content object id of the newsletter list (0 = every list, or the global default). |
| `collaboration_item_id` | int, default `0` | `ezcollaboration_item.id` in the approver's inbox. |
| `status` | tinyint, default `0` | State, see the table description. |
| `requested_by` | int, default `0` | User id who asked for the approval. |
| `requested` | int, default `0` | When. |
| `decided_by` | int, default `0` | User id who decided. |
| `decided` | int, default `0` | When. |
| `comment` | text, null | The approver's comment. |

Indexes: `cjwnl_approval_edition` (edition_contentobject_id, edition_contentobject_version); `cjwnl_approval_collaboration` (collaboration_item_id).

### cjwnl_interest

Area N3 Rendering, class `CjwNewsletterInterest` (`classes/rendering/cjwnewsletterinterest.php`). An interest subscribers can pick on the central preference page; the "articles for your interests" block fills per person from the pool.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `list_contentobject_id` | int, default `0` | Content object id of the newsletter list (0 = every list, or the global default). |
| `identifier` | varchar(100), default '' | Identifier, unique per list. |
| `name` | varchar(255), default '' | Name shown to subscribers. |
| `source` | varchar(20), default `topic` | `topic` or `eztags`. |
| `eztags_id` | int, default `0` | eztags tag id for `eztags`; 0 otherwise. |
| `priority` | int, default `0` | Order on the page. |
| `is_active` | tinyint (0/1), default `1` | 1 = offered. |
| `created` | int, default `0` | When the row was made (Unix time). |
| `modified` | int, default `0` | When the row was last changed (Unix time). |

Indexes: `cjwnl_interest_identifier` (unique, list_contentobject_id, identifier).

### cjwnl_user_interest

Area N3 Rendering, class `CjwNewsletterUserInterest` (`classes/rendering/cjwnewsletteruserinterest.php`). An interest a user picked. Removed with the user.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `newsletter_user_id` | int, default `0` | `cjwnl_user.id`. |
| `interest_id` | int, default `0` | `cjwnl_interest.id`. |
| `created` | int, default `0` | When the row was made (Unix time). |

Indexes: `cjwnl_user_interest_pair` (unique, newsletter_user_id, interest_id); `cjwnl_user_interest_interest` (interest_id).

### cjwnl_edition_send_output

Area N3 Rendering, class `CjwNewsletterEditionSendOutput` (`classes/rendering/cjwnewslettereditionsendoutput.php`). The rendered output of a send in one more language. The main language stays in `cjwnl_edition_send.output_xml`.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `edition_send_id` | int, default `0` | `cjwnl_edition_send.id`. |
| `language` | varchar(20), default '' | Locale. |
| `output_xml` | longtext, null | Output XML as in `cjwnl_edition_send.output_xml`. |
| `created` | int, default `0` | When the row was made (Unix time). |

Indexes: `cjwnl_edition_send_output_lang` (unique, edition_send_id, language).

### cjwnl_link

Area N4 Statistics, class `CjwNewsletterLink` (`classes/statistics/cjwnewsletterlink.php`). A link of a send, rewritten to the click redirect when the queue is made. The redirect only goes to URLs stored here (no open redirect); `mailto:`, anchors and the gate's links are never rewritten.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `edition_send_id` | int, default `0` | `cjwnl_edition_send.id`. |
| `url_hash` | varchar(64), default '' | sha256 of the URL (unique per send). |
| `url` | text, null | The URL. |
| `contentobject_id` | int, default `0` | Article the link points to, if any (article statistics). |
| `position` | int, default `0` | Order in the mail. |
| `click_count` | int, default `0` | All clicks (anonymous total). |
| `created` | int, default `0` | When the row was made (Unix time). |

Indexes: `cjwnl_link_send_url` (unique, edition_send_id, url_hash); `cjwnl_link_object` (contentobject_id).

### cjwnl_link_click

Area N4 Statistics, class `CjwNewsletterLinkClick` (`classes/statistics/cjwnewsletterlinkclick.php`). One click. Per person only for recipients who agreed; removed after `[TrackingSettings] PersonRetentionMonths` and at once on withdrawal or erasure, the totals stay in `cjwnl_stat_total`.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `link_id` | int, default `0` | `cjwnl_link.id`. |
| `edition_send_item_id` | int, default `0` | `cjwnl_edition_send_item.id`; 0 when it is not counted per person. |
| `created` | int, default `0` | When the row was made (Unix time). |

Indexes: `cjwnl_link_click_link` (link_id); `cjwnl_link_click_item` (edition_send_item_id); `cjwnl_link_click_created` (created).

### cjwnl_open

Area N4 Statistics, class `CjwNewsletterOpen` (`classes/statistics/cjwnewsletteropen.php`). One open (pixel). Same retention as the clicks.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `edition_send_id` | int, default `0` | `cjwnl_edition_send.id`. |
| `edition_send_item_id` | int, default `0` | `cjwnl_edition_send_item.id`; 0 when it is not counted per person. |
| `created` | int, default `0` | When the row was made (Unix time). |

Indexes: `cjwnl_open_send` (edition_send_id); `cjwnl_open_item` (edition_send_item_id); `cjwnl_open_created` (created).

### cjwnl_stat_total

Area N4 Statistics, class `CjwNewsletterStatTotal` (`classes/statistics/cjwnewsletterstattotal.php`). Anonymous totals per send, link, type and day; what the reports, the dashboard trends and the CSV export read.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `edition_send_id` | int, default `0` | `cjwnl_edition_send.id`. |
| `link_id` | int, default `0` | `cjwnl_link.id`; 0 for opens and send-level counts. |
| `stat_type` | varchar(20), default '' | `sent`, `open`, `unique_open`, `click`, `unique_click`, `bounce`, `unsubscribe`, `blocked`. |
| `stat_day` | int, default `0` | Day as yyyymmdd. |
| `total` | int, default `0` | Count. |

Indexes: `cjwnl_stat_total_key` (unique, edition_send_id, link_id, stat_type, stat_day); `cjwnl_stat_total_day` (stat_type, stat_day).

### cjwnl_ab_test

Area N4 Statistics, class `CjwNewsletterAbTest` (`classes/statistics/cjwnewsletterabtest.php`). `status`: 0 sampling, 1 waiting, 2 winner chosen, 3 done, 9 cancelled. Defaults from `[ABTestSettings]`: two samples of 10 %, winner by open rate (clicks when only anonymous totals exist) after 4 hours.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `edition_send_id` | int, default `0` | `cjwnl_edition_send.id`. |
| `status` | tinyint, default `0` | State, see the table description. |
| `sample_percent` | int, default `10` | Share of the recipients per variant. |
| `variant_count` | int, default `2` | Number of variants. |
| `criterion` | varchar(20), default `open` | `open` or `click`. |
| `wait_seconds` | int, default `14400` | Wait between the samples and the choice. |
| `winner_variant_id` | int, default `0` | `cjwnl_ab_variant.id` that won; 0 = not yet. |
| `samples_sent` | int, default `0` | When the samples were sent. |
| `decided` | int, default `0` | When the winner was chosen. |
| `created` | int, default `0` | When the row was made (Unix time). |
| `modified` | int, default `0` | When the row was last changed (Unix time). |

Indexes: `cjwnl_ab_test_send` (unique, edition_send_id); `cjwnl_ab_test_status` (status).

### cjwnl_ab_variant

Area N4 Statistics, class `CjwNewsletterAbVariant` (`classes/statistics/cjwnewsletterabvariant.php`). One subject variant.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `ab_test_id` | int, default `0` | `cjwnl_ab_test.id`. |
| `variant_key` | varchar(10), default '' | `A`, `B`, ... |
| `subject` | varchar(255), default '' | Subject of the variant. |
| `item_count` | int, default `0` | Items sent with it. |
| `open_count` | int, default `0` | Opens (unique). |
| `click_count` | int, default `0` | Clicks (unique). |
| `created` | int, default `0` | When the row was made (Unix time). |

Indexes: `cjwnl_ab_variant_test` (unique, ab_test_id, variant_key).

### cjwnl_sms_code

Area N5 SMS, class `CjwNewsletterSmsCode` (`classes/sms/cjwnewslettersmscode.php`). A confirmation code sent by SMS. Only its hash is stored.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `newsletter_user_id` | int, default `0` | `cjwnl_user.id`. |
| `phone_number` | varchar(50), default '' | Number the code was sent to. |
| `code_hash` | varchar(64), default '' | sha256 of the code with the site secret. |
| `purpose` | varchar(20), default `confirm` | `confirm`. |
| `attempts` | tinyint, default `0` | Wrong entries so far (at most `[SmsSettings] CodeMaxAttempts`). |
| `expires` | int, default `0` | Valid until. |
| `used` | int, default `0` | When it was used; 0 = not yet. |
| `created` | int, default `0` | When the row was made (Unix time). |

Indexes: `cjwnl_sms_code_user` (newsletter_user_id, purpose).

### cjwnl_sms_message

Area N5 SMS, class `CjwNewsletterSmsMessage` (`classes/sms/cjwnewslettersmsmessage.php`). One SMS of an SMS edition. `status`: 0 new, 1 sent, 2 failed, 9 aborted.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `edition_send_id` | int, default `0` | `cjwnl_edition_send.id`. |
| `newsletter_user_id` | int, default `0` | `cjwnl_user.id`. |
| `phone_number` | varchar(50), default '' | Number sent to. |
| `body` | text, null | Text sent. |
| `status` | tinyint, default `0` | State, see the table description. |
| `transport` | varchar(50), default '' | SMS transport used. |
| `provider_message_id` | varchar(255), default '' | Id the provider returned. |
| `error` | text, null | Error of a failed send. |
| `batch_id` | int, default `0` | `cjwnl_send_batch.id`. |
| `created` | int, default `0` | When the row was made (Unix time). |
| `processed` | int, default `0` | When it was sent or failed. |

Indexes: `cjwnl_sms_message_send` (edition_send_id, status); `cjwnl_sms_message_user` (newsletter_user_id).

### cjwnl_sms_inbound

Area N5 SMS, class `CjwNewsletterSmsInbound` (`classes/sms/cjwnewslettersmsinbound.php`). An SMS that came in (from the provider callback).

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `phone_number` | varchar(50), default '' | Sender number. |
| `keyword` | varchar(50), default '' | First word, upper case (`STOP`, ...). |
| `body` | text, null | Full text. |
| `newsletter_user_id` | int, default `0` | User found by the number; 0 = none. |
| `action` | varchar(20), default '' | What was done: `stop`, `ignored`. |
| `provider_message_id` | varchar(255), default '' | Id of the provider. |
| `created` | int, default `0` | When the row was made (Unix time). |
| `processed` | int, default `0` | When it was processed. |

Indexes: `cjwnl_sms_inbound_phone` (phone_number).

### cjwnl_import_mapping

Area N6 Import/export and migration, class `CjwNewsletterImportMapping` (`classes/importexport/cjwnewsletterimportmapping.php`). A stored column mapping for CSV imports.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `name` | varchar(255), default '' | Name shown in the import form. |
| `list_contentobject_id` | int, default `0` | Content object id of the newsletter list (0 = every list, or the global default). |
| `mapping` | text, null | JSON: column index or header => field (`[ImportMappingSettings] MappableFields[]`) or `ignore`. |
| `delimiter` | varchar(5), default `;` | Field delimiter. |
| `has_header` | tinyint (0/1), default `1` | 1 = the first row holds the column names. |
| `encoding` | varchar(20), default `UTF-8` | Encoding of the file. |
| `consent_source` | varchar(100), default '' | Source written to the consent log. |
| `creator_contentobject_id` | int, default `0` | Content object id of the user who made it. |
| `created` | int, default `0` | When the row was made (Unix time). |
| `modified` | int, default `0` | When the row was last changed (Unix time). |

Indexes: `cjwnl_import_mapping_list` (list_contentobject_id).

### cjwnl_migration_log

Area N6 Import/export and migration, class `CjwNewsletterMigrationLog` (`classes/importexport/cjwnewslettermigrationlog.php`). One row of the old eznewsletter tables that `ext:cjw_newsletter:import-eznewsletter` read. The old tables are never changed.

| Column | Type | Meaning |
|---|---|---|
| `id` | int, auto increment | Key (auto increment). |
| `run_id` | varchar(40), default '' | Id of the run. |
| `source_table` | varchar(100), default '' | Old table (`ezsubscription`, `ezsubscriptionuserdata`, ...). |
| `source_id` | varchar(100), default '' | Key of the old row. |
| `target_table` | varchar(100), default '' | cjwnl table written. |
| `target_id` | int, default `0` | Id written; 0 = none. |
| `action` | varchar(20), default '' | `created`, `merged`, `skipped` or `failed`. |
| `is_dry_run` | tinyint (0/1), default `0` | 1 = a dry run, nothing was written. |
| `message` | text, null | Details. |
| `created` | int, default `0` | When the row was made (Unix time). |

Indexes: `cjwnl_migration_log_run` (run_id); `cjwnl_migration_log_source` (source_table, source_id).
