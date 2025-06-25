/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	BootstrapSettingsPanels,
	computeClassName,
} from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';
import {
	useEcwidProductData,
	ProductSelectionPanel,
	getProductFieldValue,
} from '../utils/ecwid-product-utils';

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
 * Displays a product field with unified product data handling.
 *
 * @param {Object} props - Component props
 *
 * @return {JSX.Element} - Edit component
 */
function ProductFieldEdit( props ) {
	const { attributes, setAttributes, context, clientId } = props;
	const { fieldType, htmlTag, customFieldKey } = attributes;

	// Use unified product data hook
	const {
		productData,
		isLoading,
		error,
		hasProductDetailAncestor,
		selectedProductId,
		contextProductData,
		openEcwidProductPopup,
		clearSelectedProduct,
	} = useEcwidProductData( context, attributes, setAttributes, clientId );

	// Compute class name with Bootstrap utilities
	const computedClassName = useMemo( () => {
		return computeClassName( attributes );
	}, [ attributes ] );

	const blockProps = useBlockProps( {
		className: computedClassName,
	} );

	/**
	 * Get preview text for the field
	 *
	 * @return {string} - Preview text
	 */
	const getPreviewText = () => {
		return getProductFieldValue( productData, fieldType, customFieldKey );
	};

	return (
		<>
			<InspectorControls>
				<ProductSelectionPanel
					productData={ productData }
					isLoading={ isLoading }
					error={ error }
					hasProductDetailAncestor={ hasProductDetailAncestor }
					selectedProductId={ selectedProductId }
					contextProductData={ contextProductData }
					openEcwidProductPopup={ openEcwidProductPopup }
					clearSelectedProduct={ clearSelectedProduct }
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>

				<PanelBody
					title={ __(
						'Product Field Settings',
						'ecwid-shopping-cart'
					) }
				>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Field Type', 'ecwid-shopping-cart' ) }
						value={ fieldType }
						options={ FIELD_TYPES }
						onChange={ ( value ) =>
							setAttributes( { fieldType: value } )
						}
					/>

					{ fieldType === 'custom' && (
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
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
						__nextHasNoMarginBottom
						__next40pxDefaultSize
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

			{ isLoading && (
				<div className="text-center p-2">
					<div
						className="spinner-border spinner-border-sm"
						role="status"
					>
						<span className="visually-hidden">
							{ __(
								'Loading product data…',
								'ecwid-shopping-cart'
							) }
						</span>
					</div>
				</div>
			) }

			{ ! isLoading && (
				<div { ...blockProps }>
					{ fieldType === 'description' && productData?.description
						? React.createElement( htmlTag, {
								dangerouslySetInnerHTML: {
									__html: productData.description,
								},
						  } )
						: React.createElement( htmlTag, {}, getPreviewText() ) }
				</div>
			) }
		</>
	);
}

export default ProductFieldEdit;
