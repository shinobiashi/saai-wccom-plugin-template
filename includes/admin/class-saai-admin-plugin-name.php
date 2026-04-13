<?php
/**
 * SAAI Admin Plugin Name class file.
 *
 * @package Plugin_Name
 * @since 1.0.0
 */

namespace SAAI\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin Plugin Class
 */
class SAAI_Admin_Plugin_Name {

	/**
	 * Single instance of the class.
	 *
	 * @var SAAI_Admin_Plugin_Name|null
	 */
	private static $instance = null;

	/**
	 * Page hook suffix.
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Get single instance of the class.
	 *
	 * @return SAAI_Admin_Plugin_Name
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add submenu page under SAAI Overview.
	 */
	public function add_menu_page() {
		$this->page_hook = add_submenu_page(
			'saai-overview',
			__( 'Plugin Name', 'plugin-name' ),
			__( 'Plugin Name', 'plugin-name' ),
			'manage_woocommerce',
			'plugin-name',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( $hook !== $this->page_hook ) {
			return;
		}

		$asset_path = PLUGIN_NAME_PATH . '/assets/build/saai/admin/plugin-name.asset.php';
		$asset_file = file_exists( $asset_path )
			? require $asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( PLUGIN_NAME_PATH . '/assets/build/saai/admin/plugin-name.js' ),
			);

		wp_enqueue_script(
			'plugin-name-admin',
			PLUGIN_NAME_URL . 'assets/build/saai/admin/plugin-name.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		wp_enqueue_style(
			'plugin-name-admin',
			PLUGIN_NAME_URL . 'assets/build/saai/admin/plugin-name.css',
			array( 'wp-components' ),
			$asset_file['version']
		);

		wp_localize_script(
			'plugin-name-admin',
			'pluginNameData',
			array(
				'apiUrl'    => rest_url( 'plugin-name/v1' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'pluginUrl' => PLUGIN_NAME_URL,
			)
		);

		wp_set_script_translations(
			'plugin-name-admin',
			'plugin-name',
			PLUGIN_NAME_PATH . '/i18n'
		);
	}

	/**
	 * Render settings page container.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'plugin-name' ),
				esc_html__( 'Permission Denied', 'plugin-name' ),
				array( 'response' => 403 )
			);
		}

		echo '<div class="wrap">';
		echo '<div id="plugin-name-root"></div>';
		echo '</div>';
	}
}
