# CLAUDE.md — WooCommerce Extension Plugin Development

## Project Overview

- **Type**: WooCommerce extension plugin (WordPress plugin)
- **Namespace**: `{Vendor}\{PluginName}` (PSR-4)
- **Min Requirements**: PHP 8.2+, WordPress 6.7+, WooCommerce 9.0+
- **Text Domain**: `{plugin-slug}`
- **License**: GPL-3.0-or-later

## Architecture & Conventions

### Directory Structure

```
plugin-slug/
├── plugin-slug.php          # Main plugin file (plugin header, bootstrap)
├── uninstall.php             # Clean uninstall handler
├── composer.json
├── package.json
├── webpack.config.js         # or vite.config.ts
├── phpcs.xml.dist
├── phpstan.neon
├── phpunit.xml.dist
├── .wp-env.json
├── src/                      # PSR-4 autoloaded PHP classes
│   ├── Admin/                # Admin pages, settings, meta boxes
│   ├── API/                  # REST API endpoints (WP_REST_Controller)
│   ├── Blocks/               # WooCommerce Blocks integration
│   ├── Gateway/              # Payment gateway classes (if applicable)
│   ├── Model/                # Data models / entities
│   ├── Repository/           # Data access layer (CRUD)
│   ├── Service/              # Business logic
│   ├── Shipping/             # Shipping method classes (if applicable)
│   └── Hooks.php             # Central hook registration
├── assets/
│   └── js/
│       └── src/              # React/TypeScript source
├── build/                    # Compiled JS/CSS (wp-scripts output)
├── includes/                 # Legacy procedural code (if needed)
├── languages/                # .pot / .po / .mo files
├── templates/                # PHP template files (overridable)
└── tests/
    ├── Unit/                 # PHPUnit unit tests
    ├── Integration/          # PHPUnit integration tests (WP/WC loaded)
    └── E2E/                  # Playwright E2E tests
```

### Coding Standards

- Follow **WordPress Coding Standards** (WPCS) for PHP
- Use `wp_kses_post()`, `esc_html()`, `esc_attr()`, `esc_url()` for all output escaping
- Use `sanitize_text_field()`, `absint()`, `wc_clean()` for all input sanitization
- Nonces required for ALL form submissions and AJAX: `wp_nonce_field()` / `wp_verify_nonce()`
- Capability checks: `current_user_can( 'manage_woocommerce' )` before admin operations
- Prefix all global functions, hooks, options with plugin slug: `{plugin_slug}_`
- Database table names: `$wpdb->prefix . '{plugin_slug}_tablename'`
- PHPStan level 5+ (`phpstan.neon` with WordPress/WooCommerce stubs)
- ESLint with `@woocommerce/eslint-plugin` (extends WordPress rules + WooCommerce dependency groups)
- TypeScript strict mode for all frontend code

### PHP Style Rules

- Declare `declare(strict_types=1);` at top of every PHP file
- Type hints on all method parameters and return types
- Use constructor promotion where appropriate (PHP 8.2+)
- No `@` error suppression operator
- Array syntax: short arrays `[]` only, never `array()`
- Singleton `$instance` must be typed `@var ClassName|null` and initialized to `null`; use `is_null()` not `isset()` for the null check

## WooCommerce HPOS Compatibility (CRITICAL)

All plugins MUST be compatible with High-Performance Order Storage (HPOS / Custom Order Tables).

### Required in Main Plugin File

```php
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});
```

### HPOS-Incompatible Patterns — NEVER USE

| ❌ Forbidden | ✅ Use Instead |
|---|---|
| `get_post_meta($order_id, ...)` | `$order->get_meta(...)` |
| `update_post_meta($order_id, ...)` | `$order->update_meta_data(...); $order->save();` |
| `delete_post_meta($order_id, ...)` | `$order->delete_meta_data(...); $order->save();` |
| `get_post($order_id)` | `wc_get_order($order_id)` |
| `new WP_Query(['post_type' => 'shop_order'])` | `wc_get_orders([...])` |
| `$wpdb->get_results("...{$wpdb->posts}... post_type='shop_order'")` | `wc_get_orders()` or `OrdersTableQuery` |
| `wp_insert_post()` for orders | `$order = new WC_Order(); $order->save();` |
| Accessing `$order->post` property | Use `$order->get_*()` getter methods |

### Order Query Examples

```php
// Simple query
$orders = wc_get_orders([
    'meta_key'   => '_custom_key',
    'meta_value' => 'value',
    'limit'      => 20,
    'orderby'    => 'date',
    'order'      => 'DESC',
    'status'     => ['wc-processing', 'wc-completed'],
]);

// Date range query
$orders = wc_get_orders([
    'date_created' => '2024-01-01...2024-12-31',
    'type'         => 'shop_order',
]);
```

