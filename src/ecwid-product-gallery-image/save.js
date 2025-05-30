/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { computeClassName } from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

/**
 * Product Gallery Image Save Component
 *
 * Renders the frontend markup with interactivity API attributes for dynamic media display.
 * Includes media type-specific context for proper rendering.
 *
 * @param {Object} props            - Component props
 * @param {Object} props.attributes - Block attributes
 *
 * @return {JSX.Element} - Save component
 */
export default function save( { attributes } ) {
	const {
		selectedMediaTag = '',
		hideIfMissing = true,
		fallbackType = 'none',
		fallbackTagKey = '',
		fallbackMediaId = 0,
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

	// Don't render anything if no tag is selected
	if ( ! selectedMediaTag ) {
		return null;
	}

	const blockProps = useBlockProps.save( {
		className: computeClassName( attributes ),
		'data-wp-interactive': 'peaches-ecwid-product-gallery-image',
		'data-wp-context': JSON.stringify( {
			selectedMediaTag,
			hideIfMissing,
			fallbackType,
			fallbackTagKey,
			fallbackMediaId,
			// Media type-specific settings
			videoAutoplay,
			videoMuted,
			videoLoop,
			videoControls,
			audioAutoplay,
			audioLoop,
			audioControls,
			// Runtime state
			isLoading: true,
			hasMedia: false,
			mediaUrl: '',
			mediaAlt: '',
			mediaType: 'image',
			expectedMediaType: '', // Will be set by JavaScript
			fallbackMediaUrl: '',
			fallbackMediaAlt: '',
			fallbackMediaType: 'image',
		} ),
		'data-wp-init': 'callbacks.initGalleryImage',
		// Hide entire block when hideIfMissing is true and no media found
		'data-wp-class--d-none': 'state.shouldHideBlock',
	} );

	return (
		<div { ...blockProps }>
			{ /* Dynamic content container - content will be created and managed by JavaScript */ }
			<div className="gallery-media-container w-100 h-100">
				{ /* Loading state - will be hidden when content loads */ }
				<div
					className="loading-container d-flex align-items-center justify-content-center text-muted"
					data-wp-class--d-none="!state.showLoading"
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

				{ /* Media will be inserted here by JavaScript based on media type */ }
				{ /* The view.js will create appropriate elements: */ }
				{ /* - <img> for images */ }
				{ /* - <video> for videos with proper attributes */ }
				{ /* - <audio> for audio files with controls */ }
				{ /* - <a> or <iframe> for documents */ }
			</div>
		</div>
	);
}
