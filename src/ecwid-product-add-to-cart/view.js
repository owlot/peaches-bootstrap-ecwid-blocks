import { store, getContext } from '@wordpress/interactivity';

// Access the parent product detail store
const productDetailStore = store( 'peaches-ecwid-product-detail' );

const { state } = store( 'peaches-ecwid-add-to-cart', {
	state: {
		get productId() {
			return productDetailStore.state.productId;
		},
		get productData() {
			return productDetailStore.state.productData;
		},
		get isInStock() {
			// Check stock status from product data
			if ( state.productData ) {
				return state.productData.inStock !== false;
			}
			return true; // Default to in stock if no data
		},
		get shouldDisableControls() {
			const context = getContext();
			// Disable controls if out of stock AND allowOutOfStockPurchase is false
			return ! state.isInStock && ! context.allowOutOfStockPurchase;
		},
	},

	actions: {
		increaseAmount( e ) {
			const context = getContext();
			// Only allow increase if controls are not disabled
			if ( ! state.shouldDisableControls ) {
				context.amount = parseInt( context.amount ) + 1;
			}
		},
		decreaseAmount( e ) {
			const context = getContext();
			// Only allow decrease if controls are not disabled and amount > 1
			if ( ! state.shouldDisableControls && context.amount > 1 ) {
				context.amount = parseInt( context.amount ) - 1;
			}
		},
		setAmount( e ) {
			const context = getContext();
			// Only allow amount changes if controls are not disabled
			if ( ! state.shouldDisableControls ) {
				const value = parseInt( e.target.value );
				context.amount = value > 0 ? value : 1;
			}
		},
		addToCart( e ) {
			const context = getContext();
			const productId = state.productId;

			// Check if controls are disabled (this includes the allowOutOfStockPurchase logic)
			if ( state.shouldDisableControls ) {
				console.warn(
					'Cannot add product to cart - controls are disabled'
				);
				return;
			}

			if ( productId ) {
				// Check if Ecwid cart API is available
				if ( typeof Ecwid !== 'undefined' && Ecwid.Cart ) {
					try {
						Ecwid.Cart.addProduct( {
							id: productId,
							quantity: parseInt( context.amount ),
						} );

						// Reset quantity to 1 after successful add
						context.amount = 1;
					} catch ( error ) {
						console.error( 'Error adding product to cart:', error );
					}
				} else {
					console.error( 'Ecwid Cart API not available' );
				}
			} else {
				console.error( 'Product ID not found' );
			}
		},
	},
} );
