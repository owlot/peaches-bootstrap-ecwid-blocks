import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Push GTM event to dataLayer
 * @param {Object} eventData - The event data to push
 */
const pushGTMEvent = ( eventData ) => {
	window.dataLayer = window.dataLayer || [];
	window.dataLayer.push( eventData );
};

/**
 * Track product impression in GTM
 * @param {Object} productData - Product data from state
 */
const trackProductImpression = ( productData ) => {
	if ( ! productData ) {
		return;
	}

	pushGTMEvent( {
		event: 'productImpression',
		ecommerce: {
			currencyCode: 'EUR',
			impressions: [
				{
					id: productData.id,
					name: productData.name,
					price: productData.price,
					brand: productData.brand,
					category: productData.category,
					variant: productData.variant,
				},
			],
		},
	} );
};

/**
 * Track product click in GTM
 * @param {Object} productData - Product data from state
 */
const trackProductClick = ( productData ) => {
	if ( ! productData ) {
		return;
	}

	pushGTMEvent( {
		event: 'productClick',
		ecommerce: {
			click: {
				products: [
					{
						id: productData.id,
						name: productData.name,
						price: productData.price,
						brand: productData.brand,
						category: productData.category,
						variant: productData.variant,
					},
				],
			},
		},
	} );
};

/**
 * NOTE: Add to cart tracking is handled by Ecwid's native analytics.
 * Ecwid automatically sends GA4 'add_to_cart' events when Ecwid.Cart.addProduct() is called.
 * We don't need to duplicate this tracking here.
 */

const { state } = store( 'peaches-ecwid-product', {
	actions: {
		navigateToProduct() {
			const context = getContext();
			const productData = state.products?.[ context.productId ];

			if ( productData?.productUrl ) {
				// Track product click before navigation
				trackProductClick( productData );
				window.location.href = productData.productUrl;
			}
		},

		addToCart( e ) {
			e.preventDefault();
			e.stopPropagation();

			const context = getContext();
			const productId = context.productId;
			const productData = state.products?.[ productId ];

			if ( productId ) {
				// Check if Ecwid cart API is available
				if ( typeof Ecwid !== 'undefined' && Ecwid.Cart ) {
					try {
						// Ecwid.Cart.addProduct automatically triggers GA4 'add_to_cart' event
						// No need for additional tracking here
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
			const productId = context.productId;
			const productData = state.products?.[ productId ];

			context.isLoading = false;

			// Track product impression using Intersection Observer
			// Only track when product is actually visible in viewport
			const element = getElement();
			if ( element?.ref && 'IntersectionObserver' in window ) {
				const observer = new IntersectionObserver(
					( entries ) => {
						entries.forEach( ( entry ) => {
							if (
								entry.isIntersecting &&
								! context.impressionTracked
							) {
								trackProductImpression( productData );
								context.impressionTracked = true;
								// Unobserve after tracking to prevent duplicate events
								observer.unobserve( entry.target );
							}
						} );
					},
					{
						threshold: 0.5, // Track when 50% of product is visible
					}
				);

				observer.observe( element.ref );
			}
		},
	},
} );
