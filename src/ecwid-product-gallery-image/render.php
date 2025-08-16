<?php
/**
 * PHP file to use when rendering the ecwid-product-gallery-image block on the server.
 *
 * Implements responsive images for both WordPress and Ecwid media sources.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 */

// Get attributes with defaults
$selected_product_id = isset($attributes['selectedProductId']) ? absint($attributes['selectedProductId']) : 0;
$selected_media_tag = isset($attributes['selectedMediaTag']) ? sanitize_text_field($attributes['selectedMediaTag']) : '';
$hide_if_missing = isset($attributes['hideIfMissing']) ? (bool) $attributes['hideIfMissing'] : true;
$fallback_type = isset($attributes['fallbackType']) ? sanitize_text_field($attributes['fallbackType']) : 'none';
$fallback_tag_key = isset($attributes['fallbackTagKey']) ? sanitize_text_field($attributes['fallbackTagKey']) : '';
$fallback_media_id = isset($attributes['fallbackMediaId']) ? absint($attributes['fallbackMediaId']) : 0;

// Media type-specific settings
$video_autoplay = isset($attributes['videoAutoplay']) ? (bool) $attributes['videoAutoplay'] : false;
$video_muted = isset($attributes['videoMuted']) ? (bool) $attributes['videoMuted'] : false;
$video_loop = isset($attributes['videoLoop']) ? (bool) $attributes['videoLoop'] : false;
$video_controls = isset($attributes['videoControls']) ? (bool) $attributes['videoControls'] : true;
$audio_autoplay = isset($attributes['audioAutoplay']) ? (bool) $attributes['audioAutoplay'] : false;
$audio_loop = isset($attributes['audioLoop']) ? (bool) $attributes['audioLoop'] : false;
$audio_controls = isset($attributes['audioControls']) ? (bool) $attributes['audioControls'] : true;

// Lightbox settings
$enable_lightbox = isset($attributes['enableLightbox']) ? (bool) $attributes['enableLightbox'] : true;
$lightbox_zoom_level = isset($attributes['lightboxZoomLevel']) ? sanitize_text_field($attributes['lightboxZoomLevel']) : 'fit';

// Early return if no media tag selected
if (empty($selected_media_tag)) {
	return;
}

// Get product ID from context or attributes
$product_detail_state = wp_interactivity_state('peaches-ecwid-product-detail');
$product_id = $selected_product_id;

if (empty($product_id) && $product_detail_state) {
	$product_id = isset($product_detail_state['productId']) ? absint($product_detail_state['productId']) : 0;
}

/**
 * Helper function to determine media type from URL and mime type
 *
 * @param string $url       Media URL
 * @param string $mime_type MIME type (optional)
 *
 * @return string Media type (image, video, audio, document)
 */
if (!function_exists('peaches_gallery_determine_media_type')) {
	function peaches_gallery_determine_media_type($url, $mime_type = '') {
		if (!empty($mime_type)) {
			if (strpos($mime_type, 'image/') === 0) {
				return 'image';
			}
			if (strpos($mime_type, 'video/') === 0) {
				return 'video';
			}
			if (strpos($mime_type, 'audio/') === 0) {
				return 'audio';
			}
			return 'document';
		}

		// Fallback to URL extension
		$extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
		$image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp');
		$video_extensions = array('mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv');
		$audio_extensions = array('mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a');

		if (in_array($extension, $image_extensions, true)) {
			return 'image';
		}
		if (in_array($extension, $video_extensions, true)) {
			return 'video';
		}
		if (in_array($extension, $audio_extensions, true)) {
			return 'audio';
		}

		return 'document';
	}
}

/**
 * Helper function to fetch WordPress media data
 *
 * @param int $media_id WordPress attachment ID
 *
 * @return array|null Media data with url, alt, type, attachment_id
 */
if (!function_exists('peaches_gallery_fetch_wordpress_media')) {
	function peaches_gallery_fetch_wordpress_media($media_id) {
		if (empty($media_id)) {
			return null;
		}

		$attachment = get_post($media_id);
		if (!$attachment || $attachment->post_type !== 'attachment') {
			return null;
		}

		$url = wp_get_attachment_url($media_id);
		if (!$url) {
			return null;
		}

		return array(
			'url' => $url,
			'lightbox_url' => wp_get_attachment_image_url($media_id, 'full') ?: $url, // Get full size for lightbox
			'alt' => get_post_meta($media_id, '_wp_attachment_image_alt', true) ?: $attachment->post_title,
			'type' => peaches_gallery_determine_media_type($url, $attachment->post_mime_type),
			'attachment_id' => $media_id,
		);
	}
}

// Try to get media data using your existing template function
$media_data = null;
$lightbox_media_url = null; // For storing full-size version

