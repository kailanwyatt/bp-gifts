<?php
/**
 * myCred Service Implementation
 *
 * Handles myCred integration for point-based gift costs and transactions.
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
 * Class BP_Gifts_MyCred_Service
 *
 * Implementation of myCred integration service.
 *
 * @since 2.1.0
 */
class BP_Gifts_MyCred_Service {

	/**
	 * myCred instance.
	 *
	 * @since 2.1.0
	 * @var   object
	 */
	private $mycred;

	/**
	 * Point type ID.
	 *
	 * @since 2.1.0
	 * @var   string
	 */
	private $point_type;

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		$this->point_type = BP_Gifts_Settings::get_mycred_point_type();
		
		if ( $this->is_available() ) {
			$this->mycred = mycred( $this->point_type );
		}
	}

	/**
	 * Check if myCred is available and integration is enabled.
	 *
	 * @since 2.1.0
	 * @return bool True if available, false otherwise.
	 */
	public function is_available() {
		return BP_Gifts_Settings::is_mycred_enabled() && 
			   BP_Gifts_Settings::is_mycred_available();
	}

	/**
	 * Get user's point balance.
	 *
	 * @since 2.1.0
	 * @param int $user_id User ID.
	 * @return int|float User's point balance.
	 */
	public function get_user_balance( $user_id ) {
		if ( ! $this->is_available() || ! $user_id ) {
			return 0;
		}

		return $this->mycred->get_users_balance( $user_id, $this->point_type );
	}

	/**
	 * Check if user has sufficient points for a cost.
	 *
	 * @since 2.1.0
	 * @param int        $user_id User ID.
	 * @param int|float  $cost    Required points.
	 * @return bool True if user has sufficient points, false otherwise.
	 */
	public function user_can_afford( $user_id, $cost ) {
		if ( ! $this->is_available() || $cost <= 0 ) {
			return true;
		}

		$balance = $this->get_user_balance( $user_id );
		return $balance >= $cost;
	}

	/**
	 * Deduct points from user for gift cost.
	 *
	 * @since 2.1.0
	 * @param int        $user_id User ID.
	 * @param int|float  $cost    Points to deduct.
	 * @param int        $gift_id Gift ID (for reference).
	 * @param array      $data    Additional transaction data.
	 * @return bool True on success, false on failure.
	 */
	public function deduct_points( $user_id, $cost, $gift_id = 0, $data = array() ) {
		if ( ! $this->is_available() || $cost <= 0 ) {
			return true;
		}

		// Check if user can afford the cost
		if ( ! $this->user_can_afford( $user_id, $cost ) ) {
			return false;
		}

		// Prepare transaction data
		$entry_data = wp_parse_args( $data, array(
			'user_id' => $user_id,
			'type'    => 'bp_gift_sent',
			'amount'  => -abs( $cost ), // Negative for deduction
			'entry'   => sprintf(
				/* translators: 1: gift name, 2: cost */
				__( 'Sent gift: %1$s (Cost: %2$s)', 'bp-gifts' ),
				isset( $data['gift_name'] ) ? $data['gift_name'] : __( 'Unknown Gift', 'bp-gifts' ),
				$this->format_points( $cost )
			),
			'ref'     => 'bp_gift_purchase',
			'ref_id'  => $gift_id,
			'data'    => array(
				'gift_id'     => $gift_id,
				'gift_name'   => isset( $data['gift_name'] ) ? $data['gift_name'] : '',
				'recipient'   => isset( $data['recipient_id'] ) ? $data['recipient_id'] : 0,
				'message_id'  => isset( $data['message_id'] ) ? $data['message_id'] : 0,
				'thread_id'   => isset( $data['thread_id'] ) ? $data['thread_id'] : 0,
			)
		));

		// Execute the transaction
		$result = $this->mycred->add_creds(
			$entry_data['type'],
			$entry_data['user_id'],
			$entry_data['amount'],
			$entry_data['entry'],
			$entry_data['ref_id'],
			$entry_data['ref'],
			$this->point_type
		);

		/**
		 * Fires after points are deducted for a gift.
		 *
		 * @since 2.1.0
		 * @param int        $user_id User ID.
		 * @param int|float  $cost    Points deducted.
		 * @param int        $gift_id Gift ID.
		 * @param bool       $result  Transaction result.
		 * @param array      $data    Transaction data.
		 */
		do_action( 'bp_gifts_points_deducted', $user_id, $cost, $gift_id, $result, $entry_data );

		return $result !== false;
	}

	/**
	 * Award points to user for receiving a gift.
	 *
	 * @since 2.1.0
	 * @param int        $user_id User ID.
	 * @param int|float  $amount  Points to award.
	 * @param int        $gift_id Gift ID (for reference).
	 * @param array      $data    Additional transaction data.
	 * @return bool True on success, false on failure.
	 */
	public function award_points( $user_id, $amount, $gift_id = 0, $data = array() ) {
		if ( ! $this->is_available() || $amount <= 0 ) {
			return true;
		}

		// Prepare transaction data
		$entry_data = wp_parse_args( $data, array(
			'user_id' => $user_id,
			'type'    => 'bp_gift_received',
			'amount'  => abs( $amount ), // Positive for award
			'entry'   => sprintf(
				/* translators: 1: gift name, 2: amount */
				__( 'Received gift: %1$s (Bonus: %2$s)', 'bp-gifts' ),
				isset( $data['gift_name'] ) ? $data['gift_name'] : __( 'Unknown Gift', 'bp-gifts' ),
				$this->format_points( $amount )
			),
			'ref'     => 'bp_gift_received',
			'ref_id'  => $gift_id,
			'data'    => array(
				'gift_id'     => $gift_id,
				'gift_name'   => isset( $data['gift_name'] ) ? $data['gift_name'] : '',
				'sender'      => isset( $data['sender_id'] ) ? $data['sender_id'] : 0,
				'message_id'  => isset( $data['message_id'] ) ? $data['message_id'] : 0,
				'thread_id'   => isset( $data['thread_id'] ) ? $data['thread_id'] : 0,
			)
		));

		// Execute the transaction
		$result = $this->mycred->add_creds(
			$entry_data['type'],
			$entry_data['user_id'],
			$entry_data['amount'],
			$entry_data['entry'],
			$entry_data['ref_id'],
			$entry_data['ref'],
			$this->point_type
		);

		/**
		 * Fires after points are awarded for receiving a gift.
		 *
		 * @since 2.1.0
		 * @param int        $user_id User ID.
		 * @param int|float  $amount  Points awarded.
		 * @param int        $gift_id Gift ID.
		 * @param bool       $result  Transaction result.
		 * @param array      $data    Transaction data.
		 */
		do_action( 'bp_gifts_points_awarded', $user_id, $amount, $gift_id, $result, $entry_data );

		return $result !== false;
	}

	/**
	 * Get gift point cost.
	 *
	 * @since 2.1.0
	 * @param int $gift_id Gift ID.
	 * @return int|float Gift point cost, 0 if free or myCred disabled.
	 */
	public function get_gift_cost( $gift_id ) {
		if ( ! $this->is_available() || ! $gift_id ) {
			return 0;
		}

		$cost = get_post_meta( $gift_id, '_bp_gift_point_cost', true );
		return max( 0, floatval( $cost ) );
	}

	/**
	 * Set gift point cost.
	 *
	 * @since 2.1.0
	 * @param int        $gift_id Gift ID.
	 * @param int|float  $cost    Point cost to set.
	 * @return bool True on success, false on failure.
	 */
	public function set_gift_cost( $gift_id, $cost ) {
		if ( ! $gift_id ) {
			return false;
		}

		$cost = max( 0, floatval( $cost ) );
		return update_post_meta( $gift_id, '_bp_gift_point_cost', $cost );
	}

	/**
	 * Format points for display.
	 *
	 * @since 2.1.0
	 * @param int|float  $points Points to format.
	 * @param bool       $show_zero Whether to show zero values.
	 * @return string Formatted points string.
	 */
	public function format_points( $points, $show_zero = true ) {
		if ( ! $this->is_available() ) {
			return '';
		}

		if ( ! $show_zero && $points == 0 ) {
			return __( 'Free', 'bp-gifts' );
		}

		// Use myCred's formatting if available
		if ( method_exists( $this->mycred, 'format_creds' ) ) {
			return $this->mycred->format_creds( $points );
		}

		// Fallback formatting
		$singular = $this->get_point_type_name( true );
		$plural = $this->get_point_type_name( false );
		
		return sprintf(
			'%s %s',
			number_format( $points ),
			( abs( $points ) == 1 ) ? $singular : $plural
		);
	}

	/**
	 * Get point type name.
	 *
	 * @since 2.1.0
	 * @param bool $singular Whether to get singular form.
	 * @return string Point type name.
	 */
	public function get_point_type_name( $singular = false ) {
		if ( ! $this->is_available() ) {
			return $singular ? __( 'Point', 'bp-gifts' ) : __( 'Points', 'bp-gifts' );
		}

		// Get from myCred settings
		if ( isset( $this->mycred->singular ) && isset( $this->mycred->plural ) ) {
			return $singular ? $this->mycred->singular : $this->mycred->plural;
		}

		// Fallback
		return $singular ? __( 'Point', 'bp-gifts' ) : __( 'Points', 'bp-gifts' );
	}

	/**
	 * Get user's gift transaction history.
	 *
	 * @since 2.1.0
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 * @return array Array of transaction records.
	 */
	public function get_user_gift_transactions( $user_id, $args = array() ) {
		if ( ! $this->is_available() || ! $user_id ) {
			return array();
		}

		$defaults = array(
			'user_id' => $user_id,
			'ref'     => array( 'bp_gift_purchase', 'bp_gift_received' ),
			'number'  => 20,
			'offset'  => 0,
			'order'   => 'DESC'
		);

		$args = wp_parse_args( $args, $defaults );

		// Use myCred's log query if available
		if ( function_exists( 'mycred_get_log' ) ) {
			return mycred_get_log( $args );
		}

		return array();
	}
}