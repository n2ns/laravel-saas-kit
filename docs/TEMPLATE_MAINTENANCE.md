# Template Maintenance

The package should keep its installable application stubs consistent and release-ready.

`stubs/root` is the application skeleton copied into a fresh Laravel app by `php artisan saas-kit:install --force`. Do not add generated runtime artifacts, local credentials, or product-specific data to this directory.

## Maintenance Model

The kit is installer-first. Every product site gets its own copy of `stubs/root`, and from then on the site owns those files. Because of this:

- Shared improvements (bug fixes, security fixes, and generic features that every product site should have) must be written back into this package's `stubs/root`. Future product sites then inherit the same implementation.
- A fix made only inside one product site stays in that site. Nothing flows back into the kit automatically.
- Existing product sites do not receive kit changes automatically. See [Updating Existing Product Sites](#updating-existing-product-sites).

## Source of Truth

Keep generic SaaS/product-site behavior in `stubs/root`:

- authentication
- API auth
- subscriptions and billing
- product catalog
- blog
- SEO
- Filament admin
- analytics
- deployment scripts
- tests

You can edit `stubs/root` directly. If you instead maintain a separate working app while developing shared changes, copy the verified files into this package:

```bash
rsync -a \
  --exclude='.git' \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='composer.lock' \
  --exclude='.env' \
  --exclude='.phpunit.cache' \
  --exclude='.phpunit.result.cache' \
  --exclude='bootstrap/cache/*.php' \
  --exclude='storage/app/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/testing/*' \
  --exclude='storage/framework/views/*' \
  --exclude='storage/logs/*' \
  --exclude='public/build' \
  --exclude='public/storage' \
  /path/to/source-template/ stubs/root/
```

Check that the packaged stubs have not drifted from that working source:

```bash
bash scripts/check-template-sync.sh /path/to/source-template
```

The script also reads the source path from `SAAS_KIT_TEMPLATE_PATH` when no argument is given. It runs `diff -qr`, which lists differing or missing files and exits non-zero when anything differs. It ignores `.git`, `vendor`, `node_modules`, `bootstrap/cache`, `storage`, `composer.lock`, `.env`, PHPUnit caches, and build output.

Keep package-only files in this repository, outside `stubs/root`:

- `composer.json`
- `src/SaasKitServiceProvider.php`
- `src/Console/InstallCommand.php`
- `docs`
- package tests in `tests`
- `scripts/check-template-sync.sh`
- `.github/workflows/ci.yml`
- `CHANGELOG.md`

## Keeping the Package in Step with the Stubs

Some template changes also need package-side changes:

- **Runtime dependencies.** The installer does not merge `require` from `stubs/root/composer.json`; the product site gets its runtime packages through this package's `composer.json`. When the template gains, drops, or bumps a runtime dependency, update `require` in both `stubs/root/composer.json` and the package `composer.json`. Template-only dev tools in `stubs/root/composer.json` `require-dev` are not installed in product sites.
- **Autoload and plugins.** New `autoload.files`, `autoload.psr-4`, or `config.allow-plugins` entries in `stubs/root/composer.json` are merged into the host automatically.
- **New kit-owned root paths.** If the template adds a top-level directory or root file that should replace a Laravel skeleton default, add it to `InstallCommand::prepareFreshTarget()`. Also update the lists in [Installation](INSTALLATION.md#why---force) and [Package Structure](PACKAGE_STRUCTURE.md#installer).
- **Package tests.** `tests/InstallCommandTest.php` asserts that key stub files exist, for example `routes/api.php`, `app/Console/Commands/EnsurePassportSocialClientCommand.php`, and `database/migrations/0001_01_01_000000_create_identity_tables.php`. It also asserts that `scripts/deploy-production.sh` runs `php artisan passport:ensure-social-client` and that `routes/api.php` has no built-in MCP routes. Update the tests when you move or rename those files.

## Do Not Copy

Do not copy runtime or local development artifacts into `stubs/root`:

- `.git`
- `vendor`
- `node_modules`
- `bootstrap/cache/*.php`
- `composer.lock`
- `public/build`
- `public/storage`
- `storage/logs`
- `storage/framework/*` (the committed `.gitignore` placeholders are the exception)
- `.env`
- real keys, credentials, webhook payloads, or production exports

The installer never copies `composer.json`, `composer.lock`, `vendor`, `node_modules`, or `.git` from `stubs/root`, even if they are present.

## What Belongs in the Kit

Keep generic SaaS/product-site capability in `stubs/root`:

- login
- account pages
- API auth
- Stripe plans/orders/subscriptions/credits
- Blog
- product catalog
- SEO
- analytics
- admin resources
- deployment scripts

Do not add product-specific business logic to the kit:

- product-specific workflows
- provider-specific business logic
- customer-specific data
- private production seed data
- product-specific prompts or automation rules

Those belong in the product site after installation.

## Updating Existing Product Sites

Existing product sites do not automatically receive copied file changes. Updating the Composer package does not touch files that are already installed. Re-running `saas-kit:install --force` on an existing site would delete and replace its owned paths (see [Package Structure](PACKAGE_STRUCTURE.md#installer)), so do not use it to update a site.

For shared fixes that should reach existing sites:

1. Patch and verify the shared implementation.
2. Copy the change into this package's `stubs/root`.
3. Apply the same patch manually or with a focused migration script to existing product sites.
4. Run that product site's own tests.

Avoid hiding major application behavior in the package runtime. The goal is fast site creation with editable code, not a vendor-locked framework.
