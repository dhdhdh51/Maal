# Maal — Premium Video Streaming Platform

A production-grade, legal, age-gated video streaming platform built with **Laravel 12 / PHP 8.2+**, MySQL 8, Redis, FFmpeg and S3-compatible object storage (Cloudflare R2 / Amazon S3 / Backblaze B2 / DigitalOcean Spaces). Adaptive **HLS** delivery, category-based access locking, resumable uploads, background transcoding and a full admin panel.

> **Status — Foundation phase.** This branch (`develop`) contains the project scaffold, full database schema, domain models, roles/permissions and seed data. Application layers (controllers, services, queue jobs, payment gateways, frontend) are being built incrementally on top of this foundation. See [Roadmap](#roadmap).

---

## Tech stack

| Layer | Choice |
|-------|--------|
| Framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL 8 |
| Cache / Queue / Session | Redis |
| Object storage | Cloudflare R2 / S3 / Backblaze B2 / DO Spaces (S3-compatible via Flysystem) |
| Streaming | FFmpeg → HLS adaptive (360p–4K), signed expiring URLs |
| Auth | Laravel + Sanctum (API tokens), Spatie roles/permissions, Google2FA (admin 2FA) |
| Payments | **PayU (primary)**, Razorpay, Stripe, Manual approval (modular) |

---

## Requirements (production VPS)

- PHP 8.2+ with extensions: `bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `redis`, `zip`
- Composer 2.x
- MySQL 8.x
- Redis 6+
- FFmpeg + ffprobe
- Nginx (or Apache)
- Supervisor (queue workers)
- Node.js 18+ (frontend build)

---

## Quick start (local development)

```bash
# 1. Install dependencies
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Configure DB + Redis + storage in .env (see below)

# 4. Migrate and seed (creates roles, settings, demo content, test accounts)
php artisan migrate --seed

# 5. Storage symlink + serve
php artisan storage:link
php artisan serve
```

> For a quick zero-dependency local spin-up you can set `DB_CONNECTION=sqlite`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` and `touch database/database.sqlite`.

### Test accounts (seeded)

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@maal.test` | `Admin@12345` |
| Content Manager | `manager@maal.test` | `Manager@12345` |
| Premium User | `premium@maal.test` | `Premium@12345` |
| Demo User | `user@maal.test` | `User@12345` |

> Override any of these at seed time with `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD`, etc. **Change all passwords before going to production.**

---

## Configuration

All tunables live in `.env` (see `.env.example` for the full annotated template) and three config files:

- `config/streaming.php` — HLS rendition ladder, FFmpeg paths, preview defaults, upload limits, watermark, dedicated queue channels, processing states.
- `config/payments.php` — gateway registry (PayU primary), webhook hardening.
- `config/filesystems.php` — `local`, `s3`, `r2`, `b2`, `spaces` disks (all private by default).

### Object storage

Set `MEDIA_DISK` to the active provider and fill its credentials block. Originals are stored **privately**; HLS/posters/thumbnails are served via `CDN_URL` or signed routes. Original video files are never publicly accessible.

### Roles & permissions

Five roles: **guest** (unauthenticated), **user**, **premium**, **content_manager**, **admin**. Permissions are defined centrally in `app/Support/Permissions.php` (46 permissions across 10 groups) and seeded by `RolePermissionSeeder`.

---

## Database schema

`php artisan migrate` creates 35 tables. Domain highlights:

- **Catalog:** `categories`, `category_access_plans`, `videos`, `video_files`, `video_previews`, `video_subtitles`, `video_thumbnails`, `video_processing_jobs`
- **Access & billing:** `user_category_access`, `payments`, `payment_transactions`, `coupons`, `coupon_redemptions`, `referrals`
- **Engagement:** `watch_history`, `favorites`, `user_devices`, `user_sessions`, `notifications`, `preview_analytics`
- **Moderation & support:** `reports`, `support_tickets`, `support_ticket_replies`
- **Admin & config:** `settings`, `storage_configurations`, `country_restrictions`, `banners`, `homepage_sections`, `email_templates`, `audit_logs`, `newsletter_subscribers`, `otp_verifications`
- **Auth/infra:** `users`, `roles`, `permissions`, `personal_access_tokens`, `jobs`, `cache`, `sessions`

---

## Queue workers (Supervisor)

Transcoding runs on dedicated queues so the app stays responsive. Channel names are defined in `config/streaming.php`. Example Supervisor program:

```ini
[program:maal-worker-transcoding]
command=php /var/www/maal/artisan queue:work redis --queue=transcoding --tries=3 --backoff=30 --timeout=21600
directory=/var/www/maal
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/maal/storage/logs/worker-transcoding.log
stopwaitsecs=3600
```

Replicate per queue (`uploads`, `thumbnails`, `previews`, `subtitles`, `notifications`, `analytics`, `default`). Full Supervisor + Nginx samples will live in `deploy/` as those layers land.

## Scheduler (cron)

```cron
* * * * * cd /var/www/maal && php artisan schedule:run >> /dev/null 2>&1
```

---

## Roadmap

Built in this foundation phase:
- [x] Laravel scaffold, packages, config (streaming/payments/filesystems)
- [x] Full database schema (35 migrations)
- [x] Eloquent models + relationships
- [x] Roles, permissions and seed data (settings, templates, demo catalog, test accounts)

Next phases:
- [ ] Auth (register/login/OTP/age-gate/2FA) + Sanctum API
- [ ] Resumable multipart upload + storage adapter service
- [ ] FFmpeg transcoding service + queue jobs (HLS, thumbnails, previews, subtitles)
- [ ] Signed HLS streaming + player + watermark + device limits
- [ ] Category access, checkout and payment gateways (PayU first) with secure webhooks
- [ ] Coupons, referrals, offers
- [ ] Admin panel + analytics + moderation
- [ ] Cinematic frontend (dark glassmorphism, Three.js hero, mobile-first)
- [ ] Deploy samples (Nginx, Supervisor), backups, troubleshooting guide

---

## License

Proprietary. All rights reserved.
