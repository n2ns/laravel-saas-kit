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
php artisan db:seed --class=ReferenceDataSeeder --force
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

Expected result:

```text
Laravel tests pass
PHP syntax checks pass
vite build succeeds
```

## Current Verified Baseline

The package has been verified with:

- Laravel framework 12.70.0 in the smoke app.
- Filament 5.10.2.
- Passport 13.10.0.
- Cashier 16.9.0.
- PHP 8.5.10 (Windows); CI runs PHP 8.3.

The smoke test confirms:

- package discovery registers `n2ns/laravel-saas-kit`;
- `saas-kit:install --force` runs;
- root dotfiles such as `.env.example` and `.env.testing` are copied;
- kit migrations create the full schema;
- the reference seeder creates the starter product, plans, and Stripe gateway;
- route registration works;
- Passport keys can be generated;
- the Passport social grant client can be created for external API login;
- installed template tests pass through `composer test`;
- installed PHP files pass syntax linting;
- frontend assets build through Vite.
