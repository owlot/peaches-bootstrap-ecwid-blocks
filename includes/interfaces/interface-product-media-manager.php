<?php
/**
 * Product Media Manager Interface
 *
 * Defines the contract for managing product media across different sources.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.1
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface Peaches_Product_Media_Manager_Interface
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.1
 */
interface Peaches_Product_Media_Manager_Interface {

	/**
	 * Save product media data.
	 *
	 * @since 0.2.1
	 *
	 * @param int   $post_id    Post ID
	 * @param array $media_data Media data from form
	 *
	 * @return void
	 */
	public function save_product_media($post_id, $media_data);

	/**
	 * Get product media by tag with enhanced data.
	 *
	 * @since 0.2.1
	 *
	 * @param int    $post_id Post ID
	 * @param string $tag_key Media tag key
	 *
	 * @return array|null Media data or null if not found
	 */
	public function get_product_media_by_tag($post_id, $tag_key);

	/**
	 * Render media tag item with multiple input modes.
	 *
	 * @since 0.2.1
	 *
	 * @param string $tag_key       Tag key
	 * @param array  $tag_data      Tag data
	 * @param mixed  $current_media Current media data
	 * @param int    $post_id       Current post ID for Ecwid fallback
	 *
	 * @return void
	 */
	public function render_media_tag_item($tag_key, $tag_data, $current_media = null, $post_id = 0);
}