## WooCommerce Blocks / Checkout Integration

### Cart & Checkout Block Compatibility

```php
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
    }
});
```

### StoreAPI ExtendSchema (Adding Data to Checkout)

```php
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;

add_action('woocommerce_blocks_loaded', function () {
    $extend = StoreApi::container()->get(ExtendSchema::class);
    $extend->register_endpoint_data([
        'endpoint'        => CheckoutSchema::IDENTIFIER,
        'namespace'       => '{plugin-slug}',
        'data_callback'   => fn() => ['custom_field' => ''],
        'schema_callback' => fn() => [
            'custom_field' => [
                'description' => 'Custom field',
                'type'        => 'string',
                'context'     => ['view', 'edit'],
            ],
        ],
    ]);
});
```

### Payment Method Block Registration

```javascript
// assets/js/src/blocks/payment-method/index.tsx
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';

const settings = window.wc?.wcSettings?.getSetting('{pluginSlug}_data') ?? {};

registerPaymentMethod({
    name: '{plugin-slug}',
    label: decodeEntities(settings.title || ''),
    content: <Content />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: settings.title || '',
    supports: {
        features: settings.supports ?? ['products'],
    },
});
```

## Payment Gateway Development (if applicable)

### Gateway Class Template

```php
class CustomGateway extends \WC_Payment_Gateway {
    public function __construct() {
        $this->id                 = '{plugin_slug}';
        $this->method_title       = __('Custom Gateway', '{text-domain}');
        $this->method_description = __('Description', '{text-domain}');
        $this->has_fields         = true;
        $this->supports           = ['products', 'refunds'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title   = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']
        );
    }

    public function process_payment($order_id): array {
        $order = wc_get_order($order_id);
        // Token-based payment processing (トークン決済)
        // NEVER log or store raw card numbers
        $order->payment_complete($transaction_id);
        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }

    public function process_refund($order_id, $amount = null, $reason = ''): bool|WP_Error {
        // Implement refund logic
    }
}
```

### Security Rules for Payment Gateways

- NEVER log, store, or expose raw credit card numbers (PCI DSS)
- Always use token-based payments (トークン決済) — send card data directly to gateway JS, receive a token
- Validate webhook signatures / IP restrictions
- Use `WC_Logger` for debug logging (not `error_log`)
- Sanitize and validate ALL callback/webhook payloads

## REST API Endpoints

### Endpoint Registration Pattern

```php
class CustomEndpoint extends \WP_REST_Controller {
    protected $namespace = '{plugin-slug}/v1';
    protected $rest_base = 'items';

    public function register_routes(): void {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args'                => $this->get_collection_params(),
            ],
        ]);
    }

    public function get_items_permissions_check($request): bool|\WP_Error {
        if (! current_user_can('manage_woocommerce')) {
            return new \WP_Error(
                'rest_forbidden',
                __('Insufficient permissions.', '{text-domain}'),
                ['status' => 403]
            );
        }
        return true;
    }
}
```

- Always implement `permission_callback` (never set to `__return_true` for write operations)
- Return `WP_REST_Response` or `WP_Error`
- Use `$request->get_param()` with sanitization; use `?? default` (null coalescing) not `?: default` (Elvis / short ternary — banned by PHPCS)
- Validate with `'validate_callback'` in args

## React Admin UI (Gutenberg / wp-scripts)

### Setup

```json
// package.json
{
  "scripts": {
    "build": "wp-scripts build",
    "start": "wp-scripts start",
    "lint:js": "wp-scripts lint-js",
    "lint:css": "wp-scripts lint-style"
  },
  "devDependencies": {
    "@wordpress/scripts": "latest",
    "@woocommerce/eslint-plugin": "latest",
    "@wordpress/prettier-config": "latest",
    "@wordpress/api-fetch": "latest",
    "@wordpress/i18n": "latest"
  }
}
```

> **Note**: Use `latest` for `@wordpress/*` and `@woocommerce/*` packages to stay current with Gutenberg releases.
> `package-lock.json` MUST be committed — do NOT add it to `.gitignore`. CI caches depend on it.

### Data Fetching with wp.apiFetch

```typescript
import apiFetch from '@wordpress/api-fetch';

// GET
const items = await apiFetch<Item[]>({
    path: '/{plugin-slug}/v1/items',
});

// POST with nonce (automatic via apiFetch)
await apiFetch({
    path: '/{plugin-slug}/v1/items',
    method: 'POST',
    data: { title: 'New Item' },
});
```

### SPA Admin Page Registration

