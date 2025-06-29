/**
 * Admin script for Product Lines management
 *
 * Handles the dynamic interface for managing product line media and attributes.
 * Fixed to work on both add new and edit existing product line pages.
 *
 * @param $
 * @package
 * @since   0.2.0
 * @version 0.3.2
 */

( function ( $ ) {
	$( document ).ready( function () {
		// Enhanced page detection for both add and edit pages
		const isProductLinePage =
			( typeof pagenow !== 'undefined' &&
				( pagenow === 'edit-product_line' ||
					pagenow === 'product_line' ||
					pagenow === 'edit-tags' ||
					pagenow === 'term' ) ) ||
			window.location.href.indexOf( 'taxonomy=product_line' ) > -1 ||
			$( 'body' ).hasClass( 'taxonomy-product_line' );

		if ( ! isProductLinePage ) {
			return;
		}

		// Ensure ProductLinesParams exists with fallbacks
		if ( typeof ProductLinesParams === 'undefined' ) {
			window.ProductLinesParams = {
				selectMediaTitle: 'Select Line Media',
				selectMediaButton: 'Use this media',
				selectMediaText: 'Select Media',
				changeMediaText: 'Change Media',
				removeText: 'Remove',
				deleteText: 'Delete',
				nonce: '',
				ajaxUrl: '',
			};
		}

		let mediaIndex = $( '.line-media-item' ).length;

		/**
		 * Initialize media management functionality
		 *
		 * Sets up event handlers for adding, removing, and selecting media items.
		 *
		 * @since 0.2.0
		 * @since 0.3.2 Fixed to work on both add and edit forms
		 */
		function initializeMediaManagement() {
			// Add new media item button - works for both add and edit forms
			$( document ).on( 'click', '#add-line-media', function ( e ) {
				e.preventDefault();
				addMediaItem();
			} );

			// Handle media selection - works for both add and edit forms
			$( document ).on( 'click', '.select-media-button', function ( e ) {
				e.preventDefault();
				selectMedia( $( this ) );
			} );

			// Handle media removal - works for both add and edit forms
			$( document ).on( 'click', '.remove-media-button', function ( e ) {
				e.preventDefault();
				removeMedia( $( this ) );
			} );

			// Remove entire media item - works for both add and edit forms
			$( document ).on( 'click', '.remove-media-item', function ( e ) {
				e.preventDefault();
				if (
					confirm(
						'Are you sure you want to delete this media item?'
					)
				) {
					$( this ).closest( '.line-media-item' ).remove();
					updateMediaIndices();
				}
			} );

			// Auto-suggest for media tags
			initializeTagSuggestions();
		}

		/**
		 * Add a new media item to the container
		 *
		 * Creates a new media item with empty values and increments the index.
		 * Works for both add new and edit forms.
		 *
		 * @since 0.2.0
		 * @since 0.3.2 Enhanced to work with edit form table structure
		 */
		function addMediaItem() {
			const $container = $( '#line-media-container' );

			if ( ! $container.length ) {
				console.error( 'Media container not found' );
				return;
			}

			const mediaItemHtml = createMediaItemHtml( mediaIndex, '', '' );
			$container.append( mediaItemHtml );
			mediaIndex++;
		}

		/**
		 * Create HTML for a media item
		 *
		 * Generates the complete HTML structure for a media item with the given parameters.
		 * Works for both add new and edit form layouts.
		 *
		 * @since 0.2.0
		 * @since 0.3.2 Enhanced with proper localization and better fallbacks
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
				? `<button type="button" class="button remove-media-button">${
						ProductLinesParams.removeText || 'Remove'
				  }</button>`
				: '';
			const deleteText = ProductLinesParams.deleteText || 'Delete';

			return `
				<div class="line-media-item" data-index="${ index }" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; background: #f9f9f9;">
					<div style="display: flex; gap: 15px; align-items: flex-start;">
						<div style="flex: 1;">
							<p>
								<label>${ ProductLinesParams.mediaTagLabel || 'Media Tag:' }</label>
								<input type="text"
									   name="line_media[${ index }][tag]"
									   value="${ $( '<div>' ).text( tag ).html() }"
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
								<button type="button" class="button remove-media-item" style="color: #a00;">${ deleteText }</button>
							</p>
						</div>
						<div class="media-preview" style="flex: 0 0 150px; text-align: center;">
							${
								hasMedia
									? '<!-- Media preview will be populated -->'
									: '<div style="width: 150px; height: 100px; background: #f0f0f0; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">No media selected</div>'
							}
						</div>
					</div>
				</div>
			`;
		}

		/**
		 * Handle media selection using WordPress media library
		 *
		 * Opens the WordPress media library and handles the selection callback.
		 * Fixed to work properly on edit forms.
		 *
		 * @since 0.2.0
		 * @since 0.3.2 Enhanced with proper localization and better error handling
		 *
		 * @param {jQuery} $button - The button that triggered the media selection
		 */
		function selectMedia( $button ) {
			const $container = $button.closest( '.line-media-item' );

			// Ensure wp.media is available
			if (
				typeof wp === 'undefined' ||
				typeof wp.media === 'undefined'
			) {
				alert(
					'WordPress media library is not available. Please refresh the page.'
				);
				return;
			}

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

				if ( ! attachment || ! attachment.id ) {
					alert( 'Error: No valid media selected.' );
					return;
				}

				// Update the attachment ID
				$container.find( '.media-attachment-id' ).val( attachment.id );

				// Update the preview with proper image handling
				const previewHtml =
					attachment.sizes && attachment.sizes.thumbnail
						? `<img src="${ attachment.sizes.thumbnail.url }" style="max-width: 150px; height: auto;" alt="Media preview">`
						: `<img src="${ attachment.url }" style="max-width: 150px; height: auto;" alt="Media preview">`;

				$container.find( '.media-preview' ).html( previewHtml );

				// Update button text using localized strings
				$button.text(
					ProductLinesParams.changeMediaText || 'Change Media'
				);

				// Show remove button if not already present
				if ( ! $container.find( '.remove-media-button' ).length ) {
					const removeText =
						ProductLinesParams.removeText || 'Remove';
					$button.after(
						`<button type="button" class="button remove-media-button">${ removeText }</button>`
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
		 * @since 0.2.0
		 * @since 0.3.2 Enhanced with better confirmation and localization
		 *
		 * @param {jQuery} $button - The remove button that was clicked
		 */
		function removeMedia( $button ) {
			if ( ! confirm( 'Are you sure you want to remove this media?' ) ) {
				return;
			}

			const $container = $button.closest( '.line-media-item' );

			// Clear the attachment ID
			$container.find( '.media-attachment-id' ).val( '' );

			// Clear the preview and show placeholder
			$container
				.find( '.media-preview' )
				.html(
					'<div style="width: 150px; height: 100px; background: #f0f0f0; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">No media selected</div>'
				);

			// Update button text using localized strings
			$container
				.find( '.select-media-button' )
				.text( ProductLinesParams.selectMediaText || 'Select Media' );

			// Remove the remove button
			$button.remove();
		}

		/**
		 * Mark an attachment as line media in WordPress
		 *
		 * Makes an AJAX call to mark the attachment with appropriate meta data.
		 *
		 * @since 0.2.0
		 * @since 0.3.2 Enhanced with proper AJAX handling
		 *
		 * @param {number} attachmentId - WordPress attachment ID
		 */
		function markAttachmentAsLineMedia( attachmentId ) {
			// Enhanced AJAX implementation for marking attachments
			if ( ProductLinesParams.ajaxUrl && ProductLinesParams.nonce ) {
				$.ajax( {
					url: ProductLinesParams.ajaxUrl,
					type: 'POST',
					data: {
						action: 'mark_line_media',
						attachment_id: attachmentId,
						nonce: ProductLinesParams.nonce,
					},
					success( response ) {
						if ( response.success ) {
							console.log(
								'Marked attachment as line media:',
								attachmentId
							);
						} else {
							console.warn(
								'Failed to mark attachment as line media:',
								response.data
							);
						}
					},
					error( xhr, status, error ) {
						console.error(
							'AJAX error marking attachment as line media:',
							error
						);
					},
				} );
			}
		}

		/**
		 * Initialize tag auto-suggestions
		 *
		 * Sets up autocomplete functionality for media tag fields.
		 *
		 * @since 0.2.0
		 */
		function initializeTagSuggestions() {
			// The datalist is already in the HTML, but we can enhance it
			$( document ).on( 'input', '.media-tag-field', function () {
				const $field = $( this );
				// Clear any previous error styling
				$field.css( 'border-color', '' );
			} );
		}

		/**
		 * Initialize form validation
		 *
		 * Validates the form before submission to ensure data integrity.
		 *
		 * @since 0.2.0
		 * @since 0.3.2 Enhanced validation with better error messages
		 */
		function initializeFormValidation() {
			// Find the form - it could be #addtag or #edittag
			const $form = $( '#addtag, #edittag, form[name="edittag"]' );

			$form.on( 'submit', function ( e ) {
				let hasErrors = false;
				const errors = [];

				// Clear any previous error styling
				$( '.media-tag-field, .line-media-item' ).css(
					'border-color',
					''
				);

				// Check for duplicate media tags within this line
				const tags = [];
				$( '.media-tag-field' ).each( function () {
					const tag = $( this ).val().trim();
					if ( tag ) {
						if ( tags.includes( tag ) ) {
							hasErrors = true;
							errors.push( 'Duplicate media tag: "' + tag + '"' );
							$( this ).css( 'border-color', '#dc3232' );
						} else {
							tags.push( tag );
						}
					}
				} );

				// Validate tag format (alphanumeric, underscore, dash)
				$( '.media-tag-field' ).each( function () {
					const tag = $( this ).val().trim();
					if ( tag && ! /^[a-zA-Z0-9_-]+$/.test( tag ) ) {
						hasErrors = true;
						errors.push(
							'Invalid media tag format: "' +
								tag +
								'". Use only letters, numbers, underscore, and dash.'
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

				// Show saving feedback
				$( '#add-line-media' )
					.prop( 'disabled', true )
					.text( 'Saving...' );
			} );
		}

		/**
		 * Initialize sortable functionality for media items
		 *
		 * Allows users to reorder media items by dragging.
		 *
		 * @since 0.2.0
		 */
		function initializeSortable() {
			if (
				$( '#line-media-container' ).length &&
				typeof $.fn.sortable !== 'undefined'
			) {
				$( '#line-media-container' ).sortable( {
					items: '.line-media-item',
					cursor: 'move',
					opacity: 0.65,
					handle: '.line-media-item',
					placeholder: 'sortable-placeholder',
					start( event, ui ) {
						ui.placeholder.height( ui.item.height() );
						ui.placeholder.css( {
							'background-color': '#f0f0f0',
							border: '2px dashed #ddd',
						} );
					},
					stop( event, ui ) {
						updateMediaIndices();
					},
				} );
			}
		}

		/**
		 * Update media item indices after reordering
		 *
		 * Ensures form field names have correct indices after drag-and-drop reordering.
		 *
		 * @since 0.3.2
		 */
		function updateMediaIndices() {
			$( '.line-media-item' ).each( function ( newIndex ) {
				const $item = $( this );
				$item.attr( 'data-index', newIndex );

				// Update form field names
				$item
					.find( 'input[name*="[tag]"]' )
					.attr( 'name', `line_media[${ newIndex }][tag]` );
				$item
					.find( 'input[name*="[attachment_id]"]' )
					.attr( 'name', `line_media[${ newIndex }][attachment_id]` );
			} );

			// Update the global mediaIndex to continue from the highest index + 1
			mediaIndex = $( '.line-media-item' ).length;
		}

		/**
		 * Initialize existing media previews
		 *
		 * Sets up proper preview display for media items that already exist.
		 *
		 * @since 0.3.2
		 */
		function initializeExistingMediaPreviews() {
			$( '.line-media-item' ).each( function () {
				const $item = $( this );
				const attachmentId = $item.find( '.media-attachment-id' ).val();
				const $preview = $item.find( '.media-preview' );

				// If we have an attachment ID but no preview image,
				// the PHP should have already rendered it, but let's ensure button state is correct
				if ( attachmentId ) {
					const $selectButton = $item.find( '.select-media-button' );
					$selectButton.text(
						ProductLinesParams.changeMediaText || 'Change Media'
					);

					// Ensure remove button exists
					if ( ! $item.find( '.remove-media-button' ).length ) {
						const removeText =
							ProductLinesParams.removeText || 'Remove';
						$selectButton.after(
							`<button type="button" class="button remove-media-button">${ removeText }</button>`
						);
					}
				}
			} );
		}

		// Initialize all functionality
		initializeMediaManagement();
		initializeFormValidation();
		initializeSortable();
		initializeExistingMediaPreviews();

		// If we're on the add new page and have no media items, add one empty item
		if (
			$( '#line-media-container' ).length &&
			$( '.line-media-item' ).length === 0 &&
			( typeof pagenow === 'undefined' || pagenow === 'edit-tags' )
		) {
			addMediaItem();
		}

		// Auto-update media index based on existing items
		if ( $( '.line-media-item' ).length > 0 ) {
			const existingIndices = [];
			$( '.line-media-item' ).each( function () {
				const index = parseInt( $( this ).attr( 'data-index' ), 10 );
				if ( ! isNaN( index ) ) {
					existingIndices.push( index );
				}
			} );

			if ( existingIndices.length > 0 ) {
				mediaIndex = Math.max( ...existingIndices ) + 1;
			}
		}
	} );
} )( jQuery );
