/**
 * Product Descriptions Admin JavaScript
 *
 * Enhanced version with proper TinyMCE initialization, duplicate prevention, and Bootstrap accordion integration.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.4
 */

(function($) {
	'use strict';

	let descriptionIndex = 0;

	/**
	 * Initialize WordPress editor for a specific textarea
	 *
	 * Uses wp.editor.initialize() with enhanced error handling and retry mechanism.
	 * Only initializes if not already present.
	 *
	 * @since 0.2.4
	 *
	 * @param {string} editorId - Editor ID
	 * @param {boolean} forceReInit - Force re-initialization even if editor exists
	 *
	 * @return {void}
	 * @throws {Error} - If editor initialization fails after retries
	 */
	function initializeWordPressEditor(editorId, forceReInit = false) {
		console.log('Initializing WordPress editor for:', editorId);

		// Check if element exists
		if (!$('#' + editorId).length) {
			console.error('Editor element not found:', editorId);
			return;
		}

		// Check if editor is already initialized and working (unless forcing re-init)
		if (!forceReInit && typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
			console.log('Editor already initialized for:', editorId);
			return;
		}

		// Only remove existing instances if we're forcing re-initialization
		if (forceReInit) {
			removeEditorInstances(editorId);
		}

		/**
		 * Attempt to initialize editor with retry mechanism
		 *
		 * @param {number} attempt - Current attempt number
		 *
		 * @return {void}
		 */
		function attemptInitialization(attempt = 1) {
			const maxAttempts = 3;
			const delay = attempt * 500; // Increasing delay for each attempt

			setTimeout(function() {
				if (typeof wp === 'undefined' || !wp.editor) {
					console.error('wp.editor not available on attempt', attempt);
					if (attempt < maxAttempts) {
						attemptInitialization(attempt + 1);
					}
					return;
				}

				// Double-check that the element still exists and is visible
				const $element = $('#' + editorId);
				if (!$element.length) {
					console.error('Editor element disappeared:', editorId);
					return;
				}

				// Check if the element is inside a collapsed area
				const $collapsed = $element.closest('.collapse:not(.show)');
				if ($collapsed.length > 0) {
					console.log('Editor element is in collapsed area, expanding first:', editorId);
					$collapsed.addClass('show');
					// Wait a bit more for the expansion animation
					setTimeout(function() {
						attemptInitialization(attempt);
					}, 300);
					return;
				}

				console.log('Attempting wp.editor.initialize for:', editorId, 'attempt:', attempt);

				// Ensure the textarea has proper attributes for wp.editor
				const $textarea = $('#' + editorId);
				if (!$textarea.attr('name')) {
					console.log('Adding missing name attribute to textarea');
					$textarea.attr('name', editorId + '_content');
				}

				try {
					// Use the same configuration as the PHP wp_editor() call
					wp.editor.initialize(editorId, {
						tinymce: {
							toolbar1: 'bold italic strikethrough bullist numlist blockquote hr alignleft aligncenter alignright link unlink',
							toolbar2: ''
						},
						quicktags: true,
						mediaButtons: false
					});

					console.log('wp.editor.initialize call completed for:', editorId);

				} catch (error) {
					console.error('Error initializing WordPress editor on attempt', attempt, ':', error);
					if (attempt < maxAttempts) {
						console.log('Retrying initialization for:', editorId);
						attemptInitialization(attempt + 1);
					} else {
						console.error('Failed to initialize WordPress editor after', maxAttempts, 'attempts for:', editorId);
						throw new Error('Failed to initialize WordPress editor after ' + maxAttempts + ' attempts');
					}
				}
			}, delay);
		}

		attemptInitialization();
	}

	/**
	 * Remove all editor instances for a given ID
	 *
	 * @since 0.2.4
	 *
	 * @param {string} editorId - Editor ID
	 *
	 * @return {void}
	 */
	function removeEditorInstances(editorId) {
		// Remove TinyMCE instance
		if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
			console.log('Removing TinyMCE instance:', editorId);
			tinymce.remove('#' + editorId);
		}

		// Remove QuickTags instance
		if (typeof QTags !== 'undefined' && QTags.instances[editorId]) {
			console.log('Removing QuickTags instance:', editorId);
			delete QTags.instances[editorId];
		}

		// Remove wp.editor instance
		if (typeof wp !== 'undefined' && wp.editor) {
			wp.editor.remove(editorId);
		}
	}

	/**
	 * Get list of currently selected description types
	 *
	 * @since 0.2.4
	 *
	 * @return {Array} - Array of selected type values
	 */
	function getSelectedDescriptionTypes() {
		const selectedTypes = [];
		$('.accordion-item').find('select[name*="[type]"]').each(function() {
			const value = $(this).val();
			if (value) {
				selectedTypes.push(value);
			}
		});
		return selectedTypes;
	}

	/**
	 * Update dropdown options to hide already selected types
	 *
	 * @since 0.2.4
	 *
	 * @param {jQuery} $excludeSelect - Select element to exclude from filtering
	 *
	 * @return {void}
	 */
	function updateDescriptionTypeDropdowns($excludeSelect = null) {
		const selectedTypes = getSelectedDescriptionTypes();
		console.log('Currently selected types:', selectedTypes);

		$('.accordion-item').find('select[name*="[type]"]').each(function() {
			const $select = $(this);

			// Skip the excluded select (usually the one being changed)
			if ($excludeSelect && $select.is($excludeSelect)) {
				return;
			}

			const currentValue = $select.val();

			// Reset all options to visible first
			$select.find('option').each(function() {
				const $option = $(this);
				const optionValue = $option.val();

				if (optionValue === '') {
					// Always show the empty option
					$option.show().prop('disabled', false);
				} else if (selectedTypes.includes(optionValue) && optionValue !== currentValue) {
					// Hide options that are selected elsewhere
					$option.hide().prop('disabled', true);
					console.log('Hiding option:', optionValue, 'because it is selected elsewhere');
				} else {
					// Show available options
					$option.show().prop('disabled', false);
				}
			});
		});
	}

	/**
	 * Get next available description index
	 *
	 * @since 0.2.4
	 *
	 * @return {number} - Next available index
	 */
	function getNextDescriptionIndex() {
		const existingIndices = [];
		$('.accordion-item').each(function() {
			const index = parseInt($(this).data('index'), 10);
			if (!isNaN(index)) {
				existingIndices.push(index);
			}
		});

		// Find the highest existing index and add 1
		let nextIndex = 0;
		if (existingIndices.length > 0) {
			nextIndex = Math.max(...existingIndices) + 1;
		}

		// Also ensure it's higher than our global counter
		nextIndex = Math.max(nextIndex, descriptionIndex);

		console.log('Existing indices:', existingIndices, 'Next index:', nextIndex);

		return nextIndex;
	}

	/**
	 * Update all description indices to prevent conflicts
	 * Only called when actually needed (after drag/drop)
	 *
	 * @since 0.2.4
	 *
	 * @return {void}
	 */
	function updateDescriptionIndices() {
		console.log('Updating description indices');

		$('#descriptions-container .description-item').each(function(newIndex) {
			const $item = $(this);
			const oldIndex = $item.data('index');

			if (oldIndex !== newIndex) {
				console.log('Updating index from', oldIndex, 'to', newIndex);

				// Update data attribute
				$item.attr('data-index', newIndex).data('index', newIndex);

				// Update form field names and IDs
				$item.find('select[name*="[type]"]').attr('name', 'product_descriptions[' + newIndex + '][type]');
				$item.find('input[name*="[title]"]').attr('name', 'product_descriptions[' + newIndex + '][title]');
				$item.find('textarea[name*="[content]"]').attr('name', 'product_descriptions[' + newIndex + '][content]');

				// Update textarea ID
				const $textarea = $item.find('textarea[id^="description_content_"]');
				const oldEditorId = $textarea.attr('id');
				const newEditorId = 'description_content_' + newIndex;

				if (oldEditorId !== newEditorId) {
					// Remove old editor instances
					removeEditorInstances(oldEditorId);

					// Update textarea ID
					$textarea.attr('id', newEditorId);

					// Re-initialize editor with new ID (force re-init since we changed the ID)
					initializeWordPressEditor(newEditorId, true);
				}
			}
		});
	}

	// Initialize when document is ready
	$(document).ready(function() {
		// Debug: Check what elements we can find
		console.log('Total description items found:', $('.description-item').length);
		console.log('Total accordion items found:', $('.accordion-item').length);
		console.log('Total items with data-index:', $('[data-index]').length);

		// Set initial index to the highest existing index + 1
		const existingIndices = [];

		// Try multiple selectors to find existing items
		$('.accordion-item').each(function() {
			const index = parseInt($(this).data('index'), 10);
			console.log('Found item with data-index:', $(this).data('index'), 'parsed as:', index);
			if (!isNaN(index)) {
				existingIndices.push(index);
			}
		});

		if (existingIndices.length > 0) {
			descriptionIndex = Math.max(...existingIndices) + 1;
		} else {
			descriptionIndex = 0;
		}

		console.log('Product Descriptions JavaScript initializing. Existing indices:', existingIndices, 'Initial index set to:', descriptionIndex);

		// Only initialize editors for textareas that don't have TinyMCE yet
		// This typically means template-generated textareas that need conversion
		$('.description-item textarea[id^="description_content_"]').each(function() {
			const editorId = $(this).attr('id');
			if (editorId) {
				// Check if this is a plain textarea (no TinyMCE wrapper)
				const $textarea = $(this);
				const hasWpEditor = $textarea.closest('.wp-editor-wrap').length > 0;
				const hasTinyMCE = typeof tinymce !== 'undefined' && tinymce.get(editorId);

				if (!hasWpEditor && !hasTinyMCE) {
					console.log('Initializing TinyMCE for plain textarea:', editorId);
					initializeWordPressEditor(editorId, false);
				} else {
					console.log('Skipping initialization for existing editor:', editorId);
				}
			}
		});

		// Update dropdown filters on page load
		updateDescriptionTypeDropdowns();

		// Add description button
		$('#add-description').on('click', function(e) {
			e.preventDefault();

			// Get the next available index (avoids conflicts)
			const nextIndex = getNextDescriptionIndex();

			console.log('Adding new description. Using index:', nextIndex);

			const template = $('#description-template').html();
			if (!template) {
				console.error('Template not found');
				return;
			}

			const newDescription = template.replace(/\{\{INDEX\}\}/g, nextIndex);

			// Hide empty state message
			$('.no-descriptions-message').hide();

			// Add new description to container
			$('#descriptions-container').append(newDescription);

			// Set the data-index attribute for the new item
			const $newItem = $('#descriptions-container .description-item').last();
			$newItem.attr('data-index', nextIndex).data('index', nextIndex);

			// Initialize editor for the new description
			const editorId = 'description_content_' + nextIndex;

			// Show content area using Bootstrap accordion
			const $collapseContent = $newItem.find('.accordion-collapse');
			$collapseContent.addClass('show');

			// Update the accordion button to reflect expanded state
			const $toggleButton = $newItem.find('.accordion-button');
			$toggleButton.removeClass('collapsed').attr('aria-expanded', 'true');
			$toggleButton.find('.dashicons').removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');

			// Initialize WordPress editor (force initialization for new editors)
			initializeWordPressEditor(editorId, true);

			// Update dropdown filters
			updateDescriptionTypeDropdowns();

			// Update the global counter for next time
			descriptionIndex = Math.max(descriptionIndex, nextIndex + 1);
		});

		// Remove description button
		$(document).on('click', '.description-remove-handle', function(e) {
			e.preventDefault();

			const $card = $(this).closest('.accordion-item');
			const editorId = $card.find('textarea[id^="description_content_"]').attr('id');

			console.log('Removing description with editor ID:', editorId);

			// Remove editor instances
			if (editorId) {
				removeEditorInstances(editorId);
			}

			// Remove the card with animation
			$card.fadeOut(300, function() {
				$(this).remove();

				// Update dropdown filters after removal
				updateDescriptionTypeDropdowns();

				// Show empty state if no descriptions left
				if ($('#descriptions-container .accordion-item').length === 0) {
					$('.no-descriptions-message').show();
				}
			});
		});

		// Handle description type change - update dropdown filters
		$(document).on('change', '.description-item select[name*="[type]"], .accordion-item select[name*="[type]"]', function() {
			updateDescriptionTypeDropdowns($(this));
		});

		// Bootstrap accordion event handlers for updating toggle icons
		$(document).on('show.bs.collapse', '.accordion-collapse', function() {
			const $toggleButton = $(this).closest('.accordion-item').find('.accordion-button');
			$toggleButton.removeClass('collapsed');
			$toggleButton.find('.dashicons').removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
		});

		$(document).on('hide.bs.collapse', '.accordion-collapse', function() {
			const $toggleButton = $(this).closest('.accordion-item').find('.accordion-button');
			$toggleButton.addClass('collapsed');
			$toggleButton.find('.dashicons').removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
		});

		// Initialize sortable
		$('#descriptions-container').sortable({
			handle: '.description-drag-handle',
			placeholder: 'sortable-placeholder',
			helper: 'clone',
			opacity: 0.8,
			cursor: 'move',
			tolerance: 'pointer',
			start: function(e, ui) {
				ui.placeholder.height(ui.item.height());
			},
			stop: function() {
				// Only update indices after drag and drop (when really needed)
				updateDescriptionIndices();
			}
		});

		// Form validation
		$('form').on('submit', function(e) {
			const errors = [];
			const usedTypes = [];

			// Check each description
			$('.accordion-item').each(function() {
				const $item = $(this);
				const type = $item.find('select[name*="[type]"]').val();
				const content = $item.find('textarea[name*="[content]"]').val();

				// Check for required fields
				if (!type) {
					errors.push('All descriptions must have a type selected.');
					$item.find('select[name*="[type]"]').addClass('is-invalid');
				} else {
					$item.find('select[name*="[type]"]').removeClass('is-invalid');
				}

				if (!content || content.trim() === '') {
					errors.push('All descriptions must have content.');
					$item.find('textarea[name*="[content]"]').addClass('is-invalid');
				} else {
					$item.find('textarea[name*="[content]"]').removeClass('is-invalid');
				}

				// Check for duplicate types
				if (type && usedTypes.includes(type)) {
					errors.push(`Duplicate description type: ${type}. Each type can only be used once.`);
					$item.find('select[name*="[type]"]').addClass('is-invalid');
				} else if (type) {
					usedTypes.push(type);
				}
			});

			// Display errors if any
			if (errors.length > 0) {
				e.preventDefault();
				alert('Please fix the following errors:\n\n' + errors.join('\n'));
				console.error('Form validation failed:', errors);
				return false;
			}

			console.log('Form validation passed');
			return true;
		});

		console.log('Product Descriptions JavaScript initialized');
	});

})(jQuery);
