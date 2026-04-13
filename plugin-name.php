<?php
/**
 * Plugin Name: Plugin Name
 * Plugin URI: https://woocommerce.com/products/plugin-name/
 * Description: A brief description of this plugin.
 * Version: 1.0.0
 * Author: Shohei Tanaka
 * Author URI: https://shinobiashi.ai/
 * License: GPL-3.0-or-later
 * Text Domain: plugin-name
 * Domain Path: /i18n
 * Requires at least: 6.7
 * Tested up to: 6.9
 * Requires PHP: 8.2
 * WC requires at least: 9.0
 * WC tested up to: 10.6.2
 *
 * Woo: XXXXX:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
 *
 * @package Plugin_Name
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC Detection
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	function is_woocommerce_active() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}
}

if ( is_woocommerce_active() ) {

	if ( ! defined( 'PLUGIN_NAME_PATH' ) ) {
		define( 'PLUGIN_NAME_PATH', __DIR__ );
		define( 'PLUGIN_NAME_URL', plugins_url( '/', __FILE__ ) );
		define( 'PLUGIN_NAME_VERSION', '1.0.0' );
	}

	// Load includes in dependency order.
	$plugin_name_includes = array(
		'/includes/saai_framework/class-saai-admin-page.php',
		'/includes/saai_framework/class-saai-wc-user.php',
		'/includes/admin/class-saai-admin-plugin-name.php',
		'/includes/class-plugin-name.php',
		'/includes/admin/class-plugin-name-rest-api.php',
		'/includes/class-plugin-name-core.php',
	);

	foreach ( $plugin_name_includes as $plugin_name_file ) {
		$plugin_name_file_path = __DIR__ . $plugin_name_file;
		if ( file_exists( $plugin_name_file_path ) ) {
			require_once $plugin_name_file_path;
		}
	}

	register_activation_hook( __FILE__, array( 'Plugin_Name', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'Plugin_Name', 'deactivate' ) );

	add_action( 'plugins_loaded', 'plugin_name_init', 10 );

	/**
	 * Initialize the plugin.
	 */
	function plugin_name_init() {
		Plugin_Name::instance();
	}

	/**
	 * Declare plugin compatibility with WooCommerce features.
	 */
	add_action(
		'before_woocommerce_init',
		function () {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
			}
		}
	);
}
