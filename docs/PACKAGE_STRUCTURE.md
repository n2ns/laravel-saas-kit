# Package Structure

`n2ns/laravel-saas-kit` is a Composer package that installs a complete Laravel application template.

## Files

```text
composer.json
src/
  SaasKitServiceProvider.php
  Console/InstallCommand.php
stubs/root/
  app/
  bootstrap/
  config/
  database/
  deploy/
  public/
  resources/
  routes/
  scripts/
  tests/
docs/
tests/
```

## Composer Discovery

The package registers its service provider through Laravel package discovery:

```json
"extra": {
  "laravel": {
    "providers": [
      "N2ns\\SaasKit\\SaasKitServiceProvider"
    ]
  }
}
```

Laravel discovers the provider after `composer require n2ns/laravel-saas-kit`.

## Installer

The installer command is:

```bash
php artisan saas-kit:install --force
```

It copies `stubs/root` into the host application's root.

With `--force`, it first removes Laravel skeleton directories and root template
files that the kit owns. This prevents duplicate default migrations, routes,
controllers, tests, frontend config, and handoff docs.

Owned paths currently include:

- `app`
- `bootstrap/app.php`
- `bootstrap/providers.php`
- `config`
- `database/factories`
- `database/migrations`
- `database/seeders`
- `deploy`
- `public`
- `resources`
- `routes`
- `scripts`
- `tests`
- `.env.example`
- `.env.testing`
- `DEPLOYMENT.md`
- `NEW_SITE_CHECKLIST.md`
- `README.md`
- `SCRIPTS.md`
- `package.json`
- `package-lock.json`
- `phpstan.neon`
- `phpunit.xml`
- `vite.config.js`

## Composer Metadata

The installer does not overwrite the host application's `composer.json` or `composer.lock`.

It only merges the pieces needed by the installed template:

- `autoload.files`, especially `app/Helpers/helpers.php`.
- missing `autoload.psr-4` mappings from the template.
- missing `config.allow-plugins` entries required by dependencies.

The runtime package dependencies are provided by `n2ns/laravel-saas-kit` itself through Composer.

## Template Ownership

After installation, the host application owns the copied files. This is deliberate.

That means product sites can directly edit:

- Blade templates.
- language files.
- seed data.
- product-specific controllers/services.
- assets.
- deployment scripts.

Shared improvements should still be made in the template source first, then copied into `stubs/root`.
