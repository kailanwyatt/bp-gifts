/**
 * BP Gifts JavaScript with Enhanced Accessibility and Search
 * 
 * @package BP_Gifts
 * @since   2.1.0
 */

jQuery(document).ready(function($) {
	'use strict';

	// Initialize modal with accessibility features
	var $modal = $('#bpmodalbox');
	$modal.easyModal({
		onOpen: function() {
			// Focus management
			$modal.attr('aria-hidden', 'false');
			$('#bp-gifts-search').focus();
			// Trap focus within modal
			trapFocus($modal);
		},
		onClose: function() {
			$modal.attr('aria-hidden', 'true');
			// Return focus to trigger button
			$('#bp-send-gift-btn').focus();
		}
	});

	// Initialize gift list with search and pagination
	var paginationOptions = {
		name: 'bp-gift-pagination',
		paginationClass: 'bp-gift-pagination'
	};

	var giftList = new List('bp-gifts-list', {
		valueNames: ['bp-gift-title'],
		page: 6,
		plugins: [ListPagination(paginationOptions)]
	});

	// Search functionality
	var $searchInput = $('#bp-gifts-search');
	var $categorySelect = $('#bp-gifts-category-select');
	var $resultsInfo = $('.bp-gifts-results-count');

	// Real-time search
	$searchInput.on('input', function() {
		var searchTerm = $(this).val();
		performSearch(searchTerm, $categorySelect.val());
	});

	// Category filter
	$categorySelect.on('change', function() {
		var category = $(this).val();
		performSearch($searchInput.val(), category);
	});

	// Search function
	function performSearch(searchTerm, category) {
		// Filter by search term
		if (searchTerm) {
			giftList.search(searchTerm);
		} else {
			giftList.search();
		}

		// Additional category filtering would go here
		// This would require server-side AJAX for full implementation
		
		updateResultsInfo();
		announceResults(searchTerm, category);
	}

	// Update results count
	function updateResultsInfo() {
		var visibleCount = giftList.visibleItems.length;
		var totalCount = giftList.items.length;
		
		if (visibleCount === totalCount) {
			$resultsInfo.text(sprintf(bp_gifts_vars.showing_all_text, visibleCount));
		} else {
			$resultsInfo.text(sprintf(bp_gifts_vars.showing_filtered_text, visibleCount, totalCount));
		}
	}

	// Announce search results to screen readers
	function announceResults(searchTerm, category) {
		var announcement = '';
		var visibleCount = giftList.visibleItems.length;
		
		if (searchTerm || category) {
			announcement = sprintf(bp_gifts_vars.search_results_text, visibleCount);
		}
		
		if (announcement) {
			announceToScreenReader(announcement);
		}
	}

	// Handle gift selection button click
	$(document).on('click', '#bp-send-gift-btn, .bp-gifts-open-modal', function(event) {
		event.preventDefault();
		$(this).attr('aria-expanded', 'true');
		$modal.trigger('openModal');
	});

	// Handle modal close button
	$(document).on('click', '.bp-modal-close', function(event) {
		event.preventDefault();
		$('#bp-send-gift-btn').attr('aria-expanded', 'false');
		$modal.trigger('closeModal');
	});

	// Handle individual gift selection
	$(document).on('click keydown', '.bp-gift-item-ele', function(event) {
		// Handle both click and Enter/Space key presses
		if (event.type === 'click' || 
			(event.type === 'keydown' && (event.which === 13 || event.which === 32))) {
			
			event.preventDefault();
			
			var $this = $(this);
			var image = $this.data('image');
			var id = $this.data('id');
			var name = $this.data('name') || $this.find('.bp-gift-title').text().trim();

			if (!image || !id) {
				return;
			}

			// Check if we're in thread context
			var isThreadContext = bp_gifts_vars.thread_id || $('body').hasClass('messages-thread') || 
								  $('.thread-content').length > 0 ||
								  $('[name="thread_id"]').length > 0;

			// Store gift selection in cookie for reliable AJAX handling
			var threadId = isThreadContext ? (bp_gifts_vars.thread_id || getThreadId()) : null;
			var cookieData = {
				gift_id: id,
				gift_name: name,
				gift_image: image,
				thread_id: threadId,
				context: isThreadContext ? 'thread' : 'message',
				timestamp: Date.now()
			};
            console.log('Setting cookie data:', cookieData);
			
			// Set cookie with gift data (expires in 1 hour)
			setCookie('bp_gifts_selected', JSON.stringify(cookieData), 60);
			
			// Also set a simple hidden input for backward compatibility
			var hiddenInput = '<input type="hidden" name="bp_gift_id" value="' + id + '" />';
			if ($('input[name="bp_gift_id"]').length) {
				$('input[name="bp_gift_id"]').val(id);
			} else {
				$('.bp-gift-edit-container').append(hiddenInput);
			}

			// Create gift display with accessibility
			var html = '<div class="bp-gift-holder" role="img" aria-label="' + 
				sprintf(bp_gifts_vars.selected_gift_text, name) + '"' +
				(isThreadContext ? ' data-attach-to="thread"' : ' data-attach-to="message"') + 
				' data-gift-id="' + id + '">' +
				'<img src="' + image + '" alt="' + name + '" />' +
				'<div class="bp-gift-name">' + name + '</div>';

			// Add thread/message indicator
			if (isThreadContext) {
				html += '<div class="bp-gift-attachment-type">' + 
					bp_gifts_vars.attached_to_thread_text + '</div>';
			}

			html += '<div class="bp-gift-remover">' +
					'<button type="button" class="bp-gift-remove" ' +
						'aria-label="' + bp_gifts_vars.remove_text + '" ' +
						'title="' + bp_gifts_vars.remove_text + '">&times;</button>' +
				'</div>' +
				'</div>';

			$('.bp-gift-edit-container').html(html);
			$('#bp-send-gift-btn').attr('aria-expanded', 'false');
			$modal.trigger('closeModal');
			
			// Announce selection to screen readers
			var announcement = isThreadContext ? 
				sprintf(bp_gifts_vars.gift_selected_for_thread_text, name) :
				sprintf(bp_gifts_vars.gift_selected_text, name);
			announceToScreenReader(announcement);
		}
	});

	// Handle gift removal
	$(document).on('click', '.bp-gift-remove', function(event) {
		event.preventDefault();
		var $giftHolder = $(this).closest('.bp-gift-holder');
		var giftName = $giftHolder.find('.bp-gift-name').text();
		
		// Clear gift selection cookie
		deleteCookie('bp_gifts_selected');
		
		// Remove hidden input if it exists
		$('input[name="bp_gift_id"]').remove();
		
		$giftHolder.slideUp(300, function() {
			$(this).remove();
			// Announce removal to screen readers
			announceToScreenReader(sprintf(bp_gifts_vars.gift_removed_text, giftName));
		});
	});

	// Keyboard navigation for gift grid
	$(document).on('keydown', '.bp-gift-item-ele', function(event) {
		var $items = $('.bp-gift-item-ele');
		var currentIndex = $items.index(this);
		var newIndex = currentIndex;

		switch(event.which) {
			case 37: // Left arrow
				newIndex = currentIndex > 0 ? currentIndex - 1 : $items.length - 1;
				break;
			case 39: // Right arrow
				newIndex = currentIndex < $items.length - 1 ? currentIndex + 1 : 0;
				break;
			case 38: // Up arrow
				newIndex = currentIndex - 3; // Assuming 3 columns
				if (newIndex < 0) newIndex = currentIndex;
				break;
			case 40: // Down arrow
				newIndex = currentIndex + 3; // Assuming 3 columns
				if (newIndex >= $items.length) newIndex = currentIndex;
				break;
			default:
				return; // Allow other keys to work normally
		}
		
		if (newIndex !== currentIndex) {
			event.preventDefault();
			$items.eq(newIndex).focus();
		}
	});

	// Utility functions
	
	// Simple sprintf-like function for string formatting
	function sprintf(str) {
		var args = Array.prototype.slice.call(arguments, 1);
		return str.replace(/%[sd%]/g, function(match) {
			return args.shift() || match;
		});
	}

	// Screen reader announcements
	function announceToScreenReader(message) {
		var $announcement = $('<div>', {
			'aria-live': 'polite',
			'aria-atomic': 'true',
			'class': 'sr-only bp-gifts-sr-announcement'
		}).text(message);
		
		$('body').append($announcement);
		
		setTimeout(function() {
			$announcement.remove();
		}, 1000);
	}

	// Focus trap for modal accessibility
	function trapFocus($container) {
		var focusableElements = $container.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
		var firstElement = focusableElements.first();
		var lastElement = focusableElements.last();

		$container.on('keydown.focustrap', function(e) {
			if (e.which === 9) { // Tab key
				if (e.shiftKey) {
					if (document.activeElement === firstElement[0]) {
						lastElement.focus();
						e.preventDefault();
					}
				} else {
					if (document.activeElement === lastElement[0]) {
						firstElement.focus();
						e.preventDefault();
					}
				}
			}
		});
	}

	// Get thread ID from various possible sources
	function getThreadId() {
		// First, try the server-provided thread ID (most reliable)
		if (bp_gifts_vars && bp_gifts_vars.thread_id && bp_gifts_vars.thread_id > 0) {
			return bp_gifts_vars.thread_id;
		}
		
		// Try different client-side methods as fallback
		var threadId = $('[name="thread_id"]').val();
		if (threadId) return threadId;
		
		// Try from URL parameters
		var urlParams = new URLSearchParams(window.location.search);
		threadId = urlParams.get('thread_id');
		if (threadId) return threadId;
		
		// Try from data attributes
		threadId = $('.thread-content').data('thread-id');
		if (threadId) return threadId;
		
		// Try from body class
		var bodyClasses = $('body').attr('class') || '';
		var match = bodyClasses.match(/thread-(\d+)/);
		if (match) return match[1];
		
		return null;
	}

	// Clear search
	$(document).on('click', '.bp-gifts-clear-search', function(event) {
		event.preventDefault();
		$searchInput.val('');
		$categorySelect.val('');
		giftList.search();
		updateResultsInfo();
		$searchInput.focus();
		announceToScreenReader(bp_gifts_vars.search_cleared_text || 'Search cleared');
	});

	// Initial setup
	updateResultsInfo();
	
	// Add ARIA attributes to modal
	$modal.attr({
		'role': 'dialog',
		'aria-modal': 'true',
		'aria-hidden': 'true',
		'aria-labelledby': 'bp-gifts-modal-title'
	});

	// Add loading state support
	function showLoading() {
		$('.bp-gifts-list').addClass('bp-gifts-loading');
		announceToScreenReader(bp_gifts_vars.loading_text || 'Loading...');
	}

	function hideLoading() {
		$('.bp-gifts-list').removeClass('bp-gifts-loading');
	}

	// Cookie utility functions
	function setCookie(name, value, minutes) {
		var expires = "";
		if (minutes) {
			var date = new Date();
			date.setTime(date.getTime() + (minutes * 60 * 1000));
			expires = "; expires=" + date.toUTCString();
		}
		document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/; SameSite=Lax";
	}

	function getCookie(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) === ' ') c = c.substring(1, c.length);
			if (c.indexOf(nameEQ) === 0) {
				return decodeURIComponent(c.substring(nameEQ.length, c.length));
			}
		}
		return null;
	}

	function deleteCookie(name) {
		document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
	}

	// Hook into BuddyPress message send/reply completion
	$(document).ajaxComplete(function(event, xhr, settings) {
		// Check if this was a message send or reply AJAX request
		if (settings.data && (
			settings.data.indexOf('action=messages_send_message') > -1 ||
			settings.data.indexOf('action=messages_send_reply') > -1
		)) {
			// Check if the request was successful
			if (xhr.status === 200) {
				try {
					var response = JSON.parse(xhr.responseText);
					if (response.success || (response.type && response.type !== 'error')) {
						// Message was sent successfully, trigger gift processing
						var giftData = getCookie('bp_gifts_selected');
						if (giftData) {
							// Send gift data to server for processing
							processGiftAfterMessage(JSON.parse(giftData));
							// Clear the cookie
							deleteCookie('bp_gifts_selected');
						}
					}
				} catch (e) {
					// If response isn't JSON, assume success and process gift anyway
					var giftData = getCookie('bp_gifts_selected');
					if (giftData) {
						processGiftAfterMessage(JSON.parse(giftData));
						deleteCookie('bp_gifts_selected');
					}
				}
			}
		}
	});

	// Process gift data after message is sent
	function processGiftAfterMessage(giftData) {
		// Send AJAX request to process the gift relationship
		$.ajax({
			url: bp_gifts_vars.ajax_url || (window.ajaxurl || '/wp-admin/admin-ajax.php'),
			type: 'POST',
			data: {
				action: 'bp_gifts_process_post_message',
				gift_data: giftData,
				nonce: bp_gifts_vars.nonce
			},
			success: function(response) {
				console.log('BP Gifts: Gift relationship processed successfully');
			},
			error: function() {
				console.log('BP Gifts: Error processing gift relationship');
			}
		});
	}
});