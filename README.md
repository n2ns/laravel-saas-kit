# SaaS Kit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/n2ns/laravel-saas-kit.svg)](https://packagist.org/packages/n2ns/laravel-saas-kit)
[![Total Downloads](https://img.shields.io/packagist/dt/n2ns/laravel-saas-kit.svg)](https://packagist.org/packages/n2ns/laravel-saas-kit)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](./LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.3-8892bf.svg)](https://www.php.net)
[![Laravel Version Support](https://img.shields.io/badge/Laravel-12-red)](https://laravel.com)

Installable Laravel 12 starter kit for SaaS and product websites.

This package installs a complete editable Laravel application into a fresh Laravel 12 project. It does not start from an empty skeleton: the installer copies proven application files for authentication, billing, content, SEO, analytics, and admin workflows, then the new product site owns those files.

## What This Package Is

`n2ns/laravel-saas-kit` is a Composer starter-kit package:

```bash
composer require n2ns/laravel-saas-kit
php artisan saas-kit:install --force
```

It is intentionally closer to Laravel Breeze / Jetstream installer behavior than to a runtime-only package. The package's job is to create a complete editable product-site codebase quickly.

## What It Installs

- Marketing site pages, header, footer, policy pages, 404, and 503.
- Blog, product catalog, product detail pages, pricing pages, product articles, SEO, and sitemap commands.
- Google OAuth, Google One Tap, OTT login, Passport API auth, refresh tokens, device sessions, and API keys.
- Stripe Checkout, webhook handling, Customer Portal, plans, orders, subscriptions, and credits.
- Filament admin resources for users, sessions, products, plans, orders, subscriptions, content, API keys, Stripe webhooks, and analytics.
- Site visit and event analytics.
- Starter seed data for one product, Free / Plus / Credits plans, and a Stripe gateway.
- Deployment, test, and maintenance scripts from the template app.

Product-specific business workflows, provider integrations, and customer-specific data belong in the host application after installation.

## Quick Start

Use a Laravel 12 application. Pinning `laravel/laravel:^12.0` avoids accidentally creating a Laravel 13 app when that is the current default.

```bash
composer create-project laravel/laravel:^12.0 my-site
cd my-site

composer require n2ns/laravel-saas-kit
php artisan saas-kit:install --force
composer dump-autoload

php artisan migrate:fresh --force
php artisan passport:keys --force
php artisan passport:ensure-social-client --create

npm install
npm run build
```

`migrate:fresh --force` is intentional for a brand-new Laravel application. The kit replaces Laravel's default identity tables with the template site's identity, billing, content, analytics, and API tables.

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Package Structure](docs/PACKAGE_STRUCTURE.md)
- [API Google Authentication](docs/API_AUTH.md)
- [Template Maintenance](docs/TEMPLATE_MAINTENANCE.md)
- [Post2Site Integration](docs/POST2SITE.md)
- [Verification](docs/VERIFICATION.md)

After installation, the generated product site also contains its own docs:

- `README.md`
- `NEW_SITE_CHECKLIST.md`
- `DEPLOYMENT.md`
- `SCRIPTS.md`

## Operating Model

For each new product site:

1. Create a fresh Laravel 12 app.
2. Install this package.
3. Run `saas-kit:install --force`.
4. Configure environment variables for Google, Stripe, mail, storage, database, queue, and the app URL.
5. Replace branding, legal copy, default product data, and assets.
6. Publish products, plans, blog posts, and product articles through the admin panel.
7. Add product-specific code in the host app.

Optional automated content publishing is handled by `n2ns/laravel-post2site` with `POST2SITE_PRESET=laravel_saas_kit`. That integration writes to this kit's `blog_posts` and `blog_post_translations` tables and supports product guides at `/{productCode}/guides/{slug}`.

This kit does not need a separate blog package. Its own content tables, public routes, SEO helpers, sitemap commands, and Filament resources are the supported publishing surface for generated product sites.

The older template MCP routes are disabled by default with `SAAS_KIT_LEGACY_MCP_ROUTES=false` so Post2Site can own `/api/v1/mcp`.

Shared improvements should be made in this package's installer stubs so future product sites inherit the same implementation.

---

Built by [N2NS Lab](https://n2ns.com), an open-source lab for practical AI developer tools.
