<?php
/**
 * Permissions handler for V7 One-Click Duplicate.
 *
 * @package V7_One_Click_Duplicate
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permissions class.
 *
 * @since 1.0.0
 */
class V7_OCD_Permissions {

	/**
	 * Check if current user can duplicate posts.
	 *
	 * @since 1.0.0
	 * @param int    $post_id   Post ID to duplicate.
	 * @param string $post_type Post type.
	 * @return bool True if user can duplicate, false otherwise.
	 */
	public static function can_duplicate( $post_id = 0, $post_type = '' ) {
		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Get post object if post ID provided.
		if ( $post_id > 0 ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				return false;
			}
			$post_type = $post->post_type;
		}

		// Check if post type is enabled.
		if ( ! v7_ocd_is_post_type_enabled( $post_type ) ) {
			return false;
		}

		// Get post type object.
		$post_type_obj = get_post_type_object( $post_type );
		if ( ! $post_type_obj ) {
			return false;
		}

		// Check edit capability for post type.
		$capability = $post_type_obj->cap->edit_posts;
		if ( ! current_user_can( $capability ) ) {
			return false;
		}

		// If specific post ID, check if user can edit that post.
		if ( $post_id > 0 ) {
			$edit_post_capability = $post_type_obj->cap->edit_post;
			if ( ! current_user_can( $edit_post_capability, $post_id ) ) {
				return false;
			}
		}

		// Check role-based permissions from settings.
		if ( ! self::is_role_allowed() ) {
			return false;
		}

		/**
		 * Filter whether user can duplicate.
		 *
		 * @since 1.0.0
		 * @param bool   $can_duplicate Whether user can duplicate.
		 * @param int    $post_id       Post ID.
		 * @param string $post_type     Post type.
		 */
		return apply_filters( 'v7_ocd_user_can_duplicate', true, $post_id, $post_type );
	}

	/**
	 * Check if current user's role is allowed to duplicate.
	 *
	 * @since 1.0.0
	 * @return bool True if role is allowed, false otherwise.
	 */
	public static function is_role_allowed() {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		// Super admins always allowed.
		if ( is_super_admin() ) {
			return true;
		}

		$settings      = v7_ocd_get_settings();
		$allowed_roles = isset( $settings['allowed_roles'] ) ? $settings['allowed_roles'] : array( 'administrator', 'editor' );

		// Check if user has any of the allowed roles.
		$user_roles = (array) $user->roles;
		$intersect  = array_intersect( $user_roles, $allowed_roles );

		return ! empty( $intersect );
	}

	/**
	 * Verify nonce for duplication action.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $nonce   Nonce to verify.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	public static function verify_nonce( $post_id, $nonce ) {
		return wp_verify_nonce( $nonce, 'v7_ocd_duplicate_' . $post_id );
	}

	/**
	 * Verify AJAX nonce for duplication action.
	 *
	 * @since 1.0.0
	 * @param string $nonce Nonce to verify.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	public static function verify_ajax_nonce( $nonce ) {
		return wp_verify_nonce( $nonce, 'v7_ocd_ajax_nonce' );
	}
}
