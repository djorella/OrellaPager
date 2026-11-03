# OrellaPager
This is a LAMP (Linux, Apache, Mysql, and PHP) Application designed to explore how back-end functionality works in major social media giants and bring back a resemblance of the nostalgic, awesome Pager!

Feel free to check out this website as it is live and running @
[Orella Pager](http://orellapager.me)

The app runs as two Docker containers started by Docker Compose:

| Service | What it runs |
|---|---|
| `web` | PHP 8.4 + Apache, HTTPS with Let's Encrypt, and the cron jobs (certificate renewal, nightly database backup, daily IP purge, monthly spam report) |
| `db` | MariaDB 11, reachable only from `web`, data kept in the `dbdata` volume |

## Run it locally

```
cp .env.example .env        # defaults work for local testing
docker compose up -d --build
```

Open https://localhost:8443 (accept the self-signed certificate warning). The `.env.example` defaults use hCaptcha's public test keys, because hCaptcha refuses real keys on localhost. `DB_PASSWORD` is required, or Compose will not start.

## Deploy to a new DigitalOcean droplet

### 0. Before you touch the old droplet
- Take a **snapshot** of it in the DigitalOcean panel. Keep it for a week after the new site works.
- Keep a copy of the folder `letsencrypt/` (certificates and private keys, never in git). If you no longer have one, pull it from the old droplet: `sudo tar czf - -C /etc letsencrypt`.
- Keep the full database dump somewhere private. The repository seed (`db/init.sql`) holds only the users who posted, with placeholder IPs.
- Have your real hCaptcha **secret key** ready (hCaptcha dashboard). It is not in the repo.

### 1. Create the droplet
- Image **Ubuntu 24.04 LTS**, 2 GB RAM / 50 GB disk is plenty (the stack uses about 120 MB of memory).
- Add your SSH key. Optionally attach the old droplet's **Reserved IP** so DNS does not need to change.

### 2. Prepare the server
```
ssh root@NEW_DROPLET_IP
apt-get update && apt-get -y upgrade
curl -fsSL https://get.docker.com | sh        # installs Docker Engine and the Compose plugin
ufw allow OpenSSH && ufw allow 80/tcp && ufw allow 443/tcp && ufw --force enable
```
Ports 80 and 443 must stay open to the whole internet: Let's Encrypt validates and renews over them. The database port is never published.

### 3. Get the code
```
git clone -b upgrade-10-2026 https://github.com/djorella/OrellaPager.git
cd OrellaPager
```
Use `master` instead once the `upgrade-10-2026` branch is merged.

### 4. Copy the certificates (from your laptop)
The files inside `letsencrypt/live/` are symlinks into `letsencrypt/archive/`. Copy with `tar` or `rsync -a`. **Do not use `scp -r`**: it follows the links and breaks renewal.
```
tar czf - letsencrypt | ssh root@NEW_DROPLET_IP 'tar xzf - -C OrellaPager && chmod -R go-rwx OrellaPager/letsencrypt'
```
Without this folder the container creates a self-signed certificate and browsers will warn.

### 5. Create `.env` on the server
```
cd ~/OrellaPager
cat > .env <<EOF2
DB_PASSWORD=$(openssl rand -hex 24)
HCAPTCHA_SITEKEY=6a29ba4e-d39b-4e58-b64a-5b47ba5cbad2
HCAPTCHA_SECRET=PASTE_YOUR_REAL_HCAPTCHA_SECRET
HTTP_PORT=80
HTTPS_PORT=443

# Monthly spam report email (needs an SMTP relay, e.g. a Gmail app password)
REPORT_EMAIL=you@example.com
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=you@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM=you@gmail.com
EOF2
chmod 600 .env
```
Edit the placeholders with `nano .env`. Do **not** copy your laptop's `.env`: it holds the localhost test captcha keys. Every option is described in `.env.example`.

### 6. Point DNS at the server
At your DNS provider, set the `A` records for `orellapager.me` and `www.orellapager.me` to the new droplet's IP (or reassign the Reserved IP). Lower the TTL beforehand if you can. Check with `dig +short orellapager.me`.

### 7. Start it
```
docker compose up -d --build
docker compose ps                  # both services should be running, db healthy
docker compose logs -f web         # Ctrl+C to leave
```
On the first start MariaDB creates the database from `db/init.sql`. Both containers restart automatically after a reboot.

### 8. Verify
```
curl -sI http://orellapager.me | head -3                      # 301 to https
curl -sI https://orellapager.me | head -3                     # 200
docker compose exec web certbot certificates                  # both certificates, expiry dates
docker compose exec web certbot renew --dry-run               # renewal works (DNS must already point here)
docker compose exec web sh -c '. /etc/orellapager.env && /usr/local/bin/backup-db.sh'   # first backup into ./backups
```
Then open the site, solve the captcha and post a test beep.

If email is configured, test the report now instead of waiting for the 1st of the month:
```
docker compose exec web sh -c '. /etc/orellapager.env && php /usr/local/bin/spam-report.php'
```

## Day-to-day operations

| Task | Command |
|---|---|
| Update the site | `git pull && docker compose up -d --build` |
| Update PHP | change the `FROM php:...-apache` tag in `Dockerfile`, then rebuild |
| Update MariaDB / OS | `docker compose pull db && docker compose up -d`; `apt-get upgrade` on the host |
| See errors and blocked attempts | `docker compose logs web`, `logs/rejections.log` |
| Stop (keeps data) | `docker compose down` |

**Never run `docker compose down -v`**: the `-v` deletes the database volume.

### Where things are written
| Folder | Contents |
|---|---|
| `backups/` | nightly `orellapager-<date>.sql.gz`, 14 days kept (`BACKUP_KEEP_DAYS`) |
| `logs/` | `rejections.log` (blocked bot attempts), `purged-ips.log` (IPs removed after 30 days), `spam-report-<date>.txt` |
| `letsencrypt/` | certificates and the renewal configuration, renewed in place |

All three are git-ignored. `backups/` holds real visitor IPs, so keep it private. **Copy `backups/` and `letsencrypt/` off the droplet regularly**, because they live on the same disk they protect.

### Restore a backup
```
gunzip < backups/orellapager-YYYY-MM-DD_HHMMSS.sql.gz \
  | docker compose exec -T db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD"'
```

### Scheduled jobs (inside the `web` container, UTC)
| Time | Job |
|---|---|
| 04:30 daily | database backup |
| 05:00 daily | delete IPs older than `RETENTION_DAYS` (30) that never posted, after logging them |
| 05:10 on the 1st | summarise blocked attempts and email the report |
| 06:43 daily | `certbot renew` |

### Troubleshooting
- **Browser shows a certificate warning:** `letsencrypt/` was not copied, or was copied with `scp -r`. Redo step 4 and run `docker compose restart web`.
- **Beeps are rejected with "NASTY robot":** check `logs/rejections.log`. The reason is on the last line. `invalid-input-secret` or `sitekey-secret-mismatch` means `HCAPTCHA_SECRET` or `HCAPTCHA_SITEKEY` in `.env` is wrong. After editing `.env`, run `docker compose up -d` so the container picks it up.
- **Compose says `set DB_PASSWORD in .env`:** `.env` is missing or lacks that line.
- **Changed `DB_PASSWORD` and the app cannot connect:** MariaDB only reads the password when the volume is first created. Change it inside the database with `ALTER USER`, or restore a backup into a new volume.
