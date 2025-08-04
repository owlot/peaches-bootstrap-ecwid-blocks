/**
 * Save component for ecwid-product-gallery-image block
 * Updated to include lightbox functionality
 */

import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { computeClassName } from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

export default function save( { attributes } ) {
	const {
		selectedProductId,
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
		// Lightbox settings
		enableLightbox,
		lightboxZoomLevel,
	} = attributes;

	// Don't render if no media tag is selected
	if ( ! selectedMediaTag ) {
		return null;
	}

	const blockProps = useBlockProps.save( {
		className: computeClassName( attributes ),
		'data-wp-interactive': 'peaches-ecwid-product-gallery-image',
		'data-wp-context': JSON.stringify( {
			selectedProductId: selectedProductId || null,
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
			// Lightbox settings
			enableLightbox,
			lightboxZoomLevel,
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
			// Lightbox state
			lightboxOpen: false,
			lightboxMediaType: 'image',
			isHovering: false,
		} ),
		'data-wp-init': 'callbacks.initGalleryImage',
		// Hide entire block when hideIfMissing is true and no media found
		'data-wp-class--d-none': 'state.shouldHideBlock',
	} );

	return (
		<div { ...blockProps }>
			{ /* Dynamic content container - content will be created and managed by JavaScript */ }
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
			{ /* The lightbox modal will be created dynamically when needed */ }
		</div>
	);
}
