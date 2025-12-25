<?php
/**
 * Settings page for V7 One-Click Duplicate.
 *
 * @package V7_One_Click_Duplicate
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 *
 * @since 1.0.0
 */
class V7_OCD_Settings {

	/**
	 * Instance of this class.
	 *
	 * @var V7_OCD_Settings
	 */
	private static $instance = null;

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'v7-ocd-settings';

	/**
	 * Get instance.
	 *
	 * @since 1.0.0
	 * @return V7_OCD_Settings
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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . V7_OCD_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Add settings page to admin menu.
	 *
	 * @since 1.0.0
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'V7 One-Click Duplicate Settings', 'v7-one-click-duplicate' ),
			__( 'Duplicate Settings', 'v7-one-click-duplicate' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Add plugin action links.
	 *
	 * @since 1.0.0
	 * @param array $links Plugin action links.
	 * @return array Modified links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . $this->page_slug ) ),
			esc_html__( 'Settings', 'v7-one-click-duplicate' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting(
			'v7_ocd_settings_group',
			'v7_ocd_settings',
			array(
				'sanitize_callback' => 'v7_ocd_sanitize_settings',
			)
		);

		add_settings_section(
			'v7_ocd_general_section',
			__( 'General Settings', 'v7-one-click-duplicate' ),
			array( $this, 'render_general_section' ),
			$this->page_slug
		);

		add_settings_section(
			'v7_ocd_duplication_section',
			__( 'Duplication Options', 'v7-one-click-duplicate' ),
			array( $this, 'render_duplication_section' ),
			$this->page_slug
		);

		add_settings_section(
			'v7_ocd_permissions_section',
			__( 'Permissions', 'v7-one-click-duplicate' ),
			array( $this, 'render_permissions_section' ),
			$this->page_slug
		);

		$this->add_settings_fields();
	}

	/**
	 * Add settings fields.
	 *
	 * @since 1.0.0
	 */
	private function add_settings_fields() {
		add_settings_field(
			'enabled_post_types',
			__( 'Enable for Post Types', 'v7-one-click-duplicate' ),
			array( $this, 'render_post_types_field' ),
			$this->page_slug,
			'v7_ocd_general_section'
		);

		add_settings_field(
			'default_status',
			__( 'Default Post Status', 'v7-one-click-duplicate' ),
			array( $this, 'render_status_field' ),
			$this->page_slug,
			'v7_ocd_general_section'
		);

		add_settings_field(
			'title_suffix',
			__( 'Title Suffix', 'v7-one-click-duplicate' ),
			array( $this, 'render_suffix_field' ),
			$this->page_slug,
			'v7_ocd_general_section'
		);

		add_settings_field(
			'redirect_after',
			__( 'After Duplication', 'v7-one-click-duplicate' ),
			array( $this, 'render_redirect_field' ),
			$this->page_slug,
			'v7_ocd_general_section'
		);

		add_settings_field(
			'duplication_options',
			__( 'What to Duplicate', 'v7-one-click-duplicate' ),
			array( $this, 'render_duplication_options_field' ),
			$this->page_slug,
			'v7_ocd_duplication_section'
		);

		add_settings_field(
			'ui_options',
			__( 'UI Options', 'v7-one-click-duplicate' ),
			array( $this, 'render_ui_options_field' ),
			$this->page_slug,
			'v7_ocd_duplication_section'
		);

		add_settings_field(
			'allowed_roles',
			__( 'Allowed User Roles', 'v7-one-click-duplicate' ),
			array( $this, 'render_roles_field' ),
			$this->page_slug,
			'v7_ocd_permissions_section'
		);
	}

	/**
	 * Render general section.
	 *
	 * @since 1.0.0
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure basic duplication settings.', 'v7-one-click-duplicate' ) . '</p>';
	}

	/**
	 * Render duplication section.
	 *
	 * @since 1.0.0
	 */
	public function render_duplication_section() {
		echo '<p>' . esc_html__( 'Choose what content to duplicate.', 'v7-one-click-duplicate' ) . '</p>';
	}

	/**
	 * Render permissions section.
	 *
	 * @since 1.0.0
	 */
	public function render_permissions_section() {
		echo '<p>' . esc_html__( 'Control who can duplicate posts.', 'v7-one-click-duplicate' ) . '</p>';
	}

	/**
	 * Render post types field.
	 *
	 * @since 1.0.0
	 */
	public function render_post_types_field() {
		$settings   = v7_ocd_get_settings();
		$enabled    = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array();
		$post_types = v7_ocd_get_public_post_types();

		foreach ( $post_types as $post_type => $obj ) {
			$checked = in_array( $post_type, $enabled, true ) ? 'checked' : '';
			printf(
				'<label><input type="checkbox" name="v7_ocd_settings[enabled_post_types][]" value="%s" %s> %s</label><br>',
				esc_attr( $post_type ),
				esc_attr( $checked ),
				esc_html( $obj->labels->name )
			);
		}
	}

