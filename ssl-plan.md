# SSL Automation Strategy for DeepLink SaaS

Managing SSL certificates in a multi-tenant application involves two separate challenges: securing the workspaces (subdomains) and securing custom domains.

Here is the proposed strategy for automating SSL generation across the platform.

## 1. Workspace Subdomains (e.g., `*.deeplink.trentiums.com`)

**The good news:** You **do not** need to generate a new SSL certificate every time a user creates a workspace!

Because you have configured a wildcard DNS record (`*.deeplink.trentiums.com`), we will generate a single **Wildcard SSL Certificate** for your server. This single certificate covers your main domain and an infinite number of tenant subdomains instantly.

### Implementation Steps (Manual Server Setup, 1-time):
To get a wildcard certificate from Let's Encrypt, you cannot use the standard HTTP challenge. You must use the **DNS-01 Challenge**.

1. Install Certbot on your server.
2. Run the certbot command with manual DNS verification:
   ```bash
   sudo certbot certonly --manual --preferred-challenges dns -d "deeplink.trentiums.com" -d "*.deeplink.trentiums.com"
   ```
3. Certbot will pause and ask you to create a `TXT` record in your DNS settings (e.g., `_acme-challenge.deeplink.trentiums.com`).
4. Once verified, the certificate is saved.
5. Update your Apache/Nginx config to use this wildcard certificate. Every new workspace will instantly be secured via HTTPS!

*(Note: For automatic renewals, you will eventually want to use a Certbot DNS plugin for your specific DNS provider, like Cloudflare or AWS Route53).*

---

## 2. Custom Domains (e.g., `link.theirbrand.com`)

When a user adds their own custom domain, the wildcard certificate will not work. We must dynamically provision a new SSL certificate. This is tricky on standard Apache/Nginx because triggering `certbot` from PHP requires giving the web server `root/sudo` privileges, which is a massive security risk.

Here are the proposed approaches for handling Custom Domain SSL. Please review and let me know which you prefer:

### Option A: Use Caddy Web Server (Highly Recommended for SaaS)
Instead of Apache or Nginx, we install **Caddy** as a reverse proxy on your server. 
- **How it works:** Caddy has built-in "On-Demand TLS". When a user points their custom domain to your IP, Caddy asks Laravel (`/api/check-domain`) if the domain is valid. If yes, Caddy automatically provisions and renews the Let's Encrypt SSL certificate in the background in milliseconds.
- **Pros:** Zero PHP code needed. Zero cron jobs. Extremely scalable and secure. This is the industry standard for modern SaaS (used by platforms like Fathom Analytics).
- **Cons:** You have to switch your front-facing server from Apache to Caddy (Caddy can still route traffic back to Apache on a different port if needed).

### Option B: Laravel Queue + Root Cron Job (Apache/Nginx Native)
Keep Apache, but build an asynchronous provisioning system.
- **How it works:** 
  1. User adds custom domain in the UI.
  2. Laravel adds the domain to a `pending_ssl_domains` database table.
  3. A bash script running as `root` on your server via Cron (every minute) checks this table.
  4. If it finds a new domain, the root script runs `certbot --apache -d link.theirbrand.com`, writes the config, and restarts Apache.
- **Pros:** Keeps your current Apache setup.
- **Cons:** Complex to build. Requires maintaining root bash scripts. Apache has to reload continuously as new customers onboard, which can drop live connections.
