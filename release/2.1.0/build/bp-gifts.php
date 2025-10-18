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
 * @since   1.0.0
 * @license GPL-2.0-or-later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin version for cache busting and compatibility checks.
if ( ! defined( 'BP_GIFTS_VERSION' ) ) {
	define( 'BP_GIFTS_VERSION', '2.1.0' );
}

/**
 * Check if we should use the new service-based architecture.
 * This allows for a gradual migration and testing.
 */
$use_new_architecture = apply_filters( 'bp_gifts_use_new_architecture', true );

if ( $use_new_architecture && file_exists( plugin_dir_path( __FILE__ ) . 'bp-gifts-loader-v2.php' ) ) {
	// Load the new service-based architecture
	require_once plugin_dir_path( __FILE__ ) . 'bp-gifts-loader-v2.php';
	
	/**
	 * Main instance of BP Gifts (New Architecture).
	 *
	 * Returns the main instance of BP_Gifts_Loader_V2.
	 *
	 * @since 2.1.0
	 * @return BP_Gifts_Loader_V2
	 */
	function bp_gifts() {
		return BP_Gifts_Loader_V2::instance();
	}
	
	// Global for backwards compatibility.
	$GLOBALS['bp_gifts'] = bp_gifts();
	
} else {
	// Fallback to original architecture
	require_once plugin_dir_path( __FILE__ ) . 'bp-gifts-loader.php';
	
	/**
	 * Main instance of BP Gifts (Legacy).
	 *
	 * Returns the main instance of BP_Gifts_Loader to prevent the need to use globals.
	 *
	 * @since 1.0.0
	 * @return BP_Gifts_Loader
	 */
	function bp_gifts() {
		return BP_Gifts_Loader::instance();
	}
	
	// Global for backwards compatibility.
	$GLOBALS['bp_gifts'] = bp_gifts();
}