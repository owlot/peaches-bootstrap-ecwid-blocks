<?php
/**
 * Enhanced Navigation Interface
 *
 * Defines the contract for enhanced navigation functionality.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
interface Peaches_Enhanced_Navigation_Interface {
	/**
	 * Handle redirects after saving posts.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function handle_redirect_on_save();

	/**
	 * Add navigation notice on relevant post type edit screens.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function add_navigation_notice();
}
