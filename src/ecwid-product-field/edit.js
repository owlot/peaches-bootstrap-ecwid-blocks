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
 * Styles
 */
import './style.scss';

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
		spacings: { gaps: true },
		colors: { background: true, text: true },
		text: {
			fontSize: true,
			fontWeight: true,
			fontStyle: true,
			textDecoration: true,
			textTransform: true,
			lineHeight: true,
		},
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
 * Image size options for product line media
 *
 * @since 0.3.2
 */
const IMAGE_SIZE_OPTIONS = [
	{ label: __( 'Tiny (16px)', 'peaches' ), value: 'tiny' },
	{ label: __( 'Small (32px)', 'peaches' ), value: 'small' },
	{ label: __( 'Medium (48px)', 'peaches' ), value: 'medium' },
	{ label: __( 'Large (64px)', 'peaches' ), value: 'large' },
];

/**
 * Image position options relative to text
 *
 * @since 0.3.2
 */
const IMAGE_POSITION_OPTIONS = [
	{ label: __( 'Before text', 'peaches' ), value: 'before' },
	{ label: __( 'After text', 'peaches' ), value: 'after' },
];

/**
 * Image alignment options relative to text
 *
 * @since 0.3.2
 */
const IMAGE_ALIGNMENT_OPTIONS = [
	{ label: __( 'Top aligned', 'peaches' ), value: 'top' },
	{ label: __( 'Center aligned', 'peaches' ), value: 'center' },
	{ label: __( 'Bottom aligned', 'peaches' ), value: 'bottom' },
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
 * Generate mock product lines for preview
 *
 * Creates fictional product lines to demonstrate how the block will look
 * when actual product lines are assigned. Used when no product is selected
 * or when a product has no lines.
 *
 * @since 0.3.2
 *
 * @return {Array} Array of mock product line objects
 */
const generateMockProductLines = () => {
	return [
		{
			id: 1,
			name: 'Ylang Ylang & Sandalwood',
			line_type: 'fragrance',
			description: 'Exotic floral notes with warm woody undertones',
			media: [
				{ tag: 'logo', attachment_id: 123 },
				{ tag: 'hero_image', attachment_id: 124 },
			],
		},
		{
			id: 2,
			name: 'Ocean Breeze Collection',
			line_type: 'design_collection',
			description: 'Fresh aquatic scents inspired by coastal waters',
			media: [ { tag: 'banner', attachment_id: 125 } ],
		},
		{
			id: 3,
			name: 'Sunset Warmth',
			line_type: 'color_scheme',
			description: 'Rich amber and golden tones',
			media: [],
		},
	];
};

/**
 * Get available media tags from product lines
 *
 * Extracts all unique media tags from the current product lines data.
 *
 * @since 0.3.2
 *
 * @param {Array} productLines - Array of product line objects
 *
 * @return {Array} - Array of media tag options for SelectControl
 */
function getAvailableMediaTags( productLines ) {
	const tags = new Set();

	if ( ! Array.isArray( productLines ) ) {
		return [ { label: __( 'Select media tag…', 'peaches' ), value: '' } ];
	}

	productLines.forEach( ( line ) => {
		if ( line.media && Array.isArray( line.media ) ) {
			line.media.forEach( ( mediaItem ) => {
				if ( mediaItem.tag ) {
					tags.add( mediaItem.tag );
				}
			} );
		}
	} );

	const tagOptions = Array.from( tags )
		.sort()
		.map( ( tag ) => ( {
			label: tag
				.replace( /_/g, ' ' )
				.replace( /\b\w/g, ( l ) => l.toUpperCase() ),
			value: tag,
		} ) );

	return [
		{ label: __( 'Select media tag…', 'peaches' ), value: '' },
		...tagOptions,
	];
}

/**
 * Get image for a specific product line and media tag
 *
 * Searches the line's media array for the specified tag and returns the attachment info.
 *
 * @since 0.3.2
 *
 * @param {Object} line     - Product line object
 * @param {string} mediaTag - Media tag to search for
 *
 * @return {Object|null} - Attachment info or null if not found
 */
const getLineImage = ( line, mediaTag ) => {
	if ( ! line.media || ! Array.isArray( line.media ) || ! mediaTag ) {
		return null;
	}

	return line.media.find( ( item ) => item.tag === mediaTag );
};

/**
 * Render image element for a product line
 *
 * Creates an img element with proper Bootstrap classes for styling.
 *
 * @since 0.3.2
 *
 * @param {Object} line          - Product line object
 * @param {string} imageMediaTag - Media tag to display
 * @param {string} imageSize     - Image size setting
 * @param {string} imagePosition - Image position setting
 *
 * @return {JSX.Element|null} - Image element or null if no image
 */
const renderLineImage = ( line, imageMediaTag, imageSize, imagePosition ) => {
	if ( ! imageMediaTag ) {
		return null;
	}

	const imageInfo = getLineImage( line, imageMediaTag );
	if ( ! imageInfo ) {
		return null;
	}

	const sizeClasses = {
		tiny: 'height-16',
		small: 'height-32',
		medium: 'height-48',
		large: 'height-64',
	};

	const imageClasses = `${ sizeClasses[ imageSize ] || sizeClasses.small } ${
		imagePosition === 'after' ? 'ms-2' : 'me-2'
	}`;

	console.log( 'Using image url', imageInfo );
	return (
		<img
			src={ imageInfo.thumbnail_url }
			alt={
				imageInfo.alt
					? imageInfo.alt
					: line.name || 'Product line image'
			}
			className={ imageClasses }
		/>
	);
};

/**
 * Product Field Edit Component
 *
 * Handles display of various product data types including standard Ecwid fields and custom product lines.
 * Provides a unified interface for product data with Bootstrap styling support.
 *
 * @since 0.1.0
 * @since 0.3.1 Added product lines support with filtering and display options
 * @since 0.3.2 Added image support for product lines
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
		showImage,
		imageMediaTag,
		imageSize,
		imagePosition,
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
		isLoading: productLoading,
		error,
		hasProductDetailAncestor,
		selectedProductId,
		contextProductData,
		openEcwidProductPopup,
		clearSelectedProduct,
	} = useEcwidProductData( context, attributes, setAttributes, clientId );

	const isLoading = useMemo( () => {
		return productLoading || isLoadingLines || isLoadingLineTypes;
	}, [ productLoading, isLoadingLineTypes, isLoadingLines ] );

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
	 */
	const currentLanguage = useMemo( () => {
		return getCurrentLanguageForAPI();
	}, [] );

	/**
	 * Fetch line types from the REST API
	 *
	 * Retrieves all available line types for filtering options.
	 *
	 * @since 0.3.1
	 */
	useEffect( () => {
		if ( ! isLineField ) {
			return;
		}

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
	}, [ isLineField ] );

	/**
	 * Fetch product lines from when test product data changes
	 *
	 * Fetches product line data for the given product ID with language support.
	 * Handles 404 responses gracefully and manages loading/error states.
	 * Uses mock data if no (test) product is set.
	 *
	 * @since 0.3.1
	 */
	useEffect( () => {
		if ( ! isLineField ) {
			return;
		}

		if ( ! productLoading && productData?.id ) {
			setIsLoadingLines( true );
			setLinesError( null );

			// Build API URL with language parameter for multilingual sites
			const apiUrl = `/wp-json/peaches/v1/product-lines/${
				productData.id
			}${
				fieldType === 'lines_filtered' ? `/type/${ lineType }` : ''
			}?lang=${ encodeURIComponent( currentLanguage ) }`;

			console.log( 'apiUrl', apiUrl );
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
					console.log( 'responseData', responseData );
					if (
						responseData &&
						responseData.success &&
						responseData.data &&
						Array.isArray( responseData.data )
					) {
						// Fetch media for each line to populate media tags dropdown
						return Promise.all(
							responseData.data.map( ( line ) => {
								return fetch(
									`/wp-json/peaches/v1/product-lines/${ line.id }/media`,
									{
										headers: {
											Accept: 'application/json',
										},
										credentials: 'same-origin',
									}
								)
									.then( ( mediaResponse ) => {
										if ( mediaResponse.ok ) {
											return mediaResponse.json();
										}
										return { success: false, data: [] };
									} )
									.then( ( mediaData ) => {
										line.media = mediaData.success
											? mediaData.data
											: [];
										return line;
									} )
									.catch( ( error ) => {
										console.error(
											`Error fetching media for line ${ line.id }:`,
											error
										);
										line.media = [];
										return line;
									} );
							} )
						);
					} else if ( responseData === null ) {
						// 404 case
						return [];
					}
					return [];
				} )
				.then( ( linesWithMedia ) => {
					setProductLines( linesWithMedia );
				} )
				.catch( ( fetchError ) => {
					console.error( 'Error loading product lines:', fetchError );
					setLinesError(
						__( 'Failed to load product lines', 'peaches' )
					);
					setProductLines( [] );
				} )
				.finally( () => {
					setIsLoadingLines( false );
				} );
		} else {
			setProductLines( generateMockProductLines() );
			setLinesError( null );
		}
	}, [ productData?.id, productLoading, isLineField, lineType, fieldType ] );

	/**
	 * Get preview text for non-line field types
	 *
	 * Uses the existing product field value utility function to get formatted
	 * field values for display in the editor preview.
	 *
	 * @since 0.3.1
	 *
	 * @return {string} Formatted field value for display
	 */
	const getPreviewText = () => {
		return getProductFieldValue( productData, fieldType, customFieldKey );
	};

	/**
	 * Computed state: Get complete decoded line content
	 *
	 * Returns the complete line content (name + description) with proper
	 * HTML entity decoding and separator handling for badges/spans.
	 *
	 * @param  line
	 * @since 0.3.1
	 *
	 * @return {string} - complete decoded line content
	 */
	const decodedLineContent = ( line ) => {
		let text = decodeHtmlEntities( line.name || '' );

		if ( showLineDescriptions && line.description ) {
			text +=
				descriptionSeparator + decodeHtmlEntities( line.description );
		}

		return text;
	};

	/**
	 * Get preview JSX for product lines with proper styling and images
	 *
	 * Renders product lines based on current display mode, filters, and settings.
	 * Handles loading states, errors, and empty results gracefully.
	 * Applies Bootstrap styling and user color preferences.
	 * Includes image support when enabled.
	 *
	 * @since 0.3.1
	 * @since 0.3.2 Added image support
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

		let filteredLines = productLines;
		// Apply maximum lines limit (0 = unlimited)
		if ( maxLines > 0 ) {
			filteredLines = productLines.slice( 0, maxLines );
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
					media: filteredLines[ 0 ]?.media,
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
						<li
							className={ `d-flex ${ computedClassName }` }
							key={ line.id }
						>
							{ showImage &&
								imagePosition === 'before' &&
								renderLineImage(
									line,
									imageMediaTag,
									imageSize,
									imagePosition
								) }
							{ React.createElement(
								htmlTag,
								{},
								decodedLineContent( line )
							) }
							{ showImage &&
								imagePosition === 'after' &&
								renderLineImage(
									line,
									imageMediaTag,
									imageSize,
									imagePosition
								) }
						</li>
					) ) }
				</ul>
			);
		}

		// Render badges or standard spans
		const className =
			displayMode === 'badges'
				? `badge ${ computedClassName }`
				: `${ computedClassName }`;

		return (
			<div>
				{ filteredLines.map( ( line ) =>
					React.createElement(
						htmlTag,
						{ className, key: line.id },
						showImage &&
							imagePosition === 'before' &&
							renderLineImage(
								line,
								imageMediaTag,
								imageSize,
								imagePosition
							),
						decodedLineContent( line ),
						showImage &&
							imagePosition === 'after' &&
							renderLineImage(
								line,
								imageMediaTag,
								imageSize,
								imagePosition
							)
					)
				) }
			</div>
		);
	};

	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				{ /* Product Selection Panel - unified across all product blocks */ }
				<ProductSelectionPanel
					productData={ productData }
					isLoading={ productLoading }
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
						onChange={ ( value ) => {
							setAttributes( { fieldType: value } );
							// Reset line-specific settings when switching away from lines
							if (
								value !== 'lines' &&
								value !== 'lines_filtered'
							) {
								setAttributes( {
									lineType: '',
									displayMode: 'badges',
									showLineDescriptions: false,
									showImage: false,
								} );
							}
						} }
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
						help={ __(
							'Choose the HTML element for semantic markup',
							'ecwid-shopping-cart'
						) }
					/>

					{ /* Line-specific Controls */ }
					{ isLineField && (
						<>
							<SelectControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
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

							{ fieldType === 'lines_filtered' && (
								<SelectControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									label={ __(
										'Filter by Line Type',
										'peaches'
									) }
									value={ lineType }
									options={ [
										{
											label: __(
												'All line types',
												'peaches'
											),
											value: '',
										},
										...lineTypes.map( ( type ) => ( {
											label: type,
											value: type,
										} ) ),
									] }
									onChange={ ( value ) =>
										setAttributes( { lineType: value } )
									}
									help={ __(
										'Only show lines of the selected type',
										'peaches'
									) }
								/>
							) }

							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Show Line Descriptions',
									'peaches'
								) }
								checked={ showLineDescriptions }
								onChange={ ( value ) =>
									setAttributes( {
										showLineDescriptions: value,
									} )
								}
								help={ __(
									'Include line descriptions with names',
									'peaches'
								) }
							/>

							{ showLineDescriptions && (
								<TextControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									label={ __(
										'Description Separator',
										'peaches'
									) }
									value={ descriptionSeparator }
									onChange={ ( value ) =>
										setAttributes( {
											descriptionSeparator: value,
										} )
									}
									help={ __(
										'Text between line name and description',
										'peaches'
									) }
								/>
							) }

							<RangeControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __( 'Maximum Lines', 'peaches' ) }
								value={ maxLines }
								onChange={ ( value ) =>
									setAttributes( { maxLines: value } )
								}
								min={ 0 }
								max={ 20 }
								help={ __(
									'Limit number of lines shown (0 = unlimited)',
									'peaches'
								) }
							/>

							{ displayMode === 'inline' && (
								<TextControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									label={ __( 'Line Separator', 'peaches' ) }
									value={ lineSeparator }
									onChange={ ( value ) =>
										setAttributes( {
											lineSeparator: value,
										} )
									}
									help={ __(
										'Text between lines in inline mode',
										'peaches'
									) }
								/>
							) }
						</>
					) }
				</PanelBody>

				{ /* Image Settings Panel - only for product lines */ }
				{ isLineField && (
					<PanelBody
						title={ __( 'Image Settings', 'peaches' ) }
						initialOpen={ false }
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Show Images', 'peaches' ) }
							checked={ showImage }
							onChange={ ( value ) => {
								setAttributes( { showImage: value } );
								if ( ! value ) {
									setAttributes( {
										imageMediaTag: '',
										imageSize: 'small',
										imagePosition: 'before',
									} );
								}
							} }
							help={ __(
								'Display media images for product lines',
								'peaches'
							) }
						/>

						{ showImage && (
							<>
								<SelectControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									label={ __( 'Media Tag', 'peaches' ) }
									value={ imageMediaTag }
									options={ getAvailableMediaTags(
										productLines
									) }
									onChange={ ( value ) =>
										setAttributes( {
											imageMediaTag: value,
										} )
									}
									help={ __(
										'Select which media tag to display as icon',
										'peaches'
									) }
								/>

								<SelectControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									label={ __( 'Image Size', 'peaches' ) }
									value={ imageSize }
									options={ IMAGE_SIZE_OPTIONS }
									onChange={ ( value ) =>
										setAttributes( { imageSize: value } )
									}
									help={ __(
										'Choose the thumbnail size',
										'peaches'
									) }
								/>

								<SelectControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									label={ __( 'Image Position', 'peaches' ) }
									value={ imagePosition }
									options={ IMAGE_POSITION_OPTIONS }
									onChange={ ( value ) =>
										setAttributes( {
											imagePosition: value,
										} )
									}
									help={ __(
										'Position relative to text content',
										'peaches'
									) }
								/>
							</>
						) }
					</PanelBody>
				) }

				{ /* Bootstrap Settings Panel */ }
				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			{ isLoading && (
				<div { ...blockProps }>
					{ __( 'Loading…', 'ecwid-shopping-cart' ) }
				</div>
			) }

			{ ! isLoading && error && <div { ...blockProps }>{ error }</div> }

			{ ! isLoading && ! error && (
				<div { ...blockProps }>
					{ isLineField ? (
						<>{ getProductLinesPreview() }</>
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
			) }
		</>
	);
}

export default ProductFieldEdit;
