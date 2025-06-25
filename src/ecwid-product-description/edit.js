/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useMemo, useState, useEffect } from '@wordpress/element';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
	Notice,
	Spinner,
} from '@wordpress/components';
import { sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	BootstrapSettingsPanels,
	computeClassName,
} from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';
import {
	useEcwidProductData,
	getCurrentLanguageForAPI,
	ProductSelectionPanel,
} from '../utils/ecwid-product-utils';

const SUPPORTED_SETTINGS = {
	responsive: {
		spacings: {
			margin: true,
			padding: true,
		},
	},
};

// Static description types - RESTORED FROM ORIGINAL
const descriptionTypes = [
	{ label: __( 'Product Usage', 'peaches' ), value: 'usage' },
	{ label: __( 'Detailed Ingredients', 'peaches' ), value: 'ingredients' },
	{ label: __( 'Care Instructions', 'peaches' ), value: 'care' },
	{ label: __( 'Warranty Information', 'peaches' ), value: 'warranty' },
	{ label: __( 'Key Features', 'peaches' ), value: 'features' },
	{ label: __( 'Technical Specifications', 'peaches' ), value: 'technical' },
	{ label: __( 'Custom Description', 'peaches' ), value: 'custom' },
];

/**
 * Product Description Edit Component
 *
 * Enhanced with product selection capability while maintaining original logic.
 *
 * @param {Object} props - Component props
 *
 * @return {JSX.Element} - Edit component
 */