if (!empty($product_id) && !empty($selected_media_tag)) {
	// First try using your existing template function
	if (function_exists('peaches_get_product_media_url')) {
		$media_url = peaches_get_product_media_url($product_id, $selected_media_tag, 'large');
		// Get full size for lightbox
		$lightbox_media_url = peaches_get_product_media_url($product_id, $selected_media_tag, 'full');

		if (!empty($media_url)) {
			$media_data = array(
				'url' => $media_url,
				'lightbox_url' => $lightbox_media_url ?: $media_url, // Fallback to regular if full not available
				'alt' => '',
				'type' => peaches_gallery_determine_media_type($media_url),
				'attachment_id' => null,
			);
		}
	}
}

// Try fallback options if primary media not found
if (!$media_data && $fallback_type !== 'none') {
	if ($fallback_type === 'tag' && !empty($fallback_tag_key) && !empty($product_id)) {
		if (function_exists('peaches_get_product_media_url')) {
			$fallback_url = peaches_get_product_media_url($product_id, $fallback_tag_key, 'large');
			$fallback_lightbox_url = peaches_get_product_media_url($product_id, $fallback_tag_key, 'full');

			if (!empty($fallback_url)) {
				$media_data = array(
					'url' => $fallback_url,
					'lightbox_url' => $fallback_lightbox_url ?: $fallback_url,
					'alt' => '',
					'type' => peaches_gallery_determine_media_type($fallback_url),
					'attachment_id' => null,
				);
			}
		}
	} elseif ($fallback_type === 'media' && !empty($fallback_media_id)) {
		$wp_media_data = peaches_gallery_fetch_wordpress_media($fallback_media_id);
		if ($wp_media_data) {
			// For WordPress media, get the full size URL for lightbox
			$full_size_url = wp_get_attachment_image_url($fallback_media_id, 'full');
			$wp_media_data['lightbox_url'] = $full_size_url ?: $wp_media_data['url'];
			$media_data = $wp_media_data;
		}
	}
}

// If no media found and hide_if_missing is true, return empty
if (!$media_data && $hide_if_missing) {
	return;
}

// Get block wrapper attributes using computeClassName if available
$computed_class_name = '';
if (function_exists('computeClassName')) {
	$computed_class_name = computeClassName($attributes);
} elseif (function_exists('peaches_get_safe_string_attribute')) {
	$computed_class_name = peaches_get_safe_string_attribute($attributes, 'className', '');
}

$wrapper_attributes = get_block_wrapper_attributes(array(
	'class' => $computed_class_name,
	'data-wp-interactive' => 'peaches-ecwid-product-gallery-image',
	'data-wp-context' => wp_json_encode(array(
		'selectedProductId' => $product_id,
		'selectedMediaTag' => $selected_media_tag,
		'hideIfMissing' => $hide_if_missing,
		'fallbackType' => $fallback_type,
		'fallbackTagKey' => $fallback_tag_key,
		'fallbackMediaId' => $fallback_media_id,
		'videoAutoplay' => $video_autoplay,
		'videoMuted' => $video_muted,
		'videoLoop' => $video_loop,
		'videoControls' => $video_controls,
		'audioAutoplay' => $audio_autoplay,
		'audioLoop' => $audio_loop,
		'audioControls' => $audio_controls,
		'enableLightbox' => $enable_lightbox,
		'lightboxZoomLevel' => $lightbox_zoom_level,
		'isLoading' => false,
		'hasMedia' => (bool) $media_data,
		'mediaUrl' => $media_data['url'] ?? '',
		'lightboxMediaUrl' => $media_data['lightbox_url'] ?? $media_data['url'] ?? '',
		'mediaAlt' => $media_data['alt'] ?? '',
		'mediaType' => $media_data['type'] ?? 'image',
		'lightboxOpen' => false,
		'isHovering' => false,
	)),
));

if (!$media_data) {
	// Render placeholder or empty state
	?>
	<div <?php echo $wrapper_attributes; ?>>
		<div class="alert alert-info">
			<?php esc_html_e('No media found for the selected tag.', 'peaches'); ?>
		</div>
	</div>
	<?php
	return;
}

// Generate media element based on type
$media_url = esc_url($media_data['url']);
$lightbox_media_url = esc_url($media_data['lightbox_url'] ?? $media_data['url']);
$media_alt = esc_attr($media_data['alt']);
$media_type = $media_data['type'];
$attachment_id = $media_data['attachment_id'] ?? null;

// Debug: Add some debug info as HTML comments
echo '<!-- Debug: Media URL: ' . $media_url . ' -->';
echo '<!-- Debug: Lightbox URL: ' . $lightbox_media_url . ' -->';
echo '<!-- Debug: Media Type: ' . $media_type . ' -->';
echo '<!-- Debug: Enable Lightbox: ' . ($enable_lightbox ? 'true' : 'false') . ' -->';

