<?php
/**
 * Ecwid API Interface
 *
 * Defines the contract for Ecwid API interaction classes.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface Peaches_Ecwid_API_Interface
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
interface Peaches_Ecwid_API_Interface {
	/**
	 * Get product data by product ID.
	 *
	 * @since 0.1.2
	 * @param int $product_id The product ID.
	 * @return object|null The product data or null if not found.
	 */
	public function get_product_by_id($product_id);

	/**
	 * Get product ID from slug with caching.
	 *
	 * @since 0.1.2
	 * @param string $slug The product slug.
	 * @return int The product ID or 0 if not found.
	 */
	public function get_product_id_from_slug($slug);

	/**
	 * Search for products in Ecwid store.
	 *
	 * @since 0.1.2
	 * @param string $query The search query.
	 * @param array  $options Optional search parameters.
	 * @return array Array of matching products.
	 */
	public function search_products($query, $options = array());

	/**
	 * Get categories from Ecwid store.
	 *
	 * @since 0.1.2
	 * @param array $options Optional parameters.
	 * @return array Array of categories.
	 */
	public function get_categories($options = array());

	/**
	 * Get related product IDs from a product object.
	 *
	 * Extracts related product IDs from Ecwid product data using both
	 * explicit product IDs and category-based related products.
	 *
	 * @since 0.2.6
	 * @param object $product Product object from Ecwid API.
	 * @return array Array of related product IDs.
	 */
	public function get_related_product_ids($product);
}
