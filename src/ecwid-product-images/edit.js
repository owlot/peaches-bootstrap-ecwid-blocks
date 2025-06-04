/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	RangeControl,
	Notice,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	BootstrapSettingsPanels,
	computeClassName,
} from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

const SUPPORTED_SETTINGS = {
	responsive: {
		spacings: {
			margin: true,
			padding: true,
		},
	},
};

const IMAGE_SIZE_OPTIONS = [
	{ label: __( 'Small', 'ecwid-shopping-cart' ), value: 'small' },
	{ label: __( 'Medium', 'ecwid-shopping-cart' ), value: 'medium' },
	{ label: __( 'Large', 'ecwid-shopping-cart' ), value: 'large' },
	{ label: __( 'Original', 'ecwid-shopping-cart' ), value: 'original' },
];

function ProductImagesEdit( props ) {
	const { attributes, setAttributes, context } = props;
	const { imageSize, showThumbnails, maxThumbnails } = attributes;

	// Get test product data from parent context
	const testProductData = context?.[ 'peaches/testProductData' ];

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches-ecwid-product-images',
	} );

	/**
	 * Get preview images from test product data
	 *
	 * @return {Object} Object with mainImage and thumbnails
	 */
	const getPreviewImages = () => {
		if ( ! testProductData ) {
			return {
				mainImage:
					'https://placehold.co/600x400?text=Product+Image+Preview',
				thumbnails: Array.from( {
					length: Math.min( maxThumbnails, 5 ),
				} ).map(
					( _, i ) => `https://placehold.co/100x100?text=${ i + 1 }`
				),
			};
		}

		// Size mapping for different image sizes
		const sizeMapping = {
			small: 'image160pxUrl',
			medium: 'image400pxUrl',
			large: 'image800pxUrl',
			original: 'imageOriginalUrl',
		};

		const imageSizeKey = sizeMapping[ imageSize ] || 'image400pxUrl';

		let mainImage = '';
		let thumbnails = [];

		// Try new media.images structure first
		if (
			testProductData.media &&
			testProductData.media.images &&
			testProductData.media.images.length > 0
		) {
			// Get main image
			const mainImageData = testProductData.media.images[ 0 ];
			mainImage =
				mainImageData[ imageSizeKey ] || mainImageData.image400pxUrl;

			// Get thumbnails (skip the first image since it's used as main image)
			if ( showThumbnails && testProductData.media.images.length > 1 ) {
				thumbnails = testProductData.media.images
					.slice( 1, maxThumbnails + 1 ) // Skip first image, get up to maxThumbnails
					.map( ( image ) => image.image160pxUrl );
			}
		}
		// Fallback to legacy galleryImages + main image
		else {
			// Use main product image
			mainImage =
				testProductData.thumbnailUrl || testProductData.imageUrl || '';

			// Get gallery thumbnails (these are separate from main image)
			if ( showThumbnails && testProductData.galleryImages ) {
				thumbnails = testProductData.galleryImages
					.slice( 0, maxThumbnails )
					.map(
						( image ) =>
							image.thumbnailUrl || image.smallThumbnailUrl
					);
			}
		}

		return {
			mainImage:
				mainImage || 'https://placehold.co/600x400?text=No+Image',
			thumbnails: thumbnails.length > 0 ? thumbnails : [],
		};
	};

	const { mainImage, thumbnails } = getPreviewImages();

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Product Images Settings',
						'ecwid-shopping-cart'
					) }
				>
					{ testProductData ? (
						<Notice status="success" isDismissible={ false }>
							{ __(
								'Using test product images:',
								'ecwid-shopping-cart'
							) }{ ' ' }
							<strong>{ testProductData.name }</strong>
						</Notice>
					) : (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'Using placeholder images. Configure a test product in the parent block to preview real images.',
								'ecwid-shopping-cart'
							) }
						</Notice>
					) }

					<SelectControl
						label={ __( 'Main Image Size', 'ecwid-shopping-cart' ) }
						value={ imageSize }
						options={ IMAGE_SIZE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { imageSize: value } )
						}
					/>

					<ToggleControl
						label={ __( 'Show Thumbnails', 'ecwid-shopping-cart' ) }
						checked={ showThumbnails }
						onChange={ ( value ) =>
							setAttributes( { showThumbnails: value } )
						}
					/>

					{ showThumbnails && (
						<RangeControl
							label={ __(
								'Maximum Thumbnails',
								'ecwid-shopping-cart'
							) }
							value={ maxThumbnails }
							onChange={ ( value ) =>
								setAttributes( { maxThumbnails: value } )
							}
							min={ 1 }
							max={ 10 }
						/>
					) }
				</PanelBody>

				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="product-images-container">
					<div className="main-image ratio ratio-1x1">
						<img
							className="img-fluid"
							src={ mainImage }
							alt={
								testProductData?.name ||
								__(
									'Product Image Preview',
									'ecwid-shopping-cart'
								)
							}
						/>
					</div>
					{ showThumbnails && thumbnails.length > 0 && (
						<div className="thumbnails d-flex">
							{ thumbnails.map( ( thumbnail, i ) => (
								<div key={ i } className="ratio ratio-1x1">
									<img
										src={ thumbnail }
										className="img-fluid"
										alt={ `${
											testProductData?.name || 'Product'
										} thumbnail ${ i + 1 }` }
									/>
								</div>
							) ) }
						</div>
					) }
				</div>
			</div>
		</>
	);
}

export default ProductImagesEdit;
