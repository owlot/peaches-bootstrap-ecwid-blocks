<?php
/**
 * Product Manager Interface
 *
 * Defines the contract for product management classes.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface Peaches_Product_Manager_Interface
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
interface Peaches_Product_Manager_Interface {
	/**
	 * Register block types related to products.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function register_blocks();

	/**
	 * Initialize AJAX handlers for product data.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function init_ajax_handlers();

	/**
	 * Enqueue scripts and styles for the admin.
	 *
	 * @since 0.1.2
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_admin_scripts($hook);

	/**
	 * Enqueue scripts and styles for the frontend.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function enqueue_frontend_scripts();

	/**
	 * Generate breadcrumb navigation for product detail page.
	 *
	 * @since 0.1.2
	 * @param object $product The product object.
	 * @return string HTML for breadcrumbs.
	 */
	public function generate_breadcrumbs($product);
}
