# New Product Site Checklist

Use this checklist after installing Laravel SaaS Kit into a fresh Laravel app.
Replace starter identity, product data, legal copy, payment IDs, and deployment
paths before launch.

## 1. Create the New Repository

If this site was created with `php artisan saas-kit:install --force`, initialize
the repository from the generated app.

```bash
cd new-product-site
rm -rf vendor node_modules public/build public/storage
git init
git add .
git commit -m "Initial product site"
```

Install dependencies:

```bash
composer install
npm install
```

## 2. Environment Identity

Update `.env` from `.env.example`:

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
```

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
```

## 3. Brand and Public Copy

Replace visible starter identity:

- `resources/lang/*/messages.php`
- `resources/lang/*/about.php`
- `resources/lang/*/privacy.php`
- `resources/lang/*/terms.php`
- `resources/lang/*/refund.php`
- `resources/lang/*/contact.php`
- `resources/views/account-access.blade.php`
- `resources/views/errors/404.blade.php`
- `resources/views/errors/503.blade.php`
- `resources/views/emails/*.blade.php`

Replace starter assets:

- `public/favicon.ico`
- `public/favicon-120.png`
- `public/favicon-512.png`
- `public/badges/site-badge.svg`
- `public/images/hero-bg.webp`
- `public/favicon-512.png`

Keep generic layout components unless the new product site has a real design
reason to change them.

## 4. Product Registry

The starter product code is `starter`. Before launch, decide the real product
code and update these places:

- `database/seeders/ReferenceDataSeeder.php`
- `config/auth_clients.php`
- `config/product_usage.php`
- `database/migrations/2026_06_18_000005_create_product_usage_tables.php`
- `database/migrations/2026_06_19_000001_add_admin_analytics_indexes.php`
- `app/Models/Product.php`
- `app/Models/ProductUsage/ProductUsageEvent.php`
- `app/Models/ProductUsage/ProductUsageDaily.php`
- tests that assert starter product routes or seed data

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

For app or browser-extension clients, update `config/auth_clients.php` and the
client redirect URI environment variables. The client `client_id` and
`product_code` sent by the external app must exactly match
`config/auth_clients.php`.

After migrations, create the Passport client used by API Google login:

```bash
php artisan passport:ensure-social-client --create
```

Copy the printed `PASSPORT_PASSWORD_CLIENT_ID` and
`PASSPORT_PASSWORD_CLIENT_SECRET` values into `.env`. See `API_AUTH.md` for the
full external-client checklist.

## 6. Stripe

Create Stripe products and prices for the new site. Set:

```dotenv
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PORTAL_CONFIGURATION=

STARTER_STRIPE_PRODUCT_ID=
STARTER_PLUS_MONTHLY_STRIPE_PRICE_ID=
STARTER_CREDITS_10_STRIPE_PRICE_ID=
```

Then update starter plan names and price IDs in Filament or in
`ReferenceDataSeeder.php` before first seed.

Configure the webhook endpoint:

```text
https://example.com/stripe/webhook
```

## 7. Local Smoke Test

```bash
cp .env.example .env
php artisan key:generate
php artisan passport:keys
touch database/database.sqlite
php artisan migrate:fresh --force
php artisan db:seed --class=ReferenceDataSeeder --force
php artisan storage:link
npm run build
composer check
```

Open:

- `/`
- `/products`
- `/starter`
- `/starter/pricing`
- `/blog`
- `/login`
- `/{ADMIN_PATH}`

## 8. Optional Automated Publishing

The site can publish blog posts and product guides manually through Filament.
For automated publishing, install `n2ns/laravel-post2site` and use the SaaS Kit
preset:

```env
POST2SITE_PRESET=laravel_saas_kit
POST2SITE_PUBLISHING_MODE=adapter
POST2SITE_SAAS_KIT_AUTHOR_ID=1
```

Keep `SAAS_KIT_LEGACY_MCP_ROUTES=false` when Post2Site owns `/api/v1/mcp`.

## 9. Production Deploy

Read `DEPLOYMENT.md` before first deploy.

First deployment usually needs:

```bash
php artisan key:generate
php artisan passport:keys
php artisan migrate --force
php artisan passport:ensure-social-client --create
bash scripts/deploy-production.sh --seed-reference
```

Routine deployment:

```bash
bash scripts/deploy-production.sh
```

Add one cron entry for Laravel scheduler:

```cron
* * * * * cd /srv/<app>/current && php artisan schedule:run >> /dev/null 2>&1
```

Install the systemd queue worker from:

```text
deploy/systemd/app-worker.service.example
```

Install it under a product-specific service name such as
`/etc/systemd/system/<app>-worker.service`.

## 10. Launch Verification

Before public launch, verify:

- `/up` returns healthy.
- Public pages render with production assets.
- Google login succeeds.
- Admin login works only for configured admin emails or admin users.
- Stripe test checkout creates order/subscription records.
- Stripe webhook records appear in admin.
- Queue worker is active in systemd.
- Scheduler cron is installed once.
- Access analytics write rows after page visits.
- Legal pages contain the new site's real owner/support contacts.
- Sitemap is generated if the site uses sitemap publishing.
