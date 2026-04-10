---
name: wp-security-check
description: "Use for security audits of WordPress plugins: nonce validation, capability checks, input sanitization, output escaping, SQL injection prevention, CSRF protection, PCI DSS compliance for payment plugins, and WooCommerce-specific security patterns."
compatibility: "Targets WordPress 6.4+ / WooCommerce 9.0+ (PHP 8.1+). Requires PHPCS with WordPress standards."
---

# WP Security Check

## When to use

このプラグインのセキュリティ監査を実施:

- フォーム送信・AJAX ハンドラのノンス検証漏れ
- 権限チェック（`current_user_can`）不足
- 入力値のサニタイズ漏れ
- 出力エスケープ漏れ（XSS）
- SQL インジェクション（`$wpdb->prepare()` 未使用）
- CSRF 対策
- 決済プラグインの PCI DSS 要件（カード番号のログ・保存禁止）
- WooCommerce HPOS 対応（`get_post_meta` 直接使用禁止）
- REST API エンドポイントの `permission_callback` 設定

## Inputs required

- プラグインのルートパス
- 監査対象のスコープ（全体 or 特定ファイル）
- 決済処理が含まれるか（PCI DSS スコープ確認）

## Procedure

### 0) 静的解析で全体像を掴む

```bash
# PHPCS でセキュリティ関連の警告を出力
composer phpcs 2>&1 | grep -E "(nonce|sanitize|escape|wpdb|direct)" | head -50

# PHPStan で型安全性チェック
composer phpstan 2>&1 | head -50
```

### 1) 入力サニタイズの確認

すべての `$_POST`, `$_GET`, `$_REQUEST`, `$_COOKIE` アクセスをチェック:

```bash
grep -r '\$_POST\|\$_GET\|\$_REQUEST\|\$_COOKIE' includes/ src/ --include="*.php" -n
```

**必須パターン:**
- `sanitize_text_field( wp_unslash( $_POST['key'] ) )`
- `absint( $_POST['id'] )`
- `wc_clean( wp_unslash( $_POST['field'] ) )` (WooCommerce)
- `sanitize_email()`, `esc_url_raw()`, `wp_kses_post()`

参考: `references/sanitization.md`

### 2) 出力エスケープの確認

HTML 出力箇所をすべてチェック:

```bash
grep -r 'echo \|<\?=' includes/ src/ templates/ --include="*.php" -n | grep -v 'esc_\|wp_kses'
```

**必須パターン:**
- `esc_html()` — テキスト出力
- `esc_attr()` — HTML 属性値
- `esc_url()` — URL 出力
- `esc_js()` — JS 文字列内
- `wp_kses_post()` — HTML 許可が必要な場合

参考: `references/escaping.md`

### 3) ノンス検証の確認

フォーム送信・AJAX ハンドラのすべてのアクションをチェック:

```bash
grep -r 'wp_ajax_\|admin_post_\|woocommerce_checkout_process' includes/ --include="*.php" -n
```

各ハンドラに以下があること:

```php
// フォーム送信
check_admin_referer( 'action_name', 'nonce_field' );

// AJAX
check_ajax_referer( 'action_name', 'nonce_field' );

// REST API は自動処理（cookie auth）だが permission_callback 必須
```

参考: `references/nonces-and-csrf.md`

### 4) 権限チェックの確認

管理操作・データ変更前の `current_user_can()` 確認:

```bash
grep -r 'update_option\|delete_option\|$wpdb->insert\|$wpdb->update\|$wpdb->delete' includes/ --include="*.php" -n
```

- 管理操作: `current_user_can( 'manage_options' )` または `manage_woocommerce`
- REST API: `permission_callback` で必ず確認（`__return_true` は公開 GET のみ許可）
- 注文データ: `current_user_can( 'edit_shop_orders' )`

### 5) SQL インジェクション対策

```bash
grep -r '$wpdb->' includes/ --include="*.php" -n | grep -v 'prepare\|get_charset_collate\|prefix'
```

すべての変数を含むクエリに `$wpdb->prepare()` が使われていること:

```php
// NG
$wpdb->get_results( "SELECT * FROM {$table} WHERE id = {$id}" );

// OK
$wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
```

参考: `references/sql-safety.md`

### 6) REST API セキュリティ

```bash
grep -r 'register_rest_route' includes/ src/ --include="*.php" -n
```

各ルートに `permission_callback` が設定されていること:

```php
// NG — セキュリティホール
'permission_callback' => '__return_true',  // 書き込み系では絶対NG

// OK
'permission_callback' => function() {
    return current_user_can( 'manage_woocommerce' );
},
```

参考: `references/rest-api-security.md`

### 7) 決済・PCI DSS チェック（決済プラグインのみ）

```bash
grep -r 'card\|credit\|cvv\|cvc\|expiry\|pan\|log\|error_log\|WC_Logger' includes/ --include="*.php" -n
```

- カード番号・CVV を絶対にログ・DBに保存しない
- JS 側でカードデータを直接決済ゲートウェイに送信（トークン化）
- Webhook 署名の検証を必ず実装
- IP 制限または HMAC 署名で webhook を保護

参考: `references/payment-security.md`

### 8) WooCommerce HPOS 対応チェック

```bash
grep -r 'get_post_meta\|update_post_meta\|delete_post_meta\|get_post(' includes/ --include="*.php" -n
```

注文関連で `get_post_meta()` が使われていないこと。代わりに:

```php
$order = wc_get_order( $order_id );
$value = $order->get_meta( '_meta_key' );
$order->update_meta_data( '_meta_key', $value );
$order->save();
```

### 9) ファイルアップロードセキュリティ（該当する場合）

```bash
grep -r 'wp_handle_upload\|move_uploaded_file\|$_FILES' includes/ --include="*.php" -n
```

- `wp_check_filetype_and_ext()` で MIME タイプ検証
- アップロード先は `wp_upload_dir()` を使用
- ファイル名は `sanitize_file_name()` で処理

## Verification

セキュリティチェック通過基準:

- [ ] PHPCS を実行してセキュリティ警告 0 件
- [ ] 全フォーム送信にノンス + 権限チェック
- [ ] 全 AJAX ハンドラにノンス + 権限チェック
- [ ] 全出力に適切なエスケープ
- [ ] 全入力に適切なサニタイズ
- [ ] 全 `$wpdb` クエリに `prepare()` 使用（変数含む場合）
- [ ] 全 REST ルートに `permission_callback`
- [ ] HPOS: 注文メタに `get_post_meta()` 未使用
- [ ] （決済のみ）カードデータをログ・保存していない

## References

- `references/sanitization.md` — 入力サニタイズパターン
- `references/escaping.md` — 出力エスケープパターン
- `references/nonces-and-csrf.md` — ノンスと CSRF 対策
- `references/sql-safety.md` — SQL インジェクション対策
- `references/rest-api-security.md` — REST API 権限制御
- `references/payment-security.md` — 決済プラグインの PCI DSS 要件
