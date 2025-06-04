/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { computeClassName } from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

export default function save( { attributes } ) {
	const { showThumbnails, imageSize, maxThumbnails } = attributes;

	const blockProps = useBlockProps.save( {
		className: computeClassName( attributes ),
		'data-wp-interactive': 'peaches-ecwid-product-images',
		'data-wp-context': `{ "currentImageIndex": 0 }`,
		'data-wp-bind--data-image-size': 'context.imageSize',
		'data-wp-init': 'callbacks.initProductImages',
		'data-image-size': imageSize,
		'data-max-thumbnails': maxThumbnails,
	} );

	return (
		<div { ...blockProps }>
			<div className="product-images-container">
				<div className="main-image ratio ratio-1x1">
					<img
						className="img-fluid"
						data-wp-bind--src="state.currentImage"
						data-wp-bind--alt="state.productName"
						alt={ __( 'Product Image', 'ecwid-shopping-cart' ) }
					/>
				</div>

				{ showThumbnails && (
					<div
						className="thumbnails d-flex"
						data-wp-bind--hidden="!(state.galleryImages?.length > 1)"
					>
						<template data-wp-each--image="state.galleryImages">
							<div
								data-wp-class--d-none="context.image.isCurrent"
								className="ratio ratio-1x1"
							>
								<img
									className="img-fluid"
									data-wp-bind--src="context.image.thumbnailUrl"
									data-wp-bind--alt="state.productName"
									data-wp-bind--data-imageUrl="context.image.imageUrl"
									data-wp-on--click="actions.setCurrentImage"
								/>
							</div>
						</template>
					</div>
				) }
			</div>
		</div>
	);
}
