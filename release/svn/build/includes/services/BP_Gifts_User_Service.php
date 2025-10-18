<?php
/**
 * BP Gifts User Service
 *
 * Handles user gift management and viewing functionality.
 *
 * @package BP_Gifts
 * @since   2.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User Service for BP Gifts.
 *
 * Manages user gift viewing, history, and personal gift management.
 *
 * @since 2.1.0
 */
class BP_Gifts_User_Service {

	/**
	 * Gift service instance.
	 *
	 * @since 2.1.0
	 * @var   Gift_Service_Interface
	 */
	private $gift_service;

	/**
	 * Message service instance.
	 *
	 * @since 2.1.0
	 * @var   Message_Service_Interface
	 */
	private $message_service;

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 * @param Gift_Service_Interface    $gift_service    Gift service instance.
	 * @param Message_Service_Interface $message_service Message service instance.
	 */
	public function __construct( Gift_Service_Interface $gift_service, Message_Service_Interface $message_service ) {
		$this->gift_service    = $gift_service;
		$this->message_service = $message_service;
	}

	/**
	 * Get all gifts received by a user.
	 *
	 * @since 2.1.0
	 * @param int $user_id User ID.
	 * @param array $args Query arguments.
	 * @return array Array of received gifts with metadata.
	 */
	public function get_received_gifts( int $user_id, array $args = array() ) {
		if ( $user_id <= 0 ) {
			return array();
		}

		$defaults = array(
			'limit'      => 20,
			'offset'     => 0,
			'type'       => 'all', // 'message', 'thread', 'all'
			'order_by'   => 'date_received',
			'order'      => 'DESC',
			'date_from'  => null,
			'date_to'    => null,
		);

		$args = wp_parse_args( $args, $defaults );

		global $wpdb;

		// Get message gifts
		$message_gifts = array();
		if ( in_array( $args['type'], array( 'all', 'message' ), true ) ) {
			$message_gifts = $this->get_user_message_gifts( $user_id, $args );
		}

		// Get thread gifts
		$thread_gifts = array();
		if ( in_array( $args['type'], array( 'all', 'thread' ), true ) ) {
			$thread_gifts = $this->get_user_thread_gifts( $user_id, $args );
		}

		// Combine and sort
		$all_gifts = array_merge( $message_gifts, $thread_gifts );

		// Sort by date
		usort( $all_gifts, function( $a, $b ) use ( $args ) {
			$date_a = strtotime( $a['date_received'] );
			$date_b = strtotime( $b['date_received'] );
			
			return $args['order'] === 'ASC' ? $date_a - $date_b : $date_b - $date_a;
		});

		// Apply limit and offset
		return array_slice( $all_gifts, $args['offset'], $args['limit'] );
	}

	/**
	 * Get message gifts for a user.
	 *
	 * @since 2.1.0
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 * @return array Message gifts.
	 */
	private function get_user_message_gifts( int $user_id, array $args ) {
		global $wpdb;

		$bp_prefix = bp_core_get_table_prefix();
		
		$sql = "
			SELECT m.id as message_id, m.thread_id, m.sender_id, m.date_sent, mm.meta_value as gift_id
			FROM {$bp_prefix}bp_messages_messages m
			INNER JOIN {$bp_prefix}bp_messages_recipients r ON m.thread_id = r.thread_id
			INNER JOIN {$bp_prefix}bp_messages_meta mm ON m.id = mm.message_id
			WHERE r.user_id = %d 
			AND r.is_deleted = 0
			AND mm.meta_key = '_bp_gift_id'
			AND mm.meta_value IS NOT NULL
		";

		$params = array( $user_id );

		// Add date filters
		if ( $args['date_from'] ) {
			$sql .= ' AND m.date_sent >= %s';
			$params[] = $args['date_from'];
		}

		if ( $args['date_to'] ) {
			$sql .= ' AND m.date_sent <= %s';
			$params[] = $args['date_to'];
		}

		$sql .= ' ORDER BY m.date_sent ' . $args['order'];

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		$gifts = array();
		foreach ( $results as $result ) {
			$gift_data = $this->gift_service->get_gift( absint( $result['gift_id'] ) );
			if ( $gift_data ) {
				$gifts[] = array(
					'id'            => $result['gift_id'],
					'type'          => 'message',
					'gift_data'     => $gift_data,
					'sender_id'     => $result['sender_id'],
					'sender_name'   => bp_core_get_user_displayname( $result['sender_id'] ),
					'thread_id'     => $result['thread_id'],
					'message_id'    => $result['message_id'],
					'date_received' => $result['date_sent'],
				);
			}
		}

		return $gifts;
	}

	/**
	 * Get thread gifts for a user.
	 *
	 * @since 2.1.0
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 * @return array Thread gifts.
	 */
	private function get_user_thread_gifts( int $user_id, array $args ) {
		global $wpdb;

		$bp_prefix = bp_core_get_table_prefix();
		
		$sql = "
			SELECT t.id as thread_id, t.last_sender_id, t.last_message_date, tm.meta_value as gift_id
			FROM {$bp_prefix}bp_messages_threads t
			INNER JOIN {$bp_prefix}bp_messages_recipients r ON t.id = r.thread_id
			INNER JOIN {$wpdb->prefix}bp_messages_threadmeta tm ON t.id = tm.thread_id
			WHERE r.user_id = %d 
			AND r.is_deleted = 0
			AND tm.meta_key = '_bp_thread_gift'
			AND tm.meta_value IS NOT NULL
		";

		$params = array( $user_id );

		// Add date filters
		if ( $args['date_from'] ) {
			$sql .= ' AND t.last_message_date >= %s';
			$params[] = $args['date_from'];
		}

		if ( $args['date_to'] ) {
			$sql .= ' AND t.last_message_date <= %s';
			$params[] = $args['date_to'];
		}

		$sql .= ' ORDER BY t.last_message_date ' . $args['order'];

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		$gifts = array();
		foreach ( $results as $result ) {
			$gift_data = $this->gift_service->get_gift( absint( $result['gift_id'] ) );
			if ( $gift_data ) {
				$gifts[] = array(
					'id'            => $result['gift_id'],
					'type'          => 'thread',
					'gift_data'     => $gift_data,
					'sender_id'     => $result['last_sender_id'],
					'sender_name'   => bp_core_get_user_displayname( $result['last_sender_id'] ),
					'thread_id'     => $result['thread_id'],
					'message_id'    => null,
					'date_received' => $result['last_message_date'],
				);
			}
		}

		return $gifts;
	}

