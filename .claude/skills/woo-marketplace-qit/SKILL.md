---
name: woo-marketplace-qit
description: >
  WooCommerce.com Marketplace向けプラグインの品質テストスキル。QIT（Quality Insights Toolkit）の全テストスイート
  （Activation, Security, PHPStan, PHP Compatibility, Malware, Woo E2E, Woo API）への対応方法、ローカル実行、
  GitHub Actions CI統合、カスタムE2Eテスト作成、PHPCS/ESLintによるコーディングスタンダード準拠を網羅する。
  「QIT」「マーケットプレイステスト」「品質テスト」「セキュリティスキャン」「PHPStan」「Woo E2E」「プラグインテスト」
  「プレサブミッション」「提出前チェック」「PHPCS」「コーディングスタンダード」といったキーワードが出た場合に使用する。
  WooCommerceプラグインのCI/CDやテスト戦略の設計時にも積極的に参照すること。
---

# WooCommerce Marketplace QIT & Quality Assurance

QIT（Quality Insights Toolkit）はWooCommerceが開発したテストプラットフォームで、
マーケットプレイスへの提出と継続的なバージョンアップの両方で品質ゲートとして機能する。
全テストに通過しないと提出が先に進まないため、開発初期から組み込むことが重要。

---

## QIT テストスイート一覧

マーケットプレイスで要求される管理テスト:

| テスト | 内容 | 必須 |
|--------|------|------|
| **Activation Test** | クリーンなWP+WC環境でエラー/警告なくactivateできるか | ✅ |
| **Security Test** | Semgrepベースのセキュリティスキャン、コーディングベストプラクティス | ✅ |
| **PHPStan Test** | 静的解析でバグや型エラーを検出 | ✅ |
| **PHP Compatibility Test** | 異なるPHPバージョン（8.1–8.3+）での互換性 | ✅ |
| **Malware Test** | マルウェアコードの検出 | ✅ |
| **Woo E2E Test** | WooCommerce CoreのE2Eテスト（Playwright）を拡張有効状態で実行 | ✅ |
| **Woo API Test** | WooCommerce CoreのAPIテストを拡張有効状態で実行 | ✅ |
| **Custom E2E Test** | 開発者が作成するPlaywright E2Eテスト | 推奨 |

Activation / Security / Malware テストに通過しないとバージョンアップのデプロイもブロックされる。

---

## QIT CLI セットアップ

### インストール

```bash
composer require --dev woocommerce/qit-cli
```

### 認証

WooCommerce.com Partner Developer アカウントで認証する:

```bash
./vendor/bin/qit
# ブラウザが開き、WooCommerce.comでの認証フローが始まる
```

Vendor アカウントでログインすると、マーケットプレイスに提出済み/掲載中のプラグインに対して
QIT を実行できるようになる。

### 基本的なテスト実行

```bash
# Activation テスト
./vendor/bin/qit run:activation my-extension --zip=./my-extension.zip

# Security テスト
./vendor/bin/qit run:security my-extension --zip=./my-extension.zip

# PHPStan テスト
./vendor/bin/qit run:phpstan my-extension --zip=./my-extension.zip

# PHP Compatibility テスト
./vendor/bin/qit run:phpcompatibility my-extension --zip=./my-extension.zip

# Malware テスト
./vendor/bin/qit run:malware my-extension --zip=./my-extension.zip

# Woo E2E テスト（WooCommerce CoreのE2Eをプラグイン有効状態で実行）
./vendor/bin/qit run:e2e my-extension --zip=./my-extension.zip

# Woo API テスト
./vendor/bin/qit run:api my-extension --zip=./my-extension.zip
```

### 環境カスタマイズ

```bash
# PHP / WordPress / WooCommerce バージョンを指定
./vendor/bin/qit run:e2e my-extension \
  --zip=./my-extension.zip \
  --php_version=8.3 \
  --wordpress_version=6.7 \
  --woocommerce_version=9.6

# 他の拡張を同時に有効化してテスト（互換テスト）
./vendor/bin/qit run:activation my-extension \
  --zip=./my-extension.zip \
  --with-extension=woocommerce-subscriptions \
  --with-extension=woocommerce-payments
```

