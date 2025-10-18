<?php
/**
 * BP Gifts Profile Tab
 *
 * Handles the user profile tab for viewing gifts.
 *
 * @package BP_Gifts
 * @since   2.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Gifts Profile Tab class.
 *
 * @since 2.1.0
 */
class BP_Gifts_Profile_Tab {

	/**
	 * Initialize profile tab.
	 *
	 * @since 2.1.0
	 */
	public static function init() {
		add_action( 'bp_setup_nav', array( __CLASS__, 'setup_nav' ), 100 );
		add_action( 'bp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Setup navigation for gifts tab.
	 *
	 * @since 2.1.0
	 */
	public static function setup_nav() {
		// Only add tab if enabled and gifts are available
		if ( ! BP_Gifts_Settings::is_user_tab_enabled() || ! BP_Gifts_Settings::is_gifts_available() ) {
			return;
		}

		// Only show tab to the profile owner
		if ( ! bp_is_my_profile() ) {
			return;
		}

		// Add main gifts nav item
		bp_core_new_nav_item( array(
			'name'                => __( 'Gifts', 'bp-gifts' ),
			'slug'                => 'gifts',
			'default_subnav_slug' => 'received',
			'position'            => 85,
			'screen_function'     => array( __CLASS__, 'gifts_screen' ),
			'item_css_id'         => 'gifts'
		));

		// Add sub-navigation items
		bp_core_new_subnav_item( array(
			'name'            => __( 'Received', 'bp-gifts' ),
			'slug'            => 'received',
			'parent_url'      => bp_displayed_user_domain() . 'gifts/',
			'parent_slug'     => 'gifts',
			'screen_function' => array( __CLASS__, 'received_gifts_screen' ),
			'position'        => 10
		));

		bp_core_new_subnav_item( array(
			'name'            => __( 'Sent', 'bp-gifts' ),
			'slug'            => 'sent',
			'parent_url'      => bp_displayed_user_domain() . 'gifts/',
			'parent_slug'     => 'gifts',
			'screen_function' => array( __CLASS__, 'sent_gifts_screen' ),
			'position'        => 20
		));
	}

	/**
	 * Main gifts screen function.
	 *
	 * @since 2.1.0
	 */
	public static function gifts_screen() {
		// Redirect to default sub-nav
		bp_core_redirect( bp_displayed_user_domain() . 'gifts/received/' );
	}

	/**
	 * Received gifts screen function.
	 *
	 * @since 2.1.0
	 */
	public static function received_gifts_screen() {
		add_action( 'bp_template_title', array( __CLASS__, 'received_gifts_title' ) );
		add_action( 'bp_template_content', array( __CLASS__, 'received_gifts_content' ) );
		bp_core_load_template( apply_filters( 'bp_gifts_received_template', 'members/single/plugins' ) );
	}

	/**
	 * Sent gifts screen function.
	 *
	 * @since 2.1.0
	 */
	public static function sent_gifts_screen() {
		add_action( 'bp_template_title', array( __CLASS__, 'sent_gifts_title' ) );
		add_action( 'bp_template_content', array( __CLASS__, 'sent_gifts_content' ) );
		bp_core_load_template( apply_filters( 'bp_gifts_sent_template', 'members/single/plugins' ) );
	}

	/**
	 * Display received gifts title.
	 *
	 * @since 2.1.0
	 */
	public static function received_gifts_title() {
		echo esc_html__( 'Received Gifts', 'bp-gifts' );
	}

	/**
	 * Display sent gifts title.
	 *
	 * @since 2.1.0
	 */
	public static function sent_gifts_title() {
		echo esc_html__( 'Sent Gifts', 'bp-gifts' );
	}

	/**
	 * Display received gifts content.
	 *
	 * @since 2.1.0
	 */
	public static function received_gifts_content() {
		$user_id = bp_displayed_user_id();
		
		if ( ! $user_id ) {
			echo '<p>' . esc_html__( 'User not found.', 'bp-gifts' ) . '</p>';
			return;
		}

		try {
			$loader = BP_Gifts_Loader_V2::instance();
			$user_service = $loader->get_service( 'user_service' );

			// Get filter parameters
			$gift_type = isset( $_GET['gift_type'] ) ? sanitize_text_field( $_GET['gift_type'] ) : 'all';
			$page = isset( $_GET['gifts_page'] ) ? absint( $_GET['gifts_page'] ) : 1;

			// Get received gifts
			$gifts = $user_service->get_received_gifts( $user_id, array(
				'type' => $gift_type,
				'limit' => 20,
				'offset' => ( $page - 1 ) * 20
			));

			// Get statistics
			$stats = $user_service->get_gift_stats( $user_id );

			// Load template
			self::render_gifts_template( $gifts, $stats, 'received', $gift_type, $page );

		} catch ( Exception $e ) {
			echo '<p>' . esc_html__( 'Error loading gifts. Please try again later.', 'bp-gifts' ) . '</p>';
			error_log( 'BP Gifts Profile Tab Error: ' . $e->getMessage() );
		}
	}

	/**
	 * Display sent gifts content.
	 *
	 * @since 2.1.0
	 */
	public static function sent_gifts_content() {
		$user_id = bp_displayed_user_id();
		
		if ( ! $user_id ) {
			echo '<p>' . esc_html__( 'User not found.', 'bp-gifts' ) . '</p>';
			return;
		}

		try {
			$loader = BP_Gifts_Loader_V2::instance();
			$user_service = $loader->get_service( 'user_service' );

			// Get filter parameters
			$gift_type = isset( $_GET['gift_type'] ) ? sanitize_text_field( $_GET['gift_type'] ) : 'all';
			$page = isset( $_GET['gifts_page'] ) ? absint( $_GET['gifts_page'] ) : 1;

			// Get sent gifts
			$gifts = $user_service->get_sent_gifts( $user_id, array(
				'type' => $gift_type,
				'limit' => 20,
				'offset' => ( $page - 1 ) * 20
			));

			// Get statistics
			$stats = $user_service->get_gift_stats( $user_id );

			// Load template
			self::render_gifts_template( $gifts, $stats, 'sent', $gift_type, $page );

		} catch ( Exception $e ) {
			echo '<p>' . esc_html__( 'Error loading gifts. Please try again later.', 'bp-gifts' ) . '</p>';
			error_log( 'BP Gifts Profile Tab Error: ' . $e->getMessage() );
		}
	}

	/**
	 * Render gifts template.
	 *
	 * @since 2.1.0
	 * @param array  $gifts     Array of gifts.
	 * @param array  $stats     Gift statistics.
	 * @param string $view_mode View mode (received/sent).
	 * @param string $gift_type Gift type filter.
	 * @param int    $page      Current page.
	 */
	private static function render_gifts_template( $gifts, $stats, $view_mode, $gift_type, $page ) {
		$user_id = bp_displayed_user_id();
		$is_own_profile = ( $user_id === bp_loggedin_user_id() );
		?>

		<div id="bp-gifts-profile-tab" class="bp-gifts-profile-container">
			
			<?php if ( ! empty( $stats ) ) : ?>
				<div class="bp-gifts-profile-stats">
					<div class="bp-gifts-stat-summary">
						<div class="bp-gifts-stat-item">
							<span class="bp-gifts-stat-number"><?php echo esc_html( $stats['total_received'] ); ?></span>
							<span class="bp-gifts-stat-label"><?php esc_html_e( 'Received', 'bp-gifts' ); ?></span>
						</div>
						<div class="bp-gifts-stat-item">
							<span class="bp-gifts-stat-number"><?php echo esc_html( $stats['total_sent'] ); ?></span>
							<span class="bp-gifts-stat-label"><?php esc_html_e( 'Sent', 'bp-gifts' ); ?></span>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="bp-gifts-profile-controls">
				<div class="bp-gifts-filter-controls">
					<label for="bp-gifts-type-filter" class="screen-reader-text">
						<?php esc_html_e( 'Filter by gift type', 'bp-gifts' ); ?>
					</label>
					<select id="bp-gifts-type-filter" class="bp-gifts-type-filter" data-current-view="<?php echo esc_attr( $view_mode ); ?>">
						<option value="all" <?php selected( $gift_type, 'all' ); ?>>
							<?php esc_html_e( 'All Gifts', 'bp-gifts' ); ?>
						</option>
						<option value="message" <?php selected( $gift_type, 'message' ); ?>>
							<?php esc_html_e( 'Message Gifts', 'bp-gifts' ); ?>
						</option>
						<option value="thread" <?php selected( $gift_type, 'thread' ); ?>>
							<?php esc_html_e( 'Thread Gifts', 'bp-gifts' ); ?>
						</option>
					</select>
				</div>
			</div>

			<div class="bp-gifts-profile-content">
				<?php if ( ! empty( $gifts ) ) : ?>
					<div class="bp-gifts-grid" role="grid" aria-label="<?php esc_attr_e( 'Gifts list', 'bp-gifts' ); ?>">
						<?php foreach ( $gifts as $gift ) : ?>
							<div class="bp-gifts-grid-item" role="gridcell">
								<article class="bp-gift-card">
									<div class="bp-gift-card-image">
										<img src="<?php echo esc_url( $gift['gift_data']['image'] ); ?>" 
											 alt="<?php echo esc_attr( $gift['gift_data']['name'] ); ?>"
											 loading="lazy" />
										
										<div class="bp-gift-type-badge bp-gift-type-<?php echo esc_attr( $gift['type'] ); ?>">
											<?php if ( $gift['type'] === 'thread' ) : ?>
												<span class="bp-gift-type-icon" aria-hidden="true">🧵</span>
												<?php esc_html_e( 'Thread', 'bp-gifts' ); ?>
											<?php else : ?>
												<span class="bp-gift-type-icon" aria-hidden="true">💬</span>
												<?php esc_html_e( 'Message', 'bp-gifts' ); ?>
											<?php endif; ?>
										</div>
									</div>
									
									<div class="bp-gift-card-content">
										<h3 class="bp-gift-card-title">
											<?php echo esc_html( $gift['gift_data']['name'] ); ?>
										</h3>
										
										<div class="bp-gift-card-meta">
											<?php if ( $view_mode === 'received' ) : ?>
												<p class="bp-gift-sender">
													<span class="bp-gift-meta-label"><?php esc_html_e( 'From:', 'bp-gifts' ); ?></span>
													<a href="<?php echo esc_url( bp_core_get_user_domain( $gift['sender_id'] ) ); ?>" 
													   class="bp-gift-sender-link">
														<?php echo esc_html( $gift['sender_name'] ); ?>
													</a>
												</p>
											<?php else : ?>
												<p class="bp-gift-recipient">
													<span class="bp-gift-meta-label"><?php esc_html_e( 'To:', 'bp-gifts' ); ?></span>
													<a href="<?php echo esc_url( bp_core_get_user_domain( $gift['recipient_id'] ) ); ?>" 
													   class="bp-gift-recipient-link">
														<?php echo esc_html( $gift['recipient_name'] ); ?>
													</a>
												</p>
											<?php endif; ?>
											
											<p class="bp-gift-date">
												<span class="bp-gift-meta-label"><?php esc_html_e( 'Date:', 'bp-gifts' ); ?></span>
												<time datetime="<?php echo esc_attr( date( 'c', strtotime( $gift['date_received'] ?? $gift['date_sent'] ) ) ); ?>">
													<?php echo esc_html( bp_format_time( strtotime( $gift['date_received'] ?? $gift['date_sent'] ) ) ); ?>
												</time>
											</p>
										</div>
										
										<div class="bp-gift-card-actions">
											<?php 
											$messages_link = '#';
											if ( function_exists( 'bp_core_get_user_domain' ) ) {
												$messages_link = bp_core_get_user_domain( $user_id ) . 'messages/view/' . $gift['thread_id'] . '/';
											}
											?>
											<a href="<?php echo esc_url( $messages_link ); ?>" 
											   class="bp-gift-view-conversation button">
												<?php esc_html_e( 'View Conversation', 'bp-gifts' ); ?>
											</a>
										</div>
									</div>
								</article>
							</div>
						<?php endforeach; ?>
					</div>

					<?php self::render_pagination( count( $gifts ), $page, $view_mode, $gift_type ); ?>

				<?php else : ?>
					<div class="bp-gifts-empty-state">
						<div class="bp-gifts-empty-icon" aria-hidden="true">🎁</div>
						<h3 class="bp-gifts-empty-title">
							<?php if ( $view_mode === 'sent' ) : ?>
								<?php esc_html_e( 'No gifts sent yet', 'bp-gifts' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'No gifts received yet', 'bp-gifts' ); ?>
							<?php endif; ?>
						</h3>
						<p class="bp-gifts-empty-description">
							<?php if ( $view_mode === 'sent' ) : ?>
								<?php esc_html_e( 'Start spreading joy by sending gifts to your friends!', 'bp-gifts' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Gifts you receive will appear here.', 'bp-gifts' ); ?>
							<?php endif; ?>
						</p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Filter changing
			$('#bp-gifts-type-filter').on('change', function() {
				var giftType = $(this).val();
				var view = $(this).data('current-view');
				var currentUrl = new URL(window.location);
				currentUrl.searchParams.set('gift_type', giftType);
				currentUrl.searchParams.delete('gifts_page'); // Reset to first page
				window.location.href = currentUrl.toString();
			});
		});
		</script>
		<?php
	}

	/**
	 * Render pagination for gifts.
	 *
	 * @since 2.1.0
	 * @param int    $gift_count Current page gift count.
	 * @param int    $page       Current page.
	 * @param string $view_mode  View mode.
	 * @param string $gift_type  Gift type filter.
	 */
	private static function render_pagination( $gift_count, $page, $view_mode, $gift_type ) {
		if ( $gift_count < 20 && $page === 1 ) {
			return; // No pagination needed
		}

		$base_url = bp_displayed_user_domain() . 'gifts/' . $view_mode . '/';
		?>
		<div class="bp-gifts-pagination">
			<?php if ( $page > 1 ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'gifts_page' => $page - 1, 'gift_type' => $gift_type ), $base_url ) ); ?>" 
				   class="bp-gifts-pagination-prev button">
					<?php esc_html_e( '« Previous', 'bp-gifts' ); ?>
				</a>
			<?php endif; ?>

			<?php if ( $gift_count >= 20 ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'gifts_page' => $page + 1, 'gift_type' => $gift_type ), $base_url ) ); ?>" 
				   class="bp-gifts-pagination-next button">
					<?php esc_html_e( 'Next »', 'bp-gifts' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts for profile tab.
	 *
	 * @since 2.1.0
	 */
	public static function enqueue_scripts() {
		if ( bp_is_user() && bp_is_current_component( 'gifts' ) ) {
			// Enqueue main gifts styles
			wp_enqueue_style( 'bp-gifts-main' );
		}
	}
}