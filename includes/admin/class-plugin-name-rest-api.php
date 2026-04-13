<?php
/**
 * REST API class for Plugin Name.
 *
 * @package Plugin_Name
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * REST API Class
 */
class Plugin_Name_REST_API {

	/**
	 * Single instance of the class.
	 *
	 * @var Plugin_Name_REST_API|null
	 */
	private static $instance = null;

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'plugin-name/v1';

	/**
	 * Get single instance of the class.
	 *
	 * @return Plugin_Name_REST_API
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// GET settings.
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// POST settings.
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_settings' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'settings' => array(
						'type'     => 'object',
						'required' => true,
					),
				),
			)
		);

		// GET items.
		register_rest_route(
			$this->namespace,
			'/items',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 20,
						'minimum' => 1,
						'maximum' => 100,
					),
					'status'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// GET summary.
		register_rest_route(
			$this->namespace,
			'/summary',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Check user permission.
	 *
	 * @return bool True if user has permission.
	 */
	public function check_permission() {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * GET /settings endpoint.
	 *
	 * @param WP_REST_Request $_request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_settings( $_request ) {
		$settings = array(
			'enabled'   => (bool) get_option( 'plugin_name_enabled', true ),
			'setting_1' => get_option( 'plugin_name_setting_1', 'default_value' ),
			'setting_2' => (bool) get_option( 'plugin_name_setting_2', true ),
		);

		return rest_ensure_response( array( 'settings' => $settings ) );
	}

	/**
	 * POST /settings endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function update_settings( $request ) {
		$params   = $request->get_json_params();
		$settings = isset( $params['settings'] ) ? $params['settings'] : $params;

		$allowed_settings = array(
			'enabled',
			'setting_1',
			'setting_2',
		);

		foreach ( $allowed_settings as $setting ) {
			if ( isset( $settings[ $setting ] ) ) {
				update_option( 'plugin_name_' . $setting, $settings[ $setting ] );
			}
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Settings updated successfully', 'plugin-name' ),
			)
		);
	}

	/**
	 * GET /items endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_items( $request ) {
		$page     = absint( $request->get_param( 'page' ) ?? 1 );
		$per_page = absint( $request->get_param( 'per_page' ) ?? 20 );
		$status   = sanitize_text_field( $request->get_param( 'status' ) ?? '' );

		$core  = Plugin_Name_Core::instance();
		$items = $core->get_items(
			array(
				'page'     => $page,
				'per_page' => $per_page,
				'status'   => '' !== $status ? $status : null,
			)
		);
		$total = $core->get_total_count( '' !== $status ? $status : null );

		return rest_ensure_response(
			array(
				'items'       => $items,
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / $per_page ),
			)
		);
	}

	/**
	 * GET /summary endpoint.
	 *
	 * Returns aggregate counts per status for the dashboard summary card.
	 *
	 * @param WP_REST_Request $_request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_summary( $_request ) {
		$core = Plugin_Name_Core::instance();

		return rest_ensure_response(
			array(
				'total'   => $core->get_total_count(),
				'pending' => $core->get_total_count( 'pending' ),
				'active'  => $core->get_total_count( 'active' ),
				'done'    => $core->get_total_count( 'done' ),
			)
		);
	}
}
