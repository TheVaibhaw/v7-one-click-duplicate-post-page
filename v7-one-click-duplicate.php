<?php
/**
 * Plugin Name: V7 One-Click Duplicate Post & Page
 * Description: Lightweight, fast, and secure plugin to duplicate posts, pages, and custom post types with a single click.
 * Version: 1.0.0
 * Author: Vaibhaw Kumar Parashar
 * Author URI: https://vaibhawkumarparashar.in
 * Text Domain: v7-one-click-duplicate
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package V7_One_Click_Duplicate
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'V7_OCD_VERSION', '1.0.0' );
define( 'V7_OCD_PLUGIN_FILE', __FILE__ );
define( 'V7_OCD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'V7_OCD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'V7_OCD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class loader.
 *
 * @since 1.0.0
 */
final class V7_One_Click_Duplicate {

	/**
	 * Plugin instance.
	 *
	 * @var V7_One_Click_Duplicate
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @since 1.0.0
	 * @return V7_One_Click_Duplicate
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
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required dependencies.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		require_once V7_OCD_PLUGIN_DIR . 'includes/helpers.php';
		require_once V7_OCD_PLUGIN_DIR . 'includes/class-permissions.php';
		require_once V7_OCD_PLUGIN_DIR . 'includes/class-duplicator.php';
		require_once V7_OCD_PLUGIN_DIR . 'includes/class-admin-ui.php';
		require_once V7_OCD_PLUGIN_DIR . 'includes/class-settings.php';
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init_components' ) );
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'v7-one-click-duplicate',
			false,
			dirname( V7_OCD_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Initialize plugin components.
	 *
	 * @since 1.0.0
	 */
	public function init_components() {
		if ( is_admin() ) {
			V7_OCD_Admin_UI::get_instance();
			V7_OCD_Settings::get_instance();
		}
	}
}

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 * @return V7_One_Click_Duplicate
 */
function v7_ocd_init() {
	return V7_One_Click_Duplicate::get_instance();
}

// Fire it up!
v7_ocd_init();
