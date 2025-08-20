/**
 * WordPress dependencies
 */
import {
	store,
	getContext,
	getElement,
	withScope,
} from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import {
	getProductDataWithFallbackGenerator,
	getProductIdWithFallback,
	buildApiUrl,
} from '../utils/ecwid-view-utils';

/**
 * Decode HTML entities in text content
 *
 * Safely converts HTML entities like &amp; to their proper characters.
 *
 * @since 0.3.1
 *
 * @param {string} text - Text content that may contain HTML entities
 *
 * @return {string} - Decoded text content
 */
function decodeHtmlEntities( text ) {
	if ( ! text || typeof text !== 'string' ) {
		return '';
	}

	try {
		const textarea = document.createElement( 'textarea' );
		textarea.innerHTML = text;
		return textarea.value;
	} catch ( error ) {
		console.warn( 'Error decoding HTML entities:', error );
		return text;
	}
}

/**
 * Find media item for a line by tag
 *
 * @since 0.3.2
 *
 * @param {Object} line     - Product line object
 * @param {string} mediaTag - Media tag to search for
 *
 * @return {Object|null} - Media item or null if not found
 */
function findLineMediaByTag( line, mediaTag ) {
	if ( ! line.media || ! Array.isArray( line.media ) || ! mediaTag ) {
		return null;
	}
	return line.media.find( ( item ) => item.tag === mediaTag ) || null;
}

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

/**
 * Ecwid Product Lines interactivity store
 *
 * @since 0.3.1 - Added product lines support
 */
