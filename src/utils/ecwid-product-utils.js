/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { select } from '@wordpress/data';
import {
	PanelBody,
	Button,
	Notice,
	Flex,
	Spinner,
} from '@wordpress/components';

/**
 * Get current language for API requests
 *
 * Uses the global language function if available, with fallback to original logic.
 *
 * @return {string} Current language code (normalized to 2 characters)
 */
export function getCurrentLanguageForAPI() {
	// In block editor - check for peaches-multilingual store
	if ( typeof wp !== 'undefined' && wp.data && wp.data.select ) {
		try {
			const multilingualStore = wp.data.select( 'peaches/multilingual' );
			if (
				multilingualStore &&
				typeof multilingualStore.getCurrentEditorLanguage === 'function'
			) {
				const editorLang = multilingualStore.getCurrentEditorLanguage();
				if ( editorLang ) {
					return editorLang.split( '_' )[ 0 ].toLowerCase();
				}
			}
		} catch ( error ) {
			// Peaches multilingual store not available, continue with fallbacks
		}
	}

	// Use the global language function if available (from multilingual integration)
	if ( typeof window.getCurrentLanguageForAPI === 'function' ) {
		return window.getCurrentLanguageForAPI();
	}

	// Fallback to original logic if global function isn't available
	// Frontend - check HTML lang attribute (format: "en-US", "fr-FR", "nl-NL", etc.)
	const htmlLang = document.documentElement.lang;
	if ( htmlLang ) {
		// Extract language code (e.g., 'en-US' -> 'en', 'nl-NL' -> 'nl')
		return htmlLang.split( '-' )[ 0 ].toLowerCase();
	}

	// Check URL path for language (e.g., /nl/winkel/product)
	const langMatch = window.location.pathname.match( /^\/([a-z]{2})\// );
	if ( langMatch && langMatch[ 1 ] ) {
		return langMatch[ 1 ];
	}

	// Fallback - check for language in body class (common pattern)
	const bodyClasses = document.body.className;
	const langClassMatch = bodyClasses.match( /\blang-([a-z]{2})\b/ );
	if ( langClassMatch ) {
		return langClassMatch[ 1 ];
	}

	// Ultimate fallback
	return 'en';
}

/**
 * Custom hook for Ecwid product data management
 *
 * Handles both context-based and directly selected product data with fallback logic.
 *
 * @param {Object}   context       - Block context object
 * @param {Object}   attributes    - Block attributes
 * @param {Function} setAttributes - Function to update block attributes
 * @param {string}   clientId      - Block client ID
 *
 * @return {Object} - Product data and management functions
 */
export function useEcwidProductData(
	context,
	attributes,
	setAttributes,
	clientId
) {
	const [ productData, setProductData ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	// Get product data from context or attributes
	const contextProductData = context?.[ 'peaches/testProductData' ];
	const selectedProductId = attributes?.selectedProductId;

	/**
	 * Check if block has ecwid-product-detail ancestor
	 *
	 * @return {boolean} - True if ancestor exists
	 */
	const hasProductDetailAncestor = useCallback( () => {
		if ( ! clientId ) {
			// If no clientId provided, assume no ancestor (standalone usage)
			return false;
		}

		try {
			const { getBlockParents, getBlock } = select( 'core/block-editor' );
			const parents = getBlockParents( clientId );

			return parents.some( ( parentId ) => {
				const parent = getBlock( parentId );
				return parent?.name === 'peaches/ecwid-product-detail';
			} );
		} catch ( error ) {
			// If there's an error with block editor data, assume standalone
			console.warn( 'Error checking for ancestor block:', error );
			return false;
		}
	}, [ clientId ] );

	/**
	 * Fetch product data from REST API
	 *
	 * @param {number} productId - Ecwid product ID
	 */
	const fetchProductData = useCallback( async ( productId ) => {
		if ( ! productId ) {
			setProductData( null );
			setError( null );
			return;
		}

		setIsLoading( true );
		setError( null );

		try {
			const currentLang = getCurrentLanguageForAPI();
			const url = `/wp-json/peaches/v1/products/${ productId }`;
			const urlWithLang = `${ url }?lang=${ encodeURIComponent(
				currentLang
			) }`;

			fetch( urlWithLang, {
				headers: {
					Accept: 'application/json',
				},
				credentials: 'same-origin',
			} )
				.then( ( response ) => {
					if ( response.status === 404 ) {
						throw new Error(
							__( 'Product not found', 'ecwid-shopping-cart' )
						);
					}

					if ( ! response.ok ) {
						throw new Error(
							`HTTP error! status: ${ response.status }`
						);
					}
					return response.json();
				} )
				.then( ( data ) => {
					if ( data.success && data.data ) {
						setProductData( data.data );
						setError( null );
					} else {
						throw new Error(
							__( 'Invalid response data', 'ecwid-shopping-cart' )
						);
					}
				} )
				.catch( ( fetchError ) => {
					console.error( 'Error fetching ingredients:', fetchError );
					setError( fetchError.message );
					setProductData( null );
				} )
				.finally( () => {
					setIsLoading( false );
				} );
		} catch ( fetchError ) {
			setError( fetchError.message );
			setProductData( null );
		} finally {
			setIsLoading( false );
		}
	}, [] );

	/**
	 * Handle Ecwid product selection from popup
	 *
	 * @param {Object} params - Selection parameters
	 */
	const handleProductSelect = useCallback( ( params ) => {
		const newAttributes = {
			selectedProductId: params.newProps.product.id,
		};

		// Update global cache if available
		if ( window.EcwidGutenbergParams?.products ) {
			window.EcwidGutenbergParams.products[ params.newProps.product.id ] =
				{
					name: params.newProps.product.name,
					imageUrl: params.newProps.product.thumb,
				};
		}

		params.originalProps.setAttributes( newAttributes );
	}, [] );

	/**
	 * Open Ecwid product selection popup
	 *
	 * @param {Object} popupProps - Popup properties
	 */
	const openEcwidProductPopup = useCallback(
		( popupProps ) => {
			if ( typeof window.ecwid_open_product_popup === 'function' ) {
				window.ecwid_open_product_popup( {
					saveCallback: handleProductSelect,
					props: popupProps,
				} );
			} else {
				console.error( 'Ecwid product popup function not found' );
			}
		},
		[ handleProductSelect ]
	);

	/**
	 * Clear selected product
	 */
	const clearSelectedProduct = useCallback( () => {
		setAttributes( { selectedProductId: undefined } );
		setProductData( null );
		setError( null );
	}, [ setAttributes ] );

	// Effect to handle product data loading
	useEffect( () => {
		// Priority 1: Use context data if available
		if ( contextProductData ) {
			setProductData( contextProductData );
			setError( null );
			setIsLoading( false );
			return;
		}

		// Priority 2: Fetch data for selected product ID
		if ( selectedProductId ) {
			fetchProductData( selectedProductId );
			return;
		}

		// No product data available
		setProductData( null );
		setError( null );
		setIsLoading( false );
	}, [ contextProductData, selectedProductId, fetchProductData ] );

	return {
		productData,
		isLoading,
		error,
		hasProductDetailAncestor: hasProductDetailAncestor(),
		selectedProductId,
		openEcwidProductPopup,
		clearSelectedProduct,
		contextProductData,
	};
}

/**
 * Product Selection Panel Component
 *
 * Reusable Inspector Controls panel for product selection.
 *
 * @param {Object} props                          - Component props
 *
 * @param          props.productData
 * @param          props.isLoading
 * @param          props.error
 * @param          props.hasProductDetailAncestor
 * @param          props.selectedProductId
 * @param          props.contextProductData
 * @param          props.openEcwidProductPopup
 * @param          props.clearSelectedProduct
 * @param          props.attributes
 * @param          props.setAttributes
 * @return {JSX.Element} - Product selection panel
 */
export function ProductSelectionPanel( {
	productData,
	isLoading,
	error,
	hasProductDetailAncestor,
	selectedProductId,
	contextProductData,
	openEcwidProductPopup,
	clearSelectedProduct,
	attributes,
	setAttributes,
} ) {
	// Don't show panel if we have ancestor context
	if ( hasProductDetailAncestor ) {
		return (
			<PanelBody
				title={ __( 'Product Information', 'ecwid-shopping-cart' ) }
				initialOpen={ false }
			>
				{ ! contextProductData && (
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'No test product configured in parent block. Configure a test product to preview real data.',
							'ecwid-shopping-cart'
						) }
					</Notice>
				) }
				{ contextProductData && (
					<Notice status="success" isDismissible={ false }>
						{ __(
							'Using product data from parent block:',
							'ecwid-shopping-cart'
						) }{ ' ' }
						<strong>{ contextProductData.name }</strong>
					</Notice>
				) }
			</PanelBody>
		);
	}

	return (
		<PanelBody
			title={ __( 'Product Selection', 'ecwid-shopping-cart' ) }
			initialOpen={ true }
		>
			<Notice className="mb-2" status="info" isDismissible={ false }>
				{ __(
					'Select an Ecwid product to display its data in this block.',
					'ecwid-shopping-cart'
				) }
			</Notice>

			{ ! selectedProductId && (
				<Button
					variant="secondary"
					onClick={ () =>
						openEcwidProductPopup( {
							attributes,
							setAttributes,
						} )
					}
				>
					{ __( 'Select Product', 'ecwid-shopping-cart' ) }
				</Button>
			) }

			{ selectedProductId && selectedProductId > 0 && (
				<div className="selected-product-info">
					<div className="selected-product-header">
						<strong>
							{ __( 'Selected Product:', 'ecwid-shopping-cart' ) }
						</strong>
						<span className="selected-product-id">
							{ __( 'ID:', 'ecwid-shopping-cart' ) }{ ' ' }
							{ selectedProductId }
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

					{ productData && ! isLoading && ! error && (
						<div className="selected-product-details">
							<p>
								<strong>{ productData.name }</strong>
							</p>
							<div className="button-group">
								<Button
									variant="secondary"
									isSmall
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
									variant="secondary"
									isSmall
									onClick={ clearSelectedProduct }
								>
									{ __(
										'Clear Selection',
										'ecwid-shopping-cart'
									) }
								</Button>
							</div>
						</div>
					) }
				</div>
			) }
		</PanelBody>
	);
}

