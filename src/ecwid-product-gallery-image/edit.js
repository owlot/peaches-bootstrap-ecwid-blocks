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

const SUPPORTED_SETTINGS = {
	responsive: {
		spacings: {
			margin: true,
			padding: true,
		},
		sizes: {
			width: true,
			height: true,
		},
		display: {
			flex: true,
			block: true,
		},
		placements: {
			position: true,
			overflow: true,
		},
	},
};

/**
 * Helper Functions (defined at top to prevent lexical declaration errors)
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

	// Check for different possible property names from the API
	const mediaType =
		tagData.expectedMediaType ||
		tagData.expected_media_type ||
		tagData.mediaType;

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
 * Get media type from tag data (helper to handle different property names)
 *
 * @param {Object} tagData - Tag data object
 *
 * @return {string|null} - Media type or null if not found
 */
function getMediaTypeFromTag( tagData ) {
	if ( ! tagData ) {
		return null;
	}

	return (
		tagData.expectedMediaType ||
		tagData.expected_media_type ||
		tagData.mediaType ||
		null
	);
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
		// If no specific types defined, allow anything
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
 * Product Gallery Image Edit Component
 *
 * Renders the editor interface for selecting media tags and configuring display options.
 *
 * @param {Object} props - Component props
 *
 * @return {JSX.Element} - Edit component
 */
function ProductGalleryImageEdit( props ) {
	const { attributes, setAttributes } = props;
	const {
		selectedMediaTag,
		hideIfMissing,
		fallbackType,
		fallbackTagKey,
		fallbackMediaId,
		// Video-specific attributes
		videoAutoplay = false,
		videoMuted = false,
		videoLoop = false,
		videoControls = true,
		// Audio-specific attributes
		audioAutoplay = false,
		audioLoop = false,
		audioControls = true,
	} = attributes;

	const [ mediaTags, setMediaTags ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ fallbackMedia, setFallbackMedia ] = useState( null );
	const [ mediaValidation, setMediaValidation ] = useState( null );

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
	 *
	 * Retrieves detailed information about the currently selected media tag.
	 *
	 * @return {Object|null} - Selected tag object or null
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
	 *
	 * Retrieves available media tags grouped by category from the REST API.
	 */
	useEffect( () => {
		const fetchMediaTags = async () => {
			try {
				setIsLoading( true );
				setError( null );

				const response = await fetch(
					'/wp-json/peaches/v1/media-tags',
					{
						headers: {
							Accept: 'application/json',
						},
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
	 * Load fallback media information when fallbackMediaId changes
	 */
	useEffect( () => {
		if ( fallbackMediaId && fallbackType === 'media' ) {
			// Fetch media data from WordPress REST API
			fetch( `/wp-json/wp/v2/media/${ fallbackMediaId }`, {
				headers: {
					Accept: 'application/json',
				},
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
	 *
	 * Groups media tags by category for better organization in the dropdown.
	 *
	 * @return {Array} - Array of option objects with category grouping
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
					// Add category header (disabled option)
					options.push( {
						label: `── ${ categoryLabel } ──`,
						value: `category_${ categoryKey }`,
						disabled: true,
					} );

					// Add tags in this category
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
	 * Get fallback tag options (excluding the primary selected tag)
	 *
	 * @return {Array} - Array of fallback tag options
	 */
	const getFallbackTagOptions = useMemo( () => {
		const options = [
			{
				label: __( 'Select a fallback tag…', 'ecwid-shopping-cart' ),
				value: '',
			},
		];

		// Filter out the currently selected tag and group by media type
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
			// Group by media type for better organization
			const groupedByType = {};

			availableTags.forEach( ( tag ) => {
				const mediaType = getMediaTypeFromTag( tag );
				if ( ! groupedByType[ mediaType ] ) {
					groupedByType[ mediaType ] = [];
				}
				groupedByType[ mediaType ].push( tag );
			} );

			// Add compatible tags first (same media type)
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

			// Add other media types
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
	 *
	 * Resets fallback values when type changes.
	 *
	 * @param {string} newType - New fallback type
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
	 *
	 * @param {Object} media - Selected media object
	 */
	const handleFallbackMediaSelect = ( media ) => {
		setAttributes( { fallbackMediaId: media.id } );

		// Validate media type
		const selectedTagInfo = getSelectedTagInfo();
		if ( selectedTagInfo ) {
			const expectedMediaType = getMediaTypeFromTag( selectedTagInfo );
			const validation = validateMediaType( media, expectedMediaType );
			setMediaValidation( validation );
		}
	};

	/**
	 * Render type-specific controls
	 *
	 * Shows appropriate controls based on the expected media type.
	 *
	 * @return {JSX.Element|null} - Type-specific controls or null
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
							help={ __(
								'Start playing the video automatically when the page loads.',
								'ecwid-shopping-cart'
							) }
						/>

						<ToggleControl
							label={ __( 'Muted', 'ecwid-shopping-cart' ) }
							checked={ videoMuted }
							onChange={ ( value ) =>
								setAttributes( { videoMuted: value } )
							}
							help={ __(
								'Start with the video muted. Required for autoplay in most browsers.',
								'ecwid-shopping-cart'
							) }
						/>

						<ToggleControl
							label={ __( 'Loop', 'ecwid-shopping-cart' ) }
							checked={ videoLoop }
							onChange={ ( value ) =>
								setAttributes( { videoLoop: value } )
							}
							help={ __(
								'Restart the video when it reaches the end.',
								'ecwid-shopping-cart'
							) }
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
							help={ __(
								'Display video player controls (play, pause, volume, etc.).',
								'ecwid-shopping-cart'
							) }
						/>

						{ videoAutoplay && ! videoMuted && (
							<Notice status="warning" isDismissible={ false }>
								{ __(
									'Most browsers require videos to be muted for autoplay to work.',
									'ecwid-shopping-cart'
								) }
							</Notice>
						) }
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
							help={ __(
								'Start playing the audio automatically when the page loads.',
								'ecwid-shopping-cart'
							) }
						/>

						<ToggleControl
							label={ __( 'Loop', 'ecwid-shopping-cart' ) }
							checked={ audioLoop }
							onChange={ ( value ) =>
								setAttributes( { audioLoop: value } )
							}
							help={ __(
								'Restart the audio when it reaches the end.',
								'ecwid-shopping-cart'
							) }
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
							help={ __(
								'Display audio player controls (play, pause, volume, etc.).',
								'ecwid-shopping-cart'
							) }
						/>

						{ audioAutoplay && (
							<Notice status="info" isDismissible={ false }>
								{ __(
									'Browser autoplay policies may prevent audio from automatically playing.',
									'ecwid-shopping-cart'
								) }
							</Notice>
						) }
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
	 *
	 * Shows appropriate controls based on current fallback type.
	 *
	 * @return {JSX.Element|null} - Fallback controls or null
	 */
	const renderFallbackControls = () => {
		if ( hideIfMissing ) {
			return null;
		}

		const selectedTagInfo = getSelectedTagInfo();

		return (
			<>
				<SelectControl
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
													) }
													{ fallbackMedia.alt_text }
												</div>
											) }
											<div className="text-xs text-muted mt-1">
												{ __(
													'Type:',
													'ecwid-shopping-cart'
												) }
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
												) }
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
	 *
	 * Shows a preview of how the block will appear with current settings.
	 *
	 * @return {JSX.Element} - Preview content
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
		const mediaTypeIcon = selectedTagInfo
			? getMediaTypeIcon( getMediaTypeFromTag( selectedTagInfo ) )
			: 'format-image';
		const mediaTypeLabel = selectedTagInfo
			? getMediaTypeLabel( selectedTagInfo )
			: '';
		const expectedMediaType = selectedTagInfo
			? getMediaTypeFromTag( selectedTagInfo )
			: null;

		// Show fallback info in preview
		let fallbackInfo = '';
		if ( ! hideIfMissing ) {
			switch ( fallbackType ) {
				case 'tag':
					if ( fallbackTagKey ) {
						const fallbackTag = mediaTags.find(
							( tag ) => tag.key === fallbackTagKey
						);
						fallbackInfo =
							__( 'Fallback:', 'ecwid-shopping-cart' ) +
							( fallbackTag?.label || fallbackTagKey );
					}
					break;
				case 'media':
					if ( fallbackMediaId && fallbackMedia ) {
						fallbackInfo =
							__( 'Fallback:', 'ecwid-shopping-cart' ) +
							( fallbackMedia.title?.rendered ||
								fallbackMedia.slug ||
								'Media file' );
					}
					break;
				case 'none':
					fallbackInfo = __(
						'Fallback: Placeholder',
						'ecwid-shopping-cart'
					);
					break;
				default:
					break;
			}
		}

		return (
			<div className="gallery-image-preview">
				<div
					className="bg-light border border-2 border-dashed d-flex align-items-center justify-content-center"
					style={ { minHeight: '200px' } }
				>
					<div className="text-center text-muted">
						<i
							className={ `dashicons dashicons-${ mediaTypeIcon }` }
							style={ { fontSize: '3rem' } }
						></i>
						<p className="mb-1">
							{ selectedTagInfo?.label || selectedMediaTag }
							{ selectedTagInfo && (
								<span
									className={ `badge ${ getMediaTypeBadgeClass(
										getMediaTypeFromTag( selectedTagInfo )
									) } ms-2` }
								>
									{ mediaTypeLabel }
								</span>
							) }
						</p>
						<small>
							{ __(
								'Media will be displayed here',
								'ecwid-shopping-cart'
							) }
						</small>
						{ hideIfMissing ? (
							<small className="d-block mt-1 text-warning">
								{ __(
									'Hidden if media not found',
									'ecwid-shopping-cart'
								) }
							</small>
						) : (
							fallbackInfo && (
								<small className="d-block mt-1 text-info">
									{ fallbackInfo }
								</small>
							)
						) }

						{ /* Show type-specific preview info */ }
						{ selectedTagInfo && (
							<div className="mt-2">
								{ expectedMediaType === 'video' && (
									<div className="text-xs text-muted">
										{ videoAutoplay &&
											__(
												'▶ Autoplay',
												'ecwid-shopping-cart'
											) }
										{ videoMuted &&
											__(
												'🔇 Muted',
												'ecwid-shopping-cart'
											) }
										{ videoLoop &&
											__(
												'🔄 Loop',
												'ecwid-shopping-cart'
											) }
										{ ! videoControls &&
											__(
												'🎛️ No Controls',
												'ecwid-shopping-cart'
											) }
									</div>
								) }
								{ expectedMediaType === 'audio' && (
									<div className="text-xs text-muted">
										{ audioAutoplay &&
											__(
												'▶ Autoplay',
												'ecwid-shopping-cart'
											) }
										{ audioLoop &&
											__(
												'🔄 Loop',
												'ecwid-shopping-cart'
											) }
										{ ! audioControls &&
											__(
												'🎛️ No Controls',
												'ecwid-shopping-cart'
											) }
									</div>
								) }
							</div>
						) }
					</div>
				</div>
			</div>
		);
	};

	return (
		<>
			<InspectorControls>
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
							) }
							{ error }
						</Notice>
					) }

					{ ! isLoading && ! error && (
						<>
							<SelectControl
								label={ __(
									'Media Tag',
									'ecwid-shopping-cart'
								) }
								value={ selectedMediaTag }
								options={ getGroupedMediaTagOptions }
								onChange={ ( value ) => {
									// Don't allow selection of category headers
									if ( ! value.startsWith( 'category_' ) ) {
										setAttributes( {
											selectedMediaTag: value,
										} );
										setMediaValidation( null ); // Reset validation when tag changes
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

							{ /* Show selected tag info */ }
							{ getSelectedTagInfo() && (
								<div className="mt-2 p-3 bg-light border rounded">
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

				{ /* Render type-specific controls based on selected tag */ }
				{ renderTypeSpecificControls() }

				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			<div { ...blockProps }>{ renderPreview() }</div>
		</>
	);
}

export default ProductGalleryImageEdit;
