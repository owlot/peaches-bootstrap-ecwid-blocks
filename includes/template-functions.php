<?php
/**
 * Template Functions for Product Media
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.7
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Get product media data by tag.
 *
 * Enhanced template function for retrieving complete media data associated with
 * specific media tags for Ecwid products. Returns full media object with responsive
 * image data including srcset and sizes attributes.
 *
 * @since 0.4.3
 *
 * @param int    $product_id  Ecwid product ID.
 * @param string $tag_key     Media tag key.
 * @param string $size        Image size (thumbnail, medium, large, full).
 * @param bool   $fallback    Whether to use fallback images.
 *
 * @return array|null Complete media data array or null if not found.
 *
 * @throws InvalidArgumentException If parameters are invalid.
 */
function peaches_get_product_media_data($product_id, $tag_key, $size = 'large', $fallback = true) {
	// Validate parameters
	if (empty($product_id) || !is_numeric($product_id)) {
		return null;
	}

	if (empty($tag_key) || !is_string($tag_key)) {
		return null;
	}

	// Get the main plugin instance
	$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
	if (!$ecwid_blocks) {
		return null;
	}

	// Get the product media manager
	$product_media_manager = $ecwid_blocks->get_product_media_manager();
	if (!$product_media_manager || !method_exists($product_media_manager, 'get_product_media_data')) {
		return null;
	}

	// Get the complete media data
	return $product_media_manager->get_product_media_data($product_id, $tag_key, $size, $fallback);
}

/**
 * Get product media URL by tag (legacy function for backward compatibility).
 *
 * @since 0.2.7
 * @deprecated 0.4.3 Use peaches_get_product_media_data() instead.
 *
 * @param int    $product_id  Ecwid product ID.
 * @param string $tag_key     Media tag key.
 * @param string $size        Image size (thumbnail, medium, large, full).
 * @param bool   $fallback    Whether to use fallback images.
 *
 * @return string|null Media URL or null if not found.
 */
function peaches_get_product_media_url($product_id, $tag_key, $size = 'large', $fallback = true) {
	$media_data = peaches_get_product_media_data($product_id, $tag_key, $size, $fallback);
	return $media_data ? $media_data['url'] : null;
}

/**
 * Generate responsive image HTML for product media.
 *
 * Creates a complete img tag with responsive srcset and sizes attributes
 * based on the enhanced media data.
 *
 * @since 0.4.3
 *
 * @param array $media_data Media data from peaches_get_product_media_data.
 * @param array $attributes Additional HTML attributes for the image tag.
 *
 * @return string Complete img HTML tag or empty string if invalid data.
 */
