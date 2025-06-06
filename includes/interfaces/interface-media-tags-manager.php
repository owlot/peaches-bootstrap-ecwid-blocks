<?php
/**
 * Media Tags Manager Interface
 *
 * Defines the contract for managing predefined media tags.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface Peaches_Media_Tags_Manager_Interface
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
interface Peaches_Media_Tags_Manager_Interface {

	/**
	 * Get all media tags.
	 *
	 * @since 0.2.0
	 *
	 * @return array Array of media tags
	 */
	public function get_all_tags();

	/**
	 * Get tags by category.
	 *
	 * @since 0.2.0
	 *
	 * @param string $category Category to filter by
	 *
	 * @return array Array of filtered tags
	 */
	public function get_tags_by_category($category);

	/**
	 * Get tag data by key.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key Tag key
	 *
	 * @return array|null Tag data or null if not found
	 */
	public function get_tag($tag_key);

	/**
	 * Check if tag exists.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key Tag key
	 *
	 * @return bool True if tag exists
	 */
	public function tag_exists($tag_key);

	/**
	 * Get expected media type for a tag.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key Tag key
	 *
	 * @return string|null Expected media type or null if not found
	 */
	public function get_tag_expected_media_type($tag_key);

	/**
	 * Validate media type against tag expectations.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key   Tag key
	 * @param string $media_url Media URL to check
	 * @param string $mime_type Optional mime type
	 *
	 * @return array Validation result with 'valid' boolean and 'message'
	 */
	public function validate_media_for_tag($tag_key, $media_url, $mime_type = '');
}
