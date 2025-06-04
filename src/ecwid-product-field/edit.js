/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import {
	PanelBody,
	SelectControl,
	TextControl,
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

const FIELD_TYPES = [
	{ label: __( 'Product Title', 'ecwid-shopping-cart' ), value: 'title' },
	{
		label: __( 'Product Subtitle', 'ecwid-shopping-cart' ),
		value: 'subtitle',
	},
	{ label: __( 'Product Price', 'ecwid-shopping-cart' ), value: 'price' },
	{ label: __( 'Stock Status', 'ecwid-shopping-cart' ), value: 'stock' },
	{
		label: __( 'Product Description', 'ecwid-shopping-cart' ),
		value: 'description',
	},
	{ label: __( 'Custom Field', 'ecwid-shopping-cart' ), value: 'custom' },
];

const HTML_TAGS = [
	{ label: __( 'Paragraph (p)', 'ecwid-shopping-cart' ), value: 'p' },
	{ label: __( 'Heading 1 (h1)', 'ecwid-shopping-cart' ), value: 'h1' },
	{ label: __( 'Heading 2 (h2)', 'ecwid-shopping-cart' ), value: 'h2' },
	{ label: __( 'Heading 3 (h3)', 'ecwid-shopping-cart' ), value: 'h3' },
	{ label: __( 'Heading 4 (h4)', 'ecwid-shopping-cart' ), value: 'h4' },
	{ label: __( 'Heading 5 (h5)', 'ecwid-shopping-cart' ), value: 'h5' },
	{ label: __( 'Heading 6 (h6)', 'ecwid-shopping-cart' ), value: 'h6' },
	{ label: __( 'Span', 'ecwid-shopping-cart' ), value: 'span' },
];

/**
 * Product Field Edit Component
 *
 * Displays a product field with test data when available from parent context.
 *
 * @param {Object} props - Component props
 *
 * @return {JSX.Element} - Edit component
 */
function ProductFieldEdit( props ) {
	const { attributes, setAttributes, context } = props;
	const { fieldType, htmlTag, customFieldKey } = attributes;

	// Get test product data from parent context
	const testProductData = context?.[ 'peaches/testProductData' ];

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches-ecwid-product-field',
	} );

	/**
	 * Get preview text based on field type and available test data
	 *
	 * @return {string} - Preview text to display
	 */
	const getPreviewText = () => {
		if ( testProductData ) {
			try {
				switch ( fieldType ) {
					case 'title':
						return testProductData.name || '';

					case 'subtitle':
						if ( testProductData.attributes ) {
							const subtitleAttr =
								testProductData.attributes.find(
									( attr ) =>
										attr.name === 'Ondertitel' ||
										attr.name === 'Subtitle'
								);
							return (
								subtitleAttr?.valueTranslated?.nl ||
								subtitleAttr?.value ||
								''
							);
						}
						return '';

					case 'price':
						if ( testProductData.price ) {
							// Check if there's a sale price
							if (
								testProductData.compareToPrice &&
								testProductData.compareToPrice >
									testProductData.price
							) {
								return `€ ${ testProductData.compareToPrice.toFixed(
									2
								) } €ht ${ testProductData.price.toFixed(
									2
								) }`;
							}
							return `€ ${ testProductData.price.toFixed( 2 ) }`;
						}
						return '';

					case 'stock':
						return testProductData.inStock
							? __( 'In Stock', 'ecwid-shopping-cart' )
							: __( 'Out of Stock', 'ecwid-shopping-cart' );

					case 'description':
						// Return HTML content for description, but we'll handle it specially
						return testProductData.description || '';

					case 'custom':
						if ( customFieldKey && testProductData.attributes ) {
							const customField = testProductData.attributes.find(
								( attr ) => attr.name === customFieldKey
							);
							return (
								customField?.valueTranslated?.nl ||
								customField?.value ||
								''
							);
						}
						return customFieldKey
							? __(
									'Custom field not found in test product',
									'ecwid-shopping-cart'
							  )
							: __(
									'Select a custom field',
									'ecwid-shopping-cart'
							  );

					default:
						return '';
				}
			} catch ( error ) {
				return __(
					'Error loading test product field',
					'ecwid-shopping-cart'
				);
			}
		}

		// Fallback to placeholder text when no test product is available
		switch ( fieldType ) {
			case 'title':
				return __( 'Sample Product Title', 'ecwid-shopping-cart' );
			case 'subtitle':
				return __( 'Sample Product Subtitle', 'ecwid-shopping-cart' );
			case 'price':
				return '€ 29.99';
			case 'stock':
				return __( 'In Stock', 'ecwid-shopping-cart' );
			case 'description':
				return __(
					'This is a sample product description…',
					'ecwid-shopping-cart'
				);
			case 'custom':
				return customFieldKey
					? __( 'Custom Field:', 'ecwid-shopping-cart' ) +
							' ' +
							customFieldKey
					: __( 'Select a custom field', 'ecwid-shopping-cart' );
			default:
				return '';
		}
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Product Field Settings',
						'ecwid-shopping-cart'
					) }
				>
					{ testProductData ? (
						<Notice status="success" isDismissible={ false }>
							{ __(
								'Using test product data:',
								'ecwid-shopping-cart'
							) }{ ' ' }
							<strong>{ testProductData.name }</strong>
						</Notice>
					) : (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'Using placeholder data. Configure a test product in the parent block to preview real data.',
								'ecwid-shopping-cart'
							) }
						</Notice>
					) }

					<SelectControl
						label={ __( 'Field Type', 'ecwid-shopping-cart' ) }
						value={ fieldType }
						options={ FIELD_TYPES }
						onChange={ ( value ) =>
							setAttributes( { fieldType: value } )
						}
					/>

					{ fieldType === 'custom' && (
						<TextControl
							label={ __(
								'Custom Field Key',
								'ecwid-shopping-cart'
							) }
							value={ customFieldKey }
							onChange={ ( value ) =>
								setAttributes( { customFieldKey: value } )
							}
							help={ __(
								'Enter the key name of the Ecwid custom field',
								'ecwid-shopping-cart'
							) }
						/>
					) }

					<SelectControl
						label={ __( 'HTML Tag', 'ecwid-shopping-cart' ) }
						value={ htmlTag }
						options={ HTML_TAGS }
						onChange={ ( value ) =>
							setAttributes( { htmlTag: value } )
						}
					/>
				</PanelBody>

				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ fieldType === 'description' && testProductData?.description
					? React.createElement( htmlTag, {
							dangerouslySetInnerHTML: {
								__html: testProductData.description,
							},
					  } )
					: React.createElement( htmlTag, {}, getPreviewText() ) }
			</div>
		</>
	);
}

export default ProductFieldEdit;
