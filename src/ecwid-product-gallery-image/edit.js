/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import { useMemo, useState, useEffect, useCallback } from '@wordpress/element';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	Notice,
	Spinner,
	Button,
	Flex,
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
} from '../utils/ecwid-product-utils';

// Content block settings
const SUPPORTED_SETTINGS = {
	responsive: {
		sizes: {
			ratio: true,
			rowCols: true,
		},
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
			gutter: true,
		},
	},
	general: {
		border: {
			rounded: true,
			display: true,
		},
		sizes: {
			width: true,
			height: true,
		},
		spacings: {
			gaps: true,
		},
	},
};

/**
 * Helper Functions
 */

/**
 * Get media type label from tag data
 *
 * @param {Object} tagData - Tag data object
 *
 * @return {string} - Human readable media type label
 */
function getMediaTypeLabel( tagData ) {
	if ( ! tagData ) {
		return __( 'Unknown', 'ecwid-shopping-cart' );
	}

	const mediaType = tagData.expectedMediaType || 'image';

	if ( ! mediaType ) {
		return __( 'Unknown', 'ecwid-shopping-cart' );
	}

	const mediaTypeLabels = {
		image: __( 'Image', 'ecwid-shopping-cart' ),
		video: __( 'Video', 'ecwid-shopping-cart' ),
		audio: __( 'Audio', 'ecwid-shopping-cart' ),
		document: __( 'Document', 'ecwid-shopping-cart' ),
	};

	return mediaTypeLabels[ mediaType ] || mediaType;
}

/**
 * Get media type icon for display
 *
 * @param {string} mediaType - Media type key
 *
 * @return {string} - Dashicon class name
 */
function getMediaTypeIcon( mediaType ) {
	const iconMap = {
		image: 'format-image',
		video: 'format-video',
		audio: 'format-audio',
		document: 'media-document',
	};

	return iconMap[ mediaType ] || 'format-image';
}

/**
 * Get media type color for badges
 *
 * @param {string} mediaType - Media type key
 *
 * @return {string} - CSS color class
 */
function getMediaTypeBadgeClass( mediaType ) {
	const colorMap = {
		image: 'bg-success',
		video: 'bg-danger',
		audio: 'bg-warning text-dark',
		document: 'bg-info',
	};

	return colorMap[ mediaType ] || 'bg-secondary';
}

/**
 * Get media type from tag data
 *
 * @param {Object} tagData - Tag data object
 *
 * @return {string|null} - Media type or null if not found
 */
function getMediaTypeFromTag( tagData ) {
	if ( ! tagData ) {
		return 'image'; // Always return a valid default
	}

	// Try multiple property names for compatibility
	const mediaType =
		tagData.expectedMediaType ||
		tagData.expected_media_type ||
		tagData.mediaType ||
		tagData.expected_type ||
		'image'; // Always fallback to 'image'

	// Ensure we return a valid media type
	const validTypes = [ 'image', 'video', 'audio', 'document' ];
	return validTypes.includes( mediaType ) ? mediaType : 'image';
}

/**
 * Get allowed mime types for media library filtering
 *
 * @param {string} expectedMediaType - Expected media type
 *
 * @return {Array} - Array of allowed mime types
 */
function getAllowedMimeTypes( expectedMediaType ) {
	const mimeTypeMap = {
		image: [
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
			'image/svg+xml',
		],
		video: [ 'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime' ],
		audio: [
			'audio/mpeg',
			'audio/wav',
			'audio/ogg',
			'audio/aac',
			'audio/flac',
		],
		document: [
			'application/pdf',
			'text/plain',
			'application/msword',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		],
	};

	return mimeTypeMap[ expectedMediaType ] || [];
}

/**
 * Validate media against expected type
 *
 * @param {Object} media             - Media object
 * @param {string} expectedMediaType - Expected media type
 *
 * @return {Object} - Validation result with isValid and message
 */