function peaches_generate_responsive_image_html($media_data, $attributes = array(), $force_auto_sizes = false) {
	// Debug: Log function entry
	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log("peaches_generate_responsive_image_html - Entry with media_data: " . print_r($media_data, true));
		error_log("peaches_generate_responsive_image_html - Entry with attributes: " . print_r($attributes, true));
	}

	if (!is_array($media_data) || empty($media_data['url'])) {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log("peaches_generate_responsive_image_html - FAILED: Invalid media_data or empty URL");
		}
		return '';
	}

	// Safely extract and convert values to strings
	$src = isset($media_data['url']) && is_scalar($media_data['url']) ? (string)$media_data['url'] : '';
	$srcset = isset($media_data['srcset']) && is_scalar($media_data['srcset']) ? (string)$media_data['srcset'] : '';

	// Debug: Log extracted values
	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log("peaches_generate_responsive_image_html - src: $src, srcset: $srcset");
	}

	// Handle complex alt text from Ecwid (fix for all blocks)
	$alt = '';
	if (isset($media_data['alt'])) {
		if (is_string($media_data['alt'])) {
			$alt = $media_data['alt'];
		} elseif (is_scalar($media_data['alt'])) {
			$alt = (string)$media_data['alt'];
		} elseif (is_object($media_data['alt'])) {
			// Handle Ecwid's complex alt structure: alt.translated
			if (isset($media_data['alt']->translated) && is_string($media_data['alt']->translated) && !empty($media_data['alt']->translated)) {
				$alt = $media_data['alt']->translated;
			} elseif (isset($media_data['alt']->translated) && is_scalar($media_data['alt']->translated) && !empty($media_data['alt']->translated)) {
				$alt = (string)$media_data['alt']->translated;
			}
		}
	}

	// Debug: Log alt processing
	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log("peaches_generate_responsive_image_html - processed alt: '$alt'");
	}

	if (empty($src)) {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log("peaches_generate_responsive_image_html - FAILED: Empty src after processing");
		}
		return '';
	}

	// Prepare default attributes
	$default_attributes = array(
		'src' => $src,
		'alt' => $alt,
		'loading' => 'lazy',
		'decoding' => 'async',
		'class' => 'img-fluid'
	);

	// Use existing sizes from media data, or fall back to auto
	if (!empty($srcset)) {
		$default_attributes['srcset'] = $srcset;
		$default_attributes['sizes'] = $force_auto_sizes ? 'auto' : peaches_generate_smart_sizes_attribute(null, 'gallery', 400);
	}

	// Add responsive classes for Ecwid images
	if (isset($media_data['source']) && $media_data['source'] === 'ecwid') {
		$default_attributes['class'] .= ' peaches-responsive-img peaches-ecwid-img';
		$default_attributes['data-responsive-type'] = 'ecwid';
	}

	// Merge with provided attributes (this allows overriding alt, sizes, etc.)
	$final_attributes = array_merge($default_attributes, $attributes);

	// Debug: Log final attributes
	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log("peaches_generate_responsive_image_html - final_attributes: " . print_r($final_attributes, true));
	}

	// Build attribute string
	$attribute_string = '';
	foreach ($final_attributes as $key => $value) {
		if ($value !== null && $value !== '') {
			$attribute_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
		}
	}

	// Debug: Log final output
	$final_html = '<img' . $attribute_string . ' />';
	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log("peaches_generate_responsive_image_html - final_html: $final_html");
	}

	// Output the image tag
	return $final_html;
}

/**
 * Display product media with enhanced responsive support.
 *
 * Template function for displaying product media with proper responsive
 * image attributes, fallbacks and HTML attributes.
 *
 * @since 0.2.7
 * @since 0.4.3 Enhanced with responsive image support.
 *
 * @param int    $product_id  Ecwid product ID.
 * @param string $tag_key     Media tag key.
 * @param string $size        Image size (thumbnail, medium, large, full).
 * @param array  $attributes  HTML attributes for the image tag.
 * @param bool   $fallback    Whether to use fallback images.
 *
 * @return void
 */
function peaches_the_product_media($product_id, $tag_key, $size = 'large', $attributes = array(), $fallback = true) {
	$media_data = peaches_get_product_media_data($product_id, $tag_key, $size, $fallback);

	if (!$media_data) {
		return;
	}

	// For non-image media types, use simple output
	if (isset($media_data['type']) && $media_data['type'] !== 'image') {
		$simple_attributes = array_merge(
			array(
				'src' => $media_data['url'],
				'alt' => $media_data['alt'] ?? sprintf(__('Product media: %s', 'peaches'), $tag_key),
				'loading' => 'lazy'
			),
			$attributes
		);

		$attribute_string = '';
		foreach ($simple_attributes as $key => $value) {
			$attribute_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
		}

		echo '<img' . $attribute_string . ' />';
		return;
	}

	// For images, use responsive image generation
	echo peaches_generate_responsive_image_html($media_data, $attributes);
}

/**
 * Check if product has media for a specific tag.
 *
 * @since 0.2.7
 *
 * @param int    $product_id Ecwid product ID.
 * @param string $tag_key    Media tag key.
 *
 * @return bool True if media exists, false otherwise.
 */
function peaches_product_has_media($product_id, $tag_key) {
	$media_data = peaches_get_product_media_data($product_id, $tag_key, 'thumbnail', false);
	return !empty($media_data);
}

/**
 * Get available media tags.
 *
 * Template function for retrieving all available media tags.
 *
 * @since 0.2.7
 *
 * @param string $category Optional category filter.
 *
 * @return array Array of media tags.
 */
