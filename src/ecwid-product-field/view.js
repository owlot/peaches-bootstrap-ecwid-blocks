/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { getProductDataWithFallbackGenerator } from '../utils/ecwid-view-utils';

/**
 * Ecwid Product Field interactivity store
 */
store( 'peaches-ecwid-product-field', {
	callbacks: {
		/**
		 * Initialize product field display
		 *
		 * Gets product data from global state or fetches selected product data.
		 */
		*initProductField() {
			const context = getContext();
			const element = getElement();

			// Use consolidated utility to get product data
			const product = yield* getProductDataWithFallbackGenerator(
				context.selectedProductId
			);

			if ( ! product ) {
				console.error( 'Product data not found' );
				return;
			}

			// Rest of the existing logic unchanged
			let value = '';
			let isHtml = false;

			try {
				switch ( context.fieldType ) {
					case 'title':
						value = product.name || '';
						break;

					case 'subtitle':
						// Look for subtitle in product attributes
						if ( product.attributes ) {
							const subtitleAttr = product.attributes.find(
								( attr ) =>
									attr.name
										.toLowerCase()
										.includes( 'ondertitel' ) ||
									attr.name
										.toLowerCase()
										.includes( 'subtitle' ) ||
									attr.name
										.toLowerCase()
										.includes( 'sub-title' ) ||
									attr.name
										.toLowerCase()
										.includes( 'tagline' )
							);
							value =
								subtitleAttr?.valueTranslated?.nl ||
								subtitleAttr?.value ||
								'';
						}
						break;

					case 'price':
						if ( product.price !== undefined ) {
							if (
								product.compareToPrice &&
								product.compareToPrice > product.price
							) {
								value = `<span class="original-price text-decoration-line-through text-muted">€ ${ product.compareToPrice
									.toFixed( 2 )
									.replace( '.', ',' ) }</span>
									 <span class="sale-price text-danger">€ ${ product.price
											.toFixed( 2 )
											.replace( '.', ',' ) }</span>`;
								isHtml = true;
							} else {
								value = `€ ${ product.price
									.toFixed( 2 )
									.replace( '.', ',' ) }`;
							}
						}
						break;

					case 'stock':
						const stockClass = product.inStock
							? 'text-success'
							: 'text-danger';
						const stockText = product.inStock
							? wp.i18n.__( 'In Stock', 'ecwid-shopping-cart' )
							: wp.i18n.__(
									'Out of Stock',
									'ecwid-shopping-cart'
							  );
						value = `<span class="${ stockClass }">${ stockText }</span>`;
						isHtml = true;
						break;

					case 'description':
						value = product.description || '';
						isHtml = true; // Description contains HTML
						break;

					case 'custom':
						// Look for custom field by key
						const customFieldKey = context.customFieldKey;
						if ( customFieldKey && product.attributes ) {
							const customField = product.attributes.find(
								( attr ) => attr.name === customFieldKey
							);
							value =
								customField?.valueTranslated?.nl ||
								customField?.value ||
								'';
						}
						break;

					default:
						value = '';
				}

				// Handle HTML vs text content manually
				if ( element && element.ref ) {
					if ( isHtml ) {
						// Set HTML content for fields that contain HTML
						element.ref.innerHTML = value;
					} else {
						// Set text content for plain text fields
						element.ref.textContent = value;
					}
				}

				// Also store in context for consistency
				context.fieldValue = isHtml ? '' : value; // Only store text values in context
			} catch ( error ) {
				console.error( 'Error processing product field:', error );
				context.fieldValue = '';
			}
		},
	},
} );