function validateMediaType( media, expectedMediaType ) {
	if ( ! media || ! media.mime ) {
		return {
			isValid: false,
			message: __( 'No media selected', 'ecwid-shopping-cart' ),
		};
	}

	const allowedTypes = getAllowedMimeTypes( expectedMediaType );

	if ( allowedTypes.length === 0 ) {
		return {
			isValid: true,
			message: __( 'Media type is acceptable', 'ecwid-shopping-cart' ),
		};
	}

	const isValid = allowedTypes.some( ( type ) =>
		media.mime.startsWith( type.split( '/' )[ 0 ] )
	);

	if ( isValid ) {
		return {
			isValid: true,
			message: __(
				'Media type matches expectations',
				'ecwid-shopping-cart'
			),
		};
	}

	const expectedLabel = getMediaTypeLabel( { expectedMediaType } );
	return {
		isValid: false,
		message: `Expected ${ expectedLabel } but selected ${
			media.type || 'unknown type'
		}. This may not display correctly.`,
	};
}

/**
 * Determine media type from URL and optional mime type
 *
 * @param {string} url      - Media URL
 * @param {string} mimeType - Optional mime type
 *
 * @return {string} - Media type
 */
function determineMediaType( url, mimeType = '' ) {
	if ( mimeType ) {
		if ( mimeType.startsWith( 'video/' ) ) {
			return 'video';
		}
		if ( mimeType.startsWith( 'audio/' ) ) {
			return 'audio';
		}
		if ( mimeType.startsWith( 'image/' ) ) {
			return 'image';
		}
		if (
			mimeType === 'application/pdf' ||
			mimeType.startsWith( 'text/' ) ||
			mimeType.includes( 'document' ) ||
			mimeType.includes( 'word' )
		) {
			return 'document';
		}
	}

	if ( ! url ) {
		return 'image';
	}

	const pathname = url.split( '?' )[ 0 ];
	const extension = pathname.split( '.' ).pop().toLowerCase();

	const videoExtensions = [
		'mp4',
		'webm',
		'ogg',
		'avi',
		'mov',
		'wmv',
		'flv',
		'm4v',
		'3gp',
		'mkv',
	];
	if ( videoExtensions.includes( extension ) ) {
		return 'video';
	}

	const audioExtensions = [
		'mp3',
		'wav',
		'ogg',
		'aac',
		'flac',
		'm4a',
		'wma',
	];
	if ( audioExtensions.includes( extension ) ) {
		return 'audio';
	}

	const documentExtensions = [
		'pdf',
		'doc',
		'docx',
		'txt',
		'rtf',
		'xls',
		'xlsx',
		'ppt',
		'pptx',
	];
	if ( documentExtensions.includes( extension ) ) {
		return 'document';
	}

	return 'image';
}

/**
 * Create media element for preview
 *
 * @param {string} mediaUrl   - Media URL
 * @param {string} mediaAlt   - Alt text
 * @param {string} mediaType  - Media type
 * @param {Object} attributes - Block attributes for video/audio settings
 *
 * @return {JSX.Element} - Media element
 */
function createMediaElement( mediaUrl, mediaAlt, mediaType, attributes ) {
	const {
		videoAutoplay = false,
		videoMuted = false,
		videoLoop = false,
		videoControls = true,
		audioAutoplay = false,
		audioLoop = false,
		audioControls = true,
	} = attributes;

	switch ( mediaType ) {
		case 'video':
			return (
				<video
					className="media-element w-100 h-100"
					style={ { objectFit: 'cover' } }
					src={ mediaUrl }
					autoPlay={ videoAutoplay }
					muted={ videoMuted }
					loop={ videoLoop }
					controls={ videoControls }
					preload="metadata"
				>
					<p>Your browser does not support the video element.</p>
				</video>
			);

		case 'audio':
			return (
				<audio
					className="media-element w-100"
					src={ mediaUrl }
					autoPlay={ audioAutoplay }
					loop={ audioLoop }
					controls={ audioControls }
					preload="metadata"
				>
					<p>Your browser does not support the audio element.</p>
				</audio>
			);

		case 'document':
			if ( mediaUrl.toLowerCase().includes( '.pdf' ) ) {
				return (
					<iframe
						className="media-element w-100 h-100"
						src={ mediaUrl }
						style={ { minHeight: '400px' } }
						title={ mediaAlt || 'PDF Document' }
					/>
				);
			}
			const fileName =
				mediaUrl.split( '/' ).pop() || mediaAlt || 'Download';
			const fileExtension =
				fileName.split( '.' ).pop().toUpperCase() || 'FILE';

			return (
				<div
					className="media-element w-100 d-flex align-items-center justify-content-center"
					style={ { minHeight: '200px' } }
				>
					<div className="text-center">
						<div className="mb-3">
							<i
								className="dashicons dashicons-media-document"
								style={ {
									fontSize: '4rem',
									color: '#666',
								} }
							></i>
						</div>
						<h5 className="mb-2">{ mediaAlt || fileName }</h5>
						<p className="text-muted mb-3">
							{ fileExtension } Document
						</p>
						<a
							href={ mediaUrl }
							className="btn btn-primary"
							target="_blank"
							rel="noopener noreferrer"
							download
						>
							<i className="dashicons dashicons-download"></i>
							Download
						</a>
					</div>
				</div>
			);

		default: // image
			return (
				<img
					className="media-element img-fluid w-100 h-100"
					style={ { objectFit: 'cover' } }
					src={ mediaUrl }
					alt={ mediaAlt || '' }
				/>
			);
	}
}

