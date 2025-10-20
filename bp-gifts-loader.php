<?php
/**
 * Main plugin loader file for BP Gifts.
 *
 * This file contains the main plugin class that handles initialization,
 * hooks, and core functionality for the BP Gifts plugin.
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
 * Main BP Gifts class.
 *
 * Singleton class that handles all plugin functionality including
 * post type registration, hooks, and gift management.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'BP_Gifts_Loader' ) ) :
	/**
	 * Class BP_Gifts_Loader
	 *
	 * Main plugin class that initializes and manages the BP Gifts plugin.
	 *
	 * @since 1.0.0
	 */
	class BP_Gifts_Loader {

		/**
		 * Single instance of this class.
		 *
		 * @since 1.0.0
		 * @var   BP_Gifts_Loader|null
		 */
		protected static $instance = null;

		/**
		 * Plugin basename.
		 *
		 * @since 1.0.0
		 * @var   string
		 */
		public $basename;

		/**
		 * Custom post type name for gifts.
		 *
		 * @since 1.0.0
		 * @var   string
		 */
		public $post_type;

		/**
		 * Array of all active gifts.
		 *
		 * @since 1.0.0
		 * @var   array|null
		 */
		public $gifts = null;
		/**
		 * Get the single instance of this class.
		 *
		 * @since 1.0.0
		 * @return BP_Gifts_Loader Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * BP Gifts Constructor.
		 *
		 * Initializes the plugin by checking requirements, defining constants,
		 * including necessary files, and setting up hooks.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			if ( $this->check_requirements() ) {
				$this->define_constants();
				$this->get_post_type();
				$this->includes();
				$this->init_hooks();
				$this->gifts = $this->get_all_gifts();

				/**
				 * Fires after BP Gifts has been loaded.
				 *
				 * @since 1.0.0
				 */
				do_action( 'bp_gifts_loaded' );
			} else {
				$this->display_requirement_message();
			}
		}
		/**
		 * Check if BuddyPress is activated and meets minimum requirements.
		 *
		 * @since 1.0.0
		 * @return bool True if requirements are met, false otherwise.
		 */
		public function check_requirements() {
			// Check if BuddyPress is active.
			if ( ! class_exists( 'BuddyPress' ) && ! in_array( 'buddypress/bp-loader.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
				return false;
			}

			// Check minimum WordPress version.
			if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
				return false;
			}

			// Check minimum PHP version.
			if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
				return false;
			}

			return true;
		}
		/**
		 * Initialize requirement notice display.
		 *
		 * @since 1.0.0
		 */
		public function display_requirement_message() {
			add_action( 'admin_notices', array( $this, 'display_admin_notice' ) );
		}

		/**
		 * Display admin notice for missing requirements.
		 *
		 * @since 1.0.0
		 */
		public function display_admin_notice() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			echo '<div class="error notice is-dismissible"><p>';
			echo esc_html__( 'BP Gifts requires BuddyPress, WordPress 5.0+, and PHP 7.4+. Please ensure these requirements are met.', 'bp-gifts' );
			echo '</p></div>';
		}
		/**
		 * Define BP Gifts Constants.
		 *
		 * @since 1.0.0
		 */
		private function define_constants() {
			$this->define( 'BP_GIFTS_VERSION', '2.0.0' );
			$this->define( 'BP_GIFTS_DIR', plugin_dir_path( __FILE__ ) );
			$this->define( 'BP_GIFTS_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'BP_GIFTS_BASENAME', plugin_basename( __FILE__ ) );
		}

		/**
		 * Define constant if not already set.
		 *
		 * @since 1.0.0
		 * @param string      $name  Constant name.
		 * @param string|bool $value Constant value.
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}
		/**
		 * Include all necessary files.
		 *
		 * @since 1.0.0
		 */
		public function includes() {
			// Admin functionality is now handled by the modern BP_Gifts_Admin class in includes/
		}
		/**
		 * Initialize hooks.
		 *
		 * @since 1.0.0
		 */
		public function init_hooks() {
			add_action( 'init', array( $this, 'register_post_type' ) );
			add_action( 'bp_after_messages_compose_content', array( $this, 'render_gift_composer' ) );
			add_action( 'bp_after_message_reply_box', array( $this, 'render_gift_composer' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'messages_message_after_save', array( $this, 'send_gift' ), 12, 1 );
			add_action( 'bp_after_message_content', array( $this, 'display_gift' ) );
			add_action( 'save_post', array( $this, 'update_transient' ), 12, 2 );
		}

		/**
		 * Set post type for the plugin.
		 *
		 * @since 1.0.0
		 */
		public function get_post_type() {
			$this->post_type = apply_filters( 'bp_gifts_post_type', 'bp_gifts' );
		}
		/**
		 * Register custom post type for gifts.
		 *
		 * @since 1.0.0
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
		}
		/**
		 * Update gifts transient when gift posts are saved.
		 *
		 * @since 1.0.0
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 */
		public function update_transient( $post_id, $post ) {
			// If this isn't a 'gifts' post, don't update it.
			if ( $this->post_type !== $post->post_type ) {
				return;
			}

			// If this is a revision, skip.
			if ( wp_is_post_revision( $post_id ) ) {
				return;
			}

			// Clear and regenerate transient.
			delete_transient( 'bp_gifts_array' );
			$gifts = $this->get_all_gifts();
			set_transient( 'bp_gifts_array', $gifts, DAY_IN_SECONDS );
		}
		/**
		 * Fetch all published gifts from the database.
		 *
		 * @since 1.0.0
		 * @return array Array of gift data.
		 */
		public function get_all_gifts() {
			// Check for cached gifts first.
			$all_gifts = get_transient( 'bp_gifts_array' );

			if ( false === $all_gifts ) {
				$gifts = array();

				// Use WP_Query instead of direct database queries.
				$query_args = array(
					'post_type'      => $this->post_type,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'     => '_thumbnail_id',
							'compare' => 'EXISTS',
						),
					),
				);

				$gift_query = new WP_Query( $query_args );

				if ( $gift_query->have_posts() ) {
					while ( $gift_query->have_posts() ) {
						$gift_query->the_post();
						$post_id = get_the_ID();

						$post_thumbnail_id = get_post_thumbnail_id( $post_id );
						$image_attributes  = wp_get_attachment_image_src( $post_thumbnail_id, 'thumbnail' );

						if ( ! empty( $image_attributes ) ) {
							$gifts[] = array(
								'id'    => $post_id,
								'name'  => get_the_title(),
								'image' => esc_url( $image_attributes[0] ),
							);
						}
					}
					wp_reset_postdata();
				}

				$all_gifts = $gifts;
				set_transient( 'bp_gifts_array', $all_gifts, DAY_IN_SECONDS );
			}

			return $all_gifts;
		}
		/**
		 * Enqueue all JavaScript and CSS files.
		 *
		 * @since 1.0.0
		 */
		public function add_scripts() {
			// Only load on BuddyPress message pages.
			if ( ! bp_is_messages_component() ) {
				return;
			}

			// Enqueue CSS.
			wp_enqueue_style(
				'bp-gifts-style',
				plugin_dir_url( __FILE__ ) . 'assets/bp-gifts.css',
				array(),
				BP_GIFTS_VERSION
			);

			// Enqueue required JS libraries.
			wp_enqueue_script(
				'bp-gift-modal',
				plugin_dir_url( __FILE__ ) . 'assets/jquery.easyModal.js',
				array( 'jquery' ),
				BP_GIFTS_VERSION,
				true
			);

			wp_enqueue_script(
				'bp-gift-list',
				plugin_dir_url( __FILE__ ) . 'assets/list.min.js',
				array( 'jquery' ),
				BP_GIFTS_VERSION,
				true
			);

			wp_enqueue_script(
				'bp-gift-list-pagination',
				plugin_dir_url( __FILE__ ) . 'assets/list.pagination.min.js',
				array( 'jquery', 'bp-gift-list' ),
				BP_GIFTS_VERSION,
				true
			);

			// Enqueue main plugin JS.
			wp_enqueue_script(
				'bp-gifts-main',
				plugin_dir_url( __FILE__ ) . 'assets/bp-gifts.js',
				array( 'jquery', 'bp-gift-modal', 'bp-gift-list', 'bp-gift-list-pagination' ),
				BP_GIFTS_VERSION,
				true
			);

			// Localize script with data and accessibility strings.
			wp_localize_script(
				'bp-gifts-main',
				'bp_gifts_vars',
				array(
					'remove_text'           => __( 'Remove gift', 'bp-gifts' ),
					'ajax_url'              => admin_url( 'admin-ajax.php' ),
					'nonce'                 => wp_create_nonce( 'bp_gifts_nonce' ),
					'selected_gift_text'    => __( 'Selected gift: %s', 'bp-gifts' ),
					'gift_selected_text'    => __( '%s has been selected as your gift.', 'bp-gifts' ),
					'gift_removed_text'     => __( 'Gift %s has been removed.', 'bp-gifts' ),
					'showing_all_text'      => __( 'Showing all %s gifts', 'bp-gifts' ),
					'showing_filtered_text' => __( 'Showing %s of %s gifts', 'bp-gifts' ),
					'search_results_text'   => __( '%s gifts found', 'bp-gifts' ),
					'no_gifts_text'         => __( 'No gifts available.', 'bp-gifts' ),
					'search_placeholder'    => __( 'Search gifts...', 'bp-gifts' ),
					'all_categories'        => __( 'All categories', 'bp-gifts' ),
				)
			);
		}
		/**
		 * Render the gift picker/composer interface.
		 *
		 * @since 1.0.0
		 */
		public function render_gift_composer() {
			$gifts = $this->gifts;

			if ( empty( $gifts ) ) {
				return;
			}
			?>
			<label>
				<a href="#" class="button bp-send-gift-btn"><?php esc_html_e( 'Send a Gift', 'bp-gifts' ); ?></a>
			</label>
			<div class="bp-gift-edit-container"></div>
			<div class="easy-modal" id="bpmodalbox">
				<div class="bp-modal-inner">
					<h3><?php esc_html_e( 'Select a gift', 'bp-gifts' ); ?></h3>
					<div class="bp-gifts-list" id="bp-gifts-list">
						<?php if ( ! empty( $gifts ) ) : ?>
							<ul class="list">
								<?php foreach ( $gifts as $gift ) : ?>
									<li class="bp-gift-item">
										<div class="bp-gift-item-ele" data-id="<?php echo esc_attr( $gift['id'] ); ?>" data-image="<?php echo esc_url( $gift['image'] ); ?>">
											<img src="<?php echo esc_url( $gift['image'] ); ?>" alt="<?php echo esc_attr( $gift['name'] ); ?>" />
											<div class="bp-gift-title">
												<?php echo esc_html( $gift['name'] ); ?>
											</div>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<div class="clear clearfix"></div>
						<ul class="bp-gift-pagination"></ul>
					</div>
				</div>
			</div>
			<?php
		}
		/**
		 * Save gift to message meta when a message is sent.
		 *
		 * @since 1.0.0
		 * @param BP_Messages_Message $message Message object.
		 */
		public function send_gift( $message ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			if ( ! isset( $_POST['bp_gift_id'] ) || ! is_numeric( $_POST['bp_gift_id'] ) ) {
				return;
			}

			$gift_id = absint( $_POST['bp_gift_id'] );

			// Verify the gift exists and is published.
			$gift_post = get_post( $gift_id );
			if ( ! $gift_post || $gift_post->post_type !== $this->post_type || $gift_post->post_status !== 'publish' ) {
				return;
			}

			bp_messages_update_meta( $message->id, '_bp_gift', $gift_id );
		}
		/**
		 * Display the gift on single message page.
		 *
		 * @since 1.0.0
		 */
		public function display_gift() {
			$message_id = bp_get_the_thread_message_id();
			$gift_id    = bp_messages_get_meta( $message_id, '_bp_gift', true );

			if ( ! $gift_id ) {
				return;
			}

			$gift_id = absint( $gift_id );
			$gift_post = get_post( $gift_id );

			if ( ! $gift_post || $gift_post->post_type !== $this->post_type ) {
				return;
			}

			$post_thumbnail_id = get_post_thumbnail_id( $gift_id );
			$image_attributes  = wp_get_attachment_image_src( $post_thumbnail_id, 'thumbnail' );

			if ( empty( $image_attributes ) ) {
				return;
			}
			?>
			<div class="bp-gift-holder">
				<img src="<?php echo esc_url( $image_attributes[0] ); ?>" alt="<?php echo esc_attr( get_the_title( $gift_id ) ); ?>" />
			</div>
			<?php
		}
	}
endif;

/**
 * Main instance of BP Gifts.
 *
 * Returns the main instance of BP_Gifts_Loader to prevent the need to use globals.
 *
 * @since 1.0.0
 * @return BP_Gifts_Loader
 */
function bp_gifts() {
	return BP_Gifts_Loader::instance();
}

// Global for backwards compatibility.
$GLOBALS['bp_gifts'] = bp_gifts();