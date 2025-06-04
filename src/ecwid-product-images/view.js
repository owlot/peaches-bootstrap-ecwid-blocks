import { store } from '@wordpress/interactivity';

// Access the parent product detail store
const productDetailStore = store( 'peaches-ecwid-product-detail' );

// Store definition with proper lifecycle methods
const { state } = store( 'peaches-ecwid-product-images', {
	state: {
		get productId() {
			return productDetailStore.state.productId;
		},
		get productData() {
			return productDetailStore.state.productData;
		},
		productName: '',
		currentImage: '',
		galleryImages: [],

		processProductData() {
			try {
				const product = state.productData;

				if ( ! product ) {
					console.error( 'Product data not found' );
					return;
				}

				// Set the product data
				state.productName = product.name;

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
					product.media &&
					product.media.images &&
					product.media.images.length > 0
				) {
					// Set the main image using the correct URL property
					const mainImage = product.media.images[ 0 ];
					state.currentImage =
						mainImage[ imageSizeKey ] || mainImage.image400pxUrl;

					// Set gallery images with the correct URL properties, respecting maxThumbnails
					state.galleryImages = product.media.images
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
					product.galleryImages &&
					product.galleryImages.length > 0
				) {
					// Use main product image as current image
					state.currentImage =
						product.thumbnailUrl || product.imageUrl || '';

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
							product.thumbnailUrl || product.smallThumbnailUrl,
						imageUrl:
							product[ legacyImageSizeKey ] || product.imageUrl,
						isCurrent: true,
					};

					// Add gallery images, respecting maxThumbnails (subtract 1 for main image)
					const galleryItems = product.galleryImages
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
				else if ( product.thumbnailUrl || product.imageUrl ) {
					state.currentImage =
						product.thumbnailUrl || product.imageUrl;
					state.galleryImages = [
						{
							thumbnailUrl:
								product.thumbnailUrl ||
								product.smallThumbnailUrl,
							imageUrl: product.imageUrl || product.thumbnailUrl,
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
				console.error( 'Error processing product images data:', error );
			}
		},
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
			if ( ! state.productId || ! state.productData ) {
				console.error( 'Product images - Product data not available' );
				return;
			}

			try {
				// Process product data that's already available from parent block
				state.processProductData();
			} catch ( error ) {
				console.error( 'Error processing product images:', error );
			}
		},
	},
} );