?>
<div <?php echo $wrapper_attributes; ?>>
	<?php if ($media_type === 'image'): ?>
		<?php if ($attachment_id): ?>
			<?php
			// WordPress image with full responsive image support
			echo wp_get_attachment_image(
				$attachment_id,
				'large',
				false,
				array(
					'alt' => $media_alt,
					'class' => 'img-fluid' . ($enable_lightbox ? ' lightbox-trigger' : ''),
					'loading' => 'lazy',
					'style' => $enable_lightbox ? 'cursor: pointer;' : '',
					'data-wp-on--click' => $enable_lightbox ? 'actions.openLightbox' : '',
				)
			);
			?>
		<?php else: ?>
			<?php
			// Ecwid image - will be processed by class-ecwid-responsive-images.php
			$img_classes = 'img-fluid' . ($enable_lightbox ? ' lightbox-trigger' : '');
			?>
			<img src="<?php echo $media_url; ?>"
				 alt="<?php echo $media_alt; ?>"
				 class="<?php echo esc_attr($img_classes); ?>"
				 style="<?php echo $enable_lightbox ? 'cursor: pointer;' : ''; ?>"
				 <?php echo $enable_lightbox ? 'data-wp-on--click="actions.openLightbox"' : ''; ?>
				 loading="lazy" />
		<?php endif; ?>

	<?php elseif ($media_type === 'video'): ?>
		<video class="w-100"
			   <?php echo $video_controls ? 'controls' : ''; ?>
			   <?php echo $video_autoplay ? 'autoplay' : ''; ?>
			   <?php echo $video_muted ? 'muted' : ''; ?>
			   <?php echo $video_loop ? 'loop' : ''; ?>
			   preload="metadata">
			<source src="<?php echo $media_url; ?>" type="video/mp4">
			<?php esc_html_e('Your browser does not support the video tag.', 'peaches'); ?>
		</video>

	<?php elseif ($media_type === 'audio'): ?>
		<audio class="w-100"
			   <?php echo $audio_controls ? 'controls' : ''; ?>
			   <?php echo $audio_autoplay ? 'autoplay' : ''; ?>
			   <?php echo $audio_loop ? 'loop' : ''; ?>
			   preload="metadata">
			<source src="<?php echo $media_url; ?>" type="audio/mpeg">
			<?php esc_html_e('Your browser does not support the audio element.', 'peaches'); ?>
		</audio>

	<?php else: // document ?>
		<div class="document-container text-center p-4 border rounded">
			<div class="document-icon mb-3">
				<svg width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
					<path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
				</svg>
			</div>
			<h5 class="document-title"><?php echo $media_alt ?: __('Document', 'peaches'); ?></h5>
			<a href="<?php echo $media_url; ?>"
			   class="btn btn-primary"
			   download
			   target="_blank"
			   rel="noopener noreferrer">
				<?php esc_html_e('Download Document', 'peaches'); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php if ($enable_lightbox && in_array($media_type, array('image', 'video'), true)): ?>
		<!-- Lightbox modal structure -->
		<div class="lightbox-modal d-none"
			 data-wp-class--d-block="context.lightboxOpen"
			 data-wp-class--d-none="!context.lightboxOpen"
			 style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999;">
			<div class="lightbox-content d-flex align-items-center justify-content-center h-100 p-4"
				 data-wp-on--click="actions.closeLightbox">
				<button class="btn-close position-absolute top-0 end-0 m-3"
						data-wp-on--click="actions.closeLightbox"
						style="filter: invert(1); z-index: 10001;"
						aria-label="<?php esc_attr_e('Close', 'peaches'); ?>"></button>

				<?php if ($media_type === 'image'): ?>
					<?php
					// For lightbox, use a simple img tag with single-image srcset to prevent responsive processing
					$lightbox_img_attrs = array(
						'src="' . esc_url($lightbox_media_url) . '"',
						'alt="' . esc_attr($media_alt) . '"',
						'class="lightbox-original-image"',
						'style="max-width: 100%; max-height: 100%; object-fit: contain;"',
						'srcset="' . esc_url($lightbox_media_url) . ' 1w"', // Single image srcset
						'sizes="100vw"', // Simple sizes
						'loading="lazy"'
					);
					echo '<img ' . implode(' ', $lightbox_img_attrs) . ' />';
					?>
				<?php elseif ($media_type === 'video'): ?>
					<video controls
						   <?php echo $video_autoplay ? 'autoplay' : ''; ?>
						   <?php echo $video_muted ? 'muted' : ''; ?>
						   <?php echo $video_loop ? 'loop' : ''; ?>
						   style="max-width: 100%; max-height: 90vh;">
						<source src="<?php echo $lightbox_media_url; ?>" type="video/mp4">
						<?php esc_html_e('Your browser does not support the video tag.', 'peaches'); ?>
					</video>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
