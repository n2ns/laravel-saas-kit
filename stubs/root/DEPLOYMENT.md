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
- MySQL or MariaDB for production. SQLite is only the local starter default.
- systemd for queue workers.
- One cron entry for Laravel's scheduler if scheduled commands are used.
- A web server that points the document root to `public`.

Laravel must be able to write to:

- `storage`
- `bootstrap/cache`

## Environment

Use `.env.example` as the starting point. For production, set at least:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

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

ADMIN_EMAIL=
ADMIN_EMAILS=
```

Generate and keep `APP_KEY` once:

```bash
php artisan key:generate
```

Do not regenerate `APP_KEY` on an existing production site.

## First Server Bootstrap

Install dependencies and build assets:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
```

Create Passport keys on the first server if you are not using
`PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` environment variables:

```bash
php artisan passport:keys
```

Run migrations:

```bash
php artisan migrate --force
```

Seed starter reference data only for a new product site:

```bash
php artisan db:seed --class=ReferenceDataSeeder --force
```

The reference seeder creates the starter product, starter plans, and Stripe
gateway records. Do not run it blindly after the site has real edited content
unless you intentionally want to re-apply starter defaults.

Cache the application:

```bash
php artisan optimize
```

## Routine Deployment

Use the deployment script from the project root:

```bash
bash scripts/deploy-production.sh
```

On routine deployments where `vendor` already exists, the script enters
maintenance mode with a pre-rendered 503 view before updating dependencies, then
brings the application back up after the release steps finish.

For the first deploy of a new site, before real content exists, you may seed the
starter reference data:

```bash
bash scripts/deploy-production.sh --seed-reference
```

The script runs:

1. `composer install --no-dev --prefer-dist --optimize-autoloader`
2. `npm ci`
3. `npm run build`
4. `php artisan migrate --force`
5. `php artisan storage:link` when `public/storage` does not exist
6. `php artisan optimize:clear`
7. optional `php artisan db:seed --class=ReferenceDataSeeder --force`
8. `php artisan optimize`
9. `php artisan reload`

`php artisan reload` is required because queue workers and other long-running
services do not automatically notice new code. The systemd service restarts the
worker after it gracefully exits.

See `SCRIPTS.md` for deployment script options.

## Web Server

The web server document root must be the `public` directory. Never serve the
project root.

An nginx starter config is available at:

```text
deploy/nginx/app.conf.example
```

Customize the domain, project path, PHP-FPM socket, and TLS settings for the
actual server.

## systemd Queue Worker

Stripe webhooks, mail, and product usage jobs depend on Laravel queues. Use
systemd to keep the worker running in production.

A systemd service example is available at:

```text
deploy/systemd/app-worker.service.example
```

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

If the site uses scheduled commands, add one cron entry on the server:

```cron
* * * * * cd /srv/<app>/current && php artisan schedule:run >> /dev/null 2>&1
```

Laravel evaluates all configured scheduled tasks from that single entry.

## Stripe Setup

Configure Stripe with:

- Checkout price IDs mapped in `.env`.
- A webhook endpoint pointing to `https://example.com/stripe/webhook`.
- The webhook signing secret in `STRIPE_WEBHOOK_SECRET`.
- Customer Portal configuration ID in `STRIPE_PORTAL_CONFIGURATION`.

After changing Stripe products or prices, update the matching `plans` records in
Filament or update the starter environment values before the initial seed.

## Google Login Setup

Configure Google OAuth with:

- Authorized JavaScript origin: `https://example.com`
- Authorized redirect URI: `https://example.com/auth/google/callback`

Set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` in
production.

## Health Check

Laravel's health route is enabled at:

```text
/up
```

Use it for uptime checks or load balancer health checks.
