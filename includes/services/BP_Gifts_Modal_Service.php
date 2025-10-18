<?php
/**
 * Modal Service Implementation
 *
 * Handles modal and UI rendering with accessibility features.
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
 * Class BP_Gifts_Modal_Service
 *
 * Implementation of modal and UI rendering service.
 *
 * @since 2.1.0
 */
class BP_Gifts_Modal_Service implements Modal_Service_Interface {

	/**
	 * Gift service instance.
	 *
	 * @since 2.1.0
	 * @var   Gift_Service_Interface
	 */
	private $gift_service;

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 * @param Gift_Service_Interface $gift_service Gift service instance.
	 */
	public function __construct( Gift_Service_Interface $gift_service ) {
		$this->gift_service = $gift_service;
	}

	/**
	 * Render the gift composer interface.
	 *
	 * @since 2.1.0
	 * @param array $args Rendering arguments.
	 * @return string HTML output.
	 */
	public function render_gift_composer( array $args = array() ) {
		$gifts = $this->gift_service->get_gifts();
		$categories = $this->gift_service->get_categories();

		if ( empty( $gifts ) ) {
			return '';
		}

		$defaults = array(
			'show_button' => true,
			'button_text' => __( 'Send a Gift', 'bp-gifts' ),
			'button_class' => 'button bp-send-gift-btn',
		);

		$args = wp_parse_args( $args, $defaults );

		ob_start();
		?>
		<?php if ( $args['show_button'] ) : ?>
		<label for="bp-send-gift-btn">
			<button 
				type="button" 
				id="bp-send-gift-btn"
				class="<?php echo esc_attr( $args['button_class'] ); ?>"
				aria-controls="bpmodalbox"
				aria-expanded="false">
				<?php echo esc_html( $args['button_text'] ); ?>
			</button>
		</label>
		<?php endif; ?>
		
		<div class="bp-gift-edit-container" role="region" aria-label="<?php esc_attr_e( 'Selected gift', 'bp-gifts' ); ?>"></div>
		
		<?php echo $this->render_gift_modal( $gifts, array( 'categories' => $categories ) ); ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the gift modal.
	 *
	 * @since 2.1.0
	 * @param array $gifts Array of gifts to display.
	 * @param array $args  Rendering arguments.
	 * @return string HTML output.
	 */
	public function render_gift_modal( array $gifts, array $args = array() ) {
		$defaults = array(
			'modal_id' => 'bpmodalbox',
			'title' => __( 'Select a gift', 'bp-gifts' ),
			'categories' => array(),
			'show_search' => true,
		);

		$args = wp_parse_args( $args, $defaults );

		// Get myCred service and user balance if enabled
		$mycred_enabled = BP_Gifts_Settings::is_mycred_enabled();
		$user_balance = 0;
		$point_type_name = '';

		if ( $mycred_enabled ) {
			try {
				$loader = BP_Gifts_Loader_V2::instance();
				$mycred_service = $loader->get_service( 'mycred_service' );
				$user_balance = $mycred_service->get_user_balance( get_current_user_id() );
				$point_type_name = $mycred_service->get_point_type_name( false );
			} catch ( Exception $e ) {
				$mycred_enabled = false;
			}
		}

		ob_start();
		?>
		<div 
			class="easy-modal" 
			id="<?php echo esc_attr( $args['modal_id'] ); ?>"
			role="dialog"
			aria-labelledby="bp-gifts-modal-title"
			aria-modal="true"
			aria-hidden="true">
			<div class="bp-modal-inner">
				<div class="bp-modal-header">
					<h3 id="bp-gifts-modal-title"><?php echo esc_html( $args['title'] ); ?></h3>
					<?php if ( $mycred_enabled ) : ?>
						<div class="bp-gifts-user-balance">
							<span class="bp-balance-label"><?php esc_html_e( 'Your Balance:', 'bp-gifts' ); ?></span>
							<span class="bp-balance-amount" id="bp-user-balance" data-balance="<?php echo esc_attr( $user_balance ); ?>">
								<?php 
								try {
									echo esc_html( $mycred_service->format_points( $user_balance ) );
								} catch ( Exception $e ) {
									echo esc_html( number_format( $user_balance ) . ' ' . $point_type_name );
								}
								?>
							</span>
						</div>
					<?php endif; ?>
					<button 
						type="button" 
						class="bp-modal-close" 
						aria-label="<?php esc_attr_e( 'Close gift selection modal', 'bp-gifts' ); ?>"
						data-action="close-modal">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				
				<?php if ( $args['show_search'] ) : ?>
					<?php echo $this->render_search_controls( $args['categories'] ); ?>
				<?php endif; ?>
				
				<div class="bp-gifts-list" id="bp-gifts-list" role="region" aria-label="<?php esc_attr_e( 'Gift selection list', 'bp-gifts' ); ?>">
					<?php if ( ! empty( $gifts ) ) : ?>
						<ul class="list" role="grid" aria-label="<?php esc_attr_e( 'Available gifts', 'bp-gifts' ); ?>">
							<?php foreach ( $gifts as $gift ) : ?>
								<?php echo $this->render_gift_item( $gift, array( 'show_mycred' => $mycred_enabled ) ); ?>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="bp-gifts-no-results">
							<?php esc_html_e( 'No gifts available.', 'bp-gifts' ); ?>
						</p>
					<?php endif; ?>
					<div class="clear clearfix"></div>
					<ul class="bp-gift-pagination" role="navigation" aria-label="<?php esc_attr_e( 'Gift pagination', 'bp-gifts' ); ?>"></ul>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single gift item.
	 *
	 * @since 2.1.0
	 * @param array $gift Gift data.
	 * @param array $args Rendering arguments.
	 * @return string HTML output.
	 */
	public function render_gift_item( array $gift, array $args = array() ) {
		$defaults = array(
			'show_categories' => false,
			'show_mycred' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		$categories_text = '';
		if ( $args['show_categories'] && ! empty( $gift['categories'] ) ) {
			$categories_text = ' (' . implode( ', ', $gift['categories'] ) . ')';
		}

		// Get myCred information if enabled
		$gift_cost = 0;
		$can_afford = true;
		$cost_display = '';
		
		if ( $args['show_mycred'] && BP_Gifts_Settings::is_mycred_enabled() ) {
			try {
				$loader = BP_Gifts_Loader_V2::instance();
				$mycred_service = $loader->get_service( 'mycred_service' );
				$gift_cost = $mycred_service->get_gift_cost( $gift['id'] );
				$can_afford = $mycred_service->user_can_afford( get_current_user_id(), $gift_cost );
				$cost_display = $mycred_service->format_points( $gift_cost, false );
			} catch ( Exception $e ) {
				// Fallback if service fails
			}
		}

		$item_classes = array( 'bp-gift-item' );
		if ( ! $can_afford && $gift_cost > 0 ) {
			$item_classes[] = 'bp-gift-insufficient-funds';
		}
		if ( $gift_cost > 0 ) {
			$item_classes[] = 'bp-gift-has-cost';
		}

		$aria_label = sprintf(
			/* translators: %s: Gift name */
			__( 'Select gift: %s', 'bp-gifts' ),
			$gift['name'] . $categories_text
		);

		if ( $gift_cost > 0 ) {
			$aria_label .= sprintf(
				/* translators: %s: cost */
				__( ' (Cost: %s)', 'bp-gifts' ),
				$cost_display
			);
		}

		if ( ! $can_afford && $gift_cost > 0 ) {
			$aria_label .= ' ' . __( '(Insufficient funds)', 'bp-gifts' );
		}

		ob_start();
		?>
		<li class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>" role="gridcell">
			<button 
				type="button"
				class="bp-gift-item-ele" 
				data-id="<?php echo esc_attr( $gift['id'] ); ?>" 
				data-image="<?php echo esc_url( $gift['image'] ); ?>"
				data-name="<?php echo esc_attr( $gift['name'] ); ?>"
				data-cost="<?php echo esc_attr( $gift_cost ); ?>"
				data-can-afford="<?php echo esc_attr( $can_afford ? '1' : '0' ); ?>"
				aria-label="<?php echo esc_attr( $aria_label ); ?>"
				<?php echo $can_afford ? '' : 'disabled'; ?>
				tabindex="0">
				<img 
					src="<?php echo esc_url( $gift['image'] ); ?>" 
					alt="<?php echo esc_attr( $gift['image_alt'] ?: $gift['name'] ); ?>" 
					loading="lazy" />
				<div class="bp-gift-title">
					<?php echo esc_html( $gift['name'] ); ?>
				</div>
				<?php if ( $args['show_categories'] && ! empty( $gift['categories'] ) ) : ?>
					<div class="bp-gift-categories">
						<?php echo esc_html( implode( ', ', $gift['categories'] ) ); ?>
					</div>
				<?php endif; ?>
				<?php if ( $args['show_mycred'] && $gift_cost > 0 ) : ?>
					<div class="bp-gift-cost">
						<?php if ( $can_afford ) : ?>
							<span class="bp-cost-amount"><?php echo esc_html( $cost_display ); ?></span>
						<?php else : ?>
							<span class="bp-cost-insufficient">
								<?php echo esc_html( $cost_display ); ?>
								<small><?php esc_html_e( 'Insufficient funds', 'bp-gifts' ); ?></small>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( ! $can_afford && $gift_cost > 0 ) : ?>
					<div class="bp-gift-overlay" aria-hidden="true">
						<span class="bp-insufficient-icon">🚫</span>
					</div>
				<?php endif; ?>
			</button>
		</li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render gift display in message.
	 *
	 * @since 2.1.0
	 * @param array $gift Gift data.
	 * @param array $args Rendering arguments.
	 * @return string HTML output.
	 */
	public function render_message_gift( array $gift, array $args = array() ) {
		$defaults = array(
			'show_name' => true,
			'show_remove' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		ob_start();
		?>
		<div class="bp-gift-holder" role="img" aria-label="<?php echo esc_attr( sprintf( __( 'Gift: %s', 'bp-gifts' ), $gift['name'] ) ); ?>">
			<img 
				src="<?php echo esc_url( $gift['image'] ); ?>" 
				alt="<?php echo esc_attr( $gift['image_alt'] ?: $gift['name'] ); ?>" />
			
			<?php if ( $args['show_name'] ) : ?>
				<div class="bp-gift-name"><?php echo esc_html( $gift['name'] ); ?></div>
			<?php endif; ?>
			
			<?php if ( $args['show_remove'] ) : ?>
				<div class="bp-gift-remover">
					<button 
						type="button"
						class="bp-gift-remove" 
						aria-label="<?php esc_attr_e( 'Remove selected gift', 'bp-gifts' ); ?>"
						title="<?php esc_attr_e( 'Remove gift', 'bp-gifts' ); ?>">
						&times;
					</button>
				</div>
				<input type="hidden" name="bp_gift_id" value="<?php echo esc_attr( $gift['id'] ); ?>" />
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render search and filter controls.
	 *
	 * @since 2.1.0
	 * @param array $categories Available categories.
	 * @param array $args       Rendering arguments.
	 * @return string HTML output.
	 */
	public function render_search_controls( array $categories, array $args = array() ) {
		$defaults = array(
			'show_search' => true,
			'show_categories' => true,
			'search_placeholder' => __( 'Search gifts...', 'bp-gifts' ),
		);

		$args = wp_parse_args( $args, $defaults );

		ob_start();
		?>
		<div class="bp-gifts-search-controls" role="search" aria-label="<?php esc_attr_e( 'Gift search and filters', 'bp-gifts' ); ?>">
			<?php if ( $args['show_search'] ) : ?>
				<div class="bp-gifts-search-field">
					<label for="bp-gifts-search" class="screen-reader-text">
						<?php esc_html_e( 'Search gifts', 'bp-gifts' ); ?>
					</label>
					<input 
						type="search" 
						id="bp-gifts-search"
						class="bp-gifts-search" 
						placeholder="<?php echo esc_attr( $args['search_placeholder'] ); ?>"
						aria-describedby="bp-gifts-search-help">
					<div id="bp-gifts-search-help" class="screen-reader-text">
						<?php esc_html_e( 'Type to search through available gifts by name', 'bp-gifts' ); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $args['show_categories'] && ! empty( $categories ) ) : ?>
				<div class="bp-gifts-category-filter">
					<label for="bp-gifts-category-select">
						<?php esc_html_e( 'Filter by category:', 'bp-gifts' ); ?>
					</label>
					<select id="bp-gifts-category-select" class="bp-gifts-category-filter-select">
						<option value=""><?php esc_html_e( 'All categories', 'bp-gifts' ); ?></option>
						<?php foreach ( $categories as $category ) : ?>
							<option value="<?php echo esc_attr( $category['slug'] ); ?>">
								<?php echo esc_html( $category['name'] ); ?> (<?php echo esc_html( $category['count'] ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<div class="bp-gifts-search-results-info" aria-live="polite" aria-atomic="true">
				<span class="bp-gifts-results-count"></span>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a thread gift for display.
	 *
	 * @since 2.1.0
	 * @param array $gift Gift data.
	 * @return string Rendered gift HTML.
	 */
	public function render_thread_gift( array $gift ) {
		if ( empty( $gift ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="bp-gift-thread-display" role="region" aria-label="<?php esc_attr_e( 'Thread gift', 'bp-gifts' ); ?>">
			<div class="bp-gift-thread-wrapper">
				<div class="bp-gift-thread-indicator">
					<span class="bp-gift-icon" aria-hidden="true">🎁</span>
					<span class="bp-gift-label"><?php esc_html_e( 'Thread Gift', 'bp-gifts' ); ?></span>
				</div>
				<div class="bp-gift-thread-content">
					<img src="<?php echo esc_url( $gift['image'] ); ?>" 
						 alt="<?php echo esc_attr( $gift['name'] ); ?>" 
						 class="bp-gift-thread-image" />
					<div class="bp-gift-thread-info">
						<h4 class="bp-gift-thread-name"><?php echo esc_html( $gift['name'] ); ?></h4>
						<?php if ( ! empty( $gift['description'] ) ) : ?>
							<p class="bp-gift-thread-description"><?php echo esc_html( $gift['description'] ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render all gifts in a thread.
	 *
	 * @since 2.1.0
	 * @param array $gifts Array of gifts with metadata.
	 * @return string HTML output.
	 */
	public function render_thread_gifts( array $gifts ) {
		if ( empty( $gifts ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="bp-gifts-thread-summary" role="region" aria-label="<?php esc_attr_e( 'Gifts in this conversation', 'bp-gifts' ); ?>">
			<div class="bp-gifts-thread-header">
				<span class="bp-gifts-icon" aria-hidden="true">🎁</span>
				<h3 class="bp-gifts-thread-title">
					<?php
					printf(
						/* translators: %d: number of gifts */
						_n( '%d Gift in this conversation', '%d Gifts in this conversation', count( $gifts ), 'bp-gifts' ),
						count( $gifts )
					);
					?>
				</h3>
			</div>
			<div class="bp-gifts-thread-list">
				<?php foreach ( $gifts as $gift_data ) : ?>
					<?php
					$gift = $gift_data['gift'];
					$sender_name = bp_core_get_user_displayname( $gift_data['sender_id'] );
					$is_current_user = $gift_data['sender_id'] == bp_loggedin_user_id();
					?>
					<div class="bp-gift-thread-item <?php echo $is_current_user ? 'sent' : 'received'; ?>">
						<div class="bp-gift-thread-meta">
							<span class="bp-gift-sender">
								<?php
								if ( $is_current_user ) {
									esc_html_e( 'You sent:', 'bp-gifts' );
								} else {
									printf(
										/* translators: %s: sender name */
										esc_html__( '%s sent:', 'bp-gifts' ),
										esc_html( $sender_name )
									);
								}
								?>
							</span>
							<time class="bp-gift-date" datetime="<?php echo esc_attr( $gift_data['sent_date'] ); ?>">
								<?php echo esc_html( bp_format_time( strtotime( $gift_data['sent_date'] ) ) ); ?>
							</time>
						</div>
						<div class="bp-gift-thread-content">
							<img src="<?php echo esc_url( $gift['image'] ); ?>" 
								 alt="<?php echo esc_attr( $gift['name'] ); ?>" 
								 class="bp-gift-thread-image" />
							<div class="bp-gift-thread-info">
								<h4 class="bp-gift-thread-name"><?php echo esc_html( $gift['name'] ); ?></h4>
								<?php if ( ! empty( $gift['description'] ) ) : ?>
									<p class="bp-gift-thread-description"><?php echo esc_html( $gift['description'] ); ?></p>
								<?php endif; ?>
								<?php if ( ! empty( $gift['category'] ) ) : ?>
									<span class="bp-gift-category"><?php echo esc_html( $gift['category'] ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}