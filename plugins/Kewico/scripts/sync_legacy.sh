#!/bin/sh
#
# One-way sync: kewico_php8 (live database, `legacy` connection) -> this
# system (`default` connection). Re-runs the full import, so the preview
# shows the live data of the last run. Everything changed in this system
# since the previous run is replaced.
#
# Usage (from cron, e.g. every night at 02:30):
#   30 2 * * * /bin/sh /home/<user>/www/kewico.com/APP/CAD_NEW/plugins/Kewico/scripts/sync_legacy.sh
#
# Optional environment:
#   PHP_BIN    PHP binary to use (default: php)
#   KEWICO_SYNC_TABLES  tables/groups to import (default: all)
#   KEWICO_LEGACY_FILES  the old system's app/webroot/files folder. When set,
#              new profile photos are copied from there to webroot/files/photos
#              (only files that do not exist here yet; nothing is overwritten
#              or deleted). Attachments are not copied: case_files is a link.
#
# Exit code 0 = import finished and all row counts match.

set -u

APP_DIR=$(cd "$(dirname "$0")/../../.." && pwd)
PHP_BIN=${PHP_BIN:-php}
TABLES=${KEWICO_SYNC_TABLES:-all}
LOG="$APP_DIR/logs/kewico_sync.log"
LOCK="$APP_DIR/tmp/kewico_sync.lock"

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') $*" >> "$LOG"
}

# One run at a time (mkdir is atomic; no flock on FreeBSD).
if ! mkdir "$LOCK" 2>/dev/null; then
    # A lock older than 2 hours is left over from a crashed run.
    if [ -n "$(find "$LOCK" -maxdepth 0 -mmin +120 2>/dev/null)" ]; then
        log "removing stale lock"
        rmdir "$LOCK" 2>/dev/null
        mkdir "$LOCK" 2>/dev/null || { log "could not take the lock, skipping"; exit 1; }
    else
        log "another sync is still running, skipping"
        exit 1
    fi
fi
trap 'rmdir "$LOCK" 2>/dev/null' EXIT INT TERM

cd "$APP_DIR" || { log "cannot cd to $APP_DIR"; exit 1; }

log "sync started (tables: $TABLES)"
START=$(date +%s)

# Output goes to a file, so CakePHP writes it without colour codes.
"$PHP_BIN" bin/cake.php kewico import_legacy --tables "$TABLES" --truncate >> "$LOG" 2>&1
STATUS=$?

# Profile photos: a new upload in the old system gets a new file name, and the
# imported users.photo already points at it. Copy the files that are missing.
LEGACY_FILES=${KEWICO_LEGACY_FILES:-}
if [ -n "$LEGACY_FILES" ]; then
    if [ -d "$LEGACY_FILES/photos" ]; then
        mkdir -p "$APP_DIR/webroot/files/photos"
        BEFORE=$(ls "$APP_DIR/webroot/files/photos" | wc -l)
        cp -Rn "$LEGACY_FILES/photos/." "$APP_DIR/webroot/files/photos/" 2>> "$LOG"
        AFTER=$(ls "$APP_DIR/webroot/files/photos" | wc -l)
        log "photos: $(( AFTER - BEFORE )) new file(s) copied"
    else
        log "photos: $LEGACY_FILES/photos not found, skipped"
    fi
fi

# Cached query results and metadata would still show the old data.
rm -rf "$APP_DIR"/tmp/cache/models/* "$APP_DIR"/tmp/cache/persistent/* 2>/dev/null

log "sync finished in $(( $(date +%s) - START ))s, exit code $STATUS"
exit $STATUS
