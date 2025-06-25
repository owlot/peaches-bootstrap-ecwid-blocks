<?php
/**
 * Handles block registration
 *
 * This class is responsible for registering all Gutenberg blocks
 * provided by the plugin.
 *
 * @package PeachesBootstrapEcwidBlocks
 * @since   0.3.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Ecwid_Block_Registration
 *
 * Implements block registration functionality.
 *
 * @package    PeachesBootstrapEcwidBlocks
 * @since      0.3.0
 * @implements Peaches_Ecwid_Block_Registration_Interface
 */
class Peaches_Ecwid_Block_Registration implements Peaches_Ecwid_Block_Registration_Interface {

	/**
	 * List of block types to register.
	 *
	 * @since  0.3.0
	 * @access private
	 * @var    array
	 */
	private $block_types = array(
		'ecwid-category',
		'ecwid-product',
		'ecwid-product-add-to-cart',
		'ecwid-product-description',
		'ecwid-product-detail',
		'ecwid-product-field',
		'ecwid-product-gallery-image',
		'ecwid-product-images',
		'ecwid-product-ingredients',
		'ecwid-product-related-products',
	);

	/**
	 * Constructor.
	 *
	 * Hooks into WordPress init action to register blocks.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action('init', array($this, 'register_blocks'), 20);
	}

	/**
	 * Register all block types.
	 *
	 * Registers the block metadata collection and individual block types.
	 *
	 * @since 0.3.0
	 */
	public function register_blocks() {
		// Register block metadata collection for better performance
		wp_register_block_metadata_collection(
			PEACHES_ECWID_PLUGIN_DIR . 'dist',
			PEACHES_ECWID_PLUGIN_DIR . 'dist/blocks-manifest.php'
		);

		// Register each block type from metadata
		foreach ($this->block_types as $block_type) {
			$block_path = PEACHES_ECWID_PLUGIN_DIR . 'build/' . $block_type;
			register_block_type_from_metadata($block_path);
		}
	}
}
