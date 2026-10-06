# Kewico plugin

Kewico-specific functionality moved over from the old CakePHP 2 system
(`kewico_php8`). Kept separate from the Orangescrum core so upstream releases
can still be merged.

## Data import from the old system

The old database is read through the `legacy` connection
(`config/app_local.php`, `LEGACY_DB_*` environment variables).

```bash
# Show what would be imported, write nothing
bin/cake kewico import_legacy --dry-run

# Import users and accounts (replaces the rows already in those tables)
bin/cake kewico import_legacy --truncate

# Only some tables
bin/cake kewico import_legacy --tables users,company_users --truncate

# Everything (the switch-day command)
bin/cake kewico import_legacy --tables all --truncate
```

Groups for `--tables`: `accounts` (companies, users, memberships, accounts),
`cases` (statuses, types, projects and comments), `files` (file rows, time
logs, labels), `userdata` (checklists, filters, notification and email
settings, invitations, invoice customers), `kewico` (the 59 Kewico-only
tables, copied 1:1), `all`.

Kewico-only tables are copied with their old structure and keep a fingerprint
of it (table comment `kewico-copy:...`). When the old table changes, the next
import rebuilds the copy; when the copy was changed in the new system, the
import stops instead.

Tables marked `merge` in the map (status groups, custom statuses, types) keep
the rows that ship with the new version and replace only the imported ids.

What the import does per table:

- Copies every column that exists in both schemas, and applies the renames and
  mappings in `src/Import/LegacyTableMap.php`.
- Fills new required columns with the defaults from the map, or with a safe
  typed value (`0`, `''`, current time).
- Turns MySQL zero dates (`0000-00-00`) into `NULL` where allowed.
- Keeps the row IDs, so relations between tables stay intact.
- Stores old columns that no longer exist in the new schema in a side table
  `kewico_legacy_<table>` (same `id`), so later steps can use that data.
- Compares row counts at the end and fails if they differ.

Old passwords are plain MD5. They are copied unchanged. The login accepts them
through the fallback password hasher and re-saves them as bcrypt at each
user's next successful login.

## Keeping the preview in sync (one way)

`scripts/sync_legacy.sh` re-runs the full import, so this system shows the
live data of kewico_php8 as of the last run. It is one-way only: everything
changed in this system since the previous run is replaced. It only reads the
`legacy` database.

```bash
# by hand
sh plugins/Kewico/scripts/sync_legacy.sh

# from cron, every night at 02:30 (full path to PHP, cron has a minimal PATH)
30 2 * * * PHP_BIN=/usr/local/php83/bin/php KEWICO_LEGACY_FILES=/home/<user>/www/kewico.com/APP/CAD/app/webroot/files /bin/sh /home/<user>/www/kewico.com/APP/CAD_NEW/plugins/Kewico/scripts/sync_legacy.sh
```

With `KEWICO_LEGACY_FILES` set, new profile photos are copied from the old
system after the import (only missing files, nothing is overwritten or
deleted). Attachments need no copy: `webroot/files/case_files` is a link to
the old folder.

One run at a time (a lock in `tmp/`), output in `logs/kewico_sync.log`, exit
code 0 when every row count matches. The cache is cleared after each run.
