# SaaS Starter

Laravel product-site template extracted from the current main site implementation.

This is intentionally a template application. The goal is to reuse a proven implementation for authentication, billing, products, content, SEO, access analytics, and admin operations while leaving product-specific business code to each product site.

## Relationship to Laravel SaaS Kit

`n2ns/laravel-saas-kit` installs this starter application into a fresh Laravel application. After installation, the generated product site owns the copied files and should replace the starter identity, products, plans, legal copy, assets, OAuth credentials, and Stripe IDs.

## Included

- Google web login through Laravel Socialite.
- Google One Tap using the same provider pattern as the main site.
- API Google ID token login through Passport social grant.
- Long-lived refresh tokens, device sessions, multi-device revoke, and `auth_epoch`.
- Stripe Checkout, webhooks, Customer Portal, orders, subscriptions, and plans.
- Product catalog, product pages, pricing pages, blog, preview routes, sitemap, canonical/structured-data helpers.
- Filament admin for users, sessions, products, plans, subscriptions, orders, blog posts, API keys, and access analytics.
- Site visit/event tracking through `TrackSiteVisit`, `SiteAnalyticsTracker`, and the site analytics tables.
- Automated content publishing through `n2ns/laravel-post2site`; the older template MCP routes stay disabled unless `SAAS_KIT_LEGACY_MCP_ROUTES=true`.
- Starter seed data for one product, Free/Plus/Credits plans, and Stripe gateway.

## Not Included

- Product-specific business workflows, provider integrations, prompts, or automation rules.
- Production data, Stripe live IDs, webhook payloads, or exported production seed snapshots.
- Earlier experimental package implementations.

## Setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan passport:keys

touch database/database.sqlite
php artisan migrate:fresh --force
php artisan passport:ensure-social-client --create
php artisan db:seed --class=ReferenceDataSeeder --force
npm run build
php artisan serve
```

Configure these before a real launch:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
# Optional override. Defaults to APP_URL/auth/google/callback.
# GOOGLE_REDIRECT_URI=https://your-site.com/auth/google/callback

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PORTAL_CONFIGURATION=

STARTER_STRIPE_PRODUCT_ID=
STARTER_PLUS_MONTHLY_STRIPE_PRICE_ID=
STARTER_CREDITS_10_STRIPE_PRICE_ID=

PASSPORT_PASSWORD_CLIENT_ID=
PASSPORT_PASSWORD_CLIENT_SECRET=
```

Copy the Passport client values printed by
`php artisan passport:ensure-social-client --create` into `.env` before using
external-client API login. See [API_AUTH.md](API_AUTH.md).

## Verification

```bash
composer validate
composer check
npm audit --audit-level=high
npm run build
```

Tests use SQLite in memory by default. MySQL/MariaDB production migrations still create fulltext indexes; SQLite test runs skip those fulltext indexes.

## New Product Sites

After installing with Laravel SaaS Kit, follow
[NEW_SITE_CHECKLIST.md](NEW_SITE_CHECKLIST.md) to replace starter identity,
product codes, OAuth settings, Stripe IDs, legal copy, assets, deployment
paths, and launch checks.

## Deployment

Production deployment notes, the deployment script, nginx example, and systemd
queue worker example are in [DEPLOYMENT.md](DEPLOYMENT.md).

Script behavior and options are documented in [SCRIPTS.md](SCRIPTS.md).
External-client API login setup is documented in [API_AUTH.md](API_AUTH.md).

## Operating Model

Use the admin panel to replace the starter product, plans, blog posts, SEO fields, and legal pages with the new product site's own content.

For automated publishing, use `n2ns/laravel-post2site` with `POST2SITE_PRESET=laravel_saas_kit`. The site publishes through its built-in `blog_posts` and `blog_post_translations` tables; it does not need another blog package.

Product-specific code belongs in the product site after the template is copied. Shared improvements that apply to every product site should be backported into this template.
