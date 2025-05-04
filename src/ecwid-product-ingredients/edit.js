/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import { PanelBody, ToggleControl, TextControl, Notice } from '@wordpress/components';

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

function ProductIngredientsEdit(props) {
	const { attributes, setAttributes } = props;
	const { showTitle, accordionTitle, startOpened } = attributes;

	const className = useMemo(
		() => computeClassName(attributes),
		[attributes]
	);

	const blockProps = useBlockProps({
		className,
		'data-wp-interactive': "peaches-ecwid-product-ingredients",
	});

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Product Ingredients Settings', 'ecwid-shopping-cart')}>
					<Notice status="info" isDismissible={false}>
						{__('This block displays product ingredients dynamically based on the product detail block.', 'ecwid-shopping-cart')}
					</Notice>

					<ToggleControl
						label={__('Start Opened', 'ecwid-shopping-cart')}
						checked={startOpened}
						onChange={(value) => setAttributes({ startOpened: value })}
					/>
				</PanelBody>

				<BootstrapSettingsPanels
					setAttributes={setAttributes}
					attributes={attributes}
					supportedSettings={SUPPORTED_SETTINGS}
				/>
			</InspectorControls>

			<div {...blockProps}>
				<div className="product-ingredients-preview">
					{showTitle && (
						<h3 className="mb-3">{accordionTitle}</h3>
					)}

					<div className="accordion" id="ingredientsPreview">
						<div className="accordion-item">
							<div className="accordion-header">
								<button
									className={ `accordion-button ${
										! startOpened ? 'collapsed' : ''
									}` }
									type="button"
								>
									{ __(
										'Prickly Pear Seed Oil',
										'ecwid-shopping-cart'
									) }
								</button>
							</div>
							<div
								className={ `accordion-collapse collapse ${
									startOpened ? 'show' : ''
								}` }
							>
								<div className="accordion-body">
									{__('Rich in antioxidants and fatty acids.', 'ecwid-shopping-cart')}
								</div>
							</div>
						</div>

						<div className="accordion-item">
							<div className="accordion-header">
								<button
									className="accordion-button collapsed"
									type="button"
								>
									{ __(
										'Coriander Seed Oil',
										'ecwid-shopping-cart'
									) }
								</button>
							</div>
							<div className="accordion-collapse collapse">
								<div className="accordion-body">
									{__('Known for its hydrating properties.', 'ecwid-shopping-cart')}
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</>
	);
}

export default ProductIngredientsEdit;
