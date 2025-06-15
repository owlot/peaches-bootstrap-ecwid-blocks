<?php
/**
 * Product Lines Manager Interface
 *
 * Defines the contract for product lines management functionality.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface Peaches_Product_Lines_Manager_Interface
 *
 * Defines methods for managing product lines with proper error handling and validation.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
interface Peaches_Product_Lines_Manager_Interface {

	/**
	 * Get all product lines.
	 *
	 * Retrieves all product lines with comprehensive error handling and caching.
	 *
	 * @since 0.2.0
	 *
	 * @return array Array of product lines.
	 */
	public function get_all_lines();

	/**
	 * Get line media by line ID.
	 *
	 * Retrieves media associated with a specific product line.
	 *
	 * @since 0.2.0
	 *
	 * @param int $line_id Line ID.
	 *
	 * @return array Array of media items.
	 *
	 * @throws InvalidArgumentException If line ID is invalid.
	 */
	public function get_line_media($line_id);

	/**
	 * Get lines for a specific product.
	 *
	 * Retrieves product lines associated with a given product ID.
	 *
	 * @since 0.2.0
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array Array of line IDs.
	 *
	 * @throws InvalidArgumentException If product ID is invalid.
	 */
	public function get_product_lines($product_id);

	/**
	 * Get line by slug or ID.
	 *
	 * Retrieves a product line by slug or ID with error handling.
	 *
	 * @since 0.2.0
	 *
	 * @param string|int $identifier Line slug or ID.
	 *
	 * @return WP_Term|null Product line term or null if not found.
	 */
	public function get_line($identifier);

	/**
	 * Create a new product line.
	 *
	 * Creates a new product line with validation and error handling.
	 *
	 * @since 0.2.0
	 *
	 * @param string $name Line name.
	 * @param array  $args Optional arguments (description, line_type, etc.).
	 *
	 * @return WP_Term|WP_Error|null Created term on success, WP_Error on failure.
	 */
	public function create_line($name, $args = array());

	/**
	 * Delete a product line.
	 *
	 * Deletes a product line with proper cleanup and error handling.
	 *
	 * @since 0.2.0
	 *
	 * @param int $line_id Line ID to delete.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function delete_line($line_id);
}
