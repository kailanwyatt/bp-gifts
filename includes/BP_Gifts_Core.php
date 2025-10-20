<?php
/**
 * BP Gifts Core Class
 *
 * Simple, flat implementation of the BP Gifts plugin functionality.
 *
 * @package BP_Gifts
 * @since   2.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main BP Gifts Core class.
 *
 * @since 2.2.0
 */
class BP_Gifts_Core {

	/**
	 * Single instance of this class.
	 *
	 * @since 2.2.0
	 * @var   BP_Gifts_Core|null
	 */
	private static $instance = null;

	/**
	 * Custom post type name for gifts.
	 *
	 * @since 2.2.0
	 * @var   string
	 */
	public $post_type = 'bp_gifts';

	/**
	 * Get the single instance of this class.
	 *
	 * @since 2.2.0
	 * @return BP_Gifts_Core Main instance.
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
	 * @since 2.2.0
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.2.0
	 */
	private function init_hooks() {
		// Core hooks
		add_action( 'bp_register_admin_settings', array( $this, 'init_admin_settings' ) );
		
		// Initialize component features after BuddyPress loads
		add_action( 'bp_init', array( $this, 'init_component_features' ) );
	}

	/**
	 * Initialize admin settings.
	 *
	 * @since 2.2.0
	 */
	public function init_admin_settings() {
		if ( class_exists( 'BP_Admin' ) ) {
			// Settings class is already loaded by the early hook
			// No need to initialize again here
		}
	}