function peaches_get_available_media_tags($category = '') {
	// Get the main plugin instance
	$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
	if (!$ecwid_blocks) {
		return array();
	}

	// Get the media tags manager
	$media_tags_manager = $ecwid_blocks->get_media_tags_manager();
	if (!$media_tags_manager || !method_exists($media_tags_manager, 'get_all_tags')) {
		return array();
	}

	$all_tags = $media_tags_manager->get_all_tags();

	// Filter by category if specified
	if (!empty($category)) {
		$filtered_tags = array();
		foreach ($all_tags as $tag_key => $tag_data) {
			if (isset($tag_data['category']) && $tag_data['category'] === $category) {
				$filtered_tags[$tag_key] = $tag_data;
			}
		}
		return $filtered_tags;
	}

	return $all_tags;
}

/**
 * Safely get string value from attributes to prevent null parameter errors.
 *
 * @param array  $attributes The block attributes.
 * @param string $key        The attribute key.
 * @param string $default    Default value if key doesn't exist or isn't a string.
 *
 * @return string Safe string value.
 */
function peaches_get_safe_string_attribute($attributes, $key, $default = '') {
	if (!isset($attributes[$key])) {
		return $default;
	}

	return is_string($attributes[$key]) ? $attributes[$key] : $default;
}

// ========================================
// Enhanced Responsive Image Helper Functions
// ========================================

/**
 * Generate complete responsive img tag with all attributes
 *
 * Enhanced version that creates a complete img tag with responsive attributes
 * for both WordPress attachments and Ecwid images.
 *
 * @since 0.4.3
 *
 * @param int    $product_id   Ecwid product ID
 * @param string $tag_key      Media tag key
 * @param string $size         Image size (thumbnail, medium, large, full)
 * @param array  $attributes   Additional HTML attributes
 * @param bool   $fallback     Whether to use fallback images
 *
 * @return string Complete img HTML tag or empty string
 */
function peaches_get_responsive_product_image($product_id, $tag_key, $size = 'large', $attributes = array(), $fallback = true) {
	$media_data = peaches_get_product_media_data($product_id, $tag_key, $size, $fallback);

	if (!$media_data) {
		return '';
	}

	// For non-image media types, return empty string
	if (isset($media_data['type']) && $media_data['type'] !== 'image') {
		return '';
	}

	return peaches_generate_responsive_image_html($media_data, $attributes);
}

/**
 * Get image dimensions from media data
 *
 * @since 0.4.3
 *
 * @param array  $media_data Media data from peaches_get_product_media_data
 * @param string $size       Size to get dimensions for
 *
 * @return array Array with width and height keys, or empty array
 */
function peaches_get_media_dimensions($media_data, $size = 'large') {
	if (!is_array($media_data) || !isset($media_data['sizes'])) {
		return array();
	}

	if (isset($media_data['sizes'][$size])) {
		return array(
			'width' => $media_data['sizes'][$size]['width'] ?? 0,
			'height' => $media_data['sizes'][$size]['height'] ?? 0,
		);
	}

	return array();
}

/**
 * Check if media data supports responsive images
 *
 * @since 0.4.3
 *
 * @param array $media_data Media data from peaches_get_product_media_data
 *
 * @return bool True if responsive data is available
 */
function peaches_media_is_responsive($media_data) {
	return is_array($media_data) &&
		   !empty($media_data['responsive']) &&
		   !empty($media_data['srcset']);
}

/**
 * Get media URL for specific size
 *
 * Helper function to extract URL for a specific size from media data.
 *
 * @since 0.4.3
 *
 * @param array  $media_data Media data from peaches_get_product_media_data
 * @param string $size       Size to get URL for
 *
 * @return string URL or empty string if not found
 */
function peaches_get_media_size_url($media_data, $size = 'large') {
	if (!is_array($media_data)) {
		return '';
	}

	// Check sizes array first
	if (isset($media_data['sizes'][$size]['url'])) {
		return $media_data['sizes'][$size]['url'];
	}

	// Fallback to main URL for requested size
	if ($size === 'large' || $size === 'full') {
		return $media_data['url'] ?? '';
	}

	// If specific size not found, return main URL as fallback
	return $media_data['url'] ?? '';
}

