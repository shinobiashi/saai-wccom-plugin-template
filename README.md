# saai-wccom-plugin-template

WooCommerce extension plugin development template for WooCommerce.com Marketplace submission.

Template version: v1.0.0

## Stack

- **PHP**: 8.2+, WordPress 6.7+, WooCommerce 9.0+
- **Frontend**: `@wordpress/scripts` (webpack) + React
- **Dev environment**: `wp-env`
- **Coding standards**: WordPress Coding Standards (WPCS) + PHPCompatibilityWP
- **Static analysis**: PHPStan level 5
- **Testing**: PHPUnit (Unit / Integration) + Playwright (E2E)
- **Quality assurance**: [QIT CLI](https://qit.woo.com/)
- **HPOS**: WooCommerce Custom Order Tables compatibility declared
- **Blocks**: Cart & Checkout Blocks compatibility declared

## Directory Structure

```text
plugin-name/
├── plugin-name.php                            # Main plugin file (header + bootstrap)
├── uninstall.php                              # Uninstall handler
├── composer.json                              # PHP dependencies & scripts
├── package.json                               # JS dependencies & scripts
├── webpack.config.js                          # Build configuration (wp-scripts)
├── phpcs.xml                                  # PHPCS ruleset
├── phpstan.neon                               # PHPStan configuration
├── phpunit.xml.dist                           # PHPUnit configuration
├── .wp-env.json                               # wp-env dev environment
├── bin/                                       # CLI helper scripts
├── includes/
│   ├── class-plugin-name.php                  # Main singleton class
│   ├── class-plugin-name-core.php             # Core logic & DB operations
│   ├── admin/
│   │   ├── class-plugin-name-admin.php        # Admin page logic
│   │   ├── class-plugin-name-rest-api.php     # REST API endpoints
│   │   └── class-saai-admin-plugin-name.php   # SAAI admin submenu integration
│   └── saai_framework/
│       ├── class-saai-admin-page.php          # Shared SAAI top-level admin menu
│       └── class-saai-wc-user.php             # WooCommerce user utility
├── src/saai/
│   ├── admin/
│   │   ├── overview/                          # SAAI overview page (React)
│   │   └── plugin-name/                       # Plugin admin UI (React)
│   └── saai/                                  # Shared SAAI UI components
├── assets/
│   ├── build/                                 # webpack build output
│   └── images/                               # Icons and images
├── i18n/                                      # .pot / .po / .mo translation files
└── tests/
    ├── bootstrap.php                          # PHPUnit bootstrap
    ├── Unit/                                  # Unit tests
    ├── Integration/                           # Integration tests (WP/WC loaded)
    └── e2e/                                   # Playwright E2E tests
```

## Getting Started

### Prerequisites

- [Node.js / NPM](https://nodejs.org/)
- [Composer](https://getcomposer.org/download/)
- [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)

### Installation and Build

```bash
npm install          # Installs JS dependencies (also runs composer install via postinstall)
npm run build        # Build JS/CSS assets
wp-env start         # Start local dev environment
```

## Creating a New Plugin from This Template

1. Create a new repository using this template.
2. Replace all occurrences of the following placeholders:
   - `plugin-name` → your plugin slug (kebab-case)
   - `Plugin_Name` → your class prefix (Pascal_Case)
   - `plugin_name` → your function/option prefix (snake_case)
   - `pluginName` → your JS variable prefix (camelCase)
3. Update constants in `plugin-name.php`: `PLUGIN_NAME_PATH`, `PLUGIN_NAME_URL`, `PLUGIN_NAME_VERSION`.
4. Update `name` fields in `composer.json` and `package.json`.
5. Update `phpcs.xml` text domain and `phpstan.neon` paths.
6. Run `npm install && npm run build`.

## Common Commands

### JavaScript / CSS

| Command | Description |
| --- | --- |
| `npm run build` | Build JS/CSS (production) |
| `npm run start` | Watch mode (development) |
| `npm run lint:js` | Lint JavaScript/TypeScript |
| `npm run lint:css` | Lint CSS |
| `npm run format` | Auto-format source files |
| `npm run plugin-zip` | Create distributable plugin zip |

### PHP

| Command | Description |
| --- | --- |
| `composer phpcs` | Run PHPCS code style check |
| `composer phpcbf` | Auto-fix PHPCS violations |
| `composer phpstan` | Run PHPStan static analysis (level 5) |
| `composer test` | Run all PHPUnit tests |
| `composer test:unit` | Run unit tests only |
| `composer test:integration` | Run integration tests only |

### Dev Environment

| Command | Description |
| --- | --- |
| `wp-env start` | Start dev environment |
| `wp-env stop` | Stop dev environment |
| `wp-env clean` | Reset environment data |

### Internationalization

| Command | Description |
| --- | --- |
| `npm run make-pot` | Generate `.pot` translation template |
| `npm run make-json` | Generate JS translation JSON files |

### E2E Tests

| Command | Description |
| --- | --- |
| `npm run playwright:install` | Install Playwright browsers |
| `npm run test:e2e` | Run Playwright E2E tests |
| `npm run test:e2e:ui` | Run E2E tests with Playwright UI |
| `npm run test:e2e:debug` | Debug E2E tests |

## WooCommerce.com Marketplace

This template is pre-configured for WooCommerce Marketplace submission:

- HPOS (Custom Order Tables) compatibility declared
- Cart & Checkout Blocks compatibility declared
- PHPCS ruleset targeting WordPress Coding Standards + PHPCompatibilityWP
- QIT CLI included for running Marketplace quality checks locally
