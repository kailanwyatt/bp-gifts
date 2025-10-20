<?php
/**
 * BP Gifts - Messages Class
 *
 * Handles gift-message integration.
 *
 * @package BP_Gifts
 * @since   2.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Gifts Messages class.
 *
 * @since 2.2.0
 */
class BP_Gifts_Messages {

	/**
	 * Process gift from submission.
	 *
	 * @since 2.2.0
	 * @param object $message Message object.
	 */
	public function process_gift_from_submission( $message ) {
		
		// Check if a gift was attached (support both field names for compatibility)
		$gift_id = 0;
		if ( isset( $_POST['bp_gift_id'] ) && ! empty( $_POST['bp_gift_id'] ) ) {
			$gift_id = absint( $_POST['bp_gift_id'] );
		} elseif ( isset( $_POST['bp_gifts_selected_gift'] ) && ! empty( $_POST['bp_gifts_selected_gift'] ) ) {
			$gift_id = absint( $_POST['bp_gifts_selected_gift'] );
		}
		
		// Check for gift data in BuddyPress cookies (passed via bp_get_cookies())
		if ( ! $gift_id && isset( $_POST['cookie'] ) ) {
			$gift_id = $this->extract_gift_from_bp_cookies( $_POST['cookie'], $message->thread_id );
		}

		if ( ! $gift_id ) {
			return;
		}		// Verify the gift exists
		$gift_post = get_post( $gift_id );
		if ( ! $gift_post || $gift_post->post_type !== 'bp_gifts' ) {
			return;
		}

		// Check nonce
		if ( ! isset( $_POST['bp_gifts_nonce'] ) || ! wp_verify_nonce( $_POST['bp_gifts_nonce'], 'bp_gifts_nonce' ) ) {
			return;
		}

		// Check if user can afford the gift (if myCred is enabled)
		if ( BP_Gifts_Settings::is_mycred_enabled() ) {
			$mycred = new BP_Gifts_MyCred();
			if ( ! $mycred->can_user_afford_gift( bp_loggedin_user_id(), $gift_id ) ) {
				// Add error notice
				bp_core_add_message( __( 'You do not have enough points to send this gift.', 'bp-gifts' ), 'error' );
				return;
			}
		}

		// Attach gift to message
		$this->attach_gift_to_message( $message->id, $gift_id );

		// Create gift relationship record
		$relationship_result = $this->create_gift_relationship( $message, $gift_id );

		// Deduct points if myCred is enabled
		if ( BP_Gifts_Settings::is_mycred_enabled() && $relationship_result ) {
			$mycred = new BP_Gifts_MyCred();
			// Get recipients for proper transaction logging
			$recipients = $this->get_message_recipients( $message->thread_id );
			$receiver_id = ! empty( $recipients ) ? $recipients[0]->user_id : 0;
			$success = $mycred->charge_user_for_gift( bp_loggedin_user_id(), $receiver_id, $gift_id );
		}
	}

	/**
	 * Attach a gift to a message.
	 *
	 * @since 2.2.0
	 * @param int $message_id Message ID.
	 * @param int $gift_id Gift ID.
	 */
	public function attach_gift_to_message( $message_id, $gift_id ) {
		update_post_meta( $message_id, 'bp_gifts_message_gift', $gift_id );
	}

	/**
	 * Get gift attached to a message.
	 *
	 * @since 2.2.0
	 * @param int $message_id Message ID.
	 * @return object|null Gift object or null if none found.
	 */
	public function get_message_gift( $message_id ) {
		$gift_id = get_post_meta( $message_id, 'bp_gifts_message_gift', true );
		
		if ( ! $gift_id ) {
			return null;
		}

		$gifts = new BP_Gifts_Gifts();
		return $gifts->get_gift( $gift_id );
	}