/**
 * Generate smart responsive sizes attribute
 *
 * Wrapper function for the responsive sizes calculator.
 *
 * @since 0.4.4
 *
 * @param WP_Block $block      Optional block instance for context detection
 * @param string   $context    Block context (product, gallery, hero, etc.)
 * @param int      $max_width  Maximum width in pixels (fallback)
 *
 * @return string Complete sizes attribute value
 */
function peaches_generate_smart_sizes_attribute($block = null, $context = 'product', $max_width = 400) {
	if (!class_exists('Peaches_Responsive_Sizes_Calculator')) {
		// Fallback to simple sizes if class not available
		return $context === 'product' ? '(max-width: 575px) 50vw, (max-width: 767px) 33vw, 25vw' : 'auto';
	}

	return Peaches_Responsive_Sizes_Calculator::generate_smart_sizes_attribute($block, $context, $max_width);
}

/**
 * Generate picture element with multiple sources for enhanced responsive images
 *
 * Creates a picture element with source tags for different screen densities
 * and sizes when enhanced responsive data is available.
 *
 * @since 0.4.3
 *
 * @param array $media_data Media data from peaches_get_product_media_data
 * @param array $breakpoints Array of breakpoint definitions
 * @param array $img_attributes Attributes for the img fallback
 *
 * @return string Complete picture HTML element
 */
function peaches_generate_picture_element($media_data, $breakpoints = array(), $img_attributes = array()) {
	if (!is_array($media_data) || empty($media_data['url'])) {
		return '';
	}

	// If no responsive data available, fall back to simple img
	if (!peaches_media_is_responsive($media_data)) {
		return peaches_generate_responsive_image_html($media_data, $img_attributes);
	}

	$picture_html = '<picture>';

	// Add source elements for different breakpoints
	if (!empty($breakpoints) && !empty($media_data['srcset'])) {
		foreach ($breakpoints as $breakpoint) {
			if (isset($breakpoint['media']) && isset($breakpoint['sizes'])) {
				$picture_html .= sprintf(
					'<source media="%s" srcset="%s" sizes="%s" />',
					esc_attr($breakpoint['media']),
					esc_attr($media_data['srcset']),
					esc_attr($breakpoint['sizes'])
				);
			}
		}
	}

	// Add the fallback img element
	$picture_html .= peaches_generate_responsive_image_html($media_data, $img_attributes);
	$picture_html .= '</picture>';

	return $picture_html;
}

/**
 * Get all available sizes for a media item
 *
 * @since 0.4.3
 *
 * @param array $media_data Media data from peaches_get_product_media_data
 *
 * @return array Array of available size names
 */
function peaches_get_available_media_sizes($media_data) {
	if (!is_array($media_data) || !isset($media_data['sizes'])) {
		return array();
	}

	return array_keys($media_data['sizes']);
}

/**
 * Enhanced media output function with automatic responsive handling
 *
 * This function automatically determines the best output method based on
 * the media type and available data.
 *
 * @since 0.4.3
 *
 * @param int    $product_id   Ecwid product ID
 * @param string $tag_key      Media tag key
 * @param string $context      Context for sizing (hero, gallery, thumbnail, etc.)
 * @param array  $attributes   Additional HTML attributes
 * @param bool   $fallback     Whether to use fallback images
 *
 * @return void Outputs the media element directly
 */
