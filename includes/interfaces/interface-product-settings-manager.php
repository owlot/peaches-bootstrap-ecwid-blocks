<?php
/**
 * Product Settings Manager Interface
 *
 * Defines the contract for managing product settings and configurations.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface Peaches_Product_Settings_Manager_Interface
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
interface Peaches_Product_Settings_Manager_Interface {

	/**
	 * Get product ingredients by product ID.
	 *
	 * @since 0.2.0
	 *
	 * @param int $product_id Ecwid product ID
	 *
	 * @return array Array of ingredients or empty array
	 */
	public function get_product_ingredients($product_id);

	/**
	 * Get product media by tag.
	 *
	 * @since 0.2.0
	 *
	 * @param int    $product_id Product settings post ID
	 * @param string $tag_key    Media tag key
	 *
	 * @return array|null Media data or null if not found
	 */
	public function get_product_media_by_tag($product_id, $tag_key);

	/**
	 * Get all product media organized by tags.
	 *
	 * @since 0.2.0
	 *
	 * @param int $product_id Product settings post ID
	 *
	 * @return array Array of media organized by tag key
	 */
	public function get_product_media_by_tags($product_id);

	/**
	 * Get product descriptions by product ID
	 *
	 * @since 0.2.4
	 *
	 * @param int $product_id Ecwid product ID
	 *
	 * @return array Array of descriptions or empty array
	 */
	public function get_product_descriptions($product_id);

	/**
	 * Get a specific product description by type
	 *
	 * @since 0.2.4
	 *
	 * @param int    $product_id Ecwid product ID
	 * @param string $type       Description type (usage, ingredients, care, etc.)
	 *
	 * @return array|null Description data or null if not found
	 */
	public function get_product_description_by_type($product_id, $type);

	/**
	 * Get all available description types
	 *
	 * @since 0.2.4
	 *
	 * @return array Array of description types with labels
	 */
	public function get_description_types();

}
