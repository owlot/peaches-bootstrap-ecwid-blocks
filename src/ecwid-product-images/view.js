import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { getProductDataWithFallbackGenerator } from '../utils/ecwid-view-utils';

/**
 * Helper function to create responsive image with enhanced data
 * @param imageData
 * @param productId
 * @param position
 * @param context
 */
function createResponsiveImage(
	imageData,
	productId,
	position,
	context = 'gallery'
) {
	const img = document.createElement( 'img' );

	// Basic attributes
	img.src = imageData.src || imageData.url || imageData.image400pxUrl;
	img.alt =
		imageData.alt || productId
			? `Product ${ productId } Image`
			: 'Product Image';
	img.className = 'img-fluid peaches-responsive-img peaches-ecwid-img';
	img.loading = 'lazy';
	img.decoding = 'async';

	// Add responsive attributes if available
	if ( imageData.srcset ) {
		img.srcset = imageData.srcset;
	}
	if ( imageData.sizes ) {
		img.sizes = imageData.sizes;
	}

	// Add dimensions if available
	if ( imageData.width ) {
		img.width = imageData.width;
	}
	if ( imageData.height ) {
		img.height = imageData.height;
	}

	// Add data attributes
	img.setAttribute( 'data-responsive-type', 'ecwid' );
	img.setAttribute( 'data-image-position', position );
	img.setAttribute( 'data-product-id', productId );

	return img;
}

/**
 * Fetch enhanced responsive image data from REST API
 * @param productId
 * @param position
 * @param context
 */
async function fetchEnhancedImageData(
	productId,
	position,
	context = 'gallery'
) {
	try {
		const response = await fetch(
			'/wp-json/peaches/v1/responsive-image-data',
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					Accept: 'application/json',
				},
				credentials: 'same-origin',
				body: JSON.stringify( {
					product_id: productId,
					position,
					context,
				} ),
			}
		);

		if ( response.ok ) {
			const result = await response.json();
			if ( result.success && result.data ) {
				return result.data;
			}
		}

		console.warn( 'Enhanced image data not available, using fallback' );
		return null;
	} catch ( error ) {
		console.warn( 'Error fetching enhanced image data:', error );
		return null;
	}
}

// Store definition with proper lifecycle methods
const { state } = store( 'peaches-ecwid-product-images', {
	state: {
		productName: '',
		currentImage: '',
		galleryImages: [],
	},

	actions: {
		setCurrentImage( event ) {
			const imageUrl = event.target.getAttribute( 'data-imageUrl' );
			if ( imageUrl ) {
				state.currentImage = imageUrl;
				state.galleryImages.forEach( ( image ) => {
					image.isCurrent = image.imageUrl === imageUrl;
				} );
			}
		},
	},

	// The callbacks section for lifecycle methods
	callbacks: {
		// This will be automatically called when the component is mounted via data-wp-init
		*initProductImages() {
			const context = getContext();

			// Use consolidated utility to get product data and ID
			const productData = yield* getProductDataWithFallbackGenerator(
				context.selectedProductId
			);

			if ( ! productData ) {
				console.error( 'Product images - Product data not available' );
				return;
			}

			try {
				// Set the product data
				state.productName = productData.name;

				// Process images based on image size setting
				const element = document.querySelector(
					'[data-wp-interactive="peaches-ecwid-product-images"]'
				);

				// Size mapping to match the actual properties in the product media object
				const sizeMapping = {
					small: 'image400pxUrl',
					medium: 'image800pxUrl',
					large: 'image1500pxUrl',
					original: 'imageOriginalUrl',
				};

				const imageSizeKey =
					sizeMapping[ context.imageSize ] || 'image400pxUrl';

				// Handle media.images first (newer API structure)
				if (
					productData.media &&
					productData.media.images &&
					productData.media.images.length > 0
				) {
					// Set the main image using the correct URL property
					const mainImage = productData.media.images[ 0 ];
					state.currentImage =
						mainImage[ imageSizeKey ] || mainImage.image400pxUrl;

					// Set gallery images with the correct URL properties, respecting maxThumbnails
					state.galleryImages = productData.media.images
						.slice( 0, context.maxThumbnails + 1 )
						.map( ( image ) => ( {
							thumbnailUrl: image.image400pxUrl, // Use the smallest image for thumbnails
							imageUrl:
								image[ imageSizeKey ] || image.image400pxUrl,
							isCurrent:
								( image[ imageSizeKey ] ||
									image.image400pxUrl ) ===
								state.currentImage,
						} ) );
				}
				// Fall back to legacy galleryImages if available
				else if (
					productData.galleryImages &&
					productData.galleryImages.length > 0
				) {
					// Use main product image as current image
					state.currentImage =
						productData.thumbnailUrl || productData.imageUrl || '';

					// Process legacy gallery images
					const legacySizeMapping = {
						small: 'thumbnailUrl',
						medium: 'hdThumbnailUrl',
						large: 'imageUrl',
						original: 'originalImageUrl',
					};

					const legacyImageSizeKey =
						legacySizeMapping[ context.imageSize ] || 'imageUrl';

					// Add main image as first gallery item
					const mainGalleryItem = {
						thumbnailUrl:
							productData.thumbnailUrl ||
							productData.smallThumbnailUrl,
						imageUrl:
							productData[ legacyImageSizeKey ] ||
							productData.imageUrl,
						isCurrent: true,
					};

					// Add gallery images, respecting maxThumbnails (subtract 1 for main image)
					const galleryItems = productData.galleryImages
						.slice( 0, context.maxThumbnails - 1 )
						.map( ( image ) => ( {
							thumbnailUrl:
								image.thumbnailUrl || image.smallThumbnailUrl,
							imageUrl:
								image[ legacyImageSizeKey ] || image.imageUrl,
							isCurrent: false,
						} ) );

					state.galleryImages = [ mainGalleryItem, ...galleryItems ];
				}
				// Last resort, use the main product image only
				else if ( productData.thumbnailUrl || productData.imageUrl ) {
					state.currentImage =
						productData.thumbnailUrl || productData.imageUrl;
					state.galleryImages = [
						{
							thumbnailUrl:
								productData.thumbnailUrl ||
								productData.smallThumbnailUrl,
							imageUrl:
								productData.imageUrl ||
								productData.thumbnailUrl,
							isCurrent: true,
						},
					];
				} else {
					console.error( 'No images found for product' );
					state.currentImage = ''; // Set to empty or a placeholder image URL
					state.galleryImages = [];
				}

				// Debug output to verify the image URLs
				console.log(
					'Product Images - Current image set to:',
					state.currentImage
				);
				console.log(
					'Product Images - Gallery images (max:',
					context.maxThumbnails,
					'):',
					state.galleryImages
				);
			} catch ( error ) {
				console.error( 'Error processing product images:', error );
			}
		},
	},
} );
