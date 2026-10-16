# New Product Site Checklist

Use this checklist after installing Laravel SaaS Kit into a fresh Laravel app.
Replace starter identity, product data, legal copy, payment IDs, and deployment
paths before launch.

## 1. Create the New Repository

If this site was created with `php artisan saas-kit:install --force`, initialize
the repository from the generated app. The installed `.gitignore` already
excludes `vendor`, `node_modules`, `public/build`, `public/storage`, `.env`,
`database/database.sqlite`, and `public/sitemap.xml`.

```bash
cd new-product-site
git init
git add .
git commit -m "Initial product site"
```

Install frontend dependencies (the installer does not create `node_modules`):

```bash
npm install
```

On a fresh clone of the repository, install both:

```bash
composer install
npm install
```

## 2. Environment Identity

The installer replaces `.env.example` but does not touch an existing `.env`, so
the `.env` created by Laravel's installer still has Laravel's defaults. Start
from the kit's file:

```bash
cp .env.example .env
php artisan key:generate
```

Then update:

```dotenv
APP_NAME=
APP_URL=
COMPANY_NAME=
COMPANY_EMAIL=
SUPPORT_EMAIL=
PRIVACY_EMAIL=
COMPANY_ADDRESS=
APP_SAME_AS=

ADMIN_PATH=admin
ADMIN_EMAIL=
ADMIN_EMAILS=

MAIL_FROM_ADDRESS=
CORS_ALLOWED_ORIGINS=
```

- `COMPANY_NAME` falls back to `APP_NAME`; `SUPPORT_EMAIL` and `PRIVACY_EMAIL`
  fall back to `COMPANY_EMAIL`. A fallback only applies when the variable is
  absent; an empty `KEY=` line counts as set.
- `APP_SAME_AS` is a comma-separated list of profile URLs.
- Admin emails are read from `ADMIN_EMAILS` (comma-separated). `ADMIN_EMAIL` is
  used only when `ADMIN_EMAILS` is absent, so fill in `ADMIN_EMAILS` or delete
  that line.
- `CORS_ALLOWED_ORIGINS` applies to `api/*` and `auth/google/one-tap`; keep
  `https://accounts.google.com` and add the site's origin.

For production, also switch database and runtime drivers:

```dotenv
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

MAIL_MAILER=
```

`.env.example` defaults to `MAIL_MAILER=log`, which only writes mail to the log.

## 3. Brand and Public Copy

Replace visible starter identity ("SaaS Starter" and starter copy):

- `resources/lang/*/messages.php`
- `resources/lang/*/about.php`
- `resources/lang/*/privacy.php`
- `resources/lang/*/terms.php`
- `resources/lang/*/refund.php`
- `resources/lang/*/contact.php`
- `resources/lang/*/get_started.php`
- `resources/lang/*/products.php`
- `resources/lang/*/errors.php`
- `resources/views/account-access.blade.php`
- `resources/views/account.blade.php`
- `resources/views/products/index.blade.php`
- `resources/views/blog/show.blade.php`
- `resources/views/errors/404.blade.php`
- `resources/views/errors/503.blade.php`
- `resources/views/emails/subscription-activated.blade.php`
- `public/llms.txt`
- `resources/views/robots.blade.php` (served at `/robots.txt`; its `Sitemap:`
  URL follows `APP_URL`)

Locales are `de`, `en`, `es`, and `zh_CN`.

Replace starter assets:

- `public/favicon.ico`
- `public/favicon-512.png` (header, footer, login page, blog author block, and
  the structured data logo)
- `public/badges/site-badge.svg` (404 and 503 pages)
- `public/images/products/starter_banner.svg` (default product image when a
  catalog item has no image)

`public/favicon-120.png`, `public/images/hero-bg.webp`, `public/images/team/*`,
and `public/images/tech/*` also ship with the kit but are not referenced by the
templates. Replace or remove them.

Keep generic layout components unless the new product site has a real design
reason to change them.

## 4. Product Registry

The starter product code is `starter`. Before launch, decide the real product
code and update these places:

- `database/seeders/ReferenceDataSeeder.php` (catalog item, product, and the
  `starter_free`, `starter_plus_monthly`, `starter_credits_10` plans)
- `database/migrations/2026_06_21_000002_create_catalog_profile_detail_taxonomy_tables.php`
  (`$itemTerms` taxonomy assignments keyed by `starter`)
- `database/migrations/2026_06_18_000005_create_product_usage_tables.php`
  (`$clients`)
- `database/migrations/2026_06_19_000001_add_admin_analytics_indexes.php`
  (`product_usage_daily_starter`)
- `config/auth_clients.php`
- `config/product_usage.php`
- `config/services.php` (`stripe.products.starter`)
- `app/Models/Product.php` (`Product::STARTER`)
- `app/Models/ProductUsage/ProductUsageEvent.php` (`product_usage_events_starter`)
- `app/Models/ProductUsage/ProductUsageDaily.php` (`product_usage_daily_starter`)
- `app/Services/ProductService.php` (icon and color maps keyed by product code)
- `app/Filament/Widgets/ProductUsageStatsWidget.php` (color map)
- `database/seeders/data/posts/starter-*` (sample blog posts)
- `phpunit.xml` (`STARTER_*` Stripe test values)
- tests that assert starter product routes or seed data
  (`tests/Feature/TemplateSmokeTest.php`,
  `tests/Feature/PassportSocialClientCommandTest.php`)

After the first seed, product copy, plans, and prices can be edited in the admin
panel under Product Management > Catalog Items, Commerce > Product Listings, and
Commerce > Plans.

