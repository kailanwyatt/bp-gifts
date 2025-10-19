<?php
/**
 * BP Gifts - Gifts Class
 *
 * Handles gift-related functionality.
 *
 * @package BP_Gifts
 * @since   2.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Gifts Gifts class.
 *
 * @since 2.2.0
 */
class BP_Gifts_Gifts {

	/**
	 * Get all gifts.
	 *
	 * @since 2.2.0
	 * @return array Array of gift objects.
	 */
	public function get_all_gifts() {
		$cache_key = 'bp_gifts_all';
		$gifts = wp_cache_get( $cache_key, 'bp_gifts' );
		
		if ( false === $gifts ) {
			$posts = get_posts( array(
				'post_type'      => 'bp_gifts',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			) );
			
			$gifts = array();
			foreach ( $posts as $post ) {
				$gifts[] = $this->format_gift_object( $post );
			}
			
			wp_cache_set( $cache_key, $gifts, 'bp_gifts', HOUR_IN_SECONDS );
		}
		
		return $gifts;
	}

	/**
	 * Get gifts by category.
	 *
	 * @since 2.2.0
	 * @param string $category_slug Category slug.
	 * @return array Array of gift objects.
	 */
	public function get_gifts_by_category( $category_slug ) {
		$posts = get_posts( array(
			'post_type'      => 'bp_gifts',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'gift_category',
					'field'    => 'slug',
					'terms'    => $category_slug,
				),
			),
		) );
		
		$gifts = array();
		foreach ( $posts as $post ) {
			$gifts[] = $this->format_gift_object( $post );
		}
		
		return $gifts;
	}

	/**
	 * Get a single gift by ID.
	 *
	 * @since 2.2.0
	 * @param int $gift_id Gift post ID.
	 * @return object|null Gift object or null if not found.
	 */
	public function get_gift( $gift_id ) {
		$post = get_post( $gift_id );
		
		if ( ! $post || $post->post_type !== 'bp_gifts' ) {
			return null;
		}
		
		return $this->format_gift_object( $post );
	}

	/**
	 * Get gifts sent by a user.
	 *
	 * @since 2.2.0
	 * @param int $user_id User ID.
	 * @return array Array of gift objects with message info.
	 */
	public function get_user_sent_gifts( $user_id ) {
		global $wpdb;
		
		$bp = buddypress();
		
		$query = $wpdb->prepare( "
			SELECT m.id as message_id, m.thread_id, m.sender_id, m.date_sent, 
			       gm.gift_id, p.post_title as gift_title
			FROM {$bp->messages->table_name_messages} m
			INNER JOIN {$wpdb->postmeta} gm ON m.id = gm.post_id AND gm.meta_key = 'bp_gifts_message_gift'
			INNER JOIN {$wpdb->posts} p ON gm.meta_value = p.ID
			WHERE m.sender_id = %d
			ORDER BY m.date_sent DESC
		", $user_id );
		
		return $wpdb->get_results( $query );
	}

	/**
	 * Get gifts received by a user.
	 *
	 * @since 2.2.0
	 * @param int $user_id User ID.
	 * @return array Array of gift objects with message info.
	 */
	public function get_user_received_gifts( $user_id ) {
		global $wpdb;
		
		$bp = buddypress();
		
		$query = $wpdb->prepare( "
			SELECT m.id as message_id, m.thread_id, m.sender_id, m.date_sent, 
			       gm.gift_id, p.post_title as gift_title
			FROM {$bp->messages->table_name_messages} m
			INNER JOIN {$bp->messages->table_name_recipients} r ON m.thread_id = r.thread_id
			INNER JOIN {$wpdb->postmeta} gm ON m.id = gm.post_id AND gm.meta_key = 'bp_gifts_message_gift'
			INNER JOIN {$wpdb->posts} p ON gm.meta_value = p.ID
			WHERE r.user_id = %d
			ORDER BY m.date_sent DESC
		", $user_id );
		
		return $wpdb->get_results( $query );
	}

	/**
	 * Get all gifts for a user (sent and received).
	 *
	 * @since 2.2.0
	 * @param int $user_id User ID.
	 * @return array Array of gift objects.
	 */
	public function get_user_gifts( $user_id ) {
		$sent = $this->get_user_sent_gifts( $user_id );
		$received = $this->get_user_received_gifts( $user_id );
		
		return array_merge( $sent, $received );
	}

	/**
	 * Clear gift cache.
	 *
	 * @since 2.2.0
	 */
	public function clear_cache() {
		wp_cache_delete( 'bp_gifts_all', 'bp_gifts' );
	}

	/**
	 * Format a post object into a gift object.
	 *
	 * @since 2.2.0
	 * @param WP_Post $post Post object.
	 * @return object Formatted gift object.
	 */
	private function format_gift_object( $post ) {
		$gift = new stdClass();
		$gift->id = $post->ID;
		$gift->title = $post->post_title;
		$gift->description = $post->post_content;
		$gift->thumbnail = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
		$gift->cost = get_post_meta( $post->ID, 'gift_cost', true );
		$gift->categories = wp_get_post_terms( $post->ID, 'gift_category', array( 'fields' => 'names' ) );
		
		return $gift;
	}
}