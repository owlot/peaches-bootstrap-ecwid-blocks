/**
 * Ecwid Product Add to Cart interactivity store
 *//**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import {
	getProductIdWithFallback,
	getProductDataWithFallbackGenerator,
	getCurrentLanguageForAPI,
} from '../utils/ecwid-view-utils';

/**
 * Ecwid Product Add to Cart interactivity store
 */
const { state } = store( 'peaches-ecwid-add-to-cart', {
	state: {
		/**
		 * Computed state: Button text based on out of stock status and current language
		 *
		 * Uses the same translation pattern as other ecwid blocks for consistency.
		 *
		 * @return {string} - Text to show for the 'add to cart' button
		 */
		get buttonText() {
			const context = getContext();

			// Get current language (dynamic, supports language switching)
			const currentLang = getCurrentLanguageForAPI();

			// Get multilingual data if available
			const multilingualData = window.peachesLanguageData;
			const defaultLanguage = multilingualData?.defaultLanguage;

			// Determine which text to use based on stock status
			const useOutOfStock = context.isOutOfStock;
			const defaultText = useOutOfStock
				? context.defaultOutOfStockText
				: context.defaultButtonText;
			const translations = useOutOfStock
				? context.outOfStockTextTranslations
				: context.buttonTextTranslations;

			// If no language info or on default language, use default text
			if ( ! currentLang || currentLang === defaultLanguage ) {
				return (
					defaultText ||
					( useOutOfStock ? 'Out of Stock' : 'Add to Cart' )
				);
			}

			// Try to get translation for current language
			if ( translations && translations[ currentLang ] ) {
				return translations[ currentLang ];
			}

			// Fallback to default text
			return (
				defaultText ||
				( useOutOfStock ? 'Out of Stock' : 'Add to Cart' )
			);
		},

		/**
		 * Computed state: Should disable controls based on stock status
		 *
		 * @return {boolean} - Whether to disable quantity and cart controls
		 */
		get shouldDisableControls() {
			const context = getContext();

			if ( context.allowOutOfStockPurchase || ! context.isOutOfStock ) {
				return false;
			}
			return true;
		},
	},

	actions: {
		/**
		 * Increase quantity by 1
		 *
		 * @param {Event} e - Click event
		 */
		increaseQuantity( e ) {
			const context = getContext();
			// Only allow increase if controls are not disabled
			if ( ! state.shouldDisableControls ) {
				context.quantity = parseInt( context.quantity ) + 1;
			}
		},

		/**
		 * Decrease quantity by 1 (minimum 1)
		 *
		 * @param {Event} e - Click event
		 */
		decreaseQuantity( e ) {
			const context = getContext();
			// Only allow decrease if controls are not disabled and amount > 1
			if ( ! state.shouldDisableControls && context.quantity > 1 ) {
				context.quantity = parseInt( context.quantity ) - 1;
			}
		},

		/**
		 * Set quantity from input field
		 *
		 * @param {Event} e - Input event
		 */
		setQuantity( e ) {
			const context = getContext();
			// Only allow amount changes if controls are not disabled
			if ( ! state.shouldDisableControls ) {
				const value = parseInt( e.target.value );
				context.quantity = value > 0 ? value : 1;
			}
		},

		/**
		 * Add product to cart
		 *
		 * Handles the add to cart action with Ecwid integration.
		 * Also pushes Google Tag Manager events for enhanced e-commerce tracking.
		 */
		addToCart: () => {
			const context = getContext();
			const { quantity, productData } = context;

			// Use consolidated utility to get product ID
			const productId = getProductIdWithFallback(
				context.selectedProductId
			);

			if ( ! productId ) {
				console.error( 'No product ID available for add to cart' );
				return;
			}

			// Push GTM add to cart event before adding to Ecwid cart
			if ( productData ) {
				window.dataLayer = window.dataLayer || [];
				window.dataLayer.push( {
					event: 'addToCart',
					ecommerce: {
						add: {
							products: [
								{
									id: productId,
									name: productData.name || '',
									price: productData.price || 0,
									brand:
										productData.brand ||
										document.querySelector(
											'meta[property="og:site_name"]'
										)?.content ||
										'',
									category: productData.categoryName || '',
									variant: productData.sku || '',
									quantity: parseInt( quantity ) || 1,
								},
							],
						},
					},
				} );
			}

			// Add to Ecwid cart using the Ecwid JavaScript API
			if ( typeof window.Ecwid !== 'undefined' && window.Ecwid.Cart ) {
				try {
					window.Ecwid.Cart.addProduct( {
						id: productId,
						quantity: parseInt( quantity ) || 1,
					} );
				} catch ( error ) {
					console.error( 'Error adding product to cart:', error );
				}
			} else {
				console.error( 'Ecwid Cart API not available' );
			}
		},
	},

	callbacks: {
		/**
		 * Initialize add to cart component
		 *
		 * Gets product data and sets up initial state including stock status.
		 */
		initAddToCart: () => {
			const context = getContext();
			const { selectedProductId } = context;

			// Use consolidated utility to get product ID
			const productId = getProductIdWithFallback( selectedProductId );

			if ( ! productId ) {
				console.error( 'No product ID available for initialization' );
				return;
			}

			// Get product data and check stock status
			const getProductData = getProductDataWithFallbackGenerator();
			getProductData( productId )
				.then( ( productData ) => {
					if ( productData ) {
						// Update context with stock information
						context.isOutOfStock = ! productData.inStock;
						context.productData = productData;
					} else {
						console.warn(
							`No product data found for ID: ${ productId }`
						);
						// Assume in stock if we can't get data
						context.isOutOfStock = false;
					}
				} )
				.catch( ( error ) => {
					console.error( 'Error fetching product data:', error );
					// Assume in stock if there's an error
					context.isOutOfStock = false;
				} );
		},
	},
} );
