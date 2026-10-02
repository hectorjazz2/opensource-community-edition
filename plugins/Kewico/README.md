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
