import { store, getContext, getElement } from '@wordpress/interactivity';

// Access the parent product detail store
const productDetailStore = store( 'peaches-ecwid-product-detail' );

const { state, actions } = store( 'peaches-ecwid-product-field', {
	state: {
		get productId() {
			return productDetailStore.state.productId;
		},
		get productData() {
			return productDetailStore.state.productData;
		},
	},

	callbacks: {
		*initProductField() {
			const context = getContext();
			const element = getElement();

			if ( ! state.productId || ! state.productData ) {
				console.error( 'Product data not available' );
				return;
			}

			const product = state.productData;
			const fieldType = context.fieldType;
			let value = '';
			let isHtml = false;

			try {
				switch ( fieldType ) {
					case 'title':
						value = product.name || '';
						break;

					case 'subtitle':
						// Look for subtitle in product attributes
						if ( product.attributes ) {
							const subtitleAttr = product.attributes.find(
								( attr ) =>
									attr.name === 'Ondertitel' ||
									attr.name === 'Subtitle'
							);
							value =
								subtitleAttr?.valueTranslated?.nl ||
								subtitleAttr?.value ||
								'';
						}
						break;

					case 'price':
						// Check if there's a sale price
						if (
							product.compareToPrice &&
							product.compareToPrice > product.price
						) {
							value = `<span class="regular-price text-decoration-line-through me-2 text-muted">€ ${ product.compareToPrice
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