	/**
	 * Render status field.
	 *
	 * @since 1.0.0
	 */
	public function render_status_field() {
		$settings = v7_ocd_get_settings();
		$status   = isset( $settings['default_status'] ) ? $settings['default_status'] : 'draft';

		$statuses = array(
			'draft'   => __( 'Draft', 'v7-one-click-duplicate' ),
			'pending' => __( 'Pending Review', 'v7-one-click-duplicate' ),
			'private' => __( 'Private', 'v7-one-click-duplicate' ),
			'publish' => __( 'Published', 'v7-one-click-duplicate' ),
		);

		echo '<select name="v7_ocd_settings[default_status]">';
		foreach ( $statuses as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $status, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	/**
	 * Render suffix field.
	 *
	 * @since 1.0.0
	 */
	public function render_suffix_field() {
		$settings = v7_ocd_get_settings();
		$suffix   = isset( $settings['title_suffix'] ) ? $settings['title_suffix'] : __( '(Copy)', 'v7-one-click-duplicate' );

		printf(
			'<input type="text" name="v7_ocd_settings[title_suffix]" value="%s" class="regular-text">',
			esc_attr( $suffix )
		);
		echo '<p class="description">' . esc_html__( 'Text to append to duplicated post titles.', 'v7-one-click-duplicate' ) . '</p>';
	}

	/**
	 * Render redirect field.
	 *
	 * @since 1.0.0
	 */
	public function render_redirect_field() {
		$settings  = v7_ocd_get_settings();
		$redirect  = isset( $settings['redirect_after'] ) ? $settings['redirect_after'] : 'list';

		$options = array(
			'list' => __( 'Stay on list page', 'v7-one-click-duplicate' ),
			'edit' => __( 'Edit duplicated post', 'v7-one-click-duplicate' ),
		);

		echo '<select name="v7_ocd_settings[redirect_after]">';
		foreach ( $options as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $redirect, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	/**
	 * Render duplication options field.
	 *
	 * @since 1.0.0
	 */
	public function render_duplication_options_field() {
		$settings = v7_ocd_get_settings();

		$options = array(
			'duplicate_content'    => __( 'Content', 'v7-one-click-duplicate' ),
			'duplicate_excerpt'    => __( 'Excerpt', 'v7-one-click-duplicate' ),
			'duplicate_thumbnail'  => __( 'Featured Image', 'v7-one-click-duplicate' ),
			'duplicate_taxonomies' => __( 'Categories & Tags', 'v7-one-click-duplicate' ),
			'duplicate_meta'       => __( 'Custom Fields & Meta', 'v7-one-click-duplicate' ),
			'duplicate_author'     => __( 'Original Author', 'v7-one-click-duplicate' ),
			'duplicate_date'       => __( 'Original Date', 'v7-one-click-duplicate' ),
			'duplicate_menu_order' => __( 'Menu Order', 'v7-one-click-duplicate' ),
		);

		foreach ( $options as $key => $label ) {
			$checked = isset( $settings[ $key ] ) && $settings[ $key ] ? 'checked' : '';
			printf(
				'<label><input type="checkbox" name="v7_ocd_settings[%s]" value="1" %s> %s</label><br>',
				esc_attr( $key ),
				esc_attr( $checked ),
				esc_html( $label )
			);
		}
	}

	/**
	 * Render UI options field.
	 *
	 * @since 1.0.0
	 */
	public function render_ui_options_field() {
		$settings = v7_ocd_get_settings();

		$options = array(
			'show_in_admin_bar' => __( 'Show in Admin Bar', 'v7-one-click-duplicate' ),
			'show_in_gutenberg' => __( 'Show in Gutenberg (Experimental)', 'v7-one-click-duplicate' ),
		);

		foreach ( $options as $key => $label ) {
			$checked = isset( $settings[ $key ] ) && $settings[ $key ] ? 'checked' : '';
			printf(
				'<label><input type="checkbox" name="v7_ocd_settings[%s]" value="1" %s> %s</label><br>',
				esc_attr( $key ),
				esc_attr( $checked ),
				esc_html( $label )
			);
		}
	}

	/**
	 * Render roles field.
	 *
	 * @since 1.0.0
	 */
	public function render_roles_field() {
		$settings      = v7_ocd_get_settings();
		$allowed_roles = isset( $settings['allowed_roles'] ) ? $settings['allowed_roles'] : array();
		$roles         = wp_roles()->get_names();

		foreach ( $roles as $role_key => $role_name ) {
			$checked = in_array( $role_key, $allowed_roles, true ) ? 'checked' : '';
			printf(
				'<label><input type="checkbox" name="v7_ocd_settings[allowed_roles][]" value="%s" %s> %s</label><br>',
				esc_attr( $role_key ),
				esc_attr( $checked ),
				esc_html( $role_name )
			);
		}
		echo '<p class="description">' . esc_html__( 'Users with these roles can duplicate posts (if they have edit permissions).', 'v7-one-click-duplicate' ) . '</p>';
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'v7_ocd_messages',
				'v7_ocd_message',
				__( 'Settings saved successfully.', 'v7-one-click-duplicate' ),
				'updated'
			);
		}

		settings_errors( 'v7_ocd_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'v7_ocd_settings_group' );
				do_settings_sections( $this->page_slug );
				submit_button( __( 'Save Settings', 'v7-one-click-duplicate' ) );
				?>
			</form>
		</div>
		<?php
	}
}
