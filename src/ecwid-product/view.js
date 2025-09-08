import { store, getContext, getElement } from '@wordpress/interactivity';

const { state } = store( 'peaches-ecwid-product', {
	actions: {
		navigateToProduct() {
			const context = getContext();
			const productData = state.products?.[ context.productId ];

			if ( productData?.productUrl ) {
				window.location.href = productData.productUrl;
			}
		},

		addToCart( e ) {
			e.preventDefault();
			e.stopPropagation();

			const context = getContext();
			const productId = context.productId;

			if ( productId ) {
				// Check if Ecwid cart API is available
				if ( typeof Ecwid !== 'undefined' && Ecwid.Cart ) {
					try {
						Ecwid.Cart.addProduct( {
							id: productId,
							quantity: 1,
						} );
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

		handleMouseEnter() {
			const context = getContext();
			const productData = state.products?.[ context.productId ];

			if ( productData?.hoverImageUrl ) {
				context.isHovering = true;
			}
		},

		handleMouseLeave() {
			const context = getContext();
			context.isHovering = false;
		},
	},

	callbacks: {
		*initProduct() {
			const context = getContext();
			context.isLoading = false;
		},
	},
} );
