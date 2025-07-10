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
	SelectControl,
	Notice,
	Spinner,
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
	const {
		translations = {},
		showTitle = true,
		customTitle = '',
		maxProducts = 4,
		showAddToCart = true,
		buttonText = 'Add to cart',
		showCardHoverShadow = true,
		showCardHoverJump = true,
		hoverMediaTag = '',
	} = attributes;

	// State for related products
	const [ relatedProducts, setRelatedProducts ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ isLoadingTags, setIsLoadingTags ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ mediaTags, setMediaTags ] = useState( [] );

	// Use refs for stable references
	const currentProductIdRef = useRef( null );
	const blocksInsertedRef = useRef( false );
	const isProcessingRef = useRef( false );

	// Get language information for translations
	const defaultLanguage = useSelect( () => {
		if ( typeof window.peachesMultilingual !== 'undefined' ) {
			return window.peachesMultilingual.defaultLanguage;
		}
		return null;
	}, [] );

	const currentLang = useSelect( ( select ) => {
		// Check if the store exists before trying to select from it
		if ( select( 'peaches/multilingual' ) ) {
			return select( 'peaches/multilingual' ).getCurrentEditorLanguage();
		}
		return null;
	}, [] );

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

	// Load media tags on component mount
	useEffect( () => {
		const loadMediaTags = async () => {
			setIsLoadingTags( true );
			try {
				const response = await fetch(
					'/wp-json/peaches/v1/media-tags',
					{
						headers: { Accept: 'application/json' },
						credentials: 'same-origin',
					}
				);

				if ( ! response.ok ) {
					throw new Error(
						`HTTP error! status: ${ response.status }`
					);
				}

				const data = await response.json();

				if ( data.success && Array.isArray( data.data ) ) {
					// Filter for image media tags only
					const imageTags = data.data.filter(
						( tag ) => tag.expectedMediaType === 'image'
					);
					setMediaTags( imageTags );
				} else {
					throw new Error(
						__(
							'Invalid response format',
							'peaches-bootstrap-ecwid-blocks'
						)
					);
				}
			} catch ( fetchError ) {
				console.error( 'Failed to load media tags:', fetchError );
			}
			setIsLoadingTags( false );
		};

		loadMediaTags();
	}, [] );

	// Memoized translated button text based on current language
	const translatedButtonText = useMemo( () => {
		// If we don't have language info or translations, use buttonText
		if ( ! currentLang || ! translations ) {
			return buttonText;
		}

		// If we're on default language, use buttonText
		if ( currentLang === defaultLanguage ) {
			return buttonText;
		}

		// Check for new multilingual system format first (buttonText: { lang: 'text' })
		if (
			translations.buttonText &&
			translations.buttonText.hasOwnProperty( currentLang )
		) {
			// Return the translation even if it's an empty string
			return translations.buttonText[ currentLang ];
		}

		// Fallback to old format for backward compatibility (lang: 'text')
		if ( translations.hasOwnProperty( currentLang ) ) {
			return translations[ currentLang ];
		}

		// Return default if no translation found
		return buttonText;
	}, [ currentLang, defaultLanguage, translations, buttonText ] );

	// Handle button text changes with new multilingual system support
	const handleButtonTextChange = useCallback(
		( newButtonText ) => {
			if ( typeof newButtonText === 'string' ) {
				// If we're on the default language, update the main buttonText attribute
				if ( ! currentLang || currentLang === defaultLanguage ) {
					setAttributes( { buttonText: newButtonText } );
				} else {
					// For non-default languages, store in the new translation format
					// Allow empty strings to be saved as translations
					setAttributes( {
						translations: {
							...translations,
							buttonText: {
								...( translations.buttonText || {} ),
								[ currentLang ]: newButtonText, // This can be empty string
							},
						},
					} );
				}
			}
		},
		[ setAttributes, currentLang, defaultLanguage, translations ]
	);

	/**
	 * Get grouped media tag options for select control
	 */
	const getGroupedMediaTagOptions = useMemo( () => {
		if ( ! mediaTags.length ) {
			return [
				{
					label: __(
						'No image media tags available',
						'peaches-bootstrap-ecwid-blocks'
					),
					value: '',
					disabled: true,
				},
			];
		}

		// Group tags by category
		const groupedTags = mediaTags.reduce( ( groups, tag ) => {
			const category = tag.category || 'other';
			if ( ! groups[ category ] ) {
				groups[ category ] = [];
			}
			groups[ category ].push( tag );
			return groups;
		}, {} );

		// Create options with category headers
		const options = [
			{
				label: __( 'No hover image', 'peaches-bootstrap-ecwid-blocks' ),
				value: '',
			},
		];

		// Define category order and labels
		const categoryInfo = {
			primary: __( 'Primary Content', 'peaches-bootstrap-ecwid-blocks' ),
			secondary: __(
				'Secondary Content',
				'peaches-bootstrap-ecwid-blocks'
			),
			reference: __(
				'Reference Materials',
				'peaches-bootstrap-ecwid-blocks'
			),
			media: __( 'Rich Media', 'peaches-bootstrap-ecwid-blocks' ),
			other: __( 'Other', 'peaches-bootstrap-ecwid-blocks' ),
		};

		Object.entries( categoryInfo ).forEach(
			( [ categoryKey, categoryLabel ] ) => {
				if ( groupedTags[ categoryKey ] ) {
					options.push( {
						label: `── ${ categoryLabel } ──`,
						value: `category_${ categoryKey }`,
						disabled: true,
					} );

					groupedTags[ categoryKey ].forEach( ( tag ) => {
						options.push( {
							label: `    ${ tag.label }`,
							value: tag.key,
						} );
					} );
				}
			}
		);

		return options;
	}, [ mediaTags ] );

	/**
	 * Get selected tag info
	 */
	const getSelectedHoverTagInfo = () => {
		if ( ! hoverMediaTag ) {
			return null;
		}
		return mediaTags.find( ( tag ) => tag.key === hoverMediaTag );
	};

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
	 * Create product blocks for the related products with all settings passed through
	 *
	 * @param {Array}  productIds       - Array of product IDs
	 * @param {number} maxProducts      - Maximum products to create
	 * @param {Object} parentAttributes - All parent attributes to pass through
	 *
	 * @return {void}
	 */
	const createProductBlocks = useCallback(
		( productIds, maxProducts, parentAttributes ) => {
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

							// Extract all product settings from parent attributes
							const productSettings = {
								id: productId,
								showAddToCart: parentAttributes.showAddToCart,
								buttonText:
									parentAttributes.buttonText ||
									'Add to cart',
								showCardHoverShadow:
									parentAttributes.showCardHoverShadow !==
									undefined
										? parentAttributes.showCardHoverShadow
										: true,
								showCardHoverJump:
									parentAttributes.showCardHoverJump !==
									undefined
										? parentAttributes.showCardHoverJump
										: true,
								hoverMediaTag:
									parentAttributes.hoverMediaTag || '',
								translations:
									parentAttributes.translations || {},
							};

							// Create the product block with all settings
							const productBlock = createBlock(
								'peaches/ecwid-product',
								productSettings
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
	 * Update existing product blocks with new attributes without recreating them
	 *
	 * @param {Object} newAttributes - Updated attributes to apply
	 *
	 * @return {void}
	 */
	const updateExistingProductBlocks = useCallback(
		( newAttributes ) => {
			try {
				// Get all inner blocks (columns)
				const { getBlocks } = wp.data.select( 'core/block-editor' );
				const { updateBlockAttributes } =
					wp.data.dispatch( 'core/block-editor' );
				const innerBlocks = getBlocks( clientId );

				// Update each product block within the columns
				innerBlocks.forEach( ( colBlock ) => {
					if ( colBlock.name === 'peaches/bs-col' ) {
						const productBlocks = getBlocks( colBlock.clientId );
						productBlocks.forEach( ( productBlock ) => {
							if (
								productBlock.name === 'peaches/ecwid-product'
							) {
								// Extract only the product-related attributes
								const productSettings = {
									showAddToCart: newAttributes.showAddToCart,
									buttonText:
										newAttributes.buttonText ||
										'Add to cart',
									showCardHoverShadow:
										newAttributes.showCardHoverShadow !==
										undefined
											? newAttributes.showCardHoverShadow
											: true,
									showCardHoverJump:
										newAttributes.showCardHoverJump !==
										undefined
											? newAttributes.showCardHoverJump
											: true,
									hoverMediaTag:
										newAttributes.hoverMediaTag || '',
									translations:
										newAttributes.translations || {},
								};

								// Update the product block attributes
								updateBlockAttributes(
									productBlock.clientId,
									productSettings
								);
							}
						} );
					}
				} );
			} catch ( e ) {
				console.error( 'Error updating existing product blocks:', e );
			}
		},
		[ clientId ]
	);

	/**
	 * Update blocks when related products change (for structural changes only)
	 *
	 * @return {void}
	 */
	const updateBlocks = useCallback( () => {
		const productId = productData?.id;

		// Check if we need to CREATE new blocks (structure change)
		const shouldCreateBlocks =
			productId &&
			relatedProducts.length > 0 &&
			! isProcessingRef.current &&
			( ! blocksInsertedRef.current ||
				currentProductIdRef.current !== productId );

		if ( shouldCreateBlocks ) {
			// Clear existing blocks first
			clearInnerBlocks();

			// Create new blocks
			createProductBlocks(
				relatedProducts,
				attributes.maxProducts,
				attributes
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
		attributes.maxProducts, // Only watch maxProducts for structure changes
		clearInnerBlocks,
		createProductBlocks,
	] );

	// Update blocks when dependencies change (for structure changes only)
	useEffect( () => {
		updateBlocks();
	}, [ updateBlocks ] );

	// Separate effect for updating existing block attributes (non-structural changes)
	useEffect( () => {
		// Only update attributes if blocks already exist and we're not creating new ones
		if (
			blocksInsertedRef.current &&
			! isProcessingRef.current &&
			relatedProducts.length > 0
		) {
			updateExistingProductBlocks( attributes );
		}
	}, [
		// Only watch attributes that should update existing blocks without recreating
		attributes.showAddToCart,
		attributes.buttonText,
		attributes.showCardHoverShadow,
		attributes.showCardHoverJump,
		attributes.hoverMediaTag,
		attributes.translations,
		updateExistingProductBlocks,
	] );

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
			renderAppender: false,
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
						value={ maxProducts }
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

				{ /* Product Appearance Settings */ }
				<PanelBody
					title={ __( 'Product Appearance', 'peaches' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Show Add to Cart Button',
							'peaches-bootstrap-ecwid-blocks'
						) }
						checked={ showAddToCart }
						onChange={ ( value ) =>
							setAttributes( { showAddToCart: value } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					{ showAddToCart && (
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Button Text', 'peaches' ) }
							value={ translatedButtonText }
							onChange={ handleButtonTextChange }
							placeholder={ __( 'Add to cart', 'peaches' ) }
							help={
								currentLang &&
								currentLang !== defaultLanguage &&
								translations.buttonText?.[ currentLang ]
									? __( 'Using translated text', 'peaches' )
									: currentLang &&
									  currentLang !== defaultLanguage
									? __(
											'Using default language text - add translation above',
											'peaches'
									  )
									: __( 'Default language text', 'peaches' )
							}
						/>
					) }

					<ToggleControl
						__nextHasNoMarginBottom
						className="pt-2"
						label={ __(
							'Show Card shadow on hover',
							'peaches-bootstrap-ecwid-blocks'
						) }
						checked={ showCardHoverShadow }
						onChange={ ( value ) =>
							setAttributes( { showCardHoverShadow: value } )
						}
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						className="pt-2"
						label={ __(
							'Show Card jump effect on hover',
							'peaches-bootstrap-ecwid-blocks'
						) }
						checked={ showCardHoverJump }
						onChange={ ( value ) =>
							setAttributes( { showCardHoverJump: value } )
						}
					/>

					{ isLoadingTags && (
						<div className="loading-indicator">
							<Spinner />
							<span>
								{ __(
									'Loading media tags…',
									'peaches-bootstrap-ecwid-blocks'
								) }
							</span>
						</div>
					) }

					{ ! isLoadingTags && (
						<>
							<SelectControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __(
									'Hover Image Media Tag',
									'peaches-bootstrap-ecwid-blocks'
								) }
								value={ hoverMediaTag }
								options={ getGroupedMediaTagOptions }
								onChange={ ( value ) => {
									if ( ! value.startsWith( 'category_' ) ) {
										setAttributes( {
											hoverMediaTag: value,
										} );
									}
								} }
								help={ __(
									'Select a media tag to display when hovering over the product image. Leave empty for no hover effect.',
									'peaches-bootstrap-ecwid-blocks'
								) }
							/>

							{ getSelectedHoverTagInfo() && (
								<div className="mt-2 p-3 bg-light border rounded">
									<div className="d-flex align-items-center gap-2 mb-2">
										<i className="dashicons dashicons-format-image"></i>
										<strong>
											{ getSelectedHoverTagInfo().label }
										</strong>
									</div>
									{ getSelectedHoverTagInfo().description && (
										<p className="text-sm text-muted mb-0">
											{
												getSelectedHoverTagInfo()
													.description
											}
										</p>
									) }
								</div>
							) }
						</>
					) }
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
