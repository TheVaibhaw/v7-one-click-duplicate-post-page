<?php
/**
 * Uninstall script for V7 One-Click Duplicate.
 *
 * Fired when the plugin is uninstalled.
 *
 * @package V7_One_Click_Duplicate
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data on uninstall.
 *
 * @since 1.0.0
 */
function v7_ocd_uninstall() {
	// Delete plugin settings.
	delete_option( 'v7_ocd_settings' );

	// For multisite, delete settings from all sites.
	if ( is_multisite() ) {
		global $wpdb;

		// Get all blog IDs.
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			delete_option( 'v7_ocd_settings' );
			restore_current_blog();
		}
	}

	// Clear any cached data.
	wp_cache_flush();
}

// Execute uninstall.
v7_ocd_uninstall();
