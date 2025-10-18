/**
 * BP Gifts User Dashboard Template
 * 
 * Template for displaying user's gift history and statistics.
 *
 * @package BP_Gifts
 * @since   2.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current user ID or displayed user ID
$user_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();

if ( ! $user_id ) {
	return;
}

// Get services
$container = BP_Gifts_Loader_V2::instance()->get_container();
$user_service = $container->get( 'user_service' );
$modal_service = $container->get( 'modal_service' );

// Get filter parameters
$gift_type = isset( $_GET['gift_type'] ) ? sanitize_text_field( $_GET['gift_type'] ) : 'all';
$view_mode = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'received';

// Get gifts based on view mode
if ( $view_mode === 'sent' ) {
	$gifts = $user_service->get_sent_gifts( $user_id, array( 'type' => $gift_type ) );
} else {
	$gifts = $user_service->get_received_gifts( $user_id, array( 'type' => $gift_type ) );
}

// Get statistics
$stats = $user_service->get_gift_stats( $user_id );

$is_own_profile = ( $user_id === bp_loggedin_user_id() );
?>

<div id="bp-gifts-dashboard" class="bp-gifts-user-dashboard">
	
	<div class="bp-gifts-dashboard-header">
		<h2 class="bp-gifts-dashboard-title">
			<?php if ( $is_own_profile ) : ?>
				<?php esc_html_e( 'My Gifts', 'bp-gifts' ); ?>
			<?php else : ?>
				<?php printf( esc_html__( "%s's Gifts", 'bp-gifts' ), bp_get_displayed_user_fullname() ); ?>
			<?php endif; ?>
		</h2>
		
		<?php if ( ! empty( $stats ) ) : ?>
			<div class="bp-gifts-stats-summary">
				<div class="bp-gifts-stat-item">
					<span class="bp-gifts-stat-number"><?php echo esc_html( $stats['total_received'] ); ?></span>
					<span class="bp-gifts-stat-label"><?php esc_html_e( 'Received', 'bp-gifts' ); ?></span>
				</div>
				<?php if ( $is_own_profile ) : ?>
					<div class="bp-gifts-stat-item">
						<span class="bp-gifts-stat-number"><?php echo esc_html( $stats['total_sent'] ); ?></span>
						<span class="bp-gifts-stat-label"><?php esc_html_e( 'Sent', 'bp-gifts' ); ?></span>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="bp-gifts-dashboard-controls">
		<div class="bp-gifts-view-tabs" role="tablist">
			<button type="button" 
					class="bp-gifts-tab-button <?php echo $view_mode === 'received' ? 'active' : ''; ?>"
					role="tab"
					aria-selected="<?php echo $view_mode === 'received' ? 'true' : 'false'; ?>"
					aria-controls="bp-gifts-received-panel"
					data-view="received">
				<?php esc_html_e( 'Received Gifts', 'bp-gifts' ); ?>
			</button>
			
			<?php if ( $is_own_profile ) : ?>
				<button type="button" 
						class="bp-gifts-tab-button <?php echo $view_mode === 'sent' ? 'active' : ''; ?>"
						role="tab"
						aria-selected="<?php echo $view_mode === 'sent' ? 'true' : 'false'; ?>"
						aria-controls="bp-gifts-sent-panel"
						data-view="sent">
					<?php esc_html_e( 'Sent Gifts', 'bp-gifts' ); ?>
				</button>
			<?php endif; ?>
		</div>

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

	<div class="bp-gifts-dashboard-content">
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

	<?php if ( ! empty( $stats ) && $is_own_profile ) : ?>
		<div class="bp-gifts-detailed-stats">
			<h3><?php esc_html_e( 'Gift Statistics', 'bp-gifts' ); ?></h3>
			
			<div class="bp-gifts-stats-grid">
				<?php if ( ! empty( $stats['favorite_gift'] ) ) : ?>
					<div class="bp-gifts-stat-card">
						<h4><?php esc_html_e( 'Most Received Gift', 'bp-gifts' ); ?></h4>
						<div class="bp-gifts-favorite-gift">
							<img src="<?php echo esc_url( $stats['favorite_gift']['gift_data']['image'] ); ?>" 
								 alt="<?php echo esc_attr( $stats['favorite_gift']['gift_data']['name'] ); ?>" />
							<div>
								<p class="bp-gifts-favorite-name">
									<?php echo esc_html( $stats['favorite_gift']['gift_data']['name'] ); ?>
								</p>
								<p class="bp-gifts-favorite-count">
									<?php printf( esc_html__( 'Received %d times', 'bp-gifts' ), $stats['favorite_gift']['count'] ); ?>
								</p>
							</div>
						</div>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $stats['most_active_sender'] ) ) : ?>
					<div class="bp-gifts-stat-card">
						<h4><?php esc_html_e( 'Most Active Gift Sender', 'bp-gifts' ); ?></h4>
						<div class="bp-gifts-active-sender">
							<p class="bp-gifts-sender-name">
								<?php echo esc_html( $stats['most_active_sender']['sender_name'] ); ?>
							</p>
							<p class="bp-gifts-sender-count">
								<?php printf( esc_html__( '%d gifts sent', 'bp-gifts' ), $stats['most_active_sender']['count'] ); ?>
							</p>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
	// Tab switching
	$('.bp-gifts-tab-button').on('click', function() {
		var view = $(this).data('view');
		var currentUrl = new URL(window.location);
		currentUrl.searchParams.set('view', view);
		window.location.href = currentUrl.toString();
	});
	
	// Filter changing
	$('#bp-gifts-type-filter').on('change', function() {
		var giftType = $(this).val();
		var view = $(this).data('current-view');
		var currentUrl = new URL(window.location);
		currentUrl.searchParams.set('gift_type', giftType);
		currentUrl.searchParams.set('view', view);
		window.location.href = currentUrl.toString();
	});
});
</script>