<?php
/**
 * Message Service Interface
 *
 * Defines the contract for message and gift integration operations.
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
 * Interface Message_Service_Interface
 *
 * Contract for message and gift integration services.
 *
 * @since 2.1.0
 */
interface Message_Service_Interface {

	/**
	 * Attach a gift to a message.
	 *
	 * @since 2.1.0
	 * @param int $message_id Message ID.
	 * @param int $gift_id    Gift ID.
	 * @return bool True on success, false on failure.
	 */
	public function attach_gift_to_message( int $message_id, int $gift_id );

	/**
	 * Get gift attached to a message.
	 *
	 * @since 2.1.0
	 * @param int $message_id Message ID.
	 * @return array|null Gift data or null if no gift attached.
	 */
	public function get_message_gift( int $message_id );

	/**
	 * Remove gift from a message.
	 *
	 * @since 2.1.0
	 * @param int $message_id Message ID.
	 * @return bool True on success, false on failure.
	 */
	public function remove_gift_from_message( int $message_id );

	/**
	 * Get all gifts in a thread (both thread-level and message-level).
	 *
	 * @since 2.1.0
	 * @param int $thread_id Thread ID.
	 * @return array Array of gifts with metadata.
	 */
	public function get_thread_gifts( int $thread_id );

	/**
	 * Validate gift attachment permissions.
	 *
	 * @since 2.1.0
	 * @param int $user_id User ID.
	 * @param int $gift_id Gift ID.
	 * @return bool True if user can attach gift, false otherwise.
	 */
	public function can_user_attach_gift( int $user_id, int $gift_id );
}