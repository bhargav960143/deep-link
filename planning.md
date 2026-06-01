# Deep Link SaaS — Complete Planning Document

> Stack: Laravel 11, Blade, MySQL 8, Redis, Supervisor (Linux), Razorpay
> Multi-tenancy: `stancl/tenancy` (domain-based)
> Target: Replace Firebase Dynamic Links as SaaS
> Region: India — Razorpay for billing, Supervisor (supervisord) for queue workers

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Market Research](#2-market-research)
3. [Tech Stack & Rationale](#3-tech-stack--rationale)
4. [Architecture Overview](#4-architecture-overview)
5. [Database Schema](#5-database-schema)
6. [Multi-Tenancy Strategy](#6-multi-tenancy-strategy)
7. [Phase 1 — Foundation & Auth](#7-phase-1--foundation--auth)
8. [Phase 2 — App Registration](#8-phase-2--app-registration)
9. [Phase 3 — Well-Known File Serving](#9-phase-3--well-known-file-serving)
10. [Phase 4 — Link Management](#10-phase-4--link-management)
11. [Phase 5 — Landing Page & Redirect Engine](#11-phase-5--landing-page--redirect-engine)
12. [Phase 6 — Custom Domain + SSL](#12-phase-6--custom-domain--ssl)
13. [Phase 7 — Analytics](#13-phase-7--analytics)
14. [Phase 8 — REST API & Webhooks](#14-phase-8--rest-api--webhooks)
15. [Phase 9 — Billing & Plans](#15-phase-9--billing--plans)
16. [Phase 10 — Security Hardening](#16-phase-10--security-hardening)
17. [Phase 11 — Testing Strategy](#17-phase-11--testing-strategy)
18. [Phase 12 — Deployment & DevOps](#18-phase-12--deployment--devops)
19. [Complete E2E Flow](#19-complete-e2e-flow)
20. [Edge Cases Registry](#20-edge-cases-registry)
21. [Validation Rules](#21-validation-rules)
22. [API Reference](#22-api-reference)

---

## 1. Executive Summary

Firebase Dynamic Links shutdown (Aug 2025) left ~500k apps without a deep link solution. Branch.io costs $500+/mo. No affordable, self-serve SaaS exists for indie devs and small teams.

**Product:** Multi-tenant SaaS where clients register their mobile apps, create short links, and get Universal Links (iOS) + App Links (Android) + URI scheme fallback — all served from our infrastructure or their custom domain.

**Revenue model:** Freemium (100 links/mo free) → Pro ($29/mo) → Business ($99/mo) → Enterprise (custom)

---

## 2. Market Research

### Competitors

| Product | Price | Deep Link Type | Weakness |
|---|---|---|---|
| Firebase Dynamic Links | Free (dead) | Universal + URI | Shutdown Aug 2025 |
| Branch.io | $500+/mo | Universal + URI + deferred | Too expensive for small devs |
| Adjust | Enterprise | Universal + URI | Enterprise-only |
| AppsFlyer | Enterprise | Universal + URI | Enterprise-only |
| Bitly | $35/mo | No Universal Links | No mobile-native routing |
| Short.io | $25/mo | No Universal Links | No AASA/assetlinks |

### Gap

Affordable ($0–$99/mo), self-serve, Universal Links + App Links + URI scheme, custom domain support. **That product does not exist.**

### How Universal Links Work (iOS)

1. App declares associated domain in Xcode: `applinks:yourdomain.com`
2. On first app install, iOS fetches `https://yourdomain.com/.well-known/apple-app-site-association`
3. iOS caches which paths this app handles
4. User taps `https://yourdomain.com/l/abc123` → iOS opens app directly (no browser)
5. App receives the full URL, extracts path, navigates to screen

### How App Links Work (Android)

1. App declares intent filter with `android:autoVerify="true"` in manifest
2. On install, Android fetches `https://yourdomain.com/.well-known/assetlinks.json`
3. Verifies SHA-256 fingerprint matches installed app signing cert
4. User taps link → Android opens app directly

### URI Scheme Fallback

- `myapp://path/to/screen` — older approach
- No OS verification needed
- Works if Universal Links fail or app not configured
- Cannot open App Store if app not installed (just fails silently)
- Used as JS fallback: attempt URI scheme → timeout → redirect to store

---

## 3. Tech Stack & Rationale

### Backend
- **Laravel 11** — battle-tested, rich ecosystem, excellent multi-tenancy support
- **PHP 8.3** — typed properties, readonly classes, fibers
- **MySQL 8.0** — JSON columns for fingerprint arrays, UUID support, window functions for analytics
- **Redis** — queue driver, cache driver, rate limiting, session storage
- **Supervisor (supervisord)** — Linux process manager for queue workers. No Horizon. Multiple `queue:work` workers managed via `/etc/supervisor/conf.d/`. Restart on crash, logging to `/var/log/supervisor/`.
- **Razorpay** — Indian payment gateway. Supports subscriptions, INR + international cards, UPI, netbanking. No `laravel/cashier` equivalent — use `razorpay/razorpay` PHP SDK with custom billing logic.

### Frontend
- **Blade** — server-rendered templates (SSR required for OG meta crawlers)
- **Alpine.js** — lightweight reactivity for dashboard interactions (no Vue/React overhead)
- **Tailwind CSS** — utility-first, no custom CSS files
- **Chart.js** — analytics charts (CDN loaded)

### Infrastructure
- **Apache2** — web server, wildcard VirtualHost for `*.deeplink.io`, `mod_rewrite` + `mod_headers` + `mod_ssl`
- **Certbot** — Let's Encrypt SSL for custom tenant domains (wildcard `*.deeplink.io` handled by Cloudflare)
- **Supervisor (supervisord)** — Linux process manager. Runs multiple `php artisan queue:work` processes. Config at `/etc/supervisor/conf.d/deeplink-worker.conf`. Auto-restarts crashed workers. No Horizon needed.
- **Cloudflare** — CDN, DDoS protection, wildcard SSL for `*.deeplink.io` subdomains
- **AWS Lightsail Object Storage** — optional v2+ only (OG image uploads). Not required v1.

### Packages
```
stancl/tenancy                  # Multi-tenancy (domain-based)
razorpay/razorpay               # Razorpay PHP SDK (billing, subscriptions)
spatie/laravel-permission       # Role/permission (tenant admin, member)
spatie/laravel-activitylog      # Audit log
spatie/laravel-rate-limited-job-middleware  # Queue rate limiting
league/uri                      # URI parsing and validation
pragmarx/google2fa-laravel      # 2FA
laravel/socialite               # OAuth (Google login)
sentry/sentry-laravel           # Error monitoring
```

---

## 4. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        CENTRAL APP                              │
│   app.deeplink.io  →  Auth, Dashboard, Billing, Settings        │
│   Laravel app on central DB                                     │
└─────────────────────────────┬───────────────────────────────────┘
                              │ tenant identified by domain
              ┌───────────────┼───────────────┐
              ▼               ▼               ▼
    ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
    │ acme.deeplink│  │ beta.deeplink│  │links.client  │
    │    .io       │  │    .io       │  │   .com       │
    │  (subdomain) │  │  (subdomain) │  │(custom domain│
    └──────┬───────┘  └──────┬───────┘  └──────┬───────┘
           │                 │                  │
           ▼                 ▼                  ▼
    ┌──────────────────────────────────────────────────┐
    │              TENANT ROUTING LAYER                │
    │   /.well-known/apple-app-site-association        │
    │   /.well-known/assetlinks.json                   │
    │   /{shortCode}  →  Landing Page / Redirect       │
    └──────────────────────────────────────────────────┘
                              │
                              ▼
    ┌──────────────────────────────────────────────────┐
    │              ANALYTICS PIPELINE                  │
    │   Click event → Redis queue → DB write           │
    │   (async, non-blocking, does not slow redirect)  │
    └──────────────────────────────────────────────────┘
```

### Request Flow Summary

```
DNS (wildcard *.deeplink.io)  →  Nginx  →  PHP-FPM  →  Laravel
                                           (identifies tenant by Host header)
```

---

## 5. Database Schema

### Central Database (non-tenant tables)

#### `users`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
uuid            CHAR(36) UNIQUE NOT NULL
name            VARCHAR(255) NOT NULL
email           VARCHAR(255) UNIQUE NOT NULL
password        VARCHAR(255) NOT NULL          -- bcrypt hashed
email_verified_at TIMESTAMP NULL
two_factor_secret VARCHAR(255) NULL            -- encrypted
two_factor_recovery_codes TEXT NULL            -- encrypted
google_id       VARCHAR(255) NULL              -- Socialite
avatar_url      VARCHAR(500) NULL
remember_token  VARCHAR(100) NULL
last_login_at   TIMESTAMP NULL
last_login_ip   VARCHAR(45) NULL               -- IPv6 safe
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP NULL                 -- soft delete

INDEX: email, uuid
```

#### `tenants`
```sql
id              VARCHAR(255) PK                -- slug e.g. "acmecorp"
data            JSON NULL                      -- stancl/tenancy storage
created_at      TIMESTAMP
updated_at      TIMESTAMP

-- stancl/tenancy stores tenant data in JSON column
-- We extend with custom fields via TenantModel
```

#### `tenant_users` (pivot)
```sql
id              BIGINT UNSIGNED PK
tenant_id       VARCHAR(255) FK tenants.id
user_id         BIGINT UNSIGNED FK users.id
role            ENUM('owner','admin','member') DEFAULT 'member'
invited_by      BIGINT UNSIGNED FK users.id NULL
invited_at      TIMESTAMP NULL
accepted_at     TIMESTAMP NULL
created_at      TIMESTAMP
updated_at      TIMESTAMP

UNIQUE: (tenant_id, user_id)
INDEX: tenant_id, user_id
```

#### `domains`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       VARCHAR(255) FK tenants.id
domain          VARCHAR(255) UNIQUE NOT NULL   -- e.g. "acme.deeplink.io" or "links.acme.com"
type            ENUM('subdomain','custom') DEFAULT 'subdomain'
is_primary      BOOLEAN DEFAULT FALSE
verification_token VARCHAR(64) NULL            -- for custom domain DNS TXT verification
verified_at     TIMESTAMP NULL
ssl_issued_at   TIMESTAMP NULL
ssl_expires_at  TIMESTAMP NULL
status          ENUM('pending','verifying','active','failed','suspended') DEFAULT 'pending'
created_at      TIMESTAMP
updated_at      TIMESTAMP

INDEX: tenant_id, domain, status
```

#### `plans`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            VARCHAR(255) NOT NULL          -- 'Free', 'Pro', 'Business'
stripe_price_id VARCHAR(255) UNIQUE NULL
price_monthly   DECIMAL(10,2) NOT NULL
price_yearly    DECIMAL(10,2) NOT NULL
links_limit     INT NOT NULL DEFAULT 100       -- -1 = unlimited
clicks_limit    INT NOT NULL DEFAULT 10000     -- -1 = unlimited
apps_limit      INT NOT NULL DEFAULT 1
custom_domains  BOOLEAN DEFAULT FALSE
api_access      BOOLEAN DEFAULT FALSE
webhooks        BOOLEAN DEFAULT FALSE
analytics_retention_days INT DEFAULT 30
is_active       BOOLEAN DEFAULT TRUE
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `subscriptions` (managed by Laravel Cashier)
```sql
-- Standard Cashier table, no custom changes needed
-- tenant has stripe_id, pm_type, pm_last_four on tenants JSON
```

### Tenant Database (per-tenant tables, or shared with tenant_id scope)

> Strategy: Single shared database, all tenant tables have `tenant_id` FK + RLS via global scope.
> (Separate DB per tenant = too expensive at start; migrate later if needed)

#### `apps`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
uuid            CHAR(36) UNIQUE NOT NULL
tenant_id       VARCHAR(255) FK tenants.id NOT NULL
name            VARCHAR(255) NOT NULL           -- "My App iOS"
platform        ENUM('ios','android','both') NOT NULL
-- iOS fields
ios_bundle_id   VARCHAR(255) NULL               -- com.example.myapp
ios_team_id     VARCHAR(20) NULL                -- 10-char Apple Team ID
ios_app_id      VARCHAR(300) NULL               -- computed: TEAMID.BUNDLEID
ios_store_url   VARCHAR(500) NULL               -- https://apps.apple.com/app/id...
ios_min_version VARCHAR(20) NULL                -- optional: min app version
-- Android fields
android_package_name VARCHAR(255) NULL          -- com.example.myapp
android_sha256_fingerprints JSON NULL           -- array of fingerprint strings
android_store_url VARCHAR(500) NULL             -- https://play.google.com/...
-- Shared
uri_scheme      VARCHAR(50) NULL                -- "myapp" (without ://)
web_fallback_url VARCHAR(500) NULL              -- fallback if no app
default_domain_id BIGINT UNSIGNED FK domains.id NULL
is_active       BOOLEAN DEFAULT TRUE
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP NULL

INDEX: tenant_id, uuid, platform
```

#### `links`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
uuid            CHAR(36) UNIQUE NOT NULL
tenant_id       VARCHAR(255) FK tenants.id NOT NULL
app_id          BIGINT UNSIGNED FK apps.id NOT NULL
domain_id       BIGINT UNSIGNED FK domains.id NOT NULL
short_code      VARCHAR(20) NOT NULL            -- nanoid, e.g. "xK9mP2"
-- Destination
destination_path VARCHAR(2000) NOT NULL         -- deep link path, e.g. "/products/123"
web_fallback_url VARCHAR(500) NULL              -- override app-level fallback
-- OG Meta
og_title        VARCHAR(255) NULL
og_description  VARCHAR(500) NULL
og_image_url    VARCHAR(500) NULL
og_image_width  SMALLINT NULL
og_image_height SMALLINT NULL
-- Options
link_type       ENUM('universal','uri_scheme','both') DEFAULT 'both'
is_active       BOOLEAN DEFAULT TRUE
password        VARCHAR(255) NULL               -- bcrypt, optional link password
expires_at      TIMESTAMP NULL
max_clicks      INT UNSIGNED NULL               -- null = unlimited
click_count     INT UNSIGNED DEFAULT 0          -- denormalized counter
-- Metadata
title           VARCHAR(255) NULL               -- internal name for dashboard
tags            JSON NULL                       -- ["campaign","summer-sale"]
utm_source      VARCHAR(255) NULL
utm_medium      VARCHAR(255) NULL
utm_campaign    VARCHAR(255) NULL
created_by      BIGINT UNSIGNED FK users.id NULL
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP NULL

UNIQUE: (tenant_id, short_code, domain_id)
INDEX: tenant_id, app_id, domain_id, short_code, is_active, expires_at
```

#### `link_clicks`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
link_id         BIGINT UNSIGNED FK links.id NOT NULL
clicked_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
-- Device
platform        ENUM('ios','android','desktop','bot','unknown') NOT NULL
os_version      VARCHAR(50) NULL
browser         VARCHAR(100) NULL
device_type     ENUM('mobile','tablet','desktop','bot') NOT NULL
-- Outcome
outcome         ENUM('app_opened','store_redirect_ios','store_redirect_android','web_fallback','link_expired','link_inactive','password_required','max_clicks_reached','bot_filtered') NOT NULL
-- Location
country_code    CHAR(2) NULL
region          VARCHAR(100) NULL
city            VARCHAR(100) NULL
-- Source
ip_hash         VARCHAR(64) NOT NULL            -- SHA-256 of IP, never store raw IP
referrer_domain VARCHAR(255) NULL               -- only domain, not full URL
utm_source      VARCHAR(255) NULL
utm_medium      VARCHAR(255) NULL
utm_campaign    VARCHAR(255) NULL
-- Internal
is_unique       BOOLEAN DEFAULT FALSE           -- unique per ip_hash per link per 24h
created_at      TIMESTAMP

INDEX: link_id, clicked_at, platform, outcome, country_code
PARTITION BY RANGE (YEAR(clicked_at))          -- for analytics performance
```

#### `api_keys`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       VARCHAR(255) FK tenants.id NOT NULL
name            VARCHAR(255) NOT NULL           -- "Production API Key"
key_prefix      VARCHAR(8) NOT NULL             -- "dl_live_" shown in UI
key_hash        VARCHAR(64) NOT NULL            -- SHA-256 of actual key
last_used_at    TIMESTAMP NULL
last_used_ip    VARCHAR(45) NULL
expires_at      TIMESTAMP NULL
scopes          JSON NULL                       -- ["links:read","links:write","analytics:read"]
is_active       BOOLEAN DEFAULT TRUE
created_by      BIGINT UNSIGNED FK users.id NOT NULL
created_at      TIMESTAMP
updated_at      TIMESTAMP

INDEX: tenant_id, key_hash
```

#### `webhooks`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       VARCHAR(255) FK tenants.id NOT NULL
name            VARCHAR(255) NOT NULL
url             VARCHAR(500) NOT NULL           -- HTTPS only, validated against SSRF
secret          VARCHAR(255) NOT NULL           -- for HMAC-SHA256 signature
events          JSON NOT NULL                  -- ["link.clicked","link.created"]
is_active       BOOLEAN DEFAULT TRUE
failure_count   TINYINT UNSIGNED DEFAULT 0     -- disable after 10 consecutive failures
last_triggered_at TIMESTAMP NULL
last_response_code SMALLINT NULL
created_at      TIMESTAMP
updated_at      TIMESTAMP

INDEX: tenant_id, is_active
```

#### `webhook_deliveries`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
webhook_id      BIGINT UNSIGNED FK webhooks.id NOT NULL
event           VARCHAR(100) NOT NULL
payload         JSON NOT NULL
response_code   SMALLINT NULL
response_body   TEXT NULL
duration_ms     SMALLINT NULL
attempt         TINYINT UNSIGNED DEFAULT 1
delivered_at    TIMESTAMP NULL
failed_at       TIMESTAMP NULL
created_at      TIMESTAMP

INDEX: webhook_id, event, created_at
```

#### `activity_logs` (spatie/activitylog)
```sql
-- Standard spatie table
-- Log: link created/deleted, app config changed, domain added, API key created
```

---

## 6. Multi-Tenancy Strategy

### Package: `stancl/tenancy`

**Mode:** Domain-based tenant identification. Each request, the middleware reads `Host` header, looks up `domains` table, identifies tenant, initializes tenant context.

### Subdomain Strategy (Phase 1)

- Pattern: `{slug}.deeplink.io`
- Wildcard DNS: `*.deeplink.io → server IP`
- Wildcard SSL: Cloudflare handles (free, automatic)
- Slug: lowercase, alphanumeric + hyphens, 3–30 chars, unique

### Custom Domain Strategy (Phase 2)

- Client sets DNS CNAME: `links.theircorp.com → deeplink.io`
- We verify ownership via DNS TXT record: `_deeplink-verify.links.theircorp.com = {verification_token}`
- After verification: issue Let's Encrypt cert via ACME HTTP-01 challenge
- Nginx config dynamically updated (or use Nginx + OpenResty with Lua for runtime config)

### Tenant Initialization

```php
// TenantMiddleware resolves tenant from Host header
// Sets: Tenancy::initialize($tenant)
// All models with HasTenantScopes auto-filter by tenant_id
// GlobalScope added on all tenant models: WHERE tenant_id = current_tenant_id
```

### Data Isolation Rules

- Every tenant model has `tenant_id` column
- `TenantScope` global scope applied via `BootsTenantScopes` trait
- Policies double-check ownership on every controller action
- Foreign key queries always include `tenant_id` (prevent ID oracle attacks)
- Never expose internal integer IDs in URLs — use UUIDs

---

## 7. Phase 1 — Foundation & Auth

### 7.1 Project Setup

```bash
composer create-project laravel/laravel deeplink-saas
composer require stancl/tenancy razorpay/razorpay spatie/laravel-permission
composer require spatie/laravel-activitylog pragmarx/google2fa-laravel
composer require league/uri sentry/sentry-laravel
npm install -D tailwindcss alpinejs
```

### 7.2 Authentication Screens

#### Registration Flow
1. User lands on `app.deeplink.io/register`
2. Form: Name, Email, Password, Password Confirm, Company/App Name (= tenant slug suggestion)
3. Submit → validate → create user → create tenant → create subdomain → send verification email
4. Redirect to `/dashboard` with banner: "Check your email to verify your account"

#### Fields & Validation
| Field | Rules |
|---|---|
| name | required, string, min:2, max:255 |
| email | required, email:rfc,dns, unique:users, max:255 |
| password | required, min:8, confirmed, regex: at least 1 uppercase + 1 number |
| company_name | required, string, min:2, max:100 |
| slug | auto-generated from company_name, user can edit, regex:/^[a-z0-9-]{3,30}$/, unique:domains |

#### Slug Generation
```php
// "Acme Corp!" → "acme-corp" → check unique → if taken: "acme-corp-2"
Str::slug($companyName)
// Reserved slugs: www, api, app, admin, mail, ftp, dashboard, help, blog, status
```

#### Email Verification
- Use Laravel's built-in `MustVerifyEmail`
- Resend verification: rate limited (1 per minute per email)
- Verification link expires: 60 minutes
- Unverified users: can browse dashboard but cannot create links (banner shown)

#### Login Flow
1. `app.deeplink.io/login`
2. Email + Password → remember me option
3. Rate limit: 5 attempts per minute per IP, 10 per minute per email
4. On success → redirect to `/dashboard`
5. 2FA prompt if enabled (after password correct)

#### 2FA (Optional)
- TOTP via Google Authenticator
- Setup: show QR code, confirm with 6-digit code before enabling
- Recovery codes: 8 codes, each single-use, download/copy on setup
- If 2FA device lost: recovery code flow → disable 2FA → re-setup

#### Password Reset
- Rate limit: 3 requests per hour per email
- Token expires: 60 minutes
- Single use (invalidated after use)
- Notify user via email if password changed (security alert)

#### Google OAuth (via Socialite)
- Button on login/register page
- If email already exists: link accounts, log in
- If new email: create user + tenant (prompt for company name after OAuth)
- No password required for OAuth users

### 7.3 Dashboard Layout (Blade)

```
┌─────────────────────────────────────────────────────┐
│  LOGO    [Tenant: acme.deeplink.io ▼]    [User ▼]   │
├─────────┬───────────────────────────────────────────┤
│         │                                           │
│ SIDEBAR │              MAIN CONTENT                 │
│         │                                           │
│ Overview│                                           │
│ Links   │                                           │
│ Apps    │                                           │
│ Domains │                                           │
│ Analytics│                                          │
│ API Keys│                                           │
│ Webhooks│                                           │
│ Settings│                                           │
│ Billing │                                           │
│         │                                           │
└─────────┴───────────────────────────────────────────┘
```

### 7.4 Team Members

- Owner can invite members by email
- Invite email sent → accept link (expires 48h)
- Roles: `owner` (full access), `admin` (no billing), `member` (links + analytics read-only)
- Owner cannot remove themselves
- Max members per plan: Free=1, Pro=3, Business=10, Enterprise=unlimited

---

## 8. Phase 2 — App Registration

### 8.1 App Registration Form

Client registers their mobile app. This stores config used to generate AASA / assetlinks files.

#### Step 1: Basic Info
| Field | Rules |
|---|---|
| App Name | required, max:255 (internal label) |
| Platform | required, enum: ios / android / both |
| URI Scheme | required, regex:/^[a-z][a-z0-9+\-.]{1,30}$/, no "://" suffix, unique per tenant |

#### Step 2a: iOS Config (if platform = ios or both)
| Field | Rules | Notes |
|---|---|---|
| iOS Bundle ID | required, regex:/^[a-zA-Z][a-zA-Z0-9.]{1,254}$/ | e.g. com.example.myapp |
| Apple Team ID | required, regex:/^[A-Z0-9]{10}$/ | 10-char from Apple Developer portal |
| App Store URL | required, url, starts_with:https://apps.apple.com | validated format |

**Auto-compute:** `ios_app_id = "{team_id}.{bundle_id}"` stored for AASA generation.

**Validation edge cases:**
- Bundle ID cannot start with a number
- Bundle ID cannot have consecutive dots
- Team ID: exactly 10 uppercase alphanumeric chars
- If platform = "both", both iOS and Android fields required

#### Step 2b: Android Config (if platform = android or both)
| Field | Rules | Notes |
|---|---|---|
| Package Name | required, regex:/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/ | e.g. com.example.myapp |
| SHA-256 Fingerprint(s) | required, array, each: regex:/^([A-F0-9]{2}:){31}[A-F0-9]{2}$/ | colons included |
| Play Store URL | required, url, starts_with:https://play.google.com/store/apps/details?id= | |

**Multiple fingerprints:** Allow up to 5 fingerprints (debug cert + release cert + upload key cert)

**How to get SHA-256:**
- Show instructions panel: `keytool -list -v -keystore release.keystore -alias release`
- Copy the SHA-256 line from output

**Validation edge cases:**
- Package name min 2 segments (must have at least one dot)
- Package name segments cannot start with number
- Fingerprint must be exactly 95 chars (32 pairs + 31 colons)
- Fingerprint normalized to uppercase on save
- Duplicate fingerprints in same array rejected

#### Step 3: Web Fallback
| Field | Rules |
|---|---|
| Web Fallback URL | optional, url, https only, max:500, validated against SSRF |
| Default Domain | select from verified tenant domains |

**SSRF validation for URLs (all URL fields):**
```php
// Block private IP ranges, localhost, metadata endpoints
$blocked = ['127.0.0.1','10.0.0.0/8','172.16.0.0/12','192.168.0.0/16',
            '169.254.169.254','::1','fc00::/7'];
// Resolve hostname → check resulting IP not in blocked ranges
// Reject: file://, ftp://, gopher://, dict:// schemes
// Accept: https:// only for fallback URLs
```

### 8.2 App Verification Badge

Show green "Verified" badge when:
- iOS: AASA file served correctly AND Apple's CDN has fetched it (can check via Apple's CDN cache-busting endpoint: `https://app-site-association.cdn-apple.com/a/v1/{domain}`)
- Android: assetlinks.json valid JSON, accessible, fingerprint parseable

Show "Pending" until first verification check completes (run on cron every 5 min for new apps).

---

## 9. Phase 3 — Well-Known File Serving

### 9.1 Routes (Tenant Domain)

```php
// These MUST be on the tenant domain (subdomain or custom)
Route::get('/.well-known/apple-app-site-association', [WellKnownController::class, 'aasa']);
Route::get('/.well-known/assetlinks.json', [WellKnownController::class, 'assetlinks']);
```

### 9.2 AASA Response

**Critical requirements:**
- Content-Type: `application/json` (NOT `text/html` or `application/pkcs7-mime`)
- No redirect (must be direct 200 response)
- Served over HTTPS (HTTP will be rejected by iOS)
- Must be accessible without authentication
- Response time < 3 seconds (iOS will timeout)

```php
public function aasa(Request $request): Response
{
    $tenant = tenancy()->tenant;
    $apps = App::where('tenant_id', $tenant->id)
        ->where('is_active', true)
        ->whereIn('platform', ['ios', 'both'])
        ->get();

    $details = $apps->map(fn($app) => [
        'appIDs' => [$app->ios_app_id],
        'components' => [['/' => '/l/*']],  // only handle our short link paths
    ])->values()->all();

    $payload = [
        'applinks' => [
            'details' => $details,
        ],
        'activitycontinuation' => [  // Handoff support
            'apps' => $apps->pluck('ios_app_id')->all(),
        ],
        'webcredentials' => [        // Password autofill (bonus)
            'apps' => $apps->pluck('ios_app_id')->all(),
        ],
    ];

    return response()
        ->json($payload)
        ->header('Content-Type', 'application/json')
        ->header('Cache-Control', 'public, max-age=3600')
        ->header('Access-Control-Allow-Origin', '*');
}
```

**AASA Caching Strategy:**
- Cache per tenant for 5 minutes (Redis)
- Invalidate cache when tenant's app config changes
- Add `Cache-Control: public, max-age=3600` header
- Apple caches AASA via their CDN — clients must re-install app to refresh, OR Apple re-fetches periodically

**AASA Paths Strategy:**
- Use `'/l/*'` to scope only to our short link prefix
- Do NOT use `'*'` for all paths (too permissive, Apple may reject)
- If client uses custom paths, allow configuration in app settings

### 9.3 assetlinks.json Response

**Critical requirements:**
- Content-Type: `application/json`
- No redirect
- HTTPS only
- Accessible without authentication
- Must include ALL SHA-256 fingerprints registered for the app

```php
public function assetlinks(Request $request): Response
{
    $tenant = tenancy()->tenant;
    $apps = App::where('tenant_id', $tenant->id)
        ->where('is_active', true)
        ->whereIn('platform', ['android', 'both'])
        ->get();

    $payload = $apps->flatMap(fn($app) =>
        collect($app->android_sha256_fingerprints)->map(fn($fingerprint) => [
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => [
                'namespace' => 'android_app',
                'package_name' => $app->android_package_name,
                'sha256_cert_fingerprints' => $app->android_sha256_fingerprints,
            ],
        ])->unique('target.package_name')
    )->values()->all();

    return response()
        ->json($payload)
        ->header('Content-Type', 'application/json')
        ->header('Cache-Control', 'public, max-age=3600')
        ->header('Access-Control-Allow-Origin', '*');
}
```

### 9.4 Apache Configuration

**Required Apache modules:**
```bash
sudo a2enmod rewrite headers ssl proxy_fcgi setenvif
sudo a2enconf php8.3-fpm
```

**Wildcard VirtualHost (`/etc/apache2/sites-available/deeplink-wildcard.conf`):**
```apache
# HTTP → HTTPS redirect (but NOT for /.well-known/ — iOS/Android reject redirects)
<VirtualHost *:80>
    ServerName deeplink.io
    ServerAlias *.deeplink.io

    # Allow ACME HTTP-01 challenge through (Let's Encrypt for custom domains)
    Alias /.well-known/acme-challenge/ /var/www/deeplink/public/.well-known/acme-challenge/
    <Directory "/var/www/deeplink/public/.well-known/acme-challenge/">
        Options None
        AllowOverride None
        Require all granted
    </Directory>

    # Redirect everything else to HTTPS
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</VirtualHost>

# HTTPS wildcard
<VirtualHost *:443>
    ServerName deeplink.io
    ServerAlias *.deeplink.io

    DocumentRoot /var/www/deeplink/public

    SSLEngine on
    # Wildcard cert from Cloudflare Origin CA (covers *.deeplink.io)
    SSLCertificateFile    /etc/ssl/cloudflare/deeplink.io.crt
    SSLCertificateKeyFile /etc/ssl/cloudflare/deeplink.io.key

    <Directory /var/www/deeplink/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # PHP-FPM via socket
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>

    # Security headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"

    # CRITICAL: .well-known must NOT redirect, must return correct Content-Type
    # Apache serves these via Laravel (PHP) which sets Content-Type: application/json
    # Ensure .htaccess does not block /.well-known/ paths

    ErrorLog ${APACHE_LOG_DIR}/deeplink-error.log
    CustomLog ${APACHE_LOG_DIR}/deeplink-access.log combined
</VirtualHost>
```

**Laravel `public/.htaccess` (standard + well-known passthrough):**
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send requests to front controller
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>
```

**Custom domain VirtualHost (generated per domain after SSL issued):**
```apache
# /etc/apache2/sites-available/custom-links.client.com.conf
<VirtualHost *:443>
    ServerName links.client.com
    DocumentRoot /var/www/deeplink/public

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/links.client.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/links.client.com/privkey.pem

    <Directory /var/www/deeplink/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

**After adding custom domain vhost:**
```bash
sudo a2ensite custom-links.client.com.conf
sudo apache2ctl configtest && sudo systemctl reload apache2
```

**Edge cases:**
- Cloudflare "Rocket Loader" must be disabled — it mangles JSON responses from `.well-known/`
- Apple fetches AASA via their CDN: `app-site-association.cdn-apple.com/a/v1/{domain}` — cache takes time to update
- Android re-fetches assetlinks only on app re-install or every 30 days
- `AllowOverride All` required on DocumentRoot for Laravel `.htaccess` to work
- `mod_headers` must be enabled for security headers to apply
- Apache `mod_php` must NOT be used — use PHP-FPM via `proxy_fcgi` only

---

## 10. Phase 4 — Link Management

### 10.1 Link Creation Form

| Field | Rules | Notes |
|---|---|---|
| Title (internal) | optional, max:255 | Just for dashboard labeling |
| App | required, FK apps.id, must belong to tenant | |
| Domain | required, FK domains.id, must be active + verified | |
| Destination Path | required, max:2000 | The deep link path, e.g. `/products/123` |
| Link Type | required, enum: universal / uri_scheme / both | |
| OG Title | optional, max:255 | Falls back to app name |
| OG Description | optional, max:500 | |
| OG Image | optional, url, https, max:500, SSRF validated | External URL or upload to S3 |
| Web Fallback URL | optional, url, https, SSRF validated | Overrides app-level fallback |
| Short Code | auto-generated OR custom | Custom: regex /^[a-zA-Z0-9_-]{3,20}$/ |
| Tags | optional, array, max 10 tags, each max:50 chars | |
| UTM params | optional (source, medium, campaign) | Appended to analytics |
| Password | optional, min:6, max:72 | Hashed with bcrypt |
| Expires At | optional, datetime, must be future | |
| Max Clicks | optional, integer, min:1 | |

### 10.2 Short Code Generation

```php
// Default: 6-char nanoid from URL-safe alphabet
// Alphabet: "ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789"
// Excluded: 0,O,1,I,l (ambiguous chars)
// Collision handling: retry up to 5 times with longer code if collision
function generateShortCode(int $length = 6): string
{
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
    } while (Link::where('short_code', $code)->where('domain_id', $domainId)->exists());
    return $code;
}
```

**Reserved short codes:** `l`, `go`, `r`, `api`, `app`, `admin`, `status`, `health`, `ping`

### 10.3 Destination Path

- Stored as path only: `/products/123` or `/profile?user=456`
- At render time, combined with URI scheme: `myapp://products/123`
- At render time, combined with deep link path for Universal Links (handled by app's link handler)
- Query params on destination path: preserved
- Hash fragments: preserved
- URL-encoded chars: preserved, do not double-encode

### 10.4 Link List View

- Paginated table (20 per page)
- Columns: Short URL (copyable), Title, App, Clicks (last 7d), Status, Created, Actions
- Filter: by app, by domain, by status, by date range, by tag
- Search: by title, short code, destination path
- Sort: by created_at, click_count, expires_at
- Bulk actions: deactivate, delete, add tag

### 10.5 Plan Limits Enforcement

```php
// Check before link creation
$linkCount = Link::where('tenant_id', $tenant->id)->count();
$plan = $tenant->plan;
if ($plan->links_limit !== -1 && $linkCount >= $plan->links_limit) {
    throw new PlanLimitExceededException('Upgrade to create more links.');
}
```

---

## 11. Phase 5 — Landing Page & Redirect Engine

This is the core user-facing feature. When someone taps a deep link, they hit this page.

### 11.1 Route

```php
// On tenant domain
Route::get('/{shortCode}', [LinkRedirectController::class, 'handle'])->name('link.redirect');
```

### 11.2 Request Processing Flow

```
Request: GET acme.deeplink.io/xK9mP2
    ↓
1. Identify tenant from Host header (middleware)
    ↓
2. Find link: SELECT * FROM links WHERE short_code='xK9mP2' AND domain_id=? AND tenant_id=?
    ↓ (cache this query in Redis, TTL 60s)
3. Check link exists → 404 if not
    ↓
4. Check link is_active → show "Link Inactive" page if false
    ↓
5. Check expires_at → show "Link Expired" page if past
    ↓
6. Check max_clicks → show "Link Unavailable" page if click_count >= max_clicks
    ↓
7. Check password → show password form if set (validate password before proceeding)
    ↓
8. Detect bot (User-Agent check) → log bot click, serve OG-only response, skip redirect JS
    ↓
9. Detect platform (User-Agent)
    ↓
10. Log click asynchronously (dispatch job to queue, do not block response)
    ↓
11. Serve landing page with JS redirect logic
```

### 11.3 Bot Detection

```php
// Bot UA patterns — not exhaustive, update regularly
$botPatterns = [
    'bot', 'crawler', 'spider', 'scraper', 'facebookexternalhit',
    'Twitterbot', 'LinkedInBot', 'Slackbot', 'WhatsApp', 'TelegramBot',
    'Googlebot', 'bingbot', 'DuckDuckBot', 'Baiduspider', 'YandexBot',
    'curl/', 'wget/', 'python-requests', 'axios/', 'java/',
];
// If bot: return HTML with only OG meta tags, no redirect JS
// Log as outcome: 'bot_filtered'
// Do NOT count bot clicks in click_count
```

### 11.4 Platform Detection (PHP, server-side)

```php
$ua = $request->userAgent() ?? '';
$isIOS = preg_match('/iPad|iPhone|iPod/', $ua) && !str_contains($ua, 'Windows');
$isAndroid = str_contains($ua, 'Android');
$isMobile = $isIOS || $isAndroid;
$platform = $isIOS ? 'ios' : ($isAndroid ? 'android' : 'desktop');
```

**Why PHP-side detection:** Pre-render correct store URL in JS so client doesn't need JS to determine store. Reduces flicker. But final decision still done in JS (more accurate, handles edge cases).

### 11.5 Landing Page (Blade Template)

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $link->og_title ?? $app->name }}</title>

    <!-- Open Graph (for social sharing previews) -->
    <meta property="og:title" content="{{ e($link->og_title ?? $app->name) }}">
    <meta property="og:description" content="{{ e($link->og_description ?? '') }}">
    <meta property="og:image" content="{{ $link->og_image_url ?? '' }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ e($link->og_title ?? $app->name) }}">
    <meta name="twitter:description" content="{{ e($link->og_description ?? '') }}">
    <meta name="twitter:image" content="{{ $link->og_image_url ?? '' }}">

    <!-- iOS Universal Link hint (no JS needed) -->
    @if($isIOS && $app->ios_store_url)
    <meta name="apple-itunes-app" content="app-id={{ $app->ios_app_store_id }}">
    @endif
</head>
<body>
    <!-- Visible fallback UI -->
    <div id="app-redirect-page">
        <div class="logo">{{ $app->name }}</div>
        <div class="message">Opening app...</div>
        <div class="manual-links">
            @if($app->ios_store_url)
            <a href="{{ $app->ios_store_url }}" id="ios-store-link">Download on App Store</a>
            @endif
            @if($app->android_store_url)
            <a href="{{ $app->android_store_url }}" id="android-store-link">Get it on Google Play</a>
            @endif
            @if($webFallback)
            <a href="{{ $webFallback }}" id="web-link">Continue on Web</a>
            @endif
        </div>
    </div>

    <script>
        // Data from server (escaped)
        const config = {
            linkType: @json($link->link_type),
            uriScheme: @json($app->uri_scheme),
            destinationPath: @json($link->destination_path),
            iosStoreUrl: @json($app->ios_store_url),
            androidStoreUrl: @json($app->android_store_url),
            webFallback: @json($webFallback),
            platform: @json($platform),
        };

        (function() {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isAndroid = /Android/.test(navigator.userAgent);

            function tryUriScheme() {
                if (!config.uriScheme || !config.destinationPath) return false;
                const uri = config.uriScheme + ':/' + config.destinationPath;
                window.location = uri;
                return true;
            }

            function redirectToStore() {
                if (isIOS && config.iosStoreUrl) {
                    window.location = config.iosStoreUrl;
                } else if (isAndroid && config.androidStoreUrl) {
                    window.location = config.androidStoreUrl;
                } else if (config.webFallback) {
                    window.location = config.webFallback;
                }
                // else: stay on page, show manual links
            }

            // Universal Links work automatically (iOS/Android open app before JS runs)
            // if link_type = 'universal' only, no URI scheme attempt
            if (config.linkType === 'uri_scheme' || config.linkType === 'both') {
                if (isIOS || isAndroid) {
                    const schemeAttempted = tryUriScheme();
                    if (schemeAttempted) {
                        // If app opens, user leaves page — this timeout never fires
                        // If app not installed, stays on page, timeout fires
                        setTimeout(redirectToStore, 2500);
                        return;
                    }
                }
            }

            // Desktop or universal-only: redirect to web fallback or show download links
            if (!isIOS && !isAndroid) {
                if (config.webFallback) {
                    setTimeout(() => { window.location = config.webFallback; }, 1500);
                }
            }
        })();
    </script>
</body>
</html>
```

### 11.6 Password-Protected Links

- GET `/{shortCode}` → detect password required → render password form
- POST `/{shortCode}/auth` → validate password → set session cookie → redirect to GET
- Session: `deeplink_unlocked_{linkId}` = true (expires with session)
- Brute force: rate limit 5 attempts per minute per IP per link

### 11.7 Expired / Inactive / Max Clicks Pages

- Render branded error page with: app name, reason, app store links
- HTTP status: 410 Gone (for expired/inactive), 200 for max_clicks

### 11.8 Click Logging Job

```php
// Dispatched async, does NOT block redirect response
class LogLinkClick implements ShouldQueue
{
    public function handle(): void
    {
        // 1. Determine uniqueness (SHA-256 of IP, per link, per 24h window)
        $ipHash = hash('sha256', $this->ip . $this->linkId . date('Y-m-d'));
        $isUnique = !Cache::has("click_unique:{$ipHash}");
        if ($isUnique) {
            Cache::put("click_unique:{$ipHash}", 1, now()->addDay());
        }

        // 2. Parse UA for OS, browser, device type
        // 3. GeoIP lookup (MaxMind GeoLite2 local DB, no external API call)
        // 4. Write to link_clicks table
        // 5. Increment links.click_count (atomic: UPDATE links SET click_count = click_count + 1)
        // 6. Dispatch webhook if configured
    }
}
```

---

## 12. Phase 6 — Custom Domain + SSL

### 12.1 Domain Registration Flow

1. Client goes to Domains → Add Custom Domain
2. Enters: `links.theircorp.com`
3. System validates: 
   - Valid domain format (regex + dns_check_record)
   - Not already registered by another tenant
   - Not a reserved domain
   - Not a subdomain of our root domain (prevent bypass)
4. System generates: `verification_token` = `dl_` + random 32-char hex
5. Shows instructions:
   ```
   Add this DNS TXT record:
   Host: _deeplink-verify.links.theircorp.com
   Value: dl_a1b2c3d4e5f6...
   TTL: 3600
   ```
6. Client adds record → clicks "Verify"
7. System checks DNS TXT record via DNS lookup

### 12.2 DNS Verification

```php
function verifyDomain(string $domain, string $token): bool
{
    $records = dns_get_record("_deeplink-verify.{$domain}", DNS_TXT);
    foreach ($records as $record) {
        if (isset($record['txt']) && $record['txt'] === $token) {
            return true;
        }
    }
    return false;
}
// Also run via queue job every 10 minutes for 'verifying' status domains
// Give up after 72 hours (set status = 'failed')
```

### 12.3 SSL Issuance (Let's Encrypt)

After DNS verification:
1. Client sets CNAME: `links.theircorp.com → deeplink.io` (instructions shown)
2. We run ACME HTTP-01 challenge
3. On success: cert saved, Nginx config generated for this domain
4. Auto-renewal: cron 2x/month, renew if expiry < 30 days

```php
// Simplified flow using acme.sh or certbot
// Run in queue job with 10 minute timeout
dispatch(new IssueSslCertificate($domain));
```

### 12.4 Nginx Dynamic Vhost

- Option A: Generate static Nginx server block file per domain, reload Nginx
- Option B: Use OpenResty + Lua to dynamically resolve domain → cert from DB
- Start with Option A (simpler), migrate to Option B at scale

### 12.5 Domain Status States

```
pending → verifying → (ssl_issuing) → active
                    ↘ failed (DNS not found in 72h or SSL failed)
active → suspended (manual admin action or plan downgrade)
```

---

## 13. Phase 7 — Analytics

### 13.1 Dashboard Overview Cards

- Total clicks (all time)
- Unique clicks (all time)
- Clicks today / this week / this month
- Top performing links (top 5)
- Platform breakdown (iOS / Android / Desktop)
- Top countries (top 10)

### 13.2 Link Detail Analytics

- Clicks over time (line chart, selectable: 7d / 30d / 90d / custom)
- Platform pie chart
- Country bar chart
- Outcome breakdown (app opened / store redirect / web fallback / expired)
- Referrer domain breakdown
- UTM breakdown (if UTMs configured)
- Unique vs total clicks toggle

### 13.3 Analytics Queries

```sql
-- Clicks per day last 30 days
SELECT DATE(clicked_at) as date, COUNT(*) as total,
       SUM(is_unique) as unique_clicks
FROM link_clicks
WHERE link_id = ? AND clicked_at >= NOW() - INTERVAL 30 DAY
  AND outcome NOT IN ('bot_filtered')
GROUP BY DATE(clicked_at)
ORDER BY date ASC;

-- Platform breakdown
SELECT platform, COUNT(*) as count
FROM link_clicks
WHERE link_id = ? AND clicked_at >= ?
GROUP BY platform;
```

**Performance:**
- Index on `(link_id, clicked_at)` covers all analytics queries
- For large tenants: aggregate into daily summary table via scheduled job
- Raw clicks table: retain per plan retention setting (30d free, 365d pro, 2yr business)
- Archived data: export to S3 before deletion

### 13.4 Data Export

- Export clicks CSV: date, platform, country, outcome, referrer
- Rate limited: 1 export per 10 minutes
- Large exports: async job → email download link

---

## 14. Phase 8 — REST API & Webhooks

### 14.1 API Authentication

```
Header: Authorization: Bearer dl_live_xxxxxxxxxxxxxxxx
```

- Key prefix `dl_live_` for production, `dl_test_` for test mode
- Hash stored: `SHA-256(rawKey)` → compare on each request
- Key displayed ONCE on creation (cannot retrieve again)
- Scope-based permissions per key

### 14.2 API Endpoints

```
POST   /api/v1/links                    Create link
GET    /api/v1/links                    List links (paginated)
GET    /api/v1/links/{uuid}             Get link
PATCH  /api/v1/links/{uuid}             Update link
DELETE /api/v1/links/{uuid}             Delete link (soft)
GET    /api/v1/links/{uuid}/analytics   Link analytics

GET    /api/v1/apps                     List apps
POST   /api/v1/apps                     Create app
GET    /api/v1/apps/{uuid}              Get app
PATCH  /api/v1/apps/{uuid}              Update app

GET    /api/v1/domains                  List domains
GET    /api/v1/analytics/overview       Tenant analytics overview

POST   /api/v1/webhooks                 Register webhook
GET    /api/v1/webhooks                 List webhooks
DELETE /api/v1/webhooks/{id}            Delete webhook
```

### 14.3 API Rate Limiting

- Free: 60 req/min
- Pro: 300 req/min
- Business: 1000 req/min
- Burst: allow 2x for 10s bursts
- Headers returned: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`

### 14.4 Webhook Events

```json
// link.clicked
{
    "event": "link.clicked",
    "timestamp": "2025-09-01T12:00:00Z",
    "data": {
        "link_uuid": "...",
        "short_code": "xK9mP2",
        "platform": "ios",
        "outcome": "store_redirect_ios",
        "country_code": "US",
        "is_unique": true
    }
}

// link.created / link.updated / link.expired / link.max_clicks_reached
```

### 14.5 Webhook Security

```php
// HMAC-SHA256 signature in header
$signature = 'sha256=' . hash_hmac('sha256', $rawPayload, $webhook->secret);
// Header: X-DeepLink-Signature: sha256=abc123...
// Client must verify this signature before processing payload
```

**Delivery:**
- Timeout: 10 seconds per attempt
- Retry: 3 attempts (immediate, 5min, 30min)
- Disable webhook after 10 consecutive failures (email client)
- Log all delivery attempts in `webhook_deliveries`

---

## 15. Phase 9 — Billing & Plans

### 15.1 Plan Tiers (INR pricing)

| Feature | Free | Pro (₹2,499/mo) | Business (₹7,999/mo) |
|---|---|---|---|
| Links | 100 | 10,000 | Unlimited |
| Monthly clicks | 10,000 | 500,000 | Unlimited |
| Apps | 1 | 5 | 20 |
| Custom domains | No | 1 | 5 |
| API access | No | Yes | Yes |
| Webhooks | No | No | Yes |
| Analytics retention | 30 days | 365 days | 2 years |
| Team members | 1 | 3 | 10 |
| Support | Community | Email | Priority |

> Also offer USD pricing for international clients via Razorpay international card support.

### 15.2 Razorpay Integration

**Package:** `razorpay/razorpay` (official PHP SDK)

**Subscription Flow:**

```
Client clicks Upgrade
    ↓
Backend: Create Razorpay Subscription via API
    razorpay->subscription->create([
        'plan_id'     => 'plan_xxx',       // pre-created in Razorpay dashboard
        'total_count' => 12,               // 12 billing cycles
        'quantity'    => 1,
        'customer_notify' => 1,
    ])
    ↓
Frontend: Open Razorpay Checkout (JS SDK)
    var rzp = new Razorpay({
        key: 'rzp_live_xxx',
        subscription_id: 'sub_xxx',
        name: 'DeepLink SaaS',
        description: 'Pro Plan',
        handler: function(response) {
            // response.razorpay_payment_id
            // response.razorpay_subscription_id
            // response.razorpay_signature
            verifyPayment(response);
        }
    });
    rzp.open();
    ↓
Backend: Verify payment signature
    $expectedSignature = hash_hmac(
        'sha256',
        $paymentId . '|' . $subscriptionId,
        config('razorpay.key_secret')
    );
    if (!hash_equals($expectedSignature, $receivedSignature)) {
        abort(400, 'Invalid signature');
    }
    ↓
Update tenant plan in DB → activate features
```

**Razorpay Webhook Events to handle:**
```
subscription.activated    → activate plan
subscription.charged      → log payment, extend period
subscription.completed    → mark subscription ended
subscription.cancelled    → schedule downgrade at period end
subscription.halted       → payment failed too many times → notify client
payment.failed            → notify client, keep grace period
```

**Webhook Verification:**
```php
// Razorpay sends X-Razorpay-Signature header
$webhookBody = $request->getContent();
$webhookSecret = config('razorpay.webhook_secret');
$expectedSignature = hash_hmac('sha256', $webhookBody, $webhookSecret);
if (!hash_equals($expectedSignature, $request->header('X-Razorpay-Signature'))) {
    abort(400);
}
```

**Supported payment methods via Razorpay:**
- Credit / Debit cards (Visa, MC, Rupay, Amex)
- UPI (GPay, PhonePe, Paytm, BHIM)
- Net banking (all major Indian banks)
- EMI (credit card EMI)
- International cards (with international payments enabled)

### 15.3 Razorpay Plans Setup (pre-create in dashboard)

```
Plan: pro_monthly
  interval: 1
  period: monthly
  item.amount: 249900   (₹2,499 in paise)
  item.currency: INR

Plan: pro_yearly
  interval: 1
  period: yearly
  item.amount: 2399900  (₹23,999 in paise — ~20% discount)
  item.currency: INR

Plan: business_monthly
  interval: 1
  period: monthly
  item.amount: 799900

Plan: business_yearly
  interval: 1
  period: yearly
  item.amount: 7679900
```

### 15.4 Subscriptions Table (custom, no Cashier)

```sql
CREATE TABLE subscriptions (
    id                      BIGINT UNSIGNED PK AUTO_INCREMENT,
    tenant_id               VARCHAR(255) FK tenants.id,
    razorpay_subscription_id VARCHAR(255) UNIQUE NOT NULL,
    razorpay_customer_id    VARCHAR(255) NULL,
    razorpay_plan_id        VARCHAR(255) NOT NULL,
    plan_name               VARCHAR(100) NOT NULL,       -- 'pro', 'business'
    billing_period          ENUM('monthly','yearly'),
    status                  ENUM('created','authenticated','active','paused',
                                 'halted','cancelled','completed','expired'),
    current_period_start    TIMESTAMP NULL,
    current_period_end      TIMESTAMP NULL,
    cancel_at_period_end    BOOLEAN DEFAULT FALSE,
    cancelled_at            TIMESTAMP NULL,
    trial_ends_at           TIMESTAMP NULL,
    created_at              TIMESTAMP,
    updated_at              TIMESTAMP,

    INDEX: tenant_id, status, razorpay_subscription_id
);

CREATE TABLE payments (
    id                      BIGINT UNSIGNED PK AUTO_INCREMENT,
    tenant_id               VARCHAR(255) FK tenants.id,
    subscription_id         BIGINT UNSIGNED FK subscriptions.id NULL,
    razorpay_payment_id     VARCHAR(255) UNIQUE NOT NULL,
    razorpay_order_id       VARCHAR(255) NULL,
    amount                  INT UNSIGNED NOT NULL,       -- in paise
    currency                CHAR(3) DEFAULT 'INR',
    status                  ENUM('created','authorized','captured','refunded','failed'),
    method                  VARCHAR(50) NULL,            -- card/upi/netbanking/emi
    captured_at             TIMESTAMP NULL,
    refunded_at             TIMESTAMP NULL,
    failure_reason          VARCHAR(255) NULL,
    created_at              TIMESTAMP,
    updated_at              TIMESTAMP,

    INDEX: tenant_id, razorpay_payment_id, status
);
```

### 15.5 Plan Downgrade Handling

- Immediate downgrade: links above limit DEACTIVATED (not deleted)
- Warning shown: "12 links deactivated due to plan downgrade. Upgrade to reactivate."
- FIFO deactivation: oldest links stay active, newest deactivated
- 14-day grace after `subscription.halted` before forcing downgrade

### 15.6 Usage Tracking

```php
// Tracked in real-time for enforcement:
// - link count: COUNT(links) per tenant
// - monthly clicks: SUM(link_clicks) WHERE clicked_at >= start_of_month
// - Checked on: link creation, daily cron warning emails at 80% / 100% usage
```

---

## 16. Phase 10 — Security Hardening

### 16.1 Input Validation

- All form inputs: validated via Laravel Form Requests
- All API inputs: validated via same Form Requests (reused)
- No raw `$request->all()` in controllers
- Mass assignment protection: explicit `$fillable` on all models
- XSS: all Blade output via `{{ }}` (auto-escaped), never `{!! !!}` for user content

### 16.2 SQL Injection

- Exclusively use Eloquent ORM or parameterized query builder
- No raw SQL with user input
- No `whereRaw()` with user data

### 16.3 CSRF Protection

- All web forms: `@csrf` token (Laravel default)
- API routes: exempt from CSRF (use Bearer token auth instead)
- SameSite cookie: `Strict`

### 16.4 Open Redirect Prevention

```php
// All URLs stored in DB are validated at WRITE time:
// 1. Must be valid HTTPS URL
// 2. Must not resolve to private IP ranges (SSRF check)
// 3. Redirect destinations come ONLY from DB — never from URL params

// BAD (never do this):
// $url = $request->get('redirect');
// return redirect($url);

// GOOD:
// $url = $link->web_fallback_url; // from DB, pre-validated
// return redirect($url);
```

### 16.5 SSRF Prevention

```php
class UrlSsrfValidator
{
    private const BLOCKED_CIDRS = [
        '127.0.0.0/8', '10.0.0.0/8', '172.16.0.0/12',
        '192.168.0.0/16', '169.254.0.0/16', '100.64.0.0/10',
        '::1/128', 'fc00::/7', 'fe80::/10',
    ];

    public function validate(string $url): bool
    {
        $parsed = parse_url($url);
        if (!in_array($parsed['scheme'] ?? '', ['https'])) return false;
        
        $host = $parsed['host'] ?? '';
        $ips = gethostbynamel($host);
        if ($ips === false) return false; // could not resolve
        
        foreach ($ips as $ip) {
            foreach (self::BLOCKED_CIDRS as $cidr) {
                if ($this->ipInCidr($ip, $cidr)) return false;
            }
        }
        return true;
    }
}
```

### 16.6 Rate Limiting

```php
// Laravel throttle middleware
// Landing page: 60 req/min per IP (prevent click fraud)
// Auth endpoints: 5 req/min per IP
// API: per-plan limits
// Well-known files: 120 req/min (bots/CDNs need access)
// Password attempt: 5 req/min per IP per link
```

### 16.7 Tenant Isolation Audit Checklist

- [ ] All models have `tenant_id` column and `TenantScope` global scope
- [ ] All policies check `$tenant_id === $model->tenant_id`
- [ ] No route exposes cross-tenant data (test: create two tenants, try accessing tenant B resources with tenant A session)
- [ ] UUID used in all API/URL routes (no sequential integer IDs)
- [ ] Well-known files only return data for the requesting tenant's domain
- [ ] Analytics queries always filter by `tenant_id`
- [ ] API keys scoped to tenant (never cross-tenant)
- [ ] Webhooks can only be triggered by their tenant's events

### 16.8 Security Headers (Apache)

Set in VirtualHost config (NOT .htaccess — VirtualHost is parsed once, .htaccess per request):

```apache
# In <VirtualHost *:443> block
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; img-src 'self' https: data:; style-src 'self' 'unsafe-inline'"
# HSTS: only on app.deeplink.io, NOT wildcard tenant domains (breaks custom domain SSL)
# Add Header always set Strict-Transport-Security "max-age=31536000" only on central app vhost
```

**Requires:** `sudo a2enmod headers` then `sudo systemctl reload apache2`

### 16.9 Secrets Management

- `.env` never committed to git
- Database password, Stripe keys, app key: environment variables only
- API keys: stored as SHA-256 hash, original never stored
- Webhook secrets: stored encrypted (Laravel encryption)
- 2FA secrets: stored encrypted

### 16.10 Logging & Monitoring

- All auth events logged: login, failed login, password reset, 2FA enable/disable
- All admin actions logged: link created/deleted, app config changed, domain added
- Sentry: exception tracking
- Uptime: ping health endpoint every 60s
- Anomaly alerts: >10x normal click rate on single link = possible abuse

---

## 17. Phase 11 — Testing Strategy

### 17.1 Unit Tests

#### Model Tests
- `LinkTest::test_short_code_is_unique_per_domain()`
- `LinkTest::test_is_active_returns_false_when_expired()`
- `LinkTest::test_is_active_returns_false_when_max_clicks_reached()`
- `AppTest::test_ios_app_id_computed_correctly()`
- `AppTest::test_sha256_fingerprint_normalized_to_uppercase()`

#### Service Tests
- `ShortCodeGeneratorTest::test_generates_url_safe_code()`
- `ShortCodeGeneratorTest::test_handles_collision_with_longer_code()`
- `UrlSsrfValidatorTest::test_rejects_private_ip()`
- `UrlSsrfValidatorTest::test_rejects_localhost()`
- `UrlSsrfValidatorTest::test_rejects_aws_metadata_endpoint()`
- `UrlSsrfValidatorTest::test_accepts_valid_public_url()`
- `BotDetectorTest::test_detects_googlebot()`
- `BotDetectorTest::test_detects_facebookbot()`
- `BotDetectorTest::test_accepts_real_ios_ua()`
- `PlatformDetectorTest::test_detects_iphone()`
- `PlatformDetectorTest::test_detects_android()`
- `PlatformDetectorTest::test_detects_desktop_chrome()`

#### AASA/Assetlinks Tests
- `WellKnownTest::test_aasa_returns_correct_content_type()`
- `WellKnownTest::test_aasa_contains_correct_app_id()`
- `WellKnownTest::test_aasa_excludes_inactive_apps()`
- `WellKnownTest::test_assetlinks_contains_all_fingerprints()`
- `WellKnownTest::test_assetlinks_content_type_is_json()`
- `WellKnownTest::test_aasa_excludes_android_only_apps()`

### 17.2 Feature Tests

#### Auth
- `RegistrationTest::test_user_can_register_with_valid_data()`
- `RegistrationTest::test_registration_creates_tenant_and_subdomain()`
- `RegistrationTest::test_duplicate_email_rejected()`
- `RegistrationTest::test_duplicate_slug_generates_alternative()`
- `RegistrationTest::test_reserved_slug_rejected()`
- `LoginTest::test_rate_limiter_blocks_after_5_attempts()`
- `LoginTest::test_2fa_required_when_enabled()`

#### Link Management
- `LinkCreationTest::test_link_created_with_valid_data()`
- `LinkCreationTest::test_link_creation_blocked_at_plan_limit()`
- `LinkCreationTest::test_custom_short_code_accepted()`
- `LinkCreationTest::test_reserved_short_code_rejected()`
- `LinkCreationTest::test_cross_tenant_app_rejected()`
- `LinkCreationTest::test_ssrf_url_rejected()`

#### Redirect Engine
- `LinkRedirectTest::test_active_link_serves_landing_page()`
- `LinkRedirectTest::test_inactive_link_shows_error_page()`
- `LinkRedirectTest::test_expired_link_returns_410()`
- `LinkRedirectTest::test_bot_ua_gets_og_only_response()`
- `LinkRedirectTest::test_password_protected_link_shows_form()`
- `LinkRedirectTest::test_correct_password_grants_access()`
- `LinkRedirectTest::test_wrong_password_rejected()`
- `LinkRedirectTest::test_click_logged_asynchronously()`
- `LinkRedirectTest::test_max_clicks_exceeded_shows_error()`
- `LinkRedirectTest::test_nonexistent_short_code_returns_404()`

#### Tenant Isolation
- `TenantIsolationTest::test_tenant_a_cannot_access_tenant_b_link()`
- `TenantIsolationTest::test_tenant_a_cannot_use_tenant_b_app_for_new_link()`
- `TenantIsolationTest::test_well_known_files_only_return_own_tenant_apps()`
- `TenantIsolationTest::test_api_key_cannot_access_other_tenant_data()`

#### API
- `ApiTest::test_create_link_via_api()`
- `ApiTest::test_invalid_api_key_returns_401()`
- `ApiTest::test_rate_limit_returns_429_with_retry_after_header()`
- `ApiTest::test_scope_restriction_returns_403()`

### 17.3 Browser Tests (Laravel Dusk)

- `DashboardTest::test_user_can_create_link_and_copy_url()`
- `DashboardTest::test_analytics_chart_loads_for_link()`
- `DashboardTest::test_app_registration_form_validates_bundle_id()`
- `DashboardTest::test_custom_domain_verification_flow()`

### 17.4 Manual Testing Checklist (iOS)

Before any production release:

- [ ] Install test app on real iPhone (not simulator — simulator doesn't support Universal Links)
- [ ] Configure Xcode Associated Domains with test domain
- [ ] Verify AASA fetched: check `https://app-site-association.cdn-apple.com/a/v1/{domain}`
- [ ] Tap link in Messages → app opens directly (not Safari)
- [ ] Tap link in Safari → banner shown at top to open in app
- [ ] Uninstall app → tap link → App Store opens
- [ ] Reinstall app → tap link → app opens at correct screen
- [ ] Test expired link → error page shown in Safari
- [ ] Test password-protected link → password form shown in Safari

### 17.5 Manual Testing Checklist (Android)

- [ ] Install debug APK with correct intent filter and autoVerify
- [ ] Run: `adb shell pm get-app-links --package com.example.app` → verify domain verified
- [ ] Tap link in Messages → app opens directly
- [ ] Tap link in Chrome → app opens (Chrome shows "Open with app" or opens directly)
- [ ] Uninstall → tap link → Play Store opens
- [ ] Check `assetlinks.json` validates via: `https://digitalassetlinks.googleapis.com/v1/statements:list?source.web.site=https://yourdomain.com&relation=delegate_permission/common.handle_all_urls`

### 17.6 Load Testing

- Tool: k6 or Artillery
- Scenario: 1000 concurrent users hitting redirect endpoint
- Target: < 200ms P95 response time for redirect
- Test: well-known files under load (simulate Apple CDN refresh)
- Alert: if Redis cache miss rate > 20%, investigate

---

## 18. Phase 12 — Deployment & DevOps

### 18.1 Server Setup (AWS Lightsail)

```
Production:
  - 1x Lightsail instance $20/mo (4GB RAM, 2 vCPU, 80GB SSD)
      → Apache2, PHP-FPM 8.3, Laravel app
  - 1x Lightsail instance $10/mo (2GB RAM, 1 vCPU, 60GB SSD)
      → MySQL 8 (dedicated DB server)
  - 1x Lightsail instance $10/mo (2GB RAM, 1 vCPU)
      → Redis (cache + queue broker)
  - Supervisor (supervisord) on app server — manages all queue workers
  - Lightsail static IP attached to app server
  - Cloudflare (CDN + DDoS + wildcard SSL for *.deeplink.io)
  - Lightsail firewall rules: allow 80, 443, 22 (SSH) only

Staging:
  - 1x Lightsail instance $10/mo — all-in-one (Apache + MySQL + Redis)
  - Separate DB name, separate .env

Lightsail Advantages for this project:
  - Fixed monthly price (no surprise bills)
  - Built-in firewall (no Security Group complexity)
  - Easy snapshots for backups
  - Same region as Indian traffic: ap-south-1 (Mumbai)
  - Can upgrade instance size with snapshot restore

Object Storage (S3):
  - NOT required for v1
  - Clients supply their own OG image URL — we store string only, never host image bytes
  - Add Lightsail Object Storage ($1/mo/5GB) only if we implement OG image upload feature (v2+)
```

### Supervisor Configuration

```ini
; /etc/supervisor/conf.d/deeplink-worker.conf

[program:deeplink-default-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/deeplink/artisan queue:work redis --queue=default --sleep=3 --tries=3 --timeout=90 --max-jobs=500
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4                          ; 4 parallel workers for default queue
redirect_stderr=true
stdout_logfile=/var/log/supervisor/deeplink-default-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=60

[program:deeplink-clicks-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/deeplink/artisan queue:work redis --queue=clicks --sleep=1 --tries=3 --timeout=30 --max-jobs=1000
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8                          ; 8 workers for high-volume click logging
redirect_stderr=true
stdout_logfile=/var/log/supervisor/deeplink-clicks-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=30

[program:deeplink-ssl-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/deeplink/artisan queue:work redis --queue=ssl --sleep=10 --tries=2 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1                          ; 1 worker for SSL issuance (rate limited by Let's Encrypt)
redirect_stderr=true
stdout_logfile=/var/log/supervisor/deeplink-ssl-worker.log
stdout_logfile_maxbytes=5MB
stdout_logfile_backups=3
stopwaitsecs=120

[group:deeplink]
programs=deeplink-default-worker,deeplink-clicks-worker,deeplink-ssl-worker
```

**Queue channel strategy:**
| Channel | Jobs dispatched | Workers |
|---|---|---|
| `default` | Link CRUD, email, webhooks, domain verify | 4 |
| `clicks` | `LogLinkClick` — high volume, fast, simple | 8 |
| `ssl` | `IssueSslCertificate` — slow, rate-limited | 1 |

**Supervisor commands:**
```bash
sudo supervisorctl reread           # pick up new config
sudo supervisorctl update           # apply config changes
sudo supervisorctl restart deeplink:*   # restart all workers (e.g. after deploy)
sudo supervisorctl status           # check all worker status
sudo supervisorctl tail -f deeplink-clicks-worker:deeplink-clicks-worker_00 stdout
```

**Deploy hook (restart workers after deploy):**
```bash
# In CI/CD pipeline, after artisan commands:
sudo supervisorctl restart deeplink:*
```

### 18.2 Environment Variables

```env
APP_KEY=                            # 32-char random key
APP_URL=https://app.deeplink.io
TENANT_URL_PATTERN="{slug}.deeplink.io"

DB_HOST=
DB_DATABASE=deeplink_saas
DB_USERNAME=
DB_PASSWORD=

REDIS_HOST=
REDIS_PASSWORD=

RAZORPAY_KEY=rzp_live_xxxxxxxxxxxx
RAZORPAY_SECRET=
RAZORPAY_WEBHOOK_SECRET=

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=no-reply@deeplink.io

SENTRY_LARAVEL_DSN=

AWS_ACCESS_KEY_ID=          # for S3/R2 OG image storage
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=

MAXMIND_LICENSE_KEY=        # GeoIP2 database
```

### 18.3 CI/CD Pipeline

```yaml
# GitHub Actions
on: push to main
jobs:
  test:
    - composer install
    - php artisan test (PHPUnit)
    - php artisan dusk (browser tests, headless Chrome)
  deploy:
    - ssh to server
    - git pull
    - composer install --no-dev
    - php artisan migrate --force
    - php artisan config:cache
    - php artisan route:cache
    - php artisan view:cache
    - sudo supervisorctl restart deeplink:*
    - sudo apache2ctl configtest && sudo systemctl reload apache2
```

### 18.4 Scheduled Tasks

```php
// Kernel.php
$schedule->job(new VerifyPendingDomains)->everyFiveMinutes();
$schedule->job(new RenewExpiringSslCerts)->daily();
$schedule->job(new AggregateAnalytics)->hourly();
$schedule->job(new PruneOldClickLogs)->daily(); // per plan retention
$schedule->job(new CheckWebhookHealth)->hourly();
$schedule->job(new SendUsageWarningEmails)->dailyAt('09:00');
$schedule->job(new UpdateMaxmindDatabase)->weekly();
```

---

## 19. Complete E2E Flow

### Flow A: New Client Onboarding

```
1. Client visits app.deeplink.io
2. Clicks "Get Started Free"
3. Fills registration form → submits
4. System:
   a. Creates user record
   b. Creates tenant (slug: "acmecorp")
   c. Creates domain: "acmecorp.deeplink.io" (status: active immediately for subdomain)
   d. Sends verification email
5. Client sees dashboard with "Verify your email" banner
6. Client clicks email link → email verified
7. Client clicks "Add App" in sidebar
8. Fills app form:
   - Name: "Acme iOS App"
   - Platform: both
   - Bundle ID: com.acme.app
   - Team ID: ABCDE12345
   - App Store URL: https://apps.apple.com/app/id123456
   - Package: com.acme.app
   - SHA-256: AA:BB:CC:...
   - Play Store URL: https://play.google.com/store/apps/details?id=com.acme.app
   - URI Scheme: acmeapp
9. System saves app, generates ios_app_id = "ABCDE12345.com.acme.app"
10. System queues verification job (check AASA + assetlinks accessible)
11. Client goes to Links → Create Link
12. Fills:
    - App: "Acme iOS App"
    - Domain: acmecorp.deeplink.io
    - Destination Path: /products/special-offer
    - OG Title: "Summer Sale 50% Off"
    - OG Image: https://acme.com/images/summer-sale.jpg
    - Link Type: both
13. System generates short code: "xK9mP2"
14. Link URL: https://acmecorp.deeplink.io/xK9mP2
15. Client copies link → shares on Instagram story

USER JOURNEY:
16. Instagram user taps link in story
17. Browser opens: https://acmecorp.deeplink.io/xK9mP2
18. Server: identifies tenant "acmecorp", finds link "xK9mP2"
19. Server: detects iOS (User-Agent)
20. Server: renders landing page with OG meta + JS redirect logic
21. JS: tries URI scheme: acmeapp://products/special-offer
22a. App installed: iOS intercepts URI scheme → app opens → navigates to /products/special-offer
22b. App not installed: timeout fires → window.location = App Store URL
23. Async: LogLinkClick job dispatched → platform=ios, outcome=app_opened or store_redirect_ios
24. Dashboard: Client sees click in analytics within 10 seconds
```

### Flow B: Deep Link Routing (Universal Links — iOS)

```
1. Acme dev configures Xcode:
   - Associated Domains: applinks:acmecorp.deeplink.io
2. User installs Acme app on iPhone
3. iOS fetches: https://acmecorp.deeplink.io/.well-known/apple-app-site-association
4. iOS validates: app ID "ABCDE12345.com.acme.app" matches paths ["/l/*"]
   Wait — NOTE: short codes are served at root "/" not "/l/"
   Developer should use paths: ["*"] OR configure link prefix
5. iOS caches association
6. User taps https://acmecorp.deeplink.io/xK9mP2 anywhere (message, email, web)
7. iOS sees domain is associated with Acme app
8. iOS opens Acme app directly (browser never opens)
9. App's AppDelegate/SceneDelegate receives: application(_:continue:restorationHandler:)
10. App reads URL: https://acmecorp.deeplink.io/xK9mP2
11. App calls our API: GET /api/v1/links/resolve?url=...
    OR App parses short code "xK9mP2" → fetches its own backend to get destination
    OR App reads link metadata from OG tags (pre-fetched)
12. App navigates to /products/special-offer screen
```

### Flow C: Android App Links

```
1. Acme dev configures AndroidManifest.xml:
   <intent-filter android:autoVerify="true">
     <data android:scheme="https" android:host="acmecorp.deeplink.io" />
   </intent-filter>
2. User installs Acme app on Android
3. Android fetches: https://acmecorp.deeplink.io/.well-known/assetlinks.json
4. Android validates SHA-256 fingerprint matches installed app cert
5. Android verifies and caches association
6. User taps https://acmecorp.deeplink.io/xK9mP2
7. Android opens Acme app directly
8. App receives Intent with URL, extracts path, navigates to screen
```

### Flow D: Custom Domain Setup

```
1. Client (Pro plan) goes to Domains → Add Domain
2. Enters: links.acme.com
3. System validates format, checks not taken
4. System generates token: dl_a1b2c3d4e5...
5. Client shown: "Add TXT record: _deeplink-verify.links.acme.com = dl_a1b2c3d4..."
6. Client adds record in their DNS provider
7. Client clicks "Verify Domain"
8. System runs DNS lookup → finds TXT record → marks verified_at = now()
9. Client shown: "Now add CNAME: links.acme.com → acmecorp.deeplink.io"
10. Client adds CNAME
11. System detects CNAME propagation (via background job checking every 5 min)
12. System runs ACME HTTP-01 challenge for links.acme.com → issues Let's Encrypt cert
13. System generates Nginx server block for links.acme.com → reloads Nginx
14. Domain status = active
15. Client can now use links.acme.com as domain for new links
16. links.acme.com/.well-known/apple-app-site-association now works
```

---

## 20. Edge Cases Registry

### Link Resolution Edge Cases

| Case | Behavior |
|---|---|
| Short code not found | 404 page with search/home CTA |
| Short code exists but different tenant's domain | 404 (tenant isolation) |
| Link soft-deleted | 404 |
| Link deactivated | 410 page explaining link inactive |
| Link expired | 410 page with expiry time |
| Link expired AND deactivated | Show expired (expiry takes priority in messaging) |
| Max clicks reached | 410 page |
| Password required, no session | Password form |
| Password required, wrong password | Re-show form with error |
| Password required, brute forced | 429 after 5 attempts/minute |
| App is deleted but link exists | Show error: "App no longer configured" |
| Domain suspended | 503 page |
| Unicode in destination path | URL-encode before using in URI scheme |
| Destination path contains `"` or `<` | Escaped in JS config JSON |
| OG image URL unreachable | Omit OG image tag (don't error) |
| Very long destination path (>2000 chars) | Rejected at link creation (validation) |
| Short code with uppercase (case sensitive) | Short codes ARE case-sensitive, xK9mP2 ≠ xk9mp2 |

### AASA / Assetlinks Edge Cases

| Case | Behavior |
|---|---|
| Tenant has 0 active iOS apps | AASA returns `{"applinks":{"details":[]}}` — valid empty |
| Tenant has 0 active Android apps | assetlinks returns `[]` — valid empty |
| App has no ios_team_id set | Exclude from AASA details |
| App has empty sha256_fingerprints | Exclude from assetlinks |
| Domain has no apps | Return empty valid JSON |
| HTTP request to .well-known | Nginx redirects to HTTPS (EXCEPT during ACME challenge) |
| Cloudflare caches .well-known | Set `Cache-Control: max-age=3600` AND add CF Page Rule to bypass cache for /.well-known/* |
| Apple CDN cached stale AASA | User must re-install app to force refresh (communicate this in docs) |
| Android caches stale assetlinks | Re-install app OR wait 30 days |

### Domain Edge Cases

| Case | Behavior |
|---|---|
| Client adds deeplink.io itself as custom domain | Blocked: cannot register root domain |
| Client adds *.deeplink.io subdomain | Blocked: cannot use our subdomain pattern |
| Client adds IP address as domain | Blocked: must be hostname |
| DNS TXT verification times out | Status = failed after 72h, client can retry |
| CNAME pointed before DNS verification | SSL issuance fails gracefully, retry queue |
| SSL cert expires | Auto-renew 30 days before expiry |
| Let's Encrypt rate limit hit (5 certs/domain/week) | Queue issuance, show "SSL pending" status |
| Client deletes domain with active links | Warn: "X links use this domain. Links will break." Force confirm. Links deactivated. |
| Tenant churns (subscription cancelled) | Custom domains suspended after 30-day grace. Data retained 90 days then deleted. |

### Analytics Edge Cases

| Case | Behavior |
|---|---|
| Same IP clicks same link 100x in 1 hour | First click = unique, rest = non-unique. All logged but only 1 unique. |
| VPN / Tor exit node | Logged normally, country = VPN exit country (MaxMind may flag it) |
| Bot click logged | outcome = bot_filtered, NOT counted in click_count |
| Analytics query for link with 0 clicks | Return empty arrays, not errors |
| Date range has no data | Return zeroes for each day in range |
| Click from unknown country | country_code = NULL, shown as "Unknown" in dashboard |

### Billing Edge Cases

| Case | Behavior |
|---|---|
| Razorpay webhook arrives out of order | Idempotent: check `razorpay_subscription_id` status in DB before processing, skip if already applied |
| Duplicate webhook delivery | Store `razorpay_payment_id` as UNIQUE — second insert fails gracefully, event skipped |
| Payment signature verification fails | Return 400, log, alert admin — possible replay attack |
| `subscription.halted` (payment failed 3x Razorpay retry) | Notify client via email, 14-day grace, then downgrade |
| Payment fails, then succeeds before grace period | `subscription.charged` fires → resume immediately |
| UPI payment pending (UPI collect flow) | Status = `authorized`, wait for `captured` webhook before activating |
| Tenant on Free tries to add custom domain | Upgrade prompt shown with Razorpay checkout |
| Tenant downgrades Pro→Free with 5 custom domains | 1 domain kept (primary), others suspended (not deleted) |
| Tenant cancels mid-billing-period | `cancel_at_period_end = true`, access until `current_period_end`, then downgrade |
| Tenant disputes charge (chargeback via bank) | Admin notified, account suspended pending Razorpay dispute resolution |
| Razorpay API down during subscription create | Show error, do not create partial subscription in DB, user can retry |
| INR amount overflow (paise) | Store as INT UNSIGNED — max ₹42,94,967. Safe for current prices. |

---

## 21. Validation Rules

### URL Validation (all URL fields)

```php
'url' => [
    'required',
    'string',
    'max:500',
    'url:https',                            // HTTPS only
    new NotSsrfUrl(),                       // Custom rule: blocks private IPs
    function($attr, $val, $fail) {
        $parsed = parse_url($val);
        if (!isset($parsed['host'])) $fail('Invalid URL.');
        if (str_ends_with($parsed['host'], '.internal')) $fail('Not allowed.');
    }
]
```

### Short Code Validation (custom)

```php
'short_code' => [
    'sometimes',
    'string',
    'min:3',
    'max:20',
    'regex:/^[a-zA-Z0-9_-]+$/',
    Rule::notIn(['l','go','r','api','app','admin','status','health','ping','login']),
    Rule::unique('links')->where('domain_id', $this->domain_id)
        ->whereNull('deleted_at'),
]
```

### Bundle ID Validation

```php
'ios_bundle_id' => [
    'required_if:platform,ios,both',
    'string',
    'regex:/^[a-zA-Z][a-zA-Z0-9]*(\.[a-zA-Z][a-zA-Z0-9]*)+$/',
    'max:255',
]
```

### SHA-256 Fingerprint Validation

```php
'android_sha256_fingerprints.*' => [
    'required',
    'string',
    'regex:/^([A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}$/',
]
// Normalize to uppercase on save
```

### Tenant Slug Validation

```php
'slug' => [
    'required',
    'string',
    'min:3',
    'max:30',
    'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',    // no leading/trailing hyphens
    'not_regex:/--/',                              // no consecutive hyphens
    Rule::notIn(['www','api','app','admin','mail','ftp','dashboard','help',
                 'blog','status','cdn','static','assets','media','img']),
    Rule::unique('domains', 'domain')
        ->where(fn($q) => $q->where('type', 'subdomain')),
]
```

### Webhook URL Validation

```php
'url' => [
    'required',
    'url:https',
    new NotSsrfUrl(),
    'max:500',
    // Must not be our own domain (prevent webhook loops)
    function($attr, $val, $fail) {
        $host = parse_url($val, PHP_URL_HOST);
        if (str_ends_with($host, 'deeplink.io')) {
            $fail('Webhook URL cannot point to our own service.');
        }
    }
]
```

---

## 22. API Reference

### Create Link

```
POST /api/v1/links
Authorization: Bearer dl_live_xxx

Request Body:
{
    "app_uuid": "uuid",
    "domain_id": 1,
    "destination_path": "/products/123",
    "link_type": "both",
    "og_title": "Check this out",
    "og_description": "Amazing product",
    "og_image_url": "https://example.com/img.jpg",
    "web_fallback_url": "https://example.com/products/123",
    "short_code": "custom123",      // optional
    "title": "Summer campaign link", // optional, internal
    "tags": ["summer", "promo"],    // optional
    "expires_at": "2025-12-31T23:59:59Z", // optional
    "max_clicks": 1000,             // optional
    "password": "secret123"         // optional
}

Response 201:
{
    "data": {
        "uuid": "...",
        "short_url": "https://acmecorp.deeplink.io/custom123",
        "short_code": "custom123",
        "destination_path": "/products/123",
        "link_type": "both",
        "is_active": true,
        "expires_at": "2025-12-31T23:59:59Z",
        "created_at": "2025-09-01T10:00:00Z"
    }
}

Response 422:
{
    "message": "Validation failed",
    "errors": {
        "short_code": ["This short code is already taken."],
        "og_image_url": ["URL must be publicly accessible HTTPS."]
    }
}

Response 402:
{
    "message": "Plan limit reached. You have used 100/100 links on the Free plan.",
    "upgrade_url": "https://app.deeplink.io/billing/upgrade"
}
```

### Resolve Link (for app SDK use)

```
GET /api/v1/links/resolve?url=https://acmecorp.deeplink.io/xK9mP2

Response 200:
{
    "data": {
        "destination_path": "/products/123",
        "uri_scheme": "acmeapp",
        "full_uri": "acmeapp://products/123"
    }
}
```

---

## Implementation Priority Order — REALITY BUILD

### Situation: 3 React Native customers. Both iOS + Android. BROKEN NOW.

**The real bottleneck is NOT your code. It is iOS App Store review (1-3 days).**
Customers must submit new app version with reconfigured Associated Domains.
Every day you delay giving them a working AASA endpoint = 1 more day of broken links.

**DO THIS BEFORE WRITING A SINGLE LINE OF CODE:**
Set price. Send WhatsApp/email to all 3 customers TODAY:
> "I'm building the replacement. It will be ready for testing in 48 hours.
> Price: ₹999/mo per app. I need payment before I hand over credentials.
> Razorpay Payment Link incoming."

If they say yes → build. If they negotiate → fine, but get commitment first.
Free access now = they will never pay. This is the most important step.

---

### Actual Build Order (Urgency-First)

```
DAY 1 (today): Server + AASA/assetlinks live
  Priority: give customers a working HTTPS domain TODAY so they can
  start Xcode reconfiguration and Android manifest changes immediately.
  App Store review takes 1-3 days — every hour counts.

  - Lightsail instance up ($20/mo, ap-south-1 Mumbai)
  - Apache2 + PHP 8.3 FPM installed
  - Cloudflare wildcard DNS: *.deeplink.io → server IP
  - Cloudflare Origin CA cert for *.deeplink.io (free, 15-year cert)
  - Laravel bare install, single tenant (NO stancl/tenancy yet)
  - Hardcode 3 tenant subdomains in config (customer1.deeplink.io etc.)
  - AASA endpoint: /.well-known/apple-app-site-association
  - assetlinks endpoint: /.well-known/assetlinks.json
  - Both return correct Content-Type, no gzip, no redirect
  - Test with curl from external network: curl -I https://customer1.deeplink.io/.well-known/apple-app-site-association
  - Send subdomain to each customer → they start Xcode + Android work NOW

DAY 2: Link creation (admin only, no dashboard yet)
  - links table: id, short_code, tenant, destination_path, uri_scheme, click_count
  - Artisan command or Tinker to create links manually (you, not customer)
  - Short code: 6-char nanoid
  - Landing page with correct JS (iOS URI scheme + Android intent://)
  - Atomic click increment (synchronous, no queue)
  - Rate limit: throttle:60,1 on redirect route (1 line, do NOT skip)
  - Test on real iPhone + real Android device (budget full day for this)

DAY 3: Minimal self-serve dashboard
  - Simple login (single hardcoded admin user per tenant, no registration yet)
  - List links → copy short URL
  - Create link form (destination path, URI scheme, title)
  - Total click count per link
  - That's it. No charts. No analytics. No OG meta.

DAY 4: React Native docs + customer handover
  - Write React Native integration guide (see below — only framework that matters now)
  - Send credentials + docs to 3 customers
  - Be on call for their questions (they will have questions)
  - Collect Razorpay payment before giving access

DAY 5-7: Fix what breaks
  - Real devices will find bugs your testing didn't
  - Apple CDN may reject AASA for undocumented reasons
  - Android verification may fail (wrong fingerprint, wrong package name)
  - Budget 3 days for support and fixes

AFTER 3 CUSTOMERS PAYING:
  - Add proper auth (email/password registration)
  - Add stancl/tenancy for real multi-tenant
  - Add OG meta to landing page
  - Add Sentry
  - Then look at Phase 2

KILL FOR V1 (do not touch):
  ✗ stancl/tenancy (hardcode 3 tenants in config, migrate later)
  ✗ Registration flow (you onboard manually)
  ✗ Custom domains
  ✗ Analytics charts
  ✗ REST API
  ✗ Webhooks
  ✗ Team members
  ✗ 2FA
  ✗ Subscription billing (Razorpay Payment Link only)
  ✗ Password-protected links
  ✗ Link expiry / max clicks
  ✗ OG image
  ✗ Plan enforcement
  ✗ Queue workers / Supervisor (not needed until analytics)
```

---

### React Native Integration Guide (ship this on Day 4)

**Step 1: Install dependency**
```bash
# React Native 0.72+ has built-in Linking API
# For navigation-based deep linking use react-navigation:
npm install @react-navigation/native
# OR for Expo:
npx expo install expo-linking
```

**Step 2: iOS — Xcode Associated Domains**
```
Xcode → Your Target → Signing & Capabilities → + Capability → Associated Domains
Add: applinks:yourslug.deeplink.io
```

Also update `ios/YourApp/AppDelegate.mm`:
```objc
// Already handled by React Native's LinkingManager — no code change needed
// Just ensure you have react-native-get-random-values or similar not blocking Linking
```

**Step 3: Android — Manifest Intent Filters**

In `android/app/src/main/AndroidManifest.xml`, inside your `<activity>`:
```xml
<!-- Universal App Links (deep link when app installed) -->
<intent-filter android:autoVerify="true">
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data
        android:scheme="https"
        android:host="yourslug.deeplink.io" />
</intent-filter>

<!-- URI Scheme fallback -->
<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="yourscheme" />
</intent-filter>
```

**Step 4: Handle link in React Native**
```javascript
import { Linking } from 'react-native';
import { useEffect } from 'react';

// With react-navigation (recommended)
const linking = {
    prefixes: [
        'https://yourslug.deeplink.io',
        'yourscheme://',
    ],
    config: {
        screens: {
            Product: 'products/:id',
            Profile: 'profile/:userId',
            Home: '',
        },
    },
};

// Pass to NavigationContainer:
// <NavigationContainer linking={linking}>

// Manual handling (without react-navigation):
useEffect(() => {
    // Handle link when app is already open
    const subscription = Linking.addEventListener('url', ({ url }) => {
        handleDeepLink(url);
    });

    // Handle link that opened the app from background/closed
    Linking.getInitialURL().then((url) => {
        if (url) handleDeepLink(url);
    });

    return () => subscription?.remove();
}, []);

function handleDeepLink(url) {
    // url = "https://yourslug.deeplink.io/xK9mP2"
    //    OR "yourscheme://products/123"
    const path = url.replace('https://yourslug.deeplink.io', '')
                    .replace('yourscheme:/', '');
    // Navigate based on path
    console.log('Deep link path:', path);
}
```

**Step 5: Get SHA-256 fingerprint for Android**
```bash
# Debug keystore (for testing):
keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android

# Release keystore:
keytool -list -v -keystore android/app/your-release.keystore -alias your-alias

# Copy the SHA-256 line — paste into our dashboard
# Format: AA:BB:CC:DD:... (95 characters with colons)
```

**Step 6: Verify Android App Links working**
```bash
# After installing updated APK:
adb shell pm get-app-links --package com.yourapp
# Should show: yourslug.deeplink.io: verified
```

**Step 7: Verify iOS Universal Links working**
- Check Apple CDN fetched your config:
  `https://app-site-association.cdn-apple.com/a/v1/yourslug.deeplink.io`
- Test: Open Notes app → type `https://yourslug.deeplink.io/testlink` → tap → app should open

**Known issue — iOS Universal Links stop working sometimes:**
- Long-press the link (in iMessage, Notes, etc.) → shows "Open in Safari" option
- This confirms Universal Links are working (iOS shows app option)
- If link opens Safari directly without offering app → app not installed OR AASA not fetched yet
- Fix: reinstall the app

**Known issue — Android App Links not verified after install:**
- Check: `adb shell pm get-app-links --package com.yourapp`
- If shows `none` or `failed` → SHA-256 fingerprint mismatch OR assetlinks.json had error during install
- Fix: re-check fingerprint in dashboard, rebuild APK, reinstall

---

### App Store Review Timeline (iOS) — Critical Path

```
Day 1: You give customer their subdomain + AASA is live
Day 1: Customer updates Xcode Associated Domains → builds new IPA
Day 1: Customer submits to App Store Connect for review
Day 2-4: Apple review (1-3 days typical, can be up to 7 days)
Day 4-5: New version approved → released to users
Day 5+: Users who update to new version get Universal Links working

Reality: even if your server is perfect on Day 1,
customers won't have working Universal Links for 4-5 days minimum.
Set this expectation immediately. It is NOT your fault.
```

---

## 23. Brutal Truth — Known Issues & Fixed Approaches

### Issue 1: Universal Link outcome tracking is impossible

**Problem:** When Universal Links work correctly, iOS opens app before browser loads. Our landing page JS never executes. We cannot detect "app opened" outcome — only "user landed on page" (which means app did NOT open or Universal Links not configured).

**Fix:**
- Never show "outcome: app_opened" in analytics. Remove this outcome.
- Replace with two honest outcomes only:
  - `page_loaded` — user hit our landing page (Universal Links did NOT intercept, or user is on desktop)
  - `store_redirect` — our JS sent them to App Store / Play Store
- To get real app-open tracking: client integrates our lightweight SDK (v2+ feature). SDK calls `POST /api/v1/track/app-open` on app launch when opened from deep link. Then we can attribute the open back to the link.
- Document this limitation clearly on the dashboard: "Clicks = link tapped. App opens tracked only with SDK integration."

---

### Issue 2: Android URI scheme broken in Chrome 92+ (2021)

**Problem:** `window.location = "myapp://path"` is blocked by Chrome Android from cross-origin pages. Silent failure — no error, app never opens, user sees blank or stays on page.

**Fixed JS redirect for Android:**

```javascript
function tryAndroidDeepLink(packageName, uriScheme, path) {
    // intent:// format — works in Chrome Android 92+
    const intentUrl = `intent://${path}#Intent;scheme=${uriScheme};package=${packageName};S.browser_fallback_url=${encodeURIComponent(androidStoreUrl)};end`;
    window.location = intentUrl;
    // No timeout needed — intent:// handles fallback internally via S.browser_fallback_url
}

function tryIOSDeepLink(uriScheme, path, storeUrl) {
    // iOS still works with bare URI scheme
    window.location = `${uriScheme}://${path}`;
    setTimeout(() => { window.location = storeUrl; }, 2500);
}

const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
const isAndroid = /Android/.test(navigator.userAgent);

if (isAndroid && config.linkType !== 'universal') {
    tryAndroidDeepLink(config.androidPackage, config.uriScheme, config.destinationPath);
} else if (isIOS && config.linkType !== 'universal') {
    tryIOSDeepLink(config.uriScheme, config.destinationPath, config.iosStoreUrl);
} else if (!isIOS && !isAndroid && config.webFallback) {
    setTimeout(() => { window.location = config.webFallback; }, 1500);
}
```

**New DB column needed:** `apps.android_package_name` already exists — pass to JS config.

**Android intent:// advantages:**
- `S.browser_fallback_url` = Chrome handles fallback automatically (no JS timeout hack)
- Works across Chrome, Samsung Browser, Firefox Android
- App not installed → Chrome opens `browser_fallback_url` directly

---

### Issue 3: Custom domain SSL with Apache — do it right

**Problem:** Dynamic Apache vhost generation + reload in a queue job = fragile. `apache2ctl configtest` can fail silently mid-reload. Let's Encrypt rate limit = 5 certs/domain/week.

**Fixed approach:**

1. **Use a dedicated `ssl_domains/` directory** for generated vhost files, not `sites-available/`
2. **Validate vhost file before enabling** — run `apache2ctl configtest` in the job, abort if exit code != 0
3. **Let's Encrypt rate limit protection** — store `ssl_issued_at`, never re-issue cert if issued < 7 days ago
4. **Retry strategy** — if ACME challenge fails, retry after 30 min (not immediate), max 5 attempts over 3 days
5. **Staging certificate first** — issue Let's Encrypt staging cert to verify ACME flow works before issuing real cert (staging has no rate limit)
6. **Never reload Apache synchronously in request** — always in queue job with `ssl` channel

```php
class IssueSslCertificate implements ShouldQueue
{
    public int $timeout = 300;
    public int $tries = 5;
    public int $backoff = 1800; // retry every 30 min

    public function handle(): void
    {
        $domain = $this->domain;

        // Guard: check CNAME resolves to our IP before attempting ACME
        $resolved = gethostbyname($domain->domain);
        if ($resolved !== config('app.server_ip')) {
            // CNAME not propagated yet — release job back to queue
            $this->release(600);
            return;
        }

        // Issue cert via certbot
        $result = Process::run(
            "certbot certonly --webroot -w /var/www/deeplink/public "
            . "--domain {$domain->domain} --non-interactive --agree-tos "
            . "--email admin@deeplink.io --cert-name {$domain->domain}"
        );

        if ($result->failed()) {
            Log::error('SSL issuance failed', ['domain' => $domain->domain, 'output' => $result->errorOutput()]);
            $this->fail(new \Exception($result->errorOutput()));
            return;
        }

        // Generate vhost file
        $vhostContent = view('vhosts.custom-domain', ['domain' => $domain->domain])->render();
        $vhostPath = "/etc/apache2/ssl-domains/{$domain->domain}.conf";
        file_put_contents($vhostPath, $vhostContent);

        // Test config before reload
        $test = Process::run('apache2ctl configtest');
        if ($test->failed()) {
            unlink($vhostPath); // rollback
            $this->fail(new \Exception('Apache config test failed: ' . $test->errorOutput()));
            return;
        }

        // Enable and reload
        Process::run("a2ensite ssl-domains/{$domain->domain}.conf");
        Process::run('systemctl reload apache2');

        $domain->update([
            'ssl_issued_at' => now(),
            'ssl_expires_at' => now()->addDays(89),
            'status' => 'active',
        ]);
    }
}
```

---

### Issue 4: stancl/tenancy — cache tenant lookup

**Problem:** Every redirect request hits DB to resolve tenant from Host header. Redirect is the hottest path.

**Fix:**

```php
// TenantResolver — override stancl's default
class CachedTenantResolver
{
    public function resolve(string $host): ?Tenant
    {
        $cacheKey = "tenant:domain:{$host}";

        return Cache::remember($cacheKey, 300, function () use ($host) {
            return Domain::with('tenant')
                ->where('domain', $host)
                ->where('status', 'active')
                ->first()
                ?->tenant;
        });
    }
}

// Cache invalidation: when domain status changes or tenant deactivated
Cache::forget("tenant:domain:{$domain}");
```

**Add integration test:** Create two tenants, verify tenant A's Host header never returns tenant B's data.

---

### Issue 5: Razorpay Indian compliance gaps

**Gap 1: GST Invoice (mandatory for B2B India)**
- Every paid invoice must have: GSTIN of buyer (if B2B), HSN code, GST rate, CGST/SGST/IGST split
- SaaS = SAC code 998314 (Information Technology Software Service)
- GST rate: 18%
- Solution: Use **Zoho Books API** or **Razorpay Invoice** for auto-generation. Do NOT build from scratch.
- Collect GSTIN during account setup for B2B tenants (optional field)

**Gap 2: UPI AutoPay (e-NACH mandate)**
- UPI recurring for subscriptions requires RBI-compliant e-mandate flow
- First payment: customer approves mandate in UPI app (different UX from card)
- Subsequent payments: deducted automatically, customer gets UPI notification
- Razorpay handles mandate creation — but UI must inform user about this flow
- Mandate limit per RBI: ₹15,000/transaction for UPI AutoPay

**Gap 3: International customers via Razorpay**
- Requires "International Payments" enabled on Razorpay account (approval needed)
- Offer USD pricing separately; collect via international card
- FEMA compliance: file returns for foreign remittances above threshold

**Gap 4: DPDP Act 2023 (India data protection)**
- Privacy policy must specify: data collected, purpose, retention period, user rights
- Must have mechanism for users to: access their data, delete their data
- Consent for analytics tracking required
- Data must not be transferred outside India without consent (we use ap-south-1 — compliant)

---

### Issue 6: Analytics table will die — pre-aggregate from day 1

**Problem:** Querying `link_clicks` directly for dashboards = full table scan at scale.

**Fixed architecture — two-tier analytics:**

```sql
-- Raw events (insert-only, never query for dashboards)
CREATE TABLE link_clicks (
    -- same schema as before
    -- indexed on (link_id, clicked_at) only
    -- partitioned by YEAR(clicked_at)
);

-- Pre-aggregated hourly (built by background job every 5 min)
CREATE TABLE link_click_hourly (
    id              BIGINT UNSIGNED PK AUTO_INCREMENT,
    link_id         BIGINT UNSIGNED NOT NULL,
    tenant_id       VARCHAR(255) NOT NULL,
    hour            DATETIME NOT NULL,              -- truncated to hour: 2025-09-01 14:00:00
    platform        ENUM('ios','android','desktop','bot','unknown'),
    country_code    CHAR(2) NULL,
    outcome         VARCHAR(50) NOT NULL,
    total_clicks    INT UNSIGNED DEFAULT 0,
    unique_clicks   INT UNSIGNED DEFAULT 0,

    UNIQUE: (link_id, hour, platform, country_code, outcome)
    INDEX: (tenant_id, hour), (link_id, hour)
);

-- Pre-aggregated daily (built nightly from hourly)
CREATE TABLE link_click_daily (
    -- same structure as hourly, but hour → date DATE
    UNIQUE: (link_id, date, platform, country_code, outcome)
);
```

**Dashboard queries now hit only `link_click_daily` — tiny table, fast.**

```php
// AggregateClickStats job — runs every 5 minutes via scheduler
class AggregateClickStats implements ShouldQueue
{
    public function handle(): void
    {
        // Find clicks not yet aggregated (last 15 min window)
        $from = now()->subMinutes(15)->startOfMinute();
        $to = now()->subMinutes(5)->endOfMinute();

        DB::statement("
            INSERT INTO link_click_hourly
                (link_id, tenant_id, hour, platform, country_code, outcome, total_clicks, unique_clicks)
            SELECT
                link_id, tenant_id,
                DATE_FORMAT(clicked_at, '%Y-%m-%d %H:00:00') as hour,
                platform, country_code, outcome,
                COUNT(*) as total_clicks,
                SUM(is_unique) as unique_clicks
            FROM link_clicks
            WHERE clicked_at BETWEEN ? AND ?
              AND outcome != 'bot_filtered'
            GROUP BY link_id, tenant_id, hour, platform, country_code, outcome
            ON DUPLICATE KEY UPDATE
                total_clicks = total_clicks + VALUES(total_clicks),
                unique_clicks = unique_clicks + VALUES(unique_clicks)
        ", [$from, $to]);
    }
}
```

---

### Issue 7: Redis — enable persistence (AOF)

**Problem:** Redis default = no persistence. Crash = all queued click events lost = wrong analytics.

**Fix in `/etc/redis/redis.conf`:**
```
# Append-Only File persistence
appendonly yes
appendfsync everysec          # flush to disk every second (balance safety vs performance)
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb

# Also keep RDB snapshots as backup
save 900 1
save 300 10
save 60 10000
```

**Supervisor clicks workers already configured with `--tries=3`** — failed jobs retry 3x before going to failed_jobs table. With AOF enabled, Redis restart preserves queue.

---

### Issue 8: Worker count — start small

**v1 Supervisor config (scale later):**
```ini
[program:deeplink-default-worker]
numprocs=2          ; start with 2, not 4

[program:deeplink-clicks-worker]
numprocs=2          ; start with 2, not 8 — scale when queue depth grows
```

Monitor queue depth weekly. Scale `numprocs` when `queue:failed` jobs appear or queue depth > 1000 persistently.

---

### Issue 9: AASA caching — document for clients

Add prominent warning in dashboard after app config is saved:

> **Important: Universal Links take time to activate**
>
> Apple caches your configuration file (AASA) via their CDN. After saving your app config:
> - Users who already have your app installed: must **reinstall the app** to pick up new config
> - New installs: will work within **24–72 hours** of your first save
> - Changes to existing config: Apple re-fetches periodically, up to **72 hours**
>
> To check if Apple has fetched your config:
> `https://app-site-association.cdn-apple.com/a/v1/yourdomain.deeplink.io`
>
> Android re-fetches `assetlinks.json` on each app install. Config changes require **re-install**.

---

### Issue 10: Pricing — revise for Indian market

```
Free:     0         — 100 links, 10k clicks/mo, 1 app
Starter:  ₹499/mo   — 2,000 links, 100k clicks/mo, 2 apps  ← new tier
Pro:      ₹1,499/mo — 10,000 links, 500k clicks/mo, 5 apps, API access
Business: ₹4,999/mo — unlimited links + clicks, 20 apps, custom domains, webhooks

International (USD, for non-Indian billing):
Starter:  $9/mo
Pro:      $29/mo
Business: $79/mo
```

Razorpay supports multi-currency. Create separate plan IDs per currency.

---

### Issue 11: Mobile integration docs — ship with v1

Without docs, clients fail setup and blame the product. Must ship with v1.

**Required doc pages (in-app, not external):**

**iOS Integration Guide:**
```swift
// 1. Xcode → Signing & Capabilities → + Capability → Associated Domains
// Add: applinks:yourslug.deeplink.io

// 2. In SceneDelegate.swift:
func scene(_ scene: UIScene, continue userActivity: NSUserActivity) {
    guard userActivity.activityType == NSUserActivityTypeBrowsingWeb,
          let url = userActivity.webpageURL else { return }
    // url = https://yourslug.deeplink.io/xK9mP2
    // Call your API to resolve destination, then navigate
    DeepLinkRouter.handle(url: url)
}

// 3. For URI scheme fallback, add to Info.plist:
// CFBundleURLSchemes: ["yourscheme"]
// Handle in application(_:open:options:)
```

**Android Integration Guide:**
```xml
<!-- AndroidManifest.xml — inside your launcher Activity -->
<intent-filter android:autoVerify="true">
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data
        android:scheme="https"
        android:host="yourslug.deeplink.io" />
</intent-filter>
<!-- Also add URI scheme intent filter -->
<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="yourscheme" />
</intent-filter>
```

```kotlin
// In Activity.onCreate and onNewIntent:
override fun onCreate(savedInstanceState: Bundle?) {
    super.onCreate(savedInstanceState)
    handleIntent(intent)
}
override fun onNewIntent(intent: Intent) {
    super.onNewIntent(intent)
    handleIntent(intent)
}
fun handleIntent(intent: Intent) {
    val data = intent.data ?: return
    // data.toString() = "https://yourslug.deeplink.io/xK9mP2" or "yourscheme://path"
    DeepLinkRouter.handle(data)
}
```

**Verification checklist shown in dashboard:**
- [ ] Associated Domain added in Xcode
- [ ] AASA file fetched (auto-check via Apple CDN endpoint)
- [ ] Test: tap link in Notes app on real iPhone → app opens
- [ ] `autoVerify="true"` added to Android manifest
- [ ] assetlinks.json valid (auto-check via Google Digital Asset Links API)
- [ ] Test: `adb shell pm get-app-links --package com.yourapp` shows domain verified

---

## 24. Phase 2+ Roadmap — After 10 Paying Customers

> Trigger: 10 tenants paying ≥ ₹499/mo = ₹4,990/mo MRR. Then and only then build Phase 2.
> Priority order based on customer interview feedback, not assumption.

---

### Phase 2A — Analytics (most requested feature, high retention impact)

**Scope:**
- Replace click counter with full `link_click_hourly` + `link_click_daily` aggregation pipeline (see Issue 7 fix)
- Dashboard: clicks over time chart (7d/30d), platform pie, country map, outcome breakdown
- Link detail page: full analytics for single link
- Export: CSV download of click data

**Effort:** 2 weeks

**DB changes:**
- Add `link_click_hourly` table
- Add `link_click_daily` table
- Backfill from existing `link_clicks` data
- Add `AggregateClickStats` scheduled job (every 5 min)
- Add `AggregateDailyStats` scheduled job (nightly)

**Retention query for pruning:**
```php
// Per plan retention settings
$schedule->call(function () {
    $freeRetentionDays = 30;
    $proRetentionDays = 365;

    // Delete raw clicks older than plan retention
    DB::table('link_clicks')
        ->where('clicked_at', '<', now()->subDays($freeRetentionDays))
        ->whereIn('link_id', Link::whereHas('tenant', fn($q) =>
            $q->where('plan_name', 'free')
        )->pluck('id'))
        ->delete();
})->daily();
```

---

### Phase 2B — Razorpay Subscription Billing (revenue automation)

**Scope:**
- Self-serve plan upgrade/downgrade via Razorpay subscriptions
- Razorpay Checkout on billing page
- Webhook handler for all subscription events
- `subscriptions` + `payments` tables (schema in Phase 9)
- Plan limit enforcement (links, clicks, apps)
- Usage warning emails at 80% / 100% limits
- Invoices via Razorpay or Zoho Books API

**Effort:** 2 weeks

**Critical path:**
1. Create Razorpay plans via API (or dashboard) — one-time setup
2. Build subscription create endpoint + Checkout JS integration
3. Build webhook handler with signature verification
4. Build plan enforcement middleware (check limits on link create)
5. Build downgrade handler (FIFO link deactivation)
6. GST invoice: integrate Zoho Books API or Razorpay Invoice — do NOT build from scratch

**GST invoice minimum fields:**
```
Invoice Number (sequential, prefix: INV-)
Invoice Date
Seller: company name, address, GSTIN, SAC code (998314)
Buyer: name, email, address, GSTIN (if B2B)
Line item: "DeepLink SaaS - Pro Plan - [Month Year]"
Amount (ex-GST): ₹X
GST 18%: ₹Y
Total: ₹Z
```

---

### Phase 2C — REST API + API Keys

**Scope:**
- API key creation/revocation in dashboard
- `api_keys` table (schema in Phase 8)
- All CRUD endpoints from Phase 8 plan
- Rate limiting per plan tier
- API docs page (simple Blade page, not Swagger — too complex for v2)

**Effort:** 1.5 weeks

**Implementation order:**
1. API key middleware (hash lookup, scope check)
2. Link endpoints (CRUD + resolve)
3. App read endpoints
4. Analytics overview endpoint
5. Rate limiter with `X-RateLimit-*` headers

---

### Phase 2D — Custom Domains + SSL (hardest feature)

**Scope:**
- DNS TXT ownership verification
- CNAME propagation detection
- Let's Encrypt SSL via certbot (Apache HTTP-01 challenge)
- Dynamic Apache vhost generation
- Domain status flow: pending → verifying → ssl_pending → active

**Effort:** 3 weeks (biggest Phase 2 item)

**Prerequisites before building:**
- Must have Apache `ssl-domains/` directory configured in main vhost include
- Must have `certbot` installed with webroot plugin
- Must have `sudo` configured for `www-data` to run specific certbot + apache commands (no full sudo)
- Must have server IP documented in `config/app.php` for CNAME verification

**`/etc/sudoers.d/deeplink-certbot`:**
```
www-data ALL=(ALL) NOPASSWD: /usr/bin/certbot certonly *
www-data ALL=(ALL) NOPASSWD: /usr/sbin/apache2ctl configtest
www-data ALL=(ALL) NOPASSWD: /usr/sbin/a2ensite *
www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload apache2
```

**Risk mitigation:**
- Test entire flow on staging with Let's Encrypt staging endpoint first
- Build retry logic with exponential backoff (not immediate retry — LE rate limits)
- Alert admin via Sentry if SSL issuance fails after 5 attempts
- Show domain status in client dashboard at all times (not just success/fail)

---

### Phase 2E — Team Members

**Scope:**
- Invite by email
- Roles: owner, admin, member
- `tenant_users` table + `spatie/laravel-permission`
- Permission checks on all controllers
- Invite expiry (48h), resend invite

**Effort:** 1 week

**Limit per plan:**
```
Free: 1 member (owner only)
Starter: 2 members
Pro: 5 members
Business: 15 members
```

---

### Phase 3 — After ₹1 Lakh MRR (~20-30 paying customers)

**Build when MRR > ₹1,00,000/mo:**

#### 3A — Webhooks
- `webhooks` + `webhook_deliveries` tables (schema in Phase 8)
- Event: `link.clicked`, `link.created`, `link.expired`
- HMAC-SHA256 signature header
- Retry logic (3 attempts: 0min, 5min, 30min)
- Disable after 10 consecutive failures
- Effort: 1.5 weeks

#### 3B — App SDK (lightweight)
- Solve the outcome tracking problem (Issue 1)
- iOS Swift package: calls `POST /api/v1/track/open` on app launch from deep link
- Android library: same via Kotlin
- Flutter plugin wrapping both
- Revenue unlock: "SDK Verified Opens" analytics — premium feature
- Effort: 3 weeks (cross-platform)

#### 3C — 2FA (TOTP)
- `pragmarx/google2fa-laravel` already in composer
- QR code setup, 6-digit confirm before enable
- Recovery codes (8 single-use codes)
- Effort: 3 days

#### 3D — White-Label (Enterprise tier)
- Remove "Powered by DeepLink" branding from landing page
- Custom email sender domain
- Custom dashboard subdomain: `app.clientcorp.com`
- Pricing: ₹19,999/mo or ₹50,000/mo depending on usage
- Effort: 1 week (mostly config + billing)

#### 3E — Link-in-Bio (product expansion)
- Single page with multiple links (like Linktree)
- Same deep link infrastructure, new UI type
- Revenue: expands use case beyond mobile devs to influencers, businesses
- Effort: 2 weeks

#### 3F — Status Page
- `status.deeplink.io`
- Monitor: well-known endpoint per tenant domain (sampled), redirect response time, DB health
- Public status page showing uptime
- Incident history
- Use `cachet/cachet` or build minimal Blade page
- Effort: 1 week

---

### Phase 4 — Infrastructure Scaling (after 100 paying customers)

**Trigger:** P95 redirect latency > 300ms OR single Lightsail instance at >70% CPU consistently.

**Changes:**
- Add second Lightsail app instance ($20/mo) behind Lightsail Load Balancer ($18/mo)
- MySQL: add read replica for analytics queries (`DB::connection('analytics')`)
- Redis: enable Redis Cluster or upgrade to ElastiCache (ap-south-1)
- Supervisor: scale `deeplink-clicks-worker` numprocs from 2 → 8 on each server
- Cloudflare: enable caching for well-known files (custom cache rule, 1hr TTL)
- Add `link_click_monthly` aggregation table for long-range analytics
- Consider migrating from Apache to Caddy for automatic SSL (removes custom domain SSL complexity)

**Total infra cost at this stage:** ~₹15,000/mo ($180/mo) — still profitable at ₹1L+ MRR.

---

### Phase 5 — Enterprise Features (after ₹5 Lakh MRR)

- **Dedicated instance per enterprise tenant** — full isolation, custom SLA
- **SOC 2 Type II audit** — required for enterprise sales
- **SSO (SAML/OIDC)** — enterprise identity providers (Okta, Azure AD)
- **Advanced analytics** — cohort analysis, A/B link testing, conversion funnels
- **Bulk link import** — CSV upload for migrating from Firebase Dynamic Links
- **Link retargeting** — pass UTM params through to destination
- **QR code generation** — auto-generate QR for each link

---

## Validation Checkpoints

| Milestone | Metric | Action |
|---|---|---|
| After v1 launch | Any 1 paying customer | Validate problem is real |
| After 3 customers | Common feature request identified | Build that next, not the plan |
| After 10 customers | ₹4,990+ MRR | Start Phase 2A (analytics) |
| After 25 customers | ₹25,000+ MRR | Start Phase 2D (custom domains) |
| After 50 customers | ₹50,000+ MRR | Hire 1 part-time support person |
| After ₹1L MRR | ~30-40 customers | Start Phase 3 (SDK, webhooks) |
| After ₹5L MRR | ~100+ customers | Enterprise sales motion begins |

> **Rule:** Never build Phase N+1 until Phase N milestone is hit. Market always surprises — let customers tell you what to build next.

---

*Document version: 2.0 | Updated: 2026-06-01 | Stack: Laravel 11 + Blade + MySQL + Redis + Apache + Supervisor + Razorpay*
*V1 target: 3 weeks to first paying customer. Everything else is Phase 2+.*
