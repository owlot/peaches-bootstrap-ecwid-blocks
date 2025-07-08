/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo, useState, useEffect } from '@wordpress/element';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	BootstrapSettingsPanels,
	computeClassName,
} from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
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

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( { className } );

	// Fetch product data when ID changes using the WordPress AJAX endpoint
	useEffect( () => {
		if ( attributes.id ) {
			setIsLoading( true );

			// Check if Ecwid cart API is available
			if ( typeof Ecwid !== 'undefined' && Ecwid.Cart ) {
				try {
					Ecwid.Product.addProduct( {
						id: productId,
						quantity: parseInt( context.amount ),
					} );
				} catch ( error ) {
					console.error( 'Error retrieving product info:', error );
				}
			} else {
				console.error( 'Ecwid Product API not available' );
			}

			// Use WordPress AJAX to fetch product data from server
			window.jQuery.ajax( {
				url: window.ajaxurl || '/wp-admin/admin-ajax.php',
				method: 'POST',
				dataType: 'json',
				data: {
					action: 'get_ecwid_product_data',
					product_id: attributes.id,
					_ajax_nonce: window.EcwidGutenbergParams?.nonce || '',
					security: window.EcwidGutenbergParams?.nonce || '',
				},
				success( response ) {
					setIsLoading( false );
					if ( response && response.success && response.data ) {
						setProductData( response.data );
					} else {
						console.error( 'Product not found:', response );
					}
				},
				error( xhr, status, error ) {
					setIsLoading( false );
					console.error( 'AJAX Error:', {
						status,
						error,
						responseText: xhr.responseText,
						statusCode: xhr.status,
					} );
				},
			} );
		} else {
			setProductData( null );
		}
	}, [ attributes.id ] );

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

	// Extract subtitle from product attributes
	const getProductSubtitle = () => {
		if ( ! productData || ! productData.attributes ) {
			return '';
		}

		const subtitleAttribute = productData.attributes.find(
			( attr ) => attr.name === 'Ondertitel'
		);

		return (
			subtitleAttribute?.valueTranslated?.nl ||
			subtitleAttribute?.value ||
			''
		);
	};

	return (
		<>
			<InspectorControls>
				<div className="block-editor-block-card">
					{ attributes.id && (
						<div>
							<div className="ec-store-inspector-row">
								<label className="ec-store-inspector-subheader">
									{ __(
										'Displayed product',
										'ecwid-shopping-cart'
									) }
								</label>
							</div>
							<div className="ec-store-inspector-row">
								{ productData?.name && (
									<label>{ productData.name }</label>
								) }

								<button
									className="button"
									onClick={ () =>
										openEcwidProductPopup( props )
									}
								>
									{ __( 'Change', 'ecwid-shopping-cart' ) }
								</button>
							</div>
						</div>
					) }
					{ ! attributes.id && (
						<div className="ec-store-inspector-row">
							<button
								className="button"
								onClick={ () => openEcwidProductPopup( props ) }
							>
								{ __(
									'Choose product',
									'ecwid-shopping-cart'
								) }
							</button>
						</div>
					) }
				</div>
				<PanelBody
					title={ __(
						'Product Cart Settings',
						'ecwid-shopping-cart'
					) }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							"Show 'Add to cart' button",
							'ecwid-shopping-cart'
						) }
						checked={ attributes.showAddToCart }
						onChange={ ( value ) =>
							setAttributes( { showAddToCart: value } )
						}
						help={ __(
							'Shows an icon button to instantly add the product to the cart.',
							'ecwid-shopping-cart'
						) }
					/>
				</PanelBody>
				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ ! attributes.id && (
					<div className="ratio ratio-1x1 text-center">
						<button
							className="btn btn-primary"
							onClick={ () => {
								const params = {
									saveCallback,
									props,
								};
								openEcwidProductPopup( params );
							} }
						>
							{ window.EcwidGutenbergParams?.chooseProduct ||
								__( 'Choose Product', 'ecwid-shopping-cart' ) }
						</button>
					</div>
				) }
				{ attributes.id && (
					<div className="card h-100 border-0">
						{ isLoading && (
							<div className="position-absolute top-50 start-50 translate-middle">
								<div
									className="spinner-border text-primary"
									role="status"
								>
									<span className="visually-hidden">
										{ __(
											'Loading product…',
											'ecwid-shopping-cart'
										) }
									</span>
								</div>
							</div>
						) }

						<div className="ratio ratio-1x1">
							{ productData?.thumbnailUrl ? (
								<img
									className="card-img-top"
									src={ productData.thumbnailUrl }
									alt={ productData.name }
								/>
							) : (
								<div className="card-img-top bg-light d-flex align-items-center justify-content-center">
									<span className="text-muted">
										{ isLoading
											? __( '…', 'ecwid-shopping-cart' )
											: __(
													'Product Image',
													'ecwid-shopping-cart'
											  ) }
									</span>
								</div>
							) }
						</div>
						<div className="card-body p-2 p-md-3 d-flex row-cols-1 flex-wrap align-content-between">
							<h5 role="button" className="card-title">
								{ productData?.name ||
									( isLoading
										? '...'
										: __(
												'Product Name',
												'ecwid-shopping-cart'
										  ) ) }
							</h5>
							<p className="card-text text-muted">
								{ getProductSubtitle() ||
									( isLoading
										? '...'
										: __(
												'Product Subtitle',
												'ecwid-shopping-cart'
										  ) ) }
							</p>
						</div>
						<div className="card-footer border-0 hstack justify-content-between">
							<div className="card-text fw-bold lead">
								{ productData?.price
									? `€ ${ productData.price }`
									: isLoading
									? '...'
									: '€ --' }
							</div>
							{ attributes.showAddToCart && (
								<button className="add-to-cart btn" />
							) }
						</div>
					</div>
				) }
			</div>
		</>
	);
}

export default ProductEdit;
