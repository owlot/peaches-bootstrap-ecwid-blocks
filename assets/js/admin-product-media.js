/**
 * Enhanced Product Media Management JavaScript
 *
 * Handles the enhanced media interface with global Ecwid image loading.
 *
 * @param $
 * @package
 * @since   0.2.1
 */

( function ( $ ) {
	'use strict';

	/**
	 * Media Management Controller
	 *
	 * Manages all aspects of product media selection and preview.
	 */
	const MediaManager = {
		/**
		 * Cached Ecwid images data
		 */
		ecwidImages: null,
		ecwidProductName: '',
		ecwidLoadingPromise: null,

		/**
		 * Initialize media management functionality
		 *
		 * Sets up event handlers for all media types and interactions.
		 */
		init() {
			this.bindEvents();
			this.initializeExistingItems();
			this.addGlobalEcwidLoader();
		},

		/**
		 * Add global Ecwid loader to the page
		 *
		 * Adds a single "Load Product Images" button that loads images for all tags.
		 */
		addGlobalEcwidLoader() {
			// Find the media container
			const $mediaContainer = $( '#product-media-container' );

			if (
				$mediaContainer.length &&
				$( '.media-tag-item[data-tag-key]' ).length > 1
			) {
				// Add global loader before the first card
				const globalLoaderHtml = `
					<div class="card mb-3 border-primary" id="global-ecwid-loader">
						<div class="card-header bg-primary text-white">
							<h6 class="card-title mb-0">
								<i class="dashicons dashicons-store"></i>
								${ this.t( 'Ecwid Product Images' ) }
							</h6>
						</div>
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-center">
								<div>
									<p class="mb-1">${ this.t(
										'Load images from the linked Ecwid product and make them available for all media tags.'
									) }</p>
									<small class="text-muted">${ this.t(
										'This will populate the Ecwid media options for all tags at once.'
									) }</small>
								</div>
								<button type="button" class="btn btn-primary" id="global-load-ecwid-media">
									<i class="dashicons dashicons-update"></i>
									${ this.t( 'Load All Product Images' ) }
								</button>
							</div>
							<div id="global-ecwid-status" class="mt-2" style="display: none;"></div>
						</div>
					</div>
				`;

				$mediaContainer.prepend( globalLoaderHtml );

				// Hide individual load buttons initially
				$( '.load-ecwid-media-button' ).hide();
			}
		},

		/**
		 * Get translation string
		 *
		 * Simple translation helper function.
		 *
		 * @param {string} text - Text to translate
		 *
		 * @return {string} - Translated text
		 */
		t( text ) {
			// In a real implementation, this would use WordPress i18n
			return text;
		},

		/**
		 * Bind event handlers
		 *
		 * Sets up all event listeners for media management.
		 */
		bindEvents() {
			// Media type selection
			$( document ).on(
				'change',
				'.media-type-radio',
				this.handleMediaTypeChange
			);

			// Upload file handling
			$( document ).on(
				'click',
				'.select-media-button',
				this.handleMediaSelection
			);
			$( document ).on(
				'click',
				'.remove-media-button',
				this.handleMediaRemoval
			);

			// URL handling
			$( document ).on(
				'input',
				'.media-url-input',
				this.handleUrlInput
			);
			$( document ).on(
				'click',
				'.preview-url-button',
				this.handleUrlPreview
			);
			$( document ).on(
				'click',
				'.clear-url-button',
				this.handleUrlClear
			);

			// Ecwid media handling
			$( document ).on(
				'click',
				'#global-load-ecwid-media',
				this.handleGlobalEcwidLoad
			);
			$( document ).on(
				'click',
				'.load-ecwid-media-button',
				this.handleEcwidMediaLoad
			);
			$( document ).on(
				'change',
				'.ecwid-position-select',
				this.handleEcwidPositionChange
			);
			$( document ).on(
				'click',
				'.clear-ecwid-button',
				this.handleEcwidClear
			);
		},

		/**
		 * Initialize existing media items
		 *
		 * Sets up the UI state for items that already have media assigned.
		 */
		initializeExistingItems() {
			$( '.media-tag-item' ).each( function () {
				const $container = $( this );
				const mediaType = $container.find( '.media-type-value' ).val();

				if ( mediaType && mediaType !== 'none' ) {
					MediaManager.updateControlsVisibility(
						$container,
						mediaType
					);
				}
			} );
		},

		/**
		 * Handle global Ecwid media loading
		 *
		 * Loads Ecwid images once and makes them available to all tags.
		 *
		 * @param {Event} e - Click event
		 */
		handleGlobalEcwidLoad( e ) {
			e.preventDefault();

			const $button = $( e.target ).closest( '#global-load-ecwid-media' );
			const $statusDiv = $( '#global-ecwid-status' );

			// Get product ID from page
			const productId = $( '#ecwid_product_id' ).val();

			if ( ! productId ) {
				$statusDiv
					.html(
						'<div class="alert alert-warning alert-sm">Please link an Ecwid product first</div>'
					)
					.show();
				return;
			}

			// Set loading state
			MediaManager.setButtonLoading( $button, true, 'Loading Images...' );
			$statusDiv
				.html(
					'<div class="alert alert-info alert-sm">Loading product images...</div>'
				)
				.show();

			// Store the promise so we can reuse it if multiple calls happen
			if ( MediaManager.ecwidLoadingPromise ) {
				return MediaManager.ecwidLoadingPromise;
			}

			MediaManager.ecwidLoadingPromise = $.post(
				ProductMediaParams.ajaxUrl,
				{
					action: 'get_ecwid_product_media',
					nonce: ProductMediaParams.nonce,
					product_id: productId,
				}
			);

			MediaManager.ecwidLoadingPromise
				.done( function ( response ) {
					if ( response.success ) {
						// Cache the images data
						MediaManager.ecwidImages = response.data.images;
						MediaManager.ecwidProductName =
							response.data.product_name;

						// Update all Ecwid selects
						MediaManager.updateAllEcwidSelects();

						// Show success status
						$statusDiv.html(
							`<div class="alert alert-success alert-sm">
							<strong>Success!</strong> Loaded ${ response.data.images.length } images from "${ response.data.product_name }".
							<br><small>All Ecwid media options have been populated. You can now select images for individual tags.</small>
						</div>`
						);

						// Show individual load buttons as "Refresh" buttons
						$( '.load-ecwid-media-button' )
							.show()
							.html(
								'<i class="dashicons dashicons-update"></i>' +
									MediaManager.t( 'Refresh' )
							);
					} else {
						$statusDiv.html(
							`<div class="alert alert-danger alert-sm">${
								response.data || 'Failed to load Ecwid media'
							}</div>`
						);
					}
				} )
				.fail( function ( xhr, status, error ) {
					$statusDiv.html(
						`<div class="alert alert-danger alert-sm">Error loading images: ${ status } (${ xhr.status })</div>`
					);
				} )
				.always( function () {
					MediaManager.setButtonLoading( $button, false );
					// Clear the promise so it can be called again
					MediaManager.ecwidLoadingPromise = null;
				} );
		},

		/**
		 * Update all Ecwid selects with cached data
		 *
		 * Populates all Ecwid position selects with the loaded images.
		 */
		updateAllEcwidSelects() {
			if ( ! MediaManager.ecwidImages ) {
				return;
			}

			$( '.ecwid-position-select' ).each( function () {
				const $select = $( this );
				const $container = $select.closest( '.media-tag-item' );
				const currentValue = $select.val();

				MediaManager.updateEcwidMediaOptions( $container, {
					images: MediaManager.ecwidImages,
					product_name: MediaManager.ecwidProductName,
				} );

				// Restore selection if it still exists
				if ( currentValue ) {
					$select.val( currentValue );
					if ( $select.val() === currentValue ) {
						// Value was restored successfully, trigger change to update preview
						MediaManager.handleEcwidPositionChange( {
							target: $select[ 0 ],
						} );
					}
				}
			} );
		},

		/**
		 * Handle media type change
		 *
		 * Updates the interface when user switches between upload/URL/Ecwid options.
		 *
		 * @param {Event} e - Change event
		 */
		handleMediaTypeChange( e ) {
			const $radio = $( e.target );
			const $container = $radio.closest( '.media-tag-item' );
			const mediaType = $radio.val();

			// Update hidden field
			$container.find( '.media-type-value' ).val( mediaType );

			// Clear other media data
			MediaManager.clearOtherMediaData( $container, mediaType );

			// Update controls visibility
			MediaManager.updateControlsVisibility( $container, mediaType );

			// If switching to Ecwid and we have cached images, populate immediately
			if ( mediaType === 'ecwid' && MediaManager.ecwidImages ) {
				MediaManager.updateEcwidMediaOptions( $container, {
					images: MediaManager.ecwidImages,
					product_name: MediaManager.ecwidProductName,
				} );
			}

			// Clear preview if switching away from current media
			MediaManager.clearPreview( $container );

			MediaManager.showFeedback(
				$container,
				'Media type changed to ' + mediaType,
				'info'
			);
		},

		/**
		 * Clear other media data when switching types
		 *
		 * Ensures only the selected media type has data.
		 *
		 * @param {jQuery} $container - Media container element
		 * @param {string} activeType - Currently active media type
		 */
		clearOtherMediaData( $container, activeType ) {
			if ( activeType !== 'upload' ) {
				$container.find( '.media-attachment-id' ).val( '' );
			}
			if ( activeType !== 'url' ) {
				$container.find( '.media-url-value' ).val( '' );
				$container.find( '.media-url-input' ).val( '' );
			}
			if ( activeType !== 'ecwid' ) {
				$container.find( '.media-ecwid-position' ).val( '' );
				$container.find( '.ecwid-position-select' ).val( '' );
			}
		},

		/**
		 * Update controls visibility based on media type
		 *
		 * Shows/hides appropriate controls for the selected media type.
		 *
		 * @param {jQuery} $container - Media container element
		 * @param {string} mediaType  - Selected media type
		 */
		updateControlsVisibility( $container, mediaType ) {
			// Hide all controls first
			$container.find( '.media-upload-controls' ).hide();
			$container.find( '.media-url-controls' ).hide();
			$container.find( '.media-ecwid-controls' ).hide();

			// Show relevant controls
			switch ( mediaType ) {
				case 'upload':
					$container.find( '.media-upload-controls' ).show();
					break;
				case 'url':
					$container.find( '.media-url-controls' ).show();
					break;
				case 'ecwid':
					$container.find( '.media-ecwid-controls' ).show();
					break;
			}
		},

		/**
		 * Handle WordPress media selection
		 *
		 * Opens the WordPress media library for file selection.
		 *
		 * @param {Event} e - Click event
		 */
		handleMediaSelection( e ) {
			e.preventDefault();

			const $button = $( e.target ).closest( '.select-media-button' );
			const $container = $button.closest( '.media-tag-item' );

			// Set loading state
			MediaManager.setButtonLoading( $button, true, 'Loading...' );

			// Create media frame
			const mediaFrame = wp.media( {
				title:
					ProductMediaParams.selectMediaTitle ||
					'Select Product Media',
				button: {
					text:
						ProductMediaParams.selectMediaButton ||
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

				MediaManager.updateUploadMedia( $container, attachment );

				// Explicitly close the frame after selection
				mediaFrame.close();
			} );

			// Handle frame close
			mediaFrame.on( 'close', function () {
				MediaManager.setButtonLoading( $button, false );
			} );

			mediaFrame.open();
		},

		/**
		 * Update upload media data and preview
		 *
		 * Updates the interface when a WordPress media file is selected.
		 *
		 * @param {jQuery} $container - Media container element
		 * @param {Object} attachment - WordPress media attachment object
		 */
		updateUploadMedia( $container, attachment ) {
			// Update hidden field
			$container.find( '.media-attachment-id' ).val( attachment.id );

			// Update preview
			const previewHtml = `
				<img src="${
					attachment.url
				}" class="img-fluid rounded" style="max-height: 100px;" alt="${
					attachment.alt || attachment.title
				}">
				<div class="position-absolute top-0 start-0 p-1">
					<small class="badge bg-success">WP Media</small>
				</div>
			`;
			$container.find( '.media-preview' ).html( previewHtml );

			// Update button
			const $selectBtn = $container.find( '.select-media-button' );
			$selectBtn
				.removeClass( 'btn-secondary' )
				.addClass( 'btn-outline-secondary' )
				.html(
					'<span class="dashicons dashicons-update"></span>Change'
				);

			// Show remove button
			let $removeBtn = $container.find( '.remove-media-button' );
			if ( $removeBtn.length === 0 ) {
				$removeBtn = $(
					'<button type="button" class="btn btn-outline-danger btn-sm remove-media-button"><span class="dashicons dashicons-trash"></span>Remove</button>'
				);
				$selectBtn.after( $removeBtn );
			}
			$removeBtn.show();

			MediaManager.showFeedback(
				$container,
				'Media selected successfully!',
				'success'
			);
		},

		/**
		 * Handle media removal
		 *
		 * Removes the selected WordPress media file.
		 *
		 * @param {Event} e - Click event
		 */
		handleMediaRemoval( e ) {
			e.preventDefault();

			const $button = $( e.target ).closest( '.remove-media-button' );
			const $container = $button.closest( '.media-tag-item' );

			if ( ! confirm( 'Are you sure you want to remove this media?' ) ) {
				return;
			}

			// Clear data
			$container.find( '.media-attachment-id' ).val( '' );

			// Update button
			const $selectBtn = $container.find( '.select-media-button' );
			$selectBtn
				.removeClass( 'btn-outline-secondary' )
				.addClass( 'btn-secondary' )
				.html(
					'<span class="dashicons dashicons-plus-alt2"></span>Select'
				);

			// Hide remove button
			$button.hide();

			// Clear preview
			MediaManager.clearPreview( $container );

			MediaManager.showFeedback(
				$container,
				'Media removed successfully!',
				'info'
			);
		},

		/**
		 * Handle URL input
		 *
		 * Updates the hidden field and validates URL format.
		 *
		 * @param {Event} e - Input event
		 */
		handleUrlInput( e ) {
			const $input = $( e.target );
			const $container = $input.closest( '.media-tag-item' );
			const url = $input.val().trim();

			// Update hidden field
			$container.find( '.media-url-value' ).val( url );

			// Validate URL
			if ( url && ! MediaManager.isValidUrl( url ) ) {
				$input.addClass( 'is-invalid' );
				MediaManager.showFeedback(
					$container,
					'Please enter a valid URL',
					'warning'
				);
			} else {
				$input.removeClass( 'is-invalid' );

				if ( url ) {
					// Auto-preview if URL looks valid
					setTimeout( () => {
						MediaManager.updateUrlPreview( $container, url );
					}, 500 );

					// Show/update clear button
					let $clearBtn = $container.find( '.clear-url-button' );
					if ( $clearBtn.length === 0 ) {
						$clearBtn = $(
							'<button type="button" class="btn btn-outline-danger btn-sm w-100 mt-1 clear-url-button"><span class="dashicons dashicons-trash"></span>Clear URL</button>'
						);
						$input
							.closest( '.media-url-controls' )
							.append( $clearBtn );
					}
					$clearBtn.show();
				}
			}
		},

		/**
		 * Handle URL preview
		 *
		 * Manually triggers URL preview when preview button is clicked.
		 *
		 * @param {Event} e - Click event
		 */
		handleUrlPreview( e ) {
			e.preventDefault();

			const $button = $( e.target ).closest( '.preview-url-button' );
			const $container = $button.closest( '.media-tag-item' );
			const url = $container.find( '.media-url-input' ).val().trim();

			if ( ! url ) {
				MediaManager.showFeedback(
					$container,
					'Please enter a URL first',
					'warning'
				);
				return;
			}

			if ( ! MediaManager.isValidUrl( url ) ) {
				MediaManager.showFeedback(
					$container,
					'Please enter a valid URL',
					'warning'
				);
				return;
			}

			MediaManager.setButtonLoading( $button, true, 'Loading...' );

			MediaManager.updateUrlPreview( $container, url );

			setTimeout( () => {
				MediaManager.setButtonLoading( $button, false );
			}, 1000 );
		},

		/**
		 * Update URL preview
		 *
		 * Updates the preview area with the URL content.
		 *
		 * @param {jQuery} $container - Media container element
		 * @param {string} url        - URL to preview
		 */
		updateUrlPreview( $container, url ) {
			let previewHtml = '';

			if ( MediaManager.isVideoUrl( url ) ) {
				previewHtml = `
					<div class="video-preview-placeholder bg-dark text-white d-flex align-items-center justify-content-center rounded" style="width: 100px; height: 100px;">
						<i class="fas fa-play fa-2x"></i>
					</div>
					<div class="position-absolute top-0 start-0 p-1">
						<small class="badge bg-info">External</small>
					</div>
				`;
			} else {
				previewHtml = `
					<img src="${ url }" class="img-fluid rounded" style="max-height: 100px;"
						 alt="External media"
						 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
					<div class="error-placeholder bg-warning text-dark d-flex align-items-center justify-content-center rounded" style="width: 100px; height: 100px; display: none;">
						<i class="fas fa-exclamation-triangle"></i>
					</div>
					<div class="position-absolute top-0 start-0 p-1">
						<small class="badge bg-info">External</small>
					</div>
				`;
			}

			$container.find( '.media-preview' ).html( previewHtml );
			MediaManager.showFeedback(
				$container,
				'URL preview updated!',
				'success'
			);
		},

		/**
		 * Handle URL clear
		 *
		 * Clears the URL input and preview.
		 *
		 * @param {Event} e - Click event
		 */
		handleUrlClear( e ) {
			e.preventDefault();

			const $button = $( e.target ).closest( '.clear-url-button' );
			const $container = $button.closest( '.media-tag-item' );

			// Clear input and hidden field
			$container.find( '.media-url-input' ).val( '' );
			$container.find( '.media-url-value' ).val( '' );

			// Hide clear button
			$button.hide();

			// Clear preview
			MediaManager.clearPreview( $container );

			MediaManager.showFeedback( $container, 'URL cleared!', 'info' );
		},

		/**
		 * Handle individual Ecwid media loading (fallback/refresh)
		 *
		 * Loads available images from the linked Ecwid product for a single tag.
		 *
		 * @param {Event} e - Click event
		 */
		handleEcwidMediaLoad( e ) {
			e.preventDefault();

			// If we already have cached images, just update this container
			if ( MediaManager.ecwidImages ) {
				const $container = $( e.target ).closest( '.media-tag-item' );
				MediaManager.updateEcwidMediaOptions( $container, {
					images: MediaManager.ecwidImages,
					product_name: MediaManager.ecwidProductName,
				} );
				MediaManager.showFeedback(
					$container,
					'Images refreshed!',
					'success'
				);
				return;
			}

			// Otherwise, trigger the global load
			$( '#global-load-ecwid-media' ).trigger( 'click' );
		},

		/**
		 * Update Ecwid media options
		 *
		 * Populates the position selector with available Ecwid images.
		 *
		 * @param {jQuery} $container - Media container element
		 * @param {Object} data       - Response data with images
		 */
		updateEcwidMediaOptions( $container, data ) {
			const $select = $container.find( '.ecwid-position-select' );
			const currentValue = $select.val();

			// Clear existing options except first
			$select.find( 'option:not(:first)' ).remove();

			// Add new options
			data.images.forEach( function ( image ) {
				const $option = $( '<option></option>' )
					.val( image.position )
					.text( image.label )
					.data( 'url', image.url );

				$select.append( $option );
			} );

			// Restore selection if it still exists
			if ( currentValue ) {
				$select.val( currentValue );
				if ( $select.val() === currentValue ) {
					MediaManager.handleEcwidPositionChange( {
						target: $select[ 0 ],
					} );
				}
			}
		},

		/**
		 * Handle Ecwid position change
		 *
		 * Updates preview when a different Ecwid image position is selected.
		 *
		 * @param {Event} e - Change event
		 */
		handleEcwidPositionChange( e ) {
			const $select = $( e.target );
			const $container = $select.closest( '.media-tag-item' );
			const position = $select.val();

			// Update hidden field
			$container.find( '.media-ecwid-position' ).val( position );

			if ( position !== '' ) {
				// Get image URL from option data
				const imageUrl = $select
					.find( 'option:selected' )
					.data( 'url' );

				if ( imageUrl ) {
					// Update preview
					const previewHtml = `
						<img src="${ imageUrl }" class="img-fluid rounded" style="max-height: 100px;" alt="Ecwid media">
						<div class="position-absolute top-0 start-0 p-1">
							<small class="badge bg-warning text-dark">Ecwid</small>
						</div>
					`;
					$container.find( '.media-preview' ).html( previewHtml );

					// Show clear button
					let $clearBtn = $container.find( '.clear-ecwid-button' );
					if ( $clearBtn.length === 0 ) {
						$clearBtn = $(
							'<button type="button" class="btn btn-outline-danger btn-sm w-100 mt-1 clear-ecwid-button"><span class="dashicons dashicons-trash"></span>Clear Selection</button>'
						);
						$container
							.find( '.media-ecwid-controls' )
							.append( $clearBtn );
					}
					$clearBtn.show();

					MediaManager.showFeedback(
						$container,
						'Ecwid image selected!',
						'success'
					);
				}
			} else {
				MediaManager.clearPreview( $container );
			}
		},

		/**
		 * Handle Ecwid clear
		 *
		 * Clears the Ecwid media selection.
		 *
		 * @param {Event} e - Click event
		 */
		handleEcwidClear( e ) {
			e.preventDefault();

			const $button = $( e.target ).closest( '.clear-ecwid-button' );
			const $container = $button.closest( '.media-tag-item' );

			// Clear selection and hidden field
			$container.find( '.ecwid-position-select' ).val( '' );
			$container.find( '.media-ecwid-position' ).val( '' );

			// Hide clear button
			$button.hide();

			// Clear preview
			MediaManager.clearPreview( $container );

			MediaManager.showFeedback(
				$container,
				'Ecwid selection cleared!',
				'info'
			);
		},

		/**
		 * Clear media preview
		 *
		 * Resets the preview area to the default state.
		 *
		 * @param {jQuery} $container - Media container element
		 */
		clearPreview( $container ) {
			$container
				.find( '.media-preview' )
				.html( '<i class="fas fa-image fa-2x text-muted"></i>' );
		},

		/**
		 * Check if URL is valid
		 *
		 * Validates URL format.
		 *
		 * @param {string} url - URL to validate
		 *
		 * @return {boolean} - True if valid URL
		 */
		isValidUrl( url ) {
			try {
				new URL( url );
				return true;
			} catch {
				return false;
			}
		},

		/**
		 * Check if URL is a video URL
		 *
		 * Determines if the URL points to a video resource.
		 *
		 * @param {string} url - URL to check
		 *
		 * @return {boolean} - True if video URL
		 */
		isVideoUrl( url ) {
			const videoPatterns = [
				/youtube\.com\/watch\?v=/,
				/youtu\.be\//,
				/vimeo\.com\//,
				/wistia\.com\//,
				/\.mp4$/i,
				/\.webm$/i,
				/\.ogg$/i,
				/\.mov$/i,
			];

			return videoPatterns.some( ( pattern ) => pattern.test( url ) );
		},

		/**
		 * Set button loading state
		 *
		 * Shows/hides loading spinner on buttons.
		 *
		 * @param {jQuery}  $button     - Button element
		 * @param {boolean} isLoading   - Whether to show loading state
		 * @param {string}  loadingText - Optional loading text
		 */
		setButtonLoading( $button, isLoading, loadingText = 'Loading...' ) {
			if ( isLoading ) {
				$button.data( 'original-html', $button.html() );
				$button.html(
					`<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${ loadingText }`
				);
				$button.prop( 'disabled', true );
			} else {
				const originalHtml = $button.data( 'original-html' );
				if ( originalHtml ) {
					$button.html( originalHtml );
				}
				$button.prop( 'disabled', false );
			}
		},

		/**
		 * Show feedback message
		 *
		 * Displays a temporary feedback message.
		 *
		 * @param {jQuery} $container - Container element
		 * @param {string} message    - Feedback message
		 * @param {string} type       - Message type (success, info, warning, danger)
		 */
		showFeedback( $container, message, type ) {
			// Remove existing feedback
			$container.find( '.media-feedback' ).remove();

			// Create feedback element
			const $feedback = $(
				`<div class="media-feedback alert alert-${ type } alert-sm mt-2 mb-0">${ message }</div>`
			);

			// Add feedback
			$container.append( $feedback );

			// Auto-remove after 3 seconds
			setTimeout( function () {
				$feedback.fadeOut( 300, function () {
					$( this ).remove();
				} );
			}, 3000 );
		},
	};

	// Initialize when document is ready
	$( document ).ready( function () {
		// Check if this is the product_settings post type
		if (
			typeof pagenow !== 'undefined' &&
			pagenow === 'product_settings'
		) {
			MediaManager.init();
		}
	} );
} )( jQuery );
