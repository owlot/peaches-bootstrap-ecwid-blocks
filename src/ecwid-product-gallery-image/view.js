/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Internal dependencies - utilities used across blocks
 */
import { getProductIdWithFallback } from '../utils/ecwid-view-utils';

store( 'peaches-ecwid-product-gallery-image', {
	state: {
		/**
		 * Computed property to determine if block should be hidden
		 *
		 * @return {boolean} Whether the block should be hidden
		 */
		get shouldHideBlock() {
			const context = getContext();
			return context.hideIfMissing && ! context.hasMedia;
		},

		/**
		 * Computed property to determine if loading state should be shown
		 *
		 * @return {boolean} Whether loading state should be shown
		 */
		get showLoading() {
			const context = getContext();
			return context.isLoading;
		},
	},

	actions: {
		/**
		 * Open lightbox for image/video media
		 */
		openLightbox() {
			const context = getContext();

			if ( ! context.enableLightbox || ! context.hasMedia ) {
				return;
			}

			// Only allow lightbox for images and videos
			if ( ! [ 'image', 'video' ].includes( context.mediaType ) ) {
				return;
			}

			context.lightboxOpen = true;

			// Prevent body scrolling when lightbox is open
			document.body.style.overflow = 'hidden';
		},

		/**
		 * Close lightbox
		 */
		closeLightbox() {
			const context = getContext();
			context.lightboxOpen = false;

			// Restore body scrolling
			document.body.style.overflow = '';
		},

		/**
		 * Handle mouse enter for hover effects
		 */
		handleMouseEnter() {
			const context = getContext();
			context.isHovering = true;
		},

		/**
		 * Handle mouse leave for hover effects
		 */
		handleMouseLeave() {
			const context = getContext();
			context.isHovering = false;
		},
	},

	callbacks: {
		/**
		 * Initialize the gallery image block
		 */
		*initGalleryImage() {
			const context = getContext();

			// Server has already provided the media data, but we can
			// refresh the product ID if needed
			const productId = getProductIdWithFallback(
				context.selectedProductId
			);

			// Update context if product ID changed
			if ( productId && productId !== context.selectedProductId ) {
				context.selectedProductId = productId;
			}

			// Mark as initialized and not loading
			context.isLoading = false;

			// Add keyboard event listener for Escape key
			document.addEventListener( 'keydown', ( event ) => {
				if ( event.key === 'Escape' && context.lightboxOpen ) {
					context.lightboxOpen = false;
					document.body.style.overflow = '';
				}
			} );
		},
	},

	utils: {
		/**
		 * Create lightbox media element
		 *
		 * @param {string} mediaUrl  Media URL
		 * @param {string} mediaAlt  Alt text
		 * @param {string} mediaType Media type
		 * @param {Object} context   Block context
		 *
		 * @return {HTMLElement} Media element
		 */
		createLightboxMedia( mediaUrl, mediaAlt, mediaType, context ) {
			let mediaElement;

			if ( mediaType === 'video' ) {
				mediaElement = document.createElement( 'video' );
				mediaElement.src = mediaUrl;
				mediaElement.controls = true;
				mediaElement.autoplay = context.videoAutoplay || false;
				mediaElement.muted = context.videoMuted || false;
				mediaElement.loop = context.videoLoop || false;

				// Style based on zoom level
				switch ( context.lightboxZoomLevel ) {
					case 'fill':
						mediaElement.style.cssText = `
							width: 100%;
							height: 100%;
							object-fit: cover;
						`;
						break;
					case 'original':
						mediaElement.style.cssText = `
							max-height: 90vh;
							width: auto;
							height: auto;
						`;
						break;
					default: // 'fit'
						mediaElement.style.cssText = `
							max-width: 100%;
							max-height: 90vh;
							width: auto;
							height: auto;
							object-fit: contain;
						`;
				}
			} else {
				// Image
				mediaElement = document.createElement( 'img' );
				mediaElement.src = mediaUrl;
				mediaElement.alt = mediaAlt || '';

				// Style based on zoom level
				switch ( context.lightboxZoomLevel ) {
					case 'fill':
						mediaElement.style.cssText = `
							width: 100%;
							height: 100%;
							object-fit: cover;
						`;
						break;
					case 'original':
						mediaElement.style.cssText = `
							max-height: 90vh;
							width: auto;
							height: auto;
						`;
						break;
					default: // 'fit'
						mediaElement.style.cssText = `
							max-width: 100%;
							max-height: 100%;
							width: auto;
							height: auto;
							object-fit: contain;
						`;
				}
			}

			return mediaElement;
		},
	},
} );
