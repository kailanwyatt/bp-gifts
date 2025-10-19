<?php
/**
 * BP Gifts Settings Integration
 *
 * Integrates BP Gifts settings with BuddyPress settings page.
 *
 * @package BP_Gifts
 * @since   2.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Gifts Settings class for BuddyPress integration.
 *
 * @since 2.1.0
 */
class BP_Gifts_Settings {

	/**
	 * Initialize settings integration.
	 *
	 * @since 2.1.0
	 */
	public static function init() {
		add_action( 'bp_register_admin_settings', array( __CLASS__, 'register_settings' ) );
		add_action( 'bp_register_admin_settings', array( __CLASS__, 'register_admin_fields' ), 99 );
	}

	/**
	 * Register BP Gifts settings.
	 *
	 * @since 2.1.0
	 */
	public static function register_settings() {
		// Register settings
		register_setting( 'buddypress', 'bp_gifts_enable_gifts', array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
		));

		register_setting( 'buddypress', 'bp_gifts_enable_user_tab', array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
		));

		register_setting( 'buddypress', 'bp_gifts_mycred_enabled', array(
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
		));

		register_setting( 'buddypress', 'bp_gifts_mycred_point_type', array(
			'type'              => 'string',
			'default'           => 'mycred_default',
			'sanitize_callback' => 'sanitize_text_field',
		));
	}

	/**
	 * Register admin fields and settings section.
	 *
	 * @since 2.1.0
	 */
	public static function register_admin_fields() {
		// Add BP Gifts settings section
		add_settings_section( 
			'bp_gifts', 
			__( 'Gifts', 'bp-gifts' ), 
			array( __CLASS__, 'settings_section_callback' ), 
			'buddypress' 
		);

		// Add enable gifts field
		add_settings_field( 
			'bp_gifts_enable_gifts', 
			__( 'Enable Gifts', 'bp-gifts' ), 
			array( __CLASS__, 'enable_gifts_field_callback' ), 
			'buddypress', 
			'bp_gifts' 
		);

		// Add user tab field
		add_settings_field( 
			'bp_gifts_enable_user_tab', 
			__( 'User Profile Tab', 'bp-gifts' ), 
			array( __CLASS__, 'user_tab_field_callback' ), 
			'buddypress', 
			'bp_gifts' 
		);

		// Add myCred integration fields if myCred is available
		if ( self::is_mycred_available() ) {
			add_settings_field( 
				'bp_gifts_mycred_enabled', 
				__( 'myCred Integration', 'bp-gifts' ), 
				array( __CLASS__, 'mycred_enabled_field_callback' ), 
				'buddypress', 
				'bp_gifts' 
			);

			add_settings_field( 
				'bp_gifts_mycred_point_type', 
				__( 'Point Type', 'bp-gifts' ), 
				array( __CLASS__, 'mycred_point_type_field_callback' ), 
				'buddypress', 
				'bp_gifts' 
			);
		}
	}

	/**
	 * Settings section callback.
	 *
	 * @since 2.1.0
	 */
	public static function settings_section_callback() {
		?>
		<p><?php esc_html_e( 'Configure BP Gifts plugin settings and user interface options.', 'bp-gifts' ); ?></p>
		<?php
	}

	/**
	 * Enable gifts field callback.
	 *
	 * @since 2.1.0
	 */
	public static function enable_gifts_field_callback() {
		?>
		<label for="bp-gifts-enable-gifts">
			<input 
				type="checkbox" 
				id="bp-gifts-enable-gifts" 
				name="bp_gifts_enable_gifts" 
				value="1" 
				<?php checked( self::is_gifts_enabled() ); ?> 
			/>
			<?php esc_html_e( 'Allow users to send and receive gifts through messages', 'bp-gifts' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, users can attach gifts to their BuddyPress messages. Disable this to hide all gift functionality.', 'bp-gifts' ); ?>
		</p>
		<?php
	}

	/**
	 * User tab field callback.
	 *
	 * @since 2.1.0
	 */
	public static function user_tab_field_callback() {
		?>
		<label for="bp-gifts-enable-user-tab">
			<input 
				type="checkbox" 
				id="bp-gifts-enable-user-tab" 
				name="bp_gifts_enable_user_tab" 
				value="1" 
				<?php checked( self::is_user_tab_enabled() ); ?> 
			/>
			<?php esc_html_e( 'Add a "Gifts" tab to user profiles', 'bp-gifts' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, users will see a "Gifts" tab on their own profile where they can view their gift history.', 'bp-gifts' ); ?>
		</p>
		<?php
	}

	/**
	 * myCred enabled field callback.
	 *
	 * @since 2.1.0
	 */
	public static function mycred_enabled_field_callback() {
		?>
		<label for="bp-gifts-mycred-enabled">
			<input 
				type="checkbox" 
				id="bp-gifts-mycred-enabled" 
				name="bp_gifts_mycred_enabled" 
				value="1" 
				<?php checked( self::is_mycred_enabled() ); ?> 
				<?php disabled( ! self::is_mycred_available() ); ?>
			/>
			<?php esc_html_e( 'Allow gifts to cost points using myCred', 'bp-gifts' ); ?>
		</label>
		<p class="description">
			<?php 
			if ( self::is_mycred_available() ) {
				esc_html_e( 'When enabled, gifts can have point costs and users must spend points to send them.', 'bp-gifts' );
			} else {
				echo '<strong>' . esc_html__( 'myCred plugin is required for this feature.', 'bp-gifts' ) . '</strong>';
			}
			?>
		</p>
		<?php
	}

	/**
	 * myCred point type field callback.
	 *
	 * @since 2.1.0
	 */
	public static function mycred_point_type_field_callback() {
		?>
		<select id="bp-gifts-mycred-point-type" name="bp_gifts_mycred_point_type">
			<?php
			$current_type = self::get_mycred_point_type();
			$point_types = self::get_mycred_point_types();
			foreach ( $point_types as $type_id => $type_name ) :
			?>
				<option value="<?php echo esc_attr( $type_id ); ?>" <?php selected( $current_type, $type_id ); ?>>
					<?php echo esc_html( $type_name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select which myCred point type to use for gift costs.', 'bp-gifts' ); ?>
		</p>
		<?php
	}

	/**
	 * Check if gifts are enabled.
	 *
	 * @since 2.1.0
	 * @return bool True if gifts are enabled, false otherwise.
	 */
	public static function is_gifts_enabled() {
		return (bool) get_option( 'bp_gifts_enable_gifts', true );
	}

	/**
	 * Check if user gifts tab is enabled.
	 *
	 * @since 2.1.0
	 * @return bool True if user tab is enabled, false otherwise.
	 */
	public static function is_user_tab_enabled() {
		return (bool) get_option( 'bp_gifts_enable_user_tab', true );
	}

	/**
	 * Check if gifts functionality should be available.
	 *
	 * @since 2.1.0
	 * @return bool True if gifts should be available, false otherwise.
	 */
	public static function is_gifts_available() {
		return self::is_gifts_enabled() && function_exists( 'bp_is_active' ) && bp_is_active( 'messages' );
	}

	/**
	 * Check if myCred integration is enabled.
	 *
	 * @since 2.1.0
	 * @return bool True if myCred integration is enabled, false otherwise.
	 */
	public static function is_mycred_enabled() {
		return (bool) get_option( 'bp_gifts_mycred_enabled', false ) && self::is_mycred_available();
	}

	/**
	 * Check if myCred plugin is available and active.
	 *
	 * @since 2.1.0
	 * @return bool True if myCred is available, false otherwise.
	 */
	public static function is_mycred_available() {
		return function_exists( 'mycred' ) || class_exists( 'myCRED_Core' );
	}

	/**
	 * Get the selected myCred point type.
	 *
	 * @since 2.1.0
	 * @return string Point type ID.
	 */
	public static function get_mycred_point_type() {
		return get_option( 'bp_gifts_mycred_point_type', 'mycred_default' );
	}

	/**
	 * Get the default myCred point type (alias for get_mycred_point_type).
	 *
	 * @since 2.1.0
	 * @return string Point type ID.
	 */
	public static function get_default_point_type() {
		return self::get_mycred_point_type();
	}

	/**
	 * Get available myCred point types.
	 *
	 * @since 2.1.0
	 * @return array Array of point type ID => name pairs.
	 */
	public static function get_mycred_point_types() {
		if ( ! self::is_mycred_available() ) {
			return array();
		}

		$types = array();

		// Get myCred point types
		if ( function_exists( 'mycred_get_types' ) ) {
			$mycred_types = mycred_get_types();
			if ( ! empty( $mycred_types ) && is_array( $mycred_types ) ) {
				foreach ( $mycred_types as $type_id => $type_name ) {
					$types[ $type_id ] = $type_name;
				}
			}
		} else {
			// Fallback for older myCred versions
			$types['mycred_default'] = __( 'Points', 'bp-gifts' );
		}

		return $types;
	}
}