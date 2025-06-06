<?php
/**
 * Ingredients Manager Interface
 *
 * Defines the contract for ingredients management functionality.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface Peaches_Ingredients_Manager_Interface
 *
 * @since 0.2.0
 */
interface Peaches_Ingredients_Manager_Interface {
	/**
	 * Get product ingredients by product ID.
	 *
	 * @since 0.2.0
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array Array of ingredients.
	 */
	public function get_product_ingredients($product_id);

	/**
	 * Save product ingredients.
	 *
	 * @since 0.2.0
	 *
	 * @param int   $post_id Post ID.
	 * @param array $ingredients Array of ingredients.
	 *
	 * @return bool Success status.
	 */
	public function save_product_ingredients($post_id, $ingredients);
}
