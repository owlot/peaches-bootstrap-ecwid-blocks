<?php
/**
 * Ingredients Manager Interface
 *
 * Defines the contract for product ingredients management.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface Peaches_Ingredients_Manager_Interface
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
interface Peaches_Ingredients_Manager_Interface {
	/**
	 * Register the Product Ingredients post type.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function register_post_type();

	/**
	 * Add meta boxes to the Product Ingredients post type.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function add_meta_boxes();

	/**
	 * Save the meta box data.
	 *
	 * @since 0.1.2
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function save_meta_data($post_id);

	/**
	 * Register REST API routes for product ingredients.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function register_api_routes();

	/**
	 * Get ingredients for a specific product from the API.
	 *
	 * @since 0.1.2
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function get_product_ingredients_api($request);

	/**
	 * Get product ingredients by product ID or SKU.
	 *
	 * @since 0.1.2
	 * @param int|string $product_id The product ID or SKU.
	 * @return array Array of ingredients.
	 */
	public function get_product_ingredients($product_id);

	/**
	 * Register strings for translation.
	 *
	 * @since 0.1.2
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function register_translation_strings($post_id);

	/**
	 * AJAX handler for product search in the admin.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function ajax_search_products();
}
