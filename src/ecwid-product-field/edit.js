/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useMemo, useState, useEffect } from '@wordpress/element';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	RangeControl,
} from '@wordpress/components';

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
	getCurrentLanguageForAPI,
} from '../utils/ecwid-product-utils';

/**
 * Bootstrap settings configuration for the product field block
 *
 * Defines which Bootstrap utility classes are available in the settings panel.
 *
 * @since 0.3.1
 */
const SUPPORTED_SETTINGS = {
	responsive: {
		display: {
			opacity: true,
			display: true,
		},
		placements: {
			zIndex: true,
			textAlign: true,
			justifyContent: true,
			alignSelf: true,
			alignItems: true,
		},
		spacings: {
			margin: true,
			padding: true,
		},
	},
	general: {
		border: { rounded: true, color: true, width: true },
		colors: { background: true, text: true },
	},
};

/**
 * Available field types for product field display
 *
 * Includes both standard Ecwid product fields and custom product lines.
 *
 * @since 0.3.1 - Added product lines support
 */
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
	{ label: __( 'Product Lines', 'peaches' ), value: 'lines' },
	{
		label: __( 'Product Lines (Filtered)', 'peaches' ),
		value: 'lines_filtered',
	},
];

/**
 * Available HTML tags for rendering product fields
 *
 * Used for semantic markup when displaying non-line field types.
 */
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
 * Display modes for product lines
 *
 * Different visual styles for rendering product line collections.
 *
 * @since 0.3.1
 */
const DISPLAY_MODES = [
	{ label: __( 'Badges', 'peaches' ), value: 'badges' },
	{ label: __( 'List', 'peaches' ), value: 'list' },
	{ label: __( 'Inline, seperated', 'peaches' ), value: 'inline' },
];

/**
 * Decode HTML entities in text content
 *
 * Safely converts HTML entities like &amp; to their proper characters.
 *
 * @since 0.3.1
 *
 * @param {string} text - Text content that may contain HTML entities
 *
 * @return {string} - Decoded text content
 */
function decodeHtmlEntities( text ) {
	if ( ! text || typeof text !== 'string' ) {
		return '';
	}

	try {
		const textarea = document.createElement( 'textarea' );
		textarea.innerHTML = text;
		return textarea.value;
	} catch ( error ) {
		console.warn( 'Error decoding HTML entities:', error );
		return text;
	}
}

/**
 * Product Field Edit Component
 *
 * Main edit component for the product field block. Handles display of various
 * product data types including standard Ecwid fields and custom product lines.
 * Provides a unified interface for product data with Bootstrap styling support.
 *
 * @since 0.1.0
 * @since 0.3.1 Added product lines support with filtering and display options
 *
 * @param {Object}   props               - Component props
 * @param {Object}   props.attributes    - Block attributes
 * @param {Function} props.setAttributes - Function to update block attributes
 * @param {Object}   props.context       - Block context from parent blocks
 * @param {string}   props.clientId      - Unique identifier for this block instance
 *
 * @return {JSX.Element} Edit component JSX
 */
