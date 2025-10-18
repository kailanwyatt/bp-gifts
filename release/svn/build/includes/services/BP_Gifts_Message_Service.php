<?php
/**
 * Message Service Implementation
 *
 * Handles message and gift integration operations.
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
 * Class BP_Gifts_Message_Service
 *
 * Implementation of message and gift integration service.
 *
 * @since 2.1.0
 */
class BP_Gifts_Message_Service implements Message_Service_Interface {

	/**
	 * Gift service instance.
	 *
	 * @since 2.1.0
	 * @var   Gift_Service_Interface
	 */
	private $gift_service;

	/**
	 * Meta key for gift attachment.
	 *
	 * @since 2.1.0
	 * @var   string
	 */
	private $meta_key = '_bp_gift';

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 * @param Gift_Service_Interface $gift_service Gift service instance.
	 */
	public function __construct( Gift_Service_Interface $gift_service ) {
		$this->gift_service = $gift_service;
	}

	/**
	 * Attach a gift to a message thread.
	 *
	 * @since 2.1.0
	 * @param int $thread_id Thread ID.
	 * @param int $gift_id   Gift ID.
	 * @return bool True on success, false on failure.
	 */
	public function attach_gift_to_thread( int $thread_id, int $gift_id ) {
		// Validate inputs
		if ( $thread_id <= 0 || $gift_id <= 0 ) {
			return false;
		}

		// Validate gift exists
		if ( ! $this->gift_service->is_valid_gift( $gift_id ) ) {
			return false;
		}

		// Check if user can attach gift
		$current_user_id = get_current_user_id();
		if ( ! $this->can_user_attach_gift( $current_user_id, $gift_id ) ) {
			return false;
		}

		// Verify user has access to the thread
		if ( ! $this->can_user_access_thread( $current_user_id, $thread_id ) ) {
			return false;
		}

		// Attach gift to thread using WordPress metadata
		$result = add_metadata( 'bp_messages_thread', $thread_id, '_bp_thread_gift', $gift_id, true );
		
		// Store sender information
		if ( $result !== false ) {
			add_metadata( 'bp_messages_thread', $thread_id, '_bp_thread_gift_sender', $current_user_id, true );
			add_metadata( 'bp_messages_thread', $thread_id, '_bp_thread_gift_date', current_time( 'mysql' ), true );
		}

		/**
		 * Fires after a gift is attached to a thread.
		 *
		 * @since 2.1.0
		 * @param int  $thread_id Thread ID.
		 * @param int  $gift_id   Gift ID.
		 * @param bool $result    Attachment result.
		 */
		do_action( 'bp_gifts_gift_attached_to_thread', $thread_id, $gift_id, $result );

		return $result !== false;
	}

	/**
	 * Get gift attached to a thread.
	 *
	 * @since 2.1.0
	 * @param int $thread_id Thread ID.
	 * @return array|null Gift data or null if no gift attached.
	 */
	public function get_thread_gift( int $thread_id ) {
		if ( $thread_id <= 0 ) {
			return null;
		}

		$gift_id = get_metadata( 'bp_messages_thread', $thread_id, '_bp_thread_gift', true );
		
		if ( ! $gift_id ) {
			return null;
		}

		$gift_id = absint( $gift_id );
		
		return $this->gift_service->get_gift( $gift_id );
	}

	/**
	 * Get all gifts in a thread (both thread-level and message-level).
	 *
	 * @since 2.1.0
	 * @param int $thread_id Thread ID.
	 * @return array Array of gifts with metadata.
	 */
	public function get_thread_gifts( int $thread_id ) {
		if ( $thread_id <= 0 ) {
			return array();
		}

		$gifts = array();

		// Get thread-level gift if any
		$thread_gift_id = get_metadata( 'bp_messages_thread', $thread_id, '_bp_thread_gift', true );
		if ( $thread_gift_id ) {
			$gift = $this->gift_service->get_gift( absint( $thread_gift_id ) );
			if ( $gift ) {
				$gifts[] = array(
					'gift' => $gift,
					'type' => 'thread',
					'sender_id' => get_metadata( 'bp_messages_thread', $thread_id, '_bp_thread_gift_sender', true ),
					'sent_date' => get_metadata( 'bp_messages_thread', $thread_id, '_bp_thread_gift_date', true )
				);
			}
		}

		// Get all messages in the thread
		$messages = BP_Messages_Thread::get_messages( $thread_id );
		
		if ( ! empty( $messages ) ) {
			foreach ( $messages as $message ) {
				$gift_id = get_metadata( 'bp_messages_message', $message->id, '_bp_message_gift', true );
				if ( $gift_id ) {
					$gift = $this->gift_service->get_gift( absint( $gift_id ) );
					if ( $gift ) {
						$gifts[] = array(
							'gift' => $gift,
							'type' => 'message',
							'message_id' => $message->id,
							'sender_id' => $message->sender_id,
							'sent_date' => $message->date_sent
						);
					}
				}
			}
		}

		return $gifts;
	}

