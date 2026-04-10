<?php
/**
 * Admin Plugin Name class file.
 *
 * @package Plugin_Name
 * @since 1.0.0
 */

namespace SAAI\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'SAAI\Admin\SAAI_Admin_Page' ) ) :

	/**
	 * Admin Plugin Class
	 */
	class SAAI_Admin_Page {

		/**
		 * Plugin options array.
		 *
		 * @var array
		 */
		private $options;

		/**
		 * Admin menu slug.
		 *
		 * @var string
		 */
		private $menu_slug;

		/**
		 * Constructor.
		 *
		 * @param array $options Plugin options array (page_title, menu_title).
		 * @since 1.0.0
		 */
		public function __construct( $options = array() ) {
			$this->options   = $options;
			$this->menu_slug = 'saai-overview';

			add_action( 'admin_enqueue_scripts', array( $this, 'saai_admin_register_scripts' ) );
			add_action( 'admin_menu', array( $this, 'register_saai_admin_overview_page' ) );
			add_filter( 'admin_body_class', array( $this, 'add_saai_admin_body_class' ) );
		}

		/**
		 * Register SAAI WC admin overview page.
		 *
		 * @since 1.0.0
		 */
		public function register_saai_admin_overview_page() {
			add_menu_page(
				$this->options['page_title'] ?? 'SAAI',
				$this->options['menu_title'] ?? 'SAAI',
				'manage_options',
				$this->menu_slug,
				array( $this, 'saai_admin_overview_page_callback' ),
				PLUGIN_NAME_URL . 'assets/images/saai_icon.svg',
				56
			);
		}

		/**
		 * Callback function for SAAI admin overview page.
		 *
		 * @since 1.0.0
		 */
		public function saai_admin_overview_page_callback() {
			echo '<div class="wrap"><div id="saai-overview-root"></div></div>';
		}

		/**
		 * Register and enqueue admin scripts and styles for SAAI admin pages.
		 *
		 * Only enqueues assets on the saai-overview screen to avoid conflicts
		 * with WooCommerce admin pages such as wc-admin.
		 *
		 * @since 1.0.0
		 */
		public function saai_admin_register_scripts() {
			$screen = get_current_screen();
			if ( ! $screen || 'toplevel_page_' . $this->menu_slug !== $screen->id ) {
				return;
			}

			$script_path       = PLUGIN_NAME_PATH . '/assets/build/saai/admin/overview.js';
			$script_asset_path = PLUGIN_NAME_PATH . '/assets/build/saai/admin/overview.asset.php';
			$script_asset      = file_exists( $script_asset_path )
				? require $script_asset_path
				: array(
					'dependencies' => array(),
					'version'      => file_exists( $script_path ) ? filemtime( $script_path ) : PLUGIN_NAME_VERSION,
				);
			$script_url        = PLUGIN_NAME_URL . 'assets/build/saai/admin/overview.js';

			wp_register_script(
				$this->menu_slug,
				$script_url,
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);

			$style_path = PLUGIN_NAME_PATH . '/assets/build/saai/admin/overview.css';
			wp_register_style(
				$this->menu_slug,
				PLUGIN_NAME_URL . 'assets/build/saai/admin/overview.css',
				array(),
				file_exists( $style_path ) ? filemtime( $style_path ) : PLUGIN_NAME_VERSION
			);

			wp_localize_script(
				$this->menu_slug,
				'saaiBlocksData',
				array(
					'wooPartnerLogoUrl' => PLUGIN_NAME_URL . 'assets/images/woo_partner_logo.png',
				)
			);

			wp_enqueue_script( $this->menu_slug );
			wp_enqueue_style( $this->menu_slug );
		}

		/**
		 * Add custom body class for SAAI admin pages.
		 *
		 * @param string $classes Existing admin body classes.
		 * @return string Modified admin body classes.
		 * @since 1.0.0
		 */
		public function add_saai_admin_body_class( $classes ) {
			$screen = get_current_screen();
			if ( isset( $screen->id ) && 'toplevel_page_' . $this->menu_slug === $screen->id ) {
				$classes .= ' saai-admin-page';
			}
			return $classes;
		}
	}
endif;
