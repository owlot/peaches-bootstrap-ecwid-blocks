<?php
/**
 * Ecwid Blocks Interface
 *
 * Defines the contract for the main plugin class.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface Peaches_Ecwid_Blocks_Interface
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
interface Peaches_Ecwid_Blocks_Interface {
	/**
	 * Activate the plugin.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function activate();

	/**
	 * Deactivate the plugin.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function deactivate();

	/**
	 * Load plugin text domain for translations.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function load_textdomain();

	/**
	 * Check if Ecwid plugin is active.
	 *
	 * @since 0.1.2
	 * @return bool True if Ecwid plugin is active, false otherwise.
	 */
	public function check_ecwid_plugin();

	/**
	 * Add settings link to plugin page.
	 *
	 * @since 0.1.2
	 * @param array $links Current plugin links.
	 * @return array Modified plugin links.
	 */
	public function add_settings_link($links);
}
