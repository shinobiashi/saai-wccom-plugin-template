<?php
/**
 * Main class file for Plugin Name.
 *
 * @package Plugin_Name
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main Plugin Class
 */
class Plugin_Name {

	/**
	 * Instance of this class.
	 *
	 * @var Plugin_Name
	 */
	private static $instance;

	/**
	 * Get the instance of this class.
	 *
	 * @return Plugin_Name
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Initialize the plugin.
	 */
	private function init() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( is_admin() ) {
			new \SAAI\Admin\SAAI_Admin_Page(
				array(
					'page_title' => __( 'Plugin Name', 'plugin-name' ),
					'menu_title' => __( 'Plugin Name', 'plugin-name' ),
				)
			);
			\SAAI\Admin\SAAI_Admin_Plugin_Name::instance();
		}

		Plugin_Name_REST_API::instance();
		Plugin_Name_Core::instance();
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'plugin-name', false, dirname( plugin_basename( PLUGIN_NAME_PATH . '/plugin-name.php' ) ) . '/i18n' );
	}

	/**
	 * Plugin activation handler.
	 */
	public static function activate() {
		Plugin_Name_Core::create_tables();
		self::set_default_options();
	}

	/**
	 * Plugin deactivation handler.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'plugin_name_cron_hook' );
	}

	/**
	 * Set default plugin options on activation.
	 */
	private static function set_default_options() {
		$defaults = array(
			'plugin_name_setting_1' => 'default_value',
			'plugin_name_setting_2' => true,
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				update_option( $key, $value );
			}
		}
	}
}
