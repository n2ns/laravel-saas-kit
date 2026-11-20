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
```

`composer test` runs `phpunit` with the package `phpunit.xml`. The suite in `tests/` boots the service provider through Orchestra Testbench and checks that:

- the `saas-kit:install` command is registered;
- key template files are packaged, including `.env.example` and `.env.testing`;
- `stubs/root/scripts/deploy-production.sh` runs `php artisan passport:ensure-social-client`;
- the package `composer.json` exposes the `test` script and has no `version` field;
- `stubs/root/routes/api.php` has no built-in MCP routes.

If you maintain a separate source app for the template, also check for drift (see [Template Maintenance](TEMPLATE_MAINTENANCE.md#source-of-truth)):

```bash
bash scripts/check-template-sync.sh /path/to/source-template
```

`vendor`, `composer.lock`, `.phpunit.cache`, and `.phpunit.result.cache` are listed in `.gitignore` and marked `export-ignore` in `.gitattributes`. You do not need to delete them before committing, and they are not included in package archives.

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

The installer does not merge Composer scripts, so `composer test` here runs the host app's own `test` script. In the Laravel 12 skeleton, that script clears the config cache and runs `php artisan test` against the installed template tests. `php scripts/lint-php.php` is the template's PHP syntax linter; it skips `vendor` and `node_modules`.

Expected result:

```text
Laravel tests pass
PHP syntax checks pass
vite build succeeds
```

## Continuous Integration

`.github/workflows/ci.yml` runs on pushes to `main` and on pull requests, with PHP 8.3:

- **Package checks:** `composer validate --strict`, `composer install`, `php -l` on every PHP file in `src`, `tests`, and `stubs/root`, then `./vendor/bin/phpunit`.
- **Laravel install smoke:** creates a `laravel/laravel:^12.0` app, requires the checked-out kit through a path repository, and runs `saas-kit:install --force`. It then runs the backend steps above (`.env` setup, `migrate:fresh`, `ReferenceDataSeeder`, `passport:keys`, `route:list`, `about`, `composer test`, `php scripts/lint-php.php`) and `npm ci` / `npm run build` with Node.js 24.

CI does not run `composer audit`, `scripts/check-template-sync.sh`, or `passport:ensure-social-client --create`. Run those manually.

## Current Verified Baseline

The package has been verified with:

- Laravel framework 12.73.0 in the smoke app.
- Filament 5.11.4.
- Passport 13.12.0.
- Cashier 16.11.0.
- PHP 8.5.4 locally; CI runs PHP 8.3.
- SQLite and PostgreSQL 18: the installed template tests pass on both, and a `seed-data:export` / `database:restore-local` round trip on PostgreSQL restores identical rows. MySQL and MariaDB were not run in this verification.

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