If the site has multiple products, add each product to the catalog and plan
data. Do not put product-specific business logic back into the kit.

## 5. Google Login

Create or update a Google OAuth client for the new domain.

Set:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
# Optional override. Defaults to APP_URL/auth/google/callback.
# GOOGLE_REDIRECT_URI=https://example.com/auth/google/callback
PRODUCT_KIT_CHROME_REDIRECT_URI=https://example.chromiumapp.org/callback
PRODUCT_KIT_MOBILE_REDIRECT_URI=starter://callback
```

Google Console must include:

- Authorized JavaScript origin: `https://example.com`
- Authorized redirect URI: `https://example.com/auth/google/callback`

For local testing, also add `http://localhost:8000` and
`http://localhost:8000/auth/google/callback`.

For app or browser-extension clients, update `config/auth_clients.php` and the
client redirect URI environment variables. The client `client_id` and
`product_code` sent by the external app must exactly match
`config/auth_clients.php`. The starter clients are `web_starter`,
`chrome_starter`, and `app_mobile`.

After migrations, create the Passport client used by API Google login:

```bash
php artisan passport:ensure-social-client --create
```

Copy the printed `PASSPORT_PASSWORD_CLIENT_ID` and
`PASSPORT_PASSWORD_CLIENT_SECRET` values into `.env`. See
[API_AUTH.md](API_AUTH.md) for the full external-client checklist.

## 6. Stripe

Create Stripe products and prices for the new site. Set:

```dotenv
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
# Optional. Empty uses Stripe's default Customer Portal configuration.
STRIPE_PORTAL_CONFIGURATION=

STARTER_STRIPE_PRODUCT_ID=
STARTER_PLUS_MONTHLY_STRIPE_PRICE_ID=
STARTER_CREDITS_10_STRIPE_PRICE_ID=
```

The `STARTER_*` values are only used by `ReferenceDataSeeder` when it seeds the
`starter` product and plans. Checkout uses the `stripe_price_id` stored on each
plan, so after the first seed, update plan names and price IDs in the admin panel
(Commerce > Plans, Commerce > Product Listings), or edit
`ReferenceDataSeeder.php` before the first seed.

Configure the webhook endpoint:

```text
https://example.com/stripe/webhook
```

Send these events: `checkout.session.completed`, `charge.refunded`,
`customer.subscription.created`, `customer.subscription.updated`,
`customer.subscription.deleted`, and `invoice.payment_succeeded`.

## 7. Local Smoke Test

Use the local `.env` from step 2 (`APP_ENV=local`, SQLite). Laravel's installer
already ran its default migrations on `database/database.sqlite`, so use
`migrate:fresh` for the first kit migration. `ReferenceDataSeeder` must run
after the migrations; it creates the starter product and plans.

```bash
php artisan passport:keys
touch database/database.sqlite
php artisan migrate:fresh --force
php artisan db:seed --class=ReferenceDataSeeder --force
php artisan passport:ensure-social-client --create
php artisan storage:link
npm run build
php artisan test
php scripts/lint-php.php
```

Copy the Passport client values printed by `passport:ensure-social-client
--create` into `.env`.

Optionally load the sample blog posts from `database/seeders/data/posts`
(without them `/blog` renders with no posts):

```bash
php artisan db:seed --class=BlogPostSeeder --force
```

To sign in to the admin panel locally without Google, create a password user
whose email is listed in `ADMIN_EMAILS`:

```bash
php artisan make:filament-user
```

Start the site:

```bash
php artisan serve
```

Open:

- `/`
- `/products`
- `/starter`
- `/starter/pricing`
- `/blog`
- `/login`
- `/{ADMIN_PATH}`

## 8. Production Deploy

Read [DEPLOYMENT.md](DEPLOYMENT.md) before first deploy.

First deployment usually needs, after creating the production `.env`:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate
php artisan passport:keys
php artisan migrate --force
php artisan passport:ensure-social-client --create
# Copy PASSPORT_PASSWORD_CLIENT_ID and PASSPORT_PASSWORD_CLIENT_SECRET into .env.
bash scripts/deploy-production.sh --seed-reference
```

The deployment script runs `passport:ensure-social-client` without `--create`
and stops if the client values are not in `.env`.

Routine deployment, after updating the code on the server:

```bash
bash scripts/deploy-production.sh
```

The starter site defines no scheduled tasks. If you add any, add one cron entry
for Laravel's scheduler:

```cron
* * * * * cd /srv/<app>/current && php artisan schedule:run >> /dev/null 2>&1
```

Install the systemd queue worker from:

```text
deploy/systemd/app-worker.service.example
```

Install it under a product-specific service name such as
`/etc/systemd/system/<app>-worker.service`.

Adapt the nginx example from `deploy/nginx/app.conf.example`.

## 9. Launch Verification

Before public launch, verify:

- `/up` returns healthy and `/api/health` reports `"status": "ok"`.
- Public pages render with production assets.
- Google login succeeds.
- Admin login at `/{ADMIN_PATH}` works only for configured admin emails or
  users with `is_admin` enabled.
- Stripe test checkout creates order/subscription records.
- Stripe webhook records appear in admin (Commerce > Stripe Webhooks).
- Queue worker is active in systemd.
- Scheduler cron is installed once, if the site added scheduled tasks.
- Access analytics write rows after page visits (Data Center > Access analytics).
- Legal pages contain the new site's real owner/support contacts.
- `php artisan sitemap:generate` has written `public/sitemap.xml`, and
  `/robots.txt` points to the production sitemap URL.
