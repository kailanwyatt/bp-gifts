<?php
/**
 * Plugin Name: Gifts for BuddyPress
 * Plugin URI:  https://github.com/suiteplugins/bp-gifts
 * Description: Enable users to share gifts with other users in BuddyPress messages. Administrators can add unlimited gifts and users can select and send them through a modern modal interface.
 * Author:      SuitePlugins
 * Author URI:  https://suiteplugins.com
 * Version:     2.1.0
 * Requires at least: 5.0
 * Tested up to: 6.8.2
 * Requires PHP: 7.4
 * Text Domain: bp-gifts
 * Domain Path: /languages/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network:     false
 *
 * @package BP_Gifts
 * @author  SuitePlugins
 * @since   2.1.0
 * @license GPL-2.0-or-later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
if ( ! defined( 'BP_GIFTS_VERSION' ) ) {
	define( 'BP_GIFTS_VERSION', '2.1.0' );
}

if ( ! defined( 'BP_GIFTS_PLUGIN_DIR' ) ) {
	define( 'BP_GIFTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'BP_GIFTS_PLUGIN_URL' ) ) {
	define( 'BP_GIFTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once BP_GIFTS_PLUGIN_DIR . 'includes/BP_Gifts_Database.php';

/**
 * Register BP Gifts as a BuddyPress component.
 *
 * This needs to happen early, before BuddyPress processes components.
 *
 * @since 2.1.0
 * @param array $components Array of optional BuddyPress components.
 * @return array Modified array including BP Gifts component.
 */
function bp_gifts_register_component( $components ) {
	// Just add the component ID, not the array with title/description
	$components[] = 'gifts';
	
	return $components;
}

/**
 * Add component title and description for BuddyPress Components admin page.
 *
 * @since 2.1.0
 * @param array $component_info Array of component information.
 * @return array Modified array with gifts component info.
 */
function bp_gifts_component_info( $component_info ) {
	$component_info['gifts'] = array(
		'title'       => __( 'User Gifts', 'bp-gifts' ),
		'description' => __( 'Allow members to send virtual gifts to each other through private messages, creating a more engaging and social community experience.', 'bp-gifts' ),
	);
	
	return $component_info;
}

/**
 * Register BP Gifts admin settings early.
 *
 * @since 2.2.0
 */
function bp_gifts_register_admin_settings() {
	$settings_file = dirname( __FILE__ ) . '/includes/BP_Gifts_Settings.php';
	
	if ( file_exists( $settings_file ) ) {
		require_once $settings_file;
		
		if ( class_exists( 'BP_Gifts_Settings' ) ) {
			BP_Gifts_Settings::init();
		}
	}
}

// Register admin settings hook early
add_action( 'bp_register_admin_settings', 'bp_gifts_register_admin_settings' );



/**
 * Initialize BP Gifts plugin.
 * 
 * Waits for BuddyPress to be loaded before initializing.
 *
 * @since 2.1.0
 */
function bp_gifts_init() {
	// Check if BuddyPress is active
	if ( ! function_exists( 'buddypress' ) ) {
		add_action( 'admin_notices', 'bp_gifts_buddypress_required_notice' );
		return;
	}

	// Load the plugin classes
	require_once BP_GIFTS_PLUGIN_DIR . 'includes/BP_Gifts_Settings.php';
	require_once BP_GIFTS_PLUGIN_DIR . 'includes/BP_Gifts_Taxonomy.php';
	require_once BP_GIFTS_PLUGIN_DIR . 'includes/BP_Gifts_Profile_Tab.php';
	require_once BP_GIFTS_PLUGIN_DIR . 'includes/BP_Gifts_Core.php';
	require_once BP_GIFTS_PLUGIN_DIR . 'includes/BP_Gifts_Gifts.php';
	require_once BP_GIFTS_PLUGIN_DIR . 'includes/BP_Gifts_Messages.php';
	require_once BP_GIFTS_PLUGIN_DIR . 'includes/BP_Gifts_Modal.php';
	require_once BP_GIFTS_PLUGIN_DIR . 'includes/BP_Gifts_MyCred.php';
	require_once BP_GIFTS_PLUGIN_DIR . 'includes/BP_Gifts_Admin.php';
	
	// Initialize the plugin
	BP_Gifts_Core::instance();
	
	// Initialize myCred integration if available
	if ( class_exists( 'BP_Gifts_MyCred' ) ) {
		$mycred_integration = new BP_Gifts_MyCred();
		$mycred_integration->init_hooks();
	}
	
	// Ensure database table exists
	bp_gifts_check_database();
}

/**
 * Display notice if BuddyPress is not active.
 *
 * @since 2.1.0
 */
function bp_gifts_buddypress_required_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'BP Gifts requires BuddyPress to be installed and activated.', 'bp-gifts' ); ?></p>
	</div>
	<?php
}

/**
 * Plugin activation hook.
 *
 * @since 2.1.0
 */
function bp_gifts_activate() {
	// Create database tables
	require_once BP_GIFTS_PLUGIN_DIR . 'includes/BP_Gifts_Database.php';
	BP_Gifts_Database::create_table();
	
	// Set activation flag for any needed initialization
	update_option( 'bp_gifts_activated', true );
}

/**
 * Check and create database tables if needed.
 *
 * @since 2.1.0
 */
function bp_gifts_check_database() {
	// Only run on admin pages or when specifically needed
	if ( ! is_admin() && ! wp_doing_cron() ) {
		return;
	}

	// Check if table exists
	if ( ! BP_Gifts_Database::table_exists() ) {
		BP_Gifts_Database::create_table();
	}
}

// Register BP Gifts as a BuddyPress component immediately
add_filter( 'bp_optional_components', 'bp_gifts_register_component' );

// Register component info for admin display
add_filter( 'bp_admin_optional_components', 'bp_gifts_component_info' );

add_filter( 'bp_core_admin_get_components', 'bp_gifts_component_info', 12, 1 );



// Initialize after BuddyPress is loaded
add_action( 'bp_loaded', 'bp_gifts_init' );

// Plugin activation
register_activation_hook( __FILE__, 'bp_gifts_activate' );

// Check database on admin init
add_action( 'admin_init', 'bp_gifts_check_database' );