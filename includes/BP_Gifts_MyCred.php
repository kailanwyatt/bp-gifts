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
	 * @param int $user_id User ID (sender).
	 * @param int $receiver_id Receiver user ID.
	 * @param int $gift_id Gift ID.
	 * @return bool True if successful, false otherwise.
	 */
	public function charge_user_for_gift( $user_id, $receiver_id, $gift_id ) {
		if ( ! $this->is_mycred_available() ) {
			return true;
		}

		$cost = $this->get_gift_cost( $gift_id );
		if ( ! $cost ) {
			return true;
		}

		// Check if user can afford it
		if ( ! $this->can_user_afford_gift( $user_id, $gift_id ) ) {
			return false;
		}

		$point_type = BP_Gifts_Settings::get_mycred_point_type();
		$gift_title = get_the_title( $gift_id );
		$receiver_name = bp_core_get_user_displayname( $receiver_id );

		// translators: %1$s is the gift title, %2$s is the receiver's name
		$entry = sprintf(
			__( 'Sent gift "%1$s" to %2$s', 'bp-gifts' ),
			$gift_title,
			$receiver_name
		);

		if ( function_exists( 'mycred_add' ) ) {
			$result = mycred_add(
				'bp_gifts_sent',
				$user_id,
				-$cost,
				$entry,
				$gift_id,
				array( 'ref_type' => 'post', 'receiver_id' => $receiver_id ),
				$point_type
			);

			// Optional: Give bonus to receiver
			if ( $result ) {
				$this->reward_gift_receiver( $receiver_id, $gift_id, $user_id );
			}

			return $result;
		}

		return false;
	}

	/**
	 * Reward gift receiver with bonus points.
	 *
	 * @since 2.2.0
	 * @param int $receiver_id Receiver user ID.
	 * @param int $gift_id Gift ID.
	 * @param int $sender_id Sender user ID.
	 * @return bool True on success, false on failure.
	 */
	public function reward_gift_receiver( $receiver_id, $gift_id, $sender_id ) {
		if ( ! $this->is_mycred_available() ) {
			return false;
		}

		// Get bonus amount (filterable)
		$bonus = apply_filters( 'bp_gifts_receiver_bonus', 1, $gift_id, $sender_id );
		
		if ( $bonus <= 0 ) {
			return false;
		}

		$point_type = BP_Gifts_Settings::get_mycred_point_type();
		$sender_name = bp_core_get_user_displayname( $sender_id );
		$gift_title = get_the_title( $gift_id );

		// translators: %1$s is the gift title, %2$s is the sender's name
		$entry = sprintf(
			__( 'Received gift "%1$s" from %2$s', 'bp-gifts' ),
			$gift_title,
			$sender_name
		);

		if ( function_exists( 'mycred_add' ) ) {
			return mycred_add(
				'bp_gifts_received',
				$receiver_id,
				$bonus,
				$entry,
				$gift_id,
				array( 'ref_type' => 'post', 'sender_id' => $sender_id ),
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
		// Check both meta fields for compatibility
		$cost = get_post_meta( $gift_id, '_bp_gift_point_cost', true );
		if ( ! $cost ) {
			$cost = get_post_meta( $gift_id, 'gift_cost', true );
		}
		return absint( $cost );
	}

	/**
	 * Get formatted gift cost for display.
	 *
	 * @since 2.2.0
	 * @param int $gift_id Gift ID.
	 * @return string Formatted cost string.
	 */
	public function get_formatted_gift_cost( $gift_id ) {
		$cost = $this->get_gift_cost( $gift_id );
		
		if ( $cost <= 0 ) {
			return __( 'Free', 'bp-gifts' );
		}

		if ( function_exists( 'mycred_format_number' ) ) {
			return mycred_format_number( $cost );
		}

		return number_format( $cost );
	}

	/**
	 * Get all gifts with costs for JavaScript.
	 *
	 * @since 2.2.0
	 * @return array Array of gift data with costs.
	 */
	public function get_gifts_with_costs() {
		$gifts = get_posts( array(
			'post_type' => 'bp_gifts',
			'post_status' => 'publish',
			'numberposts' => -1,
		) );

		$gifts_data = array();
		foreach ( $gifts as $gift ) {
			$gifts_data[ $gift->ID ] = array(
				'cost' => $this->get_gift_cost( $gift->ID ),
				'formatted_cost' => $this->get_formatted_gift_cost( $gift->ID ),
			);
		}

		return $gifts_data;
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
	 * @param string $post_type Post type.
	 * @param WP_Post $post Post object.
	 */
	public function add_gift_cost_meta_box( $post_type = '', $post = null ) {
		// Only add for bp_gifts post type
		if ( $post_type !== 'bp_gifts' || ! $this->is_mycred_available() ) {
			return;
		}

		add_meta_box(
			'bp_gifts_mycred_cost',
			__( 'Gift Cost (myCred)', 'bp-gifts' ),
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
		$cost = $this->get_gift_cost( $post->ID );
		
		wp_nonce_field( 'bp_gifts_mycred_meta_nonce', 'bp_gifts_mycred_nonce' );
		?>
		<p>
			<label for="bp_gift_point_cost"><?php esc_html_e( 'Cost in points:', 'bp-gifts' ); ?></label>
			<input type="number" 
				   id="bp_gift_point_cost" 
				   name="bp_gift_point_cost" 
				   value="<?php echo esc_attr( $cost ); ?>" 
				   min="0" 
				   step="1" 
				   style="width: 100%;" />
		</p>
		<p class="description">
			<?php esc_html_e( 'Leave empty or set to 0 for free gifts.', 'bp-gifts' ); ?>
		</p>
		<?php
	}

	/**
	 * Save gift cost meta data.
	 *
	 * @since 2.2.0
	 * @param int $post_id Post ID.
	 */
	public function save_gift_cost_meta( $post_id ) {
		// Check if this is a gift post
		if ( get_post_type( $post_id ) !== 'bp_gifts' ) {
			return;
		}

		// Check if user has permission to edit
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check for autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check if we have the nonce field
		if ( ! isset( $_POST['bp_gifts_mycred_nonce'] ) ) {
			return;
		}

		// Verify nonce
		if ( ! wp_verify_nonce( wp_unslash( $_POST['bp_gifts_mycred_nonce'] ), 'bp_gifts_mycred_meta_nonce' ) ) {
			return;
		}

		// Save point cost
		if ( isset( $_POST['bp_gift_point_cost'] ) ) {
			$cost = max( 0, floatval( $_POST['bp_gift_point_cost'] ) );
			update_post_meta( $post_id, '_bp_gift_point_cost', $cost );
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
		
		// Add AJAX handlers
		add_action( 'wp_ajax_bp_gifts_check_balance', array( $this, 'ajax_check_balance' ) );
		add_action( 'wp_ajax_bp_gifts_get_costs', array( $this, 'ajax_get_costs' ) );
	}

	/**
	 * AJAX handler to check user balance.
	 *
	 * @since 2.2.0
	 */
	public function ajax_check_balance() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'bp_gifts_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		}

		$user_id = bp_loggedin_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => 'User not logged in' ) );
		}

		$balance = $this->get_user_balance( $user_id );
		$formatted_balance = '';
		
		if ( function_exists( 'mycred_display_users_balance' ) ) {
			$formatted_balance = mycred_display_users_balance( $user_id );
		} else {
			$formatted_balance = number_format( $balance );
		}
		
		wp_send_json_success( array( 
			'balance' => $balance,
			'formatted_balance' => $formatted_balance
		) );
	}

	/**
	 * AJAX handler to get gift costs.
	 *
	 * @since 2.2.0
	 */
	public function ajax_get_costs() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'bp_gifts_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		}

		$gifts_costs = $this->get_gifts_with_costs();
		
		wp_send_json_success( array( 
			'costs' => $gifts_costs
		) );
	}
}