# Changelog

All notable changes to this template will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).  
Template versioning follows [Semantic Versioning](https://semver.org/).

> **Note**: This changelog tracks changes to the *template itself*, not to plugins built from it.

---

## [1.0.0] - 2026-04-13

### Added

- Initial template release
- Main plugin bootstrap (`plugin-name.php`) with WooCommerce detection, HPOS and Blocks compatibility declarations
- Singleton main class (`includes/class-plugin-name.php`) with activation/deactivation hooks
- Core class (`includes/class-plugin-name-core.php`) with DB table creation via `dbDelta`
- Admin submenu integration via SAAI framework (`includes/admin/class-saai-admin-plugin-name.php`)
- REST API endpoint scaffold (`includes/admin/class-plugin-name-rest-api.php`)
- SAAI framework utilities: `class-saai-admin-page.php`, `class-saai-wc-user.php`
- React admin UI entry points under `src/saai/admin/` (overview + plugin-name pages)
- Webpack build via `@wordpress/scripts` with `--blocks-manifest` flag
- `wp-env` local development environment with WooCommerce, debug plugins pre-loaded
- PHPCS ruleset (`phpcs.xml`): WordPress-Extra, WordPress-Docs, PHPCompatibilityWP (PHP 8.2+, WP 6.7+)
- PHPStan configuration (`phpstan.neon`) at level 5 with WordPress stubs
- PHPUnit configuration (`phpunit.xml.dist`) with Unit and Integration test suites
- Playwright E2E test scaffold under `tests/e2e/`
- QIT CLI integration via Composer for WooCommerce Marketplace quality checks
- `i18n/` translation workflow: `make-pot` and `make-json` npm scripts
- `uninstall.php` clean uninstall handler
- `README.md` with full setup and command reference
