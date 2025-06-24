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
	 * Fetch product data when testProductId changes - using REST API instead of AJAX
	 */
	useEffect( () => {
		if ( testProductId ) {
			setIsLoading( true );
			setError( null );

			// Use REST API directly - works in both editor and frontend
			fetch( `/wp-json/peaches/v1/products/${ testProductId }`, {
				headers: {
					'X-WP-Nonce': wpApiSettings?.nonce || '',
					Accept: 'application/json',
				},
				credentials: 'same-origin',
			} )
				.then( ( response ) => {
					if ( response.status === 404 ) {
						setError(
							__(
								'Product not found or invalid response',
								'ecwid-shopping-cart'
							)
						);
						setTestProductData( null );
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
					setIsLoading( false );
					if (
						responseData &&
						responseData.success &&
						responseData.data
					) {
						setTestProductData( responseData.data );
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
				} )
				.catch( ( fetchError ) => {
					setIsLoading( false );
					setError(
						`${ __(
							'Failed to fetch product data',
							'ecwid-shopping-cart'
						) }: ${ fetchError.message }`
					);
					setTestProductData( null );
				} );
		} else {
			setTestProductData( null );
			setError( null );
		}
	}, [ testProductId ] );

	/**
	 * Handle Ecwid product selection
	 *
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
	 *
	 * @param {Object} popupProps - Popup properties
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
		setAttributes( { testProductId: undefined, testProductData: null } );
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
					<Notice
						className="mb-2"
						status="info"
						isDismissible={ false }
					>
						{ __(
							'Configure a test product to preview how child blocks will display product data in the editor.',
							'ecwid-shopping-cart'
						) }
					</Notice>

					{ ! testProductId && (
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
								'Select Test Product',
								'ecwid-shopping-cart'
							) }
						</Button>
					) }

					{ testProductId && testProductId > 0 && (
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

							{ testProductData && ! isLoading && ! error && (
								<div className="test-product-details">
									<p>
										<strong>
											{ testProductData.name }
										</strong>
									</p>
									<Button
										variant="secondary"
										isSmall
										onClick={ clearTestProduct }
									>
										{ __(
											'Clear Selection',
											'ecwid-shopping-cart'
										) }
									</Button>
								</div>
							) }
						</div>
					) }
				</PanelBody>

				<BootstrapSettingsPanels
					attributes={ attributes }
					setAttributes={ setAttributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			<div { ...innerBlocksProps } />
		</>
	);
}

export default ProductDetailEdit;
