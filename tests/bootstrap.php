<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Plugin_Name
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 *
 * WooCommerce must be loaded before this plugin.
 * When using wp-env, WooCommerce is installed as a regular plugin and
 * available in the test environment automatically.
 * For standalone PHPUnit runs, set WC_PLUGIN_DIR or install WooCommerce
 * via Composer (woocommerce/woocommerce) and adjust the path below.
 */
function _manually_load_plugin() {
	$wc_plugin = defined( 'WC_PLUGIN_DIR' )
		? WC_PLUGIN_DIR . '/woocommerce.php'
		: dirname( __DIR__ ) . '/vendor/woocommerce/woocommerce/woocommerce.php';

	if ( file_exists( $wc_plugin ) ) {
		require $wc_plugin;
	}

	require dirname( __DIR__ ) . '/plugin-name.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
