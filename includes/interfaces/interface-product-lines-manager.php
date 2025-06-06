<?php
/**
 * Product Lines Manager Interface
 *
 * Defines the contract for product lines management functionality.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
interface Peaches_Product_Lines_Manager_Interface {
	/**
	 * Get all product lines.
	 *
	 * @since 0.2.0
	 *
	 * @return array Array of product lines.
	 */
	public function get_all_lines();

	/**
	 * Get line media by line ID.
	 *
	 * @since 0.2.0
	 *
	 * @param int $line_id Line ID.
	 *
	 * @return array Array of media items.
	 */
	public function get_line_media($line_id);

	/**
	 * Get lines for a specific product.
	 *
	 * @since 0.2.0
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array Array of line IDs.
	 */
	public function get_product_lines($product_id);
}
