/**
 * Optimized view.js for ecwid-product-description block
 *
 * Uses hybrid server-side + client-side approach. Data is pre-loaded via render.php,
 * this script handles only interactive features and dynamic updates.
 *
 * @package
 * @since   0.4.7
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import {
	getProductDataWithFallbackGenerator,
	getCurrentLanguageForAPI,
} from '../utils/ecwid-view-utils';

/**
 * Product Description Store
 *
 * Optimized for hybrid rendering - server-side data loading with client-side interactivity.
 */
const { state, actions } = store( 'peaches-ecwid-product-description', {
	state: {
		/**
		 * Check if description content is available
		 *
		 * @return {boolean} True if content is loaded
		 */
		get hasContent() {
			const context = getContext();
			return !! (
				context.descriptionContent && context.descriptionContent.trim()
			);
		},

		/**
		 * Check if we're in loading state
		 *
		 * Only true when data wasn't pre-loaded and we're fetching client-side.
		 *
		 * @return {boolean} Loading state
		 */
		get isLoading() {
			const context = getContext();
			return ! context.isLoaded && ! context.descriptionContent;
		},
	},

	actions: {
		/**
		 * Refresh description data
		 *
		 * Called when description type changes or manual refresh is needed.
		 * Most of the time, this won't be needed due to server-side pre-loading.
		 */
		*refreshDescription() {
			const context = getContext();

			// Only refresh if not already loaded or if explicitly requested
			if ( context.isLoaded && context.descriptionContent ) {
				return; // Already have data, no need to fetch
			}

			// Get product data - use existing utilities
			const productData = yield* getProductDataWithFallbackGenerator(
				context.selectedProductId
			);

			if ( ! productData?.id ) {
				console.warn(
					'Product data not found for description refresh'
				);
				return;
			}

			const descriptionType = context.descriptionType;
			if ( ! descriptionType ) {
				console.warn( 'Description type not specified' );
				return;
			}

			try {
				// Build API URL with language support
				const currentLanguage = getCurrentLanguageForAPI();
				const apiUrl = `/wp-json/peaches/v1/product-descriptions/${ productData.id }/type/${ descriptionType }`;
				const urlWithLang =
					currentLanguage && currentLanguage !== 'en'
						? `${ apiUrl }?lang=${ encodeURIComponent(
								currentLanguage
						  ) }`
						: apiUrl;

				const response = yield window.fetch( urlWithLang, {
					headers: {
						Accept: 'application/json',
					},
					credentials: 'same-origin',
				} );

				if ( response.status === 404 ) {
					// Description not found - hide the block
					const element = getElement();
					if ( element?.ref ) {
						element.ref.style.display = 'none';
					}
					return;
				}

				if ( ! response.ok ) {
					throw new Error(
						`HTTP error! status: ${ response.status }`
					);
				}

				const data = yield response.json();

				if ( data?.success && data?.description ) {
					// Update context with fetched data
					context.descriptionContent = data.description.content || '';
					context.descriptionTitle = data.description.title || '';
					context.isLoaded = true;

					// Update DOM elements
					yield* actions.updateDOM();
				} else {
					// Hide block if no valid data
					const element = getElement();
					if ( element?.ref ) {
						element.ref.style.display = 'none';
					}
				}
			} catch ( error ) {
				console.error( 'Error fetching product description:', error );

				// Hide the block on error
				const element = getElement();
				if ( element?.ref ) {
					element.ref.style.display = 'none';
				}
			}
		},

		/**
		 * Update DOM with current context data
		 *
		 * Used when content changes dynamically (rare, but possible).
		 */
		*updateDOM() {
			const context = getContext();
			const element = getElement();

			if ( ! element?.ref ) {
				return;
			}

			// Update title if needed
			const titleElement = element.ref.querySelector(
				'.product-description-title'
			);
			if ( titleElement && context.displayTitle ) {
				if ( context.customTitle ) {
					titleElement.textContent = context.customTitle;
					titleElement.style.display = '';
				} else if ( context.descriptionTitle ) {
					titleElement.textContent = context.descriptionTitle;
					titleElement.style.display = '';
				} else {
					titleElement.style.display = 'none';
				}
			} else if ( titleElement ) {
				titleElement.style.display = 'none';
			}

			// Update content
			const contentElement = element.ref.querySelector(
				'.product-description-content'
			);
			if ( contentElement && context.descriptionContent ) {
				contentElement.innerHTML = context.descriptionContent;
			}

			// Show the block
			element.ref.style.display = '';
		},
	},

	callbacks: {
		/**
		 * Initialize description block
		 *
		 * In hybrid mode, this mainly validates the pre-loaded data
		 * and sets up any dynamic behavior.
		 */
		*initProductDescription() {
			const context = getContext();

			// If data was pre-loaded server-side, we're done!
			if ( context.isLoaded && context.descriptionContent ) {
				// Data is already rendered by PHP, just ensure everything is visible
				const element = getElement();
				if ( element?.ref ) {
					element.ref.style.display = '';
				}
				return;
			}

			// Fallback: If no server-side data, fetch client-side
			// This handles edge cases or dynamic content updates
			yield* actions.refreshDescription();
		},

		/**
		 * Handle dynamic attribute changes
		 *
		 * Called when block attributes change in the editor or via other interactions.
		 */
		*onAttributeChange() {
			const context = getContext();

			// If description type changed, we need to refresh
			if ( ! context.isLoaded ) {
				yield* actions.refreshDescription();
			} else {
				// Update DOM with current data
				yield* actions.updateDOM();
			}
		},
	},
} );
