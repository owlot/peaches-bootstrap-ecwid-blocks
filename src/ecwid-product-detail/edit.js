/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { useMemo, useState, useEffect } from '@wordpress/element';
import {
	PanelBody,
	Button,
	Notice,
	Spinner,
	Flex,
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

function ProductDetailEdit( { attributes, setAttributes } ) {
	const { testProductId } = attributes;
	const [ testProductData, setTestProductData ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( { className } );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {} );

	// Update context when testProductData changes
	useEffect( () => {
		setAttributes( { testProductData } );
	}, [ testProductData, setAttributes ] );

	/**
	 * Fetch product data when testProductId changes
	 */
	useEffect( () => {
		if ( testProductId ) {
			setIsLoading( true );
			setError( null );

			// Use WordPress AJAX to fetch product data from server
			window.jQuery.ajax( {
				url: window.ajaxurl || '/wp-admin/admin-ajax.php',
				method: 'POST',
				dataType: 'json',
				data: {
					action: 'get_ecwid_product_data',
					product_id: testProductId,
					_ajax_nonce: window.EcwidGutenbergParams?.nonce || '',
					security: window.EcwidGutenbergParams?.nonce || '',
				},
				success( response ) {
					setIsLoading( false );
					if ( response && response.success && response.data ) {
						setTestProductData( response.data );
						setError( null );
					} else {
						setError(
							__(
								'Product not found or invalid response',
								'ecwid-shopping-cart'
							)
						);
						setTestProductData( null );
					}
				},
				error( xhr, status, errorThrown ) {
					setIsLoading( false );
					setError(
						`${ __(
							'Failed to fetch product data',
							'ecwid-shopping-cart'
						) }: ${ errorThrown }`
					);
					setTestProductData( null );
				},
			} );
		} else {
			setTestProductData( null );
			setError( null );
		}
	}, [ testProductId ] );

	/**
	 * Handle Ecwid product selection
	 * @param params
	 */
	const handleProductSelect = ( params ) => {
		const newAttributes = {
			testProductId: params.newProps.product.id,
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
	 * @param popupProps
	 */
	const openEcwidProductPopup = ( popupProps ) => {
		if ( typeof window.ecwid_open_product_popup === 'function' ) {
			window.ecwid_open_product_popup( {
				saveCallback: handleProductSelect,
				props: popupProps,
			} );
		} else {
			console.error( 'Ecwid product popup function not found' );
		}
	};

	/**
	 * Clear test product selection
	 */
	const clearTestProduct = () => {
		setAttributes( { testProductId: 0, testProductData: null } );
		setTestProductData( null );
		setError( null );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Test Product Configuration',
						'ecwid-shopping-cart'
					) }
					initialOpen={ false }
				>
					<Notice status="info" isDismissible={ false }>
						{ __(
							'Configure a test product to preview how child blocks will display product data in the editor.',
							'ecwid-shopping-cart'
						) }
					</Notice>

					{ ! testProductId && (
						<Button
							variant="primary"
							onClick={ () =>
								openEcwidProductPopup( {
									attributes,
									setAttributes,
								} )
							}
						>
							{ __(
								'Select Test Product',
								'ecwid-shopping-cart'
							) }
						</Button>
					) }

					{ testProductId && (
						<div className="test-product-info">
							<div className="test-product-header">
								<strong>
									{ __(
										'Test Product:',
										'ecwid-shopping-cart'
									) }
								</strong>
								<span className="test-product-id">
									{ __( 'ID:', 'ecwid-shopping-cart' ) }{ ' ' }
									{ testProductId }
								</span>
							</div>

							{ isLoading && (
								<Flex align="center" gap={ 2 }>
									<Spinner />
									<span>
										{ __(
											'Loading product data…',
											'ecwid-shopping-cart'
										) }
									</span>
								</Flex>
							) }

							{ error && (
								<Notice status="error" isDismissible={ false }>
									{ error }
								</Notice>
							) }

							{ testProductData && ! isLoading && (
								<div className="test-product-preview">
									<div className="test-product-details">
										<h4>{ testProductData.name }</h4>
										{ testProductData.price && (
											<p className="test-product-price">
												<strong>
													€ { testProductData.price }
												</strong>
											</p>
										) }
									</div>
									{ testProductData.thumbnailUrl && (
										<div className="test-product-image">
											<img
												src={
													testProductData.thumbnailUrl
												}
												alt={ testProductData.name }
												style={ {
													maxWidth: '80px',
													height: 'auto',
													borderRadius: '4px',
												} }
											/>
										</div>
									) }
								</div>
							) }

							<Flex gap={ 2 } style={ { marginTop: '12px' } }>
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
								<Button
									variant="tertiary"
									isDestructive
									onClick={ clearTestProduct }
								>
									{ __(
										'Clear Test Product',
										'ecwid-shopping-cart'
									) }
								</Button>
							</Flex>
						</div>
					) }
				</PanelBody>

				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			{ testProductId && testProductData && ! error && (
				<div
					className="test-product-indicator"
					style={ { marginBottom: '20px' } }
				>
					<Notice status="success" isDismissible={ false }>
						{ __( 'Test mode active:', 'ecwid-shopping-cart' ) }{ ' ' }
						<strong>{ testProductData.name }</strong>
					</Notice>
				</div>
			) }
			<div { ...innerBlocksProps } />
		</>
	);
}

export default ProductDetailEdit;
