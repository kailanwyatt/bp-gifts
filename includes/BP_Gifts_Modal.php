<?php
/**
 * BP Gifts - Modal Class
 *
 * Handles gift modal and display functionality.
 *
 * @package BP_Gifts
 * @since   2.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Gifts Modal class.
 *
 * @since 2.2.0
 */
class BP_Gifts_Modal {

	/**
	 * Render the gift composer interface.
	 *
	 * @since 2.2.0
	 * @return string HTML output.
	 */
	public function render_gift_composer() {
		if ( ! BP_Gifts_Settings::is_gifts_enabled() ) {
			return '';
		}

		$gifts = new BP_Gifts_Gifts();
		$all_gifts = $gifts->get_all_gifts();
		
		if ( empty( $all_gifts ) ) {
			return '';
		}

		ob_start();
		?>
		<div id="bp-gifts-composer" class="bp-gifts-composer">
			<button type="button" id="bp-send-gift-btn" class="bp-gifts-open-modal button" aria-expanded="false">
				<?php esc_html_e( 'Attach Gift', 'bp-gifts' ); ?>
			</button>
			
			<div class="bp-gift-edit-container"></div>
			
			<div id="bpmodalbox" class="bp-gifts-modal easy-modal" style="display: none;" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="bp-gifts-modal-title">
				<div class="bp-gifts-modal-content">
					<div class="bp-modal-header">
						<h3 id="bp-gifts-modal-title"><?php esc_html_e( 'Select a Gift', 'bp-gifts' ); ?></h3>
						<button type="button" class="bp-modal-close" aria-label="<?php esc_attr_e( 'Close', 'bp-gifts' ); ?>">&times;</button>
					</div>
					
					<div class="bp-gifts-search-controls">
						<div class="bp-gifts-search-field">
							<input type="text" id="bp-gifts-search" class="bp-gifts-search" placeholder="<?php esc_attr_e( 'Search gifts...', 'bp-gifts' ); ?>" />
						</div>
						
						<div class="bp-gifts-category-filter">
							<label for="bp-gifts-category-select"><?php esc_html_e( 'Category:', 'bp-gifts' ); ?></label>
							<select id="bp-gifts-category-select">
								<option value=""><?php esc_html_e( 'All Categories', 'bp-gifts' ); ?></option>
								<?php
								$categories = get_terms( array(
									'taxonomy' => 'gift_category',
									'hide_empty' => true,
								) );
								
								foreach ( $categories as $category ) {
									echo '<option value="' . esc_attr( $category->slug ) . '">' . esc_html( $category->name ) . '</option>';
								}
								?>
							</select>
						</div>
						
						<div class="bp-gifts-results-count" aria-live="polite"></div>
					</div>
					
					<div id="bp-gifts-list" class="bp-gifts-list">
						<div class="list">
							<?php foreach ( $all_gifts as $gift ) : ?>
								<div class="bp-gift-item-ele" 
									 data-id="<?php echo esc_attr( $gift->id ); ?>" 
									 data-name="<?php echo esc_attr( $gift->title ); ?>"
									 data-image="<?php echo esc_url( $gift->thumbnail ); ?>"
									 tabindex="0"
									 role="button"
									 aria-label="<?php printf( esc_attr__( 'Select gift: %s', 'bp-gifts' ), $gift->title ); ?>">
									
									<div class="bp-gift-thumbnail">
										<?php if ( $gift->thumbnail ) : ?>
											<img src="<?php echo esc_url( $gift->thumbnail ); ?>" alt="<?php echo esc_attr( $gift->title ); ?>" />
										<?php else : ?>
											<div class="bp-gift-placeholder">
												<span class="dashicons dashicons-heart"></span>
											</div>
										<?php endif; ?>
									</div>
									
									<div class="bp-gift-title"><?php echo esc_html( $gift->title ); ?></div>
									
									<?php if ( $gift->cost && BP_Gifts_Settings::is_mycred_enabled() ) : ?>
										<div class="bp-gift-cost"><?php echo esc_html( $gift->cost ); ?> <?php esc_html_e( 'points', 'bp-gifts' ); ?></div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
						
						<div class="bp-gift-pagination"></div>
					</div>
				</div>
			</div>
			
			<input type="hidden" name="bp_gift_id" id="bp_gift_id" value="" />
			<?php wp_nonce_field( 'bp_gifts_nonce', 'bp_gifts_nonce' ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a gift attached to a message.
	 *
	 * @since 2.2.0
	 * @param object $gift Gift object.
	 * @return string HTML output.
	 */
	public function render_message_gift( $gift ) {
		if ( ! $gift ) {
			return '';
		}

		ob_start();
		?>
		<div class="bp-message-gift">
			<div class="bp-gift-attachment">
				<div class="bp-gift-icon">
					<span class="dashicons dashicons-heart"></span>
				</div>
				
				<div class="bp-gift-details">
					<h5><?php esc_html_e( 'Gift Attached', 'bp-gifts' ); ?></h5>
					<div class="bp-gift-info">
						<?php if ( $gift->thumbnail ) : ?>
							<img src="<?php echo esc_url( $gift->thumbnail ); ?>" alt="<?php echo esc_attr( $gift->title ); ?>" class="bp-gift-thumbnail" />
						<?php endif; ?>
						<div class="bp-gift-text">
							<strong><?php echo esc_html( $gift->title ); ?></strong>
							<?php if ( $gift->description ) : ?>
								<p><?php echo esc_html( $gift->description ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render gifts for a thread.
	 *
	 * @since 2.2.0
	 * @param array $gifts Array of gift objects.
	 * @return string HTML output.
	 */
	public function render_thread_gifts( $gifts ) {
		if ( empty( $gifts ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="bp-thread-gifts">
			<h4><?php esc_html_e( 'Gifts in this conversation', 'bp-gifts' ); ?></h4>
			<div class="bp-thread-gifts-list">
				<?php foreach ( $gifts as $gift ) : ?>
					<div class="bp-thread-gift-item">
						<?php if ( $gift->thumbnail ) : ?>
							<img src="<?php echo esc_url( $gift->thumbnail ); ?>" alt="<?php echo esc_attr( $gift->title ); ?>" />
						<?php endif; ?>
						<div class="bp-gift-meta">
							<strong><?php echo esc_html( $gift->title ); ?></strong>
							<small>
								<?php
								printf(
									esc_html__( 'Sent by %s on %s', 'bp-gifts' ),
									bp_core_get_user_displayname( $gift->sender_id ),
									date_i18n( get_option( 'date_format' ), strtotime( $gift->date_sent ) )
								);
								?>
							</small>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}