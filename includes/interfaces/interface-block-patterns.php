<?php
/**
 * Block Patterns Interface
 *
 * Defines the contract for block patterns classes.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface Peaches_Ecwid_Block_Patterns_Interface
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
interface Peaches_Ecwid_Block_Patterns_Interface {
	/**
	 * Register pattern category
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function register_pattern_category();

	/**
	 * Register all patterns
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public function register_patterns();
}
