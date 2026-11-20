# Upgrade from cjw_newsletter 4.2 to 4.3

4.3.0 adds saved segments and a welcome series per newsletter list. This page lists what an installation of 4.2.x has
to do. Nothing changes in how editions are sent until a segment or a series is used.

## 1. Requirements

- **cjw_newsletter 4.2.20.** The database update starts from the 4.2.20 schema. On an older 4.2.x, update to 4.2.20
  first (see the CHANGELOG).
- The requirements of 4.2 (kernel version, PHP 8.0 to 8.5) stay as they are.

## 2. Back up the database

The update adds tables and columns and changes no data, but it cannot be undone without a backup.

## 3. The database update

One file per database engine. Run it **once**: it has no `IF NOT EXISTS` and fails on a database that has the
4.3.0 table or columns already.

| Engine | File |
|---|---|
| MySQL / MariaDB | `update/database/mysql/4.3/dbupdate-4.2.20-to-4.3.0.sql` |
| PostgreSQL | `update/database/postgresql/4.3/dbupdate-4.2.20-to-4.3.0.sql` |
| SQLite | `update/database/sqlite/4.3/dbupdate-4.2.20-to-4.3.0.sql` |

```bash
# MySQL / MariaDB
mysql -u <user> -p <database> < extension/cjw_newsletter/update/database/mysql/4.3/dbupdate-4.2.20-to-4.3.0.sql
# PostgreSQL
psql -U <user> -d <database> -f extension/cjw_newsletter/update/database/postgresql/4.3/dbupdate-4.2.20-to-4.3.0.sql
# SQLite (the installation's file, here the default of a new installation)
sqlite3 var/storage/sqlite3/sqlite.db < extension/cjw_newsletter/update/database/sqlite/4.3/dbupdate-4.2.20-to-4.3.0.sql
```

What it does:

- New table `cjwnl_segment`: the saved segments of a list.
- New tables `cjwnl_series`, `cjwnl_series_step` and `cjwnl_series_member`: the welcome series of a list.
- New column `cjwnl_edition_send.series_step_id` (default `0` = not a series send).
- New column `cjwnl_edition_send.segment_id` (default `0` = the whole list).
- New column `cjwnl_edition_send_item.person_tracking` (default `0` = no per-person tracking).

The [schema page](schema-4.2.md) lists the columns. A new installation takes `sql/<engine>/schema.sql` or
`share/db_schema.dba` instead and needs no update.

## 4. The new files and the caches

1. Put the 4.3.0 files in place (`composer require se7enxweb/cjw_newsletter:~4.3.0`, or the archive of the release).
2. Regenerate the autoloads (new class files under `classes/segments/` and `classes/series/`): `php bin/php/ezpgenerateautoloads.php -e`.
3. Clear the caches: `php bin/php/ezcache.php --clear-all`.
4. Reload PHP-FPM. Under Velocity, deploy (`exp:velocity deploy`) so the workers load the new classes.
