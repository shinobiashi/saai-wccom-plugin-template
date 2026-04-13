<?php
/**
 * Core class file for Plugin Name.
 *
 * Add core plugin business logic here.
 *
 * @package Plugin_Name
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Core Plugin Class
 */
class Plugin_Name_Core {

	/**
	 * Instance of this class.
	 *
	 * @var Plugin_Name_Core|null
	 */
	private static $instance = null;

	/**
	 * Get the instance of this class.
	 *
	 * @return Plugin_Name_Core
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Initialize hooks.
	 */
	private function init() {
		// Add WooCommerce hooks here.
	}

	/**
	 * Create required database tables on activation.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Example table — rename and modify for your plugin.
		$table_name = $wpdb->prefix . 'plugin_name_items';
		$sql        = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'pending',
			data longtext,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY order_id (order_id),
			KEY status (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get items from database.
	 *
	 * @param array $args Query arguments.
	 * @return array Array of items.
	 */
	public function get_items( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'plugin_name_items';

		$defaults = array(
			'per_page' => 20,
			'page'     => 1,
			'status'   => null,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);

		$args   = wp_parse_args( $args, $defaults );
		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		$where        = '1=1';
		$where_values = array();

		if ( $args['status'] ) {
			$where         .= ' AND status = %s';
			$where_values[] = $args['status'];
		}

		$orderby = in_array( $args['orderby'], array( 'created_at', 'updated_at', 'order_id', 'status' ), true )
			? $args['orderby']
			: 'created_at';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		if ( ! empty( $where_values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$where = $wpdb->prepare( $where, $where_values );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $query, $args['per_page'], $offset ), ARRAY_A );
	}

	/**
	 * Get total item count.
	 *
	 * @param string|null $status Optional status filter.
	 * @return int Total count.
	 */
	public function get_total_count( $status = null ) {
		global $wpdb;
		$table     = $wpdb->prefix . 'plugin_name_items';
		$cache_key = 'plugin_name_count_' . ( $status ?? 'all' );
		$count     = wp_cache_get( $cache_key, 'plugin_name' );

		if ( false === $count ) {
			if ( $status ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status ) );
			} else {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
			}
			wp_cache_set( $cache_key, (int) $count, 'plugin_name', 300 );
		}

		return (int) $count;
	}
}