function ProductDescriptionEdit( props ) {
	const { attributes, setAttributes, context, clientId } = props;
	const { descriptionType, displayTitle, customTitle } = attributes;

	const [ descriptionData, setDescriptionData ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	// Use unified product data hook for product selection
	const {
		productData,
		isLoading: productLoading,
		error: productError,
		hasProductDetailAncestor,
		selectedProductId,
		contextProductData,
		openEcwidProductPopup,
		clearSelectedProduct,
	} = useEcwidProductData( context, attributes, setAttributes, clientId );

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	/**
	 * Get current language for multilingual API requests
	 *
	 * Uses the existing utility function from ecwid-view-utils to ensure
	 * consistent language detection across all blocks.
	 *
	 * @since 0.3.1
	 *
	 * @return {string} Two-letter language code (e.g., 'en', 'nl', 'fr')
	 */
	const currentLanguage = useMemo( () => {
		return getCurrentLanguageForAPI();
	}, [] );

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches-ecwid-product-description',
	} );

	/**
	 * Fetch description data when test product data or description type changes
	 */
	useEffect( () => {
		if ( productData?.id && descriptionType ) {
			setIsLoading( true );
			setError( null );

			// Build API URL with language parameter for multilingual sites
			const apiUrl = `/wp-json/peaches/v1/product-descriptions/${
				productData.id
			}/type/${ descriptionType }?lang=${ encodeURIComponent(
				currentLanguage
			) }`;

			fetch( apiUrl, {
				headers: {
					Accept: 'application/json',
				},
				credentials: 'same-origin',
			} )
				.then( ( response ) => {
					if ( response.status === 404 ) {
						// Description not found for this type - this is not an error
						setDescriptionData( null );
						setIsLoading( false );
						return;
					}

					if ( ! response.ok ) {
						throw new Error(
							`HTTP error! status: ${ response.status }`
						);
					}
					return response.json();
				} )
				.then( ( data ) => {
					if ( data && data.success && data.description ) {
						setDescriptionData( data.description );
					} else {
						setDescriptionData( null );
					}
					setIsLoading( false );
				} )
				.catch( ( err ) => {
					console.error( 'Error fetching description data:', err );
					setError( err.message );
					setDescriptionData( null );
					setIsLoading( false );
				} );
		} else {
			setDescriptionData( null );
		}
	}, [ productData?.id, descriptionType ] );

	/**
	 * Get display title for the description - ORIGINAL LOGIC
	 */
	const getDisplayTitle = () => {
		if ( customTitle ) {
			return customTitle;
		}

		if ( descriptionData?.title ) {
			return descriptionData.title;
		}

		const typeOption = descriptionTypes.find(
			( t ) => t.value === descriptionType
		);
		return typeOption?.label || __( 'Product Description', 'peaches' );
	};

	/**
	 * Render preview content - ENHANCED WITH UNIFIED PRODUCT DATA
	 */
	const renderPreviewContent = () => {
		if ( productLoading || isLoading ) {
			return (
				<div className="d-flex justify-content-center align-items-center p-4">
					<Spinner />
					<span className="ms-2">
						{ __( 'Loading description…', 'peaches' ) }
					</span>
				</div>
			);
		}

		if ( productError || error ) {
			return (
				<Notice status="error" isDismissible={ false }>
					{ __( 'Error loading description:', 'peaches' ) }{ ' ' }
					{ productError || error }
				</Notice>
			);
		}

		if ( ! productData?.id ) {
			return (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'This block will display product descriptions when a product is selected or when used inside a product detail template.',
						'peaches'
					) }
				</Notice>
			);
		}

		if ( ! descriptionData ) {
			return (
				<Notice status="warning" isDismissible={ false }>
					{ sprintf(
						__(
							'No "%s" description found for this product.',
							'peaches'
						),
						descriptionTypes.find(
							( t ) => t.value === descriptionType
						)?.label || descriptionType
					) }
				</Notice>
			);
		}

		const title = getDisplayTitle();
		const content = descriptionData.content || '';

		return (
			<div className="product-description">
				{ displayTitle && title && (
					<h3 className="product-description-title">{ title }</h3>
				) }
				<div
					className="product-description-content"
					dangerouslySetInnerHTML={ { __html: content } }
				/>
			</div>
		);
	};

	return (
		<>
			<InspectorControls>
				<ProductSelectionPanel
					productData={ productData }
					isLoading={ productLoading }
					error={ productError }
					hasProductDetailAncestor={ hasProductDetailAncestor }
					selectedProductId={ selectedProductId }
					contextProductData={ contextProductData }
					openEcwidProductPopup={ openEcwidProductPopup }
					clearSelectedProduct={ clearSelectedProduct }
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>

				<PanelBody
					title={ __( 'Description Settings', 'peaches' ) }
					initialOpen={ true }
				>
					{ productData ? (
						<Notice
							className="mb-2"
							status="success"
							isDismissible={ false }
						>
							{ __( 'Using test product data:', 'peaches' ) }{ ' ' }
							<strong>{ productData.name }</strong>
						</Notice>
					) : (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'Using placeholder data. Configure a test product in the parent block to preview real stock status.',
								'peaches'
							) }
						</Notice>
					) }

					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Description Type', 'peaches' ) }
						value={ descriptionType }
						options={ descriptionTypes }
						onChange={ ( value ) =>
							setAttributes( { descriptionType: value } )
						}
						help={ __(
							'Select which type of description to display.',
							'peaches'
						) }
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Display Title', 'peaches' ) }
						checked={ displayTitle }
						onChange={ ( value ) =>
							setAttributes( { displayTitle: value } )
						}
						help={ __(
							'Show or hide the description title.',
							'peaches'
						) }
					/>

					{ displayTitle && (
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Custom Title', 'peaches' ) }
							value={ customTitle }
							onChange={ ( value ) =>
								setAttributes( { customTitle: value } )
							}
							help={ __(
								'Override the default title. Leave empty to use the description title or type name.',
								'peaches'
							) }
							placeholder={ getDisplayTitle() }
						/>
					) }
				</PanelBody>

				<BootstrapSettingsPanels
					attributes={ attributes }
					setAttributes={ setAttributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			<div { ...blockProps }>{ renderPreviewContent() }</div>
		</>
	);
}

export default ProductDescriptionEdit;
