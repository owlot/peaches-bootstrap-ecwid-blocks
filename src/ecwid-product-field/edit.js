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

function ProductFieldEdit( props ) {
	const { attributes, setAttributes } = props;
	const { fieldType, htmlTag, customFieldKey } = attributes;

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches-ecwid-product-field',
	} );

	const getPreviewText = () => {
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
					'This is a sample product description...',
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
					<Notice status="info" isDismissible={ false }>
						{ __(
							'This block displays a product field dynamically based on the product detail block.',
							'ecwid-shopping-cart'
						) }
					</Notice>

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
				{ React.createElement( htmlTag, {}, getPreviewText() ) }
			</div>
		</>
	);
}

export default ProductFieldEdit;
