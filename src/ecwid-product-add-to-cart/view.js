/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import {
	getProductIdWithFallback,
	getProductDataWithFallbackGenerator,
	__,
} from '../utils/ecwid-view-utils';

/**
 * Ecwid Product Add to Cart interactivity store
 */
const { state } = store( 'peaches-ecwid-add-to-cart', {
	state: {
		/**
		 * Computed state: Button text based on out of stock
		 *
		 * @return {text} - Text to show for the 'add to cart' button
		 */
		get buttonText() {
			const context = getContext();
			if ( context.isOutOfStock ) {
				return __( 'Out of Stock' );
			}
			return __( 'Add to Cart' );
		},

		get shouldDisableControls() {
			const context = getContext();

			if ( context.allowOutOfStockPurchase || ! context.isOutOfStock ) {
				return false;
			}
			return true;
		},
	},

	actions: {
		increaseQuantity( e ) {
			const context = getContext();
			// Only allow increase if controls are not disabled
			if ( ! state.shouldDisableControls ) {
				context.quantity = parseInt( context.quantity ) + 1;
			}
		},
		decreaseQuantity( e ) {
			const context = getContext();
			// Only allow decrease if controls are not disabled and amount > 1
			if ( ! state.shouldDisableControls && context.quantity > 1 ) {
				context.quantity = parseInt( context.quantity ) - 1;
			}
		},
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
		 */
		addToCart: () => {
			const context = getContext();
			const { quantity } = context;

			// Use consolidated utility to get product ID
			const productId = getProductIdWithFallback(
				context.selectedProductId
			);

			if ( ! productId ) {
				console.error( 'No product ID available for add to cart' );
				return;
			}

			// Add to Ecwid cart using the Ecwid JavaScript API
			if ( typeof window.Ecwid !== 'undefined' && window.Ecwid.Cart ) {
				try {
					window.Ecwid.Cart.addProduct( {
						id: productId,
						quantity: parseInt( quantity ) || 1,
					} );

					// Optional: Show success message or feedback
					console.log(
						`Added product ${ productId } to cart with quantity ${ quantity }`
					);
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
		 * Gets product data and sets up initial state.
		 */
		*initAddToCart() {
			const context = getContext();
			context.isLoading = true;

			// Use consolidated utility to get product data
			const productData = yield* getProductDataWithFallbackGenerator(
				context.selectedProductId
			);

			if ( productData ) {
				context.isOutOfStock = ! productData.inStock;
			}

			context.isLoading = false;
		},
	},
} );
