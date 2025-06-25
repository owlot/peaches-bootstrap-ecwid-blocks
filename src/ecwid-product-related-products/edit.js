/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	useState,
	useEffect,
	useCallback,
	useRef,
	useMemo,
} from '@wordpress/element';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	InnerBlocks,
} from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	RangeControl,
	Notice,
} from '@wordpress/components';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	BootstrapSettingsPanels,
	computeClassName,
} from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';
import {
	getCurrentLanguageForAPI,
	useEcwidProductData,
	ProductSelectionPanel,
} from '../utils/ecwid-product-utils';

// Supported Bootstrap settings
const SUPPORTED_SETTINGS = {
	responsive: {
		display: { opacity: true, display: true },
		placements: {
			zIndex: true,
			textAlign: true,
			justifyContent: true,
			alignSelf: true,
			alignItems: true,
		},
		sizes: { ratio: true, rowCols: true },
		spacings: { gutter: true, padding: true },
	},
	general: {
		border: { rounded: true, display: true },
		sizes: { width: true, height: true },
		spacings: { gaps: true },
	},
};

/**
 * Related Products Block Edit Component
 *
 * @param {Object}   props               - Component props
 * @param {Object}   props.attributes    - Block attributes
 * @param {Function} props.setAttributes - Function to set attributes
 * @param {string}   props.clientId      - Block client ID
 *
 * @param            props.context
 * @return {JSX.Element} Edit component
 */
