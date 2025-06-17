import { store, getContext, getElement } from '@wordpress/interactivity';

// Access the parent product detail store
const productDetailStore = store( 'peaches-ecwid-product-detail' );

/**
 * Gallery Image Store
 *
 * Manages the state and actions for individual gallery image blocks
 * that display specific media tags from product settings with fallback support
 * and media type-specific rendering.
 */
const { state, actions } = store( 'peaches-ecwid-product-gallery-image', {
	state: {
		/**
		 * Get product ID from parent store
		 *
		 * @return {string|null} - Current product ID
		 */
		get productId() {
			return productDetailStore.state.productId;
		},

		/**
		 * Get product data from parent store
		 *
		 * @return {Object|null} - Current product data
		 */
		get productData() {
			return productDetailStore.state.productData;
		},

		/**
		 * Computed state: Should show loading spinner
		 *
		 * @return {boolean} - True if loading should be shown
		 */
		get showLoading() {
			const context = getContext();
			return context.isLoading === true;
		},

		/**
		 * Computed state: Should hide entire block
		 *
		 * @return {boolean} - True if block should be hidden
		 */
		get shouldHideBlock() {
			const context = getContext();
			return (
				context.hideIfMissing &&
				! context.hasMedia &&
				! context.isLoading
			);
		},
	},

	actions: {
		/**
		 * Handle media load errors
		 *
		 * Sets appropriate state when media fails to load.
		 */
		handleMediaError() {
			const context = getContext();
			context.hasMedia = false;
			context.isLoading = false;
			actions.updateMediaDisplay();
		},

		/**
		 * Handle successful media load
		 *
		 * Updates state when media loads successfully.
		 */
		handleMediaLoad() {
			const context = getContext();
			context.isLoading = false;
		},

		/**
		 * Update the media display in the DOM
		 *
		 * Creates the appropriate media element based on media type and inserts it into the element.
		 *
		 * @param {HTMLElement} element - DOM element reference
		 */
		updateMediaDisplay( element ) {
			if ( ! element || typeof element.querySelector !== 'function' ) {
				return;
			}

			// Clear any existing media content (keep loading spinner)
			const existingMedia = element.querySelector(
				'.media-element, .no-media-fallback'
			);
			if ( existingMedia ) {
				existingMedia.remove();
			}

			const context = getContext();
			// Don't add anything if we're still loading
			if ( context.isLoading ) {
				return;
			}

			// Declare variables just before they're used to avoid early return issues
			const hasAnyMedia = actions.determineMediaAvailability( context );

			// No media and hideIfMissing is true - show nothing (block will be hidden)
			if ( ! hasAnyMedia && context.hideIfMissing ) {
				return;
			}

			// No media and hideIfMissing is false - show fallback
			if ( ! hasAnyMedia && ! context.hideIfMissing ) {
				const fallbackDiv = document.createElement( 'div' );
				fallbackDiv.className =
					'no-media-fallback d-flex align-items-center justify-content-center text-muted bg-light border w-100 h-100';
				fallbackDiv.style.minHeight = '100px';

				// Use appropriate icon based on expected media type
				const expectedType = context.expectedMediaType || 'image';
				const iconMap = {
					image: 'format-image',
					video: 'format-video',
					audio: 'format-audio',
					document: 'media-document',
				};
				const icon = iconMap[ expectedType ] || 'format-image';

				fallbackDiv.innerHTML = `
					<div class="text-center">
						<i class="dashicons dashicons-${ icon }" style="font-size: 3rem;"></i>
						<p class="mb-0">No ${ expectedType } available</p>
					</div>
				`;
				element.appendChild( fallbackDiv );
				return;
			}

			// Get media details just before creating the element
			const mediaDetails = actions.getMediaDetails( context );
			const mediaElement = actions.createMediaElement(
				mediaDetails.url,
				mediaDetails.alt,
				mediaDetails.type,
				context
			);
			if ( mediaElement ) {
				element.appendChild( mediaElement );
			}
		},

		/**
		 * Determine if any media is available
		 *
		 * @param {Object} context - Block context
		 *
		 * @return {boolean} - True if media is available
		 */
		determineMediaAvailability( context ) {
			// Try primary media first
			if ( context.hasMedia && context.mediaUrl ) {
				return true;
			}

			// Try fallback media if primary not available
			if ( ! context.hideIfMissing && context.fallbackType !== 'none' ) {
				if (
					context.fallbackType === 'tag' &&
					context.fallbackMediaUrl
				) {
					return true;
				}
				if (
					context.fallbackType === 'media' &&
					context.fallbackMediaUrl
				) {
					return true;
				}
			}

			return false;
		},

		/**
		 * Get media details from context
		 *
		 * @param {Object} context - Block context
		 *
		 * @return {Object} - Media details object
		 */
		getMediaDetails( context ) {
			// Try primary media first
			if ( context.hasMedia && context.mediaUrl ) {
				return {
					url: context.mediaUrl,
					alt: context.mediaAlt,
					type: context.mediaType,
				};
			}

			// Try fallback media if primary not available
			if ( ! context.hideIfMissing && context.fallbackType !== 'none' ) {
				if (
					context.fallbackType === 'tag' &&
					context.fallbackMediaUrl
				) {
					return {
						url: context.fallbackMediaUrl,
						alt: context.fallbackMediaAlt,
						type: context.fallbackMediaType,
					};
				}
				if (
					context.fallbackType === 'media' &&
					context.fallbackMediaUrl
				) {
					return {
						url: context.fallbackMediaUrl,
						alt: context.fallbackMediaAlt,
						type: context.fallbackMediaType,
					};
				}
			}

			// Default fallback
			return {
				url: '',
				alt: '',
				type: 'image',
			};
		},

		/**
		 * Create media element based on type
		 *
		 * Creates the appropriate HTML element for the media type with proper attributes.
		 *
		 * @param {string} mediaUrl  - Media URL
		 * @param {string} mediaAlt  - Alt text/title
		 * @param {string} mediaType - Media type (image, video, audio, document)
		 * @param {Object} context   - Block context with settings
		 *
		 * @return {HTMLElement|null} - Created media element or null
		 */
		createMediaElement( mediaUrl, mediaAlt, mediaType, context ) {
			let mediaElement;

			switch ( mediaType ) {
				case 'video':
					mediaElement = document.createElement( 'video' );
					mediaElement.className = 'media-element w-100 h-100';
					mediaElement.style.objectFit = 'cover';
					mediaElement.src = mediaUrl;

					// Apply video-specific settings from context
					if ( context.videoAutoplay ) {
						mediaElement.autoplay = true;
					}
					if ( context.videoMuted ) {
						mediaElement.muted = true;
					}
					if ( context.videoLoop ) {
						mediaElement.loop = true;
					}
					if ( context.videoControls ) {
						mediaElement.controls = true;
					}

					// Add preload for better UX
					mediaElement.preload = 'metadata';

					// Add fallback content
					mediaElement.innerHTML =
						'<p>Your browser does not support the video element.</p>';

					// Add error handling
					mediaElement.addEventListener( 'error', () => {
						actions.handleMediaError();
					} );

					break;

				case 'audio':
					mediaElement = document.createElement( 'audio' );
					mediaElement.className = 'media-element w-100';
					mediaElement.src = mediaUrl;

					// Apply audio-specific settings from context
					if ( context.audioAutoplay ) {
						mediaElement.autoplay = true;
					}
					if ( context.audioLoop ) {
						mediaElement.loop = true;
					}
					if ( context.audioControls ) {
						mediaElement.controls = true;
					}

					// Add preload for better UX
					mediaElement.preload = 'metadata';

					// Add fallback content
					mediaElement.innerHTML =
						'<p>Your browser does not support the audio element.</p>';

					// Add error handling
					mediaElement.addEventListener( 'error', () => {
						actions.handleMediaError();
					} );

					break;

				case 'document':
					// For documents, create a download link or embed based on type
					if ( mediaUrl.toLowerCase().includes( '.pdf' ) ) {
						// Try to embed PDF, fallback to link
						mediaElement = document.createElement( 'div' );
						mediaElement.className = 'media-element w-100 h-100';

						// Check if browser supports PDF embedding
						const hasPdfSupport =
							typeof window !== 'undefined' &&
							window.navigator &&
							typeof window.navigator.pdfViewerEnabled !==
								'undefined' &&
							window.navigator.pdfViewerEnabled !== false;

						if ( hasPdfSupport ) {
							const iframe = document.createElement( 'iframe' );
							iframe.src = mediaUrl;
							iframe.className = 'w-100 h-100';
							iframe.style.minHeight = '400px';
							iframe.title = mediaAlt || 'PDF Document';

							iframe.addEventListener( 'error', () => {
								// Fallback to download link if iframe fails
								mediaElement.innerHTML =
									actions.createDocumentLinkHTML(
										mediaUrl,
										mediaAlt
									);
							} );

							mediaElement.appendChild( iframe );
						} else {
							// Browser doesn't support PDF viewing, show download link
							mediaElement.innerHTML =
								actions.createDocumentLinkHTML(
									mediaUrl,
									mediaAlt
								);
						}
					} else {
						// For other document types, always show download link
						mediaElement = document.createElement( 'div' );
						mediaElement.className =
							'media-element w-100 d-flex align-items-center justify-content-center';
						mediaElement.style.minHeight = '200px';
						mediaElement.innerHTML = actions.createDocumentLinkHTML(
							mediaUrl,
							mediaAlt
						);
					}

					break;

				default: // image
					mediaElement = document.createElement( 'img' );
					mediaElement.className =
						'media-element img-fluid w-100 h-100';
					mediaElement.style.objectFit = 'cover';
					mediaElement.src = mediaUrl;
					mediaElement.alt = mediaAlt || '';

					// Add error handling
					mediaElement.addEventListener( 'error', () => {
						actions.handleMediaError();
					} );

					break;
			}

			return mediaElement;
		},

		/**
		 * Create document link HTML
		 *
		 * Creates HTML for document download links.
		 *
		 * @param {string} mediaUrl - Document URL
		 * @param {string} mediaAlt - Document title/alt text
		 *
		 * @return {string} - HTML string for document link
		 */
		createDocumentLinkHTML( mediaUrl, mediaAlt ) {
			const fileName =
				mediaUrl.split( '/' ).pop() || mediaAlt || 'Download';
			const fileExtension =
				fileName.split( '.' ).pop().toUpperCase() || 'FILE';

			return `
				<div class="text-center">
					<div class="mb-3">
						<i class="dashicons dashicons-media-document" style="font-size: 4rem; color: #666;"></i>
					</div>
					<h5 class="mb-2">${ mediaAlt || fileName }</h5>
					<p class="text-muted mb-3">${ fileExtension } Document</p>
					<a href="${ mediaUrl }"
					   class="btn btn-primary"
					   target="_blank"
					   rel="noopener noreferrer"
					   download>
						<i class="dashicons dashicons-download"></i>
						Download
					</a>
				</div>
			`;
		},

		/**
		 * Fetch media by tag from enhanced API
		 *
		 * Uses the correct API endpoint that matches the registered route.
		 *
		 * @param {string} tagKey - Media tag key to fetch
		 *
		 * @return {Object|null} - Media data or null if not found
		 */
		*fetchMediaByTag( tagKey ) {
			const productId = state.productId;

			if ( ! productId || ! tagKey ) {
				return null;
			}

			try {
				// Fixed API URL to match the registered route in class-media-tags-api.php
				const apiUrl = `/wp-json/peaches/v1/product-media/${ productId }/tag/${ tagKey }`;

				const response = yield window.fetch( apiUrl, {
					headers: {
						Accept: 'application/json',
					},
					credentials: 'same-origin',
				} );

				if ( response.ok ) {
					const data = yield response.json();
					if ( data && data.success && data.data ) {
						return {
							url: data.data.url,
							alt: data.data.alt || data.data.title || '',
							type:
								data.data.type ||
								determineMediaType(
									data.data.url,
									data.data.mime_type
								),
							fallback: data.fallback || false,
						};
					}
				} else if ( response.status === 404 ) {
					// Media not found for this tag - this is expected for some tags
					return null;
				}

				return null;
			} catch ( error ) {
				return null;
			}
		},

		/**
		 * Fetch media tag information to get expected type
		 *
		 * Gets the tag data including expected media type for proper rendering.
		 *
		 * @param {string} tagKey - Media tag key
		 *
		 * @return {Object|null} - Tag data or null if not found
		 */
		*fetchMediaTagInfo( tagKey ) {
			if ( ! tagKey ) {
				return null;
			}

			try {
				const response = yield window.fetch(
					'/wp-json/peaches/v1/media-tags',
					{
						headers: {
							Accept: 'application/json',
						},
						credentials: 'same-origin',
					}
				);

				if ( response.ok ) {
					const data = yield response.json();
					if ( data && data.success && Array.isArray( data.data ) ) {
						const tagInfo = data.data.find(
							( tag ) => tag.key === tagKey
						);
						return tagInfo || null;
					}
				}

				return null;
			} catch ( error ) {
				return null;
			}
		},

		/**
		 * Fetch WordPress media by ID
		 *
		 * Retrieves media information from WordPress media library.
		 *
		 * @param {number} mediaId - WordPress media ID
		 *
		 * @return {Object|null} - Media data or null if not found
		 */
		*fetchWordPressMedia( mediaId ) {
			if ( ! mediaId ) {
				return null;
			}

			try {
				const apiUrl = `/wp-json/wp/v2/media/${ mediaId }`;

				const response = yield window.fetch( apiUrl, {
					headers: {
						Accept: 'application/json',
					},
					credentials: 'same-origin',
				} );

				if ( response.ok ) {
					const data = yield response.json();
					if ( data && data.source_url ) {
						return {
							url: data.source_url,
							alt: data.alt_text || data.title?.rendered || '',
							type: determineMediaType(
								data.source_url,
								data.mime_type
							),
						};
					}
				}

				return null;
			} catch ( error ) {
				return null;
			}
		},
	},

	callbacks: {
		/**
		 * Initialize gallery image
		 *
		 * Fetches and processes media data for the selected tag from the product settings,
		 * with fallback support for alternative tags or specific media files and proper
		 * media type handling.
		 */
		*initGalleryImage() {
			const context = getContext();
			const productId = state.productId;

			// Ensure we have proper initial state
			context.isLoading = true;
			context.hasMedia = false;
			context.mediaUrl = '';
			context.mediaAlt = '';
			context.mediaType = 'image';
			context.expectedMediaType = '';
			context.fallbackMediaUrl = '';
			context.fallbackMediaAlt = '';
			context.fallbackMediaType = 'image';

			// Try to get the element using the Interactivity API
			let element;
			try {
				element = getElement();
			} catch ( error ) {
				// Element access failed, continue without it
			}

			if ( ! productId || ! context.selectedMediaTag ) {
				context.isLoading = false;
				context.hasMedia = false;
				actions.updateMediaDisplay( element?.ref );
				return;
			}

			try {
				// First, get tag information to understand expected media type
				const tagInfo = yield actions.fetchMediaTagInfo(
					context.selectedMediaTag
				);
				if ( tagInfo ) {
					context.expectedMediaType =
						tagInfo.expectedMediaType || 'image';
				}

				// Try to fetch primary media
				const primaryMedia = yield actions.fetchMediaByTag(
					context.selectedMediaTag
				);

				if ( primaryMedia ) {
					// Primary media found
					context.mediaUrl = primaryMedia.url;
					context.mediaAlt = primaryMedia.alt;
					context.mediaType = primaryMedia.type;
					context.hasMedia = true;
				} else {
					// Primary media not found, try fallback if configured
					context.hasMedia = false;

					if (
						! context.hideIfMissing &&
						context.fallbackType !== 'none'
					) {
						let fallbackMedia = null;

						if (
							context.fallbackType === 'tag' &&
							context.fallbackTagKey
						) {
							// Try fallback tag
							fallbackMedia = yield actions.fetchMediaByTag(
								context.fallbackTagKey
							);
						} else if (
							context.fallbackType === 'media' &&
							context.fallbackMediaId
						) {
							// Try fallback WordPress media
							fallbackMedia = yield actions.fetchWordPressMedia(
								context.fallbackMediaId
							);
						}

						if ( fallbackMedia ) {
							context.fallbackMediaUrl = fallbackMedia.url;
							context.fallbackMediaAlt = fallbackMedia.alt;
							context.fallbackMediaType = fallbackMedia.type;
							context.hasMedia = true; // We have fallback media
						}
					}
				}
			} catch ( error ) {
				context.hasMedia = false;
				context.mediaUrl = '';
				context.mediaAlt = '';
				context.mediaType = 'image';
				context.fallbackMediaUrl = '';
				context.fallbackMediaAlt = '';
				context.fallbackMediaType = 'image';
			} finally {
				context.isLoading = false;
				// Update the display after state changes
				actions.updateMediaDisplay( element?.ref );
			}
		},
	},
} );

