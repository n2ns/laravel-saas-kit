# Scripts

## `scripts/deploy-production.sh`

Runs the routine Laravel production deployment steps. The script changes to the
project root (the parent of `scripts/`) before running, so it can be started from
any directory. It deploys the code that is already checked out; it does not pull
code.

When `vendor` already exists and `--skip-maintenance` is not passed, the script
runs `php artisan down --render="errors::503" --retry=60` before updating
dependencies. If `down` fails, the deployment continues without maintenance
mode. When the script exits, including after a failed step, it runs
`php artisan up` if it put the site into maintenance mode.

Steps:

1. `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`
2. `npm ci` (skipped with `--skip-npm`)
3. `npm run build` (skipped with `--skip-npm`)
4. `php artisan storage:link` when `public/storage` does not exist
5. `php artisan optimize:clear`
6. `php artisan migrate --force` (skipped with `--skip-migrate`)
7. `php artisan passport:ensure-social-client`
8. `php artisan db:seed --class=ReferenceDataSeeder --force` (only with `--seed-reference`)
9. `php artisan optimize`
10. `php artisan reload` (skipped with `--skip-reload`)

The script uses `set -euo pipefail`, so any failing step stops the deployment.
Step 7 runs on every deployment without `--create`: it fails when
`PASSPORT_PASSWORD_CLIENT_ID` or `PASSPORT_PASSWORD_CLIENT_SECRET` is not
configured or the client does not exist in `oauth_clients`, and otherwise makes
sure the client's grant types are `social` and `refresh_token`.

Usage:

```bash
bash scripts/deploy-production.sh [options]
```

Options:

```text
--seed-reference       Seed starter reference data after migrations.
                       Use only on a new product site before real content exists.
--skip-npm             Skip npm ci and npm run build.
--skip-migrate         Skip php artisan migrate --force.
--skip-reload          Skip php artisan reload.
--skip-maintenance     Do not run php artisan down/up around the deployment.
-h, --help             Show this help.
```

An unknown option prints the usage and exits with status 2. A missing `.env`
exits with status 1.

Examples:

```bash
# First deployment for a new site before real content exists.
bash scripts/deploy-production.sh --seed-reference

# Backend-only hotfix where assets did not change.
bash scripts/deploy-production.sh --skip-npm

# Emergency redeploy after migrations have already been applied manually.
bash scripts/deploy-production.sh --skip-migrate
```

The script requires `.env` to exist. It does not create `APP_KEY`, Passport
keys, the initial Passport social grant client, Google OAuth clients, Stripe
products, Stripe webhooks, cron entries, nginx configs, or systemd services.
Those are one-time server/provider setup tasks documented in
[DEPLOYMENT.md](DEPLOYMENT.md) and [API_AUTH.md](API_AUTH.md).

## `scripts/run-local-tests.sh`

Runs the Laravel test suite through Laravel's parallel test runner:

```bash
bash scripts/run-local-tests.sh
```

It runs `php artisan test --parallel --processes=<TEST_PROCESSES>`, adds
`--recreate-databases` unless `TEST_RECREATE_DATABASES` is `0`, and passes any
extra arguments through to `php artisan test`.

Environment knobs and their defaults:

```bash
TEST_PROCESSES=4
TEST_RECREATE_DATABASES=1
```

Parallel testing requires `brianium/paratest` 7.x, which a default Laravel 12
application does not include:

```bash
composer require --dev brianium/paratest
```

Examples:

```bash
TEST_PROCESSES=2 bash scripts/run-local-tests.sh
TEST_RECREATE_DATABASES=0 bash scripts/run-local-tests.sh
bash scripts/run-local-tests.sh --filter=TemplateSmokeTest
```

To run the suite serially without paratest:

```bash
php artisan test
```

## `scripts/lint-php.php`

Runs `php -l` on every `.php` file under the project root, skipping `vendor`,
`node_modules`, `storage/framework/cache`, `storage/framework/sessions`, and
`storage/framework/views`. It stops at the first file that fails and exits with
that status.

```bash
php scripts/lint-php.php
```

## Composer Scripts

`saas-kit:install` keeps the site's own `composer.json` and only merges autoload
entries and allowed plugins into it. The site therefore has the composer scripts
from the Laravel application it was installed into. In a default Laravel 12
application, `composer test` runs `php artisan config:clear --ansi` and then
`php artisan test` serially; it does not call `scripts/run-local-tests.sh`.
There is no `composer check` script unless you add one.

The full check sequence used by the template is:

```bash
php scripts/lint-php.php
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --memory-limit=512M
bash scripts/run-local-tests.sh
composer audit --no-dev
```

`phpstan.neon` includes `vendor/larastan/larastan/extension.neon`, so
`phpstan analyse` requires `composer require --dev larastan/larastan`. Add these
commands to `composer.json` scripts if you want `composer` shortcuts for them.

## Artisan Commands

The site adds these console commands in `app/Console/Commands`:

| Command | Purpose |
|---|---|
| `passport:ensure-social-client [--create] [--name=] [--provider=users]` | Create (with `--create`) or verify the Passport client used for API Google login. |
| `sitemap:generate` | Write `public/sitemap.xml` with static pages, catalog items, products, and published blog posts. |
| `catalog:export [--primary-group=] [--locale=] [--q=] [--path=] [--record] [--pretty]` | Export structured catalog data as JSON. |
| `translations:check [--locale=]` | Check translation coverage for products and blog posts. |
| `app:sync-product-stats` | Sync product statistics from external APIs (GitHub, NPM, VSCode). |
| `seed-data:export [--path=] [--include-migrations]` | Export current database rows to the JSON seed data used by `SeedDataSeeder`. |
| `database:restore-local [--force]` | Local environment only: run `migrate:fresh` and seed from `SeedDataSeeder`. |
| `database:restore-seed-data [--force] [--allow-production]` | Rebuild the current database from migrations and seed data; production requires `--allow-production`. |

`seed-data:export` and `SeedDataSeeder` require a MySQL, MariaDB, or PostgreSQL
connection; restore seed data on the same database type it was exported from.
`SeedDataSeeder` reads from
`database/seeders/data` (override with `DATABASE_SEED_DATA_PATH`). It expects
exported table data there, so run `seed-data:export` before using the restore
commands.

None of these commands are scheduled by default.
