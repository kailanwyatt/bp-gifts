<?php
/**
 * Admin functionality for BP Gifts.
 *
 * @package BP_Gifts
 * @since   1.0.0
 * @author  SuitePlugins
 * @license GPL-2.0-or-later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Gifts Admin class.
 *
 * Handles all admin-related functionality for the BP Gifts plugin.
 *
 * @since 1.0.0
 */
class BP_Gifts_Admin {

	/**
	 * Single instance of this class.
	 *
	 * @since 1.0.0
	 * @var   BP_Gifts_Admin|null
	 */
	protected static $instance = null;

	/**
	 * Get the single instance of this class.
	 *
	 * @since 1.0.0
	 * @return BP_Gifts_Admin Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize admin hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'save_post', array( $this, 'save_gift_meta' ) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function admin_scripts( $hook_suffix ) {
		global $post_type;

		// Only load on gift post type pages.
		if ( 'bp_gifts' !== $post_type ) {
			return;
		}

		wp_enqueue_media();
		
		wp_enqueue_style(
			'bp-gifts-admin',
			plugin_dir_url( dirname( __FILE__ ) ) . 'assets/admin.css',
			array(),
			BP_GIFTS_VERSION
		);
	}

	/**
	 * Custom post updated messages.
	 *
	 * @since 1.0.0
	 * @param array $messages Existing post update messages.
	 * @return array Modified messages array.
	 */
	public function updated_messages( $messages ) {
		$post             = get_post();
		$post_type        = get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );

		if ( 'bp_gifts' !== $post_type ) {
			return $messages;
		}

		$revision_string = false;
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['revision'] ) ) {
			// translators: %s: revision date.
			$revision_string = sprintf( __( 'Gift restored to revision from %s', 'bp-gifts' ), wp_post_revision_title( absint( $_GET['revision'] ), false ) );
		}

		$messages['bp_gifts'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Gift updated.', 'bp-gifts' ),
			2  => __( 'Custom field updated.', 'bp-gifts' ),
			3  => __( 'Custom field deleted.', 'bp-gifts' ),
			4  => __( 'Gift updated.', 'bp-gifts' ),
			5  => $revision_string,
			6  => __( 'Gift published.', 'bp-gifts' ),
			7  => __( 'Gift saved.', 'bp-gifts' ),
			8  => __( 'Gift submitted.', 'bp-gifts' ),
			9  => sprintf(
				// translators: %1$s: date and time of the scheduled gift.
				__( 'Gift scheduled for: <strong>%1$s</strong>.', 'bp-gifts' ),
				date_i18n( __( 'M j, Y @ G:i', 'bp-gifts' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Gift draft updated.', 'bp-gifts' ),
		);

		return $messages;
	}

	/**
	 * Add meta boxes for gift post type.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'bp-gifts-instructions',
			__( 'Gift Instructions', 'bp-gifts' ),
			array( $this, 'instructions_meta_box' ),
			'bp_gifts',
			'side',
			'high'
		);

		// Add myCred point cost meta box if myCred integration is enabled
		if ( BP_Gifts_Settings::is_mycred_enabled() ) {
			add_meta_box(
				'bp-gifts-mycred-cost',
				__( 'Point Cost', 'bp-gifts' ),
				array( $this, 'mycred_cost_meta_box' ),
				'bp_gifts',
				'side',
				'default'
			);
		}
	}

	/**
	 * Instructions meta box callback.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function instructions_meta_box( $post ) {
		?>
		<div class="bp-gifts-instructions">
			<p><?php esc_html_e( 'To create a gift:', 'bp-gifts' ); ?></p>
			<ol>
				<li><?php esc_html_e( 'Add a title for your gift', 'bp-gifts' ); ?></li>
				<li><?php esc_html_e( 'Set a featured image (required)', 'bp-gifts' ); ?></li>
				<li><?php esc_html_e( 'Publish the gift', 'bp-gifts' ); ?></li>
			</ol>
			<p><strong><?php esc_html_e( 'Note:', 'bp-gifts' ); ?></strong> <?php esc_html_e( 'Gifts without featured images will not appear in the gift selector.', 'bp-gifts' ); ?></p>
		</div>
		<?php
	}

	/**
	 * myCred point cost meta box callback.
	 *
	 * @since 2.1.0
	 * @param WP_Post $post The post object.
	 */
	public function mycred_cost_meta_box( $post ) {
		// Add nonce field for security
		wp_nonce_field( 'bp_gifts_save_mycred_meta', 'bp_gifts_mycred_nonce' );

		// Get current cost
		$cost = get_post_meta( $post->ID, '_bp_gift_point_cost', true );
		$cost = $cost !== '' ? floatval( $cost ) : 0;

		// Get point type name for display
		$point_type_name = __( 'Points', 'bp-gifts' );
		if ( BP_Gifts_Settings::is_mycred_available() ) {
			try {
				$loader = BP_Gifts_Loader_V2::instance();
				$mycred_service = $loader->get_service( 'mycred_service' );
				$point_type_name = $mycred_service->get_point_type_name( false );
			} catch ( Exception $e ) {
				// Fallback to default
			}
		}
		?>
		<div class="bp-gifts-mycred-cost">
			<p>
				<label for="bp_gift_point_cost">
					<?php 
					printf(
						/* translators: %s: point type name */
						esc_html__( 'Cost in %s:', 'bp-gifts' ),
						esc_html( $point_type_name )
					);
					?>
				</label>
			</p>
			<p>
				<input 
					type="number" 
					id="bp_gift_point_cost" 
					name="bp_gift_point_cost" 
					value="<?php echo esc_attr( $cost ); ?>" 
					min="0" 
					step="1"
					style="width: 100%;"
				/>
			</p>
			<p class="description">
				<?php esc_html_e( 'Enter the point cost for sending this gift. Set to 0 for free gifts.', 'bp-gifts' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Display admin notices.
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {
		global $post_type, $pagenow;

		if ( 'bp_gifts' !== $post_type || ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		// Check if BuddyPress is active.
		if ( ! class_exists( 'BuddyPress' ) ) {
			?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'BP Gifts requires BuddyPress to be active for full functionality.', 'bp-gifts' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Save gift meta data.
	 *
	 * @since 2.1.0
	 * @param int $post_id Post ID.
	 */
	public function save_gift_meta( $post_id ) {
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

		// Verify nonce for myCred meta
		if ( isset( $_POST['bp_gifts_mycred_nonce'] ) && 
			 wp_verify_nonce( $_POST['bp_gifts_mycred_nonce'], 'bp_gifts_save_mycred_meta' ) ) {
			
			// Save point cost
			if ( isset( $_POST['bp_gift_point_cost'] ) ) {
				$cost = max( 0, floatval( $_POST['bp_gift_point_cost'] ) );
				update_post_meta( $post_id, '_bp_gift_point_cost', $cost );
			}
		}
	}
}

// Initialize admin class.
BP_Gifts_Admin::instance();