### Extension Sets による互換テスト

QIT にはマーケットプレイス上位拡張のセットが用意されており、
互換テストを簡単に実行できる:

```bash
./vendor/bin/qit run:e2e my-extension \
  --zip=./my-extension.zip \
  --test-package=woocommerce/checkout-tests
```

提出前チェックリストで求められる上位拡張との互換確認はこの機能で行う。

---

## ローカルテスト環境（認証不要）

QIT のローカルテスト環境は WooCommerce.com への接続なしで使用できる:

```bash
# ローカル環境でのテスト実行
./vendor/bin/qit run:e2e my-extension \
  --zip=./my-extension.zip \
  --local
```

開発中の素早いフィードバックループに有用。ただし、マーケットプレイスの
プレミアムプラグインをテスト環境にインストールするには認証が必要。

---

## カスタム E2E テスト（Playwright）

QIT は Playwright ベースのカスタムテストをサポートする。
テストパッケージとして標準化されたフォーマットに従うことで、
クロスプラグイン互換テストにも参加できる。

### テストの構成

```
tests/
├── e2e/
│   ├── playwright.config.ts
│   ├── example.spec.ts
│   └── utils/
│       └── helpers.ts
```

### playwright.config.ts の基本設定

```typescript
import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: '.',
  timeout: 60000,
  retries: 1,
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost:8889',
    storageState: process.env.STORAGE_STATE || undefined,
  },
  projects: [
    {
      name: 'chromium',
      use: { browserName: 'chromium' },
    },
  ],
});
```

### テスト例: 設定ページのテスト

```typescript
import { test, expect } from '@playwright/test';

test.describe( 'My Extension Settings', () => {
  test.beforeEach( async ( { page } ) => {
    // WP Admin にログイン
    await page.goto( '/wp-login.php' );
    await page.fill( '#user_login', 'admin' );
    await page.fill( '#user_pass', 'password' );
    await page.click( '#wp-submit' );
    await page.waitForURL( '**/wp-admin/**' );
  });

  test( 'settings page loads without errors', async ( { page } ) => {
    await page.goto( '/wp-admin/admin.php?page=wc-settings&tab=my_extension' );
    await expect( page.locator( '.woocommerce' ) ).toBeVisible();
    // コンソールエラーがないことを確認
    const errors: string[] = [];
    page.on( 'console', msg => {
      if ( msg.type() === 'error' ) errors.push( msg.text() );
    });
    await page.waitForTimeout( 2000 );
    expect( errors ).toHaveLength( 0 );
  });

  test( 'can save settings', async ( { page } ) => {
    await page.goto( '/wp-admin/admin.php?page=wc-settings&tab=my_extension' );
    await page.fill( '#my_extension_api_key', 'test-key-123' );
    await page.click( '.woocommerce-save-button' );
    await expect( page.locator( '.updated' ) ).toBeVisible();
    // 値が保持されているか確認
    const value = await page.inputValue( '#my_extension_api_key' );
    expect( value ).toBe( 'test-key-123' );
  });
});
```

### テスト例: チェックアウトフローのテスト

```typescript
test.describe( 'Checkout Integration', () => {
  test( 'extension feature works on block checkout', async ( { page } ) => {
    // 商品をカートに追加
    await page.goto( '/shop/' );
    await page.click( '.add_to_cart_button' );
    await page.waitForTimeout( 1000 );

    // ブロックチェックアウトに移動
    await page.goto( '/checkout/' );
    await expect( page.locator( '.wc-block-checkout' ) ).toBeVisible();

    // 拡張の要素が表示されているか
    await expect(
      page.locator( '[data-block-name="my-extension/checkout-field"]' )
    ).toBeVisible();
  });
});
```

### QIT でカスタムテストを実行

```bash
./vendor/bin/qit run:e2e my-extension \
  --zip=./my-extension.zip \
  --test-package=./tests/e2e
```

---

## コーディングスタンダード設定

### PHPCS（PHP CodeSniffer）

