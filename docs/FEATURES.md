# Feature Reference

This is the technical reference for what `php artisan saas-kit:install --force`
puts into a fresh Laravel 12 application. Everything below lives in
`stubs/root` and becomes editable application code in the generated site.

For installation steps see [INSTALLATION.md](INSTALLATION.md). The generated
site ships its own docs:

- [README.md](../stubs/root/README.md): overview, local setup, common commands.
- [NEW_SITE_CHECKLIST.md](../stubs/root/NEW_SITE_CHECKLIST.md): replacing
  starter identity and data before launch.
- [DEPLOYMENT.md](../stubs/root/DEPLOYMENT.md) and
  [SCRIPTS.md](../stubs/root/SCRIPTS.md): production deployment and scripts.
- [API_AUTH.md](../stubs/root/API_AUTH.md): API authentication details.
- [BLOG_GUIDELINES.md](../stubs/root/BLOG_GUIDELINES.md): blog content
  workflow.

## Contents

- [Installer Behavior](#installer-behavior)
- [Marketing Site and Pages](#marketing-site-and-pages)
- [Product Catalog and Pricing](#product-catalog-and-pricing)
- [Blog and Content](#blog-and-content)
- [Multilingual Routing](#multilingual-routing)
- [SEO and Sitemap](#seo-and-sitemap)
- [Authentication](#authentication)
- [Stripe Billing and Credits](#stripe-billing-and-credits)
- [Filament Admin](#filament-admin)
- [Access Analytics and Product Usage](#access-analytics-and-product-usage)
- [Deployment and Operations](#deployment-and-operations)
- [Configuration Reference](#configuration-reference)

## Installer Behavior

- `saas-kit:install` copies every file under `stubs/root` into the host app,
  except `composer.json`, `composer.lock`, `vendor`, `node_modules`, and
  `.git`. Without `--force` it stops when files would be overwritten. With
  `--force` it first deletes the host app's `app`, `config`,
  `database/{factories,migrations,seeders}`, `deploy`, `public`, `resources`,
  `routes`, `scripts`, `tests`, and the top-level files it replaces (such as
  `bootstrap/app.php`, `package.json`, `phpunit.xml`, and `vite.config.js`).
- It merges only `autoload.files`, `autoload.psr-4`, and
  `config.allow-plugins` from the template `composer.json` into the host
  `composer.json`, then runs `composer dump-autoload`. Template Composer
  scripts and `require-dev` packages are not merged; runtime dependencies come
  from this package's `require`.
- `php artisan migrate:fresh --force` is expected on a new app because the
  kit's migrations replace Laravel's default identity tables.

## Marketing Site and Pages

Routes in `routes/web.php`, served by `HomeController`:

| Route | Name | View |
|---|---|---|
| `/` | `home` | `home` |
| `/about` | `about` | `about` |
| `/support` | `support` | `contact` |
| `/privacy`, `/terms`, `/refund` | `privacy`, `terms`, `refund` | same names |
| `/account-access` | `account-access` | `account-access` |
| `/get-started` | `get-started` | `get_started` |
| `/account` | `account` | `account` |

- The homepage lists catalog items with `show_on_homepage`. When exactly one
  item is shown it switches to a single-product landing layout; the header
  navigation follows the same rule (`AppServiceProvider` view composer for
  `partials.header`).
- The homepage also shows the three latest blog posts with pinned ordering.
- Authenticated users get `/dashboard` (overview) plus
  `/dashboard/subscriptions`, `/dashboard/orders`, and `/dashboard/settings`
  tabs (`DashboardController`).
- `/login` shows the Google login page for guests; `POST /logout` ends the web
  session.
- Error pages: `resources/views/errors/404.blade.php` and `503.blade.php`.
  Unknown routes fall through to `Route::fallback` (404).
- Company details come from `COMPANY_NAME`, `COMPANY_EMAIL`, `SUPPORT_EMAIL`,
  `PRIVACY_EMAIL`, `COMPANY_ADDRESS`, and `APP_SAME_AS` (`config/app.php`).
- Frontend: Vite, Tailwind CSS 4, Alpine.js, Lucide icons, and Font Awesome
  (`package.json`). A cookie consent banner is included
  (`partials/cookie-consent`).

## Product Catalog and Pricing

The public catalog is built from **catalog items**; commerce data lives in
**products** and **plans**.

| Model | Table | Holds |
|---|---|---|
| `CatalogItem` | `catalog_items` | `code` (URL slug), `status` (`draft`/`published`/`archived`), `is_visible`, `show_on_homepage`, sort orders |
| `CatalogItemTranslation` | `catalog_item_translations` | Per-locale name, descriptions, SEO title/description, card tag, CTA label, tags, key points, privacy sections |
| `CatalogItemProfile` | `catalog_item_profiles` | Product type, segment, theme, images, version, release/development status, links, facts, SEO payload |
| `CatalogItemDetail` / `CatalogItemDetailTranslation` | `catalog_item_details`, `catalog_item_detail_translations` | Detail template key, section structure, per-locale section content |
| `CatalogItemPrivacyPolicy` | `catalog_item_privacy_policies` | Per-product privacy policy content |
| `CatalogTaxonomy` / `CatalogTaxonomyTerm` | `catalog_taxonomies`, `catalog_taxonomy_terms`, `catalog_item_taxonomy_terms` | `primary_group`, `platform`, and `use_case` terms (seeded by migration) |
| `Product` | `products` | `code`, `is_active`, `pause_reason`, `stripe_product_id`, `metadata`; belongs to a catalog item |
| `Plan` | `plans` | `code`, `tier` (`free`/`pro`/`enterprise`/`addon`), `billing_cycle` (`free`/`monthly`/`yearly`/`lifetime`/`one_time`), price, `features`, `display_payload`, `stripe_price_id` |

Public routes (registered after fixed sections so reserved paths win):

| Route | Name | Controller |
|---|---|---|
| `/products` | `products.index` | `ProductController@index` (client-side `?category=` filter) |
| `/{slug}` | `catalog.show` | `ProductController@show` |
| `/{slug}/privacy` | `catalog.privacy` | `ProductController@privacy` (301 to the detail page when no policy exists) |
| `/{slug}/pricing` | `catalog.pricing` | `PlanController@pricing` |

- Detail pages render by `catalog_item_details.template_key`:
  `product-detail-v1` uses `catalog.templates.product-detail`,
  `developer-tool-detail-v1` uses `catalog.templates.developer-tool`.
  Section components live in `resources/views/components/products`.
- Only published, visible items render. Items in the `concept_project` primary
  group are excluded from public lists and the sitemap.
- Pricing uses `resources/views/products/pricing/{code}.blade.php` when it
  exists, otherwise `products/pricing/show`. A product with a `pause_reason`
  (`maintenance`, `payment_upgrade`, `coming_soon`, `region_restricted`)
  shows `products.pricing-unavailable` and blocks checkout.
- `GET /api/v1/products/{product}/config` returns active plans and the
  pricing URL for external clients.
- `php artisan catalog:export` exports catalog JSON (`catalog.export.v1`) with
  `--primary-group=`, `--locale=`, `--q=`, `--path=`, `--pretty`, and
  `--record` (stores a `catalog_releases` row).
- `php artisan app:sync-product-stats` reads `products.metadata.sources`
  (`github`, `npm`, `vscode`) and stores star and download counts in
  `metadata.stats`; `GITHUB_TOKEN` is optional.

Starter data (`ReferenceDataSeeder`): the `starter` catalog item with English
content and taxonomy terms, the `starter` product, plans `starter_free`,
`starter_plus_monthly` (pro, monthly, 29 USD), and `starter_credits_10`
(addon, one-time, 2 USD, `features.credits = 10`), and the `stripe` payment
gateway.

## Blog and Content

Details: [BLOG_GUIDELINES.md](../stubs/root/BLOG_GUIDELINES.md).

- Tables `blog_posts` (default-locale content and metadata) and
  `blog_post_translations` (other locales). Bodies are Markdown, rendered with
  `spatie/laravel-markdown`.
- Public routes: `/blog` (`blog.index`; `q`, `type`, `topic`, `sort=oldest`,
  10 per page) and `/blog/{slug}` (`blog.show`; related posts, reading time,
  `BlogPosting` JSON-LD).
- Admin preview: `/{ADMIN_PATH}/blog-posts/{blogPost}/preview`
  (`admin.blog-posts.preview`, admins only, `?locale=`).
- Post types and topics come from
  `database/seeders/data/posts/_meta_schema.json` through
  `App\Support\BlogContentVocabulary`.
- File-based seed content in `database/seeders/data/posts/{slug}/meta.json`
  and `{locale}.md`, imported by `BlogPostSeeder` (two starter posts).
- Homepage featured posts use `is_pinned`, `pin_order`, and `pinned_until`;
  `/blog` ignores pinning.
- Blog management API under `/api/v1/blog` (`index`, `store`, `show`,
  `update`, `destroy` by slug), protected by the `X-Api-Key` header.
- `php artisan translations:check [--locale=]` reports translation coverage
  for products and blog posts.

## Multilingual Routing

- `config/app.php` `supported_locales` maps URL prefixes to locale IDs:
  `en => en`, `es => es`, `de => de`, `cn => zh_CN`.
- English (the fallback locale) has no prefix. Other locales use
  `/{locale}/...` routes named `localized.*`; `/en` and `/en/*` redirect with
  301 to the unprefixed URL.
- `SetLocale` middleware sets the app locale and URL defaults from the first
  path segment (skipped for the admin path and Livewire).
- `localized_route($name, $parameters)` (`app/Helpers/helpers.php`) generates
  the right prefixed or unprefixed URL.
- `App\Support\LocaleProfile` centralizes supported locales, prefixes, and
  `hreflang` codes (`zh_CN` becomes `zh-Hans`).
- Translation files: `resources/lang/{en,es,de,zh_CN}/` (`about`, `checkout`,
  `contact`, `errors`, `get_started`, `messages`, `privacy`, `products`,
  `refund`, `terms`) and `resources/lang/{de,es,zh_CN}.json`.
- Catalog and blog content are translated in the database; pages fall back to
  the default locale when a locale has no content.
- The Filament panel runs with the `zh_CN` app locale (`SetFilamentLocale`)
  and uses the Spatie Translatable plugin with all supported locales.

## SEO and Sitemap

- `layouts/app.blade.php` emits the meta robots tag (overridable with a
  `robots` section), canonical URL, `hreflang` alternates with `x-default`,
  Open Graph, and Twitter card tags.
- Catalog and blog pages pass `seoCanonicalUrl` and `seoAlternates` that only
  include locales with real content.
- `App\Support\StructuredData` builds JSON-LD: `Organization` and `WebSite`
  on every page, `BlogPosting` on posts, `WebPage` on catalog detail pages,
  and a `breadcrumbList()` helper.
- `php artisan sitemap:generate` writes `public/sitemap.xml` with static pages
  in every locale, published catalog items and their privacy pages, product
  pricing pages, and published blog posts, each with `hreflang` alternates.
  Catalog items can opt out or set priority/frequency through
  `seo_payload.indexable` and `seo_payload.sitemap.*`.
- `public/robots.txt` (allows AI crawlers; its `Sitemap:` line points to
  `http://localhost:8000/sitemap.xml` and must be edited) and
  `public/llms.txt`.
- No task is scheduled in `routes/console.php`; run `sitemap:generate` after
  content changes or add it to the scheduler.

## Authentication

Details: [API_AUTH.md](../stubs/root/API_AUTH.md).

### Google OAuth and One Tap (web)

- `GET /auth/google` and `GET /auth/google/callback` use Laravel Socialite
  with the `openid`, `profile`, and `email` scopes.
- Google One Tap is rendered for guests in the layout and posts to
  `POST /auth/google/one-tap` (CSRF-exempt; verifies the `g_csrf_token`
  cookie against the request body) using
  `nickbeen/socialiteproviders-google-one-tap`.
- `SocialAuthService` requires a verified Google email, links accounts through
  `oauth_accounts`, and syncs name, avatar, and email.
- Env: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, optional
  `GOOGLE_REDIRECT_URI` (defaults to `APP_URL/auth/google/callback`).

### Passport API

- `POST /api/v1/auth/google` exchanges a Google ID token through the custom
  Passport `social` grant (`App\Auth\Grants\SocialGrant`);
  `POST /api/v1/auth/refresh` refreshes tokens.
- Clients are configured in `config/auth_clients.php` (`web_starter`,
  `chrome_starter`, `app_mobile`), each bound to a `product_code`.
- Access tokens last 6 hours, refresh tokens 3 months; the only scope is
  `api`.
- JWTs carry `plan`, `status`, `auth_epoch`, `name`, `email`, and `avatar`
  claims (`App\Auth\Entities\AccessToken`). The public key is published at
  `GET /.well-known/jwks.json` (route `jwks`).
- `php artisan passport:ensure-social-client [--create]` creates or repairs
  the Passport client (`grant_types = ["social","refresh_token"]`). Env:
  `PASSPORT_PASSWORD_CLIENT_ID`, `PASSPORT_PASSWORD_CLIENT_SECRET`, optional
  `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY`.
- Protected routes use `auth:api`, `CheckToken::using('api')`, `auth.epoch`,
  and `auth.session`: `/api/v1/auth/me`, `logout`, `sessions`,
  `sessions/revoke`, `DELETE sessions/{session}`, `ott`,
  `/api/v1/license/{product}`, `/api/v1/subscriptions`, `/api/v1/orders`,
  and the product usage routes.
- Browser clients calling `api/*` need their origin in
  `CORS_ALLOWED_ORIGINS`.

### Device sessions and `auth_epoch`

- `user_sessions` stores one session per user, client, product, and hashed
  device ID; `user_session_tokens` maps access and refresh token IDs to it.
- Sessions can be listed and revoked by the user through the API, or by admins
  in Filament (**User Sessions** and the sessions tab on a user).
- `users.auth_epoch` is embedded in every JWT; `EnsureAuthEpoch` rejects
  tokens with an older epoch. Billing events increment it, and admins can edit
  it.

### One-time token (OTT) login

`POST /api/v1/auth/ott` returns a 60-second token and URL;
`GET /ott/login?token=...` (route `ott.login`) logs the user into a web
session once and redirects to a sanitized internal path.

### API keys

`api_keys` rows (created in Filament under **System Configuration > API Key**)
authenticate the blog API through the `X-Api-Key` header (`api_key` middleware,
`AuthenticateApiKey`). Keys are stored in plaintext, support expiry and
revocation, and record `last_used_at`.

### Rate limiting

Named limiters in `AppServiceProvider`: `api-auth` (20/min), `api-refresh`
(60/min), `web-auth` (10/min), `web-auth-callback` (30/min), `ott-login`
(20/min), `product-usage-track` (60/min), `product-usage-read` (30/min), all
per IP.

## Stripe Billing and Credits

Built on Laravel Cashier and `spatie/laravel-stripe-webhooks`.

| Route | Purpose |
|---|---|
| `GET /checkout/{product}/{plan?}` (`checkout.create`) | Start Stripe Checkout. `{plan}` matches a plan `tier` or `code` (default `pro`); guests are sent to login first. `?lang=` sets the Checkout locale. |
| `GET /checkout/success` (`checkout.success`) | Fulfills the session immediately if no order exists yet. |
| `GET /checkout/cancel` (`checkout.cancel`) | Cancel page. |
| `GET /portal/{product?}` (`checkout.portal`) | Stripe Customer Portal (authenticated). |
| `POST /stripe/webhook` | Webhook endpoint (CSRF-exempt), stored in `webhook_calls` and processed on the queue by `HandleStripeWebhookJob`. |

- Monthly and yearly plans use subscription Checkout; lifetime plans and
  credit packs use one-time payment Checkout. Promotion codes are allowed.
- Users with an active subscription for a product cannot buy another
  subscription for it, but can buy credit packs.
- Handled webhook events: `checkout.session.completed`,
  `customer.subscription.created`, `customer.subscription.updated`,
  `customer.subscription.deleted`, `invoice.payment_succeeded` (renewals
  only), and `charge.refunded` (full or partial refunds; a full refund cancels
  the subscription).
- Fulfillment is idempotent: orders are unique per gateway and provider order
  ID. A new order sends `SubscriptionActivatedMail` (queued) and increments
  `auth_epoch`.
- **Credits**: a plan whose `features.credits` is a positive number is a
  credit pack. Paying for it creates one `credit_grants` row (`quantity`,
  `used`, `expires_at`, unique per source order and product). Consuming
  credits is left to product code (`CreditGrant::remaining()`,
  `User::creditGrants()`).
- Env: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`,
  `STRIPE_PORTAL_CONFIGURATION`, and the starter price variables
  `STARTER_STRIPE_PRODUCT_ID`, `STARTER_PLUS_MONTHLY_STRIPE_PRICE_ID`,
  `STARTER_CREDITS_10_STRIPE_PRICE_ID`. Optional: `STRIPE_WEBHOOK_CONNECTION`,
  `STRIPE_WEBHOOK_QUEUE`, `STRIPE_SIGNATURE_VERIFY`.

## Filament Admin

Filament 5 panel at `/{ADMIN_PATH}` (default `admin`). Access requires
`users.is_admin` or an email in `ADMIN_EMAILS` / `ADMIN_EMAIL`
(`User::canAccessPanel()`).

| Group | Entry | Notes |
|---|---|---|
| Data Center | Operations dashboard | Panel home: content, revenue, subscription, and product usage counters. |
| Data Center | Access analytics | Views, unique visitors, sources, UTM campaigns, countries, and click events. |
| User Management | Users | Edit profile, password, `auth_epoch`, `banned_at`, `is_admin`; OAuth accounts and sessions tabs. |
| User Management | User Sessions | Filter and revoke device sessions (single and bulk). |
| Product Management | Catalog Items | Base and display details, detail template, homepage settings; translations, detail translations, privacy policies, and taxonomy tabs. |
| Product Management | Homepage display | Toggle `show_on_homepage` / `is_visible` and ordering. |
| Commerce | Product Listings | Products with translations and plans tabs. |
| Commerce | Plans, Subscriptions, Orders | Billing records. |
| Commerce | Stripe Webhooks | Stored webhook calls (list and view). |
| Content | Blog | Markdown editor, publish and preview actions, view counts. |
| Content | Post translations | Per-locale blog content. |
| System Configuration | Payment gateways, API Key | Gateway config and API keys. |

The panel also enables database notifications and includes
`ProductUsageStatsWidget`.

## Access Analytics and Product Usage

### Site analytics

- `TrackSiteVisit` (web middleware) counts successful `GET` HTML requests to
  named routes into `site_visit_daily_stats`, with unique visitors through
  `site_visit_uniques` (daily hashed visitor key).
- Recorded dimensions: date, locale, path, route and page type, catalog item
  or blog post, source type, referrer host, UTM source/medium/campaign, and
  country from the `CF-IPCountry` header.
- Elements with `data-site-event` attributes send click events to
  `POST /site-events` (route `site-events.store`) via `resources/js/app.js`,
  stored in `site_event_daily_stats` and `site_event_uniques`.
- `config/site_analytics.php`: `SITE_ANALYTICS_ENABLED`, ignored path
  prefixes (admin, `api`, `dashboard`, `stripe`, `auth/google`, `ott`, assets),
  and bot user-agent keywords.

### Product usage API

- `config/product_usage.php` defines clients (starter: `starter`) with their
  own `product_usage_events_{client}` and `product_usage_daily_{client}`
  tables, created by migration from the config at migrate time.
- Routes (Bearer token): `POST /api/v1/product-usage/{client}/track` (queued
  `TrackProductUsage` job, returns `202`), `GET .../daily`, `GET .../summary`
  (`start`, `end`), and `GET .../events`.

## Deployment and Operations

Details: [DEPLOYMENT.md](../stubs/root/DEPLOYMENT.md) and
[SCRIPTS.md](../stubs/root/SCRIPTS.md).

- `scripts/deploy-production.sh`: maintenance mode, `composer install
  --no-dev`, `npm ci` and `npm run build`, `storage:link`, `optimize:clear`,
  `migrate --force`, `passport:ensure-social-client`, optional
  `db:seed --class=ReferenceDataSeeder`, `optimize`, and `reload`. Options:
  `--seed-reference`, `--skip-npm`, `--skip-migrate`, `--skip-reload`,
  `--skip-maintenance`.
- `scripts/run-local-tests.sh`: `php artisan test --parallel` with
  `TEST_PROCESSES` (default 4) and `TEST_RECREATE_DATABASES` (default 1).
- `scripts/lint-php.php`: PHP syntax check skipping `vendor` and
  `node_modules`.
- `deploy/nginx/app.conf.example` and
  `deploy/systemd/app-worker.service.example`.
- Health: `GET /up` (Laravel) and `GET /api/health` (database and cache
  status, latency, version from `APP_VERSION`).
- Seed data snapshots: `php artisan seed-data:export [--path=]
  [--include-migrations]` writes `manifest.json` and `tables/*.json` under
  `database/seeders/data` (or `DATABASE_SEED_DATA_PATH`).
  `php artisan database:restore-local` (local only) and
  `php artisan database:restore-seed-data` (local/testing, or production with
  `--allow-production`) rebuild the database through `SeedDataSeeder`, which
  requires MySQL or MariaDB.
- `spatie/laravel-backup` is configured in `config/backup.php`; a `google`
  filesystem disk (`GOOGLE_DRIVE_*`) is available for off-site storage.
- Tests: PHPUnit feature tests in `tests/Feature` run on in-memory SQLite
  (`phpunit.xml`, `.env.testing`).

## Configuration Reference

Environment variables in `.env.example`:

| Area | Variables |
|---|---|
| App and company | `APP_NAME`, `APP_URL`, `COMPANY_NAME`, `COMPANY_EMAIL`, `SUPPORT_EMAIL`, `PRIVACY_EMAIL`, `COMPANY_ADDRESS`, `APP_SAME_AS` |
| Admin | `ADMIN_PATH`, `ADMIN_EMAIL`, `ADMIN_EMAILS` |
| Locale | `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` |
| CORS | `CORS_ALLOWED_ORIGINS` |
| Google | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `PRODUCT_KIT_CHROME_REDIRECT_URI`, `PRODUCT_KIT_MOBILE_REDIRECT_URI` |
| Stripe | `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_PORTAL_CONFIGURATION`, `STARTER_STRIPE_PRODUCT_ID`, `STARTER_PLUS_MONTHLY_STRIPE_PRICE_ID`, `STARTER_CREDITS_10_STRIPE_PRICE_ID` |
| Passport | `PASSPORT_PRIVATE_KEY`, `PASSPORT_PUBLIC_KEY`, `PASSPORT_PASSWORD_CLIENT_ID`, `PASSPORT_PASSWORD_CLIENT_SECRET` |
| Runtime | `DB_*`, `SESSION_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `FILESYSTEM_DISK`, `MAIL_*` |

Other variables read by config: `APP_VERSION`, `SITE_ANALYTICS_ENABLED`,
`RESEND_KEY`, `GITHUB_TOKEN`, `PUBLIC_STORAGE_URL`, `GOOGLE_DRIVE_CLIENT_ID`,
`GOOGLE_DRIVE_CLIENT_SECRET`, `GOOGLE_DRIVE_REFRESH_TOKEN`,
`GOOGLE_DRIVE_FOLDER`, `DATABASE_SEED_DATA_PATH`, `STRIPE_WEBHOOK_CONNECTION`,
`STRIPE_WEBHOOK_QUEUE`, `STRIPE_SIGNATURE_VERIFY`, and `BACKUP_ARCHIVE_PASSWORD`.

Key config files: `config/app.php` (company, admin, locales),
`config/auth_clients.php`, `config/services.php` (Google, Stripe price map),
`config/passport.php`, `config/cors.php`, `config/product_usage.php`,
`config/site_analytics.php`, `config/stripe-webhooks.php`, and
`config/markdown.php`.
