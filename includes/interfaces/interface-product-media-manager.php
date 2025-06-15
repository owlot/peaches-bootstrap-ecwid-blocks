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
 * Defines methods for managing product media with proper error handling and validation.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.1
 */
interface Peaches_Product_Media_Manager_Interface {

	/**
	 * Save product media data.
	 *
	 * Saves product media with validation and error handling.
	 *
	 * @since 0.2.1
	 *
	 * @param int   $post_id    Post ID.
	 * @param array $media_data Media data from form.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException If parameters are invalid.
	 */
	public function save_product_media($post_id, $media_data);

	/**
	 * Get product media by tag with enhanced data.
	 *
	 * Retrieves media data for a specific tag with caching and validation.
	 *
	 * @since 0.2.1
	 *
	 * @param int    $post_id Post ID.
	 * @param string $tag_key Media tag key.
	 *
	 * @return array|null Media data or null if not found.
	 *
	 * @throws InvalidArgumentException If parameters are invalid.
	 */
	public function get_product_media_by_tag($post_id, $tag_key);

	/**
	 * Render media tag item with multiple input modes.
	 *
	 * Renders the complete media management interface for a specific tag.
	 *
	 * @since 0.2.1
	 *
	 * @param string $tag_key       Tag key.
	 * @param array  $tag_data      Tag data.
	 * @param mixed  $current_media Current media data.
	 * @param int    $post_id       Current post ID for Ecwid fallback.
	 *
	 * @return void
	 */
	public function render_media_tag_item($tag_key, $tag_data, $current_media = null, $post_id = 0);

	/**
	 * Get all product media for a post.
	 *
	 * Retrieves all media associated with a product settings post.
	 *
	 * @since 0.2.1
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array Array of media items.
	 *
	 * @throws InvalidArgumentException If post ID is invalid.
	 */
	public function get_all_product_media($post_id);

	/**
	 * Delete product media by tag.
	 *
	 * Removes a specific media item by tag key.
	 *
	 * @since 0.2.1
	 *
	 * @param int    $post_id Post ID.
	 * @param string $tag_key Tag key to remove.
	 *
	 * @return bool True on success, false on failure.
	 *
	 * @throws InvalidArgumentException If parameters are invalid.
	 */
	public function delete_product_media_by_tag($post_id, $tag_key);

	/**
	 * Clear media cache for a specific post.
	 *
	 * @since 0.2.1
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function clear_media_cache($post_id = null);

	/**
	 * AJAX handler for previewing media URLs.
	 *
	 * @since 0.2.1
	 *
	 * @return void
	 */
	public function ajax_preview_media_url();

	/**
	 * AJAX handler for loading Ecwid media.
	 *
	 * @since 0.2.1
	 *
	 * @return void
	 */
	public function ajax_load_ecwid_media();

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 0.2.1
	 *
	 * @return void
	 */
	public function enqueue_frontend_scripts();

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 0.2.1
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts($hook);
}