```json
// composer.json
{
  "require-dev": {
    "squizlabs/php_codesniffer": "^3.7",
    "wp-coding-standards/wpcs": "^3.0",
    "phpcompatibility/phpcompatibility-wp": "^2.1",
    "dealerdirect/phpcodesniffer-composer-installer": "^1.0"
  },
  "scripts": {
    "phpcs": "phpcs",
    "phpcbf": "phpcbf"
  }
}
```

```xml
<!-- phpcs.xml -->
<?xml version="1.0"?>
<ruleset name="My Extension">
  <description>PHPCS ruleset for WooCommerce Marketplace extension</description>

  <file>.</file>

  <exclude-pattern>vendor/*</exclude-pattern>
  <exclude-pattern>node_modules/*</exclude-pattern>
  <exclude-pattern>build/*</exclude-pattern>
  <exclude-pattern>tests/*</exclude-pattern>

  <arg name="extensions" value="php"/>
  <arg name="colors"/>
  <arg value="sp"/>

  <!-- WordPress Coding Standards -->
  <rule ref="WordPress-Extra">
    <exclude name="WordPress.Files.FileName.InvalidClassFileName"/>
    <exclude name="WordPress.Files.FileName.NotHyphenatedLowercase"/>
  </rule>

  <!-- WooCommerce-specific -->
  <rule ref="WordPress.WP.I18n">
    <properties>
      <property name="text_domain" type="array">
        <element value="my-extension"/>
      </property>
    </properties>
  </rule>

  <!-- PHP Compatibility -->
  <config name="testVersion" value="8.0-"/>
  <rule ref="PHPCompatibilityWP"/>

  <!-- Minimum WP version -->
  <config name="minimum_supported_wp_version" value="6.4"/>
</ruleset>
```

### PHPStan

```neon
# phpstan.neon
includes:
  - vendor/phpstan/phpstan/conf/bleedingEdge.neon

parameters:
  level: 5
  paths:
    - my-extension.php
    - includes/
  excludePaths:
    - vendor/
    - node_modules/
    - build/
  scanDirectories:
    - vendor/woocommerce/
  bootstrapFiles:
    - vendor/autoload.php
  ignoreErrors:
    # WooCommerce dynamic methods
    - '#Call to an undefined method WC_Order::#'
```

```json
// composer.json に追加
{
  "require-dev": {
    "phpstan/phpstan": "^1.10",
    "phpstan/extension-installer": "^1.3",
    "szepeviktor/phpstan-wordpress": "^1.3"
  },
  "scripts": {
    "phpstan": "phpstan analyse --memory-limit=512M"
  }
}
```

### ESLint（JavaScript / TypeScript）

```json
// .eslintrc.json
{
  "extends": [
    "plugin:@woocommerce/eslint-plugin/recommended"
  ],
  "env": {
    "browser": true,
    "es2021": true
  },
  "parserOptions": {
    "ecmaVersion": "latest",
    "sourceType": "module",
    "ecmaFeatures": {
      "jsx": true
    }
  },
  "settings": {
    "react": {
      "version": "detect"
    }
  }
}
```

```json
// package.json
{
  "devDependencies": {
    "@woocommerce/eslint-plugin": "^2.2.0",
    "@wordpress/scripts": "^28.0.0"
  },
  "scripts": {
    "lint:js": "wp-scripts lint-js src/",
    "lint:css": "wp-scripts lint-style src/**/*.scss",
    "build": "wp-scripts build",
    "start": "wp-scripts start"
  }
}
```

---

## GitHub Actions CI 統合

### 基本的な CI ワークフロー

