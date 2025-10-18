<?php
/**
 * Gift Service Interface
 *
 * Defines the contract for gift management operations.
 *
 * @package BP_Gifts
 * @since   2.1.0
 * @author  SuitePlugins
 * @license GPL-2.0-or-later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Gift_Service_Interface
 *
 * Contract for gift management services.
 *
 * @since 2.1.0
 */
interface Gift_Service_Interface {

	/**
	 * Get all available gifts.
	 *
	 * @since 2.1.0
	 * @param array $args Query arguments.
	 * @return array Array of gift data.
	 */
	public function get_gifts( array $args = array() );

	/**
	 * Get a single gift by ID.
	 *
	 * @since 2.1.0
	 * @param int $gift_id Gift ID.
	 * @return array|null Gift data or null if not found.
	 */
	public function get_gift( int $gift_id );

	/**
	 * Validate a gift ID.
	 *
	 * @since 2.1.0
	 * @param int $gift_id Gift ID to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function is_valid_gift( int $gift_id );

	/**
	 * Search gifts by criteria.
	 *
	 * @since 2.1.0
	 * @param string $search_term Search term.
	 * @param array  $filters     Additional filters.
	 * @return array Array of matching gifts.
	 */
	public function search_gifts( string $search_term, array $filters = array() );

	/**
	 * Get gift categories.
	 *
	 * @since 2.1.0
	 * @return array Array of categories.
	 */
	public function get_categories();

	/**
	 * Clear gift cache.
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function clear_cache();
}