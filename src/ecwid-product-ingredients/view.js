import { store, getContext } from '@wordpress/interactivity';

// Access the parent product detail store
const productDetailStore = store( 'peaches-ecwid-product-detail' );

/**
 * Get current language for frontend API requests
 *
 * @return {string} Current language code (normalized to 2 characters)
 */
function getCurrentLanguageForFrontend() {
	// Frontend - check HTML lang attribute (format: "en-US", "fr-FR", "nl-NL", etc.)
	const htmlLang = document.documentElement.lang;
	if ( htmlLang ) {
		// Extract language code (e.g., 'en-US' -> 'en', 'nl-NL' -> 'nl')
		return htmlLang.split( '-' )[ 0 ].toLowerCase();
	}

	// Check URL path for language (e.g., /nl/winkel/product)
	const langMatch = window.location.pathname.match( /^\/([a-z]{2})\// );
	if ( langMatch && langMatch[ 1 ] ) {
		return langMatch[ 1 ];
	}

	// Fallback - check for language in body class (common pattern)
	const bodyClasses = document.body.className;
	const langClassMatch = bodyClasses.match( /\blang-([a-z]{2})\b/ );
	if ( langClassMatch ) {
		return langClassMatch[ 1 ];
	}

	// Ultimate fallback
	return 'en';
}

/**
 * Enhanced fetch function that includes language headers for frontend
 *
 * @param {string} url     - API endpoint URL
 * @param {Object} options - Fetch options
 * @return {Promise} Fetch promise
 */
function fetchWithLanguageHeaders( url, options = {} ) {
	const currentLang = getCurrentLanguageForFrontend();

	// Add language headers for the API
	const headers = {
		Accept: 'application/json',
		'X-Peaches-Language': currentLang,
		...options.headers,
	};

	return fetch( url, {
		credentials: 'same-origin',
		...options,
		headers,
	} );
}

const { state } = store( 'peaches-ecwid-product-ingredients', {
	state: {
		get productId() {
			return productDetailStore.state.productId;
		},
		get productData() {
			return productDetailStore.state.productData;
		},
	},

	actions: {
		toggleAccordion() {
			const context = getContext();
			context.ingredient.isCollapsed = ! context.ingredient.isCollapsed;
		},
	},

	callbacks: {
		*initProductIngredients() {
			const context = getContext();
			const productId = state.productId;

			if ( ! productId ) {
				console.error( 'Product ID not found' );
				context.isLoading = false;
				return;
			}

			try {
				// Get current language for the API call
				const currentLang = getCurrentLanguageForFrontend();
				const baseUrl = `/wp-json/peaches/v1/product-ingredients/${ productId }`;

				// Add language parameter as backup
				const urlWithLang = `${ baseUrl }?lang=${ encodeURIComponent(
					currentLang
				) }`;

				// Fetch ingredients from WordPress API with language support
				const response = yield fetchWithLanguageHeaders( urlWithLang );

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
