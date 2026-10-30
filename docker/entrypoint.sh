#!/bin/sh
# Prepares TLS material, mail and cron (certbot renewal, IP purge, spam
# report), then runs Apache.
set -e

LE_DIR=/etc/letsencrypt
LIVE=$LE_DIR/live/orellapager.me

# Certbot's Apache TLS parameters. Normally comes with the mounted
# /etc/letsencrypt; fall back to the copy baked into the image.
if [ ! -f "$LE_DIR/options-ssl-apache.conf" ]; then
    mkdir -p "$LE_DIR"
    cp /usr/local/share/orellapager/options-ssl-apache.conf "$LE_DIR/options-ssl-apache.conf"
fi

# No Let's Encrypt certificate mounted (local development): create a
# self-signed one in the same place so the vhost config works unchanged.
if [ ! -f "$LIVE/fullchain.pem" ]; then
    echo "entrypoint: no Let's Encrypt certificate found, generating a self-signed one"
    mkdir -p "$LIVE"
    openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
        -subj "/CN=orellapager.me" \
        -keyout "$LIVE/privkey.pem" -out "$LIVE/fullchain.pem" 2>/dev/null
fi

# Log directory (mounted from ./logs, ownership left as on the host).
# Apache (www-data) appends to rejections.log, so that one file is created
# here with www-data as owner; the cron jobs run as root and write
# purged-ips.log and spam-report-*.txt themselves.
mkdir -p /var/log/orellapager
touch /var/log/orellapager/rejections.log
chown www-data:www-data /var/log/orellapager/rejections.log
chmod 644 /var/log/orellapager/rejections.log

# Outgoing mail for the monthly spam report: msmtp acts as sendmail for
# PHP's mail(). Configured only when SMTP_HOST is set.
if [ -n "${SMTP_HOST:-}" ]; then
    {
        echo "defaults"
        echo "port ${SMTP_PORT:-587}"
        echo "tls ${SMTP_TLS:-on}"
        echo "tls_starttls ${SMTP_STARTTLS:-on}"
        echo "tls_trust_file /etc/ssl/certs/ca-certificates.crt"
        echo "logfile -"
        echo "account default"
        echo "host $SMTP_HOST"
        echo "from ${SMTP_FROM:-orellapager@orellapager.me}"
        if [ -n "${SMTP_USER:-}" ]; then
            echo "auth on"
            echo "user $SMTP_USER"
            echo "password $SMTP_PASS"
        else
            echo "auth off"
        fi
    } > /etc/msmtprc
    chown root:www-data /etc/msmtprc
    chmod 640 /etc/msmtprc
else
    echo "entrypoint: SMTP_HOST not set, spam reports will be written to logs/reports but not emailed"
fi

# cron does not see the container environment: hand the settings to the
# jobs through a root-only file (values single-quoted for sh).
env | grep -E '^(DB_|RETENTION_DAYS=|PURGE_LOG=|REJECT_LOG=|REPORT_DIR=|REPORT_EMAIL=|SMTP_FROM=)' \
    | sed "s/'/'\\\\''/g; s/=/='/; s/\$/'/; s/^/export /" > /etc/orellapager.env
chmod 600 /etc/orellapager.env

# Scheduled jobs: see /etc/cron.d/certbot-renew.
cron

exec "$@"
