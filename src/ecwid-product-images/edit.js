/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import { PanelBody, ToggleControl, SelectControl, RangeControl, Notice } from '@wordpress/components';

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
	{ label: __('Small', 'ecwid-shopping-cart'), value: 'small' },
	{ label: __('Medium', 'ecwid-shopping-cart'), value: 'medium' },
	{ label: __('Large', 'ecwid-shopping-cart'), value: 'large' },
	{ label: __('Original', 'ecwid-shopping-cart'), value: 'original' },
];

function ProductImagesEdit(props) {
	const { attributes, setAttributes } = props;
	const { imageSize, showThumbnails, maxThumbnails } = attributes;

	const className = useMemo(
		() => computeClassName(attributes),
		[attributes]
	);

	const blockProps = useBlockProps({
		className,
		'data-wp-interactive': "peaches-ecwid-product-images",
	});

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Product Images Settings', 'ecwid-shopping-cart')}>
					<Notice status="info" isDismissible={false}>
						{__('This block displays product images dynamically based on the product detail block.', 'ecwid-shopping-cart')}
					</Notice>

					<SelectControl
						label={__('Main Image Size', 'ecwid-shopping-cart')}
						value={imageSize}
						options={IMAGE_SIZE_OPTIONS}
						onChange={(value) => setAttributes({ imageSize: value })}
					/>

					<ToggleControl
						label={__('Show Thumbnails', 'ecwid-shopping-cart')}
						checked={showThumbnails}
						onChange={(value) => setAttributes({ showThumbnails: value })}
					/>

					{showThumbnails && (
						<RangeControl
							label={__('Maximum Thumbnails', 'ecwid-shopping-cart')}
							value={maxThumbnails}
							onChange={(value) => setAttributes({ maxThumbnails: value })}
							min={1}
							max={10}
						/>
					)}
				</PanelBody>

				<BootstrapSettingsPanels
					setAttributes={setAttributes}
					attributes={attributes}
					supportedSettings={SUPPORTED_SETTINGS}
				/>
			</InspectorControls>

			<div {...blockProps}>
				<div className="product-images-container">
					<div className="main-image">
						<img
							src="https://placehold.co/600x400?text=Product+Image+Preview"
							className="img-fluid"
							alt={__('Product Image Preview', 'ecwid-shopping-cart')}
						/>
					</div>

					{showThumbnails && (
						<div className="thumbnails d-flex mt-2">
							{Array.from({ length: Math.min(maxThumbnails, 5) }).map((_, i) => (
								<div key={i} className="thumbnail-item me-2">
									<img
										src={`https://placehold.co/100x100?text=${i + 1}`}
										className="img-thumbnail"
										alt={__('Thumbnail', 'ecwid-shopping-cart')}
									/>
								</div>
							))}
						</div>
					)}
				</div>
			</div>
		</>
	);
}

export default ProductImagesEdit;
