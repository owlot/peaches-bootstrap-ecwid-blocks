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
 * Get product media URL by tag.
 *
 * Template function for retrieving media URLs associated with specific
 * media tags for Ecwid products.
 *
 * @since 0.2.7
 *
 * @param int    $product_id  Ecwid product ID.
 * @param string $tag_key     Media tag key.
 * @param string $size        Image size (thumbnail, medium, large, full).
 * @param bool   $fallback    Whether to use fallback images.
 *
 * @return string|null Media URL or null if not found.
 *
 * @throws InvalidArgumentException If parameters are invalid.
 */
function peaches_get_product_media_url($product_id, $tag_key, $size = 'large', $fallback = true) {
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
	if (!$product_media_manager || !method_exists($product_media_manager, 'get_product_media_url')) {
		return null;
	}

	// Get the media URL
	return $product_media_manager->get_product_media_url($product_id, $tag_key, $size, $fallback);
}

/**
 * Display product media with optional attributes.
 *
 * Template function for displaying product media with proper fallbacks
 * and HTML attributes.
 *
 * @since 0.2.7
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
	$media_url = peaches_get_product_media_url($product_id, $tag_key, $size, $fallback);

	if (!$media_url) {
		return;
	}

	// Prepare default attributes
	$default_attributes = array(
		'src' => $media_url,
		'alt' => sprintf(__('Product media: %s', 'peaches'), $tag_key),
		'loading' => 'lazy'
	);

	// Merge with provided attributes
	$final_attributes = array_merge($default_attributes, $attributes);

	// Build attribute string
	$attribute_string = '';
	foreach ($final_attributes as $key => $value) {
		$attribute_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
	}

	// Output the image tag
	echo '<img' . $attribute_string . '>';
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
	$media_url = peaches_get_product_media_url($product_id, $tag_key, 'thumbnail', false);
	return !empty($media_url);
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
