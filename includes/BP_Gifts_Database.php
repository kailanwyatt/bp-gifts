<?php
/**
 * BP Gifts - Database Class
 *
 * Handles database operations for gift relationships.
 *
 * @package BP_Gifts
 * @since   2.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Gifts Database class.
 *
 * @since 2.2.0
 */
class BP_Gifts_Database {

	/**
	 * Table name for gift relationships.
	 *
	 * @since 2.2.0
	 * @var string
	 */
	public static $table_name = 'bp_gifts_relationships';

	/**
	 * Get the full table name with WordPress prefix.
	 *
	 * @since 2.2.0
	 * @return string Full table name.
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::$table_name;
	}

	/**
	 * Create the gift relationships table.
	 *
	 * @since 2.2.0
	 */
	public static function create_table() {
		global $wpdb;

		$table_name = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			sender_id bigint(20) unsigned NOT NULL,
			receiver_id bigint(20) unsigned NOT NULL,
			message_id bigint(20) unsigned NOT NULL,
			gift_id bigint(20) unsigned NOT NULL,
			date_sent datetime DEFAULT CURRENT_TIMESTAMP,
			additional_data TEXT,
			PRIMARY KEY (id),
			KEY sender_id (sender_id),
			KEY receiver_id (receiver_id),
			KEY message_id (message_id),
			KEY gift_id (gift_id),
			KEY date_sent (date_sent)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// Update table version
		update_option( 'bp_gifts_db_version', '1.0' );
	}

	/**
	 * Insert a new gift relationship.
	 *
	 * @since 2.2.0
	 * @param array $data Gift relationship data.
	 * @return int|false Relationship ID on success, false on failure.
	 */
	public static function insert_relationship( $data ) {
		global $wpdb;

		$defaults = array(
			'sender_id' => 0,
			'receiver_id' => 0,
			'message_id' => 0,
			'gift_id' => 0,
			'date_sent' => current_time( 'mysql' ),
			'additional_data' => '',
		);

		$data = wp_parse_args( $data, $defaults );

		// Validate required fields
		if ( empty( $data['sender_id'] ) || empty( $data['receiver_id'] ) || 
			 empty( $data['message_id'] ) || empty( $data['gift_id'] ) ) {
			return false;
		}

		$result = $wpdb->insert(
			self::get_table_name(),
			$data,
			array( '%d', '%d', '%d', '%d', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get gift relationships by criteria.
	 *
	 * @since 2.2.0
	 * @param array $args Query arguments.
	 * @return array Array of relationship objects.
	 */
	public static function get_relationships( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'sender_id' => 0,
			'receiver_id' => 0,
			'message_id' => 0,
			'gift_id' => 0,
			'limit' => 0,
			'offset' => 0,
			'orderby' => 'date_sent',
			'order' => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );
		$table_name = self::get_table_name();

		$where_clauses = array();
		$where_values = array();

		if ( ! empty( $args['sender_id'] ) ) {
			$where_clauses[] = 'sender_id = %d';
			$where_values[] = $args['sender_id'];
		}

		if ( ! empty( $args['receiver_id'] ) ) {
			$where_clauses[] = 'receiver_id = %d';
			$where_values[] = $args['receiver_id'];
		}

		if ( ! empty( $args['message_id'] ) ) {
			$where_clauses[] = 'message_id = %d';
			$where_values[] = $args['message_id'];
		}

		if ( ! empty( $args['gift_id'] ) ) {
			$where_clauses[] = 'gift_id = %d';
			$where_values[] = $args['gift_id'];
		}

		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		$orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
		if ( ! $orderby ) {
			$orderby = 'date_sent DESC';
		}

		$limit_sql = '';
		if ( ! empty( $args['limit'] ) ) {
			$limit_sql = $wpdb->prepare( 'LIMIT %d', $args['limit'] );
			if ( ! empty( $args['offset'] ) ) {
				$limit_sql = $wpdb->prepare( 'LIMIT %d, %d', $args['offset'], $args['limit'] );
			}
		}

		$sql = "SELECT * FROM $table_name $where_sql ORDER BY $orderby $limit_sql";

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get gift relationship by ID.
	 *
	 * @since 2.2.0
	 * @param int $id Relationship ID.
	 * @return object|null Relationship object or null if not found.
	 */
	public static function get_relationship( $id ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$sql = $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id );

		return $wpdb->get_row( $sql );
	}

	/**
	 * Update gift relationship.
	 *
	 * @since 2.2.0
	 * @param int   $id   Relationship ID.
	 * @param array $data Data to update.
	 * @return bool True on success, false on failure.
	 */
	public static function update_relationship( $id, $data ) {
		global $wpdb;

		$result = $wpdb->update(
			self::get_table_name(),
			$data,
			array( 'id' => $id ),
			array( '%d', '%d', '%d', '%d', '%s', '%s' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete gift relationship.
	 *
	 * @since 2.2.0
	 * @param int $id Relationship ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_relationship( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			self::get_table_name(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get gift statistics for a user.
	 *
	 * @since 2.2.0
	 * @param int $user_id User ID.
	 * @return array Statistics array.
	 */
	public static function get_user_stats( $user_id ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$sent = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE sender_id = %d",
			$user_id
		) );

		$received = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE receiver_id = %d",
			$user_id
		) );

		return array(
			'sent' => (int) $sent,
			'received' => (int) $received,
		);
	}

	/**
	 * Get popular gifts.
	 *
	 * @since 2.2.0
	 * @param int $limit Number of gifts to return.
	 * @return array Array of gift IDs with counts.
	 */
	public static function get_popular_gifts( $limit = 10 ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$sql = $wpdb->prepare(
			"SELECT gift_id, COUNT(*) as count 
			 FROM $table_name 
			 GROUP BY gift_id 
			 ORDER BY count DESC 
			 LIMIT %d",
			$limit
		);

		return $wpdb->get_results( $sql );
	}

	/**
	 * Check if table exists.
	 *
	 * @since 2.2.0
	 * @return bool True if table exists, false otherwise.
	 */
	public static function table_exists() {
		global $wpdb;

		$table_name = self::get_table_name();
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );

		return $table_exists === $table_name;
	}
}