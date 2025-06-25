import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import {
	getProductDataWithFallbackGenerator,
	getCurrentLanguageForAPI,
} from '../utils/ecwid-view-utils';

const { state } = store( 'peaches-ecwid-product-description', {
	callbacks: {
		*initProductDescription() {
			const context = getContext();

			// Use consolidated utility to get product data
			const productData = yield* getProductDataWithFallbackGenerator(
				context.selectedProductId
			);

			if ( ! productData?.id ) {
				console.error( 'Product data not found for description block' );

				// Hide the block if no product data
				if ( element && element.ref ) {
					element.ref.style.display = 'none';
				}
				return;
			}

			const descriptionType = context.descriptionType;
			if ( ! descriptionType ) {
				console.error( 'Description type not specified' );
				return;
			}

			try {
				console.log(
					`Fetching description for product ${ productData?.id }, type: ${ descriptionType }`
				);

				// Fetch specific description type from unified API
				const response = yield window.fetch(
					`/wp-json/peaches/v1/product-descriptions/${ productData?.id }/type/${ descriptionType }`,
					{
						headers: {
							Accept: 'application/json',
						},
						credentials: 'same-origin',
					}
				);

				if ( response.status === 404 ) {
					// Description not found for this type - hide the entire block
					console.log(
						`No description found for type: ${ descriptionType }`
					);

					// Get the element and hide it
					try {
						const element = getElement();
						if ( element && element.ref ) {
							element.ref.style.display = 'none';
						}
					} catch ( e ) {
						// Fallback if element access fails
						console.log(
							'Could not hide element, description not found'
						);
					}
					return;
				}

				if ( ! response.ok ) {
					throw new Error(
						`HTTP error! status: ${ response.status }`
					);
				}

				const data = yield response.json();

				if ( data && data.success && data.description ) {
					console.log(
						`Description loaded for type: ${ descriptionType }`,
						data.description
					);

					// Get the element reference like product-field does
					const element = getElement();
					if ( element && element.ref ) {
						// Find title and content elements
						const titleElement = element.ref.querySelector(
							'.product-description-title'
						);
						const contentElement = element.ref.querySelector(
							'.product-description-content'
						);

						// Handle title
						if ( titleElement && context.displayTitle ) {
							if ( context.customTitle ) {
								titleElement.textContent = context.customTitle;
							} else if ( data.description.title ) {
								titleElement.textContent =
									data.description.title;
							} else {
								titleElement.style.display = 'none';
							}
						} else if ( titleElement ) {
							titleElement.style.display = 'none';
						}

						// Handle content - use innerHTML for rich content like product-field does
						if ( contentElement && data.description.content ) {
							contentElement.innerHTML = data.description.content;
						}

						// Show the block
						element.ref.style.display = '';
					}

					// Store in context for consistency
					context.descriptionContent = data.description.content || '';
					context.descriptionTitle = data.description.title || '';
				} else {
					// No description data - hide the block
					console.log(
						`No description data for type: ${ descriptionType }`
					);

					try {
						const element = getElement();
						if ( element && element.ref ) {
							element.ref.style.display = 'none';
						}
					} catch ( e ) {
						console.log(
							'Could not hide element, no description data'
						);
					}
				}
			} catch ( error ) {
				console.error( 'Error fetching product description:', error );

				// Hide the block on error
				try {
					const element = getElement();
					if ( element && element.ref ) {
						element.ref.style.display = 'none';
					}
				} catch ( e ) {
					console.log( 'Could not hide element on error' );
				}
			}
		},
	},
} );