	/**
	 * Get gifts sent by a user.
	 *
	 * @since 2.1.0
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 * @return array Sent gifts.
	 */
	public function get_sent_gifts( int $user_id, array $args = array() ) {
		if ( $user_id <= 0 ) {
			return array();
		}

		$defaults = array(
			'limit'     => 20,
			'offset'    => 0,
			'type'      => 'all',
			'order_by'  => 'date_sent',
			'order'     => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		global $wpdb;
		$bp_prefix = bp_core_get_table_prefix();

		// Get sent message gifts
		$message_gifts = array();
		if ( in_array( $args['type'], array( 'all', 'message' ), true ) ) {
			$sql = "
				SELECT m.id as message_id, m.thread_id, m.date_sent, mm.meta_value as gift_id,
				       r.user_id as recipient_id
				FROM {$bp_prefix}bp_messages_messages m
				INNER JOIN {$bp_prefix}bp_messages_recipients r ON m.thread_id = r.thread_id
				INNER JOIN {$bp_prefix}bp_messages_meta mm ON m.id = mm.message_id
				WHERE m.sender_id = %d 
				AND r.user_id != %d
				AND mm.meta_key = '_bp_gift_id'
				AND mm.meta_value IS NOT NULL
				ORDER BY m.date_sent {$args['order']}
			";

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $user_id, $user_id ), ARRAY_A );

			foreach ( $results as $result ) {
				$gift_data = $this->gift_service->get_gift( absint( $result['gift_id'] ) );
				if ( $gift_data ) {
					$message_gifts[] = array(
						'id'             => $result['gift_id'],
						'type'           => 'message',
						'gift_data'      => $gift_data,
						'recipient_id'   => $result['recipient_id'],
						'recipient_name' => bp_core_get_user_displayname( $result['recipient_id'] ),
						'thread_id'      => $result['thread_id'],
						'message_id'     => $result['message_id'],
						'date_sent'      => $result['date_sent'],
					);
				}
			}
		}

		return $message_gifts;
	}

	/**
	 * Get gift statistics for a user.
	 *
	 * @since 2.1.0
	 * @param int $user_id User ID.
	 * @return array Gift statistics.
	 */
	public function get_gift_stats( int $user_id ) {
		if ( $user_id <= 0 ) {
			return array();
		}

		$received = $this->get_received_gifts( $user_id, array( 'limit' => -1 ) );
		$sent = $this->get_sent_gifts( $user_id, array( 'limit' => -1 ) );

		$stats = array(
			'total_received'         => count( $received ),
			'total_sent'            => count( $sent ),
			'message_gifts_received' => count( array_filter( $received, function( $gift ) {
				return $gift['type'] === 'message';
			})),
			'thread_gifts_received'  => count( array_filter( $received, function( $gift ) {
				return $gift['type'] === 'thread';
			})),
			'favorite_gift'         => $this->get_most_received_gift( $received ),
			'most_active_sender'    => $this->get_most_active_sender( $received ),
		);

		return $stats;
	}

	/**
	 * Get the most received gift.
	 *
	 * @since 2.1.0
	 * @param array $received_gifts Received gifts array.
	 * @return array|null Most received gift data.
	 */
	private function get_most_received_gift( array $received_gifts ) {
		if ( empty( $received_gifts ) ) {
			return null;
		}

		$gift_counts = array();
		foreach ( $received_gifts as $gift ) {
			$gift_id = $gift['id'];
			if ( ! isset( $gift_counts[ $gift_id ] ) ) {
				$gift_counts[ $gift_id ] = array(
					'count' => 0,
					'gift_data' => $gift['gift_data'],
				);
			}
			$gift_counts[ $gift_id ]['count']++;
		}

		$most_received = array_reduce( $gift_counts, function( $carry, $item ) {
			return ( ! $carry || $item['count'] > $carry['count'] ) ? $item : $carry;
		});

		return $most_received;
	}

	/**
	 * Get the most active gift sender.
	 *
	 * @since 2.1.0
	 * @param array $received_gifts Received gifts array.
	 * @return array|null Most active sender data.
	 */
	private function get_most_active_sender( array $received_gifts ) {
		if ( empty( $received_gifts ) ) {
			return null;
		}

		$sender_counts = array();
		foreach ( $received_gifts as $gift ) {
			$sender_id = $gift['sender_id'];
			if ( ! isset( $sender_counts[ $sender_id ] ) ) {
				$sender_counts[ $sender_id ] = array(
					'count' => 0,
					'sender_name' => $gift['sender_name'],
				);
			}
			$sender_counts[ $sender_id ]['count']++;
		}

		$most_active = array_reduce( $sender_counts, function( $carry, $item ) {
			return ( ! $carry || $item['count'] > $carry['count'] ) ? $item : $carry;
		});

		return $most_active;
	}
}