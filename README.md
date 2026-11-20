# SaaS Kit

[![CI](https://img.shields.io/github/actions/workflow/status/n2ns/laravel-saas-kit/ci.yml?branch=main&label=CI)](https://github.com/n2ns/laravel-saas-kit/actions/workflows/ci.yml)
[![license](https://img.shields.io/github/license/n2ns/laravel-saas-kit)](https://github.com/n2ns/laravel-saas-kit/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.3-8892bf.svg)](https://www.php.net)
[![Laravel Version Support](https://img.shields.io/badge/Laravel-12-red)](https://laravel.com)

Launch a complete SaaS or product website on Laravel 12 in minutes.

SaaS Kit installs a ready-to-run product site into a fresh Laravel 12 app: marketing pages, product catalog and pricing, Google sign-in, Stripe billing, a blog, an admin panel, and visitor analytics. You get the full source code, so you can change anything to fit your product.

## What You Get

- **Marketing site**: home, about, support, privacy, terms, and refund pages, with a header, footer, and error pages.
- **Products and pricing**: a product catalog with detail pages, per-product pricing and privacy pages.
- **Sign-in**: Google sign-in and Google One Tap on the web, plus token-based login for desktop apps, browser extensions, and other external clients.
- **Billing**: Stripe Checkout, subscriptions, one-time purchases, credits, and the Stripe Customer Portal.
- **Customer dashboard**: customers can see their subscriptions, orders, and account settings.
- **Blog**: multilingual posts with types, topics, search, related posts, and featured posts on the home page.
- **Admin panel**: manage users, products, plans, orders, subscriptions, blog posts, API keys, and analytics.
- **Analytics**: built-in page visit and event statistics, no third-party tracker required.
- **SEO**: sitemap, canonical URLs, alternate-language links, and structured data.
- **Languages**: English, Spanish, German, and Simplified Chinese out of the box.
- **Deployment**: production deploy script and Nginx and systemd examples.

See [Features](docs/FEATURES.md) for the technical details of each area.

## Requirements

- PHP 8.3 or newer and Composer 2
- Node.js 24 and npm
- A fresh Laravel 12 application
- MySQL, MariaDB, or PostgreSQL for production (SQLite works for local testing)

## Quick Start

```bash
composer create-project laravel/laravel:^12.0 my-site
cd my-site

composer require n2ns/laravel-saas-kit
php artisan saas-kit:install --force
composer dump-autoload

php artisan migrate:fresh --force
php artisan db:seed --class=ReferenceDataSeeder --force
php artisan passport:keys --force
php artisan passport:ensure-social-client --create

npm install
npm run build
```

Run these commands only in a brand-new Laravel app: `--force` replaces Laravel's default application files, and `migrate:fresh` drops all existing tables. The seeder adds a starter product and plans that you can edit in the admin panel.

Next, configure your app URL, Google, Stripe, and mail settings in `.env`. The [Installation guide](docs/INSTALLATION.md) walks through every step, and the installed site's `NEW_SITE_CHECKLIST.md` covers launching your own product.

## Documentation

- [Installation](docs/INSTALLATION.md): install the kit and configure a new site.
- [Features](docs/FEATURES.md): what is installed, with routes, commands, and settings.
- [API Google Authentication](docs/API_AUTH.md): sign in desktop apps, extensions, and other clients.
- [Package Structure](docs/PACKAGE_STRUCTURE.md): how the package and installer are organized.
- [Template Maintenance](docs/TEMPLATE_MAINTENANCE.md): maintain the kit and update existing sites.
- [Verification](docs/VERIFICATION.md): test the kit before a release.
- [Changelog](CHANGELOG.md)

The installed site also includes its own guides: `README.md`, `NEW_SITE_CHECKLIST.md`, `DEPLOYMENT.md`, `SCRIPTS.md`, `API_AUTH.md`, and `BLOG_GUIDELINES.md`.

## Optional: AI-Assisted Publishing

If you want AI assistants to draft and publish blog posts for your site, consider [`n2ns/laravel-post2site`](https://github.com/n2ns/laravel-post2site). Together with the [`n2n-post2site`](https://github.com/n2ns/n2n-post2site) MCP bridge, it lets an AI client prepare, preview, and publish content to a Laravel 12+ site after your explicit confirmation. It is a separate project and is not required by this kit; follow its documentation to connect it to your site.

## License

SaaS Kit is open-source software licensed under the [Apache-2.0 license](LICENSE).

---

Built by [N2NS Lab](https://n2ns.com), an open-source lab for practical AI developer tools.
