# Deployment

This template follows the Laravel 12 deployment model: serve only the `public`
directory, build Vite assets, clear stale caches before migrations, run
production migrations with `--force`, cache the application, and reload
long-running services after each release.

Official references:

- Laravel 12 deployment: https://laravel.com/docs/12.x/deployment
- Laravel 12 Vite: https://laravel.com/docs/12.x/vite
- Laravel 12 migrations: https://laravel.com/docs/12.x/migrations
- Laravel 12 queues: https://laravel.com/docs/12.x/queues
- Laravel 12 scheduling: https://laravel.com/docs/12.x/scheduling
- Laravel 12 Passport: https://laravel.com/docs/12.x/passport

## Production Requirements

- PHP 8.3 or newer.
- Composer 2.
- Node.js 24 and npm for `npm ci` and `npm run build`.
- MySQL, MariaDB, or PostgreSQL for production. SQLite is only the local starter default.
  The examples below use MySQL; for PostgreSQL set `DB_CONNECTION=pgsql` and
  `DB_PORT=5432`.
- systemd for queue workers.
- A web server that points the document root to `public`.
- A cron entry for Laravel's scheduler only if you add scheduled tasks (see
  [Scheduler](#scheduler)).

Laravel must be able to write to:

- `storage`
- `bootstrap/cache`

## Environment

Use `.env.example` as the starting point. For production, set at least:

```dotenv
APP_NAME=
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

ADMIN_PATH=admin
CORS_ALLOWED_ORIGINS=https://example.com,https://accounts.google.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

MAIL_MAILER=
MAIL_FROM_ADDRESS=

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

# Optional. Leave empty to use storage/oauth-*.key files from passport:keys.
PASSPORT_PRIVATE_KEY=
PASSPORT_PUBLIC_KEY=
PASSPORT_PASSWORD_CLIENT_ID=
PASSPORT_PASSWORD_CLIENT_SECRET=

ADMIN_EMAIL=
ADMIN_EMAILS=
```

Notes:

- `.env.example` defaults `MAIL_MAILER=log`, which only writes mail to the log.
  Choose a real mailer for production (for example `smtp`, or `resend` with
  `RESEND_KEY`).
- Admin access is granted to users with `is_admin` enabled or whose email is
  listed in the admin email list. The list is read from `ADMIN_EMAILS`
  (comma-separated) and falls back to `ADMIN_EMAIL` only when `ADMIN_EMAILS`
  is not set at all. An empty `ADMIN_EMAILS=` line counts as set, so either fill
  in `ADMIN_EMAILS` or delete that line when you use `ADMIN_EMAIL`.
- `ADMIN_PATH` sets the Filament admin URL (`/{ADMIN_PATH}`) and is also
  excluded from access analytics.

Generate and keep `APP_KEY` once:

```bash
php artisan key:generate
```

Do not regenerate `APP_KEY` on an existing production site.

## First Server Bootstrap

Create `.env` from `.env.example` and fill in the production values above.
Then install PHP dependencies:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate
```

Create Passport keys on the first server if you are not using
`PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` environment variables. The
keys are written to `storage/oauth-private.key` and `storage/oauth-public.key`
and must persist across releases:

```bash
php artisan passport:keys
```

Run migrations:

```bash
php artisan migrate --force
```

Create the Passport client used by external-client API Google login:

```bash
php artisan passport:ensure-social-client --create
```

Copy the printed `PASSPORT_PASSWORD_CLIENT_ID` and
`PASSPORT_PASSWORD_CLIENT_SECRET` values into `.env`. This must be done before
the first scripted deployment, because `scripts/deploy-production.sh` runs
`php artisan passport:ensure-social-client` without `--create` and stops when
the client is not configured.

Then run the first deployment. For a new product site, include the starter
reference data:

```bash
bash scripts/deploy-production.sh --seed-reference
```

`ReferenceDataSeeder` must run after migrations. It creates or updates the
Stripe payment gateway, the `starter` catalog item and product, and the
`starter_free`, `starter_plus_monthly`, and `starter_credits_10` plans, and it
assigns catalog taxonomy terms that the migrations create.

The seeder reads the starter Stripe IDs from `config/services.php`
(`services.stripe.products.starter`), which maps `STARTER_STRIPE_PRODUCT_ID`,
`STARTER_PLUS_MONTHLY_STRIPE_PRICE_ID`, and `STARTER_CREDITS_10_STRIPE_PRICE_ID`.
It works with or without a cached configuration. If you seed manually instead of
using `--seed-reference`:

```bash
php artisan db:seed --class=ReferenceDataSeeder --force
```

Re-running the seeder overwrites those starter records with the seeder's values.
Do not run it after the site has real edited content unless you intentionally
want to re-apply starter defaults.

## Routine Deployment

The script deploys the code that is already in the project directory. It does
not pull code, so update the checkout or release directory first, then run:

```bash
bash scripts/deploy-production.sh
```

The script changes to the project root itself and requires `.env` to exist.
When `vendor` already exists and `--skip-maintenance` is not passed, it runs
`php artisan down --render="errors::503" --retry=60` before updating
dependencies. If that command fails, the deployment continues without
maintenance mode. When the script exits, including after a failed step, it runs
`php artisan up` if it put the site into maintenance mode.

The script runs:

1. `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`
2. `npm ci`
3. `npm run build`
4. `php artisan storage:link` when `public/storage` does not exist
5. `php artisan optimize:clear`
6. `php artisan migrate --force`
7. `php artisan passport:ensure-social-client`
8. optional `php artisan db:seed --class=ReferenceDataSeeder --force`
9. `php artisan optimize`
10. `php artisan reload`

Any failing step stops the script. Step 7 fails when
`PASSPORT_PASSWORD_CLIENT_ID` or `PASSPORT_PASSWORD_CLIENT_SECRET` is missing or
the client does not exist; when the client exists it only resets its grant types
to `social` and `refresh_token` if they differ.

`php artisan reload` runs `queue:restart` and `schedule:interrupt`. It is
required because queue workers do not automatically notice new code. The
systemd service restarts the worker after it gracefully exits.

See [SCRIPTS.md](SCRIPTS.md) for deployment script options.

## Web Server

The web server document root must be the `public` directory. Never serve the
project root.

An nginx starter config is available at:

```text
deploy/nginx/app.conf.example
```

It listens on port 80 only, uses `root /srv/<app>/current/public`, and passes
PHP to `unix:/var/run/php/php8.3-fpm.sock`. Customize `server_name`, the project
path, and the PHP-FPM socket, and add TLS for the actual server.

## systemd Queue Worker

Stripe webhook processing, the subscription activation email, and product usage
tracking jobs are queued. Use systemd to keep the worker running in production.

A systemd service example is available at:

```text
deploy/systemd/app-worker.service.example
```

It runs `/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600`
as `www-data` from `/srv/<app>/current` with `Restart=always`.

Copy it to a product-specific service name such as
`/etc/systemd/system/<app>-worker.service`, update paths and user names inside
the file, then run:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now <app>-worker
sudo systemctl status <app>-worker
```

After deployment, `scripts/deploy-production.sh` runs `php artisan reload`. The
worker exits after the current job and systemd starts a fresh process.

## Scheduler

`routes/console.php` defines no scheduled tasks, so the starter site does not
need a cron entry. If you add scheduled tasks, add one cron entry on the server:

```cron
* * * * * cd /srv/<app>/current && php artisan schedule:run >> /dev/null 2>&1
```

Laravel evaluates all configured scheduled tasks from that single entry.

## Sitemap

The sitemap is not generated automatically. Generate `public/sitemap.xml` after
deployment or content changes:

```bash
php artisan sitemap:generate
```

`public/sitemap.xml` is ignored by git. `/robots.txt` is rendered from
`resources/views/robots.blade.php`, and its `Sitemap:` line uses `APP_URL`, so
set `APP_URL` to the production URL.

## Stripe Setup

Configure Stripe with:

- A webhook endpoint pointing to `https://example.com/stripe/webhook`.
- The webhook signing secret in `STRIPE_WEBHOOK_SECRET`.
- Webhook events handled by the site: `checkout.session.completed`,
  `charge.refunded`, `customer.subscription.created`,
  `customer.subscription.updated`, `customer.subscription.deleted`, and
  `invoice.payment_succeeded`.
- Optionally, a Customer Portal configuration ID in
  `STRIPE_PORTAL_CONFIGURATION`. When it is empty, Stripe's default portal
  configuration is used.

Checkout uses the `stripe_price_id` stored on each `plans` record, and products
store `stripe_product_id`. The `STARTER_*` environment values are only used by
`ReferenceDataSeeder` when it seeds those records. After the initial seed,
change Stripe product and price IDs in the admin panel (Commerce > Product
Listings and Commerce > Plans).

## Google Login Setup

Configure Google OAuth with:

- Authorized JavaScript origin: `https://example.com`
- Authorized redirect URI: `https://example.com/auth/google/callback`

Set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in production. Google One Tap
uses the same client ID. Set `GOOGLE_REDIRECT_URI` only when the callback URL
differs from `APP_URL/auth/google/callback`.

External-client API login is documented in [API_AUTH.md](API_AUTH.md).

## Health Check

Laravel's health route is enabled at:

```text
/up
```

Use it for uptime checks or load balancer health checks.

The API also exposes `/api/health`, which returns JSON with database and cache
status (`status` is `ok` or `degraded`).
