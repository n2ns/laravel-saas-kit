# Changelog

All notable changes to Laravel SaaS Kit are documented in this file.

## [Unreleased]

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

## [0.1.0] - 2026-06-24

### Added

- Initial installable Composer starter kit for fresh Laravel 12 SaaS/product websites.
- Included the maintained SaaS Starter under `stubs/root`.
- Added installer command `saas-kit:install`.
- Added documentation for installation, package structure, template maintenance, Post2Site integration, verification, and deployment handoff.
- Added Composer package checks, local installation smoke coverage, and archive hygiene.