	/**
	 * Get all gifts in a message thread.
	 *
	 * @since 2.2.0
	 * @param int $thread_id Thread ID.
	 * @return array Array of gift objects with message info.
	 */
	public function get_thread_gifts( $thread_id ) {
		global $wpdb;
		
		$bp = buddypress();
		
		$query = $wpdb->prepare( "
			SELECT m.id as message_id, m.sender_id, m.date_sent, 
			       gm.meta_value as gift_id, p.post_title as gift_title
			FROM {$bp->messages->table_name_messages} m
			INNER JOIN {$wpdb->postmeta} gm ON m.id = gm.post_id AND gm.meta_key = 'bp_gifts_message_gift'
			INNER JOIN {$wpdb->posts} p ON gm.meta_value = p.ID
			WHERE m.thread_id = %d
			ORDER BY m.date_sent ASC
		", $thread_id );
		
		$results = $wpdb->get_results( $query );
		
		if ( empty( $results ) ) {
			return array();
		}

		$gifts = new BP_Gifts_Gifts();
		$thread_gifts = array();
		
		foreach ( $results as $result ) {
			$gift = $gifts->get_gift( $result->gift_id );
			if ( $gift ) {
				$gift->message_id = $result->message_id;
				$gift->sender_id = $result->sender_id;
				$gift->date_sent = $result->date_sent;
				$thread_gifts[] = $gift;
			}
		}
		
		return $thread_gifts;
	}

	/**
	 * Check if a message has a gift attached.
	 *
	 * @since 2.2.0
	 * @param int $message_id Message ID.
	 * @return bool True if message has gift, false otherwise.
	 */
	public function message_has_gift( $message_id ) {
		$gift_id = get_post_meta( $message_id, 'bp_gifts_message_gift', true );
		return ! empty( $gift_id );
	}

	/**
	 * Remove gift from message.
	 *
	 * @since 2.2.0
	 * @param int $message_id Message ID.
	 */
	public function remove_gift_from_message( $message_id ) {
		delete_post_meta( $message_id, 'bp_gifts_message_gift' );
	}

	/**
	 * Create a gift relationship record.
	 *
	 * @since 2.2.0
	 * @param object $message Message object.
	 * @param int    $gift_id Gift ID.
	 * @return int|false Relationship ID on success, false on failure.
	 */
	public function create_gift_relationship( $message, $gift_id ) {
		// Get message recipients
		$recipients = $this->get_message_recipients( $message->thread_id );
		
		if ( empty( $recipients ) ) {
			return false;
		}

		$relationship_ids = array();
		$sender_id = $message->sender_id;
		
		// Prepare additional data
		$additional_data = array(
			'thread_id' => $message->thread_id,
			'message_subject' => $message->subject ?? '',
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
			'ip_address' => $this->get_user_ip(),
		);

		// Create relationship for each recipient
		foreach ( $recipients as $recipient ) {
			// Skip sender (don't create relationship to self)
			if ( $recipient->user_id == $sender_id ) {
				continue;
			}

			$relationship_data = array(
				'sender_id' => $sender_id,
				'receiver_id' => $recipient->user_id,
				'message_id' => $message->id,
				'gift_id' => $gift_id,
				'date_sent' => $message->date_sent,
				'additional_data' => wp_json_encode( $additional_data ),
			);

			$relationship_id = BP_Gifts_Database::insert_relationship( $relationship_data );
			
			if ( $relationship_id ) {
				$relationship_ids[] = $relationship_id;
				
				/**
				 * Fires after a gift relationship is created.
				 *
				 * @since 2.2.0
				 * @param int $relationship_id Relationship ID.
				 * @param int $sender_id Sender user ID.
				 * @param int $receiver_id Receiver user ID.
				 * @param int $gift_id Gift ID.
				 * @param int $message_id Message ID.
				 */
				do_action( 'bp_gifts_relationship_created', $relationship_id, $sender_id, $recipient->user_id, $gift_id, $message->id );
			}
		}

		return ! empty( $relationship_ids ) ? $relationship_ids : false;
	}

	/**
	 * Get message recipients.
	 *
	 * @since 2.2.0
	 * @param int $thread_id Thread ID.
	 * @return array Array of recipient objects.
	 */
	private function get_message_recipients( $thread_id ) {
		global $wpdb;
		
		$bp = buddypress();
		
		$sql = $wpdb->prepare(
			"SELECT user_id 
			 FROM {$bp->messages->table_name_recipients} 
			 WHERE thread_id = %d",
			$thread_id
		);
		
		return $wpdb->get_results( $sql );
	}

	/**
	 * Get user IP address.
	 *
	 * @since 2.2.0
	 * @return string User IP address.
	 */
	private function get_user_ip() {
		// Check for various headers that might contain the real IP
		$ip_headers = array(
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'HTTP_CLIENT_IP',
			'REMOTE_ADDR'
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = $_SERVER[ $header ];
				// Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				// Validate IP address
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}

	/**
	 * Get gift relationships for a user.
	 *
	 * @since 2.2.0
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 * @return array Array of relationship objects.
	 */
	public function get_user_gift_relationships( $user_id, $args = array() ) {
		$defaults = array(
			'type' => 'both', // 'sent', 'received', or 'both'
			'limit' => 20,
			'offset' => 0,
		);

		$args = wp_parse_args( $args, $defaults );
		$query_args = array(
			'limit' => $args['limit'],
			'offset' => $args['offset'],
		);

		$relationships = array();

		if ( $args['type'] === 'sent' || $args['type'] === 'both' ) {
			$query_args['sender_id'] = $user_id;
			$sent = BP_Gifts_Database::get_relationships( $query_args );
			$relationships = array_merge( $relationships, $sent );
		}

		if ( $args['type'] === 'received' || $args['type'] === 'both' ) {
			$query_args = array(
				'receiver_id' => $user_id,
				'limit' => $args['limit'],
				'offset' => $args['offset'],
			);
			unset( $query_args['sender_id'] );
			$received = BP_Gifts_Database::get_relationships( $query_args );
			$relationships = array_merge( $relationships, $received );
		}

		// Sort by date if we have both sent and received
		if ( $args['type'] === 'both' && ! empty( $relationships ) ) {
			usort( $relationships, function( $a, $b ) {
				return strtotime( $b->date_sent ) - strtotime( $a->date_sent );
			});
		}

		return $relationships;
	}

	/**
	 * Extract gift data from BuddyPress cookies string.
	 *
	 * @since 2.2.0
	 * @param string $cookies_string URL-encoded cookie string from bp_get_cookies().
	 * @param int    $thread_id Thread ID to validate against.
	 * @return int|false Gift ID if found and valid, false otherwise.
	 */
	private function extract_gift_from_bp_cookies( $cookies_string, $thread_id = 0 ) {
		if ( empty( $cookies_string ) ) {
			return false;
		}

		// Decode the cookies string
		$cookies_string = urldecode( $cookies_string );
		
		// Parse the query string to get individual cookies
		parse_str( $cookies_string, $cookies );
		
		// Look for our gift cookie
		if ( ! isset( $cookies['bp_gifts_selected'] ) ) {
			return false;
		}

		// Try to decode the JSON gift data
		$gift_data = json_decode( $cookies['bp_gifts_selected'], true );
		
		if ( ! $gift_data || ! is_array( $gift_data ) ) {
			return false;
		}

		// Validate gift data structure
		if ( ! isset( $gift_data['gift_id'] ) || ! $gift_data['gift_id'] ) {
			return false;
		}

		// Validate thread ID if provided
		if ( $thread_id && isset( $gift_data['thread_id'] ) && $gift_data['thread_id'] != $thread_id ) {
			return false;
		}

		// Check if the gift data is not too old (1 hour max)
		if ( isset( $gift_data['timestamp'] ) ) {
			$age_seconds = time() - ( $gift_data['timestamp'] / 1000 ); // Convert from milliseconds
			if ( $age_seconds > 3600 ) { // 1 hour
				return false;
			}
		}

		return absint( $gift_data['gift_id'] );
	}
}