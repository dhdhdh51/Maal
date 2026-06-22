# Hosting Maal on cPanel

Maal **can** run on cPanel, but it was designed for a VPS (FFmpeg, Redis,
Supervisor). Read this before choosing shared hosting.

## TL;DR

| Component | Shared cPanel | cPanel VPS / WHM | Notes |
|-----------|---------------|------------------|-------|
| Laravel app + MySQL | ✅ | ✅ | PHP 8.2+ via "Select PHP Version" |
| Payments + webhooks | ✅ | ✅ | PayU primary; webhooks just need a public URL |
| Resumable uploads | ✅ | ✅ | Browser uploads **directly to cloud storage**, bypassing PHP limits |
| FFmpeg transcoding | ⚠️ usually not | ✅ | Shared hosts rarely include FFmpeg |
| Queue workers | ⚠️ cron only | ✅ Supervisor | No long-running processes on shared |
| Redis | ❌ usually | ✅ | Fall back to database/file drivers |
| HLS delivery | ⚠️ via CDN only | ✅ via CDN | Never stream big files through shared PHP |

**Recommendation:** use a **cPanel VPS / dedicated** (or any VPS). Shared cPanel
only works for a cut-down setup where transcoding is offloaded and media is on a
CDN.

---

## 1. Upload & document root

cPanel serves from `public_html`. Laravel's web root is `public/`. Two options:

**Option A (recommended) — keep the app outside the web root:**
1. Upload the project to `/home/USER/maal` (outside `public_html`).
2. Move the contents of `maal/public` into `public_html`.
3. Edit `public_html/index.php` paths:
   ```php
   require __DIR__.'/../maal/vendor/autoload.php';
   $app = require_once __DIR__.'/../maal/bootstrap/app.php';
   ```

**Option B — point the domain's document root to `.../maal/public`** in
WHM/cPanel (only available on some plans).

Laravel already ships `public/.htaccess` for Apache pretty URLs — keep it.

## 2. PHP

- cPanel → **Select PHP Version** → 8.2 or 8.3.
- Enable extensions: `bcmath ctype curl fileinfo gd intl mbstring openssl pdo_mysql zip` (and `redis` only if available).
- In **MultiPHP INI Editor**: raise `memory_limit` (≥256M) and `max_execution_time`.

## 3. Composer

If SSH is available:
```bash
cd ~/maal
composer install --no-dev --optimize-autoloader
```
If no SSH: run `composer install` locally and upload the `vendor/` folder.

## 4. Environment

```bash
cp .env.example .env
php artisan key:generate
```
Shared-hosting friendly `.env` profile:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
FORCE_HTTPS=true

DB_CONNECTION=mysql      # create DB + user in cPanel → MySQL Databases
DB_HOST=localhost
DB_DATABASE=cpaneluser_maal
DB_USERNAME=cpaneluser_maal
DB_PASSWORD=...

# No Redis on most shared hosting — use database/file instead:
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

# Keep originals + HLS off the shared disk:
MEDIA_DISK=r2
CDN_URL=https://cdn.your-domain.com
```
Then:
```bash
php artisan migrate --force --seed
php artisan storage:link        # if symlinks are allowed; otherwise copy storage/app/public into public_html/storage
php artisan config:cache route:cache view:cache
```

## 5. Queues without Supervisor (cron)

Shared hosting can't run Supervisor. Use cPanel → **Cron Jobs**:

```cron
# Laravel scheduler (every minute)
* * * * * cd /home/USER/maal && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1

# Drain the queue every minute (processes any pending jobs, then exits)
* * * * * cd /home/USER/maal && /usr/local/bin/php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```
(Confirm the PHP CLI path with cPanel → "Select PHP Version" or `which php`.)

## 6. FFmpeg (the hard part)

Transcoding needs the `ffmpeg`/`ffprobe` binaries.

- **Check availability:** `which ffmpeg` over SSH. If present, set
  `FFMPEG_BINARY` / `FFPROBE_BINARY` in `.env` to the reported paths.
- **If not available**, in order of preference:
  1. **Use a cPanel VPS** and `sudo apt-get install ffmpeg` (or yum).
  2. **Static binary in your home dir:** download a static `ffmpeg`/`ffprobe`
     build to `~/bin`, `chmod +x`, and set the `.env` paths. (Shared CPU limits
     still make large encodes slow/unreliable.)
  3. **Offload encoding:** run the transcoding queue workers on a separate small
     VPS that shares the same database + object storage, or integrate a cloud
     encoding API. The cPanel box then only serves the app.

## 7. Storage & HLS delivery

- Put originals + HLS in object storage (R2/S3/B2/Spaces) — set `MEDIA_DISK`.
- Front the bucket with a CDN and set `CDN_URL`. **Do not** proxy HLS bandwidth
  through shared PHP; it will breach CPU/bandwidth limits.
- Configure bucket CORS to allow `PUT` from your domain (for resumable uploads).

## 8. SSL & cron-based maintenance

- Enable **AutoSSL** (Let's Encrypt) in cPanel for HTTPS.
- Backups: the weekly `maal:backup` runs via the scheduler cron above; download
  `storage/app/backups` or sync it to a bucket.

---

## When to skip shared cPanel

If you expect real traffic or self-hosted transcoding, use a VPS and follow the
main [README deployment section](../README.md#vps-deployment) with
`deploy/nginx.conf` + `deploy/supervisor.conf`. It's simpler and far more
reliable than fighting shared-hosting limits.
