<?php
/**
 * Plugin uninstall handler.
 *
 * Removes all plugin data: database tables, options, and scheduled actions.
 *
 * @package Plugin_Name
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Drop plugin tables and delete options for a single site.
 *
 * @param int $blog_id Blog ID (used for multisite table prefix).
 */
function plugin_name_uninstall_site( $blog_id = 0 ) {
	global $wpdb;

	if ( $blog_id ) {
		switch_to_blog( $blog_id );
	}

	// Drop custom tables. Add plugin-specific table names here.
	$tables = array(
		$wpdb->prefix . 'plugin_name_items',
	);

	foreach ( $tables as $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	// Delete plugin options.
	delete_option( 'plugin_name_settings' );
	delete_option( 'plugin_name_enabled' );
	delete_option( 'plugin_name_setting_1' );
	delete_option( 'plugin_name_setting_2' );

	if ( $blog_id ) {
		restore_current_blog();
	}
}

// Cancel any scheduled cron events.
wp_clear_scheduled_hook( 'plugin_name_cron_hook' );

if ( is_multisite() ) {
	$plugin_name_blog_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $plugin_name_blog_ids as $plugin_name_blog_id ) {
		plugin_name_uninstall_site( (int) $plugin_name_blog_id );
	}
} else {
	plugin_name_uninstall_site();
}
