<?php
/**
 * BP Gifts - myCred Integration Class
 *
 * Handles myCred points integration.
 *
 * @package BP_Gifts
 * @since   2.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Gifts myCred class.
 *
 * @since 2.2.0
 */
class BP_Gifts_MyCred {

	/**
	 * Check if user can afford a gift.
	 *
	 * @since 2.2.0
	 * @param int $user_id User ID.
	 * @param int $gift_id Gift ID.
	 * @return bool True if user can afford, false otherwise.
	 */
	public function can_user_afford_gift( $user_id, $gift_id ) {
		if ( ! $this->is_mycred_available() ) {
			return true;
		}

		$cost = $this->get_gift_cost( $gift_id );
		if ( ! $cost ) {
			return true;
		}

		$user_balance = $this->get_user_balance( $user_id );
		return $user_balance >= $cost;
	}

	/**
	 * Charge user for a gift.
	 *
	 * @since 2.2.0
	 * @param int $user_id User ID.
	 * @param int $gift_id Gift ID.
	 * @return bool True if successful, false otherwise.
	 */
	public function charge_user_for_gift( $user_id, $gift_id ) {
		if ( ! $this->is_mycred_available() ) {
			return true;
		}

		$cost = $this->get_gift_cost( $gift_id );
		if ( ! $cost ) {
			return true;
		}

		$point_type = BP_Gifts_Settings::get_mycred_point_type();
		$gift_title = get_the_title( $gift_id );

		$entry = sprintf(
			__( 'Sent gift: %s', 'bp-gifts' ),
			$gift_title
		);

		if ( function_exists( 'mycred_add' ) ) {
			return mycred_add(
				'gift_sent',
				$user_id,
				-$cost,
				$entry,
				'',
				'',
				$point_type
			);
		}

		return false;
	}

	/**
	 * Get gift cost.
	 *
	 * @since 2.2.0
	 * @param int $gift_id Gift ID.
	 * @return int Gift cost in points.
	 */
	public function get_gift_cost( $gift_id ) {
		$cost = get_post_meta( $gift_id, 'gift_cost', true );
		return absint( $cost );
	}

	/**
	 * Get user's point balance.
	 *
	 * @since 2.2.0
	 * @param int $user_id User ID.
	 * @return int User balance.
	 */
	public function get_user_balance( $user_id ) {
		if ( ! $this->is_mycred_available() ) {
			return 0;
		}

		$point_type = BP_Gifts_Settings::get_mycred_point_type();
		
		if ( function_exists( 'mycred_get_users_balance' ) ) {
			return mycred_get_users_balance( $user_id, $point_type );
		}

		return 0;
	}

	/**
	 * Check if myCred is available.
	 *
	 * @since 2.2.0
	 * @return bool True if available, false otherwise.
	 */
	public function is_mycred_available() {
		return function_exists( 'mycred' ) || class_exists( 'myCRED_Core' );
	}

	/**
	 * Add gift cost field to gift edit screen.
	 *
	 * @since 2.2.0
	 * @param WP_Post $post Post object.
	 */
	public function add_gift_cost_meta_box( $post ) {
		if ( $post->post_type !== 'bp_gifts' || ! $this->is_mycred_available() ) {
			return;
		}

		add_meta_box(
			'bp_gifts_cost',
			__( 'Gift Cost', 'bp-gifts' ),
			array( $this, 'render_cost_meta_box' ),
			'bp_gifts',
			'side',
			'default'
		);
	}

	/**
	 * Render the cost meta box.
	 *
	 * @since 2.2.0
	 * @param WP_Post $post Post object.
	 */
	public function render_cost_meta_box( $post ) {
		$cost = get_post_meta( $post->ID, 'gift_cost', true );
		$point_type = BP_Gifts_Settings::get_mycred_point_type();
		
		wp_nonce_field( 'bp_gifts_cost_nonce', 'bp_gifts_cost_nonce' );
		?>
		<p>
			<label for="gift_cost"><?php esc_html_e( 'Cost in points:', 'bp-gifts' ); ?></label>
			<input type="number" id="gift_cost" name="gift_cost" value="<?php echo esc_attr( $cost ); ?>" min="0" step="1" />
		</p>
		<p class="description">
			<?php
			printf(
				esc_html__( 'Leave empty or set to 0 for free gifts. Point type: %s', 'bp-gifts' ),
				esc_html( $point_type )
			);
			?>
		</p>
		<?php
	}

	/**
	 * Save gift cost meta.
	 *
	 * @since 2.2.0
	 * @param int $post_id Post ID.
	 */
	public function save_gift_cost_meta( $post_id ) {
		if ( ! isset( $_POST['bp_gifts_cost_nonce'] ) || ! wp_verify_nonce( $_POST['bp_gifts_cost_nonce'], 'bp_gifts_cost_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['gift_cost'] ) ) {
			$cost = absint( $_POST['gift_cost'] );
			update_post_meta( $post_id, 'gift_cost', $cost );
		}
	}

	/**
	 * Initialize myCred hooks.
	 *
	 * @since 2.2.0
	 */
	public function init_hooks() {
		if ( ! $this->is_mycred_available() || ! BP_Gifts_Settings::is_mycred_enabled() ) {
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'add_gift_cost_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_gift_cost_meta' ) );
	}
}