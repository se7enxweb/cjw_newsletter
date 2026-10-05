# Import, export and migration (cjw_newsletter 4.2.0)

This page covers three features:

- the CSV import with a column mapping,
- the subscriber export,
- the migration from the old eznewsletter extension (`ext:cjw_newsletter:import-eznewsletter`).

All of them are admin features. They use the policy function `newsletter/import_export`. Give it only to the roles
that may see and change subscriber data.

## 1. CSV import with column mapping

The import of 4.1 (`newsletter/subscription_list_csvimport`) reads the columns in a fixed order. It is still there.
The import with a mapping lets the admin choose which column fills which field. It shows a preview, makes a dry
run, and writes the consent of every imported person to the kernel consent log.

### Steps

1. **Upload.** Open a list's subscriptions, then **Import CSV**, then **CSV import with column mapping**. You can
   also go to `newsletter/import_mapping/0/(list)/<list node id>` directly. Choose:
   - the file;
   - the delimiter (semicolon, comma, tab or pipe);
   - the encoding (UTF-8, ISO-8859-1 or Windows-1252);
   - whether the first row holds the column names;
   - optionally a saved mapping;
   - the **consent source**;
   - a note.

   The file is stored under `var/<site>/cjw_newsletter/csvimport/`. Only files from that folder are ever read.
2. **Map the columns** (`newsletter/import_mapping/<import id>`). There is one row per column of the file, with a
   select of the fields and the first values of that column.
   - The mapping is guessed from the column names. English, German, French and Norwegian names are recognised, for
     example `E-Mail`, `Vorname`, `Nachname`, `Firma`, `Telefon`, `Sprache`.
   - Without a header row, the column that holds addresses becomes the e-mail address.
   - Exactly one column must be the e-mail address. Each field can be chosen only once.
   - The fields are `[ImportMappingSettings] MappableFields[]`: e-mail address, salutation, first name, last name,
     organisation, the four custom fields, language and phone number.
3. **Preview.** The first ten rows are shown with the result each would get:
   - **new**;
   - **update** (the address exists);
   - **skip**, with the reason.
4. **Dry run.** Every row is checked and counted, and nothing is written. The report lists the rows that would not
   be imported, with the reason for each.
5. **Import.** It runs once per upload. When `[NewsletterCsvImportSettings] ImportInBackground=enabled`, it runs as
   a background job (`ext:cjw_newsletter:import --import-id=<id>`), and the page shows its progress. The report
   then shows what was done.

**Save the mapping as** stores the mapping in `cjwnl_import_mapping`, by column name, together with the delimiter,
the header and encoding settings and the consent source. The next upload of a file with the same columns can then
choose it.

### What happens to a row

| Row | Result |
|---|---|
| New address | A confirmed newsletter user and an approved subscription to the list, in the chosen output formats |
| Address of an existing newsletter user (case does not matter) | Updated, never duplicated. The mapped non-empty values replace the stored ones (switch off "Update the data of existing subscribers" to keep them), and a pending or confirmed subscription of the list is approved |
| Already approved for the list | Unchanged |
| Empty, invalid or repeated in the file | Skipped: "No e-mail address", "Not a valid e-mail address", "Repeated in the file" |
| On the kernel suppression list | Skipped: "On the suppression list" |
| On the newsletter blacklist | Skipped: "On the newsletter blacklist". An address that the blacklist bridge has already put on the suppression list counts as suppressed |
| The person switched the newsletter category, or all optional mail, off on the e-mail preference page | Skipped: "Switched the newsletter off in the e-mail preferences" |
| The person unsubscribed: the newsletter user, or the subscription of this list | Skipped: "Unsubscribed before". An import never subscribes someone again who left |
| A hard bounce | Skipped: "The address bounced" |
| A salutation, language or phone number that cannot be read | That value is left out and the rest of the row is imported. The report counts these rows |

The values are cleaned before they are stored:

- **Salutation**: `1`/`2`, or a word such as Mr, Herr, M., Ms, Frau or Mme.
- **Language**: a locale such as `ger-DE` (`ger_de` is accepted).
- **Phone number**: only digits with a leading `+`; `00` becomes `+`. It is stored unconfirmed. The import gives no
  SMS consent; that needs the code confirmation of the SMS area.

### Consent

An imported subscription is recorded in the kernel consent log (`expmail_consent_log`):

- source `import`;
- the importing admin as the actor;
- the wording `Newsletter "<list>": subscribed by the CSV import <id> (consent: <consent source>)`.

