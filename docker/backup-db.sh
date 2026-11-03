#!/bin/bash
# Nightly logical backup of the orellapager database (cron, 04:30 UTC).
# Writes a gzip-compressed mysqldump to BACKUP_DIR (./backups on the host),
# checks it is a valid archive before keeping it, and deletes backups older
# than BACKUP_KEEP_DAYS. A failed dump leaves no partial file behind.
#
# Backups contain the real visitor IPs of posters: keep ./backups git-ignored
# (it is) and chmod 700 on the host. Copy it off the droplet as well.
#
# Run by hand:
#     docker compose exec web sh -c '. /etc/orellapager.env && /usr/local/bin/backup-db.sh'
# Restore (replaces the live tables with the backup):
#     gunzip < backups/orellapager-YYYY-MM-DD_HHMMSS.sql.gz \
#       | docker compose exec -T db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD"'
set -euo pipefail

DIR="${BACKUP_DIR:-/var/backups/orellapager}"
KEEP="${BACKUP_KEEP_DAYS:-14}"
out="$DIR/orellapager-$(date -u +%Y-%m-%d_%H%M%S).sql.gz"

mkdir -p "$DIR"
export MYSQL_PWD="${DB_PASS:-}"

if ! mysqldump -h "${DB_HOST:-localhost}" -u "${DB_USER:-root}" \
        --single-transaction --routines --triggers \
        --databases "${DB_NAME:-orellapager}" | gzip -9 > "$out.tmp"; then
    rm -f "$out.tmp"
    echo "backup-db: FAILED, no backup written" >&2
    exit 1
fi
gzip -t "$out.tmp"
mv "$out.tmp" "$out"
chmod 644 "$out"

find "$DIR" -maxdepth 1 -name 'orellapager-*.sql.gz' -mtime +"$KEEP" -delete
echo "backup-db: wrote $out ($(du -h "$out" | cut -f1)), keeping $KEEP days, $(ls "$DIR"/orellapager-*.sql.gz | wc -l) backup(s) on disk"