	/**
	 * Check if gifts component is active.
	 *
	 * @since 2.2.0
	 * @return bool True if active, false otherwise.
	 */
	public function is_component_active() {
		// If BuddyPress isn't loaded yet, assume active for registration
		if ( ! function_exists( 'bp_is_active' ) ) {
			return true;
		}
		
		// Check if component is active via BuddyPress
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'gifts' ) ) {
			return true;
		}
		
		// Fallback: check the setting directly
		$active_components = bp_get_option( 'bp-active-components', array() );
		if ( isset( $active_components['gifts'] ) ) {
			return true;
		}
		
		// If no components are set yet (fresh install), default to active
		if ( empty( $active_components ) ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Initialize component features.
	 *
	 * @since 2.2.0
	 */
	public function init_component_features() {
		// Only initialize if component is active
		if ( ! $this->is_component_active() ) {
			return;
		}

		// Register post type and core functionality
		$this->register_post_type();
		$this->register_shortcodes();
		
		// Initialize admin
		if ( is_admin() ) {
			$this->init_admin();
		}

		// Create default categories on next init cycle
		add_action( 'init', array( $this, 'create_default_categories' ), 20 );

		// Frontend hooks
		add_action( 'bp_after_messages_compose_content', array( $this, 'render_gift_composer' ) );
		add_action( 'bp_after_message_reply_box', array( $this, 'render_gift_composer' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'messages_message_after_save', array( $this, 'process_gift_attachment' ), 12, 1 );
		
		// Cookie-based gift handling is done via JavaScript and process_gift_post_message
		
		// Add AJAX handler for processing gifts after message is sent (cookie-based)
		add_action( 'wp_ajax_bp_gifts_process_post_message', array( $this, 'process_gift_post_message' ) );
		
		add_action( 'bp_after_message_content', array( $this, 'display_gift' ) );
		add_action( 'bp_before_message_thread_content', array( $this, 'display_thread_gift' ) );
		add_action( 'save_post', array( $this, 'clear_gift_cache' ), 12, 2 );

		// Profile tab
		add_action( 'bp_setup_nav', array( $this, 'init_profile_tab' ), 100 );
	}

	/**
	 * Register the gifts post type.
	 *
	 * @since 2.2.0
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Gifts', 'post type general name', 'bp-gifts' ),
			'singular_name'      => _x( 'Gift', 'post type singular name', 'bp-gifts' ),
			'menu_name'          => _x( 'Gifts', 'admin menu', 'bp-gifts' ),
			'name_admin_bar'     => _x( 'Gift', 'add new on admin bar', 'bp-gifts' ),
			'add_new'            => _x( 'Add New', 'Gift', 'bp-gifts' ),
			'add_new_item'       => __( 'Add New Gift', 'bp-gifts' ),
			'new_item'           => __( 'New Gift', 'bp-gifts' ),
			'edit_item'          => __( 'Edit Gift', 'bp-gifts' ),
			'view_item'          => __( 'View Gift', 'bp-gifts' ),
			'all_items'          => __( 'All Gifts', 'bp-gifts' ),
			'search_items'       => __( 'Search Gifts', 'bp-gifts' ),
			'parent_item_colon'  => __( 'Parent Gifts:', 'bp-gifts' ),
			'not_found'          => __( 'No gifts found.', 'bp-gifts' ),
			'not_found_in_trash' => __( 'No gifts found in Trash.', 'bp-gifts' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'bp-gifts' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-heart',
			'supports'           => array( 'title', 'thumbnail' ),
			'show_in_rest'       => false,
		);

		register_post_type( $this->post_type, $args );
		
		// Register gift category taxonomy
		$this->register_gift_category_taxonomy();
	}

	/**
	 * Register the gift category taxonomy.
	 *
	 * @since 2.2.0
	 */
	public function register_gift_category_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Gift Categories', 'taxonomy general name', 'bp-gifts' ),
			'singular_name'              => _x( 'Gift Category', 'taxonomy singular name', 'bp-gifts' ),
			'search_items'               => __( 'Search Gift Categories', 'bp-gifts' ),
			'popular_items'              => __( 'Popular Gift Categories', 'bp-gifts' ),
			'all_items'                  => __( 'All Gift Categories', 'bp-gifts' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Gift Category', 'bp-gifts' ),
			'update_item'                => __( 'Update Gift Category', 'bp-gifts' ),
			'add_new_item'               => __( 'Add New Gift Category', 'bp-gifts' ),
			'new_item_name'              => __( 'New Gift Category Name', 'bp-gifts' ),
			'separate_items_with_commas' => __( 'Separate gift categories with commas', 'bp-gifts' ),
			'add_or_remove_items'        => __( 'Add or remove gift categories', 'bp-gifts' ),
			'choose_from_most_used'      => __( 'Choose from the most used gift categories', 'bp-gifts' ),
			'not_found'                  => __( 'No gift categories found.', 'bp-gifts' ),
			'menu_name'                  => __( 'Gift Categories', 'bp-gifts' ),
		);

		$args = array(
			'hierarchical'          => true,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'show_in_nav_menus'     => false,
			'show_tagcloud'         => false,
			'query_var'             => true,
			'public'                => false,
			'publicly_queryable'    => false,
			'rewrite'               => array( 'slug' => 'gift-category' ),
			'show_in_rest'          => false,
		);

		register_taxonomy( 'gift_category', array( $this->post_type ), $args );
	}

	/**
	 * Initialize admin interface.
	 *
	 * @since 2.2.0
	 */
	public function init_admin() {
		if ( class_exists( 'BP_Gifts_Admin' ) ) {
			BP_Gifts_Admin::instance();
		}
	}

	/**
	 * Create default gift categories.
	 *
	 * @since 2.2.0
	 */
	public function create_default_categories() {
		$taxonomy = new BP_Gifts_Taxonomy( $this->post_type );
		$taxonomy->create_default_categories();
	}

	/**
	 * Register shortcodes.
	 *
	 * @since 2.2.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'bp_user_gifts', array( $this, 'user_gifts_shortcode' ) );
		add_shortcode( 'bp_gift_stats', array( $this, 'gift_stats_shortcode' ) );
		add_shortcode( 'bp_popular_gifts', array( $this, 'popular_gifts_shortcode' ) );
	}

	/**
	 * User gifts shortcode.
	 *
	 * @since 2.2.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function user_gifts_shortcode( $atts = array() ) {
		$atts = wp_parse_args( $atts, array(
			'user_id' => 0,
			'type' => 'both', // 'sent', 'received', or 'both'
			'show_stats' => true,
			'limit' => 10,
		));

		if ( ! $atts['user_id'] ) {
			$atts['user_id'] = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
		}

		if ( ! $atts['user_id'] ) {
			return '<p>' . esc_html__( 'Please log in to view your gifts.', 'bp-gifts' ) . '</p>';
		}

		ob_start();
		
		// Show statistics if enabled
		if ( $atts['show_stats'] ) {
			$stats = BP_Gifts_Database::get_user_stats( $atts['user_id'] );
			echo '<div class="bp-gifts-stats">';
			echo '<h4>' . esc_html__( 'Gift Statistics', 'bp-gifts' ) . '</h4>';
			echo '<div class="bp-gifts-stats-grid">';
			echo '<div class="stat-item">';
			echo '<span class="stat-number">' . esc_html( $stats['sent'] ) . '</span>';
			echo '<span class="stat-label">' . esc_html__( 'Gifts Sent', 'bp-gifts' ) . '</span>';
			echo '</div>';
			echo '<div class="stat-item">';
			echo '<span class="stat-number">' . esc_html( $stats['received'] ) . '</span>';
			echo '<span class="stat-label">' . esc_html__( 'Gifts Received', 'bp-gifts' ) . '</span>';
			echo '</div>';
			echo '</div>';
			echo '</div>';
		}

		// Get gift relationships
		$messages = new BP_Gifts_Messages();
		$relationships = $messages->get_user_gift_relationships( $atts['user_id'], array(
			'type' => $atts['type'],
			'limit' => $atts['limit'],
		));
		
		if ( ! empty( $relationships ) ) {
			echo '<div class="bp-gifts-relationships">';
			echo '<h4>' . esc_html__( 'Recent Gift Activity', 'bp-gifts' ) . '</h4>';
			echo '<div class="bp-gifts-list">';
			
			$gifts = new BP_Gifts_Gifts();
			
			foreach ( $relationships as $relationship ) {
				$gift = $gifts->get_gift( $relationship->gift_id );
				if ( ! $gift ) continue;
				
				$is_sender = ( $relationship->sender_id == $atts['user_id'] );
				$other_user_id = $is_sender ? $relationship->receiver_id : $relationship->sender_id;
				$other_user = get_userdata( $other_user_id );
				
				echo '<div class="gift-relationship-item">';
				echo '<div class="gift-thumbnail">';
				if ( $gift->thumbnail ) {
					echo '<img src="' . esc_url( $gift->thumbnail ) . '" alt="' . esc_attr( $gift->title ) . '" />';
				} else {
					echo '<span class="dashicons dashicons-heart"></span>';
				}
				echo '</div>';
				
				echo '<div class="gift-details">';
				echo '<h5>' . esc_html( $gift->title ) . '</h5>';
				
				if ( $is_sender ) {
					// translators: %s is the recipient's name
					printf(
						'<p>' . esc_html__( 'Sent to %s', 'bp-gifts' ) . '</p>',
						'<strong>' . esc_html( $other_user->display_name ) . '</strong>'
					);
				} else {
					// translators: %s is the sender's name
					printf(
						'<p>' . esc_html__( 'Received from %s', 'bp-gifts' ) . '</p>',
						'<strong>' . esc_html( $other_user->display_name ) . '</strong>'
					);
				}
				
				echo '<time>' . esc_html( human_time_diff( strtotime( $relationship->date_sent ) ) ) . ' ' . esc_html__( 'ago', 'bp-gifts' ) . '</time>';
				echo '</div>';
				echo '</div>';
			}
			
			echo '</div>';
			echo '</div>';
		} else {
			echo '<p>' . esc_html__( 'No gift activity found.', 'bp-gifts' ) . '</p>';
		}
		
		return ob_get_clean();
	}

	/**
	 * Gift statistics shortcode.
	 *
	 * @since 2.2.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function gift_stats_shortcode( $atts = array() ) {
		$atts = wp_parse_args( $atts, array(
			'user_id' => 0,
		));

		if ( ! $atts['user_id'] ) {
			$atts['user_id'] = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
		}

		if ( ! $atts['user_id'] ) {
			return '<p>' . esc_html__( 'Please log in to view gift statistics.', 'bp-gifts' ) . '</p>';
		}

		$stats = BP_Gifts_Database::get_user_stats( $atts['user_id'] );
		
		ob_start();
		?>
		<div class="bp-gifts-stats-widget">
			<div class="bp-gifts-stats-grid">
				<div class="stat-item sent">
					<span class="stat-number"><?php echo esc_html( $stats['sent'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Gifts Sent', 'bp-gifts' ); ?></span>
				</div>
				<div class="stat-item received">
					<span class="stat-number"><?php echo esc_html( $stats['received'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Gifts Received', 'bp-gifts' ); ?></span>
				</div>
				<div class="stat-item total">
					<span class="stat-number"><?php echo esc_html( $stats['sent'] + $stats['received'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total Activity', 'bp-gifts' ); ?></span>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Popular gifts shortcode.
	 *
	 * @since 2.2.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function popular_gifts_shortcode( $atts = array() ) {
		$atts = wp_parse_args( $atts, array(
			'limit' => 5,
			'show_count' => true,
		));

		$popular_gifts = BP_Gifts_Database::get_popular_gifts( $atts['limit'] );
		
		if ( empty( $popular_gifts ) ) {
			return '<p>' . esc_html__( 'No popular gifts found.', 'bp-gifts' ) . '</p>';
		}

		ob_start();
		?>
		<div class="bp-popular-gifts-widget">
			<div class="bp-popular-gifts-list">
				<?php
				$gifts = new BP_Gifts_Gifts();
				foreach ( $popular_gifts as $popular_gift ) :
					$gift = $gifts->get_gift( $popular_gift->gift_id );
					if ( ! $gift ) continue;
				?>
					<div class="popular-gift-item">
						<div class="gift-thumbnail">
							<?php if ( $gift->thumbnail ) : ?>
								<img src="<?php echo esc_url( $gift->thumbnail ); ?>" alt="<?php echo esc_attr( $gift->title ); ?>" />
							<?php else : ?>
								<span class="dashicons dashicons-heart"></span>
							<?php endif; ?>
						</div>
						<div class="gift-info">
							<h5><?php echo esc_html( $gift->title ); ?></h5>
							<?php if ( $atts['show_count'] ) : ?>
								<span class="gift-count">
									<?php
									// translators: %d is the number of times the gift was sent
									printf(
										esc_html( _n( 'Sent %d time', 'Sent %d times', $popular_gift->count, 'bp-gifts' ) ),
										esc_html( $popular_gift->count )
									);
									?>
								</span>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 2.2.0
	 */
	public function enqueue_scripts() {
		// Load on BuddyPress message pages and user profile pages
		$load_assets = false;
		
		// Check if we're on messages component pages
		if ( function_exists( 'bp_is_messages_component' ) && bp_is_messages_component() ) {
			$load_assets = true;
		}
		
		// Check if we're on user profile pages (which might have messages)
		if ( function_exists( 'bp_is_user' ) && bp_is_user() ) {
			$load_assets = true;
		}
		
		// Check current component
		if ( function_exists( 'bp_is_current_component' ) && bp_is_current_component( 'messages' ) ) {
			$load_assets = true;
		}
		
		// Check current action for message-related actions
		if ( function_exists( 'bp_current_action' ) ) {
			$current_action = bp_current_action();
			if ( in_array( $current_action, array( 'compose', 'view', 'inbox', 'sentbox', 'notices' ) ) ) {
				$load_assets = true;
			}
		}
		
		if ( ! $load_assets ) {
			return;
		}

		$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// CSS - Use built version if available, fallback to source
		$css_file = file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/dist/bp-gifts-styles' . $suffix . '.css' ) 
			? 'assets/dist/bp-gifts-styles' . $suffix . '.css'
			: 'assets/bp-gifts.css';

		wp_enqueue_style(
			'bp-gifts-style',
			$plugin_url . $css_file,
			array(),
			BP_GIFTS_VERSION
		);

		// JavaScript dependencies
		wp_enqueue_script(
			'bp-gift-modal',
			$plugin_url . 'assets/jquery.easyModal.js',
			array( 'jquery' ),
			BP_GIFTS_VERSION,
			true
		);

		wp_enqueue_script(
			'bp-gift-list',
			$plugin_url . 'assets/list.min.js',
			array( 'jquery' ),
			BP_GIFTS_VERSION,
			true
		);

		wp_enqueue_script(
			'bp-gift-list-pagination',
			$plugin_url . 'assets/list.pagination.min.js',
			array( 'jquery', 'bp-gift-list' ),
			BP_GIFTS_VERSION,
			true
		);

		// Main script - Use built version if available, fallback to source
		$js_file = file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/dist/bp-gifts' . $suffix . '.js' ) 
			? 'assets/dist/bp-gifts' . $suffix . '.js'
			: 'assets/bp-gifts.js';

		wp_enqueue_script(
			'bp-gifts-main',
			$plugin_url . $js_file,
			array( 'jquery', 'bp-gift-modal', 'bp-gift-list', 'bp-gift-list-pagination' ),
			BP_GIFTS_VERSION,
			true
		);

		// Get current thread ID if we're on a messages thread view page
		$current_thread_id = 0;
		if ( bp_is_messages_component() && bp_is_current_action( 'view' ) ) {
			$thread_id = (int) bp_action_variable( 0 );
			if ( $thread_id && messages_is_valid_thread( $thread_id ) && messages_check_thread_access( $thread_id ) ) {
				$current_thread_id = $thread_id;
			}
		}

		// Get myCred data if enabled
		$mycred_data = array();
		if ( BP_Gifts_Settings::is_mycred_enabled() ) {
			$mycred = new BP_Gifts_MyCred();
			$user_id = bp_loggedin_user_id();
			
			$mycred_data = array(
				'enabled' => true,
				'user_balance' => $user_id ? $mycred->get_user_balance( $user_id ) : 0,
				'formatted_balance' => $user_id ? $mycred->get_user_balance( $user_id ) : '0',
				'gifts_costs' => $mycred->get_gifts_with_costs(),
			);
			
			// Get formatted balance if possible
			if ( $user_id && function_exists( 'mycred_display_users_balance' ) ) {
				$mycred_data['formatted_balance'] = mycred_display_users_balance( $user_id );
			}
		}

		// Localize script
		wp_localize_script(
			'bp-gifts-main',
			'bp_gifts_vars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'bp_gifts_nonce' ),
				'thread_id' => $current_thread_id,
				'mycred' => $mycred_data,
				// translators: %d is the number of gifts
				'showing_all_text' => __( 'Showing all %d gifts', 'bp-gifts' ),
				// translators: %1$d is the filtered count, %2$d is the total count
				'showing_filtered_text' => __( 'Showing %1$d of %2$d gifts', 'bp-gifts' ),
				// translators: %d is the number of gifts found
				'search_results_text' => __( '%d gifts found', 'bp-gifts' ),
				// translators: %s is the gift name
				'selected_gift_text' => __( 'Selected gift: %s', 'bp-gifts' ),
				'attached_to_thread_text' => __( 'Attached to thread', 'bp-gifts' ),
				'remove_text' => __( 'Remove gift', 'bp-gifts' ),
				// translators: %s is the gift name
				'gift_selected_for_thread_text' => __( 'Gift %s selected for thread', 'bp-gifts' ),
				// translators: %s is the gift name
				'gift_selected_text' => __( 'Gift %s selected', 'bp-gifts' ),
				// translators: %s is the gift name
				'gift_removed_text' => __( 'Gift %s removed', 'bp-gifts' ),
				'no_gifts_found_text' => __( 'No gifts found', 'bp-gifts' ),
				'loading_text' => __( 'Loading gifts...', 'bp-gifts' ),
				'select_gift_text' => __( 'Select a gift', 'bp-gifts' ),
				'insufficient_funds_text' => __( 'You do not have enough points to send this gift.', 'bp-gifts' ),
				'points_text' => __( 'points', 'bp-gifts' ),
				'free_text' => __( 'Free', 'bp-gifts' ),
			)
		);
	}

	/**
	 * Render gift composer interface.
	 *
	 * @since 2.2.0
	 */
	public function render_gift_composer() {
		$modal = new BP_Gifts_Modal();
		echo wp_kses_post( $modal->render_gift_composer() );
	}

	/**
	 * Process gift from message object.
	 *
	 * @since 2.2.0
	 * @param object $message Message object.
	 */
	public function process_gift_attachment( $message ) {
		$messages = new BP_Gifts_Messages();
		$messages->process_gift_from_submission( $message );
	}



	/**
	 * Process gift data after message is sent (cookie-based approach).
	 *
	 * @since 2.2.0
	 */
	public function process_gift_post_message() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'bp_gifts_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		}

		// Check if gift data was provided
		if ( ! isset( $_POST['gift_data'] ) || empty( $_POST['gift_data'] ) ) {
			wp_send_json_error( array( 'message' => 'No gift data provided' ) );
		}

		$gift_data = map_deep( wp_unslash( $_POST['gift_data'] ), 'sanitize_text_field' );
		
		// Validate gift data structure
		if ( ! isset( $gift_data['gift_id'] ) || ! $gift_data['gift_id'] ) {
			wp_send_json_error( array( 'message' => 'Invalid gift data' ) );
		}

		$gift_id = absint( $gift_data['gift_id'] );
		$thread_id = isset( $gift_data['thread_id'] ) ? absint( $gift_data['thread_id'] ) : 0;

		// Get the most recent message in the thread
		global $wpdb;
		$bp = buddypress();
		
		$sql = $wpdb->prepare(
			"SELECT id, sender_id, subject, date_sent, thread_id 
			 FROM {$bp->messages->table_name_messages} 
			 WHERE thread_id = %d 
			 ORDER BY date_sent DESC 
			 LIMIT 1",
			$thread_id
		);
		
		$message = $wpdb->get_row( $sql );
		
		if ( ! $message ) {
			wp_send_json_error( array( 'message' => 'Message not found' ) );
		}

		// Create a message object for compatibility
		$message_obj = new stdClass();
		$message_obj->id = $message->id;
		$message_obj->sender_id = $message->sender_id;
		$message_obj->subject = $message->subject;
		$message_obj->date_sent = $message->date_sent;
		$message_obj->thread_id = $message->thread_id;

		// Process the gift
		$messages = new BP_Gifts_Messages();
		
		// Attach gift to message
		$messages->attach_gift_to_message( $message_obj->id, $gift_id );
		
		// Create gift relationship
		$relationship_result = $messages->create_gift_relationship( $message_obj, $gift_id );

		if ( $relationship_result ) {
			// Deduct points if myCred is enabled
			if ( BP_Gifts_Settings::is_mycred_enabled() ) {
				$mycred = new BP_Gifts_MyCred();
				// For group messages, we'll charge per gift sent, not per recipient
				$success = $mycred->charge_user_for_gift( $message_obj->sender_id, 0, $gift_id );
			}
			
			wp_send_json_success( array( 
				'message' => 'Gift processed successfully',
				'relationships' => is_array( $relationship_result ) ? count( $relationship_result ) : 1
			) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to create gift relationship' ) );
		}
	}

	/**
	 * Display gift on single message.
	 *
	 * @since 2.2.0
	 */
	public function display_gift() {
		$message_id = bp_get_the_thread_message_id();
		$messages = new BP_Gifts_Messages();
		$gift = $messages->get_message_gift( $message_id );
		
		if ( $gift ) {
			$modal = new BP_Gifts_Modal();
			echo wp_kses_post( $modal->render_message_gift( $gift ) );
		}
	}

	/**
	 * Display gifts on thread level.
	 *
	 * @since 2.2.0
	 */
	public function display_thread_gift() {
		global $messages_template;
		
		$thread_id = null;
		if ( isset( $messages_template->thread->thread_id ) ) {
			$thread_id = $messages_template->thread->thread_id;
		}
		
		if ( ! $thread_id && isset( $_GET['thread_id'] ) ) {
			$thread_id = absint( $_GET['thread_id'] );
		}
		
		if ( ! $thread_id ) {
			return;
		}

		$messages = new BP_Gifts_Messages();
		$gifts = $messages->get_thread_gifts( $thread_id );
		
		if ( ! empty( $gifts ) ) {
			$modal = new BP_Gifts_Modal();
			echo wp_kses_post( $modal->render_thread_gifts( $gifts ) );
		}
	}

	/**
	 * Clear gift cache when posts are saved.
	 *
	 * @since 2.2.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function clear_gift_cache( $post_id, $post ) {
		if ( $this->post_type !== $post->post_type ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Clear any caches here
		wp_cache_delete( 'bp_gifts_all', 'bp_gifts' );
	}

	/**
	 * Initialize profile tab.
	 *
	 * @since 2.2.0
	 */
	public function init_profile_tab() {
		if ( BP_Gifts_Settings::is_gifts_enabled() && BP_Gifts_Settings::is_user_tab_enabled() ) {
			$profile_tab = new BP_Gifts_Profile_Tab();
			$profile_tab->init();
		}
	}
}