	/**
	 * Check if user can access a message thread.
	 *
	 * @since 2.1.0
	 * @param int $user_id   User ID.
	 * @param int $thread_id Thread ID.
	 * @return bool True if user can access thread, false otherwise.
	 */
	public function can_user_access_thread( int $user_id, int $thread_id ) {
		if ( $user_id <= 0 || $thread_id <= 0 ) {
			return false;
		}

		// Check if user is a recipient of the thread
		$thread = new BP_Messages_Thread( $thread_id );
		
		if ( empty( $thread->recipients ) ) {
			return false;
		}

		// Check if current user is among recipients
		$recipient_ids = array_keys( $thread->recipients );
		
		return in_array( $user_id, $recipient_ids, true );
	}

	/**
	 * Attach a gift to a message.
	 *
	 * @since 2.1.0
	 * @param int $message_id Message ID.
	 * @param int $gift_id    Gift ID.
	 * @return bool True on success, false on failure.
	 */
	public function attach_gift_to_message( int $message_id, int $gift_id ) {
		// Validate inputs
		if ( $message_id <= 0 || $gift_id <= 0 ) {
			return false;
		}

		// Validate gift exists
		if ( ! $this->gift_service->is_valid_gift( $gift_id ) ) {
			return false;
		}

		// Check if user can attach gift
		$current_user_id = get_current_user_id();
		if ( ! $this->can_user_attach_gift( $current_user_id, $gift_id ) ) {
			return false;
		}

		// Attach gift to message
		$result = bp_messages_update_meta( $message_id, $this->meta_key, $gift_id );
		
		// Store sender information
		if ( $result !== false ) {
			bp_messages_update_meta( $message_id, '_bp_message_gift_sender', $current_user_id );
			bp_messages_update_meta( $message_id, '_bp_message_gift_date', current_time( 'mysql' ) );
		}

		/**
		 * Fires after a gift is attached to a message.
		 *
		 * @since 2.1.0
		 * @param int  $message_id Message ID.
		 * @param int  $gift_id    Gift ID.
		 * @param bool $result     Attachment result.
		 */
		do_action( 'bp_gifts_gift_attached', $message_id, $gift_id, $result );

		return $result !== false;
	}

	/**
	 * Get gift attached to a message.
	 *
	 * @since 2.1.0
	 * @param int $message_id Message ID.
	 * @return array|null Gift data or null if no gift attached.
	 */
	public function get_message_gift( int $message_id ) {
		if ( $message_id <= 0 ) {
			return null;
		}

		$gift_id = bp_messages_get_meta( $message_id, $this->meta_key, true );
		
		if ( ! $gift_id ) {
			return null;
		}

		$gift_id = absint( $gift_id );
		
		return $this->gift_service->get_gift( $gift_id );
	}

	/**
	 * Remove gift from a message.
	 *
	 * @since 2.1.0
	 * @param int $message_id Message ID.
	 * @return bool True on success, false on failure.
	 */
	public function remove_gift_from_message( int $message_id ) {
		if ( $message_id <= 0 ) {
			return false;
		}

		$result = bp_messages_delete_meta( $message_id, $this->meta_key );

		/**
		 * Fires after a gift is removed from a message.
		 *
		 * @since 2.1.0
		 * @param int  $message_id Message ID.
		 * @param bool $result     Removal result.
		 */
		do_action( 'bp_gifts_gift_removed', $message_id, $result );

		return $result !== false;
	}

	/**
	 * Validate gift attachment permissions.
	 *
	 * @since 2.1.0
	 * @param int $user_id User ID.
	 * @param int $gift_id Gift ID.
	 * @return bool True if user can attach gift, false otherwise.
	 */
	public function can_user_attach_gift( int $user_id, int $gift_id ) {
		// Check if user is logged in
		if ( $user_id <= 0 ) {
			return false;
		}

		// Check if BuddyPress messages component is active
		if ( ! bp_is_active( 'messages' ) ) {
			return false;
		}

		// Check if user can send messages
		if ( ! bp_core_can_edit_settings() ) {
			return false;
		}

		// Check if gift exists and is valid
		if ( ! $this->gift_service->is_valid_gift( $gift_id ) ) {
			return false;
		}

		/**
		 * Filter gift attachment permissions.
		 *
		 * @since 2.1.0
		 * @param bool $can_attach Whether user can attach gift.
		 * @param int  $user_id    User ID.
		 * @param int  $gift_id    Gift ID.
		 */
		return apply_filters( 'bp_gifts_can_user_attach_gift', true, $user_id, $gift_id );
	}

	/**
	 * Process gift attachment from form submission.
	 *
	 * @since 2.1.0
	 * @param object $message Message object from BuddyPress.
	 * @return bool True if gift was processed, false otherwise.
	 */
	public function process_gift_from_submission( $message ) {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'send_message' ) ) {
			return false;
		}

		// Check if gift ID is provided
		if ( ! isset( $_POST['bp_gift_id'] ) || ! is_numeric( $_POST['bp_gift_id'] ) ) {
			return false;
		}

		$gift_id = absint( $_POST['bp_gift_id'] );
		
		// Check if this should be attached to thread instead of message
		if ( isset( $_POST['bp_gift_thread_id'] ) && is_numeric( $_POST['bp_gift_thread_id'] ) ) {
			$thread_id = absint( $_POST['bp_gift_thread_id'] );
			return $this->attach_gift_to_thread( $thread_id, $gift_id );
		}
		
		return $this->attach_gift_to_message( $message->id, $gift_id );
	}
}