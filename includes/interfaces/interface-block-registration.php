<?php
/**
 * Block Registration Interface
 *
 * Defines the contract for block registration classes.
 *
 * @package PeachesBootstrapEcwidBlocks
 * @since   0.3.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface Peaches_Ecwid_Block_Registration_Interface
 *
 * @package PeachesBootstrapEcwidBlocks
 * @since   0.3.0
 */
interface Peaches_Ecwid_Block_Registration_Interface {
	/**
	 * Register all block types
	 *
	 * @since 0.3.0
	 * @return void
	 */
	public function register_blocks();
}