export default function Edit( {
	context,
	attributes,
	setAttributes,
	clientId,
} ) {
	const { showTitle, customTitle } = attributes;

	const [ relatedProducts, setRelatedProducts ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	// Use refs for stable references
	const currentProductIdRef = useRef( null );
	const blocksInsertedRef = useRef( false );
	const isProcessingRef = useRef( false );

	// Get block editor methods
	const { insertBlock, removeBlocks } = useDispatch( 'core/block-editor' );

	// Get parent block information to detect carousel and inner blocks
	const isInCarousel = useSelect(
		( select ) => {
			const { getBlockParents, getBlock } = select( 'core/block-editor' );
			const parents = getBlockParents( clientId );

			// Check all parent blocks for carousel
			let foundCarousel = false;

			for ( const parentId of parents ) {
				const parent = getBlock( parentId );
				if ( parent?.name === 'peaches/bs-carousel' ) {
					foundCarousel = true;
					break;
				}
			}

			return foundCarousel;
		},
		[ clientId ]
	);
	// Use unified product data hook
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

	/**
	 * Clear inner blocks safely without dependencies on innerBlocks
	 *
	 * @return {void}
	 */
	const clearInnerBlocks = useCallback( () => {
		// Prevent concurrent operations
		if ( isProcessingRef.current ) {
			return;
		}

		isProcessingRef.current = true;

		try {
			// Get fresh inner blocks to avoid stale closure
			const { getBlocks } = wp.data.select( 'core/block-editor' );
			const currentInnerBlocks = getBlocks( clientId );

			if ( currentInnerBlocks && currentInnerBlocks.length > 0 ) {
				const validClientIds = currentInnerBlocks
					.filter( ( block ) => block && block.clientId )
					.map( ( block ) => block.clientId );

				if ( validClientIds.length > 0 ) {
					removeBlocks( validClientIds );
				}
			}

			blocksInsertedRef.current = false;
		} catch ( e ) {
			console.warn( 'Error clearing inner blocks:', e );
		} finally {
			isProcessingRef.current = false;
		}
	}, [ clientId, removeBlocks ] );

	/**
	 * Create product blocks for the related products
	 *
	 * @param {Array} productIds - Array of product IDs
	 *
	 * @return {void}
	 */
	const createProductBlocks = useCallback(
		( productIds, maxProducts, showAddToCart ) => {
			// Only proceed if we have valid product IDs and not already processing
			if (
				! productIds ||
				! Array.isArray( productIds ) ||
				productIds.length === 0 ||
				isProcessingRef.current
			) {
				return;
			}

			isProcessingRef.current = true;

			try {
				// Always create bs-col blocks with ecwid-product blocks inside
				const colBlocks = productIds
					.slice( 0, maxProducts )
					.map( () => {
						return createBlock( 'peaches/bs-col', {} );
					} );

				// Insert all column blocks first
				colBlocks.forEach( ( colBlock ) => {
					insertBlock( colBlock, undefined, clientId );
				} );

				// After columns are inserted, clear default content and add product blocks
				productIds
					.slice( 0, maxProducts )
					.forEach( ( productId, index ) => {
						// First, clear any existing inner blocks (like default paragraphs)
						const colClientId = colBlocks[ index ].clientId;

						setTimeout( () => {
							// Remove any existing inner blocks
							const { getBlocks } =
								wp.data.select( 'core/block-editor' );
							const existingInnerBlocks =
								getBlocks( colClientId );

							if ( existingInnerBlocks.length > 0 ) {
								const blockClientIds = existingInnerBlocks.map(
									( block ) => block.clientId
								);
								removeBlocks( blockClientIds );
							}
							const productBlock = createBlock(
								'peaches/ecwid-product',
								{
									id: productId,
									showAddToCart,
								}
							);

							// Insert the product block into the column
							insertBlock( productBlock, 0, colClientId );
						}, 50 );
					} );

				blocksInsertedRef.current = true;
			} catch ( e ) {
				console.error( 'Error creating product blocks:', e );
				blocksInsertedRef.current = false;
			} finally {
				isProcessingRef.current = false;
			}
		},
		[ insertBlock, removeBlocks, clientId ]
	);

	/**
	 * Update blocks when related products change
	 *
	 * @return {void}
	 */
	const updateBlocks = useCallback( () => {
		const productId = productData?.id;

		// Check if we need to update blocks
		const shouldUpdateBlocks =
			productId &&
			relatedProducts.length > 0 &&
			! isProcessingRef.current;

		if ( shouldUpdateBlocks ) {
			// Clear existing blocks first
			clearInnerBlocks();

			createProductBlocks(
				relatedProducts,
				attributes.maxProducts,
				attributes.showAddToCart
			);
			currentProductIdRef.current = productId;
		} else if ( ! productId ) {
			// Clear blocks if no product
			if ( blocksInsertedRef.current ) {
				clearInnerBlocks();
				currentProductIdRef.current = null;
			}
		}
	}, [
		productData?.id,
		relatedProducts,
		attributes.showAddToCart,
		attributes.maxProducts,
		clearInnerBlocks,
		createProductBlocks,
	] );

	// Update blocks when dependencies change
	useEffect( () => {
		updateBlocks();
	}, [ updateBlocks ] );

	// Update isInCarousel attribute whenever carousel detection changes
	useEffect( () => {
		if ( attributes.isInCarousel !== isInCarousel ) {
			setAttributes( { isInCarousel } );
		}
	}, [ isInCarousel, attributes.isInCarousel, setAttributes ] );

	// Fetch related products when test product changes
	useEffect( () => {
		if ( ! productData?.id ) {
			setRelatedProducts( [] );
			setError( null );
			return;
		}

		// Don't fetch if we're already loading for this product
		if ( currentProductIdRef.current === productData.id && isLoading ) {
			return;
		}

		setIsLoading( true );
		setError( null );

		fetch( `/wp-json/peaches/v1/related-products/${ productData.id }`, {
			headers: {
				Accept: 'application/json',
			},
			credentials: 'same-origin',
		} )
			.then( ( response ) => {
				if ( response.status === 404 ) {
					setError(
						__(
							'No related products found',
							'peaches-bootstrap-ecwid-blocks'
						)
					);
					setRelatedProducts( [] );
					setIsLoading( false );
					clearInnerBlocks();
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
					responseData.related_products
				) {
					setRelatedProducts( responseData.related_products );
					setError( null );
				} else {
					setError(
						__(
							'No related products found',
							'peaches-bootstrap-ecwid-blocks'
						)
					);
					setRelatedProducts( [] );
					clearInnerBlocks();
				}
			} )
			.catch( ( fetchError ) => {
				console.error( 'Error fetching related products:', fetchError );
				setError(
					__(
						'Error loading related products',
						'peaches-bootstrap-ecwid-blocks'
					) +
						': ' +
						fetchError.message
				);
				setRelatedProducts( [] );
				setIsLoading( false );
			} );
	}, [ productData?.id, clearInnerBlocks ] );

	/**
	 * Compute className from Bootstrap attributes and store it
	 */
	const computedClassName = useMemo( () => {
		return 'row ' + computeClassName( attributes );
	}, [ attributes ] );

	const blockProps = useBlockProps();

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: computedClassName,
		},
		{
			allowedBlocks: [ 'peaches/bs-col' ], // Always use bs-col blocks
			templateLock: false, // Allow dynamic insertion
		}
	);

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

				{ isInCarousel && (
					<Notice status="info" isDismissible={ false }>
						{ __(
							'This block is inside a carousel. Products will be displayed as carousel slides. Title and status messages are not shown in carousel mode.',
							'peaches-bootstrap-ecwid-blocks'
						) }
					</Notice>
				) }

				<PanelBody
					title={ __( 'Related Products Settings', 'peaches' ) }
					initialOpen={ true }
				>
					<ToggleControl
						label={ __(
							'Show Add to Cart',
							'peaches-bootstrap-ecwid-blocks'
						) }
						checked={ attributes.showAddToCart }
						onChange={ ( value ) =>
							setAttributes( { showAddToCart: value } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show Title', 'peaches' ) }
						checked={ showTitle }
						onChange={ ( value ) =>
							setAttributes( { showTitle: value } )
						}
						disabled={ isInCarousel }
						help={
							isInCarousel
								? __(
										'Title is not shown in carousel mode',
										'peaches-bootstrap-ecwid-blocks'
								  )
								: ''
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					{ showTitle && ! isInCarousel && (
						<TextControl
							label={ __( 'Custom Title', 'peaches' ) }
							value={ customTitle }
							onChange={ ( value ) =>
								setAttributes( { customTitle: value } )
							}
							placeholder={ __(
								'Related Products',
								'peaches-bootstrap-ecwid-blocks'
							) }
							help={ __(
								'Leave empty to use default title',
								'peaches-bootstrap-ecwid-blocks'
							) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }

					<RangeControl
						label={ __( 'Maximum Products', 'peaches' ) }
						value={ attributes.maxProducts }
						onChange={ ( value ) =>
							setAttributes( { maxProducts: value } )
						}
						min={ 1 }
						max={ 12 }
						help={ __(
							'Maximum number of related products to display',
							'peaches-bootstrap-ecwid-blocks'
						) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				<BootstrapSettingsPanels
					attributes={ attributes }
					setAttributes={ setAttributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			{ isInCarousel ? (
				// In carousel: render ONLY the inner blocks, no wrapper elements at all
				<InnerBlocks
					allowedBlocks={ [ 'peaches/bs-col' ] }
					templateLock={ false }
					renderAppender={ false }
				/>
			) : (
				// Not in carousel: render with wrapper div and all UI elements
				<div { ...blockProps }>
					{ showTitle && (
						<h3 className="related-products-title mb-4">
							{ customTitle ||
								__(
									'Related Products',
									'peaches-bootstrap-ecwid-blocks'
								) }
						</h3>
					) }

					{ isLoading && (
						<div className="d-flex align-items-center mb-3">
							<div
								className="spinner-border spinner-border-sm me-2"
								role="status"
							>
								<span className="visually-hidden">
									{ __(
										'Loading…',
										'peaches-bootstrap-ecwid-blocks'
									) }
								</span>
							</div>
							<span>
								{ __(
									'Loading related products…',
									'peaches-bootstrap-ecwid-blocks'
								) }
							</span>
						</div>
					) }

					{ error && (
						<div className="alert alert-warning mb-3" role="alert">
							{ error }
						</div>
					) }

					{ ! productData && (
						<div className="alert alert-info mb-3" role="alert">
							{ __(
								'Configure a test product in the Product Detail block to see related products preview.',
								'peaches-bootstrap-ecwid-blocks'
							) }
						</div>
					) }

					<div { ...innerBlocksProps } />
				</div>
			) }
		</>
	);
}
