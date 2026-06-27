# Scripts

## `scripts/deploy-production.sh`

Runs the routine Laravel production deployment steps from the project root.
When `vendor` already exists, the script enters Laravel maintenance mode before
updating dependencies and brings the site back up at the end:

1. `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`
2. `npm ci`
3. `npm run build`
4. `php artisan storage:link` when `public/storage` does not exist
5. `php artisan optimize:clear`
6. `php artisan migrate --force`
7. `php artisan passport:ensure-social-client`
8. Optional `php artisan db:seed --class=ReferenceDataSeeder --force`
9. `php artisan optimize`
10. `php artisan reload`

Usage:

```bash
bash scripts/deploy-production.sh
```

Options:

```text
--seed-reference       Seed starter reference data after migrations.
                       Use only on a new product site before real content exists.
--skip-npm             Skip npm ci and npm run build.
--skip-migrate         Skip php artisan migrate --force.
--skip-reload          Skip php artisan reload.
--skip-maintenance     Do not run php artisan down/up around the deployment.
-h, --help             Show help.
```

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
Those are one-time server/provider setup tasks documented in `DEPLOYMENT.md`
and `API_AUTH.md`.

## `scripts/run-local-tests.sh`

Runs the local Laravel test suite through Laravel's parallel test runner:

```bash
bash scripts/run-local-tests.sh
```

Environment knobs:

```bash
TEST_PROCESSES=4
TEST_RECREATE_DATABASES=1
```

Examples:

```bash
TEST_PROCESSES=2 composer test
TEST_RECREATE_DATABASES=0 composer test
composer test -- --filter=TemplateSmokeTest
```

## `scripts/lint-php.php`

Used by `composer check` to run PHP syntax checks over application, route, config,
database, and test PHP files.
