# Verification

Run package-level checks first, then verify the installer in a real Laravel 12 app.

## Package Checks

From the package root:

```bash
composer validate --strict
composer install
find src tests stubs/root -type f -name '*.php' -print -exec php -l {} \;
composer test
composer audit
bash scripts/check-template-sync.sh /path/to/source-template
```

Before committing or publishing, remove local package dependencies if this repository should remain source-only:

```bash
rm -rf vendor composer.lock .phpunit.result.cache
```

## Fresh Laravel Smoke Test

Use a temporary Laravel 12 project:

```bash
rm -rf /tmp/laravel-saas-kit-smoke
composer create-project laravel/laravel:^12.0 /tmp/laravel-saas-kit-smoke
cd /tmp/laravel-saas-kit-smoke
```

Install the local package via a path repository:

```bash
composer config repositories.laravel-saas-kit path /path/to/laravel-saas-kit
composer require n2ns/laravel-saas-kit:*@dev
```

Run the installer:

```bash
php artisan saas-kit:install --force
test -f .env.example
test -f .env.testing
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --force
php artisan passport:keys --force
php artisan passport:ensure-social-client --create
```

Check routes and app boot:

```bash
php artisan route:list --except-vendor
php artisan about
```

Run the installed test suite and frontend build:

```bash
composer test
php scripts/lint-php.php
npm ci
npm run build
```

## Post2Site Integration Smoke Test

After package checks pass, verify the generated site can install Post2Site and publish through the SaaS Kit preset:

```bash
bash scripts/smoke-post2site.sh
```

By default, the script uses a sibling `../laravel-post2site` checkout when present; otherwise it installs `n2ns/laravel-post2site:^0.2` from Composer. A successful run publishes:

- a normal blog post at `/blog/smoke-blog`
- a product guide at `/starter/guides/smoke-guide`

Expected result:

```text
Laravel tests pass
PHP syntax checks pass
vite build succeeds
Post2Site smoke publishes blog and guide posts
```

## Current Verified Baseline

The package has been verified with:

- Laravel framework 12.62.0 in the smoke app.
- Filament 5.6.7.
- Passport 13.7.5.
- Cashier 16.6.0.
- PHP 8.3.6.

The smoke test confirms:

- package discovery registers `n2ns/laravel-saas-kit`;
- `saas-kit:install --force` runs;
- root dotfiles such as `.env.example` and `.env.testing` are copied;
- kit migrations create the full schema;
- route registration works;
- Passport keys can be generated;
- the Passport social grant client can be created for external API login;
- installed template tests pass through `composer test`;
- installed PHP files pass syntax linting;
- frontend assets build through Vite.
