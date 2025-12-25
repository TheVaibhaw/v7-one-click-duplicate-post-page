<?php
/**
 * Core duplication logic for V7 One-Click Duplicate.
 *
 * @package V7_One_Click_Duplicate
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Duplicator class.
 *
 * @since 1.0.0
 */
class V7_OCD_Duplicator {

	/**
	 * Duplicate a post.
	 *
	 * @since 1.0.0
	 * @param int   $post_id  Post ID to duplicate.
	 * @param array $override Optional. Override settings for duplication.
	 * @return int|WP_Error New post ID on success, WP_Error on failure.
	 */
	public static function duplicate_post( $post_id, $override = array() ) {
		// Get original post.
		$original_post = get_post( $post_id );

		if ( ! $original_post || 'auto-draft' === $original_post->post_status ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post ID.', 'v7-one-click-duplicate' ) );
		}

		// Check permissions.
		if ( ! V7_OCD_Permissions::can_duplicate( $post_id, $original_post->post_type ) ) {
			return new WP_Error( 'permission_denied', __( 'You do not have permission to duplicate this post.', 'v7-one-click-duplicate' ) );
		}

		// Prevent duplication of revisions.
		if ( 'revision' === $original_post->post_type ) {
			return new WP_Error( 'invalid_post_type', __( 'Cannot duplicate revisions.', 'v7-one-click-duplicate' ) );
		}

		// Get settings.
		$settings = v7_ocd_get_settings();
		$settings = wp_parse_args( $override, $settings );

		/**
		 * Fires before post duplication.
		 *
		 * @since 1.0.0
		 * @param int      $post_id       Original post ID.
		 * @param WP_Post  $original_post Original post object.
		 * @param array    $settings      Duplication settings.
		 */
		do_action( 'v7_ocd_before_duplicate', $post_id, $original_post, $settings );

		// Prepare new post data.
		$new_post_data = self::prepare_post_data( $original_post, $settings );

		// Insert the new post.
		$new_post_id = wp_insert_post( $new_post_data, true );

		if ( is_wp_error( $new_post_id ) ) {
			return $new_post_id;
		}

		// Duplicate post meta.
		if ( $settings['duplicate_meta'] ) {
			self::duplicate_post_meta( $post_id, $new_post_id );
		}

		// Duplicate taxonomies.
		if ( $settings['duplicate_taxonomies'] ) {
			self::duplicate_taxonomies( $post_id, $new_post_id, $original_post->post_type );
		}

		// Duplicate featured image.
		if ( $settings['duplicate_thumbnail'] ) {
			self::duplicate_thumbnail( $post_id, $new_post_id );
		}

		// Duplicate menu order.
		if ( $settings['duplicate_menu_order'] ) {
			self::duplicate_menu_order( $post_id, $new_post_id );
		}

		/**
		 * Fires after successful post duplication.
		 *
		 * @since 1.0.0
		 * @param int     $new_post_id    New post ID.
		 * @param int     $post_id        Original post ID.
		 * @param WP_Post $original_post  Original post object.
		 * @param array   $settings       Duplication settings.
		 */
		do_action( 'v7_ocd_after_duplicate', $new_post_id, $post_id, $original_post, $settings );

		return $new_post_id;
	}

	/**
	 * Prepare new post data from original post.
	 *
	 * @since 1.0.0
	 * @param WP_Post $original_post Original post object.
	 * @param array   $settings      Duplication settings.
	 * @return array New post data.
	 */
	private static function prepare_post_data( $original_post, $settings ) {
		$current_user = wp_get_current_user();
		$suffix       = isset( $settings['title_suffix'] ) ? $settings['title_suffix'] : __( '(Copy)', 'v7-one-click-duplicate' );

		$new_post_data = array(
			'post_title'     => $original_post->post_title . ' ' . $suffix,
			'post_type'      => $original_post->post_type,
			'post_status'    => $settings['default_status'],
			'comment_status' => $original_post->comment_status,
			'ping_status'    => $original_post->ping_status,
			'post_parent'    => $original_post->post_parent,
			'menu_order'     => $original_post->menu_order,
			'post_password'  => $original_post->post_password,
		);

		// Content.
		if ( $settings['duplicate_content'] ) {
			$new_post_data['post_content'] = $original_post->post_content;
		}

		// Excerpt.
		if ( $settings['duplicate_excerpt'] ) {
			$new_post_data['post_excerpt'] = $original_post->post_excerpt;
		}

		// Author.
		if ( $settings['duplicate_author'] ) {
			$new_post_data['post_author'] = $original_post->post_author;
		} else {
			$new_post_data['post_author'] = $current_user->ID;
		}

		// Date.
		if ( $settings['duplicate_date'] ) {
			$new_post_data['post_date']     = $original_post->post_date;
			$new_post_data['post_date_gmt'] = $original_post->post_date_gmt;
		}

		/**
		 * Filter new post data before insertion.
		 *
		 * @since 1.0.0
		 * @param array   $new_post_data  New post data.
		 * @param WP_Post $original_post  Original post object.
		 * @param array   $settings       Duplication settings.
		 */
		return apply_filters( 'v7_ocd_new_post_data', $new_post_data, $original_post, $settings );
	}

