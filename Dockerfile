# OrellaPager - PHP + Apache image mirroring the live droplet (orellapager.me)
#
# PHP is pinned to 8.4.27, the 21 Oct 2026 security release that fixes the
# "PHP2Shell" remote code execution flaws CVE-2026-109618 and CVE-2026-109619.
# Bump this tag when php.net publishes the next security release.
FROM php:8.4.27-apache

# certbot + Apache plugin for Let's Encrypt renewal (same tooling as the
# droplet), cron for the scheduled jobs, msmtp as the sendmail used by PHP's
# mail() to email the monthly spam report through an SMTP relay.
RUN apt-get update \
 && apt-get install -y --no-install-recommends certbot python3-certbot-apache cron msmtp msmtp-mta ca-certificates \
 && rm -rf /var/lib/apt/lists/*

# mysqli extension for the MariaDB connection in index.php, production php.ini
RUN docker-php-ext-install mysqli \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Apache: mod_rewrite + .htaccess support (HTTPS redirect, IP bans) and TLS.
RUN a2enmod rewrite ssl headers \
 && sed -ri '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Virtual hosts copied from the droplet: :80 redirects to HTTPS, :443 serves
# the site with the Let's Encrypt certificate for orellapager.me.
COPY docker/orellapager.conf /etc/apache2/sites-available/000-default.conf

# ---- Let's Encrypt certificate data --------------------------------------
# The certificates, private keys, account and renewal configuration live in
# /etc/letsencrypt, copied from the droplet into ./letsencrypt (git-ignored,
# never baked into the image). docker-compose.yml mounts it:
#     ./letsencrypt:/etc/letsencrypt
# Inside it:
#   live/orellapager.me/{fullchain,privkey}.pem   -> used by the :443 vhost
#   renewal/orellapager.me.conf                   -> authenticator/installer = apache
#   accounts/                                     -> the ACME account
# Without the mount the entrypoint generates a self-signed cert for local use.
COPY docker/options-ssl-apache.conf /usr/local/share/orellapager/options-ssl-apache.conf

# ---- Cron jobs ------------------------------------------------------------
# Droplet: root crontab "43 6 * * * certbot renew --post-hook 'service httpd restart'"
# Here: /etc/cron.d/certbot-renew, started by the entrypoint. Renewal needs
# ports 80/443 of this container reachable from the internet as orellapager.me.
# The same file runs purge-stale-ips.php daily: visitor IPs older than
# RETENTION_DAYS with no beeps are appended to /var/log/orellapager/purged-ips.log
# (mounted from ./logs) and then deleted.
COPY docker/certbot-renew.cron /etc/cron.d/certbot-renew
RUN chmod 0644 /etc/cron.d/certbot-renew
COPY docker/purge-stale-ips.php /usr/local/bin/purge-stale-ips.php
# Monthly: blocked bot/honeypot/captcha attempts (rejections.log, written by
# index.php) are summarised into /var/log/orellapager (./logs) and emailed to
# REPORT_EMAIL via SMTP_HOST/SMTP_USER/SMTP_PASS.
COPY docker/spam-report.php /usr/local/bin/spam-report.php

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Copy the live site (contents of /var/www/html on the droplet)
COPY . /var/www/html/
RUN rm -rf /var/www/html/docker && chown -R www-data:www-data /var/www/html

# Runtime settings (override in docker-compose.yml / .env):
#   DB_*              database connection
#   HCAPTCHA_SECRET   hCaptcha secret key - REQUIRED, beeps are refused without it
#   HCAPTCHA_SITEKEY  hCaptcha site key shown in the form
#   HCAPTCHA_HOSTNAMES hostnames a captcha token may be solved on
#   BEEP_COOLDOWN     seconds an IP must wait between beeps
#   RETENTION_DAYS    days a non-posting visitor IP is kept
#   APP_DEBUG=1       also send PHP warnings to the browser DevTools console
#   REPORT_EMAIL      where the monthly spam report goes (needs SMTP_* set)
#   SMTP_HOST/PORT/USER/PASS/FROM  relay used to send it (port 587 STARTTLS)
ENV DB_HOST=localhost \
    DB_USER=root \
    DB_PASS= \
    DB_NAME=orellapager \
    HCAPTCHA_SITEKEY=6a29ba4e-d39b-4e58-b64a-5b47ba5cbad2 \
    HCAPTCHA_HOSTNAMES=orellapager.me,www.orellapager.me \
    BEEP_COOLDOWN=60 \
    RETENTION_DAYS=30 \
    APP_DEBUG=0

EXPOSE 80 443
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
