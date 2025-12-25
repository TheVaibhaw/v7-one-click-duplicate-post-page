/**
 * Admin JavaScript for V7 One-Click Duplicate
 *
 * @package V7_One_Click_Duplicate
 * @since 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * V7 One-Click Duplicate handler
	 */
	const V7_OCD = {
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			// Optional: Add AJAX-based duplication (currently using standard page reload)
			// Uncomment below to enable AJAX duplication
			// $(document).on('click', '.v7_duplicate a', this.handleAjaxDuplicate.bind(this));
		},

		/**
		 * Handle AJAX duplication (optional feature for future enhancement)
		 *
		 * @param {Event} e Click event
		 */
		handleAjaxDuplicate: function(e) {
			e.preventDefault();

			const $link = $(e.currentTarget);
			const $row = $link.closest('tr');
			const postId = this.getPostIdFromRow($row);

			if (!postId) {
				return;
			}

			// Show loading state
			$link.text(v7OcdData.duplicating);
			$row.addClass('v7-ocd-duplicating');

			// Send AJAX request
			$.ajax({
				url: v7OcdData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'v7_ocd_duplicate_ajax',
					post_id: postId,
					nonce: v7OcdData.nonce
				},
				success: function(response) {
					if (response.success) {
						// Show success message
						$link.text(v7OcdData.duplicated);
						$link.addClass('v7-ocd-success');

						// Optionally reload the page after a short delay
						setTimeout(function() {
							window.location.reload();
						}, 1000);
					} else {
						// Show error
						$link.text(v7OcdData.error);
						$link.addClass('v7-ocd-error');
						$row.removeClass('v7-ocd-duplicating');

						if (response.data && response.data.message) {
							alert(response.data.message);
						}
					}
				},
				error: function() {
					$link.text(v7OcdData.error);
					$link.addClass('v7-ocd-error');
					$row.removeClass('v7-ocd-duplicating');
				}
			});
		},

		/**
		 * Get post ID from table row
		 *
		 * @param {jQuery} $row Table row
		 * @return {number|null} Post ID
		 */
		getPostIdFromRow: function($row) {
			const rowId = $row.attr('id');
			if (!rowId) {
				return null;
			}

			const match = rowId.match(/post-(\d+)/);
			return match ? parseInt(match[1], 10) : null;
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		V7_OCD.init();
	});

})(jQuery);