	/**
	 * Duplicate post meta.
	 *
	 * @since 1.0.0
	 * @param int $original_post_id Original post ID.
	 * @param int $new_post_id      New post ID.
	 */
	private static function duplicate_post_meta( $original_post_id, $new_post_id ) {
		global $wpdb;

		// Get all meta for original post.
		$post_meta = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d",
				$original_post_id
			)
		);

		if ( empty( $post_meta ) ) {
			return;
		}

		// Meta keys to exclude from duplication.
		$excluded_meta = array(
			'_edit_lock',
			'_edit_last',
			'_wp_old_slug',
			'_wp_old_date',
		);

		/**
		 * Filter excluded meta keys.
		 *
		 * @since 1.0.0
		 * @param array $excluded_meta Excluded meta keys.
		 */
		$excluded_meta = apply_filters( 'v7_ocd_excluded_meta_keys', $excluded_meta );

		// Duplicate each meta.
		foreach ( $post_meta as $meta ) {
			// Skip excluded meta.
			if ( in_array( $meta->meta_key, $excluded_meta, true ) ) {
				continue;
			}

			// Add meta to new post.
			add_post_meta( $new_post_id, $meta->meta_key, maybe_unserialize( $meta->meta_value ) );
		}
	}

	/**
	 * Duplicate taxonomies.
	 *
	 * @since 1.0.0
	 * @param int    $original_post_id Original post ID.
	 * @param int    $new_post_id      New post ID.
	 * @param string $post_type        Post type.
	 */
	private static function duplicate_taxonomies( $original_post_id, $new_post_id, $post_type ) {
		// Get all taxonomies for post type.
		$taxonomies = get_object_taxonomies( $post_type, 'names' );

		if ( empty( $taxonomies ) ) {
			return;
		}

		foreach ( $taxonomies as $taxonomy ) {
			// Skip post formats.
			if ( 'post_format' === $taxonomy ) {
				continue;
			}

			// Get terms from original post.
			$terms = wp_get_post_terms( $original_post_id, $taxonomy, array( 'fields' => 'ids' ) );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			// Set terms to new post.
			wp_set_object_terms( $new_post_id, $terms, $taxonomy );
		}
	}

	/**
	 * Duplicate featured image.
	 *
	 * @since 1.0.0
	 * @param int $original_post_id Original post ID.
	 * @param int $new_post_id      New post ID.
	 */
	private static function duplicate_thumbnail( $original_post_id, $new_post_id ) {
		$thumbnail_id = get_post_thumbnail_id( $original_post_id );

		if ( $thumbnail_id ) {
			set_post_thumbnail( $new_post_id, $thumbnail_id );
		}
	}

	/**
	 * Duplicate menu order and page attributes.
	 *
	 * @since 1.0.0
	 * @param int $original_post_id Original post ID.
	 * @param int $new_post_id      New post ID.
	 */
	private static function duplicate_menu_order( $original_post_id, $new_post_id ) {
		$original_post = get_post( $original_post_id );

		if ( $original_post && isset( $original_post->menu_order ) ) {
			wp_update_post(
				array(
					'ID'         => $new_post_id,
					'menu_order' => $original_post->menu_order,
				)
			);
		}
	}

	/**
	 * Bulk duplicate posts.
	 *
	 * @since 1.0.0
	 * @param array $post_ids Array of post IDs to duplicate.
	 * @return array Array of results with post IDs and status.
	 */
	public static function bulk_duplicate( $post_ids ) {
		$results = array();

		if ( ! is_array( $post_ids ) || empty( $post_ids ) ) {
			return $results;
		}

		foreach ( $post_ids as $post_id ) {
			$new_post_id = self::duplicate_post( absint( $post_id ) );

			$results[ $post_id ] = array(
				'success'     => ! is_wp_error( $new_post_id ),
				'new_post_id' => is_wp_error( $new_post_id ) ? 0 : $new_post_id,
				'message'     => is_wp_error( $new_post_id ) ? $new_post_id->get_error_message() : __( 'Post duplicated successfully.', 'v7-one-click-duplicate' ),
			);
		}

		return $results;
	}
}
