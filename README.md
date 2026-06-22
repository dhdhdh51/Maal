# Maal — Premium Video Streaming Platform

A production-grade, legal, age-gated video streaming platform built with **Laravel 12 / PHP 8.2+**, MySQL 8, Redis, FFmpeg and S3-compatible object storage (Cloudflare R2 / Amazon S3 / Backblaze B2 / DigitalOcean Spaces). Adaptive **HLS** delivery, category-based access locking, resumable uploads, background transcoding, dynamic watermarking, device/session control and a full admin panel.

**PayU is the primary payment gateway** (Razorpay, Stripe and manual bank transfer are also supported).

---

## Feature overview

| Area | What's included |
|------|-----------------|
| **Auth** | Register, login, email OTP verification, optional phone OTP, OTP password reset, age gate, terms consent, admin 2FA (Google Authenticator + recovery codes), Sanctum API tokens, device & session tracking |
| **Uploads** | Resumable chunked uploads — S3 multipart (browser → bucket) or local-disk fallback; format/size/quota validation; never via plain PHP form upload |
| **Transcoding** | FFmpeg → adaptive HLS ladder (360p–4K, no upscaling), poster, sprite thumbnails, preview clip (mp4 + HLS), SRT→VTT subtitles; dedicated queues, retries/backoff, progress + reprocess/cancel |
| **Streaming** | Signed/expiring HLS via path tokens, private originals, dynamic moving watermark (email/id/masked phone/datetime/session), quality + subtitle selectors, PiP, autoplay-next, single-stream concurrency enforcement, graceful error/processing screens |
| **Access & billing** | Category access (free/paid/subscription/lifetime), plans incl. bundles, modular gateways (**PayU**, Razorpay, Stripe, Manual), secure webhooks (signature + idempotency), invoices, grant/extend/revoke/expire |
| **Offers** | Coupons (%, flat, free-access, scoped, limited), referrals (reward on first paid order), wallet credit/debit, abandoned-checkout reminders |
| **User** | Cinematic home sections (trending/newest/continue-watching/recommended/…), browse, search + filters, favorites, watch history + resume, dashboard, support tickets, content reporting, in-app notifications, newsletter |
| **Admin** | Analytics dashboard (revenue/subs/watch-time/preview-conversion), categories + plans, video management (bulk, instant disable, reprocess, subtitles), preview-duration manager, users + access, coupons, reports moderation, payments (approve/refund), settings, countries, homepage/banners/email-templates, roles & permissions, audit logs, system health |
| **Frontend** | Dark glassmorphism UI, Three.js 3D hero with low-end fallback, mobile bottom nav + desktop sidebar, toasts, WhatsApp support button, SEO (OG + VideoObject + sitemap + robots) |

---

## Tech stack

Laravel 12 (PHP 8.2+) · MySQL 8 · Redis (cache/queue/session) · FFmpeg · Flysystem S3 (R2/S3/B2/Spaces) · Laravel Sanctum · Spatie Permission · Google2FA · Blade + Tailwind + Alpine + hls.js + Three.js.

---

## Requirements (production VPS)

- PHP 8.2+ with: `bcmath ctype curl fileinfo gd intl mbstring openssl pdo_mysql redis zip`
- Composer 2.x · MySQL 8 · Redis 6+ · **FFmpeg + ffprobe** · Nginx · Supervisor · Node 18+ (only if rebuilding assets)

---

## Quick start (local)

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure DB + Redis + storage in .env
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

> Zero-dependency spin-up: set `DB_CONNECTION=sqlite`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, `MEDIA_DISK=local`, then `touch database/database.sqlite && php artisan migrate --seed`. For MySQL + Redis locally use `docker compose -f deploy/docker-compose.yml up -d`.

### Seeded test accounts (change before production)

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@maal.test` | `Admin@12345` |
| Content Manager | `manager@maal.test` | `Manager@12345` |
| Premium User | `premium@maal.test` | `Premium@12345` |
| Demo User | `user@maal.test` | `User@12345` |

Override at seed time with `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD`, etc.

---

## VPS deployment

```bash
# 1. Code + deps
git clone <repo> /var/www/maal && cd /var/www/maal
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
# edit .env: APP_URL, DB_*, REDIS_*, MEDIA_DISK + storage creds, PAYU_*, MAIL_*, HLS_SIGNING_KEY