/**
 * Get formatted product field value
 *
 * Utility function to extract specific field values from product data.
 *
 * @param {Object} productData    - Product data object
 * @param {string} fieldType      - Type of field to extract
 * @param {string} customFieldKey - Key for custom fields
 *
 * @return {string} - Formatted field value
 */
export function getProductFieldValue(
	productData,
	fieldType,
	customFieldKey = ''
) {
	if ( ! productData ) {
		// Return placeholder values
		const placeholders = {
			title: __( 'Sample Product Title', 'ecwid-shopping-cart' ),
			subtitle: __( 'Sample Product Subtitle', 'ecwid-shopping-cart' ),
			price: '€ 29.99',
			stock: __( 'In Stock', 'ecwid-shopping-cart' ),
			description: __(
				'This is a sample product description…',
				'ecwid-shopping-cart'
			),
			custom: customFieldKey
				? `${ __(
						'Custom Field:',
						'ecwid-shopping-cart'
				  ) } ${ customFieldKey }`
				: __( 'Select a custom field', 'ecwid-shopping-cart' ),
		};
		return placeholders[ fieldType ] || '';
	}

	try {
		switch ( fieldType ) {
			case 'title':
				return productData.name || '';

			case 'subtitle':
				// Look for subtitle in product attributes
				if ( productData.attributes ) {
					const subtitleAttr = productData.attributes.find(
						( attr ) =>
							attr.name.toLowerCase().includes( 'ondertitel' ) ||
							attr.name.toLowerCase().includes( 'subtitle' ) ||
							attr.name.toLowerCase().includes( 'sub-title' ) ||
							attr.name.toLowerCase().includes( 'tagline' )
					);
					return (
						subtitleAttr?.valueTranslated?.nl ||
						subtitleAttr?.value ||
						''
					);
				}
				return '';

			case 'price':
				if ( productData.price !== undefined ) {
					const comparePrice = productData.compareToPrice;
					const currentPrice = productData.price;

					// Format price with currency
					const formatPrice = ( price ) => {
						return new Intl.NumberFormat( 'nl-NL', {
							style: 'currency',
							currency: 'EUR',
						} ).format( price );
					};

					if ( comparePrice && comparePrice > currentPrice ) {
						return `${ formatPrice( currentPrice ) } ${ formatPrice(
							comparePrice
						) }`;
					}
					return formatPrice( currentPrice );
				}
				return '';

			case 'stock':
				return productData.inStock
					? __( 'In Stock', 'ecwid-shopping-cart' )
					: __( 'Out of Stock', 'ecwid-shopping-cart' );

			case 'description':
				return productData.description || '';

			case 'custom':
				if ( customFieldKey && productData.attributes ) {
					const customField = productData.attributes.find(
						( attr ) => attr.name === customFieldKey
					);
					return (
						customField?.valueTranslated?.nl ||
						customField?.value ||
						''
					);
				}
				return customFieldKey
					? __( 'Custom field not found', 'ecwid-shopping-cart' )
					: __( 'Select a custom field', 'ecwid-shopping-cart' );

			default:
				return '';
		}
	} catch ( e ) {
		console.error( 'Error getting product field value:', e );
		return __( 'Error loading field', 'ecwid-shopping-cart' );
	}
}
