<?php
/**
 * Helper functions for V7 One-Click Duplicate.
 *
 * @package V7_One_Click_Duplicate
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get plugin default settings.
 *
 * @since 1.0.0
 * @return array Default settings.
 */
function v7_ocd_get_default_settings() {
	return array(
		'enabled_post_types'   => array( 'post', 'page' ),
		'default_status'       => 'draft',
		'title_suffix'         => __( '(Copy)', 'v7-one-click-duplicate' ),
		'redirect_after'       => 'list', // 'list' or 'edit'
		'duplicate_author'     => true,
		'duplicate_date'       => false,
		'duplicate_status'     => false,
		'duplicate_excerpt'    => true,
		'duplicate_content'    => true,
		'duplicate_thumbnail'  => true,
		'duplicate_taxonomies' => true,
		'duplicate_meta'       => true,
		'duplicate_menu_order' => true,
		'show_in_admin_bar'    => true,
		'show_in_gutenberg'    => false, // Optional feature
		'allowed_roles'        => array( 'administrator', 'editor' ),
	);
}

/**
 * Get plugin settings.
 *
 * @since 1.0.0
 * @return array Plugin settings.
 */
function v7_ocd_get_settings() {
	$defaults = v7_ocd_get_default_settings();
	$settings = get_option( 'v7_ocd_settings', array() );

	return wp_parse_args( $settings, $defaults );
}

/**
 * Get enabled post types for duplication.
 *
 * @since 1.0.0
 * @return array Enabled post types.
 */
function v7_ocd_get_enabled_post_types() {
	$settings = v7_ocd_get_settings();
	$enabled  = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array( 'post', 'page' );

	/**
	 * Filter enabled post types for duplication.
	 *
	 * @since 1.0.0
	 * @param array $enabled Enabled post types.
	 */
	return apply_filters( 'v7_ocd_enabled_post_types', $enabled );
}

/**
 * Get all public post types.
 *
 * @since 1.0.0
 * @return array Public post types.
 */
function v7_ocd_get_public_post_types() {
	$post_types = get_post_types(
		array(
			'public'   => true,
			'_builtin' => false,
		),
		'objects'
	);

	// Add built-in post types.
	$builtin = array(
		'post' => get_post_type_object( 'post' ),
		'page' => get_post_type_object( 'page' ),
	);

	$post_types = array_merge( $builtin, $post_types );

	// Remove unsupported post types.
	$excluded = array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache' );

	foreach ( $excluded as $exclude ) {
		unset( $post_types[ $exclude ] );
	}

	/**
	 * Filter available post types for duplication.
	 *
	 * @since 1.0.0
	 * @param array $post_types Available post types.
	 */
	return apply_filters( 'v7_ocd_available_post_types', $post_types );
}

/**
 * Check if duplication is enabled for a post type.
 *
 * @since 1.0.0
 * @param string $post_type Post type to check.
 * @return bool True if enabled, false otherwise.
 */
function v7_ocd_is_post_type_enabled( $post_type ) {
	$enabled = v7_ocd_get_enabled_post_types();
	return in_array( $post_type, $enabled, true );
}

/**
 * Get duplicate action URL.
 *
 * @since 1.0.0
 * @param int $post_id Post ID to duplicate.
 * @return string Action URL.
 */
function v7_ocd_get_duplicate_url( $post_id ) {
	$action_url = admin_url( 'admin.php' );
	$nonce      = wp_create_nonce( 'v7_ocd_duplicate_' . $post_id );

	return add_query_arg(
		array(
			'action'  => 'v7_ocd_duplicate',
			'post_id' => absint( $post_id ),
			'_nonce'  => $nonce,
		),
		$action_url
	);
}

/**
 * Sanitize settings before saving.
 *
 * @since 1.0.0
 * @param array $settings Settings to sanitize.
 * @return array Sanitized settings.
 */
function v7_ocd_sanitize_settings( $settings ) {
	$sanitized = array();

	// Sanitize enabled post types.
	if ( isset( $settings['enabled_post_types'] ) && is_array( $settings['enabled_post_types'] ) ) {
		$sanitized['enabled_post_types'] = array_map( 'sanitize_key', $settings['enabled_post_types'] );
	}

	// Sanitize default status.
	$allowed_statuses = array( 'draft', 'pending', 'private', 'publish' );
	if ( isset( $settings['default_status'] ) && in_array( $settings['default_status'], $allowed_statuses, true ) ) {
		$sanitized['default_status'] = $settings['default_status'];
	}

	// Sanitize title suffix.
	if ( isset( $settings['title_suffix'] ) ) {
		$sanitized['title_suffix'] = sanitize_text_field( $settings['title_suffix'] );
	}

	// Sanitize redirect option.
	$allowed_redirects = array( 'list', 'edit' );
	if ( isset( $settings['redirect_after'] ) && in_array( $settings['redirect_after'], $allowed_redirects, true ) ) {
		$sanitized['redirect_after'] = $settings['redirect_after'];
	}

	// Sanitize boolean options.
	$boolean_options = array(
		'duplicate_author',
		'duplicate_date',
		'duplicate_status',
		'duplicate_excerpt',
		'duplicate_content',
		'duplicate_thumbnail',
		'duplicate_taxonomies',
		'duplicate_meta',
		'duplicate_menu_order',
		'show_in_admin_bar',
		'show_in_gutenberg',
	);

	foreach ( $boolean_options as $option ) {
		$sanitized[ $option ] = isset( $settings[ $option ] ) && $settings[ $option ] ? true : false;
	}

	// Sanitize allowed roles.
	if ( isset( $settings['allowed_roles'] ) && is_array( $settings['allowed_roles'] ) ) {
		$sanitized['allowed_roles'] = array_map( 'sanitize_key', $settings['allowed_roles'] );
	}

	return $sanitized;
}
