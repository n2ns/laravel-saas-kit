# Installation

This package is for fresh Laravel 12 applications. It copies a complete product-site application into the new project; it is not meant to be added to an existing site.

## Requirements

- PHP 8.3 or newer.
- Composer 2.
- Laravel 12 (`laravel/framework` `^12.73.0`, required by the package).
- Node.js and npm for the Vite frontend build. CI builds with Node.js 24.
- MySQL, MariaDB, or PostgreSQL for production. SQLite is fine for local smoke testing; the kit's `.env.example` defaults to `database/database.sqlite`.

## Create a New Site

Pin the Laravel version so `composer create-project` does not create a Laravel 13 app when that is the current default:

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

The installer already runs `composer dump-autoload` after merging Composer metadata. Running it again is harmless, and it is required if the installer printed `composer dump-autoload did not complete`.

Set up the environment file. `composer create-project` creates `.env` from Laravel's default `.env.example`, which does not contain the kit's variables. For a brand-new site, replace it with the kit's file and generate a new key:

```bash
cp .env.example .env
php artisan key:generate
```

See [Environment Setup](#environment-setup) for the values to fill in. Configure the database connection before migrating if you are not using the default SQLite file.

Run database setup:

```bash
php artisan migrate:fresh --force
php artisan db:seed --class=ReferenceDataSeeder --force
php artisan passport:keys --force
php artisan passport:ensure-social-client --create
```

`ReferenceDataSeeder` creates the starter product, Free / Plus / Credits plans,
and Stripe gateway records. Run it once for a new site; re-running it re-applies
the starter defaults.

`passport:ensure-social-client --create` prints `PASSPORT_PASSWORD_CLIENT_ID` and
`PASSPORT_PASSWORD_CLIENT_SECRET`. Copy them into `.env` before using external
client API login. See [API Google Authentication](API_AUTH.md).

Build frontend assets:

```bash
npm install
npm run build
```

## What the Installer Does

`php artisan saas-kit:install` copies every file under the package's `stubs/root` directory, including dotfiles, into the application root. It never copies `composer.json`, `composer.lock`, `vendor`, `node_modules`, or `.git`. Instead of replacing the host `composer.json`, it merges the autoload and plugin settings the template needs. See [Package Structure](PACKAGE_STRUCTURE.md) for the exact steps.

After installation, the product site owns the copied files. They are ordinary application code that you edit directly; later releases of this package do not update them. See [Template Maintenance](TEMPLATE_MAINTENANCE.md#updating-existing-product-sites) for how shared fixes reach existing sites.

## Why `--force`

The kit installs a complete product-site application, not a small optional feature.

Without `--force`, the installer refuses to run if any template file already exists in the target. It prints up to 20 of the conflicting paths and exits with a failure. A fresh Laravel app always conflicts (for example `routes/web.php`, `config/app.php`, and `README.md`), so the first installation needs `--force`.

With `--force`, the installer first deletes these paths, which belong to the kit, from the Laravel skeleton:

- `app`
- `bootstrap/app.php`
- `bootstrap/providers.php`
- `config`
- `database/factories`
- `database/migrations`
- `database/seeders`
- `deploy`
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

It then copies the template files and overwrites any other files with the same path, such as `artisan`, `.gitignore`, `.gitattributes`, and `.editorconfig`. Host files that the template does not contain, such as `.env`, `composer.json`, `composer.lock`, `vendor`, and the rest of `storage`, are left in place.

Without this replacement, Laravel's default migrations, routes, controllers, and tests would sit next to the kit's own. Those defaults conflict with the kit's identity, billing, content, and analytics tables.

## Why `migrate:fresh`

`composer create-project` for Laravel 12 may already have run Laravel's default migrations, for example against the default SQLite database. Those migrations created tables such as `users`, `cache`, `jobs`, and `sessions`. The kit's migrations (`0001_01_01_000000_create_identity_tables.php`, `0001_01_01_000001_create_core_runtime_tables.php`, and the rest) create the same tables with the kit's schema under different file names, plus the Passport `oauth_*` tables. A plain `migrate` would fail on tables that already exist.

Use:

```bash
php artisan migrate:fresh --force
```

`migrate:fresh` drops all tables. Use it only for the first installation of a brand-new site, and do not use ordinary `php artisan migrate` for that first installation. Routine deployments of an existing site use `php artisan migrate --force` (see the generated site's `SCRIPTS.md`).

## Environment Setup

Configure at least these values, which are all present in the kit's `.env.example`:

```dotenv
APP_NAME=
APP_URL=
COMPANY_NAME=
COMPANY_EMAIL=
SUPPORT_EMAIL=
PRIVACY_EMAIL=

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
# Optional override. Defaults to APP_URL/auth/google/callback.
# GOOGLE_REDIRECT_URI=https://example.com/auth/google/callback

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

`ReferenceDataSeeder` reads the `STARTER_*` Stripe IDs when it creates the starter product and plans, so set them before seeding if you already have them.

`APP_URL` must be reachable from the server itself, because API login sends an internal HTTP request to `APP_URL/oauth/token` (see [API Google Authentication](API_AUTH.md#request-flow)).

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

A user can open the Filament admin panel (at `/admin` by default, set with `ADMIN_PATH`) when their `users.is_admin` flag is true or their email is listed in `ADMIN_EMAILS`. `ADMIN_EMAILS` is comma-separated and falls back to `ADMIN_EMAIL` when it is empty:

```dotenv
ADMIN_EMAIL=owner@example.com
ADMIN_EMAILS=owner@example.com
```

Then log in with Google using that address (Google sign-in requires a verified email), or set `is_admin` on the user through a trusted console or database operation.

## Next Steps

The installed site contains its own handoff docs: `README.md`, `NEW_SITE_CHECKLIST.md`, `DEPLOYMENT.md`, `SCRIPTS.md`, `API_AUTH.md`, and `BLOG_GUIDELINES.md`.

Use `NEW_SITE_CHECKLIST.md` to replace starter product data, assets, legal copy, Stripe IDs, OAuth credentials, and deployment paths.
