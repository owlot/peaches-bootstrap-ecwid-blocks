/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	Button,
	Notice,
	Spinner,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useEffect, useState, useMemo, useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

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

/**
 * Product Edit component
 *
 * @param {Object}   props               Component props
 * @param {Object}   props.attributes    Block attributes
 * @param {Function} props.setAttributes Function to set block attributes
 *
 * @return {JSX.Element} React component
 */
function ProductEdit( props ) {
	const { attributes, setAttributes, clientId } = props;
	const {
		id,
		showAddToCart,
		showCardHoverShadow,
		showCardHoverJump,
		hoverMediaTag,
		buttonText = __( 'Add to cart', 'peaches' ),
		translations = {},
	} = attributes;

	// State for product data and loading
	const [ productData, setProductData ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ mediaTags, setMediaTags ] = useState( [] );
	const [ isLoadingTags, setIsLoadingTags ] = useState( false );
	const [ hoverImageUrl, setHoverImageUrl ] = useState( '' );

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

	// Check if this block is a child of a related-products block
	const isChildOf = useSelect(
		( select ) => {
			const { getBlockParents, getBlock } = select( 'core/block-editor' );

			// Check if block has the metadata flag
			if ( attributes._isChildOf ) {
				return true;
			}

			// Also check parent hierarchy for related-products block
			const parents = getBlockParents( clientId );

			for ( const parentId of parents ) {
				const parentBlock = getBlock( parentId );
				if (
					parentBlock?.name ===
					'peaches/ecwid-product-related-products'
				) {
					return true;
				}
				if ( parentBlock?.name === 'peaches/ecwid-category-products' ) {
					return true;
				}
			}

			return false;
		},
		[ clientId, attributes._isChildOf ]
	);

	const computedClassName = useMemo(
		() =>
			clsx(
				'card h-100 border-0',
				{
					'hover-jump': attributes.showCardHoverJump,
					'hover-shadow': attributes.showCardHoverShadow,
				},
				computeClassName( attributes, SUPPORTED_SETTINGS )
			),
		[ attributes ]
	);

	// Update the computedClassName attribute when it changes
	useEffect( () => {
		setAttributes( { computedClassName } );
	}, [ computedClassName, setAttributes ] );

	// Prepare block props
	const blockProps = useBlockProps( {
		className: computedClassName,
	} );

	/**
	 * Get current language for API requests
	 *
	 * @return {string} Current language code
	 */
	const getCurrentLanguage = () => {
		// Try to get from Polylang or WPML
		if ( typeof window.pll_current_language !== 'undefined' ) {
			return window.pll_current_language;
		}

		// Check HTML lang attribute
		const htmlLang = document.documentElement.lang;
		if ( htmlLang ) {
			// Extract language code (e.g., 'en' from 'en-US')
			return htmlLang.split( '-' )[ 0 ];
		}

		return '';
	};

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

	// Load hover image when hover tag or product changes
	useEffect( () => {
		if ( ! hoverMediaTag || ! id ) {
			setHoverImageUrl( '' );
			return;
		}

		// Fetch hover image URL
		const fetchHoverImage = async () => {
			try {
				const response = await fetch(
					`/wp-json/peaches/v1/product-media/${ id }/tag/${ hoverMediaTag }`,
					{
						headers: { Accept: 'application/json' },
						credentials: 'same-origin',
					}
				);

				if ( response.ok ) {
					const data = await response.json();
					if ( data && data.success && data.data ) {
						setHoverImageUrl( data.data.url );
					} else {
						setHoverImageUrl( '' );
					}
				} else {
					setHoverImageUrl( '' );
				}
			} catch ( fetchError ) {
				console.error( 'Failed to load hover image:', fetchError );
				setHoverImageUrl( '' );
			}
		};

		fetchHoverImage();
	}, [ hoverMediaTag, id ] );

	// Load product data when ID changes using REST API
	useEffect( () => {
		if ( ! id ) {
			setProductData( null );
			setError( null );
			return;
		}

		setIsLoading( true );
		setError( null );

		const currentLanguage = getCurrentLanguage();
		const apiUrl = `/wp-json/peaches/v1/products/${ id }`;
		const urlWithLang = currentLanguage
			? `${ apiUrl }?lang=${ encodeURIComponent( currentLanguage ) }`
			: apiUrl;

		// Use REST API directly - same as other blocks
		fetch( urlWithLang, {
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
							'Product not found',
							'peaches-bootstrap-ecwid-blocks'
						)
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
				} else {
					setError(
						__(
							'Failed to fetch product data: Invalid response',
							'peaches-bootstrap-ecwid-blocks'
						)
					);
				}
				setIsLoading( false );
			} )
			.catch( ( fetchError ) => {
				setIsLoading( false );
				console.error( 'Fetch Error:', {
					error: fetchError.message,
					productId: id,
				} );
				setError(
					__(
						'Failed to load product data',
						'peaches-bootstrap-ecwid-blocks'
					)
				);
			} );
	}, [ id ] );

	/**
	 * Handle Ecwid product selection
	 *
	 * @param {Object} params - Selection parameters
	 */
	const handleProductSelect = ( params ) => {
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
			return __(
				'Price not available',
				'peaches-bootstrap-ecwid-blocks'
			);
		}

		// Simple formatting - you might want to add currency symbols
		return `€ ${ parseFloat( productData.price ).toFixed( 2 ) }`;
	};

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
			translations.buttonText[ currentLang ]
		) {
			return translations.buttonText[ currentLang ];
		}

		// Fallback to old format for backward compatibility (lang: 'text')
		if ( translations[ currentLang ] ) {
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
					setAttributes( {
						translations: {
							...translations,
							buttonText: {
								...( translations.buttonText || {} ),
								[ currentLang ]: newButtonText,
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

	return (
		<>
			{ isChildOf && (
				<InspectorControls>
					<Notice
						status="info"
						isDismissible={ false }
						className="m-3"
					>
						<strong>
							{ __( 'Managed by Parent Block', 'peaches' ) }
						</strong>
						<p>
							{ __(
								'This product block is managed by a parent block. To edit product settings, select the parent block instead.',
								'peaches'
							) }
						</p>
					</Notice>
				</InspectorControls>
			) }
			{ ! isChildOf && (
				<InspectorControls>
					<PanelBody
						title={ __(
							'Product Settings',
							'peaches-bootstrap-ecwid-blocks'
						) }
						initialOpen={ true }
					>
						{ ! id && ! isLoading && (
							<div className="product-placeholder">
								<p>
									{ __(
										'Please select a product to display.',
										'peaches-bootstrap-ecwid-blocks'
									) }
								</p>
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
										'Select Product',
										'peaches-bootstrap-ecwid-blocks'
									) }
								</Button>
							</div>
						) }

						{ id && (
							<div className="product-selection">
								<p>
									<strong>
										{ __(
											'Selected Product ID:',
											'peaches-bootstrap-ecwid-blocks'
										) }
									</strong>{ ' ' }
									{ id }
								</p>

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
										'peaches-bootstrap-ecwid-blocks'
									) }
								</Button>
							</div>
						) }

						{ isLoading && (
							<div className="loading-indicator">
								<Spinner />
								<span>
									{ __(
										'Loading product data…',
										'peaches-bootstrap-ecwid-blocks'
									) }
								</span>
							</div>
						) }

						{ error && (
							<Notice status="error" isDismissible={ false }>
								<p>{ error }</p>
							</Notice>
						) }

						<ToggleControl
							__nextHasNoMarginBottom
							className="pt-2"
							label={ __(
								'Show Add to Cart Button',
								'peaches-bootstrap-ecwid-blocks'
							) }
							checked={ showAddToCart }
							onChange={ ( value ) =>
								setAttributes( { showAddToCart: value } )
							}
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
										? __(
												'Using translated text',
												'peaches'
										  )
										: currentLang &&
										  currentLang !== defaultLanguage
										? __(
												'Using default language text - add translation above',
												'peaches'
										  )
										: __(
												'Default language text',
												'peaches'
										  )
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
										if (
											! value.startsWith( 'category_' )
										) {
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
												{
													getSelectedHoverTagInfo()
														.label
												}
											</strong>
										</div>
										{ getSelectedHoverTagInfo()
											.description && (
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
						setAttributes={ setAttributes }
						attributes={ attributes }
						supportedSettings={ SUPPORTED_SETTINGS }
					/>
				</InspectorControls>
			) }

			<div { ...blockProps }>
				{ isLoading && (
					<div className="text-center my-3">
						<Spinner />
						<span className="ms-2">
							{ __(
								'Loading product…',
								'peaches-bootstrap-ecwid-blocks'
							) }
						</span>
					</div>
				) }

				{ error && (
					<Notice status="error" isDismissible={ false }>
						<p>{ error }</p>
					</Notice>
				) }

				{ ! id && ! isLoading && (
					<div className="product-placeholder">
						<p>
							{ __(
								'Please select a product to display.',
								'peaches-bootstrap-ecwid-blocks'
							) }
						</p>
					</div>
				) }

				{ productData && ! isLoading && ! error && (
					<>
						{ productData.thumbnailUrl && (
							<div className="card-img-top ratio ratio-1x1 product-image-container">
								<img
									src={ productData.thumbnailUrl }
									alt={ productData.name }
									className="object-fit-cover product-image-main visible"
								/>
								{ /* Show hover tag indicator */ }
								{ hoverMediaTag && (
									<div className="position-absolute top-0 start-0 end-0 bottom-0 d-flex align-items-center justify-content-center">
										<div className="text-center bg-white opacity-70 p-2">
											<div className="mb-2">
												<i
													className="dashicons dashicons-format-image"
													style={ {
														fontSize: '24px',
													} }
												></i>
											</div>
											<div className="badge text-dark bg-info mb-1">
												{ __(
													'Hover Effect Active',
													'peaches-bootstrap-ecwid-blocks'
												) }
											</div>
											<div className="small text-muted">
												{ hoverMediaTag }
											</div>
											{ hoverImageUrl && (
												<div className="small text-success mt-1">
													✓{ ' ' }
													{ __(
														'Image Found',
														'peaches-bootstrap-ecwid-blocks'
													) }
												</div>
											) }
											{ ! hoverImageUrl && (
												<div className="bdage text-dark bg-warning small mt-1">
													⚠{ ' ' }
													{ __(
														'No Image',
														'peaches-bootstrap-ecwid-blocks'
													) }
												</div>
											) }
										</div>
									</div>
								) }
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
							{ showAddToCart && (
								<button
									className="add-to-cart btn pe-0"
									title={ translatedButtonText }
									aria-label={ translatedButtonText }
									data-wp-on--click="actions.addToCart"
								></button>
							) }
						</div>
					</>
				) }
			</div>
		</>
	);
}

export default ProductEdit;
