/**
 * Admin script for Product Lines management
 *
 * Handles the dynamic interface for managing product line media and attributes.
 *
 * @param $
 * @package
 * @since   0.2.0
 */

( function ( $ ) {
	$( document ).ready( function () {
		// Check if this is the product_line taxonomy page
		if (
			typeof pagenow !== 'undefined' &&
			! ( pagenow === 'edit-product_line' || pagenow === 'product_line' )
		) {
			return;
		}

		let mediaIndex = $( '.line-media-item' ).length;

		/**
		 * Initialize media management functionality
		 *
		 * Sets up event handlers for adding, removing, and selecting media items.
		 */
		function initializeMediaManagement() {
			// Add new media item button
			$( document ).on( 'click', '#add-line-media', function ( e ) {
				e.preventDefault();
				addMediaItem();
			} );

			// Handle media selection
			$( document ).on( 'click', '.select-media-button', function ( e ) {
				e.preventDefault();
				selectMedia( $( this ) );
			} );

			// Handle media removal
			$( document ).on( 'click', '.remove-media-button', function ( e ) {
				e.preventDefault();
				removeMedia( $( this ) );
			} );

			// Remove entire media item
			$( document ).on( 'click', '.remove-media-item', function ( e ) {
				e.preventDefault();
				$( this ).closest( '.line-media-item' ).remove();
			} );

			// Auto-suggest for media tags
			initializeTagSuggestions();
		}

		/**
		 * Add a new media item to the container
		 *
		 * Creates a new media item with empty values and increments the index.
		 */
		function addMediaItem() {
			const mediaItemHtml = createMediaItemHtml( mediaIndex, '', '' );
			$( '#line-media-container' ).append( mediaItemHtml );
			mediaIndex++;
		}

		/**
		 * Create HTML for a media item
		 *
		 * Generates the complete HTML structure for a media item with the given parameters.
		 *
		 * @param {number} index        - Index for form field names
		 * @param {string} tag          - Media tag name
		 * @param {string} attachmentId - WordPress attachment ID
		 *
		 * @return {string} - Complete HTML for the media item
		 */
		function createMediaItemHtml( index, tag, attachmentId ) {
			const hasMedia = attachmentId ? true : false;
			const buttonText = hasMedia
				? ProductLinesParams.changeMediaText || 'Change Media'
				: ProductLinesParams.selectMediaText || 'Select Media';
			const removeButtonHtml = hasMedia
				? '<button type="button" class="button remove-media-button">Remove</button>'
				: '';

			return `
				<div class="line-media-item" data-index="${ index }" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; background: #f9f9f9;">
					<div style="display: flex; gap: 15px; align-items: flex-start;">
						<div style="flex: 1;">
							<p>
								<label>Media Tag:</label>
								<input type="text"
									   name="line_media[${ index }][tag]"
									   value="${ tag }"
									   class="regular-text media-tag-field"
									   placeholder="e.g., hero_image, logo, banner"
									   list="media_tag_suggestions">
							</p>
							<p>
								<input type="hidden"
									   name="line_media[${ index }][attachment_id]"
									   value="${ attachmentId }"
									   class="media-attachment-id">
								<button type="button" class="button select-media-button">
									${ buttonText }
								</button>
								${ removeButtonHtml }
								<button type="button" class="button remove-media-item" style="color: #a00;">Delete</button>
							</p>
						</div>
						<div class="media-preview" style="flex: 0 0 150px; text-align: center;">
							<!-- Media preview will be populated if attachment exists -->
						</div>
					</div>
				</div>
			`;
		}

		/**
		 * Handle media selection using WordPress media library
		 *
		 * Opens the WordPress media library and handles the selection callback.
		 *
		 * @param {jQuery} $button - The button that triggered the media selection
		 */
		function selectMedia( $button ) {
			const $container = $button.closest( '.line-media-item' );

			// Create media frame
			const mediaFrame = wp.media( {
				title:
					ProductLinesParams.selectMediaTitle || 'Select Line Media',
				button: {
					text:
						ProductLinesParams.selectMediaButton ||
						'Use this media',
				},
				multiple: false,
			} );

			// Handle media selection
			mediaFrame.on( 'select', function () {
				const attachment = mediaFrame
					.state()
					.get( 'selection' )
					.first()
					.toJSON();

				// Update the attachment ID
				$container.find( '.media-attachment-id' ).val( attachment.id );

				// Update the preview
				$container
					.find( '.media-preview' )
					.html(
						`<img src="${ attachment.url }" style="max-width: 150px; height: auto;">`
					);

				// Update button text
				$button.text( 'Change Media' );

				// Show remove button if not already present
				if ( ! $container.find( '.remove-media-button' ).length ) {
					$button.after(
						'<button type="button" class="button remove-media-button">Remove</button>'
					);
				}

				// Mark attachment as line media
				markAttachmentAsLineMedia( attachment.id );
			} );

			mediaFrame.open();
		}

		/**
		 * Remove media from a media item
		 *
		 * Clears the media attachment and updates the UI accordingly.
		 *
		 * @param {jQuery} $button - The remove button that was clicked
		 */
		function removeMedia( $button ) {
			const $container = $button.closest( '.line-media-item' );

			// Clear the attachment ID
			$container.find( '.media-attachment-id' ).val( '' );

			// Clear the preview
			$container.find( '.media-preview' ).empty();

			// Update button text
			$container.find( '.select-media-button' ).text( 'Select Media' );

			// Remove the remove button
			$button.remove();
		}

		/**
		 * Mark an attachment as line media in WordPress
		 *
		 * Makes an AJAX call to mark the attachment with appropriate meta data.
		 *
		 * @param {number} attachmentId - WordPress attachment ID
		 */
		function markAttachmentAsLineMedia( attachmentId ) {
			// This would require an AJAX endpoint, but for now we'll just
			// let the term save handle the media marking
			console.log( 'Marked attachment as line media:', attachmentId );
		}

		/**
		 * Initialize tag auto-suggestions
		 *
		 * Sets up autocomplete functionality for media tag fields.
		 */
		function initializeTagSuggestions() {
			// The datalist is already in the HTML, but we can enhance it
			// with dynamic loading if needed in the future

			$( document ).on( 'input', '.media-tag-field', function () {
				const $field = $( this );
				const value = $field.val();

				// You could add real-time tag suggestions here
				// For now, the datalist provides static suggestions
			} );
		}

		/**
		 * Initialize form validation
		 *
		 * Validates the form before submission to ensure data integrity.
		 */
		function initializeFormValidation() {
			$( 'form' ).on( 'submit', function ( e ) {
				let hasErrors = false;
				const errors = [];

				// Check for duplicate media tags within this line
				const tags = [];
				$( '.media-tag-field' ).each( function () {
					const tag = $( this ).val().trim();
					if ( tag ) {
						if ( tags.includes( tag ) ) {
							hasErrors = true;
							errors.push( 'Duplicate media tag: ' + tag );
							$( this ).css( 'border-color', '#dc3232' );
						} else {
							tags.push( tag );
							$( this ).css( 'border-color', '' );
						}
					}
				} );

				// Validate tag format (alphanumeric, underscore, dash)
				$( '.media-tag-field' ).each( function () {
					const tag = $( this ).val().trim();
					if ( tag && ! /^[a-zA-Z0-9_-]+$/.test( tag ) ) {
						hasErrors = true;
						errors.push(
							'Invalid media tag format: ' +
								tag +
								'. Use only letters, numbers, underscore, and dash.'
						);
						$( this ).css( 'border-color', '#dc3232' );
					}
				} );

				// Check that media items with tags also have attachments
				$( '.line-media-item' ).each( function () {
					const $item = $( this );
					const tag = $item.find( '.media-tag-field' ).val().trim();
					const attachmentId = $item
						.find( '.media-attachment-id' )
						.val();

					if ( tag && ! attachmentId ) {
						hasErrors = true;
						errors.push(
							'Media tag "' +
								tag +
								'" requires a media file to be selected.'
						);
						$item.css( 'border-color', '#dc3232' );
					} else {
						$item.css( 'border-color', '' );
					}
				} );

				if ( hasErrors ) {
					e.preventDefault();
					alert(
						'Please fix the following errors:\n\n' +
							errors.join( '\n' )
					);
					return false;
				}
			} );
		}

		/**
		 * Initialize sortable functionality for media items
		 *
		 * Allows users to reorder media items by dragging.
		 */
		function initializeSortable() {
			if ( $( '#line-media-container' ).length ) {
				$( '#line-media-container' ).sortable( {
					items: '.line-media-item',
					cursor: 'move',
					opacity: 0.65,
					handle: '.line-media-item', // Entire item is draggable
					placeholder: 'sortable-placeholder',
					start( event, ui ) {
						ui.placeholder.height( ui.item.height() );
						ui.placeholder.css( {
							'background-color': '#f0f0f0',
							border: '2px dashed #ddd',
						} );
					},
				} );
			}
		}

		/**
		 * Initialize line type suggestions
		 *
		 * Enhances the line type field with dynamic suggestions.
		 */
		function initializeLineTypeSuggestions() {
			// The datalist is already in the HTML with existing types
			// We could enhance this with AJAX loading of suggestions

			$( '#line_type' ).on( 'input', function () {
				// Future enhancement: dynamic type suggestions
			} );
		}

		// Initialize all functionality
		initializeMediaManagement();
		initializeFormValidation();
		initializeSortable();
		initializeLineTypeSuggestions();

		// If we're on the edit page and have no media items, add one empty item
		if (
			$( '#line-media-container' ).length &&
			$( '.line-media-item' ).length === 0
		) {
			addMediaItem();
		}
	} );
} )( jQuery );
