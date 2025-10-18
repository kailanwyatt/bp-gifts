<?php
/**
 * Uninstall BP Gifts
 *
 * Deletes all plugin data when the plugin is deleted.
 *
 * @package BP_Gifts
 * @since   2.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data on uninstall.
 *
 * @since 2.0.0
 */
function bp_gifts_uninstall_cleanup() {
	global $wpdb;

	// Delete all gift posts.
	$gift_posts = get_posts(
		array(
			'post_type'   => 'bp_gifts',
			'numberposts' => -1,
			'post_status' => 'any',
		)
	);

	foreach ( $gift_posts as $post ) {
		wp_delete_post( $post->ID, true );
	}

	// Delete transients.
	delete_transient( 'bp_gifts_array' );

	// Delete gift message meta.
	$wpdb->delete(
		$wpdb->prefix . 'bp_messages_meta',
		array( 'meta_key' => '_bp_gift' ),
		array( '%s' )
	);

	// Delete any options (if any were added in future versions).
	delete_option( 'bp_gifts_version' );
	delete_option( 'bp_gifts_settings' );

	// Clean up any remaining meta.
	$wpdb->delete(
		$wpdb->postmeta,
		array( 'meta_key' => '_bp_gift_data' ),
		array( '%s' )
	);

	// Flush rewrite rules.
	flush_rewrite_rules();
}

// Run cleanup.
bp_gifts_uninstall_cleanup();