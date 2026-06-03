# 🔗 DeepLink SaaS

> **Multi-tenant deep link management platform** — the self-hosted alternative to Firebase Dynamic Links and Branch.io.

Create, manage, and track deep links that intelligently route users to your iOS app, Android app, or web fallback — with full multi-tenancy, analytics, and team collaboration.

---

## ✨ Features

### Core (Phase 1–5 — Implemented)
- **Universal Links / App Links** — Serves `.well-known/apple-app-site-association` and `assetlinks.json` per tenant
- **Smart Redirect Engine** — Platform detection → Universal Link → URI scheme → App Store → web fallback cascade
- **Multi-tenancy** — Domain-based tenant isolation via [stancl/tenancy](https://tenancyforlaravel.com/) with shared database strategy
- **App Registration** — Register iOS and Android apps with bundle IDs, SHA-256 fingerprints, store URLs
- **Link Management** — Create short links with custom codes, passwords, expiration, max clicks, UTM params
- **Async Click Logging** — Non-blocking click tracking dispatched to queue with IP hashing for privacy
- **Bot Detection** — Serves OG meta tags to crawlers without triggering redirects
- **Auth System** — Registration, login, password reset, email verification, 2FA via Google Authenticator

### Planned
- Custom domain support with SSL (Phase 6)
- Analytics dashboard with charts (Phase 7)
- REST API with API key authentication (Phase 8)
- Razorpay billing integration (Phase 9)
- Security hardening & rate limiting audit (Phase 10)

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|-----------|
| **Framework** | Laravel 12 |
| **Multi-tenancy** | stancl/tenancy v3 (single DB, domain identification) |
| **Database** | MySQL 8 |
| **Cache / Queue** | Redis (via predis) — configurable via `.env` |
| **Auth** | Laravel built-in + PragmaRX Google2FA |
| **Frontend** | Blade templates + Vite |
| **Roles** | Owner / Admin / Member (via `tenant_users` pivot) |

---

## 🚀 Local Setup

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8+
- Node.js 18+ & npm
- Redis (optional, for cache/queue)

### Installation

```bash
# Clone the repository
git clone <your-repo-url> deep-link
cd deep-link

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure your database in .env
# DB_DATABASE=deeplink_saas
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed plans (Free, Pro, Business)
php artisan db:seed

# Build frontend assets
npm run dev
```

### Domain Configuration

For local development, add these to your hosts file:

```
127.0.0.1   app.deeplink.test
127.0.0.1   demo.deeplink.test
```

Update `.env`:
```
APP_URL=http://app.deeplink.test
TENANCY_CENTRAL_DOMAINS="app.deeplink.test"
TENANT_URL_PATTERN="{tenant}.deeplink.test"
```

---

## 📁 Architecture Overview

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/          # Login, Register, 2FA, Password Reset
│   │   ├── AppController  # App CRUD (tenant-scoped)
│   │   ├── LinkController # Link CRUD (tenant-scoped)
│   │   ├── LinkRedirectController  # Smart redirect engine
│   │   └── WellKnownController    # AASA + assetlinks serving
│   ├── Middleware/
│   │   └── EnsureTenantAccess     # Validates user belongs to session tenant
│   └── Requests/          # Form validation (AppRequest, LinkRequest)
├── Models/
│   ├── Concerns/
│   │   └── BelongsToTenant  # Global scope trait for tenant isolation
│   ├── App, Link, Domain, LinkClick, Tenant, User, Plan, TenantUser
├── Policies/              # Authorization (AppPolicy, LinkPolicy)
├── Services/              # AASA, Assetlinks, BotDetector, PlatformDetector, ShortCodeGenerator
└── Providers/
    ├── AppServiceProvider
    └── TenancyServiceProvider

routes/
├── web.php     # Central routes (auth, dashboard, CRUD)
└── tenant.php  # Tenant domain routes (well-known, redirects)
```

### Tenant Isolation Strategy
- **Single shared database** with `tenant_id` column on all tenant-owned tables
- **Global scopes** via `BelongsToTenant` trait — auto-filters all queries
- **EnsureTenantAccess middleware** — validates session tenant against `tenant_users` pivot
- **Laravel Policies** — authorization checks on App and Link models

---

## 📋 Plan Limits

| Feature | Free | Pro | Business |
|---------|------|-----|----------|
| Links | 100 | 10,000 | Unlimited |
| Clicks/mo | 10,000 | 500,000 | Unlimited |
| Apps | 1 | 5 | 20 |
| Team Members | 1 | 3 | 10 |
| Custom Domains | 0 | 1 | 5 |
| API Access | ❌ | ✅ | ✅ |
| Analytics Retention | 30 days | 1 year | 2 years |

---

## 🧪 Testing

```bash
php artisan test
```

---

## 📄 License

Proprietary. All rights reserved.
