/**
 * Shared utilities for Ecwid product blocks view.js files
 *
 * Provides common functionality for getting product data from global state
 * or fetching from REST API as fallback.
 *
 * Updated to use WordPress translation system.
 */

import { store } from '@wordpress/interactivity';

/**
 * Fallback translation function for view.js files
 *
 * Since view.js files can't import @wordpress/i18n, we use fallback methods.
 * Export this so other view.js files can import and use it.
 *
 * @param {string} text   - Text to translate
 * @param {string} domain - Text domain (optional)
 *
 * @return {string} - Translated text or original text
 */
export function __( text, domain = 'peaches-bootstrap-ecwid-blocks' ) {
	// Try WordPress i18n if available globally
	if ( typeof window.wp?.i18n?.__ === 'function' ) {
		return window.wp.i18n.__( text, domain );
	}

	// Try localized strings passed from PHP
	if ( window.peachesEcwidTranslations?.[ text ] ) {
		return window.peachesEcwidTranslations[ text ];
	}

	// Ultimate fallback to original text
	return text;
}

/**
 * Get current language for frontend API requests
 *
 * Uses the global language function if available, with fallback to original logic.
 *
 * @return {string} Current language code (normalized to 2 characters)
 */
export function getCurrentLanguageForAPI() {
	// Use the global language function if available (from multilingual integration)
	if ( typeof window.getCurrentLanguageForAPI === 'function' ) {
		return window.getCurrentLanguageForAPI();
	}

	// Fallback to original logic if global function isn't available
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
 * Build API URL with language parameter
 *
 * Uses the global language-aware URL function if available.
 *
 * @param {string} endpoint  - API endpoint path
 * @param {number} productId - Product ID
 * @param {string} language  - Optional language override
 *
 * @return {string} - Complete API URL with language parameter
 */
export function buildApiUrl( endpoint, productId, language = null ) {
	const apiUrl = `/wp-json/peaches/v1/${ endpoint }/${ productId }`;

	// Use global language-aware URL function if available
	if ( typeof window.getLanguageAwareApiUrl === 'function' && ! language ) {
		return window.getLanguageAwareApiUrl( apiUrl );
	}

	// Fallback to manual language detection
	const currentLanguage = language || getCurrentLanguageForAPI();
	return currentLanguage && currentLanguage !== 'en'
		? `${ apiUrl }?lang=${ encodeURIComponent( currentLanguage ) }`
		: apiUrl;
}

/**
 * Get product data from global state
 *
 * @return {Object|null} - Product data from global state or null
 */
export function getGlobalProductData() {
	const globalStore = store( 'peaches-ecwid-product-detail' );
	return globalStore?.state?.productData || null;
}

/**
 * Fetch product data from REST API (generator version for interactivity)
 *
 * Enhanced with better error handling and nonce support.
 *
 * @param {number} productId - Ecwid product ID
 *
 * @return {Generator<Object|null>} - Product data or null if error
 */
export function* fetchProductDataGenerator( productId ) {
	if ( ! productId ) {
		return null;
	}

	try {
		const apiUrl = buildApiUrl( 'products', productId );

		const response = yield fetch( apiUrl, {
			headers: {
				Accept: 'application/json',
			},
			credentials: 'same-origin',
		} );

		if ( ! response.ok ) {
			throw new Error( `HTTP error! status: ${ response.status }` );
		}

		const responseData = yield response.json();

		if ( responseData?.success && responseData?.data ) {
			return responseData.data;
		}

		console.error(
			'Invalid product data response for product:',
			productId,
			responseData
		);
		return null;
	} catch ( error ) {
		console.error(
			'Error fetching product data for product:',
			productId,
			error
		);
		return null;
	}
}

/**
 * Fetch product data from REST API (Promise version for regular async/await)
 *
 * Standard Promise-based version for use in regular async contexts.
 *
 * @param {number} productId - Ecwid product ID
 *
 * @return {Promise<Object|null>} - Product data or null if error
 */
export async function fetchProductData( productId ) {
	if ( ! productId ) {
		return null;
	}

	try {
		const apiUrl = buildApiUrl( 'ecwid/product', productId );

		const response = await fetch( apiUrl, {
			headers: {
				'X-WP-Nonce': window.wpApiSettings?.nonce || '',
				Accept: 'application/json',
			},
			credentials: 'same-origin',
		} );

		if ( ! response.ok ) {
			throw new Error( `HTTP error! status: ${ response.status }` );
		}

		const responseData = await response.json();

		if ( responseData?.success && responseData?.data ) {
			return responseData.data;
		}

		console.error(
			'Invalid product data response for product:',
			productId,
			responseData
		);
		return null;
	} catch ( error ) {
		console.error(
			'Error fetching product data for product:',
			productId,
			error
		);
		return null;
	}
}

/**
 * Get product data with fallback logic (generator version)
 *
 * Checks global state first, then fetches from API if needed.
 * Use this in interactivity callbacks that support generators.
 *
 * @param {number|null} selectedProductId - Product ID from block context
 *
 * @return {Generator<Object|null>} - Product data or null
 */
export function* getProductDataWithFallbackGenerator(
	selectedProductId = null
) {
	// Try to get product data from global state first
	const globalProductData = getGlobalProductData();

	if ( globalProductData ) {
		return globalProductData;
	}

	// Fall back to fetching selected product data
	if ( selectedProductId ) {
		return yield* fetchProductDataGenerator( selectedProductId );
	}

	return null;
}

/**
 * Get product data with fallback logic (Promise version)
 *
 * Checks global state first, then fetches from API if needed.
 * Use this in regular async contexts.
 *
 * @param {number|null} selectedProductId - Product ID from block context
 *
 * @return {Promise<Object|null>} - Product data or null
 */
export async function getProductDataWithFallback( selectedProductId = null ) {
	// Try to get product data from global state first
	const globalProductData = getGlobalProductData();

	if ( globalProductData ) {
		return globalProductData;
	}

	// Fall back to fetching selected product data
	if ( selectedProductId ) {
		return await fetchProductData( selectedProductId );
	}

	return null;
}

/**
 * Get product ID from global state or fallback to selected ID
 *
 * @param {number|null} selectedProductId - Product ID from block context
 *
 * @return {number|null} - Product ID or null
 */
export function getProductIdWithFallback( selectedProductId = null ) {
	// Try to get product ID from global state first
	const globalProductData = getGlobalProductData();

	if ( globalProductData?.id ) {
		return globalProductData.id;
	}

	const globalStore = store( 'peaches-ecwid-product-detail' );
	const globalProductId = globalStore?.state?.productId;

	if ( globalProductId ) {
		return globalProductId;
	}

	// Fall back to selected product ID
	return selectedProductId || null;
}

/**
 * Get language information from global data
 *
 * Helper function to access language data provided by multilingual integration.
 *
 * @return {Object} - Language information object
 */
export function getLanguageInfo() {
	return {
		current: getCurrentLanguageForAPI(),
		data: window.peachesLanguageData || {},
		isMultilingual: window.peachesLanguageData?.isMultilingual || false,
		plugin: window.peachesLanguageData?.plugin || 'none',
	};
}

/**
 * Debug function for language detection
 *
 * Logs all available language sources for troubleshooting.
 *
 * @return {Object} - Debug information
 */
export function debugLanguageInfo() {
	const debug = {
		current: getCurrentLanguageForAPI(),
		globalFunction: typeof window.getCurrentLanguageForAPI === 'function',
		globalData: window.peachesLanguageData,
		htmlLang: document.documentElement.lang,
		urlPath: window.location.pathname,
		bodyClasses: document.body.className,
	};

	console.log( 'Ecwid View Utils - Language Debug:', debug );
	return debug;
}

/**
 * Validate product data structure
 *
 * Helper function to ensure product data has required fields.
 *
 * @param {Object} productData - Product data to validate
 *
 * @return {boolean} - True if valid product data
 */
export function isValidProductData( productData ) {
	return (
		productData &&
		typeof productData === 'object' &&
		( productData.id || productData.productId ) &&
		productData.name
	);
}

/**
 * Get product field value with fallback
 *
 * Utility to extract specific field values from product data with proper fallbacks.
 * Uses the current language for localized values.
 *
 * @param {Object} productData    - Product data object
 * @param {string} fieldType      - Type of field to extract
 * @param {string} customFieldKey - Key for custom fields
 *
 * @return {string} - Field value or empty string
 */
export function getProductFieldValue(
	productData,
	fieldType,
	customFieldKey = ''
) {
	if ( ! isValidProductData( productData ) ) {
		return '';
	}

	// Get current language for localized values
	const currentLanguage = getCurrentLanguageForAPI();

	try {
		switch ( fieldType ) {
			case 'title':
				return productData.name || '';

			case 'subtitle':
				// Look for subtitle in product attributes
				if ( productData.attributes ) {
					const subtitleAttr = productData.attributes.find(
						( attr ) =>
							attr.name.toLowerCase().includes( 'ondertitel' ) ||
							attr.name.toLowerCase().includes( 'subtitle' ) ||
							attr.name.toLowerCase().includes( 'sub-title' ) ||
							attr.name.toLowerCase().includes( 'tagline' )
					);

					if ( subtitleAttr ) {
						// Try to get localized value for current language
						if (
							subtitleAttr.valueTranslated &&
							subtitleAttr.valueTranslated[ currentLanguage ]
						) {
							return subtitleAttr.valueTranslated[
								currentLanguage
							];
						}
						// Fallback to default value
						return subtitleAttr.value || '';
					}
				}
				return '';

			case 'price':
				if ( productData.price !== undefined ) {
					const comparePrice = productData.compareToPrice;
					const currentPrice = productData.price;

					// Use current language for price formatting
					const locale = getLocaleForLanguage( currentLanguage );
					const formatPrice = ( price ) => {
						return new Intl.NumberFormat( locale, {
							style: 'currency',
							currency: 'EUR',
						} ).format( price );
					};

					if ( comparePrice && comparePrice > currentPrice ) {
						return `${ formatPrice( currentPrice ) } ${ formatPrice(
							comparePrice
						) }`;
					}
					return formatPrice( currentPrice );
				}
				return '';

			case 'stock':
				// Use WordPress translations for stock status
				if ( productData.inStock ) {
					return __( 'In Stock', 'peaches-bootstrap-ecwid-blocks' );
				}
				return __( 'Out of Stock', 'peaches-bootstrap-ecwid-blocks' );

			case 'description':
				return productData.description || '';

			case 'custom':
				if ( customFieldKey && productData.attributes ) {
					const customField = productData.attributes.find(
						( attr ) => attr.name === customFieldKey
					);

					if ( customField ) {
						// Try to get localized value for current language
						if (
							customField.valueTranslated &&
							customField.valueTranslated[ currentLanguage ]
						) {
							return customField.valueTranslated[
								currentLanguage
							];
						}
						// Fallback to default value
						return customField.value || '';
					}
				}
				return '';

			default:
				return '';
		}
	} catch ( error ) {
		console.error( 'Error getting product field value:', error );
		return '';
	}
}

/**
 * Get locale string for language code
 *
 * Maps language codes to proper locale strings for Intl functions.
 *
 * @param {string} languageCode - 2-character language code
 *
 * @return {string} - Locale string (e.g., 'nl-NL', 'en-US')
 */
function getLocaleForLanguage( languageCode ) {
	// Use global language data if available
	if (
		window.peachesLanguageData?.currentLocale &&
		window.peachesLanguageData?.currentLanguage === languageCode
	) {
		return window.peachesLanguageData.currentLocale;
	}

	// Fallback mapping
	const localeMap = {
		nl: 'nl-NL',
		en: 'en-US',
		fr: 'fr-FR',
		de: 'de-DE',
		es: 'es-ES',
		it: 'it-IT',
	};

	return localeMap[ languageCode ] || 'en-US';
}

/**
 * Get product images array with proper structure
 *
 * @param {Object} productData - Product data object
 *
 * @return {Array} - Array of image objects
 */
export function getProductImages( productData ) {
	if ( ! isValidProductData( productData ) ) {
		return [];
	}

	const images = [];

	// Add main image first
	if ( productData.thumbnailUrl ) {
		images.push( {
			url: productData.thumbnailUrl,
			thumbnailUrl: productData.thumbnailUrl,
			isMain: true,
			position: 0,
		} );
	}

	// Add gallery images
	if (
		productData.galleryImages &&
		Array.isArray( productData.galleryImages )
	) {
		productData.galleryImages.forEach( ( image, index ) => {
			// Skip if it's the same as main image
			if ( image.url !== productData.thumbnailUrl ) {
				images.push( {
					url: image.url,
					thumbnailUrl: image.thumbnailUrl || image.url,
					isMain: false,
					position: index + 1,
				} );
			}
		} );
	}

	return images;
}
