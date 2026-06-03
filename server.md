# DeepLink SaaS — Server Documentation

Complete reference for infrastructure, configuration, and architecture of the DeepLink SaaS platform running at `deeplink.trentiums.com`.

---

## Table of Contents

1. [Server Overview](#1-server-overview)
2. [Technology Stack](#2-technology-stack)
3. [Directory Structure](#3-directory-structure)
4. [PHP & Extensions](#4-php--extensions)
5. [PostgreSQL Database](#5-postgresql-database)
6. [Redis](#6-redis)
7. [PHP-FPM](#7-php-fpm)
8. [Nginx Web Server](#8-nginx-web-server)
9. [SSL Certificates — Wildcard via Cloudflare](#9-ssl-certificates--wildcard-via-cloudflare)
10. [Laravel Application](#10-laravel-application)
11. [Multi-Tenancy Architecture](#11-multi-tenancy-architecture)
12. [How Workspace SSL Works](#12-how-workspace-ssl-works)
13. [Environment Variables](#13-environment-variables)
14. [Database Schema](#14-database-schema)
15. [Request Lifecycle](#15-request-lifecycle)
16. [Auto-Renewal Flow](#16-auto-renewal-flow)
17. [Search Engine Blocking](#17-search-engine-blocking)
18. [Queue Worker](#18-queue-worker)
19. [Git Configuration](#19-git-configuration)
20. [Deployment Checklist](#20-deployment-checklist)
21. [Troubleshooting](#21-troubleshooting)

---

## 1. Server Overview

| Property | Value |
|---|---|
| Server IP | `13.205.186.112` |
| OS | Ubuntu 24.04 LTS |
| Primary Domain | `deeplink.trentiums.com` |
| Tenant Subdomain Pattern | `{workspace}.deeplink.trentiums.com` |
| App Root | `/opt/apps/deeplink.trentiums.com/htdocs` |
| Web Root (public) | `/opt/apps/deeplink.trentiums.com/htdocs/public` |

---

## 2. Technology Stack

| Layer | Technology | Version |
|---|---|---|
| Language | PHP | 8.3.6 |
| Framework | Laravel | 13.13.0 |
| Web Server | Nginx | 1.24.0 (Ubuntu) |
| PHP Process Manager | PHP-FPM | 8.3 |
| Database | PostgreSQL | 16.14 |
| Cache / Sessions / Queue | Redis | 7.0.15 |
| Frontend Build | Vite + Node.js | Node 20.20.2 / npm 10.8.2 |
| SSL | Let's Encrypt (Certbot snap 5.6.0) | Wildcard via Cloudflare DNS-01 |
| Package Manager (PHP) | Composer | 2.x |

---

## 3. Directory Structure

```
/opt/apps/deeplink.trentiums.com/htdocs/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Auth/
│   │       │   ├── LoginController.php
│   │       │   ├── RegisterController.php
│   │       │   ├── ForgotPasswordController.php
│   │       │   ├── ResetPasswordController.php
│   │       │   ├── TwoFactorController.php
│   │       │   └── VerifyEmailController.php
│   │       ├── AppController.php
│   │       ├── DashboardController.php
│   │       ├── LinkController.php
│   │       ├── LinkRedirectController.php
│   │       └── WellKnownController.php
│   ├── Models/
│   │   ├── Tenant.php
│   │   ├── Domain.php
│   │   ├── User.php
│   │   ├── TenantUser.php
│   │   ├── Link.php
│   │   ├── LinkClick.php
│   │   └── App.php
│   ├── Providers/
│   │   └── TenancyServiceProvider.php
│   └── Services/
│       ├── TenantRegistrationService.php
│       ├── ShortCodeGenerator.php
│       ├── AssetlinksService.php
│       └── AasaService.php
├── bootstrap/
│   └── cache/                   ← writable by www-data (chmod 775)
├── config/
├── database/
│   └── migrations/
├── public/                      ← Nginx document root
│   ├── index.php
│   └── build/                   ← compiled Vite assets
├── resources/
├── routes/
│   ├── web.php                  ← central domain routes
│   └── tenant.php               ← tenant subdomain routes
├── storage/                     ← writable by www-data (chmod 775)
│   ├── app/
│   ├── framework/
│   └── logs/
├── vendor/                      ← composer packages
├── .env                         ← environment config
├── composer.json
├── package.json
└── vite.config.js
```

### Key Permission Rules

```bash
# Files owned by ubuntu user, group www-data
# storage and bootstrap/cache must be group-writable

sudo chown -R ubuntu:www-data /opt/apps/deeplink.trentiums.com/htdocs/storage
sudo chown -R ubuntu:www-data /opt/apps/deeplink.trentiums.com/htdocs/bootstrap/cache
sudo chmod -R 775 /opt/apps/deeplink.trentiums.com/htdocs/storage
sudo chmod -R 775 /opt/apps/deeplink.trentiums.com/htdocs/bootstrap/cache
```

---

## 4. PHP & Extensions

### Installed Extensions (required)

| Extension | Purpose |
|---|---|
| `pdo_pgsql` | Laravel DB connection to PostgreSQL |
| `pgsql` | Native PostgreSQL driver |
| `redis` | PHP Redis client (phpredis) |
| `bcmath` | Arbitrary precision math (used by Laravel, Razorpay) |
| `gd` | Image processing |
| `xml` / `libxml` / `xmlreader` / `xmlwriter` / `SimpleXML` | XML parsing |
| `mbstring` | Multibyte string handling |
| `curl` | HTTP requests (Socialite, Sentry, Razorpay) |
| `intl` | Internationalization |
| `zip` | Archive handling |
| `pcntl` | Process control (queue workers) |

### Install Command (if setting up fresh)

```bash
sudo apt-get install -y \
  php8.3-pgsql \
  php8.3-redis \
  php8.3-bcmath \
  php8.3-gd \
  php8.3-xml \
  php8.3-mbstring \
  php8.3-curl \
  php8.3-intl \
  php8.3-zip \
  php8.3-fpm
```

---

## 5. PostgreSQL Database

### Connection Details

| Property | Value |
|---|---|
| Host | `127.0.0.1` |
| Port | `5432` |
| Database | `deeplink_saas` |
| Username | `deeplink_user` |
| Password | stored in `.env` as `DB_PASSWORD` |
| Encoding | UTF8 |

### Setup Commands

```sql
-- Run as postgres superuser
CREATE USER deeplink_user WITH PASSWORD 'your_password';
CREATE DATABASE deeplink_saas OWNER deeplink_user;
GRANT ALL PRIVILEGES ON DATABASE deeplink_saas TO deeplink_user;
```

```bash
# Connect
psql -U deeplink_user -d deeplink_saas -h 127.0.0.1

# Via postgres superuser
sudo -u postgres psql deeplink_saas
```

### Run Migrations

```bash
cd /opt/apps/deeplink.trentiums.com/htdocs
php artisan migrate --force
php artisan db:seed --force
```

---

## 6. Redis

### Connection Details

| Property | Value |
|---|---|
| Host | `127.0.0.1` |
| Port | `6379` |
| Password | none (local only) |
| Client | `phpredis` |

### Used For

| Laravel Feature | Redis Key Prefix |
|---|---|
| Sessions | `laravel_session:*` |
| Cache | `laravel_cache:*` |
| Queue | `queues:default` |

### Verify

```bash
redis-cli ping        # → PONG
redis-cli info server | grep redis_version
```

---

## 7. PHP-FPM

### Service

```bash
sudo systemctl status php8.3-fpm
sudo systemctl start php8.3-fpm
sudo systemctl enable php8.3-fpm   # auto-start on boot
```

### Socket

```
/run/php/php8.3-fpm.sock
```

Nginx communicates with PHP via this Unix socket. Faster than TCP (`127.0.0.1:9000`) for same-server setups.

### Pool Config

```
/etc/php/8.3/fpm/pool.d/www.conf
```

Default pool `www` runs as user `www-data`. This is why `storage/` and `bootstrap/cache/` must be group-writable by `www-data`.

---

## 8. Nginx Web Server

### Config File

```
/etc/nginx/sites-available/deeplink.trentiums.com
/etc/nginx/sites-enabled/deeplink.trentiums.com  ← symlink to above
```

### Full Config

```nginx
# Central domain — deeplink.trentiums.com
server {
    server_name deeplink.trentiums.com;

    root /opt/apps/deeplink.trentiums.com/htdocs/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    listen 443 ssl;
    ssl_certificate /etc/letsencrypt/live/deeplink.trentiums.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/deeplink.trentiums.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}

# Tenant subdomains — *.deeplink.trentiums.com
# Regex captures tenant slug into $tenant variable (unused by nginx, used by Laravel)
server {
    server_name ~^(?<tenant>[^.]+)\.deeplink\.trentiums\.com$;

    root /opt/apps/deeplink.trentiums.com/htdocs/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    listen 443 ssl;
    ssl_certificate /etc/letsencrypt/live/deeplink.trentiums.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/deeplink.trentiums.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}

# HTTP → HTTPS redirect for all domains
server {
    listen 80;
    server_name deeplink.trentiums.com ~^(?<tenant>[^.]+)\.deeplink\.trentiums\.com$;
    return 301 https://$host$request_uri;
}
```

### Key Design Decisions

- **Same `root` for both blocks** — both central and tenant traffic hits the same Laravel `public/index.php`. Laravel's tenancy middleware determines context from the hostname.
- **Regex `server_name`** — catches any subdomain. Nginx passes the full `Host` header to PHP; Laravel reads it to identify the tenant.
- **`try_files → index.php`** — standard Laravel SPA-style routing. Static files served directly; everything else goes to PHP.
- **`.well-known` exception** — allows Apple App Site Association and Android Asset Links to be served (needed for deep link verification).
- **Unix socket** — faster than TCP for same-server PHP-FPM communication.

### Nginx Commands

```bash
sudo nginx -t                    # test config syntax
sudo systemctl reload nginx      # reload without dropping connections
sudo systemctl restart nginx     # full restart
sudo systemctl daemon-reload     # needed if service unit file changed
```

---

## 9. SSL Certificates — Wildcard via Cloudflare

### Certificate Details

| Property | Value |
|---|---|
| Certificate Name | `deeplink.trentiums.com` |
| Covers | `deeplink.trentiums.com` + `*.deeplink.trentiums.com` |
| Expiry | 2026-09-01 |
| Key Type | ECDSA |
| Issuer | Let's Encrypt |
| Cert Path | `/etc/letsencrypt/live/deeplink.trentiums.com/fullchain.pem` |
| Key Path | `/etc/letsencrypt/live/deeplink.trentiums.com/privkey.pem` |

### Why Wildcard

A single wildcard cert `*.deeplink.trentiums.com` covers every tenant subdomain (e.g. `milesassist.deeplink.trentiums.com`, `acme.deeplink.trentiums.com`) without issuing a new cert per workspace. Zero SSL provisioning when a tenant registers.

### Cloudflare DNS-01 Challenge

Wildcard certs cannot use HTTP-01 challenge. DNS-01 is required. Certbot uses the Cloudflare DNS plugin to automatically:
1. Add `_acme-challenge.deeplink.trentiums.com` TXT record via Cloudflare API
2. Wait for propagation (120 seconds)
3. Let's Encrypt verifies the TXT record
4. Certbot removes the TXT record
5. Certificate issued

### Credentials File

```
/etc/letsencrypt/cloudflare.ini   (chmod 600, owned by root)
```

```ini
dns_cloudflare_api_token = <your_cloudflare_api_token>
```

> **Do not edit `cloudflare.ini` directly.** The token is managed via `.env` (`CLOUDFLARE_API_TOKEN`). A pre-hook script auto-syncs it to `cloudflare.ini` before every renewal run.

**Cloudflare API Token permissions required:**
- Zone → DNS → Edit
- Zone Resources → Specific zone → trentiums.com

### Pre-Renewal Hook — Token Sync from `.env`

Before every certbot renewal, this script reads `CLOUDFLARE_API_TOKEN` from `.env` and writes it to `cloudflare.ini`. This means rotating the token only requires updating `.env`.

```
/etc/letsencrypt/renewal-hooks/pre/sync-cloudflare-token.sh
```

```bash
#!/bin/bash
ENV_FILE="/opt/apps/deeplink.trentiums.com/htdocs/.env"
INI_FILE="/etc/letsencrypt/cloudflare.ini"

TOKEN=$(grep '^CLOUDFLARE_API_TOKEN=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '"')

if [ -z "$TOKEN" ]; then
    echo "ERROR: CLOUDFLARE_API_TOKEN not found in $ENV_FILE" >&2
    exit 1
fi

echo "dns_cloudflare_api_token = $TOKEN" > "$INI_FILE"
chmod 600 "$INI_FILE"
```

**To rotate the Cloudflare API token:**
1. Generate new token in Cloudflare dashboard
2. Update `CLOUDFLARE_API_TOKEN` in `.env`
3. Run `php artisan config:cache`
4. Done — next renewal picks it up automatically

### Renewal Config

```
/etc/letsencrypt/renewal/deeplink.trentiums.com.conf
```

```ini
version = 5.6.0
archive_dir = /etc/letsencrypt/archive/deeplink.trentiums.com
cert = /etc/letsencrypt/live/deeplink.trentiums.com/cert.pem
privkey = /etc/letsencrypt/live/deeplink.trentiums.com/privkey.pem
chain = /etc/letsencrypt/live/deeplink.trentiums.com/chain.pem
fullchain = /etc/letsencrypt/live/deeplink.trentiums.com/fullchain.pem

[renewalparams]
authenticator = dns-cloudflare
dns_cloudflare_propagation_seconds = 120
dns_cloudflare_credentials = /etc/letsencrypt/cloudflare.ini
server = https://acme-v02.api.letsencrypt.org/directory
key_type = ecdsa
```

### Post-Renewal Hook

After cert renewal, nginx must reload to pick up the new cert:

```
/etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
```

```bash
#!/bin/bash
systemctl reload nginx
```

### Auto-Renewal Timer

Certbot snap installs a systemd timer that runs twice daily:

```bash
systemctl list-timers | grep certbot
# snap.certbot.renew.timer  runs at ~10:32 and ~22:32 UTC
```

### Issue or Force-Renew Manually

```bash
# Issue fresh wildcard cert
sudo certbot certonly \
  --dns-cloudflare \
  --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini \
  --dns-cloudflare-propagation-seconds 120 \
  -d "deeplink.trentiums.com" \
  -d "*.deeplink.trentiums.com" \
  --non-interactive \
  --agree-tos \
  --email info@trentiums.com

# Test renewal without issuing
sudo certbot renew --dry-run

# Force renewal now
sudo certbot renew --force-renewal
```

> **Important:** Use `--email info@trentiums.com` (not bhargav@trentiums.com) when issuing certs from this server. The `bhargav@` email has a Let's Encrypt account conflict on this IP.

---

## 10. Laravel Application

### Key Packages

| Package | Purpose |
|---|---|
| `laravel/framework ^13.8` | Core framework |
| `stancl/tenancy ^3.10` | Multi-tenancy (subdomain-based) |
| `spatie/laravel-permission ^8.0` | Roles & permissions |
| `spatie/laravel-activitylog ^4.12` | Audit log |
| `laravel/socialite ^5.27` | Google OAuth |
| `pragmarx/google2fa-laravel ^3.0` | Two-factor authentication |
| `razorpay/razorpay ^2.9` | Payment processing |
| `predis/predis ^3.5` | Redis client |
| `sentry/sentry-laravel ^4.25` | Error monitoring |

### Initial Setup Commands

```bash
cd /opt/apps/deeplink.trentiums.com/htdocs

composer install --no-interaction
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:clear
php artisan cache:clear
php artisan route:clear

npm install
npm run build
```

### Artisan Commands Reference

```bash
php artisan serve                    # dev server (not for production)
php artisan queue:work               # start queue worker
php artisan queue:work --daemon      # persistent worker
php artisan tinker                   # REPL
php artisan route:list               # list all routes
php artisan config:cache             # cache config for production
php artisan view:cache               # cache blade views
php artisan optimize                 # cache config + routes + views
php artisan optimize:clear           # clear all caches
```

---

## 11. Multi-Tenancy Architecture

### Strategy

This app uses **shared single database** tenancy via `stancl/tenancy`. All tenants share one PostgreSQL database (`deeplink_saas`). Tenant isolation is by `tenant_id` foreign keys, not separate databases.

### How a Tenant is Created

When a user registers a workspace (e.g. "Miles Assist"):

```php
// app/Services/TenantRegistrationService.php

// 1. Create User record
$user = User::create([...]);

// 2. Slugify workspace name → "milesassist"
$slug = $this->uniqueSlug($data['slug']);

// 3. Create Tenant record
$tenant = Tenant::create([
    'id'        => 'milesassist',
    'name'      => 'Miles Assist',
    'plan_slug' => 'free',
]);

// 4. Create Domain record — pattern read from config('tenancy.tenant_url_pattern')
//    NOT env() — env() returns null in production with cached config
$pattern    = config('tenancy.tenant_url_pattern');   // {tenant}.deeplink.trentiums.com
$fullDomain = str_replace('{tenant}', $slug, $pattern);

$tenant->domains()->create([
    'domain'      => $fullDomain,   // milesassist.deeplink.trentiums.com
    'type'        => 'subdomain',
    'is_primary'  => true,
    'status'      => 'active',
    'verified_at' => now(),
]);

// 5. Link User to Tenant as owner
TenantUser::create([
    'tenant_id'   => 'milesassist',
    'user_id'     => $user->id,
    'role'        => 'owner',
    'accepted_at' => now(),
]);
```

### Reserved Slugs (cannot be used as workspace names)

```
www, api, app, admin, mail, ftp, dashboard,
help, blog, status, billing, support, docs,
static, assets, cdn, auth, login, register
```

### Tenant Identification Flow

```
Request → milesassist.deeplink.trentiums.com
       ↓
Nginx regex server_name matches subdomain
       ↓
Routes to Laravel public/index.php
       ↓
Middleware: InitializeTenancyByDomain
       ↓
Lookup domains table WHERE domain = 'milesassist.deeplink.trentiums.com'
       ↓
Load Tenant (id = 'milesassist')
       ↓
TenancyBootstrapped event fires
       ↓
Request proceeds with tenant context
```

### Routes

| File | Domain | Purpose |
|---|---|---|
| `routes/web.php` | `deeplink.trentiums.com` | Registration, login, billing, central dashboard |
| `routes/tenant.php` | `*.deeplink.trentiums.com` | Links, apps, redirect engine, well-known files |

### TenancyServiceProvider

Located at `app/Providers/TenancyServiceProvider.php`. Key configuration:
- **No per-tenant DB** — `Events\TenantCreated` has no jobs attached (no `CreateDatabase` job)
- **Single shared DB strategy** — all data in `deeplink_saas` with `tenant_id` scoping
- **Bootstrapper** — `BootstrapTenancy` listener on `TenancyInitialized` event sets tenant context

### Tenancy Config (`config/tenancy.php`)

`TENANT_URL_PATTERN` is registered as `tenancy.tenant_url_pattern` so it survives `php artisan config:cache`. Using `env()` directly in code breaks in production because Laravel disables `.env` loading when config is cached.

```php
// config/tenancy.php
'tenant_url_pattern' => env('TENANT_URL_PATTERN', '{tenant}.deeplink.trentiums.com'),
```

Always access via `config('tenancy.tenant_url_pattern')`, never `env('TENANT_URL_PATTERN')`.

### Register Page — Workspace URL Preview

`resources/views/auth/register.blade.php` derives the suffix from `TENANT_URL_PATTERN`:

```php
// Shows: your-workspace.deeplink.trentiums.com
.{{ ltrim(str_replace('{tenant}', '', env('TENANT_URL_PATTERN', '{tenant}.deeplink.trentiums.com')), '.') }}
```

---

## 12. How Workspace SSL Works

```
User registers workspace "milesassist"
            │
            ▼
DB: domains table gets row:
    domain = milesassist.deeplink.trentiums.com
            │
            ▼
DNS: *.deeplink.trentiums.com → 13.205.186.112
     (wildcard A record already in Cloudflare — set once, covers all)
            │
            ▼
Nginx: regex server_name catches milesassist.deeplink.trentiums.com
       serves same Laravel app via PHP-FPM
            │
            ▼
SSL: wildcard cert *.deeplink.trentiums.com already covers it
     ZERO additional SSL provisioning needed
            │
            ▼
Result: https://milesassist.deeplink.trentiums.com works instantly
```

**This is why wildcard cert + wildcard DNS = instant secure subdomains at zero cost per tenant.**

---

## 13. Environment Variables

Full `.env` reference showing current **production** values (sensitive values redacted):

```env
APP_NAME="DeepLink SaaS"
APP_ENV=production
APP_KEY=base64:...                      # generated by php artisan key:generate
APP_DEBUG=false                         # never true in production — exposes stack traces
APP_URL=https://deeplink.trentiums.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error                         # only real errors, not debug noise

# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=deeplink_saas
DB_USERNAME=deeplink_user
DB_PASSWORD=<your_db_password>

# Sessions / Cache / Queue → Redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true                    # encrypt session data at rest in Redis
SESSION_PATH=/
SESSION_DOMAIN=.deeplink.trentiums.com  # dot prefix = shared across all subdomains
                                        # critical: without this, tenant subdomain
                                        # sessions are isolated from central login
SESSION_COOKIE=deeplink-session         # explicit name avoids conflict with stale cookies

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
CACHE_STORE=redis

# Redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (currently logs to file — configure SMTP/SES for real email)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@deeplink.io"
MAIL_FROM_NAME="${APP_NAME}"

# Multi-Tenancy
TENANCY_CENTRAL_DOMAINS="deeplink.trentiums.com"
TENANT_URL_PATTERN="{tenant}.deeplink.trentiums.com"

# Payments
RAZORPAY_KEY=<your_key>
RAZORPAY_SECRET=<your_secret>
RAZORPAY_WEBHOOK_SECRET=<your_webhook_secret>

# Google OAuth
GOOGLE_CLIENT_ID=<your_client_id>
GOOGLE_CLIENT_SECRET=<your_client_secret>
GOOGLE_REDIRECT="${APP_URL}/auth/google/callback"

# Error Monitoring
SENTRY_LARAVEL_DSN=<your_dsn>

# Cloudflare — wildcard SSL cert renewal via DNS-01 challenge
CLOUDFLARE_API_TOKEN=<your_cloudflare_api_token>
CLOUDFLARE_ZONE_ID=<your_zone_id>

VITE_APP_NAME="${APP_NAME}"
```

### Key `.env` Values — Why They Matter

| Key | Value | Why |
|---|---|---|
| `APP_ENV` | `production` | enables opcache, disables dev-only features |
| `APP_DEBUG` | `false` | hides stack traces — security critical |
| `LOG_LEVEL` | `error` | only log real errors, not every query/event |
| `SESSION_ENCRYPT` | `true` | session data encrypted in Redis |
| `SESSION_DOMAIN` | `.deeplink.trentiums.com` | dot prefix shares cookie across all subdomains — login on central domain stays active on tenant subdomains |
| `SESSION_COOKIE` | `deeplink-session` | explicit cookie name prevents browser conflicts if the name ever changes (see troubleshooting) |
| `CLOUDFLARE_API_TOKEN` | token | read by pre-hook, written to `cloudflare.ini` before cert renewal |

### Still Needs Configuration

| Key | Action needed |
|---|---|
| `MAIL_MAILER=log` | Change to `smtp`/`ses`/`mailgun` for emails to actually send |
| `RAZORPAY_*` | Fill in for payment processing |
| `GOOGLE_CLIENT_*` | Fill in for Google OAuth login |
| `SENTRY_LARAVEL_DSN` | Fill in for error monitoring |

---

## 14. Database Schema

All 24 tables in `deeplink_saas` (PostgreSQL):

| Table | Purpose |
|---|---|
| `users` | Central user accounts |
| `tenants` | Workspace records (`id` = slug) |
| `domains` | Subdomain/custom domain mappings per tenant |
| `tenant_users` | User ↔ Tenant membership (role: owner/member) |
| `tenant_user_impersonation_tokens` | Admin impersonation |
| `plans` | Subscription plan definitions |
| `apps` | iOS/Android app registrations per tenant |
| `links` | Deep links created per tenant |
| `link_clicks` | Click tracking events |
| `link_events` | Detailed link interaction events |
| `roles` | Spatie permission roles |
| `permissions` | Spatie permission definitions |
| `model_has_roles` | Role assignments |
| `model_has_permissions` | Direct permission assignments |
| `role_has_permissions` | Role ↔ Permission mapping |
| `activity_log` | Audit trail (Spatie activitylog) |
| `sessions` | Laravel DB sessions (Redis used instead) |
| `cache` | Laravel DB cache (Redis used instead) |
| `cache_locks` | Cache lock records |
| `jobs` | Queue job storage |
| `job_batches` | Queue batch tracking |
| `failed_jobs` | Failed queue jobs |
| `password_reset_tokens` | Password reset tokens |
| `migrations` | Laravel migration history |

---

## 15. Request Lifecycle

### Central Domain Request (`deeplink.trentiums.com/register`)

```
Browser → HTTPS request
       ↓
Nginx (port 443, SSL cert validated)
  server_name: deeplink.trentiums.com
       ↓
fastcgi_pass → /run/php/php8.3-fpm.sock
       ↓
PHP-FPM (www-data) → public/index.php
       ↓
Laravel bootstrap
  → no tenancy middleware on central routes
  → web.php routes → RegisterController
       ↓
Response
```

### Tenant Subdomain Request (`milesassist.deeplink.trentiums.com/links`)

```
Browser → HTTPS request
       ↓
Nginx (port 443, wildcard SSL cert *.deeplink.trentiums.com)
  server_name regex: ~^(?<tenant>[^.]+)\.deeplink\.trentiums\.com$
       ↓
fastcgi_pass → /run/php/php8.3-fpm.sock
       ↓
PHP-FPM (www-data) → public/index.php
       ↓
Laravel bootstrap
  → InitializeTenancyByDomain middleware
  → SELECT * FROM domains WHERE domain = 'milesassist.deeplink.trentiums.com'
  → Tenant loaded: id = 'milesassist'
  → TenancyBootstrapped event
  → tenant.php routes → LinkController
       ↓
Response scoped to milesassist tenant
```

### Deep Link Redirect Request (`milesassist.deeplink.trentiums.com/l/abc123`)

```
Browser → GET /l/abc123
       ↓
Nginx → Laravel (tenant context: milesassist)
       ↓
LinkRedirectController@handle
  → lookup links table WHERE short_code = 'abc123' AND tenant_id = 'milesassist'
  → detect platform (iOS/Android/Desktop)
  → redirect to app store / app / fallback URL
  → dispatch LogLinkClick job → Redis queue → worker logs to link_clicks
```

---

## 16. Auto-Renewal Flow

```
snap.certbot.renew.timer fires (twice daily, ~10:32 and ~22:32 UTC)
            │
            ▼
certbot renew checks expiry
  → renews if < 30 days remaining
            │
            ▼
dns-cloudflare plugin:
  → Cloudflare API: add TXT record _acme-challenge.deeplink.trentiums.com
  → wait 120 seconds for propagation
  → Let's Encrypt validates TXT record
  → Cloudflare API: delete TXT record
  → new cert saved to /etc/letsencrypt/live/deeplink.trentiums.com/
            │
            ▼
Deploy hook fires:
  /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
  → systemctl reload nginx
            │
            ▼
Nginx picks up new cert — zero downtime renewal
```

---

## 17. Search Engine Blocking

The entire platform is blocked from search engine indexing at two levels — belt and suspenders.

### Layer 1 — `robots.txt`

```
public/robots.txt
```

```
User-agent: *
Disallow: /
```

Blocks all crawlers (Googlebot, Bingbot, etc.) from indexing any page. This is the first signal crawlers check.

### Layer 2 — HTML Meta Tag

Both layout files include a `noindex, nofollow` meta tag:

```
resources/views/layouts/app.blade.php    ← authenticated app pages
resources/views/layouts/auth.blade.php   ← login / register pages
```

```html
<meta name="robots" content="noindex, nofollow">
```

`noindex` — do not index this page.
`nofollow` — do not follow any links on this page.

This covers cases where crawlers ignore `robots.txt` (rare but possible with some scrapers).

### Verify

```bash
# Check robots.txt
curl https://deeplink.trentiums.com/robots.txt
# Expected: User-agent: *\nDisallow: /

# Check meta tag in HTML
curl -s https://deeplink.trentiums.com/login | grep -i "robots"
# Expected: <meta name="robots" content="noindex, nofollow">
```

---

## 18. Queue Worker

### Overview

Laravel queue jobs (e.g. `LogLinkClick`) are processed by a persistent worker running as a systemd service. The worker connects to Redis and processes jobs from the `default` queue.

### Service File

```
/etc/systemd/system/deeplink-worker.service
```

```ini
[Unit]
Description=DeepLink SaaS Queue Worker
After=network.target redis.service postgresql.service
Requires=redis.service

[Service]
Type=simple
User=ubuntu
Group=ubuntu
WorkingDirectory=/opt/apps/deeplink.trentiums.com/htdocs
ExecStart=/usr/bin/php artisan queue:work redis \
    --sleep=3 \
    --tries=3 \
    --timeout=90 \
    --memory=256 \
    --max-jobs=1000 \
    --max-time=3600
Restart=on-failure
RestartSec=5
StandardOutput=append:/opt/apps/deeplink.trentiums.com/htdocs/storage/logs/worker.log
StandardError=append:/opt/apps/deeplink.trentiums.com/htdocs/storage/logs/worker.log
KillSignal=SIGTERM
TimeoutStopSec=30

[Install]
WantedBy=multi-user.target
```

### Configuration Options Explained

| Flag | Value | Reason |
|---|---|---|
| `connection` | `redis` | matches `QUEUE_CONNECTION=redis` in `.env` |
| `--sleep=3` | 3 seconds | poll interval when queue is empty |
| `--tries=3` | 3 retries | job moves to `failed_jobs` after 3 failures |
| `--timeout=90` | 90 seconds | max execution time per job (exceeds default 60s for heavier jobs) |
| `--memory=256` | 256 MB | worker self-terminates if memory exceeds limit; systemd restarts it |
| `--max-jobs=1000` | 1000 jobs | graceful restart after 1000 jobs to prevent memory creep |
| `--max-time=3600` | 3600 seconds | graceful restart every hour for fresh process |
| `Restart=on-failure` | — | systemd auto-restarts if worker crashes or exits non-zero |
| `RestartSec=5` | 5 seconds | brief pause before restart to avoid rapid crash loops |
| `After=redis.service` | — | systemd waits for Redis before starting worker |

### Worker Log

```
/opt/apps/deeplink.trentiums.com/htdocs/storage/logs/worker.log
```

```bash
tail -f storage/logs/worker.log     # live follow
```

### Service Management Commands

```bash
# Status
sudo systemctl status deeplink-worker

# Start / Stop / Restart
sudo systemctl start deeplink-worker
sudo systemctl stop deeplink-worker
sudo systemctl restart deeplink-worker      # required after every code deploy

# Enable auto-start on boot
sudo systemctl enable deeplink-worker

# Disable auto-start
sudo systemctl disable deeplink-worker

# View systemd journal logs
sudo journalctl -u deeplink-worker -f
sudo journalctl -u deeplink-worker --since "1 hour ago"
```

### Install / Re-install Service

```bash
sudo tee /etc/systemd/system/deeplink-worker.service > /dev/null << 'EOF'
[Unit]
Description=DeepLink SaaS Queue Worker
After=network.target redis.service postgresql.service
Requires=redis.service

[Service]
Type=simple
User=ubuntu
Group=ubuntu
WorkingDirectory=/opt/apps/deeplink.trentiums.com/htdocs
ExecStart=/usr/bin/php artisan queue:work redis \
    --sleep=3 \
    --tries=3 \
    --timeout=90 \
    --memory=256 \
    --max-jobs=1000 \
    --max-time=3600
Restart=on-failure
RestartSec=5
StandardOutput=append:/opt/apps/deeplink.trentiums.com/htdocs/storage/logs/worker.log
StandardError=append:/opt/apps/deeplink.trentiums.com/htdocs/storage/logs/worker.log
KillSignal=SIGTERM
TimeoutStopSec=30

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable deeplink-worker
sudo systemctl start deeplink-worker
sudo chown ubuntu:ubuntu storage/logs/worker.log
```

### Jobs That Use the Queue

| Job | Trigger | Purpose |
|---|---|---|
| `LogLinkClick` | Deep link redirect | Async click tracking → `link_clicks` table |

### Failed Jobs

Jobs that fail all 3 retries land in the `failed_jobs` table.

```bash
# List failed jobs
php artisan queue:failed

# Retry a specific failed job
php artisan queue:retry <id>

# Retry all failed jobs
php artisan queue:retry all

# Flush all failed jobs
php artisan queue:flush
```

### Deploy Workflow

After every code deployment, restart the worker so it picks up new code:

```bash
git pull origin main
composer install --no-interaction
php artisan migrate --force
php artisan optimize:clear
npm run build
sudo systemctl restart deeplink-worker
```

---

## 19. Git Configuration

### Repository

| Property | Value |
|---|---|
| GitHub Repo | `https://github.com/bhargav960143/deep-link` |
| Default Branch | `main` |
| Local Path | `/opt/apps/deeplink.trentiums.com/htdocs` |

### Global Config (`/home/ubuntu/.gitconfig`)

```ini
[user]
    name  = Bhargav
    email = bhargav@trentiums.com

[credential]
    helper = store          # credentials cached in ~/.git-credentials

[safe]
    directory = /opt/apps/deeplink.trentiums.com/htdocs
    directory = /opt/apps/gmb-automation
```

### Remote Authentication

Auth uses a **GitHub Personal Access Token (PAT)** embedded in the remote URL:

```bash
# View current remote (token redacted)
git remote get-url origin
# https://<PAT>@github.com/bhargav960143/deep-link.git

# Update remote when PAT expires
git remote set-url origin https://<NEW_PAT>@github.com/bhargav960143/deep-link.git
```

PAT is stored in the remote URL via `credential.helper=store`. When it expires, regenerate at:
**GitHub → Settings → Developer Settings → Personal Access Tokens → Fine-grained tokens**

Required PAT permissions:
- `Contents` → Read & Write
- `Metadata` → Read

### Common Git Commands

```bash
# Pull latest
git pull origin main

# Check status
git status

# Stage and commit
git add <files>
git commit -m "type(scope): message"

# Push
git push origin main

# View recent commits
git log --oneline -10

# View what changed in last commit
git show --stat HEAD
```

### Commit Message Convention

Project follows Conventional Commits:

```
<type>(<scope>): <subject>

Types: feat | fix | docs | refactor | test | chore
```

Examples:
```
feat(links): add custom alias support
fix(auth): correct session expiry check
docs(server): update queue worker section
```

### Deploy Workflow (full)

```bash
cd /opt/apps/deeplink.trentiums.com/htdocs

git pull origin main
composer install --no-interaction --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
npm ci
npm run build
sudo systemctl restart deeplink-worker
sudo systemctl reload nginx
```

---

## 20. Deployment Checklist

Use this when setting up on a fresh server.

### System Packages

```bash
sudo apt-get update
sudo apt-get install -y \
  nginx \
  postgresql postgresql-client \
  redis-server \
  php8.3-fpm php8.3-cli \
  php8.3-pgsql php8.3-redis php8.3-bcmath \
  php8.3-gd php8.3-xml php8.3-mbstring \
  php8.3-curl php8.3-intl php8.3-zip

# Certbot via snap
sudo snap install --classic certbot
sudo snap install certbot-dns-cloudflare
sudo snap set certbot trust-plugin-with-root=ok
sudo snap connect certbot:plugin certbot-dns-cloudflare
```

### Composer & Node

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node via nvm or nodesource
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### PostgreSQL Setup

```bash
sudo -u postgres psql -c "CREATE USER deeplink_user WITH PASSWORD 'your_password';"
sudo -u postgres psql -c "CREATE DATABASE deeplink_saas OWNER deeplink_user;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE deeplink_saas TO deeplink_user;"
```

### Application Setup

```bash
cd /opt/apps/deeplink.trentiums.com/htdocs
cp .env.example .env

# Required .env values to set:
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://deeplink.trentiums.com
# DB_CONNECTION=pgsql, DB_HOST, DB_PORT=5432, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# SESSION_DOMAIN=.deeplink.trentiums.com
# SESSION_ENCRYPT=true
# SESSION_COOKIE=deeplink-session
# LOG_LEVEL=error
# TENANCY_CENTRAL_DOMAINS="deeplink.trentiums.com"
# TENANT_URL_PATTERN="{tenant}.deeplink.trentiums.com"
# CLOUDFLARE_API_TOKEN=<token>
# CLOUDFLARE_ZONE_ID=<zone_id>

composer install --no-interaction
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

npm install
npm run build

sudo chown -R ubuntu:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Nginx & SSL

```bash
# Copy nginx config to /etc/nginx/sites-available/deeplink.trentiums.com
# Create symlink
sudo ln -s /etc/nginx/sites-available/deeplink.trentiums.com \
           /etc/nginx/sites-enabled/deeplink.trentiums.com

# Start PHP-FPM
sudo systemctl enable --now php8.3-fpm

# Setup Cloudflare credentials (token managed via .env — see pre-hook below)
sudo tee /etc/letsencrypt/cloudflare.ini > /dev/null <<EOF
dns_cloudflare_api_token = <your_cloudflare_api_token>
EOF
sudo chmod 600 /etc/letsencrypt/cloudflare.ini

# Pre-hook: auto-sync token from .env to cloudflare.ini before every renewal
sudo tee /etc/letsencrypt/renewal-hooks/pre/sync-cloudflare-token.sh > /dev/null << 'HOOK'
#!/bin/bash
ENV_FILE="/opt/apps/deeplink.trentiums.com/htdocs/.env"
INI_FILE="/etc/letsencrypt/cloudflare.ini"
TOKEN=$(grep '^CLOUDFLARE_API_TOKEN=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '"')
if [ -z "$TOKEN" ]; then echo "ERROR: CLOUDFLARE_API_TOKEN not found" >&2; exit 1; fi
echo "dns_cloudflare_api_token = $TOKEN" > "$INI_FILE"
chmod 600 "$INI_FILE"
HOOK
sudo chmod +x /etc/letsencrypt/renewal-hooks/pre/sync-cloudflare-token.sh

# Issue wildcard cert
sudo certbot certonly \
  --dns-cloudflare \
  --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini \
  --dns-cloudflare-propagation-seconds 120 \
  -d "deeplink.trentiums.com" \
  -d "*.deeplink.trentiums.com" \
  --non-interactive --agree-tos \
  --email info@trentiums.com

# Add nginx reload hook
sudo tee /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh > /dev/null <<'EOF'
#!/bin/bash
systemctl reload nginx
EOF
sudo chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh

# Start nginx
sudo systemctl enable --now nginx
sudo nginx -t && sudo systemctl reload nginx
```

### DNS (Cloudflare)

| Type | Name | Value | Proxy |
|---|---|---|---|
| A | `deeplink` | `13.205.186.112` | DNS only (grey cloud) |
| A | `*.deeplink` | `13.205.186.112` | DNS only (grey cloud) |

> DNS proxy must be **off** (grey cloud) for SSL-01 cert validation and for nginx to terminate SSL directly.

---

## 21. Troubleshooting

### 403 Forbidden

```bash
# Check nginx root path
grep -n "root" /etc/nginx/sites-available/deeplink.trentiums.com
# Must be: /opt/apps/deeplink.trentiums.com/htdocs/public

# Check PHP-FPM running
systemctl is-active php8.3-fpm

# Check storage permissions
ls -la /opt/apps/deeplink.trentiums.com/htdocs/storage/
# Must be writable by www-data
```

### 502 Bad Gateway

```bash
# PHP-FPM not running
sudo systemctl restart php8.3-fpm

# Wrong socket path in nginx
ls /run/php/  # should show php8.3-fpm.sock
```

### SSL Certificate Error on Subdomain

```bash
# Check wildcard cert covers *.deeplink.trentiums.com
sudo openssl x509 -in /etc/letsencrypt/live/deeplink.trentiums.com/fullchain.pem \
  -noout -text | grep DNS
# Must show: DNS:*.deeplink.trentiums.com, DNS:deeplink.trentiums.com
```

### Tenant Not Found (500 on subdomain)

```bash
# Check domain exists in DB
sudo -u postgres psql deeplink_saas -c \
  "SELECT * FROM domains WHERE domain = 'milesassist.deeplink.trentiums.com';"

# Check tenant exists
sudo -u postgres psql deeplink_saas -c \
  "SELECT * FROM tenants WHERE id = 'milesassist';"
```

### Certbot Renewal Fails

```bash
# Test Cloudflare API token works
curl -s -X GET "https://api.cloudflare.com/client/v4/zones/045b3df665820761d1a6caa5b670df52/dns_records?type=TXT" \
  -H "Authorization: Bearer <your_token>" | python3 -m json.tool | head -5

# Check logs
sudo tail -50 /var/log/letsencrypt/letsencrypt.log

# Dry run
sudo certbot renew --dry-run
```

### Laravel Config Not Updating

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
# Or all at once:
php artisan optimize:clear
```

### Login Loop (POST /login succeeds but redirects back to /login)

**Symptom:** Browser submits login form → 302 → GET /dashboard → 302 back to /login. Curl test works fine.

**Root cause:** Browser has a stale session cookie from a previous session name or before a Redis flush. The server creates a new authenticated session after login and sets a new cookie, but the browser sends the old (now invalid) cookie on the /dashboard request. The server finds no session for that old cookie ID and treats the user as unauthenticated.

**Fix — rename the session cookie** (forces all browsers to use the new cookie name, old ones ignored):

```bash
# In .env — add or change:
SESSION_COOKIE=deeplink-session

# Then:
php artisan config:clear && php artisan config:cache
```

**Fix — flush Redis** (clears all stale sessions, users must log in again):

```bash
redis-cli -n 0 FLUSHDB
php artisan cache:clear && php artisan config:cache
```

> **Note:** If `SESSION_ENCRYPT` is changed (enabled or disabled), all existing browser session cookies become undecryptable. Flush Redis to clear them.

### Queue Worker Not Processing

```bash
# Check service status
sudo systemctl status deeplink-worker

# Check Redis queue depth
redis-cli llen queues:default

# Check worker log
tail -50 storage/logs/worker.log

# Restart service
sudo systemctl restart deeplink-worker

# Start worker manually (debug mode)
php artisan queue:work redis --tries=3 -vvv
```

---

*Last updated: 2026-06-03 — production env hardened, search engine blocking, Cloudflare token via .env, tenancy config fix, session cookie renamed to fix login loop*
