/**
 * Ecwid Responsive Images JavaScript
 *
 * Handles Ecwid-specific responsive image functionality and integrates
 * with the parent plugin's responsive backgrounds system.
 *
 * @package
 * @since   1.0.0
 */

( function () {
	'use strict';

	/**
	 * Configuration object for Ecwid images
	 */
	const ECWID_CONFIG = {
		selectors: {
			ecwidImg: '.peaches-ecwid-img',
			ecwidGalleryBlock:
				'.wp-block-peaches-bootstrap-ecwid-blocks-ecwid-product-gallery-image',
		},
		classes: {
			loading: 'ecwid-img-loading',
			loaded: 'ecwid-img-loaded',
			error: 'ecwid-img-error',
		},
		attributes: {
			loading: 'data-loading',
			loaded: 'data-loaded',
			error: 'data-error',
			storeId: 'data-ecwid-store-id',
			responsiveType: 'data-responsive-type',
		},
		domains: window.peachesEcwidResponsiveImages?.ecwidDomains || [
			'images-cdn.ecwid.com',
			'd2j6dbq0eux0bg.cloudfront.net',
			'app.ecwid.com',
			'ecwid.com',
		],
	};

	/**
	 * Debug logging function for Ecwid
	 *
	 * @param {string} message - Log message
	 * @param {*}      data    - Additional data to log
	 *
	 * @return {void}
	 */
	function debugLogEcwid( message, data = null ) {
		if ( window.peachesEcwidResponsiveImages?.debug ) {
			console.log(
				'[Peaches Ecwid Responsive Images]',
				message,
				data || ''
			);
		}
	}

	/**
	 * Debug warning function for Ecwid
	 *
	 * @param {string} message - Warning message
	 * @param {*}      data    - Additional data to log
	 *
	 * @return {void}
	 */
	function debugWarnEcwid( message, data = null ) {
		if ( window.peachesEcwidResponsiveImages?.debug ) {
			console.warn(
				'[Peaches Ecwid Responsive Images]',
				message,
				data || ''
			);
		}
	}

	/**
	 * Check if an image URL is from Ecwid
	 *
	 * @param {string} imageUrl - Image URL to check
	 *
	 * @return {boolean} - True if Ecwid image
	 */
	function isEcwidImage( imageUrl ) {
		if ( ! imageUrl ) {
			return false;
		}

		try {
			const url = new URL( imageUrl );
			return ECWID_CONFIG.domains.some( ( domain ) =>
				url.hostname.includes( domain )
			);
		} catch ( error ) {
			return false;
		}
	}

	/**
	 * Extract store ID from Ecwid image URL
	 *
	 * @param {string} imageUrl - Ecwid image URL
	 *
	 * @return {string|null} - Store ID or null if not found
	 */
	function getEcwidStoreIdFromUrl( imageUrl ) {
		if ( ! isEcwidImage( imageUrl ) ) {
			return null;
		}

		// Try various patterns to extract store ID
		const patterns = [
			/\/(\d+)\//, // Simple numeric ID in path
			/store-(\d+)/, // store-12345 pattern
			/storeId[=:](\d+)/, // storeId=12345 or storeId:12345
			/[?&]store=(\d+)/, // ?store=12345 parameter
		];

		for ( const pattern of patterns ) {
			const match = imageUrl.match( pattern );
			if ( match ) {
				return match[ 1 ];
			}
		}

		return null;
	}

	/**
	 * Generate optimized Ecwid image URL
	 *
	 * @param {string} originalUrl - Original Ecwid image URL
	 * @param {number} width       - Target width
	 * @param {number} height      - Target height (optional)
	 * @param {number} quality     - Image quality (optional)
	 *
	 * @return {string} - Optimized image URL
	 */
	function generateEcwidImageUrl(
		originalUrl,
		width,
		height = 0,
		quality = 90
	) {
		if ( ! isEcwidImage( originalUrl ) ) {
			return originalUrl;
		}

		try {
			const url = new URL( originalUrl );
			const params = new URLSearchParams( url.search );

			// Set width
			if ( width > 0 ) {
				params.set( 'w', width.toString() );
			}

			// Set height if specified
			if ( height > 0 ) {
				params.set( 'h', height.toString() );
			}

			// Set quality
			params.set( 'q', quality.toString() );

			// Rebuild URL
			url.search = params.toString();
			return url.toString();
		} catch ( error ) {
			debugWarnEcwid( 'Failed to generate optimized Ecwid URL:', error );
			return originalUrl;
		}
	}

	/**
	 * Update Ecwid image loading state
	 *
	 * @param {HTMLImageElement} img   - Image element
	 * @param {string}           state - State: 'loading', 'loaded', 'error'
	 *
	 * @return {void}
	 */
	function updateEcwidImageState( img, state ) {
		// Clear all state attributes first
		img.removeAttribute( ECWID_CONFIG.attributes.loading );
		img.removeAttribute( ECWID_CONFIG.attributes.loaded );
		img.removeAttribute( ECWID_CONFIG.attributes.error );

		// Remove all state classes
		img.classList.remove(
			ECWID_CONFIG.classes.loading,
			ECWID_CONFIG.classes.loaded,
			ECWID_CONFIG.classes.error
		);

		// Apply new state
		switch ( state ) {
			case 'loading':
				img.setAttribute( ECWID_CONFIG.attributes.loading, 'true' );
				img.classList.add( ECWID_CONFIG.classes.loading );
				break;
			case 'loaded':
				img.setAttribute( ECWID_CONFIG.attributes.loaded, 'true' );
				img.classList.add( ECWID_CONFIG.classes.loaded );
				break;
			case 'error':
				img.setAttribute( ECWID_CONFIG.attributes.error, 'true' );
				img.classList.add( ECWID_CONFIG.classes.error );
				break;
		}

		debugLogEcwid( `Ecwid image state updated to: ${ state }`, img );
	}

	/**
	 * Optimize Ecwid image for current viewport
	 *
	 * @param {HTMLImageElement} img - Ecwid image element
	 *
	 * @return {Promise<void>} - Optimization promise
	 */
	async function optimizeEcwidImageForViewport( img ) {
		const src = img.getAttribute( 'src' );

		if ( ! isEcwidImage( src ) ) {
			return;
		}

		// Get current viewport width
		const viewportWidth = window.innerWidth;

		// Determine optimal width based on viewport and device pixel ratio
		const devicePixelRatio = window.devicePixelRatio || 1;
		const optimalWidth = Math.round( viewportWidth * devicePixelRatio );

		// Generate optimized URL
		const optimizedUrl = generateEcwidImageUrl( src, optimalWidth );

		// Only update if URL is different
		if ( optimizedUrl !== src ) {
			debugLogEcwid(
				`Optimizing Ecwid image for viewport ${ viewportWidth }px:`,
				{
					original: src,
					optimized: optimizedUrl,
				}
			);

			// Update src attribute
			img.setAttribute( 'src', optimizedUrl );
		}
	}

	/**
	 * Process a single Ecwid responsive image
	 *
	 * @param {HTMLImageElement} img - Ecwid image element to process
	 *
	 * @return {Promise<void>} - Processing promise
	 */
	async function processEcwidResponsiveImage( img ) {
		const src = img.getAttribute( 'src' );

		if ( ! isEcwidImage( src ) ) {
			return;
		}

		// Skip if already processed
		if (
			img.hasAttribute( ECWID_CONFIG.attributes.loaded ) ||
			img.hasAttribute( ECWID_CONFIG.attributes.error )
		) {
			return;
		}

		debugLogEcwid( 'Processing Ecwid responsive image:', img );

		// Set loading state
		updateEcwidImageState( img, 'loading' );

		// Add store ID if not present
		const storeId = getEcwidStoreIdFromUrl( src );
		if (
			storeId &&
			! img.hasAttribute( ECWID_CONFIG.attributes.storeId )
		) {
			img.setAttribute( ECWID_CONFIG.attributes.storeId, storeId );
		}

		try {
			// Integrate with parent plugin's image loading tracker if available
			if ( window.PeachesResponsiveImages?.registerLoading ) {
				window.PeachesResponsiveImages.registerLoading( img );
			}

			// Wait for image to load
			const loadSuccess = await waitForEcwidImageLoad( img );

			if ( loadSuccess ) {
				updateEcwidImageState( img, 'loaded' );

				// Notify parent plugin's tracker
				if ( window.PeachesResponsiveImages?.registerLoaded ) {
					window.PeachesResponsiveImages.registerLoaded( img );
				}

				debugLogEcwid( 'Ecwid image loaded successfully:', img );
			} else {
				updateEcwidImageState( img, 'error' );
				debugWarnEcwid( 'Ecwid image failed to load:', img );
			}
		} catch ( error ) {
			updateEcwidImageState( img, 'error' );
			debugWarnEcwid( 'Error processing Ecwid responsive image:', error );
		}
	}

	/**
	 * Wait for Ecwid image to load
	 *
	 * @param {HTMLImageElement} img - Ecwid image element
	 *
	 * @return {Promise<boolean>} - Promise resolving to load status
	 */
	function waitForEcwidImageLoad( img ) {
		return new Promise( ( resolve ) => {
			if ( img.complete && img.naturalHeight !== 0 ) {
				resolve( true );
				return;
			}

			let loadTimer = null;
			const errorTimer = null;

			const handleLoad = () => {
				clearTimeout( loadTimer );
				clearTimeout( errorTimer );
				img.removeEventListener( 'load', handleLoad );
				img.removeEventListener( 'error', handleError );
				resolve( true );
			};

			const handleError = () => {
				clearTimeout( loadTimer );
				clearTimeout( errorTimer );
				img.removeEventListener( 'load', handleLoad );
				img.removeEventListener( 'error', handleError );
				resolve( false );
			};

			img.addEventListener( 'load', handleLoad );
			img.addEventListener( 'error', handleError );

			// Fallback timeout for slow-loading images
			loadTimer = setTimeout( () => {
				img.removeEventListener( 'load', handleLoad );
				img.removeEventListener( 'error', handleError );
				resolve( img.complete && img.naturalHeight !== 0 );
			}, 15000 ); // 15 second timeout for Ecwid images
		} );
	}

	/**
	 * Process all Ecwid responsive images
	 *
	 * @param {HTMLElement} container - Container to search in
	 *
	 * @return {void}
	 */
	function processAllEcwidResponsiveImages( container = document ) {
		const ecwidImages = container.querySelectorAll(
			ECWID_CONFIG.selectors.ecwidImg
		);

		debugLogEcwid(
			`Found ${ ecwidImages.length } Ecwid responsive images to process`
		);

		ecwidImages.forEach( processEcwidResponsiveImage );
	}

	/**
	 * Handle viewport resize for Ecwid images
	 *
	 * @return {void}
	 */
	function handleEcwidImageResize() {
		// Debounce resize events
		clearTimeout( window.ecwidImagesResizeTimer );

		window.ecwidImagesResizeTimer = setTimeout( () => {
			debugLogEcwid( 'Viewport resized, optimizing Ecwid images' );

			const ecwidImages = document.querySelectorAll(
				ECWID_CONFIG.selectors.ecwidImg
			);

			ecwidImages.forEach( optimizeEcwidImageForViewport );
		}, 250 );
	}

	/**
	 * Initialize Ecwid responsive images integration
	 *
	 * @return {void}
	 */
	function initEcwidIntegration() {
		// Wait for parent plugin to be available
		const checkParentPlugin = () => {
			if ( window.PeachesResponsiveImages ) {
				debugLogEcwid(
					'Parent plugin detected, integrating Ecwid functionality...'
				);

				// Process existing Ecwid images
				processAllEcwidResponsiveImages();

				// Handle viewport changes
				window.addEventListener( 'resize', handleEcwidImageResize );

				return true;
			}
			return false;
		};

		if ( ! checkParentPlugin() ) {
			// Poll for parent plugin availability
			let attempts = 0;
			const maxAttempts = 50; // 5 seconds max

			const pollInterval = setInterval( () => {
				attempts++;

				if ( checkParentPlugin() || attempts >= maxAttempts ) {
					clearInterval( pollInterval );

					if ( attempts >= maxAttempts ) {
						debugWarnEcwid(
							'Parent plugin not detected, proceeding independently'
						);
						processAllEcwidResponsiveImages();
						window.addEventListener(
							'resize',
							handleEcwidImageResize
						);
					}
				}
			}, 100 );
		}
	}

	/**
	 * Handle mutation observer for dynamically added Ecwid images
	 *
	 * @param {MutationRecord[]} mutations - DOM mutations
	 *
	 * @return {void}
	 */
	function handleEcwidMutation( mutations ) {
		mutations.forEach( ( mutation ) => {
			if ( mutation.type === 'childList' ) {
				mutation.addedNodes.forEach( ( node ) => {
					if ( node.nodeType === Node.ELEMENT_NODE ) {
						// Check if the node itself is an Ecwid image
						if (
							node.matches &&
							node.matches( ECWID_CONFIG.selectors.ecwidImg )
						) {
							processEcwidResponsiveImage( node );
						}

						// Check for Ecwid images within the node
						const childEcwidImages =
							node.querySelectorAll &&
							node.querySelectorAll(
								ECWID_CONFIG.selectors.ecwidImg
							);
						if ( childEcwidImages ) {
							childEcwidImages.forEach(
								processEcwidResponsiveImage
							);
						}
					}
				} );
			}
		} );
	}

	/**
	 * Initialize Ecwid mutation observer
	 *
	 * @return {void}
	 */
	function initEcwidMutationObserver() {
		if ( ! window.MutationObserver ) {
			return;
		}

		const observer = new MutationObserver( handleEcwidMutation );
		observer.observe( document.body, {
			childList: true,
			subtree: true,
		} );

		debugLogEcwid( 'Ecwid mutation observer initialized' );
	}

	/**
	 * Initialize Ecwid responsive images system
	 *
	 * @return {void}
	 */
	function initEcwidResponsiveImages() {
		debugLogEcwid( 'Initializing Ecwid responsive images system' );

		// Initialize mutation observer for dynamic content
		initEcwidMutationObserver();

		// Integrate with parent plugin
		initEcwidIntegration();

		debugLogEcwid( 'Ecwid responsive images system initialized' );
	}

	/**
	 * Public API for Ecwid responsive images
	 */
	window.PeachesEcwidResponsiveImages = {
		init: initEcwidResponsiveImages,
		processImage: processEcwidResponsiveImage,
		processAll: processAllEcwidResponsiveImages,
		isEcwidImage,
		generateUrl: generateEcwidImageUrl,
		getStoreId: getEcwidStoreIdFromUrl,
		config: ECWID_CONFIG,
	};

	// Auto-initialize when DOM is ready
	if ( document.readyState === 'loading' ) {
		document.addEventListener(
			'DOMContentLoaded',
			initEcwidResponsiveImages
		);
	} else {
		// DOM already ready
		initEcwidResponsiveImages();
	}

	debugLogEcwid( 'Ecwid responsive images script loaded' );
} )();