function peaches_the_responsive_product_media($product_id, $tag_key, $context = 'gallery', $attributes = array(), $fallback = true) {
	$media_data = peaches_get_product_media_data($product_id, $tag_key, 'large', $fallback);

	if (!$media_data) {
		return;
	}

	$media_type = $media_data['type'] ?? 'image';

	switch ($media_type) {
		case 'image':
			echo peaches_generate_responsive_image_html($media_data, $attributes);
			break;

		case 'video':
			$video_attrs = array_merge(
				array(
					'controls' => 'controls',
					'preload' => 'metadata',
					'class' => 'w-100'
				),
				$attributes
			);

			echo '<video';
			foreach ($video_attrs as $key => $value) {
				if ($value !== null && $value !== '') {
					if (is_bool($value)) {
						echo $value ? ' ' . esc_attr($key) : '';
					} else {
						echo ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
					}
				}
			}
			echo '>';
			echo '<source src="' . esc_url($media_data['url']) . '" type="video/mp4">';
			echo __('Your browser does not support the video tag.', 'peaches');
			echo '</video>';
			break;

		case 'audio':
			$audio_attrs = array_merge(
				array(
					'controls' => 'controls',
					'preload' => 'metadata',
					'class' => 'w-100'
				),
				$attributes
			);

			echo '<audio';
			foreach ($audio_attrs as $key => $value) {
				if ($value !== null && $value !== '') {
					if (is_bool($value)) {
						echo $value ? ' ' . esc_attr($key) : '';
					} else {
						echo ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
					}
				}
			}
			echo '>';
			echo '<source src="' . esc_url($media_data['url']) . '" type="audio/mpeg">';
			echo __('Your browser does not support the audio element.', 'peaches');
			echo '</audio>';
			break;

		default: // document or other
			$link_attrs = array_merge(
				array(
					'href' => $media_data['url'],
					'target' => '_blank',
					'rel' => 'noopener noreferrer',
					'class' => 'btn btn-primary'
				),
				$attributes
			);

			echo '<a';
			foreach ($link_attrs as $key => $value) {
				echo ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
			}
			echo '>';
			echo esc_html($media_data['title'] ?? __('Download', 'peaches'));
			echo '</a>';
			break;
	}
}

/**
 * Get product ID from URL slug using the same method as product-detail block
 *
 * This is the canonical way to get product ID from the current page URL.
 * Used by both individual blocks and the product-detail block for consistency.
 *
 * @since 0.5.0
 *
 * @return int Product ID or 0 if not found
 */
function peaches_ecwid_get_product_id_from_current_url() {
	$product_slug = get_query_var('ecwid_product_slug', '');

	if (empty($product_slug)) {
		return 0;
	}

	$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
	if (!$ecwid_blocks) {
		return 0;
	}

	$ecwid_api = $ecwid_blocks->get_ecwid_api();
	if (!$ecwid_api || !method_exists($ecwid_api, 'get_product_id_from_slug')) {
		return 0;
	}

	$product_id = $ecwid_api->get_product_id_from_slug($product_slug);
	return !empty($product_id) ? absint($product_id) : 0;
}

/**
 * Get the effective product ID for cache key generation and product context
 *
 * Checks selectedProductId and id attributes first, then falls back to product detail state,
 * and finally to URL-based detection. This ensures consistent product identification across
 * different contexts (home page, product detail page, shop page, etc.) for the same product.
 *
 * @since 0.5.0
 *
 * @param array $attributes Block attributes
 * @return int Product ID or 0 if not found
 */
function peaches_ecwid_get_effective_product_id($attributes) {
	// First check if block has its own selectedProductId (most product blocks)
	$selected_product_id = isset($attributes['selectedProductId']) ? absint($attributes['selectedProductId']) : 0;

	if (!empty($selected_product_id)) {
		return $selected_product_id;
	}

	// Check for 'id' attribute (ecwid-product block uses this)
	$id = isset($attributes['id']) ? absint($attributes['id']) : 0;

	if (!empty($id)) {
		return $id;
	}

	// Check interactivity state first (fast, no API calls)
	$product_detail_state = wp_interactivity_state('peaches-ecwid-product-detail');

	if (!empty($product_detail_state['productId'])) {
		$product_id = absint($product_detail_state['productId']);
		return $product_id;
	}

	// Final fallback: get from URL using shared helper function
	$url_product_id = peaches_ecwid_get_product_id_from_current_url();
	if (!empty($url_product_id)) {
		return $url_product_id;
	}

	return 0;
}

/**
 * Backwards compatibility function for old function name
 *
 * @since 0.4.3
 * @deprecated Use peaches_product_has_media() instead
 *
 * @param int    $product_id Ecwid product ID
 * @param string $tag_key    Media tag key
 *
 * @return bool True if media exists
 */
function peaches_has_product_media($product_id, $tag_key) {
	return peaches_product_has_media($product_id, $tag_key);
}
