import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { getProductDataWithFallbackGenerator } from '../utils/ecwid-view-utils';

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
				const imageSize =
					element?.getAttribute( 'data-image-size' ) || 'medium';

				// Get maxThumbnails setting from element attributes or fallback to 5
				const maxThumbnails =
					parseInt(
						element?.getAttribute( 'data-max-thumbnails' )
					) || 5;

				// Size mapping to match the actual properties in the product media object
				const sizeMapping = {
					small: 'image160pxUrl',
					medium: 'image400pxUrl',
					large: 'image800pxUrl',
					original: 'imageOriginalUrl',
				};

				const imageSizeKey =
					sizeMapping[ imageSize ] || 'image400pxUrl';

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
						.slice( 0, maxThumbnails + 1 )
						.map( ( image ) => ( {
							thumbnailUrl: image.image160pxUrl, // Use the smallest image for thumbnails
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
						small: 'smallThumbnailUrl',
						medium: 'imageUrl',
						large: 'hdThumbnailUrl',
						original: 'originalImageUrl',
					};

					const legacyImageSizeKey =
						legacySizeMapping[ imageSize ] || 'imageUrl';

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
						.slice( 0, maxThumbnails - 1 )
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
					maxThumbnails,
					'):',
					state.galleryImages
				);
			} catch ( error ) {
				console.error( 'Error processing product images:', error );
			}
		},
	},
} );