function ProductFieldEdit( props ) {
	const { attributes, setAttributes, context, clientId } = props;
	const {
		fieldType,
		htmlTag,
		customFieldKey,
		lineType,
		displayMode,
		showLineDescriptions,
		maxLines,
		lineSeparator,
		descriptionSeparator,
	} = attributes;

	// State management for product lines functionality
	const [ lineTypes, setLineTypes ] = useState( [] );
	const [ productLines, setProductLines ] = useState( [] );
	const [ isLoadingLineTypes, setIsLoadingLineTypes ] = useState( false );
	const [ isLoadingLines, setIsLoadingLines ] = useState( false );
	const [ linesError, setLinesError ] = useState( null );

	// Use unified product data hook for consistent product data handling
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

	/**
	 * Compute Bootstrap classes based on block attributes
	 *
	 * Memoized to prevent unnecessary recalculations on each render.
	 *
	 * @since 0.3.1
	 */
	const computedClassName = useMemo( () => {
		return computeClassName( attributes );
	}, [ attributes ] );

	/**
	 * Check if current field type is product lines related
	 *
	 * Used to conditionally show line-specific controls and rendering.
	 *
	 * @since 0.3.1
	 *
	 * @return {boolean} True if field type is lines or lines_filtered
	 */
	const isLineField = useMemo( () => {
		return fieldType === 'lines' || fieldType === 'lines_filtered';
	}, [ fieldType ] );

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

	// Get block props for wrapper element
	const blockProps = useBlockProps();

	/**
	 * Load available line types from REST API
	 *
	 * Fetches all unique line types from the product_line taxonomy to populate
	 * the line type filter dropdown. Handles loading states and error recovery.
	 *
	 * @since 0.3.1
	 *
	 * @return {Promise<void>} Async function that updates lineTypes state
	 */
	const loadLineTypes = () => {
		setIsLoadingLineTypes( true );
		setLinesError( null );

		fetch( '/wp-json/peaches/v1/line-types', {
			headers: {
				Accept: 'application/json',
			},
			credentials: 'same-origin',
		} )
			.then( ( response ) => {
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
					setLineTypes( responseData.data );
				} else {
					throw new Error( 'Invalid response format' );
				}
				setIsLoadingLineTypes( false );
			} )
			.catch( ( fetchError ) => {
				console.error( 'Error loading line types:', fetchError );
				setLinesError( __( 'Failed to load line types', 'peaches' ) );
				setIsLoadingLineTypes( false );
			} );
	};

	/**
	 * Load product lines from REST API for a specific product
	 *
	 * Fetches product line data for the given product ID with language support.
	 * Handles 404 responses gracefully and manages loading/error states.
	 *
	 * @since 0.3.1
	 *
	 * @param {number} productId - Ecwid product ID to fetch lines for
	 *
	 * @return {Promise<void>} Async function that updates productLines state
	 */
	const loadProductLines = async ( productId ) => {
		if ( ! productId ) {
			setProductLines( [] );
			return;
		}

		setIsLoadingLines( true );
		setLinesError( null );

		// Build API URL with language parameter for multilingual sites
		const apiUrl = `/wp-json/peaches/v1/product-lines/${ productId }?lang=${ encodeURIComponent(
			currentLanguage
		) }`;

		fetch( apiUrl, {
			headers: {
				Accept: 'application/json',
			},
			credentials: 'same-origin',
		} )
			.then( ( response ) => {
				// Handle 404 as empty result (product has no lines)
				if ( response.status === 404 ) {
					setProductLines( [] );
					setIsLoadingLines( false );
					return;
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
					setProductLines( responseData.data );
				} else {
					setProductLines( [] );
				}
				setIsLoadingLines( false );
			} )
			.catch( ( fetchError ) => {
				console.error( 'Error loading product lines:', fetchError );
				setLinesError(
					__( 'Failed to load product lines', 'peaches' )
				);
				setProductLines( [] );
				setIsLoadingLines( false );
			} );
	};

	/**
	 * Effect: Load line types when field type changes to lines-related
	 *
	 * Automatically fetches available line types when user selects a product lines
	 * field type to populate the filter dropdown.
	 *
	 * @since 0.3.1
	 */
	useEffect( () => {
		if ( fieldType === 'lines' || fieldType === 'lines_filtered' ) {
			loadLineTypes();
		}
	}, [ fieldType ] );

	/**
	 * Effect: Load product lines when product or settings change
	 *
	 * Fetches product line data when:
	 * - Field type is set to lines/lines_filtered
	 * - Product ID changes (from selection or context)
	 * - Language changes (for multilingual sites)
	 *
	 * @since 0.3.1
	 */
	useEffect( () => {
		if ( fieldType === 'lines' || fieldType === 'lines_filtered' ) {
			const productId = selectedProductId || productData?.id;
			if ( productId ) {
				loadProductLines( productId );
			}
		}
	}, [ fieldType, selectedProductId, productData?.id, currentLanguage ] );

	/**
	 * Get preview text for standard (non-line) field types
	 *
	 * Uses the utility function to extract and format standard Ecwid product
	 * fields like title, price, description, etc.
	 *
	 * @since 0.3.1
	 *
	 * @return {string} Formatted field value for display
	 */
	const getPreviewText = () => {
		return getProductFieldValue( productData, fieldType, customFieldKey );
	};

	/**
	 * Get preview JSX for product lines with proper styling
	 *
	 * Renders product lines based on current display mode, filters, and settings.
	 * Handles loading states, errors, and empty results gracefully.
	 * Applies Bootstrap styling and user color preferences.
	 *
	 * @since 0.3.1
	 *
	 * @return {JSX.Element|string} Rendered product lines or status message
	 */
	const getProductLinesPreview = () => {
		// Handle loading state
		if ( isLoadingLines ) {
			return __( 'Loading product lines…', 'peaches' );
		}

		// Handle error state
		if ( linesError ) {
			return linesError;
		}

		// Handle empty data
		if ( productLines.length === 0 ) {
			return __( 'No product lines found', 'peaches' );
		}

		// Filter lines by type if specified (filtered mode only)
		let filteredLines = productLines;
		if ( fieldType === 'lines_filtered' && lineType ) {
			filteredLines = productLines.filter(
				( line ) => line.line_type === lineType
			);
		}

		// Apply maximum lines limit (0 = unlimited)
		if ( maxLines > 0 ) {
			filteredLines = filteredLines.slice( 0, maxLines );
		}

		// Special handling for inline mode - consolidate into single element
		if ( displayMode === 'inline' ) {
			const lines = filteredLines
				.map( ( line ) => {
					let text = decodeHtmlEntities( line.name );
					if ( showLineDescriptions && line.description ) {
						text +=
							descriptionSeparator +
							decodeHtmlEntities( line.description );
					}
					return text;
				} )
				.join( lineSeparator || ', ' );
			filteredLines = [
				{
					id: 0,
					name: lines,
				},
			];
		}

		// Handle empty filtered results
		if ( filteredLines.length === 0 ) {
			return __( 'No matching lines found', 'peaches' );
		}

		// Render based on display mode
		if ( displayMode === 'list' ) {
			return (
				<ul className="list-unstyled">
					{ filteredLines.map( ( line ) => (
						<li className={ computedClassName } key={ line.id }>
							{ decodeHtmlEntities( line.name ) }
							{ showLineDescriptions && line.description && (
								<>
									{ descriptionSeparator }
									{ decodeHtmlEntities( line.description ) }
								</>
							) }
						</li>
					) ) }
				</ul>
			);
		}

		// Render badges or standard spans
		const className =
			displayMode === 'badges'
				? `${ computedClassName } badge`
				: computedClassName;

		return (
			<>
				{ filteredLines.map( ( line ) =>
					React.createElement(
						htmlTag,
						{ className, key: line.id },
						decodeHtmlEntities( line.name ),
						showLineDescriptions && line.description && (
							<>
								{ descriptionSeparator }
								{ decodeHtmlEntities( line.description ) }
							</>
						)
					)
				) }
			</>
		);
	};

	return (
		<>
			<InspectorControls>
				{ /* Product Selection Panel - unified across all product blocks */ }
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

				{ /* Field Type Selection Panel */ }
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

					{ /* Custom Field Key Input - only for custom field type */ }
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

					{ /* HTML Tag Selection - available for all field types */ }
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

				{ /* Line Filter Options - only for filtered lines mode */ }
				{ fieldType === 'lines_filtered' && (
					<PanelBody
						title={ __( 'Line Filter Options', 'peaches' ) }
						initialOpen={ true }
					>
						<SelectControl
							label={ __( 'Line Type', 'peaches' ) }
							value={ lineType }
							options={ [
								{
									label: __( 'All Types', 'peaches' ),
									value: '',
								},
								...lineTypes.map( ( type ) => ( {
									label: type
										.split( '_' )
										.map(
											( word ) =>
												word.charAt( 0 ).toUpperCase() +
												word.slice( 1 )
										)
										.join( ' ' ),
									value: type,
								} ) ),
							] }
							onChange={ ( value ) =>
								setAttributes( { lineType: value } )
							}
							help={
								isLoadingLineTypes
									? __( 'Loading line types…', 'peaches' )
									: __(
											'Filter which type of product lines to display',
											'peaches'
									  )
							}
							disabled={ isLoadingLineTypes }
						/>

						<RangeControl
							label={ __( 'Maximum Lines', 'peaches' ) }
							value={ maxLines }
							onChange={ ( value ) =>
								setAttributes( { maxLines: value } )
							}
							min={ 0 }
							max={ 10 }
							help={
								maxLines === 0
									? __(
											'Show all lines (unlimited)',
											'peaches'
									  )
									: sprintf(
											__(
												'Show maximum %d lines',
												'peaches'
											),
											maxLines
									  )
							}
						/>
					</PanelBody>
				) }

				{ /* Display Options - for all line field types */ }
				{ isLineField && (
					<PanelBody
						title={ __( 'Display Options', 'peaches' ) }
						initialOpen={ true }
					>
						<SelectControl
							label={ __( 'Display Mode', 'peaches' ) }
							value={ displayMode }
							options={ DISPLAY_MODES }
							onChange={ ( value ) =>
								setAttributes( { displayMode: value } )
							}
							help={ __(
								'Choose how product lines are displayed',
								'peaches'
							) }
						/>

						{ displayMode === 'inline' && (
							<TextControl
								label={ __( 'Separator', 'peaches' ) }
								value={ lineSeparator }
								onChange={ ( value ) =>
									setAttributes( { lineSeparator: value } )
								}
								help={ __(
									'Character(s) to separate line names (e.g., ", " or " | " or " - ")',
									'peaches'
								) }
								placeholder=", "
							/>
						) }

						<ToggleControl
							label={ __( 'Show Descriptions', 'peaches' ) }
							checked={ showLineDescriptions }
							onChange={ ( value ) =>
								setAttributes( { showLineDescriptions: value } )
							}
							help={ __(
								'Display line descriptions when available',
								'peaches'
							) }
						/>

						{ showLineDescriptions && (
							<TextControl
								label={ __( 'Separator', 'peaches' ) }
								value={ descriptionSeparator }
								onChange={ ( value ) =>
									setAttributes( {
										descriptionSeparator: value,
									} )
								}
								help={ __(
									'Character(s) to separate description from name (e.g., ", " or " | " or " - ")',
									'peaches'
								) }
								placeholder=" | "
							/>
						) }
					</PanelBody>
				) }

				{ /* Bootstrap Settings Panel - styling controls */ }
				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			{ /* Block Content - renders based on field type */ }
			<div { ...blockProps }>
				{ isLineField ? (
					<>
						{ /* Product Lines Rendering - shows exactly as frontend will */ }
						{ getProductLinesPreview() }
					</>
				) : (
					<>
						{ fieldType === 'description' &&
						productData?.description
							? React.createElement( htmlTag, {
									className: computedClassName,
									dangerouslySetInnerHTML: {
										__html: productData.description,
									},
							  } )
							: React.createElement(
									htmlTag,
									{ className: computedClassName },
									getPreviewText()
							  ) }
					</>
				) }
			</div>
		</>
	);
}

export default ProductFieldEdit;
