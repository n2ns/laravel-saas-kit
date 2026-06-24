# Installation

This package is for fresh Laravel 12 applications.

## Requirements

- PHP 8.3 or newer.
- Composer 2.
- Node.js 24 and npm.
- Laravel 12.
- MySQL or MariaDB for production. SQLite is fine for local smoke testing.

## Create a New Site

```bash
composer create-project laravel/laravel:^12.0 my-site
cd my-site
```

Install the kit:

```bash
composer require n2ns/laravel-saas-kit
php artisan saas-kit:install --force
composer dump-autoload
```

Run database setup:

```bash
php artisan migrate:fresh --force
php artisan passport:keys --force
```

Build frontend assets:

```bash
npm install
npm run build
```

## Why `--force`

The kit installs a complete product-site application, not a small optional feature.

`--force` lets the installer replace Laravel's default application skeleton with the kit's application files:

- `app`
- `bootstrap/app.php`
- `bootstrap/providers.php`
- `config`
- `database/factories`
- `database/migrations`
- `database/seeders`
- `public`
- `resources`
- `routes`
- `scripts`
- `tests`
- `.env.example`
- `.env.testing`
- `DEPLOYMENT.md`
- `NEW_SITE_CHECKLIST.md`
- `README.md`
- `SCRIPTS.md`
- `package.json`
- `package-lock.json`
- `phpstan.neon`
- `phpunit.xml`
- `vite.config.js`

Without this replacement, Laravel's default migrations and routes can conflict with the kit's identity, billing, content, and analytics tables.

## Why `migrate:fresh`

Laravel's project installer may run the default migrations while creating the fresh app. After the kit replaces the migrations, the old default tables must be dropped.

Use:

```bash
php artisan migrate:fresh --force
```

Do not use ordinary `php artisan migrate` for the first kit installation.

## Environment Setup

Copy `.env.example` to `.env` if the project does not already have `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

Configure at least:

```dotenv
APP_NAME=
APP_URL=
COMPANY_NAME=
COMPANY_EMAIL=
SUPPORT_EMAIL=
PRIVACY_EMAIL=

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://example.com/auth/google/callback

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PORTAL_CONFIGURATION=

STARTER_STRIPE_PRODUCT_ID=
STARTER_PLUS_MONTHLY_STRIPE_PRICE_ID=
STARTER_CREDITS_10_STRIPE_PRICE_ID=
```

For production, set database, queue, cache, and session drivers explicitly:

```dotenv
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

## First Admin User

The kit does not grant admin access merely because an email address looks like `admin@example.com`.

Set the intended admin email in `.env`:

```dotenv
ADMIN_EMAIL=owner@example.com
ADMIN_EMAILS=owner@example.com
```

Then log in with Google using that verified email, or create/update the user through a trusted console/database operation.

## Next Steps

After installation, use the generated site's `NEW_SITE_CHECKLIST.md` to replace starter product data, assets, legal copy, Stripe IDs, OAuth credentials, and deployment paths.
