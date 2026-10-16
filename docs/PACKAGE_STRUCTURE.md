# Package Structure

`n2ns/laravel-saas-kit` is a Composer package that installs a complete Laravel application template.

## Files

```text
composer.json                 package metadata and runtime dependencies
phpunit.xml                   package test configuration
CHANGELOG.md
src/
  SaasKitServiceProvider.php  registers the installer command
  Console/InstallCommand.php  saas-kit:install
stubs/root/                   the application copied into the host project
  app/
  bootstrap/
  config/
  database/
  deploy/
  public/
  resources/
  routes/
  scripts/
  storage/
  tests/
  .env.example
  .env.testing
  artisan
  composer.json               merge source only, never copied
  package.json
  vite.config.js
  README.md, NEW_SITE_CHECKLIST.md, DEPLOYMENT.md, SCRIPTS.md,
  API_AUTH.md, BLOG_GUIDELINES.md
docs/                         package documentation
scripts/
  check-template-sync.sh      compares stubs/root with a separate source app
tests/                        package tests (Orchestra Testbench)
.github/workflows/ci.yml
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

The provider only registers `InstallCommand` when the application runs in the console. The package adds no routes, config, views, or other runtime behavior; everything the site runs comes from the copied files.

## Installer

The installer command is:

```bash
php artisan saas-kit:install --force
```

Its only option is `--force`. The command:

1. Collects every file under `stubs/root`, including dotfiles. It skips `composer.json`, `composer.lock`, `vendor`, `node_modules`, and `.git`.
2. Without `--force`, stops with an error if any of those files already exist in the host application, and lists up to 20 of the conflicting paths.
3. With `--force`, deletes the Laravel skeleton directories and root files that the kit owns (listed below). This prevents duplicate default migrations, routes, controllers, tests, frontend config, and handoff docs.
4. Copies the template files into the application root and overwrites files with the same path.
5. Merges the template's Composer metadata into the host `composer.json` (see below).
6. Runs `composer dump-autoload` with a 120-second timeout. If that fails, it prints a warning and asks you to run it manually.
7. Prints the next setup steps: configure `.env`, `migrate:fresh`, `ReferenceDataSeeder`, Passport keys, the Passport social client, and the frontend build.

The owned paths that `--force` deletes first are listed in `InstallCommand::prepareFreshTarget()`:

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

Other template files, such as `artisan`, `.gitignore`, `.gitattributes`, `.editorconfig`, `API_AUTH.md`, `BLOG_GUIDELINES.md`, and `storage/framework/.gitignore`, are not deleted first. The copy step overwrites them. Host files that are not in the template, such as `.env`, are left untouched.

See [Installation](INSTALLATION.md#why---force) for why a fresh app needs `--force`.

## Composer Metadata

The installer does not overwrite the host application's `composer.json` or `composer.lock`.

`stubs/root/composer.json` is never copied. The installer only merges these keys from it into the host `composer.json`:

- `autoload.files` (currently `app/Helpers/helpers.php`), appended without duplicates.
- `autoload.psr-4` mappings that the host does not already define.
- `config.allow-plugins` entries that the host does not already define.

Nothing else is merged: not `require`, `require-dev`, `scripts`, or `extra`. The host keeps its own Laravel skeleton Composer scripts and dev dependencies.

The runtime package dependencies come from `n2ns/laravel-saas-kit` itself. The package's `require` block lists the same packages as the template's `composer.json`, so keep the package installed in the product site. If you remove it, first add those dependencies to the host `composer.json` yourself.

## Template Ownership

After installation, the host application owns the copied files. This is deliberate.

That means product sites can directly edit:

- Blade templates.
- language files.
- seed data.
- product-specific controllers/services.
- assets.
- deployment scripts.

Package updates do not change files that are already installed. Shared improvements belong in this package's `stubs/root` so that future product sites inherit them; see [Template Maintenance](TEMPLATE_MAINTENANCE.md).
