# Template Maintenance

The package should keep its installable application stubs consistent and release-ready.

`stubs/root` is the application skeleton copied into a fresh Laravel app by `php artisan saas-kit:install --force`. Do not add generated runtime artifacts, local credentials, or product-specific data to this directory.

## Source of Truth

Keep generic SaaS/product-site behavior in `stubs/root`:

- authentication
- API auth
- subscriptions and billing
- product catalog
- blog and product articles
- SEO
- Filament admin
- analytics
- deployment scripts
- tests

If you maintain a separate working app while developing shared changes, copy the verified files into this package:

```bash
rsync -a \
  --exclude='.git' \
  --exclude='vendor' \
  --exclude='node_modules' \
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

Keep package-only files in this repository:

- `composer.json`
- `src/SaasKitServiceProvider.php`
- `src/Console/InstallCommand.php`
- `docs`
- package tests

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
- `storage/framework/*`
- `.env`
- real keys, credentials, webhook payloads, or production exports

## What Belongs in the Kit

Keep generic SaaS/product-site capability in `stubs/root`:

- login
- account pages
- API auth
- Stripe plans/orders/subscriptions/credits
- Blog
- product catalog
- product articles
- SEO
- analytics
- admin resources
- the SaaS Kit content model used by the `laravel_saas_kit` Post2Site preset
- deployment scripts

Do not add product-specific business logic to the kit:

- product-specific workflows
- provider-specific business logic
- customer-specific data
- private production seed data
- product-specific prompts or automation rules

Those belong in the product site after installation.

## Updating Existing Product Sites

This kit is installer-first. Existing product sites do not automatically receive copied file changes.

For shared fixes that should reach existing sites:

1. Patch and verify the shared implementation.
2. Copy the change into this package's `stubs/root`.
3. Apply the same patch manually or with a focused migration script to existing product sites.
4. Run that product site's own tests.

Avoid hiding major application behavior in the package runtime. The goal is fast site creation with editable code, not a vendor-locked framework.