/**
 * Determine media type from URL and optional mime type
 *
 * Analyzes the media URL and mime type to determine the appropriate media type
 * for rendering (image, video, audio, document).
 *
 * @param {string} url      - Media URL
 * @param {string} mimeType - Optional mime type
 *
 * @return {string} - Media type ('image', 'video', 'audio', 'document')
 */
function determineMediaType( url, mimeType = '' ) {
	// Check mime type first if provided
	if ( mimeType ) {
		if ( mimeType.startsWith( 'video/' ) ) {
			return 'video';
		}
		if ( mimeType.startsWith( 'audio/' ) ) {
			return 'audio';
		}
		if ( mimeType.startsWith( 'image/' ) ) {
			return 'image';
		}
		if (
			mimeType === 'application/pdf' ||
			mimeType.startsWith( 'text/' ) ||
			mimeType.includes( 'document' ) ||
			mimeType.includes( 'word' )
		) {
			return 'document';
		}
	}

	if ( ! url ) {
		return 'image';
	}

	// Parse URL to get pathname without query parameters
	let pathname;
	try {
		pathname = new URL( url ).pathname;
	} catch ( e ) {
		// Fallback for invalid URLs
		pathname = url.split( '?' )[ 0 ];
	}

	// Extract file extension from pathname
	const extension = pathname.split( '.' ).pop().toLowerCase();

	// Video extensions
	const videoExtensions = [
		'mp4',
		'webm',
		'ogg',
		'avi',
		'mov',
		'wmv',
		'flv',
		'm4v',
		'3gp',
		'mkv',
	];
	if ( videoExtensions.includes( extension ) ) {
		return 'video';
	}

	// Audio extensions
	const audioExtensions = [
		'mp3',
		'wav',
		'ogg',
		'aac',
		'flac',
		'm4a',
		'wma',
	];
	if ( audioExtensions.includes( extension ) ) {
		return 'audio';
	}

	// Document extensions
	const documentExtensions = [
		'pdf',
		'doc',
		'docx',
		'txt',
		'rtf',
		'xls',
		'xlsx',
		'ppt',
		'pptx',
	];
	if ( documentExtensions.includes( extension ) ) {
		return 'document';
	}

	// Image extensions (default case, but be explicit)
	const imageExtensions = [
		'jpg',
		'jpeg',
		'png',
		'gif',
		'webp',
		'svg',
		'bmp',
		'tiff',
	];
	if ( imageExtensions.includes( extension ) ) {
		return 'image';
	}

	// Check for video hosting patterns
	if (
		url.includes( 'youtube.com' ) ||
		url.includes( 'youtu.be' ) ||
		url.includes( 'vimeo.com' ) ||
		url.includes( 'wistia.com' ) ||
		url.includes( '/videos/' ) ||
		url.includes( '/video/' )
	) {
		return 'video';
	}

	// Default to image
	return 'image';
}