```php
add_action('admin_menu', function () {
    $hook = add_submenu_page(
        'woocommerce',
        __('Custom Plugin', '{text-domain}'),
        __('Custom Plugin', '{text-domain}'),
        'manage_woocommerce',
        '{plugin-slug}',
        function () {
            echo '<div id="{plugin-slug}-admin-root"></div>';
        }
    );

    add_action("admin_enqueue_scripts", function ($admin_hook) use ($hook) {
        if ($admin_hook !== $hook) return;

        $asset = require plugin_dir_path(__FILE__) . 'build/admin.asset.php';
        wp_enqueue_script(
            '{plugin-slug}-admin',
            plugins_url('build/admin.js', __FILE__),
            $asset['dependencies'],
            $asset['version'],
            true
        );
        wp_localize_script('{plugin-slug}-admin', '{pluginSlug}Vars', [
            'restUrl' => rest_url('{plugin-slug}/v1/'),
            'nonce'   => wp_create_nonce('wp_rest'),
        ]);
    });
});
```

## Database Operations

### Custom Table Creation (dbDelta)

```php
function create_tables(): void {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . '{plugin_slug}_items';

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        order_id bigint(20) unsigned NOT NULL DEFAULT 0,
        status varchar(20) NOT NULL DEFAULT 'open',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY order_id (order_id),
        KEY status (status)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
```

- Always use `$wpdb->prepare()` for queries with variables
- Use `$wpdb->prefix` for table names
- Store DB version in options for migration management
- Implement `uninstall.php` to drop tables and clean options

## Internationalization (i18n)

- Wrap ALL user-facing strings with `__()`, `_e()`, `esc_html__()`, `esc_html_e()`, `_n()`, `_x()`
- Text domain must match plugin slug
- Generate `.pot` with: `wp i18n make-pot . languages/{plugin-slug}.pot`
- JS translations: `wp i18n make-json languages/`
- Load translations:

```php
add_action('init', function () {
    load_plugin_textdomain(
        '{text-domain}',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});
```

## Testing

### wp-env Configuration

```json
// .wp-env.json
{
  "core": null,
  "phpVersion": "8.2",
  "plugins": [
    "https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip",
    "."
  ],
  "mappings": {
    "wp-content/mu-plugins/test-setup.php": "./tests/test-setup.php"
  },
  "lifecycleScripts": {
    "afterStart": "wp-env run cli -- wp wc update --yes && wp-env run cli -- wp rewrite structure '/%postname%/'"
  }
}
```

### PHPStan Constants Bootstrap

Plugin constants (e.g. `PLUGIN_NAME_URL`) are defined inside `if ( is_woocommerce_active() )` at runtime.
Create `phpstan-bootstrap.php` in the project root to expose them to static analysis:

```php
<?php
// phpstan-bootstrap.php
define( 'PLUGIN_NAME_PATH', __DIR__ );
define( 'PLUGIN_NAME_URL', 'http://example.com/wp-content/plugins/plugin-name/' );
define( 'PLUGIN_NAME_VERSION', '1.0.0' );
```

Reference it in `phpstan.neon`:

```neon
parameters:
    bootstrapFiles:
        - vendor/autoload.php
        - phpstan-bootstrap.php
```

### PHPUnit Bootstrap

```php
// tests/bootstrap.php
$_tests_dir = getenv('WP_TESTS_DIR') ?: '/tmp/wordpress-tests-lib';
require_once $_tests_dir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', function () {
    require dirname(__DIR__) . '/vendor/woocommerce/woocommerce/woocommerce.php';
    require dirname(__DIR__) . '/plugin-slug.php';
});

require $_tests_dir . '/includes/bootstrap.php';
```

### Test Priorities (Risk-Based)

1. **P0 (Critical)**: Payment processing, webhook handlers, security (auth, nonces, capabilities)
2. **P1 (High)**: Order meta CRUD (HPOS), checkout flow, StoreAPI integration
3. **P2 (Medium)**: Admin settings save/load, REST API endpoints, i18n
4. **P3 (Low)**: UI rendering, email templates, edge cases

## CI/CD (GitHub Actions)

### Matrix Testing

```yaml
strategy:
  matrix:
    php: ['8.2', '8.3']
    woocommerce: ['9.0', 'latest']
    wordpress: ['6.7', 'latest']
```

### Plugin Activation in CI

`wp plugin activate` takes the **directory name** (slug), not the plugin display name.
When wp-env mounts `.` as a plugin, the slug matches the repository directory name on the runner.
Always use the GitHub Actions context variable — never hardcode:

```yaml
- name: Verify activation
  run: |
    wp-env run tests-cli wp plugin activate ${{ github.event.repository.name }}
    wp-env run tests-cli wp plugin list --status=active --fields=file --format=csv \
      | grep -q "${{ github.event.repository.name }}" && echo "Plugin active: OK"
```

