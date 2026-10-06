# Upgrade from cjw_newsletter 4.1 to 4.2

4.2.0 adds the features of six areas: [deliverability](deliverability.md), [editorial](editorial.md),
[rendering](rendering.md), [statistics](statistics.md), [SMS](sms.md) and [import, export and
migration](importexport.md). This page lists what an installation of 4.1.x has to do. Read it once to the end
before you start; the steps are short.

## 1. Requirements

- **Exponential 6.0.15 or later.** 4.2.0 works through the e-mail preferences of the kernel and needs these parts
  of it:
  - the mail gate (`expMailGate`), the consent log (`expConsentLog`) and the suppression list (`expMailSuppression`);
  - the bounce reader with `mailpreferences.ini [BounceSettings] MessageListeners[]` (bounces and subscribe/unsubscribe
    mails of the newsletter read from the kernel's bounce mailbox);
  - the category handlers with `erased()` (the person's rows of the newsletter go when he is erased) and the
    category part hook (`partTemplate()`, `partVariables()`, `storePart()`), which puts the newsletter's options into
    the rows of the central preference page;
  - the collaboration inbox (`expCollaborationInbox`) for the approval of editions; from 6.0.15 it asks the
    newsletter's handler for the state of an approval (waiting, approved, denied).

  On an older kernel the extension still sends editions as in 4.1, but the features that need these parts stay off.
- **PHP 8.0 to 8.5.**
- **cjw_newsletter 4.1.20.** The database update starts from the 4.1.20 schema. On an older 4.1.x, run the updates
  of the releases in between first (`update/database/<engine>/4.1/`; for PostgreSQL
  `dbupdate-4.1.19-to-4.1.20.sql` adds the columns the 4.1 PostgreSQL schema lacked).

## 2. Back up the database

The update adds tables and columns and changes no data, but it cannot be undone without a backup.

## 3. The database update

One file per database engine. Run it **once**: it has no `IF NOT EXISTS` and fails on a database that has the
4.2.0 tables already.

| Engine | File |
|---|---|
| MySQL / MariaDB | `update/database/mysql/4.2/dbupdate-4.1.20-to-4.2.0.sql` |
| PostgreSQL | `update/database/postgresql/4.2/dbupdate-4.1.20-to-4.2.0.sql` |
| SQLite | `update/database/sqlite/4.2/dbupdate-4.1.20-to-4.2.0.sql` |

```bash
# MySQL / MariaDB
mysql -u <user> -p <database> < extension/cjw_newsletter/update/database/mysql/4.2/dbupdate-4.1.20-to-4.2.0.sql
# PostgreSQL
psql -U <user> -d <database> -f extension/cjw_newsletter/update/database/postgresql/4.2/dbupdate-4.1.20-to-4.2.0.sql
# SQLite (the installation's file, here the default of a new installation)
sqlite3 var/storage/sqlite3/sqlite.db < extension/cjw_newsletter/update/database/sqlite/4.2/dbupdate-4.1.20-to-4.2.0.sql
```

What it does:

- New columns of `cjwnl_edition_send`, `cjwnl_edition_send_item`, `cjwnl_list`, `cjwnl_user` and `cjwnl_import`,
  each with a default, so tables with rows take them.
- 24 new tables, `cjwnl_throttle_state` to `cjwnl_migration_log`. The [schema page](schema-4.2.md) lists each with its
  columns and meaning.
- **PostgreSQL only: the sequences are renamed.** The 4.1 schema named the sequences of its tables `<table>_s`; the
  kernel's PostgreSQL driver reads the id of a new row from `<table>_id_seq`, so an insert could not return its id.
  The update renames the eight sequences (`cjwnl_blacklist_item_s` to `cjwnl_blacklist_item_id_seq`, and so on for
  `cjwnl_edition_send`, `cjwnl_edition_send_item`, `cjwnl_import`, `cjwnl_mailbox`, `cjwnl_mailbox_item`,
  `cjwnl_subscription` and `cjwnl_user`) and points the default of each `id` column at the new name. A database
  made from the 4.2.0 `sql/postgresql/schema.sql` has the new names from the start.

A new installation takes `sql/<engine>/schema.sql` or `share/db_schema.dba` instead and needs no update.

## 4. The new files and the caches

1. Put the 4.2.0 files in place (`composer require se7enxweb/cjw_newsletter:~4.2.0`, or the archive of the release).
2. Regenerate the autoloads: `php bin/php/ezpgenerateautoloads.php -e`.
3. Clear the caches: `php bin/php/ezcache.php --clear-all` (INI, templates, template overrides, content: 4.2.0 brings
   new templates, template operators, a notification handler, a collaboration handler and new INI blocks).
4. Reload PHP-FPM. Under Velocity, deploy (`exp:velocity deploy`): its workers keep the classes of their warm-up and
   see the new handler classes only after it.

## 5. The one-time command

The text fields of the newsletter classes must be translatable for an edition with translations (one edition, a
language per subscriber). The class installer of 4.2.0 does this for a new installation; an existing one runs:

```bash
./console ext:cjw_newsletter:translatable-fields --dry-run   # says what it would change
./console ext:cjw_newsletter:translatable-fields
```

It sets the title, short title, short description and description of `cjw_newsletter_edition` and the title and text
of `cjw_newsletter_article` translatable, leaves every other field as it is, and can be run again at any time. Clear
the class and content caches afterwards.

## 6. Defaults: what is on, what is off

| Setting (`cjw_newsletter.ini`) | Default | Meaning |
|---|---|---|
| `[ScheduleSettings] Schedules` | `enabled` | The cron runs the due recurring sends. Nothing runs until an editor creates a recurring send. |
| `[ApprovalSettings] ApproverGroupIds[]` | `12` | The members of group 12 (the Administrator users of a standard installation) get the approval requests. Approval is off per list until the list object says "Approval: yes". |
| `[TrackingSettings] Tracking` | `disabled` | No opens or clicks are counted anywhere. Switch it on in `settings/override/cjw_newsletter.ini.append.php`, then choose a tracking mode per list. |
| `[SmsSettings] Sms` | `disabled` | No SMS. Switch it on in the override, with a transport ([SMS guide](sms.md)). |
| `[ThrottleSettings] Throttle` | `disabled` | No rate limits or batches. A transport paused on the page "Rate limits and batches" still sends nothing. |
| `[MailInSettings] MailIn` | `disabled` | No subscribe or unsubscribe by e-mail. |
| `[DeliverabilitySettings] SuppressHardBounces` | `enabled` | A hard bounce or a complaint puts the address on the kernel's suppression list. |
| `[ConditionTagSettings] ConditionTag`, `[TextViewSettings] PlainTextViews`, `[LanguageSettings] FallbackToListMainLanguage` | `enabled` | The newsletter condition, the plain text views and the fallback to the list's main language. |
| `[NewsletterCsvImportSettings] ImportInBackground` | `enabled` | Imports run as a background job. |

The two new categories of the e-mail preferences are switched on by the extension's own
`settings/mailpreferences.ini.append.php`, both optional and off for every person until he switches them on:
`newsletter_statistics` ("Newsletter statistics", the consent to counting per person) and `sms` ("Newsletters by SMS",
confirmed with a code).

## 7. Policies

The extension's `settings/site.ini.append.php` puts four public views into `[RoleSettings] PolicyOmitList[]`, so
they work for everybody with no role change:

| View | What it is |
|---|---|
| `newsletter/o` | The open pixel (counts only with a valid signature) |
| `newsletter/r` | The click redirect (goes only to the address stored for the link) |
| `newsletter/sms_inbound` | The endpoint the SMS provider calls (checks the provider's signature or secret itself) |
| `newsletter/sms_confirm` | The page for the SMS code (reached only with the subscriber's secret hash) |

A site that takes them out of `PolicyOmitList` grants the functions `newsletter/track` and `newsletter/sms_public` to
the anonymous role instead.

New policy functions for the admin roles:

| Function | Gives |
|---|---|
| `newsletter/deliverability` | Rate limits and batches, test groups, mail-in addresses, the suppression import, the deliverability block of the dashboard |
| `newsletter/editorial` | Recurring sends, article pools, the article picker, asking for an approval |
| `newsletter/approve` | Deciding approval requests |
| `newsletter/rendering` | Interests, the skin preview |
| `newsletter/statistics` | Reports, the A/B page, the statistics export, the statistics block |
| `newsletter/sms` | The SMS send page |
| `newsletter/import_export` | The import with a column mapping, the subscriber export, the migration log |

The Administrator role of a standard installation has `newsletter/*` and needs nothing.

## 8. Cron

The cron parts stay as in 4.1:

```bash
php runcronjobs.php -s <siteaccess> cjw_newsletter              # create and process the queue
php runcronjobs.php -s <siteaccess> cjw_newsletter_mailbox      # the newsletter's own mail accounts
php runcronjobs.php -s <siteaccess> mailbounces                 # the kernel's bounce reader (also mail-in)
```

`cjw_newsletter_mailqueue_create` now also runs the due recurring sends first, and makes the language outputs, the
tracking links and the A/B samples of each send. `cjw_newsletter_mailqueue_process` sends in batches within the rate
limits, sends the SMS queue, moves the A/B tests on and runs the retention cleanup of the statistics once a day.
Run the kernel's `mailbounces` part when the bounce mailbox of the e-mail preferences is set: the newsletter reads
its bounces and its mail-in addresses from it too.

The console commands of 4.2.0 (each with `--help` and `--dry-run`):

```
./console ext:cjw_newsletter:schedule list|run
./console ext:cjw_newsletter:deliverability status|suppression-import|pause|resume|read
./console ext:cjw_newsletter:statistics [--cleanup] [--decide]
./console ext:cjw_newsletter:import-eznewsletter
./console ext:cjw_newsletter:translatable-fields
```

## 9. After the upgrade

- Check the dashboard (`newsletter/index`): it lists problems, such as a consent category that is missing or an A/B
  test that waits too long.
- Set per list what you want: allowed skins, languages, the interest source, approval, the article pool, the tracking
  mode, SMS (all in the list object).
- If you switch tracking on, say in the privacy notice what is counted and for how long
  ([statistics](statistics.md), section 9).
- `./console ext:cjw_newsletter:repair --dry-run` lists orphans; since 4.2.0 also the interests of subscribers that
  older removals left behind.

## 10. Behaviour that changed

- The erasure of a person in the e-mail preferences (`exp:mail:preferences erase`, a "delete my data" request, the
  removal of the account) removes the newsletter user with his subscriptions, interests, SMS codes and SMS, and his
  per-person statistics. The totals stay, and so do the suppression entry and the blacklist entry of the address
  (without the user), so that the address stays blocked.
- Removing a subscriber removes his interests, his SMS codes and SMS and his per-person statistics with him
  (extension point `userRemoved`).
- A `450` reply of the mail server is no longer a hard bounce; it is sent again.
- Placeholders are escaped in the HTML part (since 4.1.20); the new placeholders of 4.2.0 too.

## 11. From 4.2.0 to 4.2.1: the newsletter classes in eng-US

The class packages up to 4.2.0 named the newsletter classes in British English (eng-GB). The kernel creates every
language a class package names, so importing the newsletter classes added eng-GB as a content language to a site
whose language is eng-US, and the newsletter classes had eng-GB as their initial language. The packages of 4.2.1 use
eng-US (the German names stay). An installation that imported the classes before 4.2.1 runs the upgrade step once:

```
./console ext:cjw_newsletter:class-languages --dry-run   # lists the rows it would change
./console ext:cjw_newsletter:class-languages             # or: php extension/cjw_newsletter/bin/php/class-languages.php
php bin/php/ezcache.php --clear-id=content,classid,sortkey,template,template-block,content_language
```

It moves, for every version of the six newsletter classes, the class and attribute names and descriptions from
eng-GB to eng-US (an eng-US text that exists already is kept), makes eng-US the always-available and the initial
language and corrects the language mask and the class name rows. The German texts stay. The rows are saved as JSON
before they change (`var/<site>/cjw_newsletter/class-languages-backup-<time>.json`, or `--backup-dir=<dir>`). A
second run changes nothing. The class installer (`CjwNewsletterClassInstaller::install()`) runs the same step.

Content objects and the eng-GB language itself are not touched: the command says how many newsletter objects still
carry eng-GB translations. If nothing else on the site uses eng-GB, an administrator can remove the language under
Setup > Languages; removing a language removes the translations in it, so check that first.
