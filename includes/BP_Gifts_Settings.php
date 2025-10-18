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
		add_action( 'bp_admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'bp_admin_setting_components_tabs', array( __CLASS__, 'add_settings_tab' ) );
		add_action( 'bp_admin_setting_components_form', array( __CLASS__, 'add_settings_fields' ) );
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
	 * Add BP Gifts tab to BuddyPress settings.
	 *
	 * @since 2.1.0
	 */
	public static function add_settings_tab() {
		?>
		<li><a href="#bp-gifts-settings" class="nav-tab"><?php esc_html_e( 'Gifts', 'bp-gifts' ); ?></a></li>
		<?php
	}

	/**
	 * Add BP Gifts settings fields.
	 *
	 * @since 2.1.0
	 */
	public static function add_settings_fields() {
		?>
		<div id="bp-gifts-settings" class="bp-admin-card section">
			<h2><?php esc_html_e( 'Gifts Settings', 'bp-gifts' ); ?></h2>
			<p><?php esc_html_e( 'Configure BP Gifts plugin settings and user interface options.', 'bp-gifts' ); ?></p>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="bp-gifts-enable-gifts">
								<?php esc_html_e( 'Enable Gifts', 'bp-gifts' ); ?>
							</label>
						</th>
						<td>
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
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="bp-gifts-enable-user-tab">
								<?php esc_html_e( 'Enable User Gifts Tab', 'bp-gifts' ); ?>
							</label>
						</th>
						<td>
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
								<?php esc_html_e( 'When enabled, users will see a "Gifts" tab on their own profile where they can view their gift history. Only visible to the profile owner.', 'bp-gifts' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="bp-gifts-mycred-enabled">
								<?php esc_html_e( 'Enable myCred Integration', 'bp-gifts' ); ?>
							</label>
						</th>
						<td>
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
						</td>
					</tr>

					<?php if ( self::is_mycred_enabled() && self::is_mycred_available() ) : ?>
					<tr>
						<th scope="row">
							<label for="bp-gifts-mycred-point-type">
								<?php esc_html_e( 'Point Type', 'bp-gifts' ); ?>
							</label>
						</th>
						<td>
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
						</td>
					</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<div class="bp-gifts-settings-info">
				<h3><?php esc_html_e( 'Additional Information', 'bp-gifts' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Gifts can also be displayed using the [bp_user_gifts] shortcode on any page', 'bp-gifts' ); ?></li>
					<li><?php esc_html_e( 'Gift categories can be managed under Gifts > Gift Categories', 'bp-gifts' ); ?></li>
					<li><?php esc_html_e( 'Individual gifts can be created under Gifts > Add New', 'bp-gifts' ); ?></li>
				</ul>
			</div>
		</div>

		<style>
		#bp-gifts-settings .bp-gifts-settings-info {
			margin-top: 30px;
			padding: 15px;
			background: #f8f9fa;
			border: 1px solid #dee2e6;
			border-radius: 4px;
		}

		#bp-gifts-settings .bp-gifts-settings-info h3 {
			margin-top: 0;
			margin-bottom: 10px;
			color: #495057;
		}

		#bp-gifts-settings .bp-gifts-settings-info ul {
			margin-bottom: 0;
		}

		#bp-gifts-settings .bp-gifts-settings-info li {
			margin-bottom: 5px;
			color: #6c757d;
		}
		</style>
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
			foreach ( $mycred_types as $type_id => $type_obj ) {
				$types[ $type_id ] = $type_obj['singular'];
			}
		} else {
			// Fallback for older myCred versions
			$types['mycred_default'] = __( 'Points', 'bp-gifts' );
		}

		return $types;
	}
}