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

if (empty($product_id)) {
	return;
}

/**
 * Get expected media type from tag configuration
 *
 * @param string $tag_key Media tag key
 * @return string Expected media type
 */
if (!function_exists('peaches_gallery_get_expected_media_type')) {
	function peaches_gallery_get_expected_media_type($tag_key) {
		// Get the main plugin instance
		$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
		if (!$ecwid_blocks) {
			return 'image';
		}

		// Get the media tags manager
		$media_tags_manager = $ecwid_blocks->get_media_tags_manager();
		if (!$media_tags_manager || !method_exists($media_tags_manager, 'get_tag_expected_media_type')) {
			return 'image';
		}

		$expected_type = $media_tags_manager->get_tag_expected_media_type($tag_key);
		return $expected_type ?: 'image';
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

		// Determine type from mime type
		$mime_type = $attachment->post_mime_type;
		$media_type = 'image'; // default
		if (strpos($mime_type, 'video/') === 0) {
			$media_type = 'video';
		} elseif (strpos($mime_type, 'audio/') === 0) {
			$media_type = 'audio';
		} elseif (strpos($mime_type, 'application/pdf') === 0 || strpos($mime_type, 'text/') === 0) {
			$media_type = 'document';
		}

		return array(
			'url' => $url,
			'lightbox_url' => wp_get_attachment_image_url($media_id, 'full') ?: $url,
			'alt' => get_post_meta($media_id, '_wp_attachment_image_alt', true) ?: $attachment->post_title,
			'type' => $media_type,
			'attachment_id' => $media_id,
		);
	}
}

// Get expected media type from tag configuration (this is critical!)
$expected_media_type = peaches_gallery_get_expected_media_type($selected_media_tag);

// Try to get enhanced media data using the updated template function
$media_data = null;

if (!empty($product_id) && !empty($selected_media_tag)) {
	// First, try the enhanced function if available
	if (function_exists('peaches_get_product_media_data')) {
		// Debug: Check if we can find the WordPress post for this product
		$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
		if ($ecwid_blocks) {
			$ecwid_api = $ecwid_blocks->get_ecwid_api();
			if ($ecwid_api) {
				$post_id = $ecwid_api->get_product_post_id($product_id);
				echo '<!-- Debug: Product ID: ' . $product_id . ' -->';
				echo '<!-- Debug: WordPress Post ID: ' . ($post_id ?: 'NOT FOUND') . ' -->';

				if ($post_id) {
					$product_media = get_post_meta($post_id, '_product_media', true);
					echo '<!-- Debug: Product Media Count: ' . (is_array($product_media) ? count($product_media) : 'NOT ARRAY') . ' -->';
					if (is_array($product_media)) {
						$tag_found = false;
						foreach ($product_media as $media_item) {
							if (isset($media_item['tag_name']) && $media_item['tag_name'] === $selected_media_tag) {
								$tag_found = true;
								echo '<!-- Debug: Found Tag Media Type: ' . ($media_item['media_type'] ?? 'NOT SET') . ' -->';
								echo '<!-- Debug: Found Tag Data Keys: ' . implode(', ', array_keys($media_item)) . ' -->';

								// Add specific debugging for WordPress attachment
								if (isset($media_item['attachment_id'])) {
									$attachment_id = $media_item['attachment_id'];
									echo '<!-- Debug: Attachment ID: ' . $attachment_id . ' -->';
									$attachment = get_post($attachment_id);
									echo '<!-- Debug: Attachment Exists: ' . ($attachment ? 'YES' : 'NO') . ' -->';
									if ($attachment) {
										echo '<!-- Debug: Attachment Post Type: ' . $attachment->post_type . ' -->';
										echo '<!-- Debug: Attachment Status: ' . $attachment->post_status . ' -->';
										$mime_type = get_post_mime_type($attachment_id);
										echo '<!-- Debug: Attachment MIME: ' . ($mime_type ?: 'NONE') . ' -->';
										$url = wp_get_attachment_url($attachment_id);
										echo '<!-- Debug: Attachment URL: ' . ($url ?: 'NONE') . ' -->';
									}
								}
								break;
							}
						}
						if (!$tag_found) {
							echo '<!-- Debug: Tag "' . $selected_media_tag . '" NOT FOUND in product media -->';
						}
					}
				}
			}
		}

		$media_data = peaches_get_product_media_data($product_id, $selected_media_tag, 'large');

		// For lightbox, get full size if available
		if ($media_data && $enable_lightbox) {
			$full_size_data = peaches_get_product_media_data($product_id, $selected_media_tag, 'full');
			if ($full_size_data && !empty($full_size_data['url'])) {
				$media_data['lightbox_url'] = $full_size_data['url'];
			} else {
				$media_data['lightbox_url'] = $media_data['url'];
			}
		}

		// IMPORTANT: Override the type with the expected type from tag configuration
		if ($media_data) {
			$media_data['type'] = $expected_media_type;
		}
	}
	// Fallback to legacy function for backward compatibility
	elseif (function_exists('peaches_get_product_media_url')) {
		$media_url = peaches_get_product_media_url($product_id, $selected_media_tag, 'large');
		$lightbox_media_url = peaches_get_product_media_url($product_id, $selected_media_tag, 'full');

		if (!empty($media_url)) {
			$media_data = array(
				'url' => $media_url,
				'lightbox_url' => $lightbox_media_url ?: $media_url,
				'alt' => '',
				'type' => $expected_media_type, // Use expected type, not URL-based detection
				'attachment_id' => null,
			);
		}
	}
}