```yaml
# .github/workflows/ci.yml
name: CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  phpcs:
    name: PHPCS
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer, cs2pr
      - run: composer install --no-progress
      - run: composer phpcs -- --report=checkstyle | cs2pr

  phpstan:
    name: PHPStan
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install --no-progress
      - run: composer phpstan

  eslint:
    name: ESLint
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
      - run: npm ci
      - run: npm run lint:js

  php-compatibility:
    name: PHP Compatibility
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.1', '8.2', '8.3']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - run: composer install --no-progress
      - run: |
          ./vendor/bin/phpcs \
            --standard=PHPCompatibilityWP \
            --runtime-set testVersion ${{ matrix.php }} \
            --extensions=php \
            --ignore=vendor/,node_modules/,build/ \
            .

  activation-test:
    name: Activation Test
    runs-on: ubuntu-latest
    strategy:
      matrix:
        wc: ['8.0', '9.0', 'latest']
        php: ['8.1', '8.3']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - run: npm ci && npm run build
      - name: Install wp-env
        run: npm -g install @wordpress/env
      - name: Configure wp-env
        run: |
          cat > .wp-env.override.json << 'EOF'
          {
            "plugins": ["."],
            "env": {
              "tests": {
                "phpVersion": "${{ matrix.php }}"
              }
            }
          }
          EOF
      - name: Start wp-env
        run: wp-env start
      - name: Verify activation
        run: |
          wp-env run tests-cli wp plugin activate my-extension
          wp-env run tests-cli wp plugin list --status=active --format=csv | grep my-extension
          # PHPエラーがないことを確認
          wp-env run tests-cli wp eval "error_reporting(E_ALL); do_action('admin_init');" 2>&1 | grep -v "^$" && echo "OK" || exit 1
```

### QIT を GitHub Actions に統合

```yaml
  qit-tests:
    name: QIT Tests
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install --no-progress
      - name: Build ZIP
        run: |
          npm ci && npm run build
          mkdir -p dist
          zip -r dist/my-extension.zip . \
            -x ".git/*" "node_modules/*" ".github/*" "tests/*" ".wp-env*"
      - name: Run QIT Activation
        env:
          QIT_TOKEN: ${{ secrets.QIT_TOKEN }}
        run: ./vendor/bin/qit run:activation my-extension --zip=dist/my-extension.zip
      - name: Run QIT Security
        env:
          QIT_TOKEN: ${{ secrets.QIT_TOKEN }}
        run: ./vendor/bin/qit run:security my-extension --zip=dist/my-extension.zip
```

---

## プレサブミッション品質チェックリスト

提出前に以下を全て確認する:

### 自動テスト
- [ ] PHPCS（WordPress-Extra）エラーゼロ
- [ ] PHPStan level 5 以上でエラーゼロ
- [ ] ESLint エラーゼロ
- [ ] PHP 8.1, 8.2, 8.3 で互換性テスト通過
- [ ] WP_DEBUG 有効でエラー/警告/noticeゼロ
- [ ] QIT Activation Test 通過（クリーンWP+WC環境）
- [ ] QIT Security Test 通過
- [ ] QIT Malware Test 通過
- [ ] QIT Woo E2E Test 通過（Core Critical Flows が壊れない）
- [ ] QIT Woo API Test 通過

### 互換テスト
- [ ] WooCommerce 最新安定版で動作確認
- [ ] WooCommerce 最新マイナー-1 で動作確認
- [ ] WordPress 最新安定版で動作確認
- [ ] マーケットプレイス上位拡張との共存テスト（Extension Sets）
- [ ] Block-based Cart/Checkout での動作確認
- [ ] Classic Cart/Checkout での動作確認（該当する場合）

### コード品質
- [ ] HPOS 互換宣言あり
- [ ] Blocks 互換宣言あり
- [ ] 全入力がサニタイズされている
- [ ] 全出力がエスケープされている
- [ ] Nonce 検証が全フォーム/AJAX に実装されている
- [ ] Capability チェックが全管理機能に実装されている
- [ ] テキストドメインがディレクトリ名と一致
- [ ] `Automattic\WooCommerce\Internal` 名前空間は未使用
- [ ] `changelog.txt` が正しいフォーマットで存在
- [ ] バージョン番号がヘッダーとchangelogで一致

### UX
- [ ] トップレベルメニュー未作成
- [ ] WP/WC 既存UIコンポーネント使用
- [ ] モバイルレスポンシブ
- [ ] セットアップフローが直感的
- [ ] 広告/バナー/ブランディングなし
