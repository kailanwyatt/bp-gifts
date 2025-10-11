<?php
/**
 * Improved BP Gifts Loader with Service Architecture
 *
 * This file contains the main plugin class that handles initialization
 * using a service-based architecture with dependency injection.
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
 * Main BP Gifts Loader class with improved architecture.
 *
 * Singleton class that initializes and manages the BP Gifts plugin
 * using a service-based architecture.
 *
 * @since 2.1.0
 */
if ( ! class_exists( 'BP_Gifts_Loader_V2' ) ) :
	/**
	 * Class BP_Gifts_Loader_V2
	 *
	 * Main plugin class with service-based architecture.
	 *
	 * @since 2.1.0
	 */
	class BP_Gifts_Loader_V2 {

		/**
		 * Single instance of this class.
		 *
		 * @since 2.1.0
		 * @var   BP_Gifts_Loader_V2|null
		 */
		protected static $instance = null;

		/**
		 * Plugin basename.
		 *
		 * @since 2.1.0
		 * @var   string
		 */
		public $basename;

		/**
		 * Custom post type name for gifts.
		 *
		 * @since 2.1.0
		 * @var   string
		 */
		public $post_type;

		/**
		 * Dependency injection container.
		 *
		 * @since 2.1.0
		 * @var   BP_Gifts_Container
		 */
		private $container;

		/**
		 * Get the single instance of this class.
		 *
		 * @since 2.1.0
		 * @return BP_Gifts_Loader_V2 Main instance.
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
		 * including necessary files, and setting up services.
		 *
		 * @since 2.1.0
		 */
		public function __construct() {
			if ( $this->check_requirements() ) {
				$this->define_constants();
				$this->get_post_type();
				$this->includes();
				$this->init_container();
				$this->init_hooks();

				/**
				 * Fires after BP Gifts has been loaded.
				 *
				 * @since 2.1.0
				 */
				do_action( 'bp_gifts_loaded_v2' );
			} else {
				$this->display_requirement_message();
			}
		}

		/**
		 * Check if BuddyPress is activated and meets minimum requirements.
		 *
		 * @since 2.1.0
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
		 * Display requirement message if dependencies are not met.
		 *
		 * @since 2.1.0
		 */
		public function display_requirement_message() {
			add_action( 'admin_notices', array( $this, 'display_admin_notice' ) );
		}

		/**
		 * Display admin notice for missing requirements.
		 *
		 * @since 2.1.0
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
		 * @since 2.1.0
		 */
		private function define_constants() {
			$this->define( 'BP_GIFTS_VERSION', '2.1.0' );
			$this->define( 'BP_GIFTS_DIR', plugin_dir_path( __FILE__ ) );
			$this->define( 'BP_GIFTS_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'BP_GIFTS_BASENAME', plugin_basename( __FILE__ ) );
		}

		/**
		 * Define constant if not already set.
		 *
		 * @since 2.1.0
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
		 * @since 2.1.0
		 */
		public function includes() {
			$plugin_dir = plugin_dir_path( __FILE__ );
			
			// Include container
			require_once $plugin_dir . 'includes/BP_Gifts_Container.php';
			
			// Load service interfaces
			require_once $plugin_dir . 'includes/interfaces/Gift_Service_Interface.php';
			require_once $plugin_dir . 'includes/interfaces/Message_Service_Interface.php';
			require_once $plugin_dir . 'includes/interfaces/Modal_Service_Interface.php';

			// Load service implementations
			require_once $plugin_dir . 'includes/services/BP_Gifts_Gift_Service.php';
			require_once $plugin_dir . 'includes/services/BP_Gifts_Message_Service.php';
			require_once $plugin_dir . 'includes/services/BP_Gifts_Modal_Service.php';
			require_once $plugin_dir . 'includes/services/BP_Gifts_User_Service.php';			// Include taxonomy
			require_once $plugin_dir . 'includes/BP_Gifts_Taxonomy.php';

			// Include admin files if needed
			if ( is_admin() ) {
				require_once $plugin_dir . 'admin/class-bp-gifts-admin.php';
			}
		}

		/**
		 * Initialize the dependency injection container.
		 *
		 * @since 2.1.0
		 */
		private function init_container() {
			$this->container = BP_Gifts_Container::instance();

			// Register services
			$this->register_services();
		}

		/**
		 * Register all services in the container.
		 *
		 * @since 2.1.0
		 */
		private function register_services() {
			// Register services with dependency injection
			$this->container->register( 'gift_service', function( $container ) {
				return new BP_Gifts_Gift_Service();
			});

			$this->container->register( 'message_service', function( $container ) {
				return new BP_Gifts_Message_Service( $container->get( 'gift_service' ) );
			});

			$this->container->register( 'modal_service', function( $container ) {
				return new BP_Gifts_Modal_Service( $container->get( 'gift_service' ) );
			});

			$this->container->register( 'user_service', function( $container ) {
				return new BP_Gifts_User_Service( 
					$container->get( 'gift_service' ),
					$container->get( 'message_service' )
				);
			});			// Register Taxonomy
			$this->container->register( 'taxonomy', function( $container ) {
				return new BP_Gifts_Taxonomy( $this->post_type );
			} );
		}

		/**
		 * Get a service from the container.
		 *
		 * @since 2.1.0
		 * @param string $service_name Service name.
		 * @return mixed Service instance.
		 */
		public function get_service( string $service_name ) {
			return $this->container->get( $service_name );
		}

		/**
		 * Initialize hooks.
		 *
		 * @since 2.1.0
		 */
		public function init_hooks() {
			add_action( 'plugins_loaded', array( $this, 'plugin_load_textdomain' ) );
			add_action( 'init', array( $this, 'register_post_type' ) );
			add_action( 'init', array( $this, 'create_default_categories' ), 20 );
			add_action( 'init', array( $this, 'register_shortcodes' ) );
			add_action( 'bp_after_messages_compose_content', array( $this, 'render_gift_composer' ) );
			add_action( 'bp_after_message_reply_box', array( $this, 'render_gift_composer' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'messages_message_after_save', array( $this, 'process_gift_attachment' ), 12, 1 );
			add_action( 'bp_after_message_content', array( $this, 'display_gift' ) );
			add_action( 'bp_before_message_thread_content', array( $this, 'display_thread_gift' ) );
			add_action( 'save_post', array( $this, 'clear_gift_cache' ), 12, 2 );
		}

		/**
		 * Load plugin text domain for translation.
		 *
		 * @since 2.1.0
		 */
		public function plugin_load_textdomain() {
			load_plugin_textdomain( 'bp-gifts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Set post type for the plugin.
		 *
		 * @since 2.1.0
		 */
		public function get_post_type() {
			$this->post_type = apply_filters( 'bp_gifts_post_type', 'bp_gifts' );
		}

		/**
		 * Register custom post type for gifts.
		 *
		 * @since 2.1.0
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
		 * Create default gift categories.
		 *
		 * @since 2.1.0
		 */
		public function create_default_categories() {
			try {
				$taxonomy = $this->get_service( 'taxonomy' );
				$taxonomy->create_default_categories();
			} catch ( Exception $e ) {
				// Log error but don't break execution
				error_log( 'BP Gifts: Failed to create default categories - ' . $e->getMessage() );
			}
		}

		/**
		 * Clear gift cache when gift posts are saved.
		 *
		 * @since 2.1.0
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 */
		public function clear_gift_cache( $post_id, $post ) {
			// If this isn't a 'gifts' post, don't update it.
			if ( $this->post_type !== $post->post_type ) {
				return;
			}

			// If this is a revision, skip.
			if ( wp_is_post_revision( $post_id ) ) {
				return;
			}

			try {
				$gift_service = $this->get_service( 'gift_service' );
				$gift_service->clear_cache();
			} catch ( Exception $e ) {
				// Log error but don't break execution
				error_log( 'BP Gifts: Failed to clear cache - ' . $e->getMessage() );
			}
		}

		/**
		 * Enqueue all JavaScript and CSS files.
		 *
		 * @since 2.1.0
		 */
		public function add_scripts() {
			// Only load on BuddyPress message pages.
			if ( ! bp_is_messages_component() ) {
				return;
			}

			$plugin_url = plugin_dir_url( __FILE__ );

			// Enqueue CSS with RTL support.
			wp_enqueue_style(
				'bp-gifts-style',
				$plugin_url . 'assets/bp-gifts.css',
				array(),
				BP_GIFTS_VERSION
			);

			// Add RTL stylesheet.
			wp_style_add_data( 'bp-gifts-style', 'rtl', 'replace' );

			// Enqueue required JS libraries.
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

			// Enqueue main plugin JS.
			wp_enqueue_script(
				'bp-gifts-main',
				$plugin_url . 'assets/bp-gifts.js',
				array( 'jquery', 'bp-gift-modal', 'bp-gift-list', 'bp-gift-list-pagination' ),
				BP_GIFTS_VERSION,
				true
			);

			// Localize script with comprehensive data and accessibility strings.
			wp_localize_script(
				'bp-gifts-main',
				'bp_gifts_vars',
				array(
					'remove_text'                 => __( 'Remove gift', 'bp-gifts' ),
					'ajax_url'                    => admin_url( 'admin-ajax.php' ),
					'nonce'                       => wp_create_nonce( 'bp_gifts_nonce' ),
					'selected_gift_text'          => __( 'Selected gift: %s', 'bp-gifts' ),
					'gift_selected_text'          => __( '%s has been selected as your gift.', 'bp-gifts' ),
					'gift_selected_for_thread_text' => __( '%s has been selected as your thread gift.', 'bp-gifts' ),
					'gift_removed_text'           => __( 'Gift %s has been removed.', 'bp-gifts' ),
					'showing_all_text'            => __( 'Showing all %s gifts', 'bp-gifts' ),
					'showing_filtered_text'       => __( 'Showing %s of %s gifts', 'bp-gifts' ),
					'search_results_text'         => __( '%s gifts found', 'bp-gifts' ),
					'no_gifts_text'               => __( 'No gifts available.', 'bp-gifts' ),
					'search_placeholder'          => __( 'Search gifts...', 'bp-gifts' ),
					'all_categories'              => __( 'All categories', 'bp-gifts' ),
					'attached_to_thread_text'     => __( 'Attached to thread', 'bp-gifts' ),
				)
			);
		}

		/**
		 * Render the gift picker/composer interface.
		 *
		 * @since 2.1.0
		 */
		public function render_gift_composer() {
			try {
				$modal_service = $this->get_service( 'modal_service' );
				echo $modal_service->render_gift_composer();
			} catch ( Exception $e ) {
				// Log error and show fallback
				error_log( 'BP Gifts: Failed to render gift composer - ' . $e->getMessage() );
				echo '<p>' . esc_html__( 'Gift selection temporarily unavailable.', 'bp-gifts' ) . '</p>';
			}
		}

		/**
		 * Process gift attachment from form submission.
		 *
		 * @since 2.1.0
		 * @param object $message Message object from BuddyPress.
		 */
		public function process_gift_attachment( $message ) {
			try {
				$message_service = $this->get_service( 'message_service' );
				$message_service->process_gift_from_submission( $message );
			} catch ( Exception $e ) {
				// Log error but don't break message sending
				error_log( 'BP Gifts: Failed to process gift attachment - ' . $e->getMessage() );
			}
		}

		/**
		 * Display the gift on single message page.
		 *
		 * @since 2.1.0
		 */
		public function display_gift() {
			try {
				$message_id = bp_get_the_thread_message_id();
				$message_service = $this->get_service( 'message_service' );
				$modal_service = $this->get_service( 'modal_service' );
				
				$gift = $message_service->get_message_gift( $message_id );
				
				if ( $gift ) {
					echo $modal_service->render_message_gift( $gift );
				}
			} catch ( Exception $e ) {
				// Log error but don't break display
				error_log( 'BP Gifts: Failed to display gift - ' . $e->getMessage() );
			}
		}

		/**
		 * Display the gift on thread level.
		 *
		 * @since 2.1.0
		 */
		public function display_thread_gift() {
			try {
				// Try to get thread ID from various sources
				$thread_id = null;
				
				// Check if we're in a BuddyPress messages context
				global $messages_template;
				if ( isset( $messages_template->thread->thread_id ) ) {
					$thread_id = $messages_template->thread->thread_id;
				}
				
				// Fallback to GET parameter
				if ( ! $thread_id && isset( $_GET['thread_id'] ) ) {
					$thread_id = absint( $_GET['thread_id'] );
				}
				
				if ( ! $thread_id ) {
					return;
				}

				$message_service = $this->get_service( 'message_service' );
				$modal_service = $this->get_service( 'modal_service' );
				
				$gift = $message_service->get_thread_gift( $thread_id );
				
				if ( $gift ) {
					echo $modal_service->render_thread_gift( $gift );
				}
			} catch ( Exception $e ) {
				// Log error but don't break display
				error_log( 'BP Gifts: Failed to display thread gift - ' . $e->getMessage() );
			}
		}

		/**
		 * Render the user gifts dashboard.
		 *
		 * @since 2.1.0
		 * @param array $atts Shortcode attributes.
		 * @return string Dashboard HTML.
		 */
		public function render_user_gifts_dashboard( $atts = array() ) {
			$atts = wp_parse_args( $atts, array(
				'user_id' => 0,
			));

			// If no user ID specified, use current user or displayed user
			if ( ! $atts['user_id'] ) {
				$atts['user_id'] = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
			}

			if ( ! $atts['user_id'] ) {
				return '<p>' . esc_html__( 'Please log in to view your gifts.', 'bp-gifts' ) . '</p>';
			}

			ob_start();
			include plugin_dir_path( __FILE__ ) . 'templates/user-gifts-dashboard.php';
			return ob_get_clean();
		}

		/**
		 * Register shortcodes.
		 *
		 * @since 2.1.0
		 */
		public function register_shortcodes() {
			add_shortcode( 'bp_user_gifts', array( $this, 'render_user_gifts_dashboard' ) );
		}
	}
endif;