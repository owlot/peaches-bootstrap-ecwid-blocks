/**
 * Admin Product Media JavaScript
 *
 * Handles media management functionality for product settings,
 * including auto-loading of Ecwid product images on page load.
 *
 * @param $
 * @package
 * @since   0.2.1
 */

( function ( $ ) {
	'use strict';

	const mediaFrames = {};
	let ecwidImagesLoaded = false;
	let ecwidImagesData = []; // Store loaded images data for preview

	/**
	 * Initialize media management functionality
	 *
	 * @since 0.2.1
	 *
	 * @return {void}
	 */
	function initMediaManagement() {
		initMediaTypeToggles();
		initMediaUploadHandlers();
		initUrlHandlers();
		initEcwidHandlers();

		// Auto-load Ecwid images if enabled and post ID is available
		if ( ProductMediaParams.autoLoadImages && ProductMediaParams.postId ) {
			autoLoadEcwidImages();
		}
	}

	/**
	 * Auto-load Ecwid product images on page load
	 *
	 * @since 0.2.1
	 *
	 * @return {void}
	 */
	function autoLoadEcwidImages() {
		// Only load once per page load
		if ( ecwidImagesLoaded ) {
			return;
		}

		// Show loading indicator
		$( '.ecwid-images-loading' ).show();

		loadEcwidImages( function ( success ) {
			$( '.ecwid-images-loading' ).hide();

			if ( success ) {
				ecwidImagesLoaded = true;
			}
		} );
	}

	/**
	 * Initialize media type toggle functionality
	 *
	 * @since 0.2.1
	 *
	 * @return {void}
	 */
	function initMediaTypeToggles() {
		$( document ).on( 'change', '.media-type-radio', function () {
			const $container = $( this ).closest( '.media-tag-item' );
			const selectedType = $( this ).val();

			// Update hidden input
			$container.find( '.media-type-value' ).val( selectedType );

			// Hide all control sections
			$container
				.find(
					'.media-upload-controls, .media-url-controls, .media-ecwid-controls'
				)
				.hide();

			// Show the selected type's controls
			if ( selectedType ) {
				$container
					.find( '.media-' + selectedType + '-controls' )
					.show();

				// Auto-load Ecwid images when switching to Ecwid type
				if (
					selectedType === 'ecwid' &&
					! ecwidImagesLoaded &&
					ProductMediaParams.postId
				) {
					autoLoadEcwidImages();
				}
			}

			// Clear existing data when switching types
			clearMediaData( $container, selectedType );
		} );
	}

	/**
	 * Initialize media upload handlers
	 *
	 * @since 0.2.1
	 *
	 * @return {void}
	 */
	function initMediaUploadHandlers() {
		$( document ).on( 'click', '.select-media-button', function ( e ) {
			e.preventDefault();

			const $button = $( this );
			const $container = $button.closest( '.media-tag-item' );
			const tagKey = $container.data( 'tag-key' );

			if ( ! mediaFrames[ tagKey ] ) {
				mediaFrames[ tagKey ] = wp.media( {
					title: ProductMediaParams.selectMediaTitle,
					button: {
						text: ProductMediaParams.selectMediaButton,
					},
					multiple: false,
				} );

				mediaFrames[ tagKey ].on( 'select', function () {
					const attachment = mediaFrames[ tagKey ]
						.state()
						.get( 'selection' )
						.first()
						.toJSON();
					updateMediaUpload( $container, attachment );
				} );
			}

			mediaFrames[ tagKey ].open();
		} );

		$( document ).on( 'click', '.remove-media-button', function ( e ) {
			e.preventDefault();

			if ( confirm( ProductMediaParams.confirmRemove ) ) {
				const $container = $( this ).closest( '.media-tag-item' );
				clearMediaUpload( $container );
			}
		} );
	}

	/**
	 * Initialize URL input handlers
	 *
	 * @since 0.2.1
	 *
	 * @return {void}
	 */
	function initUrlHandlers() {
		$( document ).on( 'click', '.preview-url-button', function ( e ) {
			e.preventDefault();

			const $button = $( this );
			const $input = $button.siblings( '.media-url-input' );
			const url = $input.val().trim();

			if ( ! url ) {
				alert( 'Please enter a URL first' );
				return;
			}

			previewUrl( url, $button );
		} );

		$( document ).on( 'blur', '.media-url-input', function () {
			const $input = $( this );
			const $container = $input.closest( '.media-tag-item' );
			const url = $input.val().trim();

			if ( url ) {
				updateMediaUrl( $container, url );
			}
		} );

		$( document ).on( 'click', '.clear-url-button', function ( e ) {
			e.preventDefault();

			const $container = $( this ).closest( '.media-tag-item' );
			clearMediaUrl( $container );
		} );
	}

	/**
	 * Initialize Ecwid media handlers
	 *
	 * @since 0.2.1
	 *
	 * @return {void}
	 */
	function initEcwidHandlers() {
		$( document ).on( 'change', '.ecwid-position-select', function () {
			const $select = $( this );
			const $container = $select.closest( '.media-tag-item' );
			const position = $select.val();

			if ( position !== '' ) {
				updateEcwidSelection( $container, position );
			}
		} );

		$( document ).on( 'click', '.clear-ecwid-button', function ( e ) {
			e.preventDefault();

			const $container = $( this ).closest( '.media-tag-item' );
			clearEcwidSelection( $container );
		} );
	}

	/**
	 * Load Ecwid product images via AJAX
	 *
	 * @since 0.2.1
	 *
	 * @param {Function} callback Callback function to execute after loading
	 *
	 * @return {void}
	 */
	function loadEcwidImages( callback = null ) {
		if ( ! ProductMediaParams.postId ) {
			if ( callback ) {
				callback( false );
			}
			return;
		}

		$.ajax( {
			url: ProductMediaParams.ajaxUrl,
			type: 'POST',
			data: {
				action: 'load_ecwid_media',
				nonce: ProductMediaParams.nonce,
				post_id: ProductMediaParams.postId,
			},
			success( response ) {
				if ( response.success && response.data.images ) {
					populateEcwidOptions( response.data.images );
					if ( callback ) {
						callback( true );
					}
				} else {
					console.error(
						'Failed to load Ecwid images:',
						response.data
					);
					if ( callback ) {
						callback( false );
					}
				}
			},
			error( xhr, status, error ) {
				console.error( 'AJAX error loading Ecwid images:', error );
				if ( callback ) {
					callback( false );
				}
			},
		} );
	}

	/**
	 * Populate Ecwid position select options with loaded images
	 *
	 * @since 0.2.1
	 *
	 * @param {Array} images Array of image objects
	 *
	 * @return {void}
	 */
	function populateEcwidOptions( images ) {
		// Store images data for preview functionality
		ecwidImagesData = images;

		$( '.ecwid-position-select' ).each( function () {
			const $select = $( this );
			const currentValue = $select.val();

			// First, populate the original select with all options
			// Clear existing options except the first placeholder
			$select.find( 'option:not(:first)' ).remove();

			// Add all image options to the original select
			images.forEach( function ( image ) {
				const $option = $( '<option></option>' )
					.val( image.position )
					.text( image.label );

				if ( image.position.toString() === currentValue ) {
					$option.prop( 'selected', true );
				}

				$select.append( $option );
			} );

			// Then convert to Bootstrap dropdown with thumbnails
			convertToBootstrapDropdown( $select, images, currentValue );
		} );
	}

	/**
	 * Convert select element to Bootstrap dropdown with thumbnails
	 *
	 * @since 0.2.1
	 *
	 * @param {jQuery} $select      Select element to convert
	 * @param {Array}  images       Array of image objects
	 * @param {string} currentValue Currently selected value
	 *
	 * @return {void}
	 */
	function convertToBootstrapDropdown( $select, images, currentValue ) {
		// Only convert if not already converted
		if ( $select.hasClass( 'bootstrap-converted' ) ) {
			return;
		}

		$select.addClass( 'bootstrap-converted' );

		// Hide the original select
		$select.hide();

		// Find the current selection for button text
		let buttonText = 'Select image...';
		let buttonImage = null;

		if ( currentValue ) {
			const selectedImage = images.find(
				( img ) => img.position.toString() === currentValue
			);
			if ( selectedImage ) {
				buttonText = selectedImage.label;
				buttonImage = selectedImage.url;
			}
		}

		// Create Bootstrap dropdown structure
		const dropdownHtml = `
			<div class="dropdown w-100">
				<button class="btn btn-sm btn-outline-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
					<span class="dropdown-text me-2">${ buttonText }</span>
				</button>
				<ul class="dropdown-menu">
					<li><h6 class="dropdown-header text-primary">Ecwid Product Image</h6></li>
					<li><hr class="dropdown-divider"></li>
				</ul>
			</div>
		`;

		// Insert dropdown after the select
		$select.after( dropdownHtml );
		const $dropdown = $select.next( '.dropdown' );
		const $dropdownMenu = $dropdown.find( '.dropdown-menu' );
		const $dropdownBtn = $dropdown.find( '.dropdown-toggle' );

		// Add image options
		images.forEach( function ( image ) {
			const isSelected = image.position.toString() === currentValue;
			const itemHtml = `
				<li>
					<a class="dropdown-item ${ isSelected ? 'active' : '' }"
					   href="#"
					   data-value="${ image.position }">
						<img src="${ image.url }" alt="${
							image.label
						}" width="50" class="img-thumbnail me-2">
						<span>${ image.label }</span>
					</a>
				</li>
			`;
			$dropdownMenu.append( itemHtml );
		} );

		// Handle dropdown item clicks
		$dropdownMenu.on( 'click', '.dropdown-item', function ( e ) {
			e.preventDefault();

			const $item = $( this );
			const selectedValue = $item.attr( 'data-value' );
			const selectedText = $item.find( 'span' ).text();
			const selectedImage = $item.find( 'img' ).attr( 'src' );

			// Verify the option exists in the original select
			const $targetOption = $select.find(
				`option[value="${ selectedValue }"]`
			);
			if ( $targetOption.length === 0 && selectedValue !== '' ) {
				console.error(
					'Option not found in original select:',
					selectedValue
				);
				return;
			}

			// Update the original select value
			$select.val( selectedValue );

			// Update button appearance
			$dropdownBtn.find( '.dropdown-text' ).text( selectedText );

			// Update active state
			$dropdownMenu.find( '.dropdown-item' ).removeClass( 'active' );
			$item.addClass( 'active' );

			// Trigger change event on original select
			$select.trigger( 'change' );
		} );
	}

	/**
	 * Update media upload display
	 *
	 * @since 0.2.1
	 *
	 * @param {jQuery} $container Media container element
	 * @param {Object} attachment Attachment object
	 *
	 * @return {void}
	 */
	function updateMediaUpload( $container, attachment ) {
		const $preview = $container.find( '.media-preview' );
		const $input = $container.find( '.media-attachment-id' );
		const $button = $container.find( '.select-media-button' );

		// Update hidden input
		$input.val( attachment.id );

		// Update preview
		let previewHtml = '';
		if ( attachment.type === 'image' ) {
			const thumbnailUrl =
				attachment.sizes && attachment.sizes.thumbnail
					? attachment.sizes.thumbnail.url
					: attachment.url;
			previewHtml = `<img src="${ thumbnailUrl }" alt="${ attachment.alt }" class="img-thumbnail">`;
		} else {
			previewHtml = `<div class="media-file-preview">
				<span class="dashicons dashicons-media-default"></span>
				<span>${ attachment.filename }</span>
			</div>`;
		}

		$preview.html( previewHtml ).show();

		// Update button text
		$button.find( 'span:last-child' ).text( 'Change' );

		// Show remove button
		$container.find( '.remove-media-button' ).show();
	}

	/**
	 * Clear media upload data
	 *
	 * @since 0.2.1
	 *
	 * @param {jQuery} $container Media container element
	 *
	 * @return {void}
	 */
	function clearMediaUpload( $container ) {
		const $preview = $container.find( '.media-preview' );
		const $input = $container.find( '.media-attachment-id' );
		const $button = $container.find( '.select-media-button' );

		// Clear hidden input
		$input.val( '' );

		// Clear preview
		$preview.hide().empty();

		// Update button text
		$button.find( 'span:last-child' ).text( 'Select' );

		// Hide remove button
		$container.find( '.remove-media-button' ).hide();
	}

	/**
	 * Preview URL functionality
	 *
	 * @since 0.2.1
	 *
	 * @param {string} url     URL to preview
	 * @param {jQuery} $button Button element that was clicked
	 *
	 * @return {void}
	 */
	function previewUrl( url, $button ) {
		const originalText = $button.html();
		$button
			.html( '<i class="dashicons dashicons-update-alt"></i>' )
			.prop( 'disabled', true );

		$.ajax( {
			url: ProductMediaParams.ajaxUrl,
			type: 'POST',
			data: {
				action: 'preview_media_url',
				nonce: ProductMediaParams.nonce,
				url,
			},
			success( response ) {
				if ( response.success ) {
					alert(
						`URL is valid!\nContent Type: ${
							response.data.content_type
						}\nIs Image: ${ response.data.is_image ? 'Yes' : 'No' }`
					);
				} else {
					alert( `URL validation failed: ${ response.data }` );
				}
			},
			error() {
				alert( 'Error validating URL' );
			},
			complete() {
				$button.html( originalText ).prop( 'disabled', false );
			},
		} );
	}

	/**
	 * Update media URL data
	 *
	 * @since 0.2.1
	 *
	 * @param {jQuery} $container Media container element
	 * @param {string} url        Media URL
	 *
	 * @return {void}
	 */
	function updateMediaUrl( $container, url ) {
		const $input = $container.find( '.media-url-value' );
		const $preview = $container.find( '.media-preview' );

		// Update hidden input
		$input.val( url );

		// Update preview if it's an image URL
		if ( isImageUrl( url ) ) {
			const previewHtml = `<img src="${ url }" alt="URL Preview" class="img-thumbnail">`;
			$preview.html( previewHtml ).show();
		} else {
			const previewHtml = `<div class="media-url-preview">
				<span class="dashicons dashicons-admin-links"></span>
				<span>URL: ${ url }</span>
			</div>`;
			$preview.html( previewHtml ).show();
		}

		// Show clear button
		$container.find( '.clear-url-button' ).show();
	}

	/**
	 * Clear media URL data
	 *
	 * @since 0.2.1
	 *
	 * @param {jQuery} $container Media container element
	 *
	 * @return {void}
	 */
	function clearMediaUrl( $container ) {
		const $input = $container.find( '.media-url-value' );
		const $urlInput = $container.find( '.media-url-input' );
		const $preview = $container.find( '.media-preview' );

		// Clear inputs
		$input.val( '' );
		$urlInput.val( '' );

		// Clear preview
		$preview.hide().empty();

		// Hide clear button
		$container.find( '.clear-url-button' ).hide();
	}

	/**
	 * Update Ecwid selection
	 *
	 * @since 0.2.1
	 *
	 * @param {jQuery} $container Media container element
	 * @param {string} position   Image position
	 *
	 * @return {void}
	 */
	function updateEcwidSelection( $container, position ) {
		const $input = $container.find( '.media-ecwid-position' );
		const $preview = $container.find( '.media-preview' );
		const $select = $container.find( '.ecwid-position-select' );

		// Update hidden input
		$input.val( position );

		// Find the selected image data
		const selectedImage = ecwidImagesData.find(
			( img ) => img.position.toString() === position.toString()
		);

		if ( selectedImage && selectedImage.url ) {
			// Show actual image preview
			const previewHtml = `<img src="${ selectedImage.url }" alt="${ selectedImage.label }" class="img-thumbnail">`;
			$preview.html( previewHtml ).show();
		} else {
			// Fallback to text preview
			const selectedText = $select.find( 'option:selected' ).text();
			const previewHtml = `<div class="ecwid-selection-preview">
				<span class="dashicons dashicons-format-image"></span>
				<span>${ selectedText }</span>
			</div>`;
			$preview.html( previewHtml ).show();
		}

		// Show clear button
		$container.find( '.clear-ecwid-button' ).show();
	}

	/**
	 * Clear Ecwid selection
	 *
	 * @since 0.2.1
	 *
	 * @param {jQuery} $container Media container element
	 *
	 * @return {void}
	 */
	function clearEcwidSelection( $container ) {
		const $input = $container.find( '.media-ecwid-position' );
		const $select = $container.find( '.ecwid-position-select' );
		const $preview = $container.find( '.media-preview' );

		// Clear inputs
		$input.val( '' );
		$select.val( '' );

		// Clear preview
		$preview.hide().empty();

		// Hide clear button
		$container.find( '.clear-ecwid-button' ).hide();
	}

	/**
	 * Clear media data when switching types
	 *
	 * @since 0.2.1
	 *
	 * @param {jQuery} $container Media container element
	 * @param {string} keepType   Type to keep (don't clear)
	 *
	 * @return {void}
	 */
	function clearMediaData( $container, keepType ) {
		if ( keepType !== 'upload' ) {
			clearMediaUpload( $container );
		}
		if ( keepType !== 'url' ) {
			clearMediaUrl( $container );
		}
		if ( keepType !== 'ecwid' ) {
			clearEcwidSelection( $container );
		}
	}

	/**
	 * Check if URL appears to be an image
	 *
	 * @since 0.2.1
	 *
	 * @param {string} url URL to check
	 *
	 * @return {boolean} Whether URL appears to be an image
	 */
	function isImageUrl( url ) {
		const imageExtensions = [
			'.jpg',
			'.jpeg',
			'.png',
			'.gif',
			'.webp',
			'.svg',
			'.bmp',
		];
		const lowerUrl = url.toLowerCase();
		return imageExtensions.some( ( ext ) => lowerUrl.includes( ext ) );
	}

	// Initialize when document is ready
	$( document ).ready( function () {
		initMediaManagement();
	} );
} )( jQuery );
