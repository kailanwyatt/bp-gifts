<?php
/**
 * BP Gifts - Admin Class
 *
 * Handles admin functionality.
 *
 * @package BP_Gifts
 * @since   2.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Gifts Admin class.
 *
 * @since 2.2.0
 */
class BP_Gifts_Admin {

	/**
	 * Single instance of this class.
	 *
	 * @since 2.2.0
	 * @var   BP_Gifts_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get the single instance of this class.
	 *
	 * @since 2.2.0
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_filter( 'manage_bp_gifts_posts_columns', array( $this, 'add_custom_columns' ) );
		add_action( 'manage_bp_gifts_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
		
		// Initialize myCred integration if available
		$mycred = new BP_Gifts_MyCred();
		$mycred->init_hooks();
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 2.2.0
	 * @param string $hook Hook suffix.
	 */
	public function enqueue_admin_scripts( $hook ) {
		global $post_type;
		
		if ( $post_type !== 'bp_gifts' ) {
			return;
		}

		wp_enqueue_media();
		
		$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Admin CSS - Use built version if available, fallback to source
		$admin_css_file = file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/dist/bp-gifts-admin' . $suffix . '.css' ) 
			? 'assets/dist/bp-gifts-admin' . $suffix . '.css'
			: 'assets/admin.css';

		wp_enqueue_style(
			'bp-gifts-admin',
			$plugin_url . $admin_css_file,
			array(),
			BP_GIFTS_VERSION
		);

		// MyCred admin styles if needed
		if ( BP_Gifts_Settings::is_mycred_enabled() ) {
			$mycred_css_file = file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/dist/bp-gifts-admin-mycred' . $suffix . '.css' ) 
				? 'assets/dist/bp-gifts-admin-mycred' . $suffix . '.css'
				: 'assets/admin-mycred.css';

			wp_enqueue_style(
				'bp-gifts-admin-mycred',
				$plugin_url . $mycred_css_file,
				array( 'bp-gifts-admin' ),
				BP_GIFTS_VERSION
			);
		}
	}

	/**
	 * Add custom columns to gifts list table.
	 *
	 * @since 2.2.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_custom_columns( $columns ) {
		$new_columns = array();
		
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			
			if ( $key === 'title' ) {
				$new_columns['thumbnail'] = __( 'Thumbnail', 'bp-gifts' );
				$new_columns['categories'] = __( 'Categories', 'bp-gifts' );
				
				if ( BP_Gifts_Settings::is_mycred_enabled() ) {
					$new_columns['cost'] = __( 'Cost', 'bp-gifts' );
				}
			}
		}
		
		return $new_columns;
	}

	/**
	 * Render custom columns content.
	 *
	 * @since 2.2.0
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'thumbnail':
				$thumbnail = get_the_post_thumbnail( $post_id, array( 50, 50 ) );
				if ( $thumbnail ) {
					echo $thumbnail;
				} else {
					echo '<span class="dashicons dashicons-heart" style="font-size: 30px; color: #ccc;"></span>';
				}
				break;
				
			case 'categories':
				$terms = get_the_terms( $post_id, 'gift_category' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$category_names = wp_list_pluck( $terms, 'name' );
					echo esc_html( implode( ', ', $category_names ) );
				} else {
					echo '—';
				}
				break;
				
			case 'cost':
				if ( BP_Gifts_Settings::is_mycred_enabled() ) {
					$cost = get_post_meta( $post_id, 'gift_cost', true );
					if ( $cost ) {
						echo esc_html( $cost ) . ' ' . esc_html__( 'points', 'bp-gifts' );
					} else {
						echo esc_html__( 'Free', 'bp-gifts' );
					}
				}
				break;
		}
	}

	/**
	 * Add meta boxes to gift edit screen.
	 *
	 * @since 2.2.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'bp_gifts_description',
			__( 'Gift Description', 'bp-gifts' ),
			array( $this, 'render_description_meta_box' ),
			'bp_gifts',
			'normal',
			'high'
		);
	}

	/**
	 * Render description meta box.
	 *
	 * @since 2.2.0
	 * @param WP_Post $post Post object.
	 */
	public function render_description_meta_box( $post ) {
		wp_nonce_field( 'bp_gifts_meta_nonce', 'bp_gifts_meta_nonce' );
		?>
		<p>
			<label for="gift_description"><?php esc_html_e( 'Description (optional):', 'bp-gifts' ); ?></label>
		</p>
		<textarea id="gift_description" name="gift_description" rows="4" style="width: 100%;"><?php echo esc_textarea( $post->post_content ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'This description will be shown when the gift is displayed in messages.', 'bp-gifts' ); ?>
		</p>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @since 2.2.0
	 * @param int $post_id Post ID.
	 */
	public function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['bp_gifts_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bp_gifts_meta_nonce'], 'bp_gifts_meta_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( get_post_type( $post_id ) !== 'bp_gifts' ) {
			return;
		}

		// Save description in post content
		if ( isset( $_POST['gift_description'] ) ) {
			wp_update_post( array(
				'ID'           => $post_id,
				'post_content' => sanitize_textarea_field( $_POST['gift_description'] ),
			) );
		}
	}
}