<?php
/**
 * Gift Service Implementation
 *
 * Handles all gift-related operations.
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
 * Class BP_Gifts_Gift_Service
 *
 * Implementation of gift management service.
 *
 * @since 2.1.0
 */
class BP_Gifts_Gift_Service implements Gift_Service_Interface {

	/**
	 * Post type for gifts.
	 *
	 * @since 2.1.0
	 * @var   string
	 */
	private $post_type;

	/**
	 * Cache key prefix.
	 *
	 * @since 2.1.0
	 * @var   string
	 */
	private $cache_key_prefix = 'bp_gifts_';

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 * @param string $post_type Post type for gifts.
	 */
	public function __construct( string $post_type = 'bp_gifts' ) {
		$this->post_type = $post_type;
	}

	/**
	 * Get all available gifts.
	 *
	 * @since 2.1.0
	 * @param array $args Query arguments.
	 * @return array Array of gift data.
	 */
	public function get_gifts( array $args = array() ) {
		$defaults = array(
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'post_type'      => $this->post_type,
			'meta_query'     => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
		);

		$args = wp_parse_args( $args, $defaults );
		
		// Check cache first
		$cache_key = $this->cache_key_prefix . 'all_' . md5( serialize( $args ) );
		$cached_gifts = get_transient( $cache_key );
		
		if ( false !== $cached_gifts ) {
			return $cached_gifts;
		}

		$gifts = array();
		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$gift = $this->format_gift_data( get_the_ID() );
				if ( $gift ) {
					$gifts[] = $gift;
				}
			}
			wp_reset_postdata();
		}

		// Cache results
		set_transient( $cache_key, $gifts, DAY_IN_SECONDS );

		return $gifts;
	}

	/**
	 * Get a single gift by ID.
	 *
	 * @since 2.1.0
	 * @param int $gift_id Gift ID.
	 * @return array|null Gift data or null if not found.
	 */
	public function get_gift( int $gift_id ) {
		$gift_post = get_post( $gift_id );
		
		if ( ! $gift_post || 
			 $gift_post->post_type !== $this->post_type || 
			 $gift_post->post_status !== 'publish' ) {
			return null;
		}

		return $this->format_gift_data( $gift_id );
	}

	/**
	 * Validate a gift ID.
	 *
	 * @since 2.1.0
	 * @param int $gift_id Gift ID to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function is_valid_gift( int $gift_id ) {
		$gift = $this->get_gift( $gift_id );
		return ! is_null( $gift );
	}

	/**
	 * Search gifts by criteria.
	 *
	 * @since 2.1.0
	 * @param string $search_term Search term.
	 * @param array  $filters     Additional filters.
	 * @return array Array of matching gifts.
	 */
	public function search_gifts( string $search_term, array $filters = array() ) {
		$args = array(
			's'              => sanitize_text_field( $search_term ),
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'post_type'      => $this->post_type,
			'meta_query'     => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
		);

		// Add category filter if provided
		if ( ! empty( $filters['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'bp_gift_category',
					'field'    => 'slug',
					'terms'    => sanitize_title( $filters['category'] ),
				),
			);
		}

		return $this->get_gifts( $args );
	}

	/**
	 * Get gift categories.
	 *
	 * @since 2.1.0
	 * @return array Array of categories.
	 */
	public function get_categories() {
		$cache_key = $this->cache_key_prefix . 'categories';
		$cached_categories = get_transient( $cache_key );
		
		if ( false !== $cached_categories ) {
			return $cached_categories;
		}

		$categories = get_terms( array(
			'taxonomy'   => 'bp_gift_category',
			'hide_empty' => true,
		) );

		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}

		$formatted_categories = array();
		foreach ( $categories as $category ) {
			$formatted_categories[] = array(
				'id'    => $category->term_id,
				'name'  => $category->name,
				'slug'  => $category->slug,
				'count' => $category->count,
			);
		}

		set_transient( $cache_key, $formatted_categories, HOUR_IN_SECONDS );

		return $formatted_categories;
	}

	/**
	 * Clear gift cache.
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function clear_cache() {
		global $wpdb;
		
		// Delete all transients with our prefix
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_' . $this->cache_key_prefix . '%'
			)
		);
		
		// Also clear timeout transients
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_timeout_' . $this->cache_key_prefix . '%'
			)
		);
	}

	/**
	 * Format gift data for output.
	 *
	 * @since 2.1.0
	 * @param int $gift_id Gift ID.
	 * @return array|null Formatted gift data or null.
	 */
	private function format_gift_data( int $gift_id ) {
		$post_thumbnail_id = get_post_thumbnail_id( $gift_id );
		$image_attributes  = wp_get_attachment_image_src( $post_thumbnail_id, 'thumbnail' );

		if ( empty( $image_attributes ) ) {
			return null;
		}

		// Get categories
		$categories = wp_get_post_terms( $gift_id, 'bp_gift_category' );
		$category_names = array();
		if ( ! is_wp_error( $categories ) ) {
			$category_names = wp_list_pluck( $categories, 'name' );
		}

		return array(
			'id'         => $gift_id,
			'name'       => get_the_title( $gift_id ),
			'image'      => esc_url( $image_attributes[0] ),
			'image_alt'  => get_post_meta( $post_thumbnail_id, '_wp_attachment_image_alt', true ),
			'categories' => $category_names,
		);
	}
}