# 2. Database + storage
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache route:cache view:cache

# 3. Permissions
chown -R www-data:www-data storage bootstrap/cache
```

### FFmpeg

```bash
sudo apt-get update && sudo apt-get install -y ffmpeg
# set in .env if non-standard:
# FFMPEG_BINARY=/usr/bin/ffmpeg
# FFPROBE_BINARY=/usr/bin/ffprobe
```

### Object storage

Set `MEDIA_DISK` to `r2` | `s3` | `b2` | `spaces` and fill the matching credentials block in `.env`. Set `CDN_URL` to the public CDN domain fronting the bucket. Originals are stored privately and are **never** served directly — only signed HLS and CDN URLs for derived assets.

### Nginx, Supervisor, cron

Samples live in [`deploy/`](deploy):

- `deploy/nginx.conf` — server block (TLS, gzip, static caching, no-store on `.m3u8`).
- `deploy/supervisor.conf` — one worker group per queue channel (transcoding/previews/thumbnails/uploads/subtitles/notifications/analytics/default).
- `deploy/maal.cron` — the every-minute scheduler entry.

> **Hosting on cPanel?** See [`deploy/CPANEL.md`](deploy/CPANEL.md). The app runs on cPanel, but FFmpeg/Redis/Supervisor are typically unavailable on shared plans — a cPanel VPS (or plain VPS) is strongly recommended.

```bash
sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/maal.conf
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start all
crontab -e   # add the line from deploy/maal.cron
```

### Scheduled tasks (`routes/console.php`)

| Command | Cadence | Purpose |
|---------|---------|---------|
| `maal:cleanup-uploads` | hourly | remove abandoned multipart temp files |
| `maal:expire-access` | daily | expire elapsed category grants |
| `maal:abandoned-checkout` | every 3h | nudge unfinished checkouts |
| `maal:backup` | weekly | gzip mysqldump into `storage/app/backups` |

---

## Backups

`php artisan maal:backup` writes a compressed `mysqldump` to `storage/app/backups` and prunes old copies (`--keep=14`). Media is durable in object storage and is not dumped. Sync `storage/app/backups` offsite (e.g. a separate bucket) for disaster recovery.

---

## Security highlights

CSRF protection, rate limiting (auth/api/webhooks/uploads), hashed passwords, encrypted secrets (2FA, storage keys), signed/expiring HLS tokens, private originals, webhook signature + idempotency verification, device-limit + concurrent-stream enforcement, dynamic watermark, admin 2FA, full audit log, instant content-disable, country allow/block lists, age gate and consent records.

---

## Testing

```bash
php artisan test          # or ./vendor/bin/phpunit
```

The suite covers auth, resumable upload, transcoding orchestration, signed streaming + playback control, payments + webhooks, coupons/referrals/wallet, user features and the admin panel. FFmpeg/network calls are faked, so tests run without those binaries.

---

## Troubleshooting

| Symptom | Check |
|---------|-------|
| Video stuck "processing" | Supervisor workers running? `storage/logs/worker-transcoding.log`; FFmpeg installed & paths correct; **System health** page shows failed jobs |
| HLS won't play | `HLS_SIGNING_KEY` set; `MEDIA_DISK` reachable; `CDN_URL` correct; token not expired (`HLS_URL_TTL`) |
| Uploads fail at a chunk | bucket CORS allows `PUT` from your domain; storage credentials valid; quota not exceeded |
| Payment not captured | gateway enabled + keys set; webhook URL reachable & signature secret correct; check `payment_transactions` |
| "Device limit reached" | raise per-user/global device limit in admin → users / settings |
| Emails not sending | `MAIL_*` config; queue `notifications` worker running |
| 503 maintenance for everyone | admin → Settings → maintenance off (admins bypass) |

Logs: `storage/logs/laravel.log` and per-worker logs under `storage/logs/`.

---

## License

Proprietary. All rights reserved.
