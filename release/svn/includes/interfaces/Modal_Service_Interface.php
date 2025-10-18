<?php
/**
 * Modal Service Interface
 *
 * Defines the contract for modal and UI rendering operations.
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
 * Interface Modal_Service_Interface
 *
 * Contract for modal and UI rendering services.
 *
 * @since 2.1.0
 */
interface Modal_Service_Interface {

	/**
	 * Render the gift composer interface.
	 *
	 * @since 2.1.0
	 * @param array $args Rendering arguments.
	 * @return string HTML output.
	 */
	public function render_gift_composer( array $args = array() );

	/**
	 * Render the gift modal.
	 *
	 * @since 2.1.0
	 * @param array $gifts Array of gifts to display.
	 * @param array $args  Rendering arguments.
	 * @return string HTML output.
	 */
	public function render_gift_modal( array $gifts, array $args = array() );

	/**
	 * Render a single gift item.
	 *
	 * @since 2.1.0
	 * @param array $gift Gift data.
	 * @param array $args Rendering arguments.
	 * @return string HTML output.
	 */
	public function render_gift_item( array $gift, array $args = array() );

	/**
	 * Render gift display in message.
	 *
	 * @since 2.1.0
	 * @param array $gift Gift data.
	 * @param array $args Rendering arguments.
	 * @return string HTML output.
	 */
	public function render_message_gift( array $gift, array $args = array() );

	/**
	 * Render all gifts in a thread.
	 *
	 * @since 2.1.0
	 * @param array $gifts Array of gifts with metadata.
	 * @return string HTML output.
	 */
	public function render_thread_gifts( array $gifts );

	/**
	 * Render search and filter controls.
	 *
	 * @since 2.1.0
	 * @param array $categories Available categories.
	 * @param array $args       Rendering arguments.
	 * @return string HTML output.
	 */
	public function render_search_controls( array $categories, array $args = array() );
}