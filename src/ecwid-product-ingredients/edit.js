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

function ProductIngredientsEdit(props) {
	const { attributes, setAttributes } = props;
	const { imageSize, showThumbnails, maxThumbnails } = attributes;

	const className = useMemo(
		() => computeClassName(attributes),
		[attributes]
	);

	const blockProps = useBlockProps({
		className,
		'data-wp-interactive': "peaches-ecwid-product-ingredients",
	});

	// Sample placeholder ingredients
	const placeholderIngredients = [
		{
			name: 'Prickly Pear Seed Oil',
			description: 'Rich in vitamin E and antioxidants, prickly pear seed oil helps protect the skin against environmental stressors while promoting hydration.'
		},
		{
			name: 'Coriander Seed Oil',
			description: 'Known for its soothing properties, coriander seed oil helps calm and balance the skin while providing gentle hydration.'
		},
		{
			name: 'Plum Seed Oil',
			description: 'A lightweight, non-greasy oil that deeply nourishes the skin and hair. Rich in essential fatty acids and antioxidants.'
		},
		{
			name: 'Sacha Inchi Oil',
			description: 'High in omega-3 fatty acids, this oil provides deep nourishment for damaged hair, helping to restore its natural shine and strength.'
		}
	];
	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Product Images Settings', 'ecwid-shopping-cart')}>
					<Notice status="info" isDismissible={false}>
						{__('This block displays product ingredients dynamically based on the product detail block.', 'ecwid-shopping-cart')}
					</Notice>
				</PanelBody>

				<BootstrapSettingsPanels
					setAttributes={setAttributes}
					attributes={attributes}
					supportedSettings={SUPPORTED_SETTINGS}
				/>
			</InspectorControls>

			<div {...blockProps}>
				<div className="ingredients-accordion">
					{placeholderIngredients.map((ingredient, index) => (
                        <div className="ingredient-item" key={index}>
                            <div className="ingredient-header">
                                <span className="ingredient-name">{ingredient.name}</span>
                                <span className="toggle-icon">+</span>
                            </div>
                            {index === 0 && (
                                <div className="ingredient-content" style={{ maxHeight: 'none', opacity: 1, padding: '0 1rem 1.25rem 0' }}>
                                    <div className="ingredient-description">
                                        {ingredient.description}
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
				<div className="components-notice is-info" style={{ marginTop: '20px' }}>
                    <div className="components-notice__content">
                        {__('This block displays product ingredients from the custom post type. Add ingredients in the Product Ingredients section of the admin.', 'ecwid-shopping-cart')}
                    </div>
                </div>
			</div>
		</>
	);
}

export default ProductIngredientsEdit;
