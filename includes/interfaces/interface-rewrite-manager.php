<?php
/**
 * Rewrite Manager Interface
 *
 * Defines the contract for URL rewriting management.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface Peaches_Rewrite_Manager_Interface
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
interface Peaches_Rewrite_Manager_Interface {
	/**
	 * Add Ecwid product rewrite rules.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function add_ecwid_rewrite_rules();

	/**
	 * Handle template redirect for product pages.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function product_template_redirect();

	/**
	 * Register a page template for product details.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function register_product_template();

	/**
	 * Add Open Graph tags for product pages.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function add_product_og_tags();

	/**
	 * Set additional Ecwid configuration.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function set_ecwid_config();

	/**
	 * Force rewrite rules update when needed.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function check_rewrite_rules();
}