// Try fallback options if primary media not found
if (!$media_data && $fallback_type !== 'none') {
	if ($fallback_type === 'tag' && !empty($fallback_tag_key) && !empty($product_id)) {
		$fallback_expected_type = peaches_gallery_get_expected_media_type($fallback_tag_key);

		// Try enhanced function first
		if (function_exists('peaches_get_product_media_data')) {
			$media_data = peaches_get_product_media_data($product_id, $fallback_tag_key, 'large');

			// For lightbox fallback, get full size
			if ($media_data && $enable_lightbox) {
				$fallback_full_data = peaches_get_product_media_data($product_id, $fallback_tag_key, 'full');
				if ($fallback_full_data && !empty($fallback_full_data['url'])) {
					$media_data['lightbox_url'] = $fallback_full_data['url'];
				} else {
					$media_data['lightbox_url'] = $media_data['url'];
				}
			}

			// Override type with fallback tag's expected type
			if ($media_data) {
				$media_data['type'] = $fallback_expected_type;
			}
		}
		// Fallback to legacy function
		elseif (function_exists('peaches_get_product_media_url')) {
			$fallback_url = peaches_get_product_media_url($product_id, $fallback_tag_key, 'large');
			$fallback_lightbox_url = peaches_get_product_media_url($product_id, $fallback_tag_key, 'full');

			if (!empty($fallback_url)) {
				$media_data = array(
					'url' => $fallback_url,
					'lightbox_url' => $fallback_lightbox_url ?: $fallback_url,
					'alt' => '',
					'type' => $fallback_expected_type,
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
			// Enhanced responsive image using template function
			$img_classes = 'img-fluid' . ($enable_lightbox ? ' lightbox-trigger' : '');
			$img_style = $enable_lightbox ? 'cursor: pointer;' : '';
			$img_attributes = array(
				'class' => $img_classes,
				'style' => $img_style,
				'data-wp-on--click' => $enable_lightbox ? 'actions.openLightbox' : '',
			);

			// Use enhanced template function if available
			if (function_exists('peaches_generate_responsive_image_html')) {
				echo peaches_generate_responsive_image_html($media_data, $img_attributes);
			} else {
				// Fallback to simple img tag
				echo '<img src="' . $media_url . '" alt="' . $media_alt . '" class="' . esc_attr($img_classes) . '"' .
					 ($img_style ? ' style="' . esc_attr($img_style) . '"' : '') .
					 ($enable_lightbox ? ' data-wp-on--click="actions.openLightbox"' : '') .
					 ' loading="lazy" />';
			}
			?>
		<?php endif; ?>

	<?php elseif ($media_type === 'video'): ?>
		<?php
		// For iOS autoplay compatibility, video must be muted when autoplay is enabled
		$ios_autoplay_muted = $video_autoplay ? true : $video_muted;
		?>
		<video class="w-100 h-100"
			   style="object-fit: cover;"
			   <?php echo $video_controls ? 'controls' : ''; ?>
			   <?php echo $video_autoplay ? 'autoplay' : ''; ?>
			   <?php echo $ios_autoplay_muted ? 'muted' : ''; ?>
			   <?php echo $video_loop ? 'loop' : ''; ?>
			   <?php echo $video_autoplay ? 'playsinline' : ''; ?>
			   preload="metadata"
			   <?php echo $enable_lightbox ? 'data-wp-on--click="actions.openLightbox" style="cursor: pointer; object-fit: cover;"' : ''; ?>>
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
					// For lightbox, create responsive image with enhanced data if available
					if (!empty($media_data['srcset']) && function_exists('peaches_generate_responsive_image_html')) {
						// Get full-size data for lightbox with responsive support
						$lightbox_data = $media_data;
						$lightbox_data['url'] = $lightbox_media_url;

						$lightbox_attrs = array(
							'class' => 'lightbox-original-image',
							'style' => 'max-width: 100%; max-height: 100%; object-fit: contain;',
							'loading' => 'lazy'
						);
						echo peaches_generate_responsive_image_html($lightbox_data, $lightbox_attrs);
					} else {
						// Fallback to simple image for lightbox
						?>
						<img src="<?php echo esc_url($lightbox_media_url); ?>"
							 alt="<?php echo esc_attr($media_alt); ?>"
							 class="lightbox-original-image"
							 style="max-width: 100%; max-height: 100%; object-fit: contain;"
							 loading="lazy" />
						<?php
					}
					?>
				<?php elseif ($media_type === 'video'): ?>
					<video controls
						   <?php echo $video_autoplay ? 'autoplay' : ''; ?>
						   <?php echo $ios_autoplay_muted ? 'muted' : ''; ?>
						   <?php echo $video_loop ? 'loop' : ''; ?>
						   <?php echo $video_autoplay ? 'playsinline' : ''; ?>
						   style="max-width: 100%; max-height: 90vh;">
						<source src="<?php echo $lightbox_media_url; ?>" type="video/mp4">
						<?php esc_html_e('Your browser does not support the video tag.', 'peaches'); ?>
					</video>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