The consent source is required for the import itself (not for the dry run). It says where and how the people
agreed, for example "Sign-up form at the trade fair 2025". The double opt-in mail is not sent, because the consent
was given where the addresses were collected. You are responsible for being able to show that consent. The bridge
of 4.1.19 does not record the import a second time as "approved by an administrator".

### Audit

A finished import (not a dry run) writes the audit event `data.import.csv`. Its fields:

- object: the list;
- the counts;
- the mapping;
- the consent source;
- the skipped rows by reason.

The addresses themselves are not part of the event.

### Command line

The import of an upload can also be run in a console:

```
./console ext:cjw_newsletter:import --import-id=<id> --dry-run
./console ext:cjw_newsletter:import --import-id=<id>
```

## 2. Subscriber export

`newsletter/subscriber_export/<list content object id>`. The CSV export page of a list links to it.

- **Filters**:
  - the subscription statuses (none checked means every status; the form starts with confirmed and approved);
  - a date range on the subscribed, confirmed, approved or removed date.
- **Columns**: the subscriber fields (also organisation, language and phone number), the subscription status, the
  output formats, the four dates, and the subscription, user and import ids.
- The preview shows the number of rows and the first ten.
- **Download CSV** writes the file with a UTF-8 byte order mark and the chosen delimiter, in batches of 500 rows.
  Dates are written as ISO 8601 (UTC).
- **Formula cells are defused.** A cell that starts with `=`, `+`, `-`, `@`, a tab or a carriage return gets a
  leading apostrophe, so that a spreadsheet shows it as text instead of running it. Numbers stay numbers.
- **Every download is recorded** as the audit event `data.export.csv`, with:
  - the list;
  - the statuses;
  - the date filter;
  - the columns;
  - the number of rows.

  The addresses are not part of the event.

The file holds personal data. Keep it only as long as needed.

## 3. Migration from eznewsletter

Sites that ran the old eznewsletter extension (1.x, tables `ezsubscription_list`, `ezsubscription`,
`ezsubscriptionuserdata`, ...) can take their subscribers over:

```
./console ext:cjw_newsletter:import-eznewsletter --help
```

**The old tables are only read, never changed.** The reader runs SELECT statements only, and it refuses anything
else. An SQLite source file is opened read-only.

### Options

| Option | Meaning |
|---|---|
| `--dry-run` | Check, count and log everything; write nothing else |
| `--list-map=1:18150,2:18151` | Old list id : content object id of the cjw list to fill |
| `--create-lists --system-node=<node id>` | Create a cjw list (named like the old one) under that newsletter system for every old list that is not mapped |
| `--include-pending` | Also take over subscriptions that were never confirmed, as pending. No mail is sent. Without it they are skipped, because there was never a consent |
| `--robinson-to-suppression` | Put the addresses of the old do-not-contact list (`ezrobinsonlist`, e-mail entries) on the kernel suppression list with reason `legal` |
| `--source-sqlite=<file>` | Read the old tables from an SQLite file (a copy of the old data) instead of the installation's database |
| `--batch-size=<n>` | Rows read at a time (default `[EznewsletterImportSettings] BatchSize=500`) |

### What is taken over

| Old | New |
|---|---|
| `ezsubscription_list` (the published row, else the draft) | The mapped or created cjw list. A list without a target is reported, and its subscriptions are skipped |
| `ezsubscriptionuserdata` | One cjw newsletter user per address. An existing user is used, never duplicated: empty first and last names and an empty phone number are filled in from the old data |
| `ezsubscription` (the published row) | One subscription per list and person, with the old state: confirmed, approved, unsubscribed (stays unsubscribed, so the person is not subscribed again later) or removed by an admin. The old dates (created, confirmed, approved, removed) and the output formats (text -> text, HTML and external HTML -> HTML) are kept. `remote_id` is `eznewsletter:<old id>`. A subscription cjw already has is kept as it is |
| Subscriptions with 2 or more bounces | Skipped (they were on hold in the old system) |
| `ezrobinsonlist` | These addresses are never imported (see `--robinson-to-suppression`) |
| `eznewsletter`, `ezsendnewsletteritem` | Recorded in the migration log as history (name, send date, mails sent and on hold). The old editions are not re-created |
| Old consent state | One row per person in the kernel consent log, with the source `import`, naming the run and the original opt-in date ("the person opted in on 2019-01-02"); for a person who only unsubscribed, the opt-out with its date |

