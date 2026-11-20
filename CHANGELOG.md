# Changelog

All notable changes to Laravel SaaS Kit are documented in this file.

## [Unreleased]

## [0.2.1] - 2026-11-20

### Security

- The template's `package.json` overrides `postcss-selector-parser` for `@tailwindcss/typography` to `^6.1.6`, fixing GHSA-rj75-hqrm-r3gf; `npm audit` now reports no vulnerabilities, and the built CSS is unchanged.

## [0.2.0] - 2026-11-20

### Added

- PostgreSQL support. Catalog and blog search no longer use MySQL-only SQL, match case-insensitively on PostgreSQL, and keep fulltext matching when a searched column is empty, blog pin ordering no longer compares a boolean with an integer, and `seed-data:export` and `SeedDataSeeder` (with `database:restore-local` and `database:restore-seed-data`) work on PostgreSQL.

### Changed

- Raised Composer dependencies to their newest releases that still support Laravel 12 and PHP 8.3, including Laravel 12.73, Filament 5.11, Cashier 16.11, Passport 13.12, and Socialite 5.33. `laravel/tinker` stays on 2.x because the Laravel 12 skeleton requires it, and `phpunit/phpunit` stays on 12.5.33 because Pest 4.7.8 conflicts with newer releases.
- Raised the template's npm dependencies to their newest releases, including Vite 8.3.8 and Tailwind CSS 4.3.5. The refreshed lock file no longer includes the vulnerable `follow-redirects` release.

### Removed

- Removed the built-in legacy MCP publishing routes (`/api/v1/mcp/*`), their controller, and the `SAAS_KIT_LEGACY_MCP_ROUTES` switch.
- Removed the Post2Site integration guide and smoke script. The kit does not depend on any publishing package; the README now only suggests `n2ns/laravel-post2site` as an optional add-on.

### Fixed

- Documentation no longer describes product guides, product articles, or `content_scope`, which the template does not provide.
- Quick Start, installation, verification, and CI now run `ReferenceDataSeeder` so the starter product and plans exist.
- The `--force` replacement list now includes `deploy`.
- Restored the day count, revoked-session count, and weekly usage numbers in admin messages that rendered as `{}`.
- Updated template tests to the English admin labels, so the installed test suite passes again.
- `scripts/lint-php.php` now skips `vendor` and `node_modules` on Windows.
- `ReferenceDataSeeder` reads the starter Stripe IDs from `config/services.php`, so they are no longer stored as empty values when the configuration is cached.
- `/robots.txt` is now rendered with a `Sitemap:` URL based on `APP_URL` instead of a static file pointing to `http://localhost:8000`; the Nginx example passes it to Laravel.
- The admin panel uses the site's default locale instead of forcing `zh_CN` alongside English admin labels.
- Removed the unused `ADMIN_PASSWORD` entry from `.env.example`.
- Catalog search no longer throws on SQLite; it skips the fulltext branch there, as blog search already did.
- `config/database.php` uses `Pdo\Mysql::ATTR_SSL_CA` instead of the `PDO::MYSQL_ATTR_SSL_CA` constant deprecated in PHP 8.5.
- `scripts/check-template-sync.sh` now anchors its exclusions to the template root, so `config/services.php` is compared instead of being skipped along with `bootstrap/cache/services.php`.

## [0.1.0] - 2026-06-24

### Added

- Initial installable Composer starter kit for fresh Laravel 12 SaaS/product websites.
- Included the maintained SaaS Starter under `stubs/root`.
- Added installer command `saas-kit:install`.
- Added documentation for installation, package structure, template maintenance, Post2Site integration, verification, and deployment handoff.
- Added Composer package checks, local installation smoke coverage, and archive hygiene.
