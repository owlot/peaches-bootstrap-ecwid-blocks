/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo, useState, useEffect } from '@wordpress/element';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	Button,
	ToggleControl,
	Notice,
	Spinner,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	BootstrapSettingsPanels,
	computeClassName,
} from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

/**
 * Styles
 */
import './editor.scss';

const SUPPORTED_SETTINGS = {
	responsive: {
		spacings: {
			margin: true,
			padding: true,
		},
	},
};

function ProductEdit( props ) {
	const { attributes, setAttributes } = props;
	const [ productData, setProductData ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	/**
	 * Compute className from Bootstrap attributes and store it
	 */
	const computedClassName = useMemo( () => {
		return computeClassName( attributes );
	}, [ attributes ] );

	const blockProps = useBlockProps( { className: computedClassName } );
	/**
	 * Update the computedClassName attribute when it changes
	 */
	useEffect( () => {
		if ( attributes.computedClassName !== computedClassName ) {
			setAttributes( { computedClassName } );
		}
	}, [ computedClassName, attributes.computedClassName, setAttributes ] );

	/**
	 * Fetch product data when ID changes using REST API
	 */
	useEffect( () => {
		if ( ! attributes.id ) {
			setProductData( null );
			setError( null );
			return;
		}

		setIsLoading( true );
		setError( null );

		// Use REST API directly - works in both editor and frontend
		fetch( `/wp-json/peaches/v1/products/${ attributes.id }`, {
			headers: {
				'X-WP-Nonce': wpApiSettings?.nonce || '',
				Accept: 'application/json',
			},
			credentials: 'same-origin',
		} )
			.then( ( response ) => {
				if ( response.status === 404 ) {
					setError(
						__( 'Product not found', 'ecwid-shopping-cart' )
					);
					setProductData( null );
					setIsLoading( false );
					return null;
				}

				if ( ! response.ok ) {
					throw new Error(
						`HTTP error! status: ${ response.status }`
					);
				}

				return response.json();
			} )
			.then( ( responseData ) => {
				if (
					responseData &&
					responseData.success &&
					responseData.data
				) {
					setProductData( responseData.data );
					setError( null );
				}
				setIsLoading( false );
			} )
			.catch( ( fetchError ) => {
				setIsLoading( false );
				console.error( 'Fetch Error:', {
					error: fetchError.message,
					productId: attributes.id,
				} );
				setError(
					__( 'Failed to load product data', 'ecwid-shopping-cart' )
				);
			} );
	}, [ attributes.id ] );

	/**
	 * Handle Ecwid product selection
	 *
	 * @param {Object} params - Selection parameters
	 */
	const saveCallback = function ( params ) {
		const newAttributes = {
			id: params.newProps.product.id,
		};

		// Update the global cache if it exists
		if (
			window.EcwidGutenbergParams &&
			window.EcwidGutenbergParams.products
		) {
			window.EcwidGutenbergParams.products[ params.newProps.product.id ] =
				{
					name: params.newProps.product.name,
					imageUrl: params.newProps.product.thumb,
				};
		}

		params.originalProps.setAttributes( newAttributes );
	};

	/**
	 * Open Ecwid product selection popup
	 *
	 * @param {Object} popupProps - Popup properties
	 */
	function openEcwidProductPopup( popupProps ) {
		if ( typeof window.ecwid_open_product_popup === 'function' ) {
			window.ecwid_open_product_popup( {
				saveCallback,
				props: popupProps,
			} );
		} else {
			console.error( 'Ecwid product popup function not found' );
		}
	}

	/**
	 * Extract subtitle from product attributes
	 *
	 * @return {string} Product subtitle
	 */
	const getProductSubtitle = () => {
		if ( ! productData || ! productData.attributes ) {
			return '';
		}

		// Look for subtitle in product attributes
		const subtitleAttr = productData.attributes.find(
			( attr ) =>
				attr.name.toLowerCase().includes( 'ondertitel' ) ||
				attr.name.toLowerCase().includes( 'subtitle' ) ||
				attr.name.toLowerCase().includes( 'sub-title' ) ||
				attr.name.toLowerCase().includes( 'tagline' )
		);

		return subtitleAttr ? subtitleAttr.value : '';
	};

	/**
	 * Format product price
	 *
	 * @return {string} Formatted price
	 */
	const formatPrice = () => {
		if ( ! productData || typeof productData.price === 'undefined' ) {
			return __( 'Price not available', 'ecwid-shopping-cart' );
		}

		// Simple formatting - you might want to add currency symbols
		return `€ ${ parseFloat( productData.price ).toFixed( 2 ) }`;
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Product Settings', 'ecwid-shopping-cart' ) }
					initialOpen={ true }
				>
					{ ! attributes.id && (
						<Button
							variant="primary"
							onClick={ () =>
								openEcwidProductPopup( {
									attributes,
									setAttributes,
								} )
							}
						>
							{ __( 'Choose Product', 'ecwid-shopping-cart' ) }
						</Button>
					) }

					{ attributes.id && (
						<div className="product-info">
							<p>
								<strong>
									{ __(
										'Product ID:',
										'ecwid-shopping-cart'
									) }
								</strong>{ ' ' }
								{ attributes.id }
							</p>
							{ productData && (
								<p>
									<strong>{ productData.name }</strong>
								</p>
							) }
							<Button
								variant="secondary"
								onClick={ () =>
									openEcwidProductPopup( {
										attributes,
										setAttributes,
									} )
								}
							>
								{ __(
									'Change Product',
									'ecwid-shopping-cart'
								) }
							</Button>
						</div>
					) }

					<ToggleControl
						label={ __(
							'Show Add to Cart',
							'ecwid-shopping-cart'
						) }
						checked={ attributes.showAddToCart }
						onChange={ ( value ) =>
							setAttributes( { showAddToCart: value } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ isLoading && (
					<div className="text-center my-3">
						<Spinner />
						<p>
							{ __( 'Loading product…', 'ecwid-shopping-cart' ) }
						</p>
					</div>
				) }

				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }

				{ ! attributes.id && ! isLoading && (
					<div className="product-placeholder">
						<p>
							{ __(
								'Please select a product to display.',
								'ecwid-shopping-cart'
							) }
						</p>
					</div>
				) }

				{ productData && ! isLoading && ! error && (
					<div className="card h-100 border-0 shadow-sm">
						{ productData.thumbnailUrl && (
							<div className="card-img-top ratio ratio-1x1">
								<img
									src={ productData.thumbnailUrl }
									alt={ productData.name }
									className="object-fit-cover"
								/>
							</div>
						) }
						<div className="card-body p-2 p-md-3 d-flex flex-wrap align-content-between">
							<h5
								role="button"
								className="card-title"
								data-wp-text="state.productName"
								data-wp-on--click="actions.navigateToProduct"
							>
								{ productData.name }
							</h5>
							<p
								className="card-subtitle mb-2 text-muted"
								data-wp-text="state.productSubtitle"
							>
								{ getProductSubtitle() }
							</p>
						</div>
						<div className="card-footer border-0 hstack justify-content-between">
							<div
								className="card-text fw-bold lead"
								data-wp-text="state.productPrice"
							>
								{ formatPrice() }
							</div>
							{ attributes.showAddToCart && (
								<button
									title="Add to cart"
									className="add-to-cart btn pe-0"
									aria-label="Add to cart"
									data-wp-on--click="actions.addToCart"
								></button>
							) }
						</div>
					</div>
				) }
			</div>
		</>
	);
}

export default ProductEdit;