Addresses on the suppression list or the newsletter blacklist, people who switched the newsletter off in the e-mail
preferences, and invalid addresses are skipped and logged. The old passwords, VIP levels, login steps and
subscription groups are not taken over.

### The migration log

Every row read is logged in `cjwnl_migration_log`. Each row has:

- the run id;
- the old table and id;
- the cjw table and id written;
- the action: `created`, `merged`, `skipped`, `failed` or `recorded`;
- the dry-run flag;
- details.

`newsletter/migration_log` lists the runs, and `newsletter/migration_log/<run id>` shows one run by table and
action, filtered with `(action)/` and `(table)/`. The dashboard shows the last runs.

A second run skips everything an earlier run (not a dry run) took over ("Taken over by an earlier run"), so the
command can be run again after an interruption or once more old rows have arrived. A finished run (not a dry run)
writes the audit event `system.cjw_newsletter.import`.

### Migration guide for a site on eznewsletter

1. **Back up the database.** The migration does not change the old tables, but it does write cjw rows.
2. Install cjw_newsletter 4.2.0 and its tables (the setup wizard, the installer, or `share/db_schema.dba`). Create
   the newsletter system and the lists, or plan to use `--create-lists`.
3. Find the old list ids. `newsletter/migration_log` shows which old tables it finds in the database and how many
   rows they have. Then decide for each old list: map it to an existing cjw list (`--list-map`) or create one
   (`--create-lists --system-node`).
4. Run a **dry run**, then read its log:

   ```
   ./console ext:cjw_newsletter:import-eznewsletter --dry-run --list-map=1:<list object id> --robinson-to-suppression
   ```

   Check the skipped rows by reason: no target list, never confirmed, on the do-not-contact list, bounced,
   suppressed, invalid.
5. Run it for real, with the same options and without `--dry-run`.
6. Check the lists (`newsletter/subscription_list/<node id>`) and a few people's consent history on the e-mail
   preference pages.
7. Switch the old extension off. Keep its tables until you no longer need them as the record of the old consents;
   the migration log points to their ids.

If the old data lives in another database, copy its eznewsletter tables into an SQLite file and use
`--source-sqlite`.

## Settings (`cjw_newsletter.ini`)

```
[ExtensionPointSettings]
Handlers[]=CjwNewsletterImportExportHooks
DashboardBlocks[]=design:newsletter/dashboard/importexport.tpl

[ImportMappingSettings]
MappableFields[]=email
... (salutation, first_name, last_name, organisation, custom_data_text_1..4, language, phone_number)
# the consent source written when an import names none
DefaultConsentSource=import

[EznewsletterImportSettings]
BatchSize=500

[NewsletterCsvImportSettings]
# enabled: an import runs as a background job; disabled: inside the request
ImportInBackground=enabled
```

## Privacy notes

- An import needs a lawful basis for every address. The consent source documents it, and it ends up in each
  person's consent history. Do not import addresses without consent.
- Suppressed, blacklisted, unsubscribed and opted-out addresses are never imported again.
- An export is recorded in the audit trail; the file holds personal data.
- Uploaded files stay in the import folder. Remove them when the import is done and no longer needs to be checked.

## Classes, tables, views

- **Classes** (in `classes/importexport/`):
  - `CjwNewsletterCsvMapper`: reading, guessing, cleaning a mapping;
  - `CjwNewsletterMappedImport`: the import and its dry run;
  - `CjwNewsletterImportConsent`: suppression checks and the consent log with the source "import";
  - `CjwNewsletterSubscriberExport`;
  - `CjwNewsletterEznewsletterSource`: the read-only reader;
  - `CjwNewsletterEznewsletterMigration`;
  - `CjwNewsletterImportExportHooks`: the dashboard block;
  - and the data classes `CjwNewsletterImportMapping` and `CjwNewsletterMigrationLog`.
- **Tables**: `cjwnl_import_mapping`, `cjwnl_migration_log`, and the 4.2.0 columns of `cjwnl_import` (`mapping_id`,
  `is_dry_run`, `consent_source`, `status`, `skipped_count`, `error_count`). See `doc/schema-4.2.md`.
- **Views**: `import_mapping`, `subscriber_export`, `migration_log`. Templates are in
  `design/standard/templates/newsletter/importexport/`, the stylesheet is `newsletter_importexport.css`.
- **Commands**: `ext:cjw_newsletter:import-eznewsletter` (`bin/php/import-eznewsletter.php`) and
  `ext:cjw_newsletter:import` (also runs mapped imports).
