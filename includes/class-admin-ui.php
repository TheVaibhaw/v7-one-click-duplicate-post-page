<?php
/**
 * Admin UI integrations for V7 One-Click Duplicate.
 *
 * @package V7_One_Click_Duplicate
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI class.
 *
 * @since 1.0.0
 */
class V7_OCD_Admin_UI {

	/**
	 * Instance of this class.
	 *
	 * @var V7_OCD_Admin_UI
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @since 1.0.0
	 * @return V7_OCD_Admin_UI
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_filter( 'post_row_actions', array( $this, 'add_row_action' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'add_row_action' ), 10, 2 );
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_link' ), 100 );
		add_action( 'admin_action_v7_ocd_duplicate', array( $this, 'handle_duplicate_action' ) );
		add_filter( 'bulk_actions-edit-post', array( $this, 'add_bulk_action' ) );
		add_filter( 'bulk_actions-edit-page', array( $this, 'add_bulk_action' ) );
		add_filter( 'handle_bulk_actions-edit-post', array( $this, 'handle_bulk_action' ), 10, 3 );
		add_filter( 'handle_bulk_actions-edit-page', array( $this, 'handle_bulk_action' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_v7_ocd_duplicate_ajax', array( $this, 'ajax_duplicate' ) );
		$this->register_dynamic_post_type_hooks();
	}

	/**
	 * Register hooks for custom post types.
	 *
	 * @since 1.0.0
	 */
	private function register_dynamic_post_type_hooks() {
		$post_types = v7_ocd_get_enabled_post_types();
		foreach ( $post_types as $post_type ) {
			if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
				add_filter( "{$post_type}_row_actions", array( $this, 'add_row_action' ), 10, 2 );
				add_filter( "bulk_actions-edit-{$post_type}", array( $this, 'add_bulk_action' ) );
				add_filter( "handle_bulk_actions-edit-{$post_type}", array( $this, 'handle_bulk_action' ), 10, 3 );
			}
		}
	}

	/**
	 * Add duplicate link to row actions.
	 *
	 * @since 1.0.0
	 * @param array   $actions Row actions.
	 * @param WP_Post $post    Post object.
	 * @return array Modified actions.
	 */
	public function add_row_action( $actions, $post ) {
		if ( ! V7_OCD_Permissions::can_duplicate( $post->ID, $post->post_type ) ) {
			return $actions;
		}

		if ( 'auto-draft' === $post->post_status || 'revision' === $post->post_type ) {
			return $actions;
		}

		$duplicate_url = v7_ocd_get_duplicate_url( $post->ID );

		$actions['v7_duplicate'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $duplicate_url ),
			esc_attr( sprintf( __( 'Duplicate &#8220;%s&#8221;', 'v7-one-click-duplicate' ), $post->post_title ) ),
			esc_html__( 'Duplicate', 'v7-one-click-duplicate' )
		);

		return $actions;
	}

	/**
	 * Add duplicate link to admin bar.
	 *
	 * @since 1.0.0
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function add_admin_bar_link( $wp_admin_bar ) {
		if ( ! is_admin() ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}

		$settings = v7_ocd_get_settings();
		if ( ! $settings['show_in_admin_bar'] ) {
			return;
		}

		global $post;
		if ( ! $post || ! V7_OCD_Permissions::can_duplicate( $post->ID, $post->post_type ) ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'v7-duplicate-post',
				'title' => esc_html__( 'Duplicate This', 'v7-one-click-duplicate' ),
				'href'  => esc_url( v7_ocd_get_duplicate_url( $post->ID ) ),
			)
		);
	}

	/**
	 * Handle duplicate action.
	 *
	 * @since 1.0.0
	 */
	public function handle_duplicate_action() {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_die( esc_html__( 'Invalid post ID.', 'v7-one-click-duplicate' ) );
		}

		$nonce = isset( $_GET['_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_nonce'] ) ) : '';
		if ( ! V7_OCD_Permissions::verify_nonce( $post_id, $nonce ) ) {
			wp_die( esc_html__( 'Security check failed.', 'v7-one-click-duplicate' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( esc_html__( 'Post not found.', 'v7-one-click-duplicate' ) );
		}

		$new_post_id = V7_OCD_Duplicator::duplicate_post( $post_id );

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( esc_html( $new_post_id->get_error_message() ) );
		}

		$settings = v7_ocd_get_settings();
		$redirect_type = isset( $settings['redirect_after'] ) ? $settings['redirect_after'] : 'list';

		if ( 'edit' === $redirect_type ) {
			$redirect_url = get_edit_post_link( $new_post_id, 'redirect' );
		} else {
			$redirect_url = admin_url( 'edit.php?post_type=' . $post->post_type );
			$redirect_url = add_query_arg( 'v7_ocd_duplicated', $new_post_id, $redirect_url );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Add bulk duplicate action.
	 *
	 * @since 1.0.0
	 * @param array $actions Bulk actions.
	 * @return array Modified actions.
	 */
	public function add_bulk_action( $actions ) {
		$actions['v7_duplicate'] = __( 'Duplicate', 'v7-one-click-duplicate' );
		return $actions;
	}

	/**
	 * Handle bulk duplicate action.
	 *
	 * @since 1.0.0
	 * @param string $redirect_url Redirect URL.
	 * @param string $action       Action name.
	 * @param array  $post_ids     Post IDs.
	 * @return string Modified redirect URL.
	 */
	public function handle_bulk_action( $redirect_url, $action, $post_ids ) {
		if ( 'v7_duplicate' !== $action ) {
			return $redirect_url;
		}

		$results = V7_OCD_Duplicator::bulk_duplicate( $post_ids );
		$success_count = 0;
		foreach ( $results as $result ) {
			if ( $result['success'] ) {
				$success_count++;
			}
		}

		return add_query_arg( 'v7_ocd_bulk_duplicated', $success_count, $redirect_url );
	}

	/**
	 * Display admin notices.
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {
		if ( isset( $_GET['v7_ocd_duplicated'] ) ) {
			$new_post_id = absint( $_GET['v7_ocd_duplicated'] );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
				esc_html__( 'Post duplicated successfully.', 'v7-one-click-duplicate' ),
				esc_url( get_edit_post_link( $new_post_id ) ),
				esc_html__( 'Edit duplicated post', 'v7-one-click-duplicate' )
			);
		}

		if ( isset( $_GET['v7_ocd_bulk_duplicated'] ) ) {
			$count = absint( $_GET['v7_ocd_bulk_duplicated'] );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sprintf( _n( '%d post duplicated.', '%d posts duplicated.', $count, 'v7-one-click-duplicate' ), $count ) )
			);
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'edit.php', 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'v7-ocd-admin',
			V7_OCD_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			V7_OCD_VERSION
		);

		wp_enqueue_script(
			'v7-ocd-admin',
			V7_OCD_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			V7_OCD_VERSION,
			true
		);

		wp_localize_script(
			'v7-ocd-admin',
			'v7OcdData',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'v7_ocd_ajax_nonce' ),
				'duplicating' => __( 'Duplicating...', 'v7-one-click-duplicate' ),
				'duplicated'  => __( 'Duplicated!', 'v7-one-click-duplicate' ),
				'error'       => __( 'Error occurred.', 'v7-one-click-duplicate' ),
			)
		);
	}

	/**
	 * Handle AJAX duplication.
	 *
	 * @since 1.0.0
	 */
	public function ajax_duplicate() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! V7_OCD_Permissions::verify_ajax_nonce( $nonce ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'v7-one-click-duplicate' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'v7-one-click-duplicate' ) ) );
		}

		$new_post_id = V7_OCD_Duplicator::duplicate_post( $post_id );

		if ( is_wp_error( $new_post_id ) ) {
			wp_send_json_error( array( 'message' => $new_post_id->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'     => __( 'Post duplicated successfully.', 'v7-one-click-duplicate' ),
				'new_post_id' => $new_post_id,
				'edit_link'   => get_edit_post_link( $new_post_id, 'raw' ),
			)
		);
	}
}