### Required CI Checks

- PHPCS (WordPress standards)
- PHPStan level 5+
- ESLint + TypeScript check
- PHPUnit (unit + integration)
- Playwright E2E (critical paths only)

## WooCommerce Hook Reference (Commonly Used)

### Order Lifecycle

- `woocommerce_new_order` — Order created
- `woocommerce_order_status_changed` — Status transition ($order_id, $old_status, $new_status)
- `woocommerce_payment_complete` — Payment completed
- `woocommerce_order_refunded` — Refund processed

### Checkout

- `woocommerce_checkout_process` — Validate custom fields
- `woocommerce_checkout_order_processed` — After order creation
- `woocommerce_checkout_update_order_meta` — Save custom checkout fields (legacy)
- `woocommerce_store_api_checkout_update_order_from_request` — Save fields (Blocks checkout)

### Cart

- `woocommerce_before_calculate_totals` — Modify prices
- `woocommerce_cart_calculate_fees` — Add fees
- `woocommerce_add_to_cart_validation` — Validate before adding

### Admin

- `woocommerce_admin_order_data_after_billing_address` — Add fields to order edit
- `woocommerce_process_shop_order_meta` — Save order edit fields

## Common Pitfalls — AVOID THESE

1. **Forgetting `$order->save()`** after `update_meta_data()` — data won't persist
2. **Using `$_POST` / `$_GET` directly** — always use `wc_clean()`, `sanitize_text_field()`
3. **Hardcoding `shop_order` post type** queries — breaks HPOS
4. **Missing `permission_callback`** in REST routes — security vulnerability
5. **Not checking `is_admin()` / `is_checkout()`** before enqueuing scripts — performance
6. **Using `wp_die()` in AJAX handlers** — use `wp_send_json_error()` / `wp_send_json_success()`
7. **Not declaring HPOS compatibility** — plugin will be flagged incompatible
8. **Logging sensitive data** (card numbers, tokens) — PCI DSS violation
9. **Missing text domain** in translatable strings — i18n breaks
10. **Not handling WooCommerce deactivation** — check `class_exists('WooCommerce')` on init
11. **Short ternary `?:` (Elvis operator)** — banned by PHPCS (`Universal.Operators.DisallowShortTernary`). Use `??` for null fallback or full ternary `$x ? $x : $default`
12. **`@var ClassName` without `|null` on singleton `$instance`** — PHPStan reports `is_null()` / `isset()` as always false/true. Always annotate `@var ClassName|null` and initialize `= null`
13. **`isset()` on a local variable never previously assigned** — PHPStan error. Remove the `isset()` guard; just assign directly
14. **Plugin constants defined inside conditional blocks** — PHPStan cannot resolve them. Define them in `phpstan-bootstrap.php` for static analysis
15. **`package-lock.json` in `.gitignore`** — breaks CI `actions/setup-node` cache. Always commit the lockfile
16. **Hardcoding plugin slug in `wp plugin activate`** — the directory name on the CI runner is the repository name, not `plugin-name`. Use `${{ github.event.repository.name }}`
17. **JS imports without dependency comment blocks** — `@woocommerce/eslint-plugin` requires `// External dependencies` and `// Internal dependencies` blocks around import groups
18. **Spaces instead of tabs in JS/JSX** — `prettier` config enforces tabs. Run `npm run lint:js -- --fix` to auto-correct

## Pro/Free Tier Extension Pattern

```php
// Free plugin: expose hooks for Pro
do_action('{plugin_slug}_after_process', $data);
$result = apply_filters('{plugin_slug}_modify_output', $default, $context);

// Pro plugin: extend via hooks
add_filter('{plugin_slug}_modify_output', function ($default, $context) {
    // Pro-only logic
    return $enhanced;
}, 10, 2);
```

## Claude Code Instructions

- When creating a new plugin, generate the full directory structure first
- Always run `composer install` and `npm install` before any PHP/JS work
- Run `npx wp-env start` to spin up the development environment
- After modifying PHP, run: `composer phpcs` and `composer phpstan`
- After modifying JS/TS, run: `npm run lint:js` and `npm run build`
- Write tests alongside new features, never defer testing
- Commit messages: conventional commits (`feat:`, `fix:`, `chore:`, `test:`)
- When fixing bugs, write a regression test FIRST, then fix
- When adding plugin constants (`define()`), also add them to `phpstan-bootstrap.php`
- Never add `package-lock.json` to `.gitignore`
- JS files must use tabs for indentation and include `// External dependencies` / `// Internal dependencies` comment blocks around import groups