/**
 * Product Gallery Image Edit Component
 * @param props
 */
function ProductGalleryImageEdit( props ) {
	const { attributes, setAttributes, context, clientId } = props;
	const {
		selectedMediaTag,
		hideIfMissing,
		fallbackType,
		fallbackTagKey,
		fallbackMediaId,
		videoAutoplay = false,
		videoMuted = false,
		videoLoop = false,
		videoControls = true,
		audioAutoplay = false,
		audioLoop = false,
		audioControls = true,
	} = attributes;

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

	const [ mediaTags, setMediaTags ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ fallbackMedia, setFallbackMedia ] = useState( null );
	const [ mediaValidation, setMediaValidation ] = useState( null );

	// Preview state
	const [ previewMedia, setPreviewMedia ] = useState( null );
	const [ isLoadingPreview, setIsLoadingPreview ] = useState( false );

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches-ecwid-product-gallery-image',
	} );

	/**
	 * Get selected tag information
	 */
	const getSelectedTagInfo = useCallback( () => {
		if ( ! selectedMediaTag || ! mediaTags.length ) {
			return null;
		}
		return (
			mediaTags.find( ( tag ) => tag.key === selectedMediaTag ) || null
		);
	}, [ selectedMediaTag, mediaTags ] );

	/**
	 * Fetch media tags from API
	 */
	useEffect( () => {
		const fetchMediaTags = async () => {
			try {
				setIsLoading( true );
				setError( null );

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
					setMediaTags( data.data );
				} else {
					throw new Error(
						__( 'Invalid response format', 'ecwid-shopping-cart' )
					);
				}
			} catch ( err ) {
				setError( err.message );
			} finally {
				setIsLoading( false );
			}
		};

		fetchMediaTags();
	}, [] );

	/**
	 * Fetch media by tag using the same API as frontend
	 */
	const fetchMediaByTag = useCallback( async ( tagKey, productId ) => {
		if ( ! tagKey || ! productId ) {
			return null;
		}

		try {
			const response = await fetch(
				`/wp-json/peaches/v1/product-media/${ productId }/tag/${ tagKey }`,
				{
					headers: { Accept: 'application/json' },
					credentials: 'same-origin',
				}
			);

			if ( response.ok ) {
				const data = await response.json();
				if ( data && data.success && data.data ) {
					return {
						url: data.data.url,
						alt: data.data.alt || data.data.title || '',
						type:
							data.data.type ||
							determineMediaType(
								data.data.url,
								data.data.mime_type
							),
					};
				}
			}
			return null;
		} catch ( mediaError ) {
			console.error( mediaError );
			return null;
		}
	}, [] );

	/**
	 * Fetch WordPress media by ID
	 */
	const fetchWordPressMedia = useCallback( async ( mediaId ) => {
		if ( ! mediaId ) {
			return null;
		}

		try {
			const response = await fetch( `/wp-json/wp/v2/media/${ mediaId }`, {
				headers: { Accept: 'application/json' },
				credentials: 'same-origin',
			} );

			if ( response.ok ) {
				const data = await response.json();
				if ( data && data.source_url ) {
					return {
						url: data.source_url,
						alt: data.alt_text || data.title?.rendered || '',
						type: determineMediaType(
							data.source_url,
							data.mime_type
						),
					};
				}
			}
			return null;
		} catch ( error ) {
			return null;
		}
	}, [] );

	/**
	 * Load preview media when tag or product changes
	 */
	useEffect( () => {
		const loadPreviewMedia = async () => {
			if ( ! selectedMediaTag || ! productData?.id ) {
				setPreviewMedia( null );
				return;
			}

			setIsLoadingPreview( true );

			try {
				// Try to fetch primary media
				const primaryMedia = await fetchMediaByTag(
					selectedMediaTag,
					productData.id
				);

				if ( primaryMedia ) {
					setPreviewMedia( primaryMedia );
				} else if ( ! hideIfMissing && fallbackType !== 'none' ) {
					// Try fallback media
					let fallbackMedia = null;

					if ( fallbackType === 'tag' && fallbackTagKey ) {
						fallbackMedia = await fetchMediaByTag(
							fallbackTagKey,
							productData.id
						);
					} else if ( fallbackType === 'media' && fallbackMediaId ) {
						fallbackMedia =
							await fetchWordPressMedia( fallbackMediaId );
					}

					setPreviewMedia( fallbackMedia );
				} else {
					setPreviewMedia( null );
				}
			} catch ( error ) {
				setPreviewMedia( null );
			} finally {
				setIsLoadingPreview( false );
			}
		};

		loadPreviewMedia();
	}, [
		selectedMediaTag,
		productData?.id,
		fallbackType,
		fallbackTagKey,
		fallbackMediaId,
		hideIfMissing,
		fetchMediaByTag,
		fetchWordPressMedia,
	] );

	/**
	 * Load fallback media information when fallbackMediaId changes
	 */
	useEffect( () => {
		if ( fallbackMediaId && fallbackType === 'media' ) {
			fetch( `/wp-json/wp/v2/media/${ fallbackMediaId }`, {
				headers: { Accept: 'application/json' },
				credentials: 'same-origin',
			} )
				.then( ( response ) => {
					if ( response.ok ) {
						return response.json();
					}
					throw new Error( 'Failed to fetch media' );
				} )
				.then( ( media ) => {
					setFallbackMedia( media );

					// Validate fallback media if we have a selected tag
					if ( selectedMediaTag ) {
						const selectedTagInfo = getSelectedTagInfo();
						if ( selectedTagInfo ) {
							const expectedMediaType =
								getMediaTypeFromTag( selectedTagInfo );
							const validation = validateMediaType(
								media,
								expectedMediaType
							);
							setMediaValidation( validation );
						}
					}
				} )
				.catch( () => {
					setFallbackMedia( null );
					setMediaValidation( null );
				} );
		} else {
			setFallbackMedia( null );
			setMediaValidation( null );
		}
	}, [
		fallbackMediaId,
		fallbackType,
		selectedMediaTag,
		getSelectedTagInfo,
	] );

	/**
	 * Generate grouped options for media tag selection
	 */
	const getGroupedMediaTagOptions = useMemo( () => {
		if ( ! mediaTags.length ) {
			return [
				{
					label: __(
						'No media tags available',
						'ecwid-shopping-cart'
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
				label: __( 'Select a media tag…', 'ecwid-shopping-cart' ),
				value: '',
			},
		];

		// Define category order and labels
		const categoryInfo = {
			primary: __( 'Primary Content', 'ecwid-shopping-cart' ),
			secondary: __( 'Secondary Content', 'ecwid-shopping-cart' ),
			reference: __( 'Reference Materials', 'ecwid-shopping-cart' ),
			media: __( 'Rich Media', 'ecwid-shopping-cart' ),
			other: __( 'Other', 'ecwid-shopping-cart' ),
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
						const mediaTypeLabel = getMediaTypeLabel( tag );
						options.push( {
							label: `    ${ tag.label } (${ mediaTypeLabel })`,
							value: tag.key,
						} );
					} );
				}
			}
		);

		return options;
	}, [ mediaTags ] );

	/**
	 * Get fallback tag options
	 */
	const getFallbackTagOptions = useMemo( () => {
		const options = [
			{
				label: __( 'Select a fallback tag…', 'ecwid-shopping-cart' ),
				value: '',
			},
		];

		const availableTags = mediaTags.filter(
			( tag ) => tag.key !== selectedMediaTag
		);
		const selectedTagInfo = getSelectedTagInfo();

		if ( availableTags.length === 0 ) {
			options.push( {
				label: __( 'No other tags available', 'ecwid-shopping-cart' ),
				value: '',
				disabled: true,
			} );
		} else {
			const groupedByType = {};

			availableTags.forEach( ( tag ) => {
				const mediaType = getMediaTypeFromTag( tag );
				if ( ! groupedByType[ mediaType ] ) {
					groupedByType[ mediaType ] = [];
				}
				groupedByType[ mediaType ].push( tag );
			} );

			const selectedTagMediaType = getMediaTypeFromTag( selectedTagInfo );
			if ( selectedTagInfo && groupedByType[ selectedTagMediaType ] ) {
				options.push( {
					label: `── ${ __(
						'Compatible Tags',
						'ecwid-shopping-cart'
					) } (${ getMediaTypeLabel( selectedTagInfo ) }) ──`,
					value: 'compatible_header',
					disabled: true,
				} );

				groupedByType[ selectedTagMediaType ].forEach( ( tag ) => {
					options.push( {
						label: `    ${ tag.label }`,
						value: tag.key,
					} );
				} );
			}

			Object.entries( groupedByType ).forEach(
				( [ mediaType, tags ] ) => {
					if (
						! selectedTagInfo ||
						mediaType !== selectedTagMediaType
					) {
						const mediaTypeLabel = getMediaTypeLabel( {
							expectedMediaType: mediaType,
						} );

						options.push( {
							label: `── ${ mediaTypeLabel } ${ __(
								'Tags',
								'ecwid-shopping-cart'
							) } ──`,
							value: `type_${ mediaType }_header`,
							disabled: true,
						} );

						tags.forEach( ( tag ) => {
							options.push( {
								label: `    ${ tag.label }`,
								value: tag.key,
							} );
						} );
					}
				}
			);
		}

		return options;
	}, [ mediaTags, selectedMediaTag, getSelectedTagInfo ] );

	/**
	 * Handle fallback type change
	 * @param newType
	 */
	const handleFallbackTypeChange = ( newType ) => {
		setAttributes( {
			fallbackType: newType,
			fallbackTagKey: '',
			fallbackMediaId: 0,
		} );
		setMediaValidation( null );
	};

	/**
	 * Handle media selection for fallback
	 * @param media
	 */
	const handleFallbackMediaSelect = ( media ) => {
		setAttributes( { fallbackMediaId: media.id } );

		const selectedTagInfo = getSelectedTagInfo();
		if ( selectedTagInfo ) {
			const expectedMediaType = getMediaTypeFromTag( selectedTagInfo );
			const validation = validateMediaType( media, expectedMediaType );
			setMediaValidation( validation );
		}
	};

	/**
	 * Render type-specific controls
	 */
	const renderTypeSpecificControls = () => {
		const selectedTagInfo = getSelectedTagInfo();

		if ( ! selectedTagInfo ) {
			return null;
		}

		const expectedMediaType = getMediaTypeFromTag( selectedTagInfo );

		switch ( expectedMediaType ) {
			case 'video':
				return (
					<PanelBody
						title={ __( 'Video Settings', 'ecwid-shopping-cart' ) }
						initialOpen={ false }
					>
						<ToggleControl
							label={ __( 'Autoplay', 'ecwid-shopping-cart' ) }
							checked={ videoAutoplay }
							onChange={ ( value ) =>
								setAttributes( { videoAutoplay: value } )
							}
						/>
						<ToggleControl
							label={ __( 'Muted', 'ecwid-shopping-cart' ) }
							checked={ videoMuted }
							onChange={ ( value ) =>
								setAttributes( { videoMuted: value } )
							}
						/>
						<ToggleControl
							label={ __( 'Loop', 'ecwid-shopping-cart' ) }
							checked={ videoLoop }
							onChange={ ( value ) =>
								setAttributes( { videoLoop: value } )
							}
						/>
						<ToggleControl
							label={ __(
								'Show Controls',
								'ecwid-shopping-cart'
							) }
							checked={ videoControls }
							onChange={ ( value ) =>
								setAttributes( { videoControls: value } )
							}
						/>
					</PanelBody>
				);

			case 'audio':
				return (
					<PanelBody
						title={ __( 'Audio Settings', 'ecwid-shopping-cart' ) }
						initialOpen={ false }
					>
						<ToggleControl
							label={ __( 'Autoplay', 'ecwid-shopping-cart' ) }
							checked={ audioAutoplay }
							onChange={ ( value ) =>
								setAttributes( { audioAutoplay: value } )
							}
						/>
						<ToggleControl
							label={ __( 'Loop', 'ecwid-shopping-cart' ) }
							checked={ audioLoop }
							onChange={ ( value ) =>
								setAttributes( { audioLoop: value } )
							}
						/>
						<ToggleControl
							label={ __(
								'Show Controls',
								'ecwid-shopping-cart'
							) }
							checked={ audioControls }
							onChange={ ( value ) =>
								setAttributes( { audioControls: value } )
							}
						/>
					</PanelBody>
				);

			case 'document':
				return (
					<PanelBody
						title={ __(
							'Document Settings',
							'ecwid-shopping-cart'
						) }
						initialOpen={ false }
					>
						<Notice status="info" isDismissible={ false }>
							{ __(
								'Documents will be displayed as download links. PDF files may be embedded depending on browser support.',
								'ecwid-shopping-cart'
							) }
						</Notice>
					</PanelBody>
				);

			default:
				return null;
		}
	};

	/**
	 * Render fallback controls
	 */
	const renderFallbackControls = () => {
		if ( hideIfMissing ) {
			return null;
		}

		const selectedTagInfo = getSelectedTagInfo();

		return (
			<>
				<SelectControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Fallback Type', 'ecwid-shopping-cart' ) }
					value={ fallbackType }
					options={ [
						{
							label: __(
								'No fallback (show placeholder)',
								'ecwid-shopping-cart'
							),
							value: 'none',
						},
						{
							label: __(
								'Another media tag',
								'ecwid-shopping-cart'
							),
							value: 'tag',
						},
						{
							label: __(
								'Specific media file',
								'ecwid-shopping-cart'
							),
							value: 'media',
						},
					] }
					onChange={ handleFallbackTypeChange }
					help={ __(
						'What to show when the primary media tag is not available',
						'ecwid-shopping-cart'
					) }
				/>

				{ fallbackType === 'tag' && (
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __(
							'Fallback Media Tag',
							'ecwid-shopping-cart'
						) }
						value={ fallbackTagKey }
						options={ getFallbackTagOptions }
						onChange={ ( value ) => {
							if (
								! value.includes( '_header' ) &&
								! value.startsWith( 'category_' )
							) {
								setAttributes( { fallbackTagKey: value } );
							}
						} }
						help={ __(
							'Alternative media tag to try if the primary tag is not available',
							'ecwid-shopping-cart'
						) }
					/>
				) }

				{ fallbackType === 'media' && (
					<div className="components-base-control">
						<div className="components-base-control__field">
							<div className="components-base-control__label">
								{ __(
									'Fallback Media',
									'ecwid-shopping-cart'
								) }
							</div>
							<MediaUploadCheck>
								<MediaUpload
									onSelect={ handleFallbackMediaSelect }
									value={ fallbackMediaId }
									allowedTypes={
										selectedTagInfo
											? getAllowedMimeTypes(
													getMediaTypeFromTag(
														selectedTagInfo
													)
											  )
											: [ 'image', 'video', 'audio' ]
									}
									render={ ( { open } ) => (
										<Flex>
											<Button
												variant={
													fallbackMediaId
														? 'secondary'
														: 'primary'
												}
												onClick={ open }
											>
												{ fallbackMediaId
													? __(
															'Change Media',
															'ecwid-shopping-cart'
													  )
													: __(
															'Select Media',
															'ecwid-shopping-cart'
													  ) }
											</Button>
											{ fallbackMediaId && (
												<Button
													variant="tertiary"
													onClick={ () => {
														setAttributes( {
															fallbackMediaId: 0,
														} );
														setMediaValidation(
															null
														);
													} }
													isDestructive
												>
													{ __(
														'Remove',
														'ecwid-shopping-cart'
													) }
												</Button>
											) }
										</Flex>
									) }
								/>
							</MediaUploadCheck>

							{ selectedTagInfo && (
								<div className="mt-2">
									<small className="text-muted">
										{ `Expected: ${ getMediaTypeLabel(
											selectedTagInfo
										) } files` }
									</small>
								</div>
							) }

							{ mediaValidation && (
								<Notice
									status={
										mediaValidation.isValid
											? 'success'
											: 'warning'
									}
									isDismissible={ false }
									className="mt-2"
								>
									{ mediaValidation.message }
								</Notice>
							) }

							{ fallbackMedia && (
								<div className="mt-3 p-3 bg-light border rounded">
									<div className="d-flex align-items-start gap-3">
										{ fallbackMedia.source_url && (
											<div style={ { flexShrink: 0 } }>
												{ fallbackMedia.mime_type &&
													fallbackMedia.mime_type.startsWith(
														'video/'
													) && (
														<video
															src={
																fallbackMedia.source_url
															}
															style={ {
																width: '60px',
																height: '60px',
																objectFit:
																	'cover',
																borderRadius:
																	'4px',
															} }
															controls={ false }
															muted
														/>
													) }
												{ fallbackMedia.mime_type &&
													fallbackMedia.mime_type.startsWith(
														'audio/'
													) && (
														<div
															style={ {
																width: '60px',
																height: '60px',
																background:
																	'#f0f0f0',
																borderRadius:
																	'4px',
																display: 'flex',
																alignItems:
																	'center',
																justifyContent:
																	'center',
															} }
														>
															<i
																className={ `dashicons dashicons-${ getMediaTypeIcon(
																	'audio'
																) }` }
																style={ {
																	fontSize:
																		'24px',
																	color: '#666',
																} }
															></i>
														</div>
													) }
												{ ( ! fallbackMedia.mime_type ||
													( ! fallbackMedia.mime_type.startsWith(
														'video/'
													) &&
														! fallbackMedia.mime_type.startsWith(
															'audio/'
														) ) ) && (
													<img
														src={
															fallbackMedia.source_url
														}
														alt={
															fallbackMedia.alt_text ||
															'Fallback media'
														}
														style={ {
															width: '60px',
															height: '60px',
															objectFit: 'cover',
															borderRadius: '4px',
														} }
													/>
												) }
											</div>
										) }
										<div style={ { flex: 1, minWidth: 0 } }>
											<div className="text-sm font-medium text-truncate">
												{ fallbackMedia.title
													?.rendered ||
													fallbackMedia.slug ||
													'Untitled' }
											</div>
											{ fallbackMedia.alt_text && (
												<div className="text-xs text-muted mt-1">
													{ __(
														'Alt text:',
														'ecwid-shopping-cart'
													) }{ ' ' }
													{ fallbackMedia.alt_text }
												</div>
											) }
											<div className="text-xs text-muted mt-1">
												{ __(
													'Type:',
													'ecwid-shopping-cart'
												) }{ ' ' }
												{ fallbackMedia.mime_type ||
													__(
														'Unknown',
														'ecwid-shopping-cart'
													) }
											</div>
											<div className="text-xs text-muted mt-1">
												{ __(
													'ID:',
													'ecwid-shopping-cart'
												) }{ ' ' }
												{ fallbackMediaId }
											</div>
										</div>
									</div>
								</div>
							) }
						</div>
					</div>
				) }
			</>
		);
	};

	/**
	 * Render preview content
	 */
	const renderPreview = () => {
		if ( ! selectedMediaTag ) {
			return (
				<div className="text-center text-muted py-4">
					<p>
						{ __(
							'Select a media tag to preview',
							'ecwid-shopping-cart'
						) }
					</p>
				</div>
			);
		}

		const selectedTagInfo = getSelectedTagInfo();
		const expectedMediaType = selectedTagInfo
			? getMediaTypeFromTag( selectedTagInfo )
			: 'image';

		// Show loading state while fetching preview
		if ( isLoadingPreview ) {
			return (
				<div
					className="loading-container d-flex align-items-center justify-content-center text-muted"
					style={ { minHeight: '100px' } }
				>
					<div
						className="spinner-border spinner-border-sm me-2"
						role="status"
					>
						<span className="visually-hidden">
							{ __( 'Loading media…', 'ecwid-shopping-cart' ) }
						</span>
					</div>
					{ __( 'Loading media…', 'ecwid-shopping-cart' ) }
				</div>
			);
		}

		// If we have preview media, show it
		if ( previewMedia ) {
			return (
				<>
					{ createMediaElement(
						previewMedia.url,
						previewMedia.alt,
						previewMedia.type,
						attributes
					) }
				</>
			);
		}

		// Show placeholder with placehold.co images
		let placeHolderText = `${
			expectedMediaType.charAt( 0 ).toUpperCase() +
			expectedMediaType.slice( 1 )
		}+Placeholder`;

		// If hideIfMissing is true and no media, show hidden message
		if ( hideIfMissing ) {
			placeHolderText = `${
				expectedMediaType.charAt( 0 ).toUpperCase() +
				expectedMediaType.slice( 1 )
			}+Placeholder\\nhidden+if+missing`;
		}

		const placeholderUrl = `https://placehold.co/400x300?text=${ placeHolderText }`;
		return (
			<img
				className="media-element img-fluid w-100 h-100"
				style={ { objectFit: 'cover' } }
				src={ placeholderUrl }
				alt={ __( 'Media placeholder', 'ecwid-shopping-cart' ) }
			/>
		);
	};

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

				<PanelBody
					title={ __(
						'Gallery Image Settings',
						'ecwid-shopping-cart'
					) }
				>
					<Notice status="info" isDismissible={ false }>
						{ __(
							'This block displays a specific media item based on the selected tag for the current product.',
							'ecwid-shopping-cart'
						) }
					</Notice>

					{ isLoading && (
						<div className="d-flex align-items-center gap-2 my-3">
							<Spinner />
							<span>
								{ __(
									'Loading media tags…',
									'ecwid-shopping-cart'
								) }
							</span>
						</div>
					) }

					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ __(
								'Error loading media tags:',
								'ecwid-shopping-cart'
							) }{ ' ' }
							{ error }
						</Notice>
					) }

					{ ! isLoading && ! error && (
						<>
							<SelectControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								className="mt-2"
								label={ __(
									'Media Tag',
									'ecwid-shopping-cart'
								) }
								value={ selectedMediaTag }
								options={ getGroupedMediaTagOptions }
								onChange={ ( value ) => {
									if ( ! value.startsWith( 'category_' ) ) {
										setAttributes( {
											selectedMediaTag: value,
										} );
										setMediaValidation( null );
									}
								} }
								help={
									getSelectedTagInfo()?.description ||
									__(
										'Choose which media tag to display for this product',
										'ecwid-shopping-cart'
									)
								}
							/>

							{ getSelectedTagInfo() && (
								<div className="mt-2 mb-3 p-3 bg-light border rounded">
									<div className="d-flex align-items-center gap-2 mb-2">
										<i
											className={ `dashicons dashicons-${ getMediaTypeIcon(
												getMediaTypeFromTag(
													getSelectedTagInfo()
												)
											) }` }
										></i>
										<strong>
											{ getSelectedTagInfo().label }
										</strong>
										<span
											className={ `badge ${ getMediaTypeBadgeClass(
												getMediaTypeFromTag(
													getSelectedTagInfo()
												)
											) }` }
										>
											{ getMediaTypeLabel(
												getSelectedTagInfo()
											) }
										</span>
									</div>
									{ getSelectedTagInfo().description && (
										<p className="text-sm text-muted mb-0">
											{ getSelectedTagInfo().description }
										</p>
									) }
								</div>
							) }

							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Hide if media missing',
									'ecwid-shopping-cart'
								) }
								checked={ hideIfMissing }
								onChange={ ( value ) =>
									setAttributes( { hideIfMissing: value } )
								}
								help={ __(
									"Hide the entire block if the product doesn't have media for the selected tag",
									'ecwid-shopping-cart'
								) }
							/>

							{ renderFallbackControls() }
						</>
					) }
				</PanelBody>

				{ renderTypeSpecificControls() }

				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			{ productLoading && (
				<div className="text-center p-2">
					<div
						className="spinner-border spinner-border-sm"
						role="status"
					>
						<span className="visually-hidden">
							{ __(
								'Loading product data…',
								'ecwid-shopping-cart'
							) }
						</span>
					</div>
				</div>
			) }

			{ ! productLoading && (
				<div { ...blockProps }>{ renderPreview() }</div>
			) }
		</>
	);
}

export default ProductGalleryImageEdit;