store( 'peaches-ecwid-product-lines', {
	state: {
		/**
		 * Computed state: Get decoded line name
		 *
		 * Returns the line name with HTML entities properly decoded.
		 *
		 * @since 0.3.1
		 *
		 * @return {string} - decoded line name
		 */
		get decodedName() {
			const context = getContext();
			return decodeHtmlEntities( context.line.name || '' );
		},

		/**
		 * Computed state: Get decoded line description
		 *
		 * Returns the line description with HTML entities properly decoded,
		 * including the separator if descriptions are shown.
		 *
		 * @since 0.3.1
		 *
		 * @return {string} - decoded line description with separator
		 */
		get decodedDescription() {
			const context = getContext();
			if (
				! context.showLineDescriptions ||
				! context.line.description
			) {
				return '';
			}
			return (
				context.descriptionSeparator +
				decodeHtmlEntities( context.line.description )
			);
		},

		/**
		 * Computed state: Get complete decoded line content
		 *
		 * Returns the complete line content (name + description) with proper
		 * HTML entity decoding and separator handling for badges/spans.
		 *
		 * @since 0.3.1
		 *
		 * @return {string} - complete decoded line content
		 */
		get decodedLineContent() {
			const context = getContext();
			let text = decodeHtmlEntities( context.line.name || '' );

			if ( context.showLineDescriptions && context.line.description ) {
				text +=
					context.descriptionSeparator +
					decodeHtmlEntities( context.line.description );
			}

			return text;
		},

		/**
		 * Computed state: Get inline content for all lines
		 *
		 * Returns all filtered lines joined with the line separator for inline display.
		 *
		 * @since 0.3.1
		 *
		 * @return {string} - complete inline content for all lines
		 */
		get inlineContent() {
			const context = getContext();

			if (
				! context.productLines ||
				! Array.isArray( context.productLines )
			) {
				return '';
			}

			// Filter lines by type if specified
			let filteredLines = context.productLines;
			if ( context.lineType && context.fieldType === 'lines_filtered' ) {
				filteredLines = context.productLines.filter(
					( line ) => line.line_type === context.lineType
				);
			}

			// Apply max lines limit
			if ( context.maxLines > 0 ) {
				filteredLines = filteredLines.slice( 0, context.maxLines );
			}

			// Generate content for each line
			const lineContents = filteredLines.map( ( line ) => {
				let text = decodeHtmlEntities( line.name || '' );

				if ( context.showLineDescriptions && line.description ) {
					text +=
						context.descriptionSeparator +
						decodeHtmlEntities( line.description );
				}

				return text;
			} );

			return lineContents.join( context.lineSeparator || ', ' );
		},

		/**
		 * Computed state: Check if line has image for selected media tag
		 *
		 * @since 0.3.2
		 *
		 * @return {boolean} - true if image exists
		 */
		get hasImage() {
			const context = getContext();

			if ( ! context.imageMediaTag ) {
				return false;
			}

			const mediaItem = findLineMediaByTag(
				context.line,
				context.imageMediaTag
			);
			return !! ( mediaItem && mediaItem.attachment_id );
		},

		/**
		 * Computed state: Get image URL for current line
		 *
		 * @since 0.3.2
		 *
		 * @return {string|null} - image URL or null
		 */
		get lineImageUrl() {
			const context = getContext();

			if ( ! context.imageMediaTag ) {
				return null;
			}

			const mediaItem = findLineMediaByTag(
				context.line,
				context.imageMediaTag
			);

			if ( ! mediaItem || ! mediaItem.attachment_id ) {
				return null;
			}

			return mediaItem.thumbnail_url || null;
		},

		/**
		 * Computed state: Get image alt text for current line
		 *
		 * @since 0.3.2
		 *
		 * @return {string} - Alt text for the image
		 */
		get lineImageAlt() {
			const context = getContext();

			if ( ! context.imageMediaTag ) {
				return '';
			}

			const mediaItem = findLineMediaByTag(
				context.line,
				context.imageMediaTag
			);

			if ( ! mediaItem ) {
				return '';
			}

			return mediaItem.alt || context.line.name || 'Product line image';
		},
	},
	callbacks: {
		/**
		 * Initialize product lines display
		 *
		 * Gets product lines for product found in global state or fetches selected product data.
		 */
		*initProductField() {
			const context = getContext();

			// Use consolidated utility to get product ID
			const productId = getProductIdWithFallback(
				context.selectedProductId
			);

			context.isLoading = true;

			try {
				const apiUrl = buildApiUrl( 'product-lines', productId );

				// Fetch ingredients from WordPress API with language support
				const response = yield fetch( apiUrl, {
					headers: {
						Accept: 'application/json',
					},
					credentials: 'same-origin',
				} );

				if ( response.status === 404 ) {
					context.productLines = [];
					context.isLoading = false;
					yield* actions.renderProductLines();
					return;
				}

				if ( ! response.ok ) {
					throw new Error(
						`HTTP error! status: ${ response.status }`
					);
				}

				const responseData = yield response.json();

				if (
					responseData &&
					responseData.data &&
					Array.isArray( responseData.data )
				) {
					// Fetch media ONLY if imageMediaTag is set (images are enabled)
					if ( context.imageMediaTag ) {
						const linesWithMedia = yield Promise.all(
							responseData.data.map( async ( line ) => {
								try {
									const mediaResponse = await fetch(
										`/wp-json/peaches/v1/product-lines/${ line.id }/media`,
										{
											headers: {
												Accept: 'application/json',
											},
											credentials: 'same-origin',
										}
									);

									if ( mediaResponse.ok ) {
										const mediaData =
											await mediaResponse.json();
										line.media = mediaData.success
											? mediaData.data
											: [];
									} else {
										line.media = [];
									}
								} catch ( error ) {
									console.error(
										`Error fetching media for line ${ line.id }:`,
										error
									);
									line.media = [];
								}
								return line;
							} )
						);

						// Apply your existing filtering and processing logic to linesWithMedia
						context.productLines = linesWithMedia; // or your filtered result
					} else {
						// No images needed, use basic data (your existing logic)
						context.productLines = responseData.data; // or your filtered result
					}

					// Filter lines by type if specified
					let filteredLines = context.productLines;
					if (
						context.fieldType === 'lines_filtered' &&
						context.lineType
					) {
						filteredLines = responseData.data.filter(
							( line ) => line.line_type === context.lineType
						);
					}

					// Apply max lines limit
					if ( context.maxLines > 0 ) {
						filteredLines = filteredLines.slice(
							0,
							context.maxLines
						);
					}

					// Check if we need to consolidate the output
					if ( context.displayMode === 'inline' ) {
						const separator = context.lineSeparator || ', '; // Use custom separator or default
						const lines = filteredLines
							.map( ( line ) => {
								let text = line.name;
								if (
									context.showLineDescriptions &&
									line.description
								) {
									text =
										text +
										context.descriptionSeparator +
										line.description;
								}
								return text;
							} )
							.join( context.lineSeparator );

						filteredLines = [
							{
								id: 0,
								name: lines,
								media: filteredLines[ 0 ]?.media,
							},
						];
					}

					context.productLines = filteredLines;
				} else {
					context.productLines = [];
				}

				context.isLoading = false;
			} catch ( error ) {
				console.error( 'Error fetching product lines:', error );
				context.productLines = [];
				context.isLoading = false;
			}
		},
	},
} );
