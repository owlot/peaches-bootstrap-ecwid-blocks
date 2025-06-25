import { store, getContext } from '@wordpress/interactivity';
/**
 * Internal dependencies
 */
import {
	getProductIdWithFallback,
	buildApiUrl,
} from '../utils/ecwid-view-utils';

const { state } = store( 'peaches-ecwid-product-ingredients', {
	actions: {
		toggleAccordion() {
			const context = getContext();
			context.ingredient.isCollapsed = ! context.ingredient.isCollapsed;
		},
	},

	callbacks: {
		*initProductIngredients() {
			const context = getContext();

			// Use consolidated utility to get product ID
			const productId = getProductIdWithFallback(
				context.selectedProductId
			);

			try {
				const apiUrl = buildApiUrl( 'product-ingredients', productId );

				console.log( `Calling API with language: ${ apiUrl }` );
				// Fetch ingredients from WordPress API with language support
				const response = yield fetch( apiUrl, {
					headers: {
						Accept: 'application/json',
					},
					credentials: 'same-origin',
				} );

				if ( ! response.ok ) {
					throw new Error(
						`HTTP error! status: ${ response.status }`
					);
				}

				const data = yield response.json();

				// Transform ingredients data for the accordion
				if (
					data &&
					data.ingredients &&
					Array.isArray( data.ingredients )
				) {
					context.ingredients = data.ingredients.map(
						( ingredient, index ) => ( {
							name: ingredient.name,
							description: ingredient.description,
							targetId: `#collapse-${ productId }-${ index }`,
							collapseId: `collapse-${ productId }-${ index }`,
							headingId: `heading-${ productId }-${ index }`,
							isCollapsed: true, // Start collapsed by default
						} )
					);

					// Log language info for debugging
					if ( data.language && data.language !== 'en' ) {
						console.log(
							`Loaded ingredients in language: ${ data.language }`
						);
					}
				} else {
					context.ingredients = [];
				}

				context.isLoading = false;
			} catch ( error ) {
				console.error( 'Error fetching product ingredients:', error );
				context.ingredients = [];
				context.isLoading = false;
			}
		},
	},
